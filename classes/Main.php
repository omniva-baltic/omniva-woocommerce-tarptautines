<?php

namespace OmnivaTarptautinesWoo;

if (!defined('ABSPATH')) {
    exit;
}

use OmnivaTarptautinesWoo\Category;
use OmnivaTarptautinesWoo\Order;
use OmnivaTarptautinesWoo\Manifest;
use OmnivaTarptautinesWoo\Helper;
use OmnivaTarptautinesWoo\Core;
use OmnivaTarptautinesWoo\ShippingMethod;

class Main {

    private $core;
    private $category;
    private $order;
    private $manifest;
    private $api;
    private $config;

    public function __construct($init = true) {
        $this->init();
        $this->core = new Core();
        $this->api = $this->core->get_api();
        $this->category = new Category();
        $this->order = new Order($this->api, $this->core);
        $this->manifest = new Manifest($this->api, $this->core);
    }

    private function init() {
        add_action('woocommerce_shipping_init', array($this, 'shipping_method_init'));
        add_filter('woocommerce_shipping_methods', array($this, 'add_shipping_method'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'front_scripts'), 99);
        add_action('woocommerce_after_shipping_rate', array($this, 'omniva_show_terminals'));
        //add_action('wp_footer', array($this, 'footer_modal'));
        add_action('omniva_global_event', array($this, 'omniva_global_event_callback_function'));
        add_filter('cron_schedules', array($this, 'cron_add_5min'));
        add_action('woocommerce_checkout_process', array($this, 'omniva_terminal_validate'));
        add_action( 'wp_ajax_omniva_terminals_sync', array($this, 'omniva_update_terminals'));

        if (get_option(Helper::get_prefix() . '_services_updated', 0) == 1) {
            add_action('admin_notices', array($this, 'updated_services_notice'));
        }
    }

    public function front_scripts() {
        if (is_checkout() && ! is_wc_endpoint_url()) {

            wp_enqueue_script('omniva-global-helper', plugin_dir_url(__DIR__) . 'assets/js/omniva_helper.js', array('jquery'), OMNIVA_GLOBAL_VERSION);
            wp_enqueue_script('omniva-global', plugin_dir_url(__DIR__) . 'assets/js/omniva.js', array('jquery'), OMNIVA_GLOBAL_VERSION);
            wp_enqueue_script('omniva-terminal', plugin_dir_url(__DIR__) . 'assets/js/terminal.js', array('jquery'), OMNIVA_GLOBAL_VERSION);

            wp_enqueue_style('omniva-global', plugin_dir_url(__DIR__) . 'assets/css/terminal-mapping.css', array(), OMNIVA_GLOBAL_VERSION);

            //wp_enqueue_script('leaflet', plugin_dir_url(__DIR__) . 'assets/js/leaflet.js', array('jquery'), null, true);
            //wp_enqueue_style('leaflet', plugin_dir_url(__DIR__) . 'assets/css/leaflet.css');

            wp_localize_script('omniva-global', 'omnivaglobaldata', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'omniva_plugin_url' => plugin_dir_url(__DIR__),
                'text_select_terminal' => __('Select terminal', 'omniva_global'),
                'text_select_post' => __('Select post office', 'omniva_global'),
                'text_search_placeholder' => __('Enter postcode', 'omniva_global'),
                'text_not_found' => __('Place not found', 'omniva_global'),
                'text_enter_address' => __('Enter postcode/address', 'omniva_global'),
                'text_map' => __('Terminals map', 'omniva_global'),
                'text_list' => __('Terminals list', 'omniva_global'),
                'text_search' => __('Search', 'omniva_global'),
                'text_reset' => __('Reset search', 'omniva_global'),
                'text_select' => __('Select', 'omniva_global'),
                'text_no_city' => __('City not found', 'omniva_global'),
                'text_my_loc' => __('Use my location', 'omniva_global'),
            ));
        }
    }

    public function admin_scripts() {
        wp_register_style('omniva_global_admin_style', plugin_dir_url(__DIR__) . 'assets/css/admin.css', false, OMNIVA_GLOBAL_VERSION);
        wp_enqueue_style('omniva_global_admin_style');

        wp_register_script('omniva_global_settings_js', plugin_dir_url(__DIR__) . 'assets/js/settings.js', array('jquery'), OMNIVA_GLOBAL_VERSION, true);
        wp_enqueue_script('omniva_global_settings_js');
        
        wp_localize_script('omniva_global_settings_js', 'omnivadata', array(
                'ajax_url' => admin_url('admin-ajax.php'),
        ));

        $current_page = get_current_screen()->base;
        if ($current_page == 'post') {
            wp_register_script('omniva_global_order_js', plugin_dir_url(__DIR__) . 'assets/js/order.js', array('jquery', 'select2'), OMNIVA_GLOBAL_VERSION, true);
            wp_enqueue_script('omniva_global_order_js');
        }
    }

    public function add_shipping_method($methods) {
        $methods['omniva_global'] = 'OmnivaTarptautinesWoo\ShippingMethod';
        return $methods;
    }

    public function shipping_method_init() {
        require "ShippingMethod.php";
        new \OmnivaTarptautinesWoo\ShippingMethod();
    }

    public function omniva_show_terminals($method) {
        $customer = WC()->session->get('customer');
        $country = "ALL";
        if (!isset($_POST['country'])) {
            return;
        }
        if (isset($customer['shipping_country'])) {
            $country = $customer['shipping_country'];
        } elseif (isset($customer['country'])) {
            $country = $customer['country'];
        }

        $termnal_id = WC()->session->get('omniva_terminal_id');

        $selected_shipping_method = WC()->session->get('chosen_shipping_methods');
        if (empty($selected_shipping_method)) {
            $selected_shipping_method = array();
        }
        if (!is_array($selected_shipping_method)) {
            $selected_shipping_method = array($selected_shipping_method);
        }

        if (!empty($selected_shipping_method) && stripos($selected_shipping_method[0], 'omniva_global_terminal_') !== false && stripos($method->id, 'omniva_global_terminal_') !== false) {
            $identifier = $this->core->get_identifier_form_method($method->id);
            echo $this->omniva_get_terminal_options($method->id, $termnal_id, $country, $identifier);
        }
    }

    public function omniva_get_terminal_options($method_id, $selected = '', $country = "ALL", $identifier = 'omniva') {
        //$country = "ALL";

        $omniva_settings = $this->core->get_config();
        $set_autoselect = (isset($omniva_settings['auto_select']) && $omniva_settings['auto_select'] == 'yes') ? 'true' : 'false';
        $max_distance = (isset($omniva_settings['terminal_distance'])) ? $omniva_settings['terminal_distance'] : '2';
       
        $script = "<script style='display:none;'>
        var omnivaSettings = {
          auto_select:" . $set_autoselect . ",
          max_distance:" . $max_distance . ",
          identifier: '{$identifier}' ,
          country: '{$country}' ,
          api_url: '".$omniva_settings['api_url']."',    
        };
        var omniva_current_terminal = '" . $selected . "';
        var omnivaint_terminal_reference = '{$method_id}';
        jQuery('document').ready(function($){     
          $('body').trigger('load-omniva-terminals');
        });
        </script>";
        return '<div id="omniva_global_map_container"></div><input type = "hidden" name="omniva_global_terminal"/>'.$script;//<div class="terminal-container"><select class="omniva_global_terminal" name="omniva_global_terminal">' . $parcel_terminals . '</select>      ' . $button . ' </div>' . $script;
    }

    public function updated_services_notice() {
        ?>
        <div class="notice notice-warning">
            <p><?php _e('Omniva services updated! Please check your selection.', 'omniva_global'); ?></p>
            <p><a href = "<?php echo Helper::get_settings_url(); ?>" class = "button-primary"><?php _e('Settings', 'omniva_global'); ?></a></p>
        </div>
        <?php
    }

    public static function activated() {
        wp_schedule_event(time(), '5min', 'omniva_global_event');
        self::create_terminals_table();
    }

    public static function deactivated() {
        wp_clear_scheduled_hook('omniva_global_event');
    }

    public function cron_add_5min($schedules) {
        $schedules['5min'] = array(
            'interval' => 300,
            'display' => __('Every 5 min')
        );
        return $schedules;
    }

    public function omniva_global_event_callback_function() {
        $args = array(
            'post_type' => 'shop_order',
            'numberposts' => -1,
            'post_status' => 'any',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_omniva_global_shipment_id',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key' => '_omniva_global_tracking_numbers',
                    'compare' => 'NOT EXISTS',
                ),
            )
        );
        $orders = get_posts($args);
        foreach ($orders as $order) {
            $shipment_id = get_post_meta($order->ID, '_omniva_global_shipment_id', true);
            if ($shipment_id) {
                try {
                    $response = $this->api->get_label($shipment_id);
                    update_post_meta($order->ID, '_omniva_global_tracking_numbers', $response->tracking_numbers);
                } catch (\Exception $e) {
                    
                }
            }
        }
    }
    
    public static function create_terminals_table() {      
        global $wpdb; 
        $db_table_name = $wpdb->prefix . 'omniva_global_terminals';
        $charset_collate = $wpdb->get_charset_collate();

        if($wpdb->get_var( "show tables like '$db_table_name'" ) != $db_table_name ) {
              $sql = "CREATE TABLE $db_table_name (
                       id int(11) NOT NULL auto_increment,
                       name varchar(255) NOT NULL,
                       city varchar(255) NOT NULL,
                       country_code varchar(10) NOT NULL,
                       address varchar(255) NOT NULL,
                       zip varchar(50) NOT NULL,
                       x_cord varchar(20) NOT NULL,
                       y_cord varchar(20) NOT NULL,
                       comment varchar(255) NOT NULL,
                       identifier varchar(50) NOT NULL,
                       UNIQUE KEY id (id)
               ) $charset_collate;";

          require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
          dbDelta( $sql );
          add_option( $db_table_name, OMNIVA_GLOBAL_VERSION );
        }
    } 


    public function omniva_terminal_validate() {
        if (isset($_POST['shipping_method'])) {
            foreach ($_POST['shipping_method'] as $ship_method) {
                if (stripos($ship_method, Helper::get_prefix() . '_terminal') !== false && empty($_POST['omniva_global_terminal'])) {
                    wc_add_notice(__('Please select parcel terminal.', 'omniva_global'), 'error');
                }
            }
        }
    }
    
 
    public function omniva_update_terminals() {
        $this->api->update_terminals();
        $array_result = array(
            'message' => 'Updated'
        );

        wp_send_json($array_result);
        wp_die();
    }

}
