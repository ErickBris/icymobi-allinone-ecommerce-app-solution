angular.module('starter')
//config param of App
.constant('appConfig', {
    DOMAIN_URL: 'http://test.inspius.com/isem/wordpress',
	ADMIN_EMAIL: 'envato@inspius.com',
	ENABLE_PUSH_PLUGIN: false,
	ENABLE_PAYPAL_PLUGIN: false,
	ENABLE_STRIPE_PLUGIN: false
	}
)


//dont change this value if you dont know what it is
.constant('appValue', {
	API_URL: '/is-commerce/api/',
	API_SUCCESS: 1,
	API_FAILD: -1
})
;