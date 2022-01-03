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
        add_action('wp_footer', array($this, 'footer_modal'));
        add_action('omniva_global_event', array($this, 'omniva_global_event_callback_function'));
        add_filter('cron_schedules', array($this, 'cron_add_5min'));
        add_action('woocommerce_checkout_process', array($this, 'omniva_terminal_validate'));

        if (get_option(Helper::get_prefix() . '_services_updated', 0) == 1) {
            add_action('admin_notices', array($this, 'updated_services_notice'));
        }
    }

    public function front_scripts() {
        if (is_cart() || is_checkout()) {

            wp_enqueue_script('omniva-helper', plugin_dir_url(__DIR__) . 'assets/js/omniva_helper.js', array('jquery'), OMNIVA_GLOBAL_VERSION);
            wp_enqueue_script('omniva', plugin_dir_url(__DIR__) . 'assets/js/omniva.js', array('jquery'), OMNIVA_GLOBAL_VERSION);

            wp_enqueue_style('omniva', plugin_dir_url(__DIR__) . 'assets/css/omniva.css', array(), OMNIVA_GLOBAL_VERSION);

            wp_enqueue_script('leaflet', plugin_dir_url(__DIR__) . 'assets/js/leaflet.js', array('jquery'), null, true);
            wp_enqueue_style('leaflet', plugin_dir_url(__DIR__) . 'assets/css/leaflet.css');

            wp_localize_script('omniva', 'omnivadata', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'omniva_plugin_url' => plugin_dir_url(__DIR__),
                'text_select_terminal' => __('Select terminal', 'omniva_global'),
                'text_select_post' => __('Select post office', 'omniva_global'),
                'text_search_placeholder' => __('Enter postcode', 'omniva_global'),
                'not_found' => __('Place not found', 'omniva_global'),
                'text_enter_address' => __('Enter postcode/address', 'omniva_global'),
                'text_show_in_map' => __('Show in map', 'omniva_global'),
                'text_show_more' => __('Show more', 'omniva_global'),
                'text_modal_title_terminal' => __('Omniva parcel terminals', 'omniva_global'),
                'text_modal_search_title_terminal' => __('Parcel terminals addresses', 'omniva_global'),
                'text_modal_title_post' => __('Omniva post offices', 'omniva_global'),
                'text_modal_search_title_post' => __('Post offices addresses', 'omniva_global'),
            ));
        }
    }

    public function admin_scripts() {
        wp_register_style('omniva_global_admin_style', plugin_dir_url(__DIR__) . 'assets/css/admin.css', false, OMNIVA_GLOBAL_VERSION);
        wp_enqueue_style('omniva_global_admin_style');

        wp_register_script('omniva_global_settings_js', plugin_dir_url(__DIR__) . 'assets/js/settings.js', array('jquery'), OMNIVA_GLOBAL_VERSION, true);
        wp_enqueue_script('omniva_global_settings_js');

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
            echo $this->omniva_get_terminal_options($termnal_id, $country, $identifier);
        }
    }

    public function omniva_get_terminal_options($selected = '', $country = "ALL", $identifier = 'omniva') {
        //$country = "ALL";

        $terminals = $this->api->get_terminals();
        $terminals = $terminals->parcel_machines ?? [];
        $parcel_terminals = '';
        if (is_array($terminals)) {
            $grouped_options = array();
            foreach ($terminals as $terminal) {
                /*
                  if (($get_list === 'terminal' && intval($terminal['TYPE']) === 1) || ($get_list === 'post' && intval($terminal['TYPE']) === 0)) {
                  continue;
                  } */
                if ($terminal->country_code != $country && $country != "ALL") {
                    continue;
                }
                if ($terminal->identifier != $identifier) {
                    continue;
                }
                if (!isset($grouped_options[$terminal->city])) {
                    $grouped_options[(string) $terminal->city] = array();
                }
                $grouped_options[(string) $terminal->city][(string) $terminal->id] = $terminal->name;
            }
            $counter = 0;
            foreach ($grouped_options as $city => $locs) {
                $parcel_terminals .= '<optgroup data-id = "' . $counter . '" label = "' . $city . '">';
                foreach ($locs as $key => $loc) {
                    $parcel_terminals .= '<option value = "' . $key . '" ' . ($key == $selected ? 'selected' : '') . '>' . $loc . '</option>';
                }

                $parcel_terminals .= '</optgroup>';
                $counter++;
            }
        }
        $nonce = wp_create_nonce("omniva_terminals_json_nonce");
        $omniva_settings = $this->core->get_config();
        $parcel_terminals = '<option value = "">' . __('Select parcel terminal', 'omniva_global') . '</option>' . $parcel_terminals;
        $set_autoselect = (isset($omniva_settings['auto_select']) && $omniva_settings['auto_select'] == 'yes') ? 'true' : 'false';
        $max_distance = (isset($omniva_settings['terminal_distance'])) ? $omniva_settings['terminal_distance'] : '2';
        $script = "<script style='display:none;'>
      var omnivaTerminals = JSON.stringify(" . json_encode($this->get_terminal_for_map('', $country, $identifier)) . ");
    </script>";
        $script .= "<script style='display:none;'>
      var omniva_current_country = '" . $country . "';
      var omnivaSettings = {
        auto_select:" . $set_autoselect . ",
        max_distance:" . $max_distance . "
      };
      var omniva_type = 'terminal';
      var omniva_current_terminal = '" . $selected . "';
      jQuery('document').ready(function($){        
        $('.omniva_global_terminal').omniva_global();
        $(document).trigger('omnivalt.checkpostcode');
      });
      </script>";
        $button = '';
        if (!isset($omniva_settings['show_map']) || isset($omniva_settings['show_map']) && $omniva_settings['show_map'] == "yes") {
            $title = __("Show parcel terminals map", "omniva_global");
            $button = '<button type="button" id="show-omniva-map" class="btn btn-basic btn-sm omniva-btn" style = "display: none;">' . __('Show in map', 'omniva_global') . '<img src = "' . plugin_dir_url(__DIR__) . 'assets/images/sasi.png" title = "' . $title . '"/></button>';
        }
        return '<div class="terminal-container"><select class="omniva_global_terminal" name="omniva_global_terminal">' . $parcel_terminals . '</select>
      ' . $button . ' </div>' . $script;
    }

    public function get_terminal_for_map($selected = '', $country = "ALL", $identifier = 'omniva') {
        $terminals = $this->api->get_terminals();
        $terminals = $terminals->parcel_machines ?? [];
        $terminalsList = array();
        //$comment_lang = (!empty($shipping_params[strtoupper($country)]['comment_lang'])) ? $shipping_params[strtoupper($country)]['comment_lang'] : 'lit';
        if (is_array($terminals)) {
            foreach ($terminals as $terminal) {
                if ($terminal->country_code != $country && $country != "ALL") { //TO DO: filter by type
                    continue;
                }
                if ($terminal->identifier != $identifier) {
                    continue;
                }
                $terminalsList[] = [$terminal->name, $terminal->y_cord, $terminal->x_cord, $terminal->id, $terminal->address, '', ''];
            }
        }
        return $terminalsList;
    }

    public function footer_modal() {
        if (is_cart() || is_checkout()) {
            echo $this->terminals_modal();
        }
    }

    private function terminals_modal() {
        return '
        <div id="omnivaLtModal" class="modal">
            <div class="omniva-modal-content">
                <div class="omniva-modal-header">
                <span class="close" id="terminalsModal">&times;</span>
                <h5 id="omnivaLt_modal_title" style="display: inline">' . __('Omniva parcel terminals', 'omniva_global') . '</h5>
                </div>
                <div class="omniva-modal-body" style="/*overflow: hidden;*/">
                    <div id = "omnivaMapContainer"></div>
                    <div class="omniva-search-bar" >
                        <h4 id="omnivaLt_modal_search" style="margin-top: 0px;">' . __('Parcel terminals addresses', 'omniva_global') . '</h4>
                        <div id="omniva-search">
                        <form>
                        <input type = "text" placeholder = "' . __('Enter postcode', 'omniva_global') . '"/>
                        <button type = "submit" id="map-search-button"></button>
                        </form>                    
                        <div class="omniva-autocomplete scrollbar" style = "display:none;"><ul></ul></div>
                        </div>
                        <div class = "omniva-back-to-list" style = "display:none;">' . __('Back', 'omniva_global') . '</div>
                        <div class="found_terminals scrollbar" id="style-8">
                          <ul>

                          </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
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

    public function omniva_terminal_validate() {
        if (isset($_POST['shipping_method'])) {
            foreach ($_POST['shipping_method'] as $ship_method) {
                if (stripos($ship_method, Helper::get_prefix() . '_terminal') !== false && empty($_POST['omniva_global_terminal'])) {
                    wc_add_notice(__('Please select parcel terminal.', 'omniva_global'), 'error');
                }
            }
        }
    }

}
