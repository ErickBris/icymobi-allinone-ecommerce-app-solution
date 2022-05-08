<?php
/**
 * Created by PhpStorm.
 * User: phongnguyen
 * Date: 6/16/16
 * Time: 3:57 PM
 */

class Inspius_Category extends AbstractApi
{
    public function response($params = [])
    {
        $data = [
            'per_page' => '20'
        ];
        return $this->wc_api->get('products/categories', $data);
    }
}