// Ionic Starter App

// angular.module is a global place for creating, registering and retrieving Angular modules
// 'starter' is the name of this angular module example (also set in a <body> attribute in index.html)
// the 2nd parameter is an array of 'requires'
// 'starter.services' is found in services.js
// 'starter.controllers' is found in controllers.js
angular.module('starter', ['ionic','ionic.service.core', 'starter.controllers', 'starter.services', 'ngMap'])

.run(function($ionicPlatform, $ionicPopup, $rootScope, AppService, appConfig, $injector) {
  $ionicPlatform.ready(function() {
		// Hide the accessory bar by default (remove this to show the accessory bar above the keyboard
		// for form inputs)
		if (window.cordova && window.cordova.plugins && window.cordova.plugins.Keyboard) {
			cordova.plugins.Keyboard.hideKeyboardAccessoryBar(false);
			cordova.plugins.Keyboard.disableScroll(true);
		}
		if (window.StatusBar) {
		  // org.apache.cordova.statusbar required
		  StatusBar.styleDefault();
		}
		try {
			if(appConfig.ENABLE_PUSH_PLUGIN) {
				var NotificationService = $injector.get('NotificationService');
				NotificationService.init();
			}
		}
		catch(err) {
			$ionicPopup.alert({
				title: $rootScope.appLanguage.MESSAGE_TEXT,
				template: 'Push Notification plugin not found'
			});
		}
	});
})

.config(function($stateProvider, $urlRouterProvider, $ionicConfigProvider) {

  $ionicConfigProvider.platform.android.tabs.position('bottom');

  // Ionic uses AngularUI Router which uses the concept of states
  // Learn more here: https://github.com/angular-ui/ui-router
  // Set up the various states which the app can be in.
  // Each state's controller can be found in controllers.js
  $stateProvider

  // setup an abstract state for the tabs directive
  .state('tab', {
    url: '/tab',
    abstract: true,
    controller: 'AppCtrl',
    templateUrl: 'templates/tabs.html'
  })
  
  .state('tab.home', {
    url: '/home',
    views: {
      'mainContent': {
        templateUrl: 'templates/tab-home.html',
        controller: 'HomeCtrl'
      }
    }
  })
  
  .state('tab.categories', {
    url: '/categories',
    views: {
      'mainContent': {
        templateUrl: 'templates/tab-categories.html',
        controller: 'CategoriesCtrl'
      }
    }
  })
  
  .state('tab.category', {
    url: '/category/:categoryId',
    views: {
      'mainContent': {
        templateUrl: 'templates/tab-category.html',
        controller: 'SingleCategoryCtrl'
      }
    }
  })
  
  .state('tab.product', {
    url: '/product/:productId',
    views: {
      'mainContent': {
        templateUrl: 'templates/tab-product.html',
        controller: 'ProductCtrl'
      }
    }
  })
  
  .state('tab.search', {
    url: '/search',
    views: {
      'mainContent': {
        templateUrl: 'templates/tab-search.html',
        controller: 'SearchCtrl'
      }
    }
  })
  
  .state('tab.cart', {
    url: '/cart',
    views: {
      'mainContent': {
        templateUrl: 'templates/tab-cart.html',
        controller: 'CartCtrl'
      }
    }
  })
  
  .state('tab.notification', {
    url: '/notification',
    views: {
      'mainContent': {
        templateUrl: 'templates/tab-notification.html',
        controller: 'NotificationCtrl'
      }
    }
  })

  .state('tab.orders', {
    url: '/orders',
    views: {
      'mainContent': {
        templateUrl: 'templates/tab-orders.html',
        controller: 'OrdersCtrl'
      }
    }
  })

  .state('tab.order', {
    url: '/order/:orderId',
    views: {
      'mainContent': {
        templateUrl: 'templates/tab-order.html',
        controller: 'OrderDetailCtrl'
      }
    }
  })

  .state('tab.account', {
    url: '/account',
    views: {
      'mainContent': {
        templateUrl: 'templates/tab-account.html',
        controller: 'AccountCtrl'
      }
    }
  })
  
  .state('tab.contactus', {
    url: '/contactus',
    views: {
      'mainContent': {
        templateUrl: 'templates/tab-contactus.html',
        controller: 'ContactUsCtrl'
      }
    }
  })

  .state('tab.checkout', {
    url: '/checkout',
    views: {
      'mainContent': {
        templateUrl: 'templates/checkout/login.html',
        controller: 'CheckoutCtrl'
      }
    }
  })

  .state('tab.checkout-billing', {
    url: '/checkout-billing',
    views: {
      'mainContent': {
        templateUrl: 'templates/checkout/billing.html',
        controller: 'CheckoutBillingCtrl'
      }
    }
  })

  .state('tab.checkout-note', {
    url: '/checkout-note',
    views: {
      'mainContent': {
        templateUrl: 'templates/checkout/note.html',
        controller: 'CheckoutNoteCtrl'
      }
    }
  })

  .state('tab.checkout-payment', {
    url: '/checkout-payment',
    views: {
      'mainContent': {
        templateUrl: 'templates/checkout/payment.html',
        controller: 'CheckoutPaymentCtrl'
      }
    }
  })
  
  .state('tab.checkout-success', {
    url: '/checkout-success',
    views: {
      'mainContent': {
        templateUrl: 'templates/checkout/success.html',
        controller: 'CheckoutSuccessCtrl'
      }
    }
  })
  ;

  // if none of the above states are matched, use this as the fallback
  $urlRouterProvider.otherwise('/tab/home');

});
