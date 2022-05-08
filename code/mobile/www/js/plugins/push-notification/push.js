angular.module('starter')

.factory('NotificationService', function($state, $ionicPopup, $rootScope, AppService, UserService) {
	return {
		init: function() {
			try {
				var thisObject = this;
				var push = new Ionic.Push({
					"debug": true,
					"onNotification": function(notification){
						thisObject.doNotification(notification);
					},
				   "pluginConfig": {
					 "ios": {
					   "alert": true,
					   "badge": true,
					   "sound": true
					 },
					 "android": {
					   "sound": true,
					   "vibrate": true,
					   "forceShow": true
					 }
				   }
				});
				push.register(function(token) {
					thisObject.saveDeviceToken(token.token);
					AppService.updateAppSetting();
				});
			}
			catch(err) {
				$ionicPopup.alert({
					title: $rootScope.appLanguage.MESSAGE_TEXT,
					template: 'Push Notification plugin not found'
				});
			}
		},
		saveDeviceToken: function(token) {
			window.localStorage.setItem("deviceToken", token);
		},
		getDeviceToken: function() {
			return window.localStorage.getItem("deviceToken");
		},
		doNotification: function(notification) {
			if(notification.payload.type === 'text') {
				this.textNotification(notification);
			}else if(notification.payload.type === 'order'){
				this.orderNotification(notification);
			}
		},
		textNotification: function(notification){
			window.localStorage.setItem("appNotificationPayload", JSON.stringify(notification.payload));
			if(!notification._raw.additionalData.foreground) {
				$state.go('tab.notification');
			}
			else {
				var confirmPopup = $ionicPopup.confirm({
					title: $rootScope.appLanguage.NOTIFICATION_TEXT,
					template: 'You have a new notification - go to it?'
				});
				confirmPopup.then(function(res) {
					if(res) {
						$state.go('tab.notification');
					}
				});
			}
		},
		orderNotification: function(notification){
			if(!notification._raw.additionalData.foreground) {
				var orderInfo = notification.payload.data;
				window.localStorage.setItem("singleOrder", JSON.stringify(orderInfo));
				$state.go('tab.order');
			}
			else {
				var confirmPopup = $ionicPopup.confirm({
					title: $rootScope.appLanguage.NOTIFICATION_TEXT,
					template: 'You have a new notification - go to it?'
				});
				confirmPopup.then(function(res) {
					if(res) {
						$state.go('tab.orders');
					}
				});
			}
		}
  	};
})
;