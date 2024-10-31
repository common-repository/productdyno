<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       productdyno.com
 * @since      1.0.0
 *
 * @package    Productdyno
 * @subpackage Productdyno/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Productdyno
 * @subpackage Productdyno/public
 * @author     ProductDyno <hello@productdyno.com>
 */

class Productdyno_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Productdyno_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Productdyno_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/productdyno-public.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Productdyno_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Productdyno_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/productdyno-public.js', array( 'jquery' ), $this->version, false );

    }

    public function get_pd_content_page($page_template_path)
    {
        if (!session_id()) {
            session_start();
        }

        global $wp;
        global $post;

        // If user is login (WP User) then don't run PD plugin functionality
        if(is_user_logged_in()) {
            return $page_template_path;
        }

        // Get current url with params
        $current_url = home_url(add_query_arg(array($_GET), $wp->request));

        // get pd type
        $pd_type = get_post_meta(get_the_ID(), 'pd_type', true);

        // get product id from db against current post id
        $pd_product_id = get_post_meta(get_the_ID(), 'pd_product_id', true);

        // get collection id from db against current post id
        $pd_collection_id = get_post_meta(get_the_ID(), 'pd_collection_id', true);

        // get collection product id from db
        $pd_collection_product_id = get_post_meta(get_the_ID(), 'pd_collection_product_id', true);

        // Login work / display login page work for product and collection
        if($api_key = get_option('_productdyno_api_key')) {

            // On login page submit work
            if(isset($_GET['_pd_login_submit']) && $_GET['_pd_login_submit'] == 1) {

                // Call our member login function
                $this->_pd_member_login($api_key, $pd_type, $pd_product_id, $pd_collection_id);
            }
            // End login form submit work

            // On forgot password submit work
            if(isset($_GET['_pd_forgot_password_submit']) && $_GET['_pd_forgot_password_submit'] == 1) {
                // Call our member forgot password function
                $this->_pd_member_forgot_password($api_key, $pd_type, $pd_product_id, $pd_collection_id);
            }
            // End forgot password submit work

            // On reset password submit work
            if(isset($_GET['_pd_reset_password_submit']) && $_GET['_pd_reset_password_submit'] == 1) {
                // Call our member login function
                $this->_pd_member_reset_password($api_key, $pd_type, $pd_product_id, $pd_collection_id);
            }
            // End reset password submit work

            // On register member submit work
            if(isset($_GET['_pd_register_submit']) && $_GET['_pd_register_submit'] == 1) {
                // Call our member login function
                $this->_pd_member_register($api_key, $pd_type, $pd_product_id, $pd_collection_id);
            }
            // End register member submit work

            // Show login page or content page
            if(!empty($pd_type)) {

                if($pd_type == 'product') {

                    // check if product detail is available in transient, if yes then don't send request to API
                    if (false === ($pd_product_data = get_transient('pd_product_data_'.$pd_product_id))) {

                        $url = PRODUCTDYNO_API_URL.'products';
                        $data['id'] = $pd_product_id;
                        $pd_product_data = $this->_wpRemoteRequestAPI($api_key, $url, 'GET', $data);

                        // set product data in transient
                        set_transient( 'pd_product_data_'.$pd_product_id, $pd_product_data);
                    }

                    // Check if product is set to public access the show the content page
                    if($pd_product_data->is_public) {
                        return $page_template_path;
                    }

                    // If session of this product is not found then show login page
                    if(!$_SESSION['_pd_auth_p_'.$pd_product_id]) {

                        if(!empty($pd_product_id)) {

                            // check if product login page is  available in transient, if yes then don't send ajax request
                            if (false === ($pd_html_page = get_transient('pd_product_login_page_'.$pd_product_id))) {

                                // Get domain urls
                                $urls = $this->_pd_get_urls($api_key, $pd_type, $pd_product_id, $pd_collection_id);

                                // Get page and replace their form action
                                $pd_html_page = $this->_pd_fetch_page_and_replace_form_action($urls, 'login');

                                // Show alert message if we get
                                $this->_pd_show_alert();

                                // set html in transient
                                set_transient( 'pd_product_login_page_'.$pd_product_id, $pd_html_page);
                            }

                            // Get forgot password page
                            if($_GET['_pd_forgot_password'] && $_GET['_pd_forgot_password'] == 1) {
                                // check if product forgot password page is  available in transient, if yes then don't send ajax request
                                if (false === ($pd_html_page = get_transient('pd_product_forgot_password_page_'.$pd_product_id))) {

                                    // Get domain urls
                                    $urls = $this->_pd_get_urls($api_key, $pd_type, $pd_product_id, $pd_collection_id);

                                    // Get PD page and replace their form action
                                    $pd_html_page = $this->_pd_fetch_page_and_replace_form_action($urls, 'forgot_password');

                                    // Set html in to transient
                                    set_transient( 'pd_product_forgot_password_page_'.$pd_product_id, $pd_html_page);
                                }
                            }

                            // Get reset password page
                            if(sanitize_text_field($_GET['_pd_reset_code'])) {
                                // check if product reset password page is  available in transient, if yes then don't send ajax request
                                if (false === ($pd_html_page = get_transient('pd_product_reset_password_page_'.$pd_product_id))) {

                                    // Get domain urls
                                    $urls = $this->_pd_get_urls($api_key, $pd_type, $pd_product_id, $pd_collection_id);

                                    // Get PD page and replace their form action
                                    $pd_html_page = $this->_pd_fetch_page_and_replace_form_action($urls, 'reset_password');

                                    // Set html in to transient
                                    set_transient( 'pd_product_reset_password_page_'.$pd_product_id, $pd_html_page);
                                }

                                // Replace reset code field value
                                $pd_html_page = preg_replace("~name=\"code\" value=\".*\"~i", "name='code' value='".sanitize_text_field($_GET['_pd_reset_code'])."'", $pd_html_page);

                                // Replace reset password form action
                                $reset_password_form_action = add_query_arg(array('_pd_reset_password_submit' => true, '_pd_login' => false, '_pd_forgot_password' => false, '_pd_forgot_password_submit' => false, '_pd_alert' => false), home_url(add_query_arg(array($_GET), $wp->request)));

                                // Replace set reset password form action
                                $pd_html_page = preg_replace("~action=\".*\"~i", "action='".$reset_password_form_action."'", $pd_html_page);
                            }

                            // Get register page
                            if($_GET['_pd_register'] && $_GET['_pd_register'] == 1) {

                                // Check if not allow registration for this product then show 404 page
                                if(!$pd_product_data->is_free_registration) {
                                    return $this->_pd_show_restricted_area_or_error_page('404 :: Page Not Found!', '404 - Page Not Found');
                                }

                                // check if product register page is  available in transient, if yes then don't send ajax request
                                if (false === ($pd_html_page = get_transient('pd_product_register_page_'.$pd_product_id))) {

                                    // Get domain urls
                                    $urls = $this->_pd_get_urls($api_key, $pd_type, $pd_product_id, $pd_collection_id);

                                    // Get PD page and replace their form action
                                    $pd_html_page = $this->_pd_fetch_page_and_replace_form_action($urls, 'register');

                                    // Set html in to transient
                                    set_transient( 'pd_product_register_page_'.$pd_product_id, $pd_html_page);
                                }

                                // Replace reset password form action
                                $register_form_action = add_query_arg(array('_pd_register_submit' => true, '_pd_reset_password_submit' => false, '_pd_login' => false, '_pd_forgot_password' => false, '_pd_forgot_password_submit' => false, '_pd_alert' => false), home_url(add_query_arg(array($_GET), $wp->request)));

                                // Replace set reset password form action
                                $pd_html_page = preg_replace("~action=\".*\"~i", "action='".$register_form_action."'", $pd_html_page);
                            }

                            // Show alert message if we get
                            $this->_pd_show_alert();

                            // If we found the page in transient then simple echo the page from transient
                            esc_html(_e($pd_html_page));
                            exit();
                        }
                    }

                    // Logout work
                    if(isset($_GET['_pd_logout']) && $_GET['_pd_logout'] == 1) {
                        // Unset session of current product/collection
                        unset($_SESSION['_pd_auth_p_'.$pd_product_id]);

                        // redirect to current page
                        $params = array('page_id' => $post->ID);
                        return $this->_redirect_to_page($params);
                    }
                }

                // If type is collection then work according to collection
                if($pd_type == 'collection') {

                    // check if collection detail is available in transient, if yes then don't send request to API
                    if (false === ($pd_collection_data = get_transient('pd_collection_data_'.$pd_collection_id))) {

                        $url = PRODUCTDYNO_API_URL.'collections';
                        $data['id'] = $pd_collection_id;
                        $pd_collection_data = $this->_wpRemoteRequestAPI($api_key, $url, 'GET', $data);

                        // set collection data in transient
                        set_transient( 'pd_collection_data_'.$pd_collection_id, $pd_collection_data);
                    }

                    // Check if collection is set to public access the show the content page
                    if($pd_collection_data->is_public) {
                        return $page_template_path;
                    }

                    // check the collection session
                    if(!$_SESSION['_pd_auth_c_'.$pd_collection_id]) {

                        if(!empty($pd_collection_id)) {

                            // check if product login page is  available in transient, if yes then don't send ajax request
                            if (false === ($pd_html_page = get_transient('pd_collection_login_page_'.$pd_collection_id))) {

                                // Get domain urls
                                $urls = $this->_pd_get_urls($api_key, $pd_type, $pd_collection_id, $pd_collection_id);

                                // Get page and replace form action
                                $pd_html_page = $this->_pd_fetch_page_and_replace_form_action($urls, 'login');

                                // Show alert message if we get
                                $this->_pd_show_alert();

                                // set html in transient
                                set_transient( 'pd_collection_login_page_'.$pd_collection_id, $pd_html_page);
                            }

                            if($_GET['_pd_forgot_password'] && $_GET['_pd_forgot_password'] == 1) {
                                // check if product forgot password page is  available in transient, if yes then don't send ajax request
                                if (false === ($pd_html_page = get_transient('pd_collection_forgot_password_page_'.$pd_collection_id))) {

                                    // Get domain urls
                                    $urls = $this->_pd_get_urls($api_key, $pd_type, $pd_collection_id, $pd_collection_id);

                                    // Get PD page and replace their form action
                                    $pd_html_page = $this->_pd_fetch_page_and_replace_form_action($urls, 'forgot_password');

                                    // Set html in to transient
                                    set_transient( 'pd_collection_forgot_password_page_'.$pd_collection_id, $pd_html_page);
                                }
                            }

                            if(sanitize_text_field($_GET['_pd_reset_code'])) {
                                // check if product reset password page is  available in transient, if yes then don't send ajax request
                                if (false === ($pd_html_page = get_transient('pd_collection_reset_password_page_'.$pd_collection_id))) {

                                    // Get domain urls
                                    $urls = $this->_pd_get_urls($api_key, $pd_type, $pd_collection_id, $pd_collection_id);

                                    // Get PD page and replace their form action
                                    $pd_html_page = $this->_pd_fetch_page_and_replace_form_action($urls, 'reset_password');

                                    // Set html in to transient
                                    set_transient( 'pd_collection_reset_password_page_'.$pd_collection_id, $pd_html_page);
                                }

                                // Replace reset code field value
                                $pd_html_page = preg_replace("~name=\"code\" value=\".*\"~i", "name='code' value='".sanitize_text_field($_GET['_pd_reset_code'])."'", $pd_html_page);

                                // Replace reset password form action
                                $reset_password_form_action = add_query_arg(array('_pd_reset_password_submit' => true, '_pd_login' => false, '_pd_forgot_password' => false, '_pd_forgot_password_submit' => false, '_pd_alert' => false), home_url(add_query_arg(array($_GET), $wp->request)));

                                // Replace set reset password form action
                                $pd_html_page = preg_replace("~action=\".*\"~i", "action='".$reset_password_form_action."'", $pd_html_page);
                            }

                            // Get register page
                            if($_GET['_pd_register'] && $_GET['_pd_register'] == 1) {

                                // Check if not allow registration for this product then show 404 page
                                if(!$pd_collection_data->is_free_registration) {
                                    return $this->_pd_show_restricted_area_or_error_page('404 :: Page Not Found!', '404 - Page Not Found');
                                }

                                // check if product register page is  available in transient, if yes then don't send ajax request
                                if (false === ($pd_html_page = get_transient('pd_collection_register_page_'.$pd_product_id))) {

                                    // Get domain urls
                                    $urls = $this->_pd_get_urls($api_key, $pd_type, $pd_product_id, $pd_collection_id);

                                    // Get PD page and replace their form action
                                    $pd_html_page = $this->_pd_fetch_page_and_replace_form_action($urls, 'register');

                                    // Set html in to transient
                                    set_transient( 'pd_collection_register_page_'.$pd_product_id, $pd_html_page);
                                }

                                // Replace register form action
                                $register_form_action = add_query_arg(array('_pd_register_submit' => true, '_pd_reset_password_submit' => false, '_pd_login' => false, '_pd_forgot_password' => false, '_pd_forgot_password_submit' => false, '_pd_alert' => false), home_url(add_query_arg(array($_GET), $wp->request)));

                                // Replace set register form action
                                $pd_html_page = preg_replace("~action=\".*\"~i", "action='".$register_form_action."'", $pd_html_page);
                            }

                            // Show alert message if we get
                            $this->_pd_show_alert();

                            // If we found the page in transient then simple echo the page from transient
                            esc_html(_e($pd_html_page));
                            exit();
                        }
                    }

                    // Logout work
                    if(isset($_GET['_pd_logout']) && $_GET['_pd_logout'] == 1) {
                        // Unset session of current product/collection
                        unset($_SESSION['_pd_auth_c_'.$pd_collection_id]);

                        // redirect to current page
                        $params = array('page_id' => $post->ID);
                        return $this->_redirect_to_page($params);
                    }
                }

                // Collection product related work
                if($pd_type == 'collection' && !empty($pd_collection_product_id)) {

                    // check if we find collection session
                    if(isset($_SESSION['_pd_auth_c_'.$pd_collection_id])) {

                        $active_products_session = (isset($_SESSION['_pd_auth_c_'.$pd_collection_id]['active_product_ids']) ? $_SESSION['_pd_auth_c_'.$pd_collection_id]['active_product_ids'] : null);


                        if(count($active_products_session) > 0 && in_array($pd_collection_product_id, $active_products_session)) {

                            // If has access return template page
                            return $page_template_path;

                        } else {
                            // show restricted area page
                            if($pd_no_access_page_id = get_post_meta(get_the_ID(), 'pd_no_access_page_id', true)) {
                                $site_url = get_site_url();

                                $new_url_params = add_query_arg( array(
                                    'page_id' => $pd_no_access_page_id,
                                ), $site_url);

                                wp_safe_redirect($new_url_params); exit();

                            } else {
                                return $this->_pd_show_restricted_area_or_error_page('403 :: Forbidden Access', 'Restricted Area!');
                            }
                        }
                    }
                }
            }
        }

        // @todo: remove all parameters before showing to original content page
        return $page_template_path;
    }

    /**
     * Member Login submit functionality
     * @param  [type] $api_key          [description]
     * @param  [type] $type             [description]
     * @param  [type] $pd_product_id    [description]
     * @param  [type] $pd_collection_id [description]
     * @return [type]                   [description]
     */
    private function _pd_member_login($api_key, $type, $pd_product_id, $pd_collection_id)
    {
        global $wp;
        global $post;

        // API Url
        $url = PRODUCTDYNO_API_URL.'members/get-by-credentials';

        // Sanitize input fields
        $email = sanitize_text_field($_POST['email']);
        $password = sanitize_text_field($_POST['password']);

        // Validation check
        if(!is_email($email)) {
            // Return validation error
            return $this->_pd_validation_error('Please enter a valid email address.', true);
        }

        // Validation Password check
        if(empty($password)) {
            // Return validation error
            return $this->_pd_validation_error('Please enter password.', true);
        }

        // Prepare data for API
        $data = array('email' => sanitize_email($email), 'password' => $password);

        // Check type for setting param and session key
        if($type == 'product') {
            $data['product_id'] = $pd_product_id;

            // Session Key set according to type
            $set_member_session_key = '_pd_auth_p_'.$pd_product_id;
        }

        // Check type for setting param and session key
        if($type == 'collection') {
            $data['collection_id'] = $pd_collection_id;

            // Session Key set according to type
            $set_member_session_key = '_pd_auth_c_'.$pd_collection_id;
        }

        // Send request to our PD api to verify member access
        $member_access_response = $this->_wpRemoteRequestAPI($api_key, $url, 'GET', $data);

        // If member has access
        if(isset($member_access_response) && $member_access_response->status) {
            // Member has access to this product/collection then, simply show the content page and set session

            $_SESSION[$set_member_session_key] = array('member_id' => $member_access_response->member_id, 'first_name' => $member_access_response->first_name, 'active_product_ids' => (isset($member_access_response->active_product_ids) ? $member_access_response->active_product_ids : null));

            // redirect to original content page
            return $this->_redirect_to_page($params);

        } else {
            // Memerb has not access to this product/collection or credentials do not match, show the error message
            $url_with_params = home_url(add_query_arg(array($_GET), $wp->request));

            // add error param to url to show the message
            $new_url_params = add_query_arg( array(
                '_pd_login' => true,
                '_pd_register' => false,
                '_pd_login_submit' => false,
                '_pd_alert' => (isset($member_access_response->message) ? $member_access_response->message : ''),
                '_pd_alert_type' => (isset($member_access_response->success) && $member_access_response->success == 1 ? 'success' : 'error'),
            ), $url_with_params );

            // Set redirect url with additional params
            $redirect_url = esc_url_raw($new_url_params);

            // redirect to login page with error
            wp_safe_redirect($redirect_url); exit();
        }
    }

    /**
     * Member forgot password submit functionality
     * @param  [type] $api_key          [description]
     * @param  [type] $type             [description]
     * @param  [type] $pd_product_id    [description]
     * @param  [type] $pd_collection_id [description]
     * @return [type]                   [description]
     */
    private function _pd_member_forgot_password($api_key, $type, $pd_product_id, $pd_collection_id)
    {
        global $wp;

        // API Url
        $url = PRODUCTDYNO_API_URL.'members/forgot-password';

        // Sanitize input field
        $email = sanitize_text_field($_POST['email']);

        // Validation check
        if(!is_email($email)) {
            // Return validation error
            return $this->_pd_validation_error('Please enter a valid email address.', false, true);
        }

        // Prepare data for API
        $data = array('email' => sanitize_email($email));

        // Check type for setting param and session key
        if($type == 'product') {
            $data['product_id'] = $pd_product_id;
        }

        // Check type for setting param and session key
        if($type == 'collection') {
            $data['collection_id'] = $pd_collection_id;
        }

        // Send request to our PD api to verify member access
        $member_forgot_password_response = $this->_wpRemoteRequestAPI($api_key, $url, 'POST', $data);

        // If member has access
        if(isset($member_forgot_password_response)) {

            $url_with_params = home_url(add_query_arg(array($_GET), $wp->request));

            // add error param to url to show the message
            $new_url_params = add_query_arg( array(
                '_pd_login' => false,
                '_pd_register' => false,
                '_pd_forgot_password_submit' => false,
                '_pd_alert' => (isset($member_forgot_password_response->message) ? $member_forgot_password_response->message : ''),
                '_pd_alert_type' => (isset($member_forgot_password_response->success) && $member_forgot_password_response->success == 1 ? 'success' : 'error'),
            ), $url_with_params );

            // Set redirect url with additional params
            $redirect_url = esc_url_raw($new_url_params);

            if($member_forgot_password_response->success == 1) {

                // Show alert message if we get
                $this->_pd_show_alert();
            } else {
                $this->_pd_show_alert();
            }

            // redirect to login page with error
            wp_safe_redirect($redirect_url); exit();

        } else {
            $this->_pd_show_alert();

        }
    }

    /**
     * member reset password functionality
     * @param  [type] $api_key          [description]
     * @param  [type] $type             [description]
     * @param  [type] $pd_product_id    [description]
     * @param  [type] $pd_collection_id [description]
     * @return [type]                   [description]
     */
    private function _pd_member_reset_password($api_key, $type, $pd_product_id, $pd_collection_id)
    {
        global $wp;

        // API Url
        $url = PRODUCTDYNO_API_URL.'members/reset-password';

        // Get reset code from url
        $pd_reset_code = sanitize_text_field($_GET['_pd_reset_code']);

        // Sanitize input fields
        $email = sanitize_text_field($_POST['email']);
        $password = sanitize_text_field($_POST['password']);
        $password_confirmation = sanitize_text_field($_POST['password_confirmation']);

        // Validation check
        if(!is_email($email)) {
            // Return validation error
            return $this->_pd_validation_error('Please enter a valid email address.', false, false, $pd_reset_code);
        }

        // Validation Password check
        if(empty($password)) {
            // Return validation error
            return $this->_pd_validation_error('Please enter password.', false, false, $pd_reset_code);
        }

        // Validation Password check
        if(empty($password_confirmation)) {
            // Return validation error
            return $this->_pd_validation_error('Please enter confirm password.', false, false, $pd_reset_code);
        }

        // Validation Password check
        if(strlen($password) < 6) {
            return $this->_pd_validation_error('Password must be at least 6 characters.', false, false, $pd_reset_code);
        }

        // Validation confirm Password check
        if($password_confirmation != $password) {
            return $this->_pd_validation_error('Password confirmation does not match.', false, false, $pd_reset_code);
        }

        // Prepare data for API
        $data = array(
            'code' => $pd_reset_code,
            'email' => sanitize_email($email),
            'password' => $password
        );

        // Check type for setting param and session key
        if($type == 'product') {
            $data['product_id'] = $pd_product_id;
        }

        // Check type for setting param and session key
        if($type == 'collection') {
            $data['collection_id'] = $pd_collection_id;
        }

        // Send request to our PD api to verify member access
        $member_reset_password_response = $this->_wpRemoteRequestAPI($api_key, $url, 'POST', $data);
        // If member has access
        if(isset($member_reset_password_response) && $member_reset_password_response->success) {

            $url_with_params = home_url(add_query_arg(array($_GET), $wp->request));

            // add error param to url to show the message
            $new_url_params = add_query_arg( array(
                '_pd_login' => false,
                '_pd_forgot_password_submit' => false,
                '_pd_reset_password_submit' => false,
                '_pd_reset_code' => false,
                '_pd_alert' => (isset($member_reset_password_response->message) ? $member_reset_password_response->message.' Please login to continue ' : ''),
                '_pd_alert_type' => (isset($member_reset_password_response->success) && $member_reset_password_response->success == 1 ? 'success' : 'error'),
            ), $url_with_params );

            // Set redirect url with additional params
            $redirect_url = esc_url_raw($new_url_params);

            // redirect to login page with error
            wp_safe_redirect($redirect_url); exit();

        } else {
            $url_with_params = home_url(add_query_arg(array($_GET), $wp->request));

            // add error param to url to show the message
            $new_url_params = add_query_arg( array(
                '_pd_login' => false,
                '_pd_forgot_password_submit' => false,
                '_pd_reset_password_submit' => false,
                '_pd_alert' => (isset($member_reset_password_response->message) ? $member_reset_password_response->message : ''),
            ), $url_with_params );

            // Set redirect url with additional params
            $redirect_url = esc_url_raw($new_url_params);

            // redirect to login page with error
            wp_safe_redirect($redirect_url); exit();
        }
    }

    /**
     * member register functionality
     * @param  [type] $api_key          [description]
     * @param  [type] $type             [description]
     * @param  [type] $pd_product_id    [description]
     * @param  [type] $pd_collection_id [description]
     * @return [type]                   [description]
     */
    private function _pd_member_register($api_key, $type, $pd_product_id, $pd_collection_id)
    {
        global $wp;

        // API Url
        $url = PRODUCTDYNO_API_URL.'members/add';

        // Sanitize input fields
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $password = sanitize_text_field($_POST['password']);
        $password_confirmation = sanitize_text_field($_POST['password_confirmation']);

        // Validation check
        if(empty($first_name)) {
            // Return validation error
            return $this->_pd_validation_error('Please enter first name.', false, false, false, true);
        }

        // Validation check
        if(empty($last_name)) {
            // Return validation error
            return $this->_pd_validation_error('Please enter last name.', false, false, false, true);
        }

        // Validation check
        if(!is_email($email)) {
            // Return validation error
            return $this->_pd_validation_error('Please enter a valid email address.', false, false, false, true);
        }

        // Validation Password check
        if(empty($password)) {
            // Return validation error
            return $this->_pd_validation_error('Please enter password.', false, false, false, true);
        }

        // Validation Password check
        if(empty($password_confirmation)) {
            // Return validation error
            return $this->_pd_validation_error('Please enter confirm password.', false, false, false, true);
        }

        // Validation Password check
        if(strlen($password) < 6) {
            return $this->_pd_validation_error('Password must be at least 6 characters.', false, false, false, true);
        }

        // Validation confirm Password check
        if($password_confirmation != $password) {
            return $this->_pd_validation_error('Password confirmation does not match.', false, false, false, true);
        }

        // Prepare data for API
        $data = array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => sanitize_email($email),
            'password' => $password
        );

        // Check type for setting param and session key
        if($type == 'product') {
            $data['product_id'] = $pd_product_id;
        }

        // Check type for setting param and session key
        if($type == 'collection') {
            $data['collection_id'] = $pd_collection_id;
        }

        // Send request to our PD api to verify member access
        $member_register_response = $this->_wpRemoteRequestAPI($api_key, $url, 'POST', $data);
        // If member has access
        if(isset($member_register_response) && $member_register_response->status) {

            // Login member after successfully register
            $this->_pd_member_login($api_key, $type, $pd_product_id, $pd_collection_id);

        } else {
            $url_with_params = home_url(add_query_arg(array($_GET), $wp->request));

            // add error param to url to show the message
            $new_url_params = add_query_arg( array(
                '_pd_login' => false,
                '_pd_forgot_password_submit' => false,
                '_pd_reset_password_submit' => false,
                '_pd_reset_code' => false,
                '_pd_register_submit' => false,
                '_pd_alert' => (isset($member_register_response->message) ? $member_register_response->message : ''),
            ), $url_with_params );

            // Set redirect url with additional params
            $redirect_url = esc_url_raw($new_url_params);

            // redirect to login page with error
            wp_safe_redirect($redirect_url); exit();

            // Show alert message if we get
            $this->_pd_show_alert();
        }
    }

    /**
     * get all public urls to show pages
     * @param  [type] $api_key          [description]
     * @param  [type] $type             [description]
     * @param  [type] $pd_product_id    [description]
     * @param  [type] $pd_collection_id [description]
     * @return [type]                   [description]
     */
    private function _pd_get_urls($api_key, $type, $pd_product_id, $pd_collection_id)
    {
        // Get domain url from PD API
        $url = PRODUCTDYNO_API_URL.'domain/urls';

        // Set id according to type
        $pid_or_cid = ($type == 'product' ? $pd_product_id : $pd_collection_id);

        // Prepare data for PD API call
        $data = array('id' => $pid_or_cid, 'type' => $type);

        // Send API call to get Domain urls
        $urls = $this->_wpRemoteRequestAPI($api_key, $url, 'GET', $data);

        // return response
        return $urls;

    }

    /**
     * Fetch page according to type and change their form action
     * @param  [type] $urls     [description]
     * @param  [type] $url_type [description]
     * @return [type]           [description]
     */
    private function _pd_fetch_page_and_replace_form_action($urls, $url_type)
    {
        if($urls) {
            global $wp;
            global $post;

            // Set params when user click on logo then redirect to main product/collection page
            $logo_action_url = add_query_arg(array('page_id' => $post->ID), get_site_url());

            // If we need to fetch login page html
            if($url_type == 'login') {

                // define login page form action
                $login_form_action = add_query_arg(array('_pd_login_submit' => true, '_pd_alert' => false), home_url(add_query_arg(array($_GET), $wp->request)));

                $forgot_password_action_button = add_query_arg(array('_pd_login' => false, '_pd_forgot_password' => true, '_pd_alert' => false), home_url(add_query_arg(array($_GET), $wp->request)));
                $register_action_button = add_query_arg(array('_pd_login' => false, '_pd_register' => true, '_pd_alert' => false), home_url(add_query_arg(array($_GET), $wp->request)));

                // Fetch HTML from URL
                $login_html = $this->_pd_get_page_html($urls->login_url);

                // Replace form action in HTML
                $login_html = str_ireplace($urls->login_url, $login_form_action, $login_html);

                // forgot password action button
                $login_html = str_ireplace($urls->forgot_password, $forgot_password_action_button, $login_html);

                // register password action button
                $login_html = str_ireplace($urls->register, $register_action_button, $login_html);

                // Replace PD logo action url
                $login_html = str_ireplace($urls->url, $logo_action_url, $login_html);

                return $login_html;
            }

            // If we need to fetch forgot password page html
            if($url_type == 'forgot_password') {

                // define remember password button action
                $remember_password_login_btn_action = add_query_arg(array('_pd_login' => true, '_pd_forgot_password' => false, '_pd_alert' => false), home_url(add_query_arg(array($_GET), $wp->request)));

                // Forgot password form action
                $forgot_password_action = add_query_arg(array('_pd_login' => false, '_pd_forgot_password_submit' => true, '_pd_alert' => false), home_url(add_query_arg(array($_GET), $wp->request)));

                // Fetch HTML from URL
                $forgot_password_html = $this->_pd_get_page_html($urls->forgot_password);

                // Replace form action in HTML
                $forgot_password_html = str_ireplace($urls->forgot_password, $forgot_password_action, $forgot_password_html);

                // Rememer password - Back to login action
                $forgot_password_html = str_ireplace($urls->login_url, $remember_password_login_btn_action, $forgot_password_html);

                // Replace PD logo action url
                $forgot_password_html = str_ireplace($urls->url, $logo_action_url, $forgot_password_html);

                return $forgot_password_html;
            }

            // If we need to fetch reset password page html
            if($url_type == 'reset_password') {

                // define remember password button action
                $remember_password_login_btn_action = add_query_arg(array('_pd_login' => true, '_pd_reset_code' => false, '_pd_alert' => false), home_url(add_query_arg(array($_GET), $wp->request)));

                // Reset password form action
                $reset_password_action = add_query_arg(array('_pd_reset_password_submit' => true, '_pd_login' => false, '_pd_forgot_password' => false, '_pd_forgot_password_submit' => false, '_pd_alert' => false), home_url(add_query_arg(array($_GET), $wp->request)));

                // Fetch HTML from URL
                $reset_password_html = $this->_pd_get_page_html($urls->reset_password.'?code='.sanitize_text_field($_GET['_pd_reset_code']));

                // Replace form action in HTML
                $reset_password_html = str_ireplace($urls->reset_password, $reset_password_action, $reset_password_html);

                // Rememer password - Back to login action
                $reset_password_html = str_ireplace($urls->login_url, $remember_password_login_btn_action, $reset_password_html);

                // Replace PD logo action url
                $reset_password_html = str_ireplace($urls->url, $logo_action_url, $reset_password_html);

                return $reset_password_html;
            }

            // If we need to fetch register page html
            if($url_type == 'register') {

                // define remember password button action
                $register_login_btn_action = add_query_arg(array('_pd_login' => true, '_pd_register' => false, '_pd_alert' => false), home_url(add_query_arg(array($_GET), $wp->request)));

                // Reset password form action
                $register_action = add_query_arg(array('_pd_register_submit' => true, '_pd_reset_password_submit' => false, '_pd_login' => false, '_pd_forgot_password' => false, '_pd_forgot_password_submit' => false, '_pd_alert' => false), home_url(add_query_arg(array($_GET), $wp->request)));

                // Fetch HTML from URL
                $register_html = $this->_pd_get_page_html($urls->register);

                // Replace form action in HTML
                $register_html = str_ireplace($urls->register, $register_action, $register_html);

                // Rememer password - Back to login action
                $register_html = str_ireplace($urls->login_url, $register_login_btn_action, $register_html);

                // Replace PD logo action url
                $register_html = str_ireplace($urls->url, $logo_action_url, $register_html);

                return $register_html;
            }
        }
    }
//sslverify
    /**
     * Get Page HTML
     * @param  [type] $url [description]
     * @return [type]      [description]
     */
    public function _pd_get_page_html($url)
    {
        // Call to get page html response
        $response = wp_remote_get($url, array( 'timeout' => 60, 'httpversion' => '1.1'));

        // If fail or get error then show error screen
        if (is_wp_error( $response)) {
            $error_message = $response->get_error_message();
            return $this->_pd_show_restricted_area_or_error_page('Something went wrong!', $error_message);
        }

        if (is_array($response)) {
            // If response ok then send body content
            if($response['response']['code'] == 200) {
                return $response['body'];
            } else {
                // If response error then show error screen
                $error_message = $response['response']['message'];
                $error_code = $response['response']['code'];
                return $this->_pd_show_restricted_area_or_error_page($error_code.' :: '.$error_message, $error_message);
            }
        }
    }

    /**
     * Redirect to page with parameters
     * @param  array  $params [description]
     * @return [type]         [description]
     */
    private function _redirect_to_page($params = array())
    {
        // Get site url
        $site_url = get_site_url();

        // Set params
        $params = add_query_arg($params, $site_url);

        // Redirect to define action
        wp_safe_redirect($params);
    }

    /**
     * Show restricted area page if member has not access to any particular collection product
     * @return [type] [description]
     */
    private function _pd_show_restricted_area_or_error_page($title, $message)
    {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title><?php echo $title; ?> </title>

            <style>
                html, body {
                    height: 100%;
                }

                body {
                    margin: 0;
                    padding: 0;
                    width: 100%;
                    color: #B0BEC5;
                    display: table;
                    font-weight: 100;
                    font-family: 'arial';
                }

                .container {
                    text-align: center;
                    display: table-cell;
                    vertical-align: middle;
                }

                .content {
                    text-align: center;
                    display: inline-block;
                }

                .title {
                    font-size: 52px;
                    margin-bottom: 40px;
                }
            </style>
        </head>
        <body>
        <div class="container">
            <div class="content">
                <div class="title"><?php echo $message; ?></div>
            </div>
        </div>
        </body>
        </html>

        <?php
        exit();
        // return PRODUCTDYNO_PLUGIN_DIR . '/public/partials/productdyno-public-restricted-display.php';
    }

    /**
     * PD validation error message
     * @return [type] [description]
     */
    private function _pd_validation_error($message, $login = false, $forgot_password = false, $reset_password = false, $register = false)
    {
        global $wp;

        $url_with_params = home_url(add_query_arg(array($_GET), $wp->request));

        // add error param to url to show the message
        $new_url_params = add_query_arg( array(
            '_pd_login' => $login,
            '_pd_forgot_password' => $forgot_password,
            '_pd_reset_code' => $reset_password,
            '_pd_register' => $register,
            '_pd_login_submit' => false,
            '_pd_forgot_password_submit' => false,
            '_pd_reset_password_submit' => false,
            '_pd_register_submit' => false,
            '_pd_alert' => $message,
        ), $url_with_params );

        // Set redirect url with additional params
        $redirect_url = esc_url_raw($new_url_params);

        // redirect to login page with error
        wp_safe_redirect($redirect_url); exit();
    }

    /**
     * centraize wp_remote curl request for API
     * @param  [type] $url    [description]
     * @param  [type] $method [description]
     * @param  array  $data   [description]
     * @return [type]         [description]
     */
    private function _wpRemoteRequestAPI($api_key, $url, $method, $data = array())
    {
        $api_key = $api_key;

        $response = wp_remote_post( $url, array(
                'method' => $method,
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array('x-api-key' => $api_key),
                'body' => $data,
                'cookies' => array()
            )
        );

        $response = json_decode($response['body']);

        return $response;
    }

    /**
     * Show alert messages
     * @return void [type] [description]
     */
    private function _pd_show_alert()
    {
        $pd_alert_message = sanitize_text_field($_GET['_pd_alert']);
        $alert_type = (isset($_GET['_pd_alert_type']) ? sanitize_text_field($_GET['_pd_alert_type']) : 'error');

        // Check error message through $_GET param
        if(!empty($pd_alert_message)) {
            ?>
            <style type="text/css">
                .pd_alert_box {
                    position: fixed;
                    right: 0px;
                    top: 10;
                    z-index: 9000000;
                }

                .pd_alert_box .pd_alert_component {
                    margin: 0 0 5px;
                    padding: 6px 12px;
                    min-height: 40px;
                }

                .pd_alert_component.error {
                    background-color: #ffffff;
                    color: #d94e50;
                    border-left: 4px solid #d94f4f;
                    box-shadow: 3px 5px 20px #cacaca;
                }

                .pd_alert_component.success {
                    background-color: #ffffff;
                    color: #18a511;
                    border-left: 4px solid #18a511;
                    box-shadow: 3px 5px 20px #cacaca;
                }

                .pd_alert_component__content {
                    margin: 0.5em 0;
                    font-family: arial;
                    font-size: 12px;
                }
            </style>
            <div class="pd_alert_box">
                <div class="pd_alert_component <?php echo $alert_type; ?> is-dismissible">
                    <div class="pd_alert_component__content"><?php echo $pd_alert_message;?></div>
                </div>
            </div>
            <?php
        }
    }

}