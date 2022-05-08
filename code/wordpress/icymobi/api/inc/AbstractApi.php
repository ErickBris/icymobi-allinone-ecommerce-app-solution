<?php
abstract class AbstractApi
{
    /**
     * @var Automattic\WooCommerce\Client
     */
    protected $wc_api;

    public function __construct()
    {
        
        $this->wc_api = Inspius_API::instance()->get_api_client();

    }

    public abstract function response($params = []);

    protected function _getParam($paramName)
    {
        if (isset($_REQUEST[$paramName]) && $_REQUEST[$paramName]) {
            return $_REQUEST[$paramName];
        }
        return null;
    }

    protected function _getParams($selectedParams = [], $paramsMask = [])
    {
        $data = [];
        if (empty($selectedParams)) {
            $data = $_REQUEST;
        } else {
            foreach ($selectedParams as $id => $param) {
                if (isset($_REQUEST[$param]) && $_REQUEST[$param]) {
                    if (isset($paramsMask[$id]) && $paramsMask[$id]) {
                        $data[$paramsMask[$id]] = $_REQUEST[$param];
                    } else {
                        $data[$param] = $_REQUEST[$param];
                    }
                }
            }
        }
        return $data;
    }
}