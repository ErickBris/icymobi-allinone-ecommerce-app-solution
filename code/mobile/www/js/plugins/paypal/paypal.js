angular.module('starter')

.factory('PaypalService', function($state, $ionicLoading, $http, $ionicHistory, $rootScope, $ionicPopup, AppService, OrderService, CartService, appConfig, appValue) {
  	return {
		init: function() {
			try {
				var total = OrderService.getOrderGrandTotal();
				var currency = OrderService.getOrderCurrency();
				var appSetting = AppService.getAppSetting();
				var paypalMode = appSetting.paypal_mode;
				var thisObject = this;
				if(paypalMode === "sandbox") {
					paypalMode = "PayPalEnvironmentSandbox";
				}
				if(paypalMode === "live") {
					paypalMode = "PayPalEnvironmentProduction";
				}
				var paypalToken = appSetting.paypal_token;
				var lineItems = OrderService.getOrderInfoLineItems();
				var shortDescription = lineItems[0].product_name;
				if(lineItems.length > 1) {
					angular.forEach(lineItems, function(product, key) {
						if(key > 0) {
							shortDescription = shortDescription + ' + ' + product.product_name;
						}
					});
				}
				var clientIDs = {
					"PayPalEnvironmentProduction": paypalToken,
					"PayPalEnvironmentSandbox": paypalToken
				};
				PayPalMobile.init(clientIDs, function(){
					var config = new PayPalConfiguration({merchantName: "", merchantPrivacyPolicyURL: "", merchantUserAgreementURL: ""});
					PayPalMobile.prepareToRender(paypalMode, config, function(){
					});
					var paymentDetails = new PayPalPaymentDetails(total, "0.00", "0.00");
					var payment = new PayPalPayment(total, currency, shortDescription, "Sale", paymentDetails);
					OrderService.createOrder().then(function(response) {
						if(response === true) {
							PayPalMobile.renderSinglePaymentUI(payment, function() {
								thisObject.completePayment();
							}, function() {
								//cancel payment
							});
						}
						else {

						}
					});
				});
			}
			catch(err) {
				$ionicPopup.alert({
					title: $rootScope.appLanguage.MESSAGE_TEXT,
					template: 'Paypal plugin not found'
				});
			}
		},
		completePayment: function() {
			var orderReceivedInfo = OrderService.getOrderReceivedInfo();
			console.log(orderReceivedInfo);
			OrderService.changeOrderStatus(orderReceivedInfo.id).then(function(response) {
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
  	};
})
;