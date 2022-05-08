<?php
require __DIR__ . '/vendor/autoload.php';

use Automattic\WooCommerce\Client;

class Inspius_API
{
    const ACTION_PRODUCT    = 'products';
    const ACTION_USER       = 'users';
    const ACTION_ORDER      = 'orders';
    const ACTION_CATEGORY   = 'categories';
    const ACTION_REVIEW     = 'reviews';
    const ACTION_SETTING    = 'settings';
    const ACTION_BLOG       = 'blogs';
    
    protected $wc_api;

    protected static $_instance = null;

    public static function instance() {
            if ( is_null( self::$_instance ) ) {
                    self::$_instance = new self();
            }
            return self::$_instance;
    }

    public function __construct()
    {
        $keys = get_option( 'icymobi_api_tokens', array(
            'consumer_key'      => '',
            'consumer_secret'   => ''
        ));

        $this->wc_api = new Client(
            get_site_url(),
            $keys['consumer_key'],
            $keys['consumer_secret'],
            [
                'wp_api' => true,
                'version' => 'wc/v1',
            ]
        );
        
        $this->include_files();
        
        add_action('init', array($this, 'init_hook'));
    }
    
    public function get_api_client(){
        return $this->wc_api;
    }
    
    
    private function include_files(){
        require_once 'inc/AbstractApi.php';
        require_once 'inc/Status.php';
        require_once 'inc/Product.php';
        require_once 'inc/User.php';
        require_once 'inc/Category.php';
        require_once 'inc/Review.php';
        require_once 'inc/Order.php';
        require_once 'inc/Setting.php';
        require_once 'inc/Blog.php';
    }

    public function init_hook()
    {
        add_action('parse_request', array($this, 'sniff_requests'));
        add_filter('query_vars', array($this, 'add_query_vars'), 0);

        add_rewrite_rule('^is-commerce/api/?([^/]+)?/?', 'index.php?__api=1&action=$matches[1]', 'top');
    }

    public function add_query_vars($vars)
    {
        $vars[] = '__api';
        $vars[] = 'action';
        return $vars;
    }

    public function sniff_requests()
    {
        global $wp;
        if (isset($wp->query_vars['__api'])) {
            $action = $wp->query_vars['action'];

            if (!$action) {
                wp_send_json($this->_formatResponse(Inspius_Status::API_FAILED, Inspius_Status::ERR_NO_ROUTE));
            }

            try {
                wp_send_json($this->_formatResponse(Inspius_Status::API_SUCCESS, null, $this->_getResponse($action)));
            } catch (Exception $ex) {
                wp_send_json($this->_formatResponse(Inspius_Status::API_FAILED, $ex->getMessage()));
            }

        }
    }

    protected function _getResponse($action)
    {
        $content = null;
        switch ($action) {
            case self::ACTION_PRODUCT:
                $product    = new Inspius_Product();
                $content    = $product->response();
                break;
            case self::ACTION_ORDER:
                $order      = new Inspius_Order();
                $content    = $order->response();
                break;
            case self::ACTION_REVIEW:
                $review     = new Inspius_Review();
                $content    = $review->response();
                break;
            case self::ACTION_CATEGORY:
                $category   = new Inspius_Category();
                $content    = $category->response();
                break;
            case self::ACTION_USER:
                $user       = new Inspius_User();
                $content    = $user->response();
                break;
            case self::ACTION_SETTING:
                $setting    = new Inspius_Setting();
                $content    = $setting->response();
                break;
            case self::ACTION_BLOG:
                $setting    = new Inspius_Blog();
                $content    = $setting->response();
                break;
            default:
                $content = apply_filters( 'icymobi_api_path_'.$action, $action );
                if(!$content){
                    throw new Exception(Inspius_Status::ERR_NO_ROUTE);
                }
                break;
        }
        return $content;
    }

    protected function _formatResponse($status, $message = null, $content = [])
    {
        return [
            'status'    => $status,
            'message'   => $message,
            'data'      => $content
        ];
    }
}