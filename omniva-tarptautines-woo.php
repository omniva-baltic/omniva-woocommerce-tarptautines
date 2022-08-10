<?php
/**
 * Plugin Name: Omniva International Shipping
 * Version: 1.0.9
 * Plugin URI: https://github.com/mijora
 * Description: Official Omniva plugin that combine shipping between different countries
 * Author: Mijora
 * Author URI: https://mijora.lt/
 * Text Domain: omniva_global
 * Domain Path: /languages
 *
 * Requires at least: 5.1
 * Tested up to: 6.0
 * WC requires at least: 4.0
 * WC tested up to: 6.6.0
 * Requires PHP: 7.2
 *
 */

require 'vendor/autoload.php';

use OmnivaTarptautinesWoo\Main;

if (!defined('ABSPATH')) {
  exit;
}

define('OMNIVA_GLOBAL_VERSION', '1.0.9');
define('OMNIVA_GLOBAL_BASENAME', plugin_basename(__FILE__));

register_activation_hook(__FILE__, array( 'OmnivaTarptautinesWoo\Main', 'activated' ) );
register_deactivation_hook( __FILE__, array( 'OmnivaTarptautinesWoo\Main', 'deactivated' ) );

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    new Main();
}
