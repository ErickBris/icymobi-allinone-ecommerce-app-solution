angular.module('starter')

.factory('StripeService', function($state, $ionicLoading, $http, $ionicHistory, $rootScope, $ionicPopup, AppService, OrderService, CartService, UserService, appConfig, appValue) {
  	return {
		init: function() {
			try {
				var total = Math.round(OrderService.getOrderGrandTotal()*100);
				var user = UserService.getUser();
				var appSetting = AppService.getAppSetting();
				var stripeMode = appSetting.stripe_mode;
				var stripeToken = appSetting.stripe_token;
				var currency = OrderService.getOrderCurrency();
				var lineItems = OrderService.getOrderInfoLineItems();
				var shortDescription = lineItems[0].product_name;
				var thisObject = this;
				if(lineItems.length > 1) {
					angular.forEach(lineItems, function(product, key) {
						if(key > 0) {
							shortDescription = shortDescription + ' + ' + product.product_name;
						}
					});
				}
				var handler = StripeCheckout.configure({
					key: stripeToken,
					locale: 'auto',
					currency: currency,
					email: user.email,
					token: function(token) {
						thisObject.createOrder(token).then(function(response) {
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
					}
				});
				handler.open({
					name: 'BUY',
					description: shortDescription,
					amount: total
				});
			}
			catch(err) {
				$ionicPopup.alert({
					title: $rootScope.appLanguage.MESSAGE_TEXT,
					template: 'Stripe plugin not found'
				});
			}
		},
		createOrder: function(token) {
			var orderInfo = OrderService.getOrderInfo();
			orderInfo.stripe_token = token.id;
			if(AppService.getDisableApp()) {
				$ionicPopup.alert({
					title: $rootScope.appLanguage.MAINTAIN_TEXT,
					template: AppService.getAppSetting().disable_app_message
				});
				return true;
			}
			$ionicLoading.show({
				template: '<ion-spinner></ion-spinner>'
			});
			return $http({
				method: 'POST',
				url: appConfig.DOMAIN_URL + appValue.API_URL + 'stripe',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				transformRequest: function(obj) {
					var str = [];
					for(var p in obj)
						str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
					return str.join("&");
				},
				data: orderInfo
			})
			.then(function(response) {
				$ionicLoading.hide();
				// handle success things
				if(response.data.status === appValue.API_SUCCESS){
					OrderService.updateOrderReceivedInfo(response.data.data);
					return true;
				}
				else {
					return false;
				}
			}, function error(response){
				$ionicLoading.hide();
				$ionicPopup.alert({
					title: $rootScope.appLanguage.MESSAGE_TEXT,
					template: $rootScope.appLanguage.NETWORK_OFFLINE_TEXT
				});
				return false;
			});
		}
  	};
})
;