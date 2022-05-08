<?php

/**
 * Created by PhpStorm.
 * User: phongnguyen
 * Date: 6/16/16
 * Time: 4:15 PM
 */
class Inspius_Review extends AbstractApi
{
    const REVIEW_ADD_NEW = 'add';

    /**
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function response($params = [])
    {
        $data = [
            'order' => 'desc',
            'order_by' => 'date_created'
        ];
        $id = $this->_getParam('id');
        $action = $this->_getParam('task');
        if ($id && is_numeric($id)) {
            if ($action && $action == self::REVIEW_ADD_NEW) {
                $this->_addNewComment($id);
            }
            return $this->setup_data($this->wc_api->get("products/$id/reviews", $data));
        }
        throw new Exception(Inspius_Status::REVIEW_ID_NOT_FOUND);
    }

    private function setup_data($response)
    {
        if (is_array($response) && count($response) > 0) {
            for ($i = 0; $i < count($response); $i++) {
                // Add link avatar url
                $response[$i]['link_avatar'] = get_avatar_url($response[$i]['email'], array('size' => 80));
                if (!isset($response[$i]['name']) || !$response[$i]['name']) {
                    /* @var $customer WP_User */
                    $customer = get_user_by('email', $response[$i]['email']);
                    if ($customer) {
                        $response[$i]['name'] = $customer->display_name;
                    }
                }
            }
        }
        return array_reverse($response);
    }

    /**
     * @param $id
     * @throws Exception
     */
    protected function _addNewComment($id)
    {
        $params = $this->_getParams(
            ['user_id', 'user_login', 'user_email', 'comment'],
            ['user_id', 'comment_author', 'comment_author_email', 'comment_content']
        );

        $params['comment_post_ID'] = $id;
        $params['comment_author_IP'] = $_SERVER['REMOTE_ADDR'];
        $params['comment_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $params['comment_date'] = current_time('mysql');
        $params['comment_date_gmt'] = current_time('mysql', 1);
        if (!isset($params['user_id']) || !$params['user_id'] || !is_numeric($params['user_id'])) $params['user_id'] = 0;

        if ($params['user_id'] != 0) {
            $customerId = $params['user_id'];
            $customer = $this->wc_api->get("customers/$customerId");
            $params['comment_author'] = $customer['username'];
            $params['comment_author_email'] = $customer['email'];
        }

        $params = wp_filter_comment($params);

        $params['comment_approved'] = $this->_check_comment($params);

        if ($commentId = wp_insert_comment($params)) {
            $rating = $this->_getParam('rating');
            if (is_numeric($rating) && $rating > 0 && $rating <= 5) {
                add_comment_meta($commentId, 'rating', (int)esc_attr($rating), true);

                // Clear transients
                delete_post_meta( $id, '_wc_average_rating' );
                delete_post_meta( $id, '_wc_rating_count' );
                delete_post_meta( $id, '_wc_review_count' );
                WC_Product::sync_average_rating( $id );
                
            }
        } else {
            throw new Exception(Inspius_Status::REVIEW_ADD_NEW_FAILED);
        }
    }

    /**
     * check comment
     *
     * @param $commentdata
     * @return int|mixed|string|void
     * @throws Exception
     */
    private function _check_comment($commentdata)
    {
        global $wpdb;

        // Simple duplicate check
        // expected_slashed ($comment_post_ID, $comment_author, $comment_author_email, $comment_content)
        $dupe = $wpdb->prepare(
            "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_parent = %s AND comment_approved != 'trash' AND ( comment_author = %s ",
            wp_unslash($commentdata['comment_post_ID']),
            wp_unslash($commentdata['comment_parent']),
            wp_unslash($commentdata['comment_author'])
        );
        if ($commentdata['comment_author_email']) {
            $dupe .= $wpdb->prepare(
                "OR comment_author_email = %s ",
                wp_unslash($commentdata['comment_author_email'])
            );
        }
        $dupe .= $wpdb->prepare(
            ") AND comment_content = %s LIMIT 1",
            wp_unslash($commentdata['comment_content'])
        );

        $dupe_id = $wpdb->get_var($dupe);

        /**
         * Filters the ID, if any, of the duplicate comment found when creating a new comment.
         *
         * Return an empty value from this filter to allow what WP considers a duplicate comment.
         *
         * @since 4.4.0
         *
         * @param int $dupe_id ID of the comment identified as a duplicate.
         * @param array $commentdata Data for the comment being created.
         */
        $dupe_id = apply_filters('duplicate_comment_id', $dupe_id, $commentdata);

        if ($dupe_id) {
            /**
             * Fires immediately after a duplicate comment is detected.
             *
             * @since 3.0.0
             *
             * @param array $commentdata Comment data.
             */
            throw new Exception(Inspius_Status::REVIEW_ADD_NEW_DUPLICATED);
        }

        /**
         * Fires immediately before a comment is marked approved.
         *
         * Allows checking for comment flooding.
         *
         * @since 2.3.0
         *
         * @param string $comment_author_IP Comment author's IP address.
         * @param string $comment_author_email Comment author's email.
         * @param string $comment_date_gmt GMT date the comment was posted.
         */
        do_action(
            'check_comment_flood',
            $commentdata['comment_author_IP'],
            $commentdata['comment_author_email'],
            $commentdata['comment_date_gmt']
        );

        if (!empty($commentdata['user_id'])) {
            $user = get_userdata($commentdata['user_id']);
            $post_author = $wpdb->get_var($wpdb->prepare(
                "SELECT post_author FROM $wpdb->posts WHERE ID = %d LIMIT 1",
                $commentdata['comment_post_ID']
            ));
        }

        if (isset($user) && ($commentdata['user_id'] == $post_author || $user->has_cap('moderate_comments'))) {
            // The author and the admins get respect.
            $approved = 1;
        } else {
            // Everyone else's comments will be checked.
            if (check_comment(
                $commentdata['comment_author'],
                $commentdata['comment_author_email'],
                $commentdata['comment_author_url'],
                $commentdata['comment_content'],
                $commentdata['comment_author_IP'],
                $commentdata['comment_agent'],
                $commentdata['comment_type']
            )) {
                $approved = 1;
            } else {
                $approved = 0;
            }

            if (wp_blacklist_check(
                $commentdata['comment_author'],
                $commentdata['comment_author_email'],
                $commentdata['comment_author_url'],
                $commentdata['comment_content'],
                $commentdata['comment_author_IP'],
                $commentdata['comment_agent']
            )) {
                $approved = EMPTY_TRASH_DAYS ? 'trash' : 'spam';
            }
        }

        /**
         * Filter a comment's approval status before it is set.
         *
         * @since 2.1.0
         *
         * @param bool|string $approved The approval status. Accepts 1, 0, or 'spam'.
         * @param array $commentdata Comment data.
         */
        $approved = apply_filters('pre_comment_approved', $approved, $commentdata);
        return $approved;
    }
}