<?php

/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Phong Nguyen
 * Author URI: http://inspius.com
 */
class Inspius_Setting extends AbstractApi
{
    public function response($params = [])
    {
        $settings   = [];

        $price      = apply_filters('raw_woocommerce_price', floatval(1000));
        $price      = apply_filters('formatted_woocommerce_price', number_format($price, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator()), $price, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator());
        

        // return update_option('icymobi_device_tokens', array());
        // update_option( 'icymobi_device_tokens', array('abc') );
        $this->set_token($this->_getParam('token'));
        // return get_option( 'icymobi_device_tokens' );
        

        // Setting Woocommerce
        $settings['thousand_separator']     = wc_get_price_thousand_separator();
        $settings['decimal_separator']      = wc_get_price_decimal_separator();
        $settings['number_decimals']        = wc_get_price_decimals();
        $settings['samplePrice']            = $price;
        $settings['samplePriceHtml']        = wc_price(1000);

        // Contact Option
        $settings['contact_map_lat']        = $this->get_option( 'contact_map_lat' );
        $settings['contact_map_lng']        = $this->get_option( 'contact_map_lng' );
        $settings['contact_map_title']      = $this->get_option( 'contact_map_title' );
        $settings['contact_map_content']    = $this->get_option( 'contact_map_content' );

        // app maintenance settings
        $settings['disable_app']            = $this->get_option( 'general_enable_app' );
        $settings['disable_app_message']    = $this->get_option( 'general_maintenance_text' );

        return  apply_filters( 'icymobi_api_settings', $settings );
    }

    private function get_option($id, $default = ''){
        $options = get_option( 'icymobi_config_option', array());
        if(isset($options[$id]) && $options[$id] != '')
            return $options[$id];
        else
            return $default;
    }

    private function set_token($token = null){
        if($token){
            $tokens = get_option( 'icymobi_device_tokens', array() );
            if(!in_array($token, $tokens)){
                $tokens[] = $token;
                update_option( 'icymobi_device_tokens', $tokens );
            }
        }
    }
}