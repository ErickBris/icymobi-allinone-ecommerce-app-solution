<?php

class Inspius_User extends AbstractApi
{
    const USER_ACTION_LOGIN     = 'login';
    const USER_ACTION_REGISTER  = 'register';
    const USER_ACTION_FORGOT    = 'forgot';
    const USER_ACTION_UPDATE    = 'update';
    const USER_ACTION_UPDATE_SHIPPING   = 'update_shipping';
    const USER_ACTION_UPDATE_BILLING    = 'update_billing';

    public function response($params = [])
    {
        $data = [];
        if ($action = $this->_getParam('task')) {
            switch ($action) {
                case self::USER_ACTION_LOGIN:
                    $data = $this->_login();
                    break;
                case self::USER_ACTION_REGISTER:
                    $data = $this->_register();
                    break;
                case self::USER_ACTION_FORGOT:
                    $this->_forgotPassword();
                    break;
                case self::USER_ACTION_UPDATE:
                    $data = $this->_updateCustomer();
                    break;
                case self::USER_ACTION_UPDATE_BILLING:
                    $data = $this->_updateBilling();
                    break;
                case self::USER_ACTION_UPDATE_SHIPPING:
                    $data = $this->_updateShipping();
                    break;
                default:
                    break;
            }
            return $data;
        }
        throw new Exception(Inspius_Status::USER_NO_ROUTE);
    }

    protected function _login()
    {
        $data = $this->_checkLogin();
        // check username - password
        $login = wp_authenticate($data['user_login'], $data['user_pass']);
        if (!is_wp_error($login)) {
            // return $this->_formatUserData($login);
            return $this->_getUserById($login->ID);
        }
        /* @var $login WP_Error */
        $errorCode = strtoupper("user_login_" . $login->get_error_code());
        throw new Exception(constant("Inspius_Status::$errorCode"));
    }

    protected function _register()
    {
        $user = $this->_checkRegister();
        $register = wp_insert_user($user);
        if (!is_wp_error($register)) {
            return $this->_getUserById($register);
        }
        /* @var $register WP_Error */
        $errorCode = strtoupper("user_register_" . $register->get_error_code());
        throw new Exception(constant("Inspius_Status::$errorCode"));
    }

    protected function _forgotPassword()
    {
        $user_data = $this->_checkForgotPassword();
        // wp generate password reset key
        $key = get_password_reset_key($user_data);
        if (is_wp_error($key)) {
            throw new Exception(Inspius_Status::USER_FORGOT_CANNOT_RESET);
        }

        $user_login = $user_data->user_login;
        $user_email = $user_data->user_email;

        // generate email message
        $message  = __('Someone has requested a password reset for the following account:') . "\r\n\r\n";
        $message .= network_home_url('/') . "\r\n\r\n";
        $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
        $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
        $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
        $message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . ">\r\n";

        if (is_multisite())
            $blogname = $GLOBALS['current_site']->site_name;
        else
            $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

        $title   = sprintf(__('[%s] Password Reset'), $blogname);
        $title   = apply_filters('retrieve_password_title', $title, $user_login, $user_data);
        $message = apply_filters('retrieve_password_message', $message, $key, $user_login, $user_data);

        // send email
        if ($message && !wp_mail($user_email, wp_specialchars_decode($title), $message)) {
            throw new Exception(Inspius_Status::USER_FORGOT_CANNOT_RESET);
        }

        return true;
    }

    protected function _updateCustomer()
    {
        $userData   = $this->_getParams(['user_id', 'user_firstname', 'user_lastname', 'user_pass', 'user_email', 'user_new_password', 'user_confirmation']);
        $data = $this->_checkUpdate($userData);
        if ($data && !empty($data)){
            $userId     = wp_update_user($data);
            if (!is_wp_error($userId)) {
                return $this->_getUserById($userId);
            }
        }

        throw new Exception(Inspius_Status::USER_UPDATE_FAILED);
    }

    protected function _updateBilling()
    {
        $billing = $this->_getParam('billing');
        $customerId = $this->_getParam('user_id');
        if ($customerId && is_numeric($customerId)) {
            $data = $this->wc_api->put("customers/$customerId", ["billing" => json_decode(stripslashes($billing))]);
            return $this->_getUserById($data['id']);
        }
        throw new Exception(Inspius_Status::USER_UPDATE_CUSTOMER_NOT_FOUND);
    }

    protected function _updateShipping()
    {
        $shipping = $this->_getParam('billing');
        $customerId = $this->_getParam('user_id');
        if ($customerId && is_numeric($customerId)) {
            $data = $this->wc_api->put("customers/$customerId", ["shipping" => json_decode(stripslashes($shipping))]);
            return $this->_getUserById($data['id']);
        }
        throw new Exception(Inspius_Status::USER_UPDATE_CUSTOMER_NOT_FOUND);
    }

    private function _checkLogin()
    {
        $userLogin      = $this->_getParam('user_login');
        $userPassword   = $this->_getParam('user_pass');
        if ($userLogin && $userPassword) {
            return [
                'user_login'    => $userLogin,
                'user_pass'     => $userPassword
            ];
        }
        throw new Exception(Inspius_Status::USER_LOGIN_INVALID_DATA);
    }

    private function _checkRegister()
    {
        $userData           = $this->_getParams(['user_login', 'user_pass', 'user_email', 'first_name', 'last_name']);
        $userData['role']   = 'subscriber';
        if (count($userData) == 6) {
            return $userData;
        }
        throw new Exception(Inspius_Status::USER_REGISTER_INVALID_DATA);
    }

    private function _checkForgotPassword()
    {
        $userLogin = $this->_getParam('user_login');
        if ($userLogin) {
            $userData = strpos($userLogin, '@') ?
                get_user_by('email', trim($userLogin)) :
                get_user_by('login', trim($userLogin));

            if ($userData) {
                return $userData;
            }
            throw new Exception(Inspius_Status::USER_FORGOT_USER_NOT_EXIST);
        }
        throw new Exception(Inspius_Status::USER_FORGOT_INVALID_DATA);
    }

    private function _checkUpdate($data)
    {
        $user_obj = get_userdata($data['user_id']);
        if (!$user_obj) {
            return new Exception(Inspius_Status::USER_UPDATE_CUSTOMER_NOT_FOUND);
        }
        if ($data['user_confirmation']) {
            if ($data['user_confirmation'] == $data['user_new_password']) {
                $login = wp_authenticate($data['user_email'], $data['user_pass']);
                if (!is_wp_error($login)) {
                    return [
                        'ID'            => $data['user_id'],
                        'first_name'    => $data['user_firstname'],
                        'last_name'     => $data['user_lastname'],
                        'user_pass'     => $data['user_new_password'],
                        'user_email'    => $data['user_email']
                    ];
                }
                throw new Exception(Inspius_Status::USER_UPDATE_WRONG_PASSWORD);
            }
            throw new Exception(Inspius_Status::USER_UPDATE_MISMATCH_CONFIRMATION);
        }
        return [
            'ID'            => $data['user_id'],
            'first_name'    => $data['user_firstname'],
            'last_name'     => $data['user_lastname'],
            'user_email'    => $data['user_email']
        ];
    }

    private function _getUserById($id)
    {
        return $this->_formatUserData($this->wc_api->get("customers/$id"));
    }

    private function _formatUserData($user)
    {
        /* @var $user WP_User */
        $user['avatar'] = 'http://www.gravatar.com/avatar/' . md5($user['email']) . '?s=80';
        return $user;
    }
}