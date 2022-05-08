<?php

class Inspius_Product extends AbstractApi
{
    const REQUEST_SINGLE = 'single';
    const REQUEST_CATEGORY = 'category';
    const REQUEST_TYPE = 'type';
    const REQUEST_SEARCH = 'search';
    const REQUEST_TAG = 'tag';

    protected $_productGroup = ['featured', 'onsale', 'best_seller'];

    public function response($params = [])
    {
        $data = [];
        $type = $this->_getParam('type');
        $param = $this->_getParam('param');

        // get product by type
        switch ($type) {
            case self::REQUEST_TYPE:
                if ($param && in_array($param, $this->_productGroup)) {
                    $fn = '_get_product_' . $param;
                    $data = [
                        'include' => $this->$fn()
                    ];
                }
                break;
            case self::REQUEST_CATEGORY:
                if ($param) {
                    $data = [
                        'category' => $param
                    ];
                }
                break;
            case self::REQUEST_SINGLE:
                if ($param) {
                    return $this->wc_api->get("products/$param");
                }
                break;
            case self::REQUEST_SEARCH:
                if ($param) {
                    $data = [
                        'search' => $param
                    ];
                }
                break;
            case self::REQUEST_TAG:
                if ($param) {
                    $data = [
                        'tag' => $param
                    ];
                }
                break;
            default:
                break;
        }

        // additional params
        if ($include = $this->_getParam('include')) {
            $data['include'] = $include;
        }
        if ($exclude = $this->_getParam('exclude')) {
            $data['exclude'] = $exclude;
        }
        if ($order = $this->_getParam('order')) {
            $data['order'] = $order;
        }
        if ($orderBy = $this->_getParam('orderby')) {
            $data['orderby'] = $orderBy;
        }
        if ($page = $this->_getParam('page')) {
            $data['page'] = $page;
        }
        if ($perPage = $this->_getParam('per_page')) {
            $data['per_page'] = $perPage;
        }

        return $this->setup_data( $this->wc_api->get('products', $data) );
    }

    private function setup_data($response){
        if( is_array($response) && count($response) > 0 ){
            for ($i=0; $i < count($response); $i++) { 

                // Add Rate Html
                $response[$i]['rating_star_html'] = $this->get_rating_html($response[$i]);

                //Edit Size Image
                $response[$i] = $this->edit_size_image($response[$i]);

                // Add Variable HTML
                // $response[$i]['price_variable_html'] = $this->get_variable_price_html($response[$i]);

                $response[$i]['attributes'] = $this->_formatAttribute($response[$i]['id'], $response[$i]['attributes']);

            }
        }

        return $response;
    }

    private function _formatAttribute($productId, $attributes = [])
    {
        $data = [];
        foreach ($attributes as $attribute) {
            $id = $attribute['id'];
            $options = [];
            if ($id > 0) {
                $data = ['product' => $productId];
                $terms =  $this->wc_api->get("products/attributes/$id/terms", $data);
                foreach ($terms as $term) {
                    $options[] = [
                        "name" => $term['name'],
                        "value" => $term['slug'],
                    ];
                }
            }
            else {
                foreach ($attribute['options'] as $option) {
                    $options[] = [
                        "name" => $option,
                        "value" => $option,
                    ];
                }
            }
            $attribute['type'] = "dropdown";
            $attribute['options'] = $options;
            $data[] = $attribute;
            if (isset($data['product'])) unset($data['product']);
        }

        return $data;
    }

    private function get_variable_price_html($response){
        if($response['type']!='variable'){
            return false;
        }
        $html           = '';
        $attrs          = $response['attributes'];
        $default_attr   = $response['default_attributes'];
        foreach ($attrs as $key => $attr) {
            $html .= '
                <label class="item item-input item-select">
                    <div class="input-label">
                        <strong>'.$attr['name'].'</strong>
                    </div>
                    <select ng-model="selectedItem'.$key.'" ng-change="updateProductAttribute('.$key.', selectedItem'.$key.')">';
                    foreach ($attr['options'] as $k => $value) {
                        if($attr['id']>0){
                            $html .='<option '. selected( $default_attr[$key]['option'], sanitize_title($value), false ) .' value="'.sanitize_title($value).'">'.$value.'</option>';
                        }else{
                            $html .='<option '. selected( $default_attr[$key]['option'], $value, false ) .' value="'.$value.'">'.$value.'</option>';
                        }
                    }
            $html .='
                    </select>
                </label> 
            ';
        }
        /**
        return '
            
        ';
        **/

        return $html;
    }

    private function get_rating_html($response){
        $width = intval($response['average_rating']) * 20;
        return '
            <div class="rate">
                <span style="width: '.$width.'%;"></span>
            </div>
            <span class="count">('.$response['rating_count'].')</span>
        ';
    }

    private function edit_size_image($response){
        for ($i=0; $i < count($response['images']); $i++) { 
            if($response['images'][$i]['id']>0){
                $image = wp_get_attachment_image_src($response['images'][$i]['id'], 'medium');
                $response['images'][$i]['src'] = $image[0];
            }
        }
        return $response;
    }


    // =============================
    // =============================
    // Start Products
    // =============================
    // =============================
    private function _get_product_featured()
    {

        $featured = get_posts(array(
            'post_type'      => array('product', 'product_variation'),
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'       => '_visibility',
                    'value'     => array('catalog', 'visible'),
                    'compare'   => 'IN'
                ),
                array(
                    'key'       => '_featured',
                    'value'     => 'yes'
                )
            ),
            'fields' => 'id=>parent'
        ));

        $product_ids            = array_keys($featured);
        $parent_ids             = array_values(array_filter($featured));
        $featured_product_ids   = array_unique(array_merge($product_ids, $parent_ids));

        return implode(',', $featured_product_ids);
    }

    private function _get_product_onsale()
    {

        global $wpdb;
        // Load from cache
        $product_ids_on_sale = get_transient('is_products_onsale');

        // Valid cache found
        if (false !== $product_ids_on_sale) {
            return implode(',', $product_ids_on_sale);
        }

        $on_sale_posts = $wpdb->get_results("
			SELECT post.ID, post.post_parent FROM `$wpdb->posts` AS post
			LEFT JOIN `$wpdb->postmeta` AS meta ON post.ID = meta.post_id
			LEFT JOIN `$wpdb->postmeta` AS meta2 ON post.ID = meta2.post_id
			WHERE post.post_type IN ( 'product', 'product_variation' )
				AND post.post_status = 'publish'
				AND meta.meta_key = '_sale_price'
				AND meta2.meta_key = '_price'
				AND CAST( meta.meta_value AS DECIMAL ) >= 0
				AND CAST( meta.meta_value AS CHAR ) != ''
				AND CAST( meta.meta_value AS DECIMAL ) = CAST( meta2.meta_value AS DECIMAL )
			GROUP BY post.ID;
		");

        $product_ids_on_sale = array_unique(array_map('absint', array_merge(wp_list_pluck($on_sale_posts, 'ID'), array_diff(wp_list_pluck($on_sale_posts, 'post_parent'), array(0)))));

        set_transient('is_products_onsale', $product_ids_on_sale, DAY_IN_SECONDS * 30);

        return implode(',', $product_ids_on_sale);
    }

    private function _get_product_best_seller()
    {

        $products = get_posts(array(
            'post_type'      => array('product'),
            'posts_per_page' => 10,
            'post_status'    => 'publish',
            'meta_key'       => 'total_sales',
            'orderby'        => 'meta_value_num',
            'meta_query'     => array(
                array(
                    'key'       => '_visibility',
                    'value'     => array('catalog', 'visible'),
                    'compare'   => 'IN'
                ),
            ),
            'fields' => 'id=>parent'
        ));

        $product_ids = array_keys($products);
        $parent_ids = array_values(array_filter($products));
        $best_seller_product_ids = array_unique(array_merge($product_ids, $parent_ids));

        return implode(',', $best_seller_product_ids);
    }

    // =============================
    // =============================
    // End Products
    // =============================
    // =============================
}