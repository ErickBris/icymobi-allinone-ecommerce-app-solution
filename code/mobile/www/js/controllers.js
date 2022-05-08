angular.module('starter.controllers', ['ionic-ratings'])

.controller('AppCtrl', function($scope, $state, $ionicSideMenuDelegate, $location, $sce, $rootScope, $ionicModal, AppService, UserService, CartService, appConfig, appLanguage) {
	$scope.userInfo = {};
	$scope.loginData = {};
	$scope.registerData = {};
	$scope.productData = {};
	$scope.forgotpassData = {};
	$rootScope.cartQuantity = CartService.getCartQuantity();
	$rootScope.appLanguage = appLanguage;
	if(!appConfig.ENABLE_PUSH) {
		AppService.updateAppSetting();
	}
	
	$scope.isLoggedIn = function() {
		if (UserService.isLoggedIn()) {
			$scope.userInfo = UserService.getUser();
			return true;
		}
		else {
			return false;
		}
	};
	$scope.doLogin = function(loginForm) {
		if (!loginForm.$valid) {
			return false;
		}
		UserService.login($scope.loginData).then(function(response) {
			if(response === true){
				$scope.closeModalLogin();
			}
		});
	};
	$scope.doLogout = function() {
		UserService.logout();
	};
	$scope.toggleLeftSideMenu = function() {
		$ionicSideMenuDelegate.toggleLeft();
	};
	$scope.trustAsHtml = function(string) {
		return $sce.trustAsHtml(string);
	};
	$scope.openCategory = function() {
		$state.go('tab.categories');
		return true;
	};
	$ionicModal.fromTemplateUrl('templates/modal/login.html', {
		scope: $scope
	}).then(function(modal) {
		$scope.mLogin = modal;
	});
	$scope.openModalLogin = function() {
		$scope.mLogin.show();
	};
	$scope.closeModalLogin = function() {
		$scope.mLogin.hide();
	};
	$ionicModal.fromTemplateUrl('templates/modal/register.html', {
		scope: $scope
	}).then(function(modal) {
		$scope.mRegister = modal;
	});
	$scope.openModalRegister = function() {
		$scope.mRegister.show();
		$scope.mLogin.hide();
	};
	$scope.closeModalRegister = function() {
		$scope.mRegister.hide();
	};
	// End Modal Register

	// Start Modal Forgot Password
	$ionicModal.fromTemplateUrl('templates/modal/forgot-password.html', {
		scope: $scope
	}).then(function(modal) {
		$scope.mForgotPassword = modal;
	});

	$scope.openModalForgotPassword = function() {
		$scope.mForgotPassword.show();
		$scope.mLogin.hide();
	};
	
	$scope.closeModalForgotPassword = function() {
		$scope.mForgotPassword.hide();
	};
	
	//login action
	$scope.doForgotpass = function(forgotpassForm) {
		if (!forgotpassForm.$valid) {
			return false;
		}
		UserService.forgotpass($scope.forgotpassData).then(function(response) {
			$scope.closeModalForgotPassword();
		});
		
	};

	$scope.doRegister = function(registerForm) {
		if (!registerForm.$valid || $scope.registerData.user_pass !== $scope.registerData.user_confirmpasss) {
			return false;
		}
		$scope.registerData.user_login = $scope.registerData.user_email;
		UserService.register($scope.registerData).then(function(response) {
			if(response === true){
				$scope.closeModalRegister();
			}
		});
	};

	$scope.getUserDisplay = function() {
		var user = UserService.getUser();
		if(user.first_name ==='' && user.last_name ===''){
			return user.username;
		} else{ 
			return user.first_name + ' ' + user.last_name;
		}
	};
})

.controller('HomeCtrl', function($scope, $state, $rootScope, ProductService) {
	$scope.productData = [];
	$scope.pageProduct = 1;
	$scope.canLoadMoreProductData = true;
	$scope.openProduct = function($productId) {
		angular.forEach($scope.productData, function(product, key) {
			if (product.id === $productId) {
				window.localStorage.setItem("product", JSON.stringify(product));
				$state.go('tab.product', {productId: $productId});
				return true;
			}
		});
	};
	$scope.getProductData = function($page) {
		ProductService.getProducts($page).then(function(listProduct) {
			if(!listProduct){
				$scope.canLoadMoreProductData = false;
				return false;
			}

			if (listProduct.length > 0) {
				angular.forEach(listProduct, function(product, key) {
					$scope.productData.push(product);
				});
				$scope.pageProduct = $page + 1;
				$scope.$broadcast('scroll.infiniteScrollComplete');
				$scope.$broadcast('scroll.refreshComplete');
				$scope.canLoadMoreProductData = true;
			}
			else {
				$scope.canLoadMoreProductData = false;
			}
		});
	};
	$scope.loadMoreProductData = function() {
		$scope.getProductData($scope.pageProduct);
	};
	$scope.loadMoreProductData();
})

.controller('SingleCategoryCtrl', function($scope, $stateParams, $state, ProductService) {
	$scope.pageCategory = 1;
	$scope.categoryData = [];
	$scope.canLoadMoreCategoryData = true;
	$scope.categoryName = window.localStorage.getItem("categoryName");
	$scope.openProduct = function($productId) {
		angular.forEach($scope.categoryData, function(product, key) {
			if (product.id === $productId) {
				window.localStorage.setItem("product", JSON.stringify(product));
				$state.go('tab.product', {productId: $productId});
				return true;
			}
		});
	};
	$scope.getCategoryData = function($page) {
		ProductService.getProducts($page,'&type=category&param=' + $stateParams.categoryId)
		.then(function(listProduct) {
			if(!listProduct){
				$scope.canLoadMoreCategoryData = false;
				return false;
			}

			if (listProduct.length > 0) {
				angular.forEach(listProduct, function(product, key) {
					$scope.categoryData.push(product);
				});
				$scope.pageCategory = $page + 1;
				$scope.$broadcast('scroll.infiniteScrollComplete');
				$scope.$broadcast('scroll.refreshComplete');
				$scope.canLoadMoreCategoryData = true;
			}
			else {
				$scope.canLoadMoreCategoryData = false;
			}
		});
	};
	$scope.loadMoreCategoryData = function() {
		$scope.getCategoryData($scope.pageCategory);
	};
	$scope.refreshCategoryData = function() {
		$scope.pageCategory = 1;
		$scope.categoryData = [];
		$scope.canLoadMoreCategoryData = false;
		$scope.getCategoryData(1);
	};
})

.controller('ProductCtrl', function($scope, $ionicModal, $sce, $timeout, $ionicPopup, $rootScope, $ionicSlideBoxDelegate, CartService, ReviewsService, UserService, AnimationService) {
	$scope.product = JSON.parse(window.localStorage.getItem("product"));
	$scope.productQuantity  = 100;
	if($scope.product.manage_stock) {
		$scope.productQuantity = $scope.product.stock_quantity;
		if($scope.product.in_stock && $scope.product.stock_quantity === 0) {
			$scope.productQuantity = 100;
		}
	}
	$scope.productFormData = {};
	$scope.productFormData.attributes = [];
	$scope.productFormData.quantity = "1";
	$scope.productFormData.info = $scope.product;
	$scope.reviewSubmitFormData = {};
	$scope.reviewSubmitFormData.reviewRating = 0;
	$scope.variationPrice = "";
	$scope.reviews = [];
	angular.forEach($scope.product.attributes, function(value, key) {
		$scope.productFormData.attributes[key] = "";
	});
	$scope.getProductQuanity = function(productQuantity) {
		return new Array(productQuantity);
	};
	$scope.productFormData.variation_id = -1;
	$scope.shareProduct = function() {
		var options = {
			message: $scope.product.name, // not supported on some apps (Facebook, Instagram)
			url: $scope.product.permalink,
			chooserTitle: $scope.product.name // Android only, you can override the default share sheet title
		};
		window.plugins.socialsharing.shareWithOptions(options, $scope.onSuccessShare);
	};
	$scope.addProductToCart = function() {
		//something to add product into cart
		var productId = $scope.product.id;
		var quantity = parseInt($scope.productFormData.quantity);
		var info = $scope.productFormData.info;
		if($scope.product.type === 'variable') {
			if($scope.productFormData.variation_id === -1){
				$ionicPopup.alert({
			     	title: $rootScope.appLanguage.MESSAGE_TEXT,
			     	template: 'Please select some product options before adding this product to your cart.'
			   	});
				return false;
			}
			productId = $scope.productFormData.variation_id;
			if(info.price === "") {
				$ionicPopup.alert({
			     	title: $rootScope.appLanguage.MESSAGE_TEXT,
			     	template: 'Sorry, this product is unavailable. Please choose a different combination.'
			   	});
				return false;
			}
		}
		CartService.addProductToCart(productId, quantity, info);
		AnimationService.moveProductToCartAnimation();
	   	$timeout(function() {
	     	AnimationService.actionByClass('shoppingcart', 'swing');
	     	$rootScope.cartQuantity = CartService.getCartQuantity();
	  	}, 1000);
		
		return true;
	};

	$scope.onSuccessShare = function() {
		//something after success share
	};

	// Modal info
	$ionicModal.fromTemplateUrl('templates/modal/product-info.html', {
		scope: $scope
	}).then(function(modal) {
		$scope.mInfo = modal;
	});

	$scope.closeModalInfo = function() {
		$scope.mInfo.hide();
	};
	$scope.openModalInfo = function() {
		$scope.mInfo.show();
	};
	// End Modal info

	// Start Modal Add Review
	$ionicModal.fromTemplateUrl('templates/modal/add-review.html', {
		scope: $scope
	}).then(function(modal) {
		$scope.mAddReview = modal;
	});

	$scope.closeModalCloseReview = function() {
		$scope.mAddReview.hide();
	};
	$scope.openModalAddReview = function() {
		$scope.mAddReview.show();
	};
	// End Modal Add Review


	$scope.loadReview = function(){
		if($scope.reviews.length <= 0){
			ReviewsService.getProductReviews($scope.product.id).then(function(reviews) {
				$scope.reviews = reviews;
			});
		}
	};

	// render rating
	$scope.renderRatting = function($rate){
		$rate = $rate * 20;
		return $sce.trustAsHtml('<div class="rate"><span style="width: '+$rate+'%;"></span></div>');
	};

	$scope.ratingsObject = {
		iconOn: 'ion-ios-star',
		iconOff: 'ion-ios-star-outline',
		iconOnColor: '#eab12a',
		iconOffColor: '#eab12a',
		rating: 0,
		minRating: 0,
		readOnly:false,
		callback: function(rating) {
		  	// $scope.ratingsCallback(rating);
			$scope.reviewSubmitFormData.reviewRating = rating;
		}
	};
	
	$scope.submitProductReview = function(reviewSubmitForm) {
		if (!reviewSubmitForm.$valid) {
			return false;
		}
		var productId = $scope.product.id;
		var userId = UserService.getUserId();
		var firstName = $scope.reviewSubmitFormData.first_name;
		var lastName = $scope.reviewSubmitFormData.last_name;
		var comment = $scope.reviewSubmitFormData.comment;
		var rating = $scope.reviewSubmitFormData.reviewRating;
		var email = $scope.reviewSubmitFormData.user_email;
		if(rating === 0){
			$ionicPopup.alert({
				title: $rootScope.appLanguage.MESSAGE_TEXT,
				template: 'Please select a rating'
			});
			return false;
		}
		ReviewsService.submitProductReview(productId, userId, firstName, lastName, email, comment, rating);
		$ionicPopup.alert({
			title: $rootScope.appLanguage.MESSAGE_TEXT,
			template: 'Your comment is awaiting approval'
		});
		$scope.closeModalCloseReview();
	};
	
	$scope.isLoggedIn = function() {
		if (UserService.isLoggedIn()) {
			return true;
		}
		else {
			return false;
		}
	};

	$scope.updateProductAttribute = function($index, $value) {
		$scope.productFormData.attributes[$index] = $value;
		//find variations
		$scope.productFormData.variation_id = -1;
		angular.forEach($scope.product.variations, function(variation, variationKey) {
			var flag = true;
			angular.forEach($scope.productFormData.attributes, function(value, key) {
				if (value !== variation.attributes[key].option) {
					flag = false;
				}
			});
			if (flag === true) {
				$scope.productFormData.variation_id = variation.id; //update new product variation id
				$scope.variationPrice = variation.price;
				$scope.productFormData.info = variation;
				$scope.productFormData.info.name = $scope.product.name;
				$scope.productFormData.info.product_id = $scope.product.id;
				//update product quantity by variation quantity
				if(variation.manage_stock) {
					$scope.productQuantity = variation.stock_quantity;
				}
				$scope.product.in_stock = variation.in_stock;
				$scope.productFormData.quantity = "1";
				//check variation image in product image
				var flagVariationImage = false;
				if(variation.image) {
					angular.forEach($scope.product.images, function(image, key) {
						if (image.id === variation.image[0].id) {
							flagVariationImage = true;
							$ionicSlideBoxDelegate.slide(key);
						}
					});
					if(!flagVariationImage) {
						$scope.product.images.push(variation.image[0]);
						$ionicSlideBoxDelegate.update();
						$timeout(function(){$ionicSlideBoxDelegate.slide($scope.product.images.length - 1);}, 100);
					}
				}
				if(variation.images) {
					var flagAddNewImage = false;
					angular.forEach(variation.images, function(variationImage, variationKey) {
						flagVariationImage = false;
						angular.forEach($scope.product.images, function(image, key) {
							if (image.id === variationImage.id) {
								flagVariationImage = true;
								$ionicSlideBoxDelegate.slide(key);
							}
						});
						if(!flagVariationImage) {
							$scope.product.images.push(variationImage);
							$ionicSlideBoxDelegate.update();
							flagAddNewImage = true;
						}
					});
					if(flagAddNewImage) {
						$timeout(function(){$ionicSlideBoxDelegate.slide($scope.product.images.length - 1);}, 100);	
					}
				}
				
			}
		});
		
	};
	$scope.$on("$ionicView.beforeEnter", function(event, data) {
		//something before enter the view
	});
})

.controller('CategoriesCtrl', function($scope, $state, ListCategoryService) {
	$scope.categoryData = [];
	$scope.openCategory = function($categoryId) {
		angular.forEach($scope.categoryData, function(category, key) {
			if (category.id === $categoryId) {
				window.localStorage.setItem("categoryName", category.name);
				$state.go('tab.category', {categoryId: $categoryId});
				return true;
			}
		});
	};
	$scope.getCategoryListData = function() {
		ListCategoryService.getListCategory().then(function(listCategory) {
			$scope.categoryData = listCategory;
		});
	};
	//call function init data, use beforenter event if you want to clear the cache data when back
	$scope.getCategoryListData();
})

.controller('CartCtrl', function($scope, $state, $timeout, UserService, CartService, AnimationService) {
	$scope.cartInfo = CartService.getCartInfo();
	$scope.cartQuantity = CartService.getCartQuantity();
	$scope.cartTotal = CartService.getCartTotal();
	$scope.cartEmpty = false;

	if($scope.cartQuantity == 0){
		$scope.cartEmpty = true;
	}

	$scope.openCheckout = function() {
		if(UserService.isLoggedIn()) {
			$state.go('tab.checkout-billing');
		}
		else {
			$state.go('tab.checkout');
		}
	};
	$scope.removeProductFromCart = function($productId) {
		AnimationService.action('cart_item_'+$productId, 'bounceOut', 2000);
		$timeout(function(){
	        CartService.removeProductFromCart($productId);
			$scope.cartInfo = CartService.getCartInfo();
			$scope.cartQuantity = CartService.getCartQuantity();
			$scope.cartTotal = CartService.getCartTotal();

			if($scope.cartQuantity==0){
				$timeout(function(){
					$scope.cartEmpty = true;
				},600);
			}

  		},200);

	};

})

.controller('CheckoutCtrl', function($scope, $state, UserService) {
	$scope.loginData = {};
	$scope.doLogin = function(loginForm) {
		if (!loginForm.$valid) {
			return false;
		}
		UserService.login($scope.loginData).then(function(response) {
			if(response === true){
				$scope.nextStep();
			}
		});
	};
	$scope.nextStep = function(){
		$state.go('tab.checkout-billing');
	};
	$scope.$on("$ionicView.beforeEnter", function(event, data) {
		
	});
})

.controller('CheckoutBillingCtrl', function($scope, $state, $ionicPopup, $rootScope, AppService, UserService, OrderService) {
	$scope.userInfo = UserService.getUser();
	$scope.listCountry = AppService.getListCountry().then(function(listCountry) {
		$scope.listCountry = listCountry;
	});
	$scope.listState = {};
	$scope.changeCountry = function() {
		//remove state default
		$scope.userInfo.billing.state = '';
	};
	$scope.checkCountryHasState = function() {
		var result = false;
		angular.forEach($scope.listCountry, function(country, key) {
			if (country.id === $scope.userInfo.billing.country) {
				if(country.state && country.state.length !== 0) {
					$scope.listState = country.state;
					result = true;
				}
			}
		});
		return result;
	};
	$scope.nextStep = function() {
		OrderService.updateOrderInfoBilling($scope.userInfo.billing);
		if(OrderService.validateOrderInfoBilling()) {
			$scope.userInfo.shipping = $scope.userInfo.billing;
			UserService.updateUser($scope.userInfo);
			$state.go('tab.checkout-note');
		}
		else {
			$ionicPopup.alert({
				title: $rootScope.appLanguage.MESSAGE_TEXT,
				template: $rootScope.appLanguage.REQUIRED_ERROR_TEXT
			});
		}
	};
})

.controller('CheckoutNoteCtrl', function($scope, $state, $ionicPopup, $rootScope, AppService, UserService, OrderService) {
	$scope.userInfo = UserService.getUser();
	$scope.orderInfo = {};
	$scope.orderInfo.orderNote = "";
	$scope.listCountry = {};
	AppService.getListCountry().then(function(listCountry){
		$scope.listCountry = listCountry;
	});
	$scope.listState = {};
	$scope.changeCountry = function() {
		//remove state default
		$scope.userInfo.shipping.state = '';
	};
	$scope.checkCountryHasState = function() {
		var result = false;
		angular.forEach($scope.listCountry, function(country, key) {
			if (country.id === $scope.userInfo.shipping.country) {
				if(country.state.length !== 0) {
					$scope.listState = country.state;
					result = true;
				}
			}
		});
		return result;
	};
	$scope.nextStep = function(){
		OrderService.updateOrderInfoShipping($scope.userInfo.shipping);
		OrderService.updateOrderInfoCustomerNote($scope.orderInfo.orderNote);
		if(OrderService.validateOrderInfoShipping()) {
			UserService.updateUser($scope.userInfo);
			$state.go('tab.checkout-payment');
		}
		else {
			$ionicPopup.alert({
				title: $rootScope.appLanguage.MESSAGE_TEXT,
				template: $rootScope.appLanguage.REQUIRED_ERROR_TEXT
			});
		}
	};
})

.controller('CheckoutPaymentCtrl', function($scope, $state, $ionicHistory, $ionicPopup, $rootScope, OrderService, UserService, CartService, appConfig, $injector) {
	$scope.orderInfo = {};
	$scope.orderInfo.payment = '';
	$scope.orderInfo.shipping = '';
	$scope.orderInfo.server_cart_id = '';
	$scope.cartInfo = CartService.getCartInfo();
	$scope.cartSubTotal = CartService.getCartTotal();
	$scope.cartTotal = 0;
	$scope.listPayment = [];
	$scope.listShipping = [];
	$scope.userInfo = UserService.getUser();
	$scope.orderInfo.coupon = "";
	$scope.orderShipping = JSON.parse(OrderService.getOrderInfo().shipping);
	$scope.shippingCost = 0;
	$scope.discountCost = 0;
	OrderService.createServeCart($scope.orderInfo.coupon, $scope.cartInfo.products, $scope.orderShipping.country, $scope.orderShipping.state, $scope.orderShipping.postcode).then(function(result) {
		$scope.listShipping = result.shipping_methods;
		var firstshipping = '';
		for(firstshipping in $scope.listShipping) break;
		$scope.orderInfo.shipping = $scope.listShipping[firstshipping].id;
		if($scope.listShipping[firstshipping].cost !== undefined) {
			$scope.shippingCost = $scope.listShipping[firstshipping].cost;
		}
		$scope.cartTotal = parseFloat($scope.shippingCost) + OrderService.getOrderGrandTotal();
		var firstPayment = '';
		for(firstPayment in result.payment_methods) break;
		$scope.orderInfo.payment = firstPayment;
		$scope.listPayment = result.payment_methods;
		$scope.orderInfo.server_cart_id = result.server_cart_id;
	});
	$scope.changeShippingMethod = function() {
		angular.forEach($scope.listShipping, function(shipping, key) {
			if (shipping.id === $scope.orderInfo.shipping) {
				if(shipping.cost !== undefined && shipping.cost !== "0" && shipping.cost !== "") {
					$scope.shippingCost = shipping.cost;
					$scope.cartTotal = parseFloat($scope.shippingCost) + OrderService.getOrderGrandTotal();
				}
				else {
					$scope.shippingCost = 0;
					$scope.cartTotal = OrderService.getOrderGrandTotal();
				}
				
			}
		});
	},
	$scope.applyCoupon = function() {
		//add product in cart to order
		var line_items = [];
		angular.forEach($scope.cartInfo.products, function(product, key) {
			if(product[2].product_id !== undefined){
				line_items.push({product_id: product[2].product_id, variation_id: product[0], quantity: product[1]});
			}
			else {
				line_items.push({product_id: product[0], quantity: product[1]});
			}
		});
		OrderService.updateOrderInfoLineItems(line_items);
		OrderService.updateOrderInfoCoupon($scope.orderInfo.coupon);
		OrderService.updateServerCartId($scope.orderInfo.server_cart_id);
		OrderService.getCartPrice().then(function(response) {
			$scope.cartTotal = parseFloat($scope.shippingCost) + OrderService.getOrderGrandTotal();
			$scope.discountCost = OrderService.getOrderDiscountCost();
		});
	},
	$scope.createOrder = function() {
		//update server cart id
		OrderService.updateServerCartId($scope.orderInfo.server_cart_id);
		//update grand total with shipping cost
		OrderService.setOrderGrandTotal($scope.cartTotal);
		//get payment method title
		angular.forEach($scope.listPayment, function(payment, key) {
			if (payment.id === $scope.orderInfo.payment) {
				var data = {};
				if(payment.id === 'bacs') {
					data = payment.accounts;
				}
				OrderService.updateOrderInfoPaymentMethod($scope.orderInfo.payment, payment.title, data);
			}
		});
		//get shipping method info
		angular.forEach($scope.listShipping, function(shipping, key) {
			if (shipping.id === $scope.orderInfo.shipping) {
				var shipping_lines = [];
				var carrier_id = "";
				if(shipping.carrier_id !== undefined) {
					carrier_id = shipping.carrier_id;
				}
				shipping_lines.push({'method_id': shipping.id, 'method_title': shipping.title, 'total': shipping.cost, 'carrier_id': carrier_id});
				OrderService.updateOrderInfoShippingMethod(shipping_lines);
			}
		});
		//add product in cart to order
		var line_items = [];
		angular.forEach($scope.cartInfo.products, function(product, key) {
			if(product[2].product_id !== undefined){
				line_items.push({product_id: product[2].product_id, product_name: product[2].name, variation_id: product[0], quantity: product[1]});
			}
			else {
				line_items.push({product_id: product[0], product_name: product[2].name, quantity: product[1]});
			}
		});
		// Add Device Token
		var device_token = window.localStorage.getItem("deviceToken");
		if(typeof device_token !== 'undefined'){
			OrderService.updateDeviceToken(device_token);
		}

		OrderService.updateOrderInfoLineItems(line_items);
		OrderService.updateOrderInfoCoupon($scope.orderInfo.coupon);
		OrderService.updateOrderInfoCustomerId($scope.userInfo.id);
		if(appConfig.ENABLE_PAYPAL_PLUGIN === true && $scope.orderInfo.payment === 'paypal') {
			try {
				var PaypalService = $injector.get('PaypalService');
				PaypalService.init();
			}
			catch(err) {
				$ionicPopup.alert({
					title: $rootScope.appLanguage.MESSAGE_TEXT,
					template: 'Paypal plugin not found'
				});
			}
			return true;
			
		}
		if(appConfig.ENABLE_STRIPE_PLUGIN === true && $scope.orderInfo.payment === 'stripe') {
			try {
				var StripeService = $injector.get('StripeService');
				StripeService.init();
			}
			catch(err) {
				$ionicPopup.alert({
					title: $rootScope.appLanguage.MESSAGE_TEXT,
					template: 'Stripe plugin not found'
				});
			}
			return true;
			
		}
		OrderService.createOrder().then(function(response) {
			if(response === true) {
				CartService.clearCart();
				OrderService.clearOrderInfo();
				$ionicHistory.nextViewOptions({
					disableBack: true
				});
				$state.go('tab.checkout-success');
			}
			else {
				
			}
		});
	};
})

.controller('CheckoutSuccessCtrl', function($scope, $state, $rootScope, $ionicHistory, OrderService) {
	$scope.orderReceivedInfo = OrderService.getOrderReceivedInfo();
	$scope.subTotal = 0;
	angular.forEach($scope.orderReceivedInfo.line_items, function(product, key) {
		$scope.subTotal = $scope.subTotal + parseFloat(product.subtotal);
	});
	$scope.$on("$ionicView.beforeEnter", function(event, data){
		$scope.orderReceivedInfo = OrderService.getOrderReceivedInfo();
		$scope.subTotal = 0;
		angular.forEach($scope.orderReceivedInfo.line_items, function(product, key) {
			$scope.subTotal = $scope.subTotal + parseFloat(product.subtotal);
		});
	});	
	$scope.goHomePage = function() {
		OrderService.updateOrderReceivedInfo({}); 
		$ionicHistory.nextViewOptions({
			disableBack: true
		});
		$ionicHistory.clearCache();
		$rootScope.hideTabs = '';
		$state.go('tab.home');
	};
})


.controller('ContactUsCtrl', function($scope, $state, $ionicModal, AppService) {
	$scope.appSettings = AppService.getAppSetting();
	// consol
	$scope.info_position = $scope.appSettings.contact_map_lat + ',' + $scope.appSettings.contact_map_lng;

	$scope.$on('mapInitialized', function(event, map) {
		$scope.map = map;
	});
	
	$scope.showInfoWindow = function($infoWindowId) {
		$scope.map.showInfoWindow($infoWindowId);
	};

	$ionicModal.fromTemplateUrl('templates/modal/contact.html', {
		scope: $scope
	}).then(function(modal) {
		$scope.mContact = modal;
	});
	$scope.openModalContact = function() {
		$scope.mContact.show();
	};
	$scope.closeModalContact = function() {
		$scope.mContact.hide();
	};
})

.controller('AccountCtrl', function($scope, $ionicPopup, $rootScope, UserService, AppService) {
	$scope.listCountry = [];
	$scope.userInfo = UserService.getUser();
	$scope.userInfo.password = "";
	$scope.userInfo.newpassword = "";
	$scope.userInfo.confirmnewpassword = "";
	AppService.getListCountry().then(function(listCountry){
		$scope.listCountry = listCountry;
	});
	$scope.listState = {};
	$scope.changeBillingCountry = function() {
		//remove state default
		$scope.userInfo.billing.state = '';
	};
	$scope.checkBillingCountryHasState = function() {
		var result = false;
		angular.forEach($scope.listCountry, function(country, key) {
			if (country.id === $scope.userInfo.billing.country) {
				if(country.state.length !== 0) {
					$scope.listState = country.state;
					result = true;
				}
			}
		});
		return result;
	};
	$scope.changeShippingCountry = function() {
		//remove state default
		$scope.userInfo.shipping.state = '';
	};
	$scope.updateUserShippingInfo = function(shippingInfoForm) {
		if (!shippingInfoForm.$valid) {
			return false;
		}
		UserService.updateUser($scope.userInfo);
		$ionicPopup.alert({
			title: $rootScope.appLanguage.MESSAGE_TEXT,
			template: 'Account details changed successfully'
		});
	};
	$scope.updateUserBillingInfo = function(billingInfoForm) {
		if (!billingInfoForm.$valid) {
			return false;
		}
		UserService.updateUser($scope.userInfo);
		$ionicPopup.alert({
			title: $rootScope.appLanguage.MESSAGE_TEXT,
			template: 'Account details changed successfully'
		});
	};
	$scope.checkShippingCountryHasState = function() {
		var result = false;
		angular.forEach($scope.listCountry, function(country, key) {
			if (country.id === $scope.userInfo.shipping.country) {
				if(country.state.length !== 0) {
					$scope.listState = country.state;
					result = true;
				}
			}
		});
		return result;
	};
	$scope.doEditAccount = function(editAccountForm) {

		if (!editAccountForm.$valid) {
			return false;
		}else if( $scope.userInfo.password !== ''){
			if( $scope.userInfo.newpassword === '' ){
				editAccountForm.new_password.$error.required = true;
				return false;
			}else if($scope.userInfo.newpassword !== $scope.userInfo.confirmnewpassword){
				editAccountForm.confirm_new_password.$error.required = true;
				return false;
			}
		}

		var editAccountFormData = {};
		editAccountFormData.user_firstname = $scope.userInfo.first_name;
		editAccountFormData.user_lastname = $scope.userInfo.last_name;
		editAccountFormData.user_email = $scope.userInfo.email;
		editAccountFormData.user_pass = $scope.userInfo.password;
		editAccountFormData.user_new_password = $scope.userInfo.newpassword;
		editAccountFormData.user_confirmation = $scope.userInfo.confirmnewpassword;
		editAccountFormData.user_id = $scope.userInfo.id;
		UserService.editAccount(editAccountFormData).then(function(response) {
			if(response === true){
				UserService.updateUser($scope.userInfo);
			}
		});
	};
})


.controller('OrdersCtrl', function($scope, $state, UserService, OrderService) {
	$scope.listOrderData = [];
	$scope.pageOrder = 1;
	$scope.canLoadMoreOrderData = false;
	$scope.openSingleOrder = function($orderId) {
		angular.forEach($scope.listOrderData, function(order, key) {
			if (order.id === $orderId) {
				window.localStorage.setItem("singleOrder", JSON.stringify(order));
				$state.go('tab.order', {orderId: $orderId});
				return true;
			}
		});
	};
	$scope.getListOrderData = function($page) {
		OrderService.getListOrder(UserService.getUserId(),$page).then(function(listOrder) {
			if (listOrder.length > 0) {
				//update sample html to root
				angular.forEach(listOrder, function(order, key) {
					$scope.listOrderData.push(order);
				});
				$scope.pageOrder = $page + 1;
				$scope.$broadcast('scroll.infiniteScrollComplete');
				$scope.$broadcast('scroll.refreshComplete');
				$scope.canLoadMoreOrderData = true;
			}
			else {
				$scope.canLoadMoreOrderData = false;
			}
			
		});
	};
	$scope.loadMoreListOrderData = function() {
		$scope.getListOrderData($scope.pageOrder);
	};
	$scope.getListOrderData($scope.pageOrder);
})

.controller('NotificationCtrl', function($scope, AppService) {
	$scope.notification = JSON.parse(window.localStorage.getItem("appNotificationPayload"));
	if($scope.notification.type === 'text'){
		$scope.notification.content = $scope.notification.content.replace(/\\"/g, '"');
	}
})

.controller('OrderDetailCtrl', function($scope) {
	$scope.order = JSON.parse(window.localStorage.getItem("singleOrder"));
	$scope.subTotal = 0;

	angular.forEach($scope.order.line_items, function(item, key) {
		$scope.subTotal += parseFloat(item.subtotal);
	});
})

.controller('SearchCtrl', function($scope, $state, appConfig, appValue, ProductService) {
	$scope.pageSearch = 1;
	$scope.searchData = [];
	$scope.searchFormData = {};
	$scope.canLoadMoreSearchData = false;
	$scope.openProduct = function($productId) {
		angular.forEach($scope.searchData, function(product, key) {
			if (product.id === $productId) {
				window.localStorage.setItem("product", JSON.stringify(product));
				$state.go('tab.product', {productId: $productId});
				return true;
			}
		});
	};
	$scope.getSearchData = function($page) {

		ProductService.getProducts($page, '&type=search&param=' + $scope.searchFormData.keyword, 1)
		.then(function(listProduct) {
			if(!listProduct){
				$scope.canLoadMoreSearchData = false;
				return false;
			}

			if (listProduct.length > 0) {
				angular.forEach(listProduct, function(product, key) {
					$scope.searchData.push(product);
				});
				$scope.pageSearch = $page + 1;
				$scope.$broadcast('scroll.infiniteScrollComplete');
				$scope.$broadcast('scroll.refreshComplete');
				$scope.canLoadMoreSearchData = true;
			}
			else {
				$scope.canLoadMoreSearchData = false;
			}
		})

		;
	};
	$scope.doSearch = function() {
		$scope.pageSearch = 1;
		$scope.searchData = [];
		$scope.canLoadMoreSearchData = false;
		if($scope.searchFormData.keyword) {
			$scope.getSearchData(1);
		}
	};
	$scope.loadMoreSearchData = function() {
		$scope.getSearchData($scope.pageSearch);
	};
})
;
