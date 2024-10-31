<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       productdyno.com
 * @since      1.0.0
 *
 * @package    Productdyno
 * @subpackage Productdyno/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Productdyno
 * @subpackage Productdyno/admin
 * @author     ProductDyno <hello@productdyno.com>
 */
class Productdyno_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/productdyno-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/productdyno-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Register the menu for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function pd_admin_menu() {

		// Add Plugin Menu
		add_menu_page( 'Productdyno' . ' - Dashboard', 'ProductDyno', 'manage_options', 'productdyno', array($this, 'pd_page_dashboard'), plugin_dir_url( __FILE__ ) . 'images/favicon.png', '64.419' );
	}

	/**
	 * allow redirection, even if plugin starts to send output to the browser
	 * @return [type] [description]
	 */
	function _pd_do_output_buffer() {
        ob_start();
	}

	/**
	 * PD Plugin dashboard page
	 *
	 * @since    1.0.0
	 */
	public function pd_page_dashboard() {
		
		// Check if user want to deactivat the PD plugin then we need to deactivate the plugin
		if($_GET['_pd_deactivate'] && $_GET['_pd_deactivate'] == 1) {
			// Remove PD API Key from DB
			update_option('_productdyno_api_key', null);
			ob_start();

			// Redirect back to dashboard
			wp_safe_redirect( add_query_arg( array( 'page' => 'productdyno'), admin_url( 'plugins.php'))); exit();
		}

		$api_key = get_option('_productdyno_api_key');
		require_once plugin_dir_path( __FILE__ ) . 'partials/productdyno-admin-display.php';
	}

	/**
	 * ProductDyno Verify API Key
	 * @return [type] [description]
	 */
	public function pd_verify_api_key()
	{	
		$api_key = sanitize_text_field($_POST['_productdyno_api_key']);

	    $url = PRODUCTDYNO_API_URL.'verify';

	    $data = array(
	    	'_productdyno_api_key' => $api_key
	    );

		// Send curl request to verify user key
		$response = $this->_wpRemoteRequestAPI($api_key, $url, 'POST', $data);

		// If valid api key then store in db
		if($response->success && $response->success == true) {

			$option_name = '_productdyno_api_key' ;
			$new_value = $api_key;
			 
			if(get_option( $option_name ) !== false ) {
			    // The option already exists, so update it.
			    update_option( $option_name, $new_value );
			 
			} else {
			    // The option hasn't been created yet'.
			    add_option( $option_name, $new_value);
			}

			// Delete all cached pd data
			$this->pd_clear_all_cache_data(true);

			wp_safe_redirect( add_query_arg( array( 'page' => 'productdyno'), admin_url( 'plugins.php' ) ) );
		} else {
			wp_safe_redirect( add_query_arg( array( 'page' => 'productdyno&act=1', 'res' => 'inv' ), admin_url( 'plugins.php' ) ) );
		}
		
        exit;
	}

	/**
	 * PD Meta box dropdowns initialize
	 *
	 * @since    1.0.0
	 */
	public function pd_register_meta_box() {
        // Add the PD meta box - we'll render it on Pages and Posts
        $render_area_types = array('page', 'post');
        add_meta_box(
            'productdyno_meta_box',
            __( 'ProductDyno', 'productdyno' ),
            array($this, 'pd_meta_box_callback'),
            $render_area_types, 'side','high'
        );
	}

	/**
	 * PD Select Type Dropdown Callback
	 *
	 * @since    1.0.0
	 */
	public function pd_meta_box_callback($post) {

        // If plugin is connected then we need to show PD meta box
        $api_key = get_option('_productdyno_api_key');

        if(!empty($api_key)) {

            // Get type from db
            $pd_type = get_post_meta(get_the_ID(), 'pd_type', true);

            // Get product id from db
            $pd_product_id = get_post_meta(get_the_ID(), 'pd_product_id', true);

            // Get collection id from db
            $pd_collection_id = get_post_meta(get_the_ID(), 'pd_collection_id', true);

            // Get collection product id from db
            $pd_collection_product_id = get_post_meta(get_the_ID(), 'pd_collection_product_id', true);

            // get no access page id from db
            $pd_no_access_page_id = get_post_meta(get_the_ID(), 'pd_no_access_page_id', true);

            // Products dropdown related work
            $hide_products_dd_class = 'pd_hide';
            $pd_products_data = null;
            if(!empty($pd_product_id)) {
                $pd_products_data = $this->pd_get_products(true);
                $hide_products_dd_class = '';
            }

            // Collections dropdoown related work
            $hide_collections_dd_class = 'pd_hide';
            $pd_collections_data = null;
            if(!empty($pd_collection_id)) {
                $pd_collections_data = $this->pd_get_collections(true);
                $hide_collections_dd_class = '';
            }

            // Collections dropdoown related work
            $hide_collection_products_dd_class = 'pd_hide';
            $hide_pd_no_access_page = 'pd_hide';
            $pd_collection_products_data = null;
            if(!empty($pd_collection_id)) {
                $pd_collection_products_data = $this->pd_get_collection_products(true, $pd_collection_id);
                $hide_collection_products_dd_class = '';
                $hide_pd_no_access_page = '';
            }

            // Collections products no access page related work
            $pd_wordpress_pages_data = null;
            $pd_wordpress_pages_data = $this->pd_get_wordpress_pages(true);


            // Drodowns of meta box
            ?>
            <div class="pd-mt-10 pd_select_type_dropdown">
                <label>Select Type</label><select name="pd_type" class="form-control pd_select_type_dd pd-dropdown">

                    <option value="">Select</option> <option value="product" <?php selected($pd_type, 'product'); ?>>Product</option> <option value="collection" <?php selected($pd_type, 'collection'); ?>>Collection</option>

                </select>
            </div>

            <!-- Products dropdown -->
            <?php if($pd_products_data): ?>
                <div class="pd-mt-10 pd_product_dropdown <?php echo $hide_products_dd_class; ?>">
                    <label>Select Product</label>
                    <select name="pd_product_id" class="form-control pd_products_dd pd-dropdown">
                        <option value="">Select</option>
                        <?php foreach ($pd_products_data as $product):?>
                            <option value="<?php echo $product->id;?>" <?php selected($pd_product_id, $product->id); ?>><?php echo $product->name;?></option>
                        <?php endforeach;?>
                    </select>
                </div>
            <?php else :?>
                <div class="pd-mt-10 pd_product_dropdown <?php echo $hide_products_dd_class; ?>">
                    <label>Select Product</label>
                    <select name="pd_product_id" class="form-control pd_products_dd pd-dropdown">
                        <option value="">Select</option>
                    </select>
                </div>
            <?php endif;?>

            <!-- Collections dropdown -->
            <?php if($pd_collections_data): ?>
                <div class="pd-mt-10 pd_collection_dropdown <?php echo $hide_collections_dd_class; ?>">
                    <label>Select Collection</label>
                    <select name="pd_collection_id" class="form-control pd_collections_dd pd-dropdown">
                        <option value="">Select</option>
                        <?php foreach ($pd_collections_data as $collection):?>
                            <option value="<?php echo $collection->id;?>" <?php selected($pd_collection_id, $collection->id); ?>><?php echo $collection->name;?></option>
                        <?php endforeach;?>
                    </select>
                </div>
            <?php else :?>
                <div class="pd-mt-10 pd_collection_dropdown <?php echo $hide_collections_dd_class; ?>">
                    <label>Select Collection</label>
                    <select name="pd_collection_id" class="form-control pd_collections_dd pd-dropdown">
                        <option value="">Select</option>
                    </select>
                </div>
            <?php endif;?>

            <!-- Collection Products dropdown -->
            <?php if($pd_collection_products_data): ?>
                <div class="pd-mt-10 pd_collection_products_dropdown <?php echo $hide_collection_products_dd_class; ?>">
                    <label>Select Collection Product</label>
                    <select name="pd_collection_product_id" class="form-control pd_select_collection_product_dd pd-dropdown">
                        <option value="">Any</option>
                        <?php foreach ($pd_collection_products_data as $collection_product):?>
                            <option value="<?php echo $collection_product->product_id;?>" <?php selected($pd_collection_product_id, $collection_product->productID); ?>><?php echo $collection_product->productName;?></option>
                        <?php endforeach;?>
                    </select>
                </div>
            <?php else: ?>
                <div class="pd-mt-10 pd_collection_products_dropdown <?php echo $hide_collection_products_dd_class; ?>">
                    <label>Select Collection Product</label>
                    <select name="pd_collection_product_id" class="form-control pd_select_collection_product_dd pd-dropdown">
                        <option value="">Select</option>
                    </select>
                </div>
            <?php endif; ?>

            <!-- NO Access page for collection products -->
            <div class="pd-mt-10 pd_no_access_page_dropdown <?php echo $hide_pd_no_access_page; ?>">
                <label>Select No Access Page</label>
                <select name="pd_no_access_page_id" class="form-control pd_select_wordpress_page_dd pd-dropdown">
                    <option value="">Default</option>
                    <?php foreach ($pd_wordpress_pages_data as $wordpress_page):?>
                        <option value="<?php echo $wordpress_page->ID;?>" <?php selected($pd_no_access_page_id, $wordpress_page->ID); ?>><?php echo $wordpress_page->post_title;?></option>
                    <?php endforeach;?>
                </select>
            </div>

            <!-- End NO access page for collectio product -->

            <!-- Loader div -->
            <div class="lds-ellipsis pd-loader-div pd_hide"><div></div><div></div><div></div><div></div></div>

            <?php
        } else {
            echo '<p>Your ProductDyno account has not been connected to your website. Click <a href="'.admin_url('admin.php?page=productdyno&act=1').'">here</a> to connect.</p>';
        }
	}

	/**
	 * Get Products
	 * @param  boolean $is_call_from_meta_box [description]
	 * @return [type]                         [description]
	 */
	public function pd_get_products($is_call_from_meta_box = false)
	{	
		if($api_key = $this->_is_api_key_exist()) {
			// check if products are availabel in transient, if yes then don't send ajax request
			
			if (false === ($pd_products_data = get_transient('pd_products_data'))) {
			    // It wasn't there, so regenerate the data and save the transient
				$url = PRODUCTDYNO_API_URL.'products';
				$pd_products_data = $this->_wpRemoteRequestAPI($api_key, $url, 'GET');
			    set_transient( 'pd_products_data', $pd_products_data);
			}
			
			if($is_call_from_meta_box) {
				return $pd_products_data;
			} else {
				echo json_encode($pd_products_data); exit();
			}
		}
	}

	/**
	 * Get Collections
	 * @param  [type] $is_call_from_meta_box [description]
	 * @return [type]                        [description]
	 */
	public function pd_get_collections($is_call_from_meta_box)
	{	
		if($api_key = $this->_is_api_key_exist()) {

			// check if products are availabel in transient, if yes then don't send ajax request
			if (false === ($pd_collections_data = get_transient('pd_collections_data'))) {
			    // It wasn't there, so regenerate the data and save the transient
				$url = PRODUCTDYNO_API_URL.'collections';
				$pd_collections_data = $this->_wpRemoteRequestAPI($api_key, $url, 'GET');
			    set_transient( 'pd_collections_data', $pd_collections_data);
			}
			
			if($is_call_from_meta_box) {
				return $pd_collections_data;
			} else {
				echo json_encode($pd_collections_data); exit();
			}
		}
	}

	/**
	 * Get Collection Products
	 * @param  [type] $is_call_from_meta_box [description]
	 * @param  [type] $collection_id         [description]
	 * @return [type]                        [description]
	 */
	public function pd_get_collection_products($is_call_from_meta_box, $collection_id = null)
	{	
		if($api_key = $this->_is_api_key_exist()) {

			// check if products are availabel in transient, if yes then don't send ajax request
			if (false === ($pd_collection_products_data = get_transient('pd_collection_products_data_'.$data['collection_id']))) {
			    // It wasn't there, so regenerate the data and save the transient
				$url = PRODUCTDYNO_API_URL.'products';
				$data['collection_id'] = ($collection_id ? $collection_id : sanitize_text_field($_POST['collection_id']));
				$pd_collection_products_data = $this->_wpRemoteRequestAPI($api_key, $url, 'GET', $data);
			    set_transient( 'pd_collection_products_data_'.$data['collection_id'], $pd_collection_products_data);
			}

			if($is_call_from_meta_box) {
				return $pd_collection_products_data;
			} else {
				echo json_encode($pd_collection_products_data); exit();
			}
		}
	}

	/**
	 * Get Wordpress Pages
	 * @param  [type] $is_call_from_meta_box [description]
	 * @return [type]                        [description]
	 */
	public function pd_get_wordpress_pages($is_call_from_meta_box)
	{	
		if($api_key = $this->_is_api_key_exist()) {

			$args = array(
				'post_type' => 'page',
				'post_status' => 'publish'
			);

			$pd_wordpress_pages_data = get_pages($args); 
			
			if($is_call_from_meta_box) {
				return $pd_wordpress_pages_data;
			} else {
				echo json_encode($pd_wordpress_pages_data); exit();
			}
		}
	}

	/**
	 * save data into database
	 * @param  [type] $post_id [description]
	 * @return [type]          [description]
	 */
	public function pd_save_post_data($post_id)
	{	
		if (isset($_POST['pd_type'])) {
			// Sanitize input field
			$pd_type = sanitize_text_field($_POST['pd_type']);        
			update_post_meta($post_id, 'pd_type', $pd_type);      
		}

		if (isset($_POST['pd_product_id'])) {        
			if($_POST['pd_type'] == 'product') {
				// Sanitize input field
				$pd_product_id = sanitize_text_field($_POST['pd_product_id']);
				update_post_meta($post_id, 'pd_product_id', $pd_product_id);      
			} else {
				update_post_meta($post_id, 'pd_product_id', null);      
			}
		}

		if (isset($_POST['pd_collection_id'])) {        
			if($_POST['pd_type'] == 'collection') {
				// Sanitize input field
				$pd_collection_id = sanitize_text_field($_POST['pd_collection_id']);
				update_post_meta($post_id, 'pd_collection_id', $pd_collection_id);      
			} else {
				update_post_meta($post_id, 'pd_collection_id', null);      
			}
		}

		if (isset($_POST['pd_collection_product_id'])) {        
			if($_POST['pd_type'] == 'collection') {
				// Sanitize input field
				$pd_collection_product_id = sanitize_text_field($_POST['pd_collection_product_id']);
				update_post_meta($post_id, 'pd_collection_product_id', $pd_collection_product_id);      
			} else {
				update_post_meta($post_id, 'pd_collection_product_id', null);      
			}
		}

		if (isset($_POST['pd_no_access_page_id'])) {        
			if($_POST['pd_type'] == 'collection') {
				// Sanitize input field
				$pd_no_access_page_id = sanitize_text_field($_POST['pd_no_access_page_id']);
				update_post_meta($post_id, 'pd_no_access_page_id', $pd_no_access_page_id);      
			} else {
				update_post_meta($post_id, 'pd_no_access_page_id', null);      
			}
		}

	}

	/**
	 * Check if api key exist in database
	 * @return boolean [description]
	 */
	private function _is_api_key_exist()
	{
		$api_key = get_option('_productdyno_api_key');

		if($api_key) {
			return $api_key;
		}
	}

	/**
	 * Delete All Cache Data
	 * @return [type] [description]
	 */
	public function pd_clear_all_cache_data($is_call_from_method = false)
	{
		sleep(1);
		global $wpdb;

        // delete all "pd namespace" transients
        $sql = "
            DELETE 
            FROM {$wpdb->options}
            WHERE option_name like '\_transient__transient_pd_products_data%'
            OR option_name like '\_transient_timeout__transient_pd_products_data%'
            OR option_name like '\_transient_timeout___transient_pd_products_data%'
            OR option_name like '\_transient___transient_pd_products_data%'

            OR option_name like '\_transient___transient_pd_products_data_%'
            OR option_name like '\_transient__transient_pd_products_data_%'
            OR 	option_name like '\_transient_timeout___transient_pd_products_data_%'

            OR option_name like '\_transient_timeout__transient_pd_collections_data%'
            OR option_name like '\_transient_timeout___transient_pd_collections_data%'
            OR option_name like '\_transient___transient_pd_collections_data%'

            OR option_name like '\_transient___transient_pd_collections_data_%'
            OR option_name like '\_transient__transient_pd_collections_data_%'
            OR option_name like '\_transient_timeout___transient_pd_collections_data_%'

            OR option_name like '\_transient_timeout__transient__transient_pd_collection_products_data%'
            OR option_name like '\_transient_timeout___transient__transient_pd_collection_products_data%'
            OR option_name like '\_transient___transient__transient_pd_collection_products_data%'

            OR option_name like '\_transient___transient__transient_pd_collection_products_data_%'
            OR option_name like '\_transient__transient__transient_pd_collection_products_data_%'
            OR option_name like '\_transient_timeout___transient__transient_pd_collection_products_data_%'
            

            OR option_name like '\_transient_pd%'
            OR option_name like '\_transient__pd%'
        ";

        $wpdb->query($sql);
		
		if(!$is_call_from_method) {
			exit('success');
		}
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

}
