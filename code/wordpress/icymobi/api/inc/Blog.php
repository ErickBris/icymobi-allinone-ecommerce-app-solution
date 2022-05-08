<?php
/**
 * Created by PhpStorm.
 * User: phongnguyen
 * Date: 6/16/16
 * Time: 3:57 PM
 */

class Inspius_Blog extends AbstractApi
{
    const REQUEST_SINGLE = 'single';
    const REQUEST_SEARCH = 'search';
    const REQUEST_CATEGORY = 'category';
    const REQUEST_TAG = 'tag';

    public function response($params = [])
    {
        $data = [
            'post_type'     => 'post',
            'post_status'   => 'publish'
        ];
        $type = $this->_getParam('type');
        $param = $this->_getParam('param');

        // get product by type
        switch ($type) {
            case self::REQUEST_SINGLE:
                if ($param) {
                    $data['p']      = $param;
                }
                break;
            case self::REQUEST_CATEGORY:
                if ($param) {
                    $data['cat']    = $param;
                }
                break;
            case self::REQUEST_SEARCH:
                if ($param) {
                    $data['s']      = $param;
                }
                break;
            case self::REQUEST_TAG:
                if ($param) {
                    $data['tag']    = $param;
                }
                break;
            default:
                break;
        }

        // additional params
        if ($order = $this->_getParam('order')) {
            $data['order'] = $order;
        }
        if ($orderBy = $this->_getParam('orderby')) {
            $data['orderby'] = $orderBy;
        }
        if ($page = $this->_getParam('page')) {
            $data['paged'] = $page;
        }
        if ($perPage = $this->_getParam('per_page')) {
            $data['posts_per_page'] = $perPage;
        }

        $the_query = new WP_Query( $data );
        return $this->_formatPost($the_query);
    }

    private function _formatPost($the_query)
    {
        /* @var $the_query WP_Query */
        $formattedPosts = [];
        while ( $the_query->have_posts() ) {
            $the_query->the_post();
            $formattedPosts[] = [
                'id' => get_the_ID(),
                'post_author' => get_the_author(),
                'post_content' => get_the_content(),
                'post_title' => get_the_title(),
                'post_excerpt' => get_the_excerpt(),
                'post_link' => get_the_permalink(),
                'post_tags' => get_the_tags(),
                'post_categories' => get_the_category(),
                'post_comment' => get_comments(['post_id' => get_the_ID()])
            ];
        }
        return $formattedPosts;
    }
}