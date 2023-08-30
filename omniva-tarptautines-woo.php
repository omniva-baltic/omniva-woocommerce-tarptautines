<?php
/**
 * Plugin Name: Omniva International Shipping
 * Version: 1.1.3
 * Plugin URI: https://github.com/omniva-baltic/omniva-woocommerce-tarptautines
 * Description: Official Omniva plugin that combine shipping between different countries
 * Author: Mijora
 * Author URI: https://mijora.lt/
 * Text Domain: omniva_global
 * Domain Path: /languages
 *
 * Requires at least: 5.1
 * Tested up to: 6.3.1
 * WC requires at least: 4.0
 * WC tested up to: 7.9.0
 * Requires PHP: 7.2
 *
 */

require 'vendor/autoload.php';

use OmnivaTarptautinesWoo\Main;

if (!defined('ABSPATH')) {
  exit;
}

define('OMNIVALT_GLOBAL_VERSION', '1.1.3');
define('OMNIVALT_GLOBAL_BASENAME', plugin_basename(__FILE__));
define('OMNIVALT_GLOBAL_PLUGIN_DIR', plugin_dir_path( __FILE__ ));

register_activation_hook(__FILE__, array( 'OmnivaTarptautinesWoo\Main', 'activated' ) );
register_deactivation_hook( __FILE__, array( 'OmnivaTarptautinesWoo\Main', 'deactivated' ) );

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    new Main();
}
