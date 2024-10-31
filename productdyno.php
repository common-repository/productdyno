<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              productdyno.com
 * @since             1.0.0
 * @package           Productdyno
 *
 * @wordpress-plugin
 * Plugin Name:       ProductDyno
 * Description:       Discover the easiest way to Sell, License & Securely Deliver any type of Digital Product!
 * Version:           1.0.24
 * Stable tag:        1.0.24
 * Tested up to:      6.6.1
 * Author:            ProductDyno
 * Author URI:        productdyno.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       productdyno
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
$plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), false);
$plugin_version = $plugin_data['Version'];

define( 'PRODUCTDYNO_PLUGIN_VERSION', $plugin_version );

// ProductDyno Plugin's name
if (!defined('PRODUCTDYNO_PLUGIN_NAME')) {
    define('PRODUCTDYNO_PLUGIN_NAME', trim(dirname(plugin_basename(__FILE__)), '/'));
}

// ProductDyno Plugin's directory path
if (!defined('PRODUCTDYNO_PLUGIN_DIR')) {
    define('PRODUCTDYNO_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . PRODUCTDYNO_PLUGIN_NAME);
}

// ProductDyno Plugin's URL
if (!defined('PRODUCTDYNO_PLUGIN_URL')) {
    define('PRODUCTDYNO_PLUGIN_URL', WP_PLUGIN_URL . '/' . PRODUCTDYNO_PLUGIN_NAME);
}

// ProductDyno API URL
if (!defined('PRODUCTDYNO_API_URL')) {
    if (is_ssl()) {
    	define('PRODUCTDYNO_API_URL', 'https://app.productdyno.com/api/v1/');
  	} else {
    	define('PRODUCTDYNO_API_URL', 'http://app.productdyno.com/api/v1/');
  	}
} 	

// ProductDyno App URL
if (!defined('PRODUCTDYNO_APP_URL')) {
    if (is_ssl()) {
    	define('PRODUCTDYNO_APP_URL', 'https://app.productdyno.com/');
    } else {
    	define('PRODUCTDYNO_APP_URL', 'http://app.productdyno.com/');
    }
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-productdyno-activator.php
 */
function activate_productdyno() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-productdyno-activator.php';
	Productdyno_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-productdyno-deactivator.php
 */
function deactivate_productdyno() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-productdyno-deactivator.php';
	Productdyno_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_productdyno' );
register_deactivation_hook( __FILE__, 'deactivate_productdyno' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-productdyno.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_productdyno() {

	$plugin = new Productdyno();
	$plugin->run();

}
run_productdyno();
