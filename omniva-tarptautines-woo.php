<?php
/**
 * Plugin Name: Omniva Tarptautines Woocommerce
 * Version: 1.0.1
 * Plugin URI: https://github.com/mijora
 * Description: Omniva shipping module
 * Author: Mijora
 * Author URI: https://mijora.lt/
 * Text Domain: omniva_global
 * Domain Path: /languages
 *
 * Tested up to: 5.8.2
 * WC requires at least: 3.4
 * WC tested up to: 5.9.0
 *
 */

require 'vendor/autoload.php';

use OmnivaTarptautinesWoo\Main;

if (!defined('ABSPATH')) {
  exit;
}

define('OMNIVA_GLOBAL_VERSION', '1.0.1');

register_activation_hook(__FILE__, array( 'OmnivaTarptautinesWoo\Main', 'activated' ) );
register_deactivation_hook( __FILE__, array( 'OmnivaTarptautinesWoo\Main', 'deactivated' ) );

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    new Main();
}
