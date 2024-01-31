<?php

namespace OmnivaTarptautinesWoo;

if (!defined('ABSPATH')) {
    exit;
}

use OmnivaTarptautinesWoo\Core;
use OmnivaTarptautinesWoo\Helper;
use WC_Shipping_Method;

if (!class_exists('\OmnivaTarptautinesWoo\ShippingMethod')) {

    class ShippingMethod extends WC_Shipping_Method
    {

        private $core;
        private $api;

        /**
         * Constructor for your shipping class
         *
         * @access public
         * @return void
         */
        public function __construct()
        {
            $this->core = new Core;
            $this->api = $this->core->get_api();
            $this->id = Helper::get_prefix();
            $this->method_title = __('Omniva international', 'omniva_global');
            $this->method_description = __('Omniva international shipping method', 'omniva_global');
            $this->supports = array(
                'settings'
            );
            $this->init();
        }

        /**
         * Init your settings
         *
         * @access public
         * @return void
         */
        private function init()
        {
            // Load the settings API
            $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
            $this->init_settings(); // This is part of the settings API. Loads settings you previously init.
            // Save settings in admin if you have any defined
            add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        }

        private function get_countries_options()
        {
            $options = [];
            $countries = $this->api->get_countries();
            foreach ($countries as $country) {
                $options[$country->code] = $country->name;
            }
            return $options;
        }

        public function init_form_fields()
        {
            $countries_options = $this->get_countries_options();
            $currency = get_woocommerce_currency();
            $currency_symbol = get_woocommerce_currency_symbol();
            $fields = array(
                'main_logo' => array(
                    'type' => 'logo'
                ),
                'enabled' => array(
                    'title' => __('Enable', 'omniva_global'),
                    'type' => 'checkbox',
                    'description' => __('Enable this shipping.', 'omniva_global'),
                    'default' => 'yes'
                ),
                'hr_api' => array(
                    'type' => 'hr'
                ),
                'api_url' => array(
                    'title' => __('Api URL', 'omniva_global'),
                    'type' => 'text',
                    'default' => 'https://tarptautines.omniva.lt',
                    'description' => __('Change only if want use custom Api URL.', 'omniva_global') . ' ' . sprintf(__('Default is %s', 'omniva_global'), '<code>https://tarptautines.omniva.lt</code>'),
                ),
                'api_token' => array(
                    'title' => __('Api token', 'omniva_global'),
                    'type' => 'text',
                ),
                /*
                'own_login' => array(
                    'title' => __('Own login', 'omniva_global'),
                    'type' => 'checkbox',
                    'description' => __('Check if you have own login', 'omniva_global'),
                    'default' => 'no',
                    'class' => 'has-depends'
                ),
                'own_login_user' => array(
                    'title' => __('Own login user', 'omniva_global'),
                    'type' => 'text',
                    'custom_attributes' => array(
                        'data-depends' => 'woocommerce_' . Helper::get_prefix() . '_own_login'
                    ),
                ),
                'own_login_password' => array(
                    'title' => __('Own login password', 'omniva_global'),
                    'type' => 'text',
                    'custom_attributes' => array(
                        'data-depends' => 'woocommerce_' . Helper::get_prefix() . '_own_login'
                    ),
                ),*/
                'hr_shop' => array(
                    'type' => 'hr'
                ),
                'company' => array(
                    'title' => __('Company name', 'omniva_global'),
                    'type' => 'text',
                ),
                'bank_account' => array(
                    'title' => __('Bank account', 'omniva_global'),
                    'type' => 'text',
                ),
                'shop_name' => array(
                    'title' => __('Shop name', 'omniva_global'),
                    'type' => 'text',
                ),
                'shop_city' => array(
                    'title' => __('Shop city', 'omniva_global'),
                    'type' => 'text',
                ),
                'shop_address' => array(
                    'title' => __('Shop address', 'omniva_global'),
                    'type' => 'text',
                ),
                'shop_postcode' => array(
                    'title' => __('Shop postcode', 'omniva_global'),
                    'type' => 'text',
                    'description' => sprintf(__('Example for Latvia: %1$s. Example for other countries: %2$s.', 'omniva_global'), '<code>LV-0123</code>', '<code>01234</code>'),
                ),
                'shop_countrycode' => array(
                    'title' => __('Shop country code', 'omniva_global'),
                    'type' => 'select',
                    'class' => 'checkout-style pickup-point',
                    'options' => $countries_options,
                    'default' => 'LT',
                ),
                'shop_phone' => array(
                    'title' => __('Shop phone number', 'omniva_global'),
                    'type' => 'text',
                ),
            );
            /*
            $fields['hr_methods'] = array(
                'type' => 'hr'
            );
            $fields['courier_enable'] = array(
                'title' => __('Enable courier', 'omniva_global'),
                'type' => 'checkbox',
                'default' => 'no',
                'class' => 'has-depends'
            );
            $fields['courier_title'] = array(
                'title' => __('Courier title', 'omniva_global'),
                'type' => 'text',
                'default' => 'Omniva courier',
                'custom_attributes' => array(
                    'data-depends' => 'woocommerce_' . Helper::get_prefix() . '_courier_enable'
                ),
            );
            $fields['terminal_enable'] = array(
                'title' => __('Enable terminal', 'omniva_global'),
                'type' => 'checkbox',
                'default' => 'no',
                'class' => 'has-depends'
            );
            $fields['terminal_title'] = array(
                'title' => __('Terminal title', 'omniva_global'),
                'type' => 'text',
                'default' => 'Omniva terminal',
                'custom_attributes' => array(
                    'data-depends' => 'woocommerce_' . Helper::get_prefix() . '_terminal_enable'
                ),
            );
            */
            
            $fields['hr_after_shop'] = array(
                'type' => 'hr'
            );

            $fields['show_type'] = array(
                'title' => __('Show of service from group', 'omniva_global'),
                'type' => 'select',
                'description' => __('Select which service of the group show in checkout. The selected value will apply to all groups.', 'omniva_global'),
                'options' => array(
                    'default' => __('Set for each group separately (Select in group settings)', 'omniva_global'),
                    'cheapest' => __('Cheapest', 'omniva_global'),
                    'expensive' => __('Most expensive', 'omniva_global'),
                    'fastest' => __('Fastest', 'omniva_global'),
                    'slowest' => __('Slowest', 'omniva_global'),
                )
            );

            /*$fields['services_limit'] = array(
                'title' => __('Services limit', 'omniva_global'),
                'type' => 'number',
                'description' => __('Select how many services will be visible in checkout. `-1` - for unlimited', 'omniva_global'),
                'default' => '-1'
            );*/
            
            $fields['send_as_one'] = array(
                'title' => __('Consolidare', 'omniva_global'),
                'description' => __('Send as one package', 'omniva_global'),
                'type' => 'checkbox',
                'default' => 'no',
            );

            $fields['add_delivery_time'] = array(
                'title' => __('Add to delivery time', 'omniva_global'),
                'description' => __('Add additional time in days to the delivery time shown to the customer', 'omniva_global'),
                'type' => 'number',
                'default' => 0,
                'custom_attributes' => array(
                    'step' => 1,
                    'min' => 0
                )
            );
            
            $services = $this->core->get_services(false);
            if (is_array($services)) {
                $service_groups = [];
                foreach ($services as $service) {
                    if ($service->direction != 'Export') {
                        continue;
                    }
                    $group_name = strtolower($service->service_type);
                    if ($service->delivery_to_address == false) {
                        $group_name = 'terminals';
                    }
                    if (!isset($service_groups[$group_name])) {
                        $service_groups[$group_name] = [];
                    }
                    $service_groups[$group_name][] = $service;
                }
                foreach ($service_groups as $group_name => $group_services) {
                    $fields['hr_service_groups_' . $group_name] = array(
                        'type' => 'hr'
                    );
                    $fields[$group_name . '_enable'] = array(
                        'type' => 'fieldset_start',
                        'label' => $group_name
                    );

                    $fields[$group_name . '_title'] = array(
                        'title' => __('Title', 'omniva_global'),
                        'type' => 'text',
                        'default' => ucfirst($group_name),
                    );

                    $fields[$group_name . '_show_type'] = array(
                        'title' => __('Service showing', 'omniva_global'),
                        'type' => 'select',
                        'description' => __('Select which service of this group show in the checkout', 'omniva_global'),
                        'options' => array(
                            //'default' => __('Default', 'omniva_global'),
                            'cheapest' => __('Cheapest', 'omniva_global'),
                            'expensive' => __('Most expensive', 'omniva_global'),
                            'fastest' => __('Fastest', 'omniva_global'),
                            'slowest' => __('Slowest', 'omniva_global'),
                        )
                    );
            
                    /*$fields[$group_name . '_sort_by'] = array(
                        'title' => __('Services order', 'omniva_global'),
                        'type' => 'select',
                        'description' => __('Select how services will be sorted in the checkout', 'omniva_global'),
                        'options' => array(
                            'default' => __('Default', 'omniva_global'),
                            'cheapest' => __('Cheapest first', 'omniva_global'),
                            'fastest' => __('Fastest first', 'omniva_global'),
                        )
                    );

                    $fields[$group_name . '_show_type'] = array(
                        'title' => __('Services showing', 'omniva_global'),
                        'type' => 'select',
                        'description' => __('Select how many this group services will be showing in the checkout', 'omniva_global'),
                        'options' => array(
                            //'all' => __('All list', 'omniva_global'),
                            'first' => __('First in the list', 'omniva_global'),
                            'last' => __('Last in the list', 'omniva_global'),
                        )
                    );*/

                    $fields[$group_name . '_price_type'] = array(
                        'title' => __('Price type', 'omniva_global'),
                        'type' => 'select',
                        'description' => sprintf(__('Select price type for services. Enter the value in the "%s" field below.', 'omniva_global'), __('Value', 'omniva_global')),
                        'options' => array(
                            'fixed' => __('Fixed price', 'omniva_global'),
                            'addition_percent' => __('Service price with added percentage', 'omniva_global') . ' (%)',
                            'addition_eur' => __('Service price with added fixed value', 'omniva_global') . ' (' . $currency . ')',
                        ),
                    );
                    $this->add_toggle($fields[$group_name . '_price_type'], array(
                        'group' => $group_name,
                        'field' => 'price_type',
                        'show' => 'additional',
                    ), true); //Toggle display by this field

                    $fields[$group_name . '_price_value'] = array(
                        'title' => __('Value', 'omniva_global'),
                        'type' => 'number',
                        'custom_attributes' => array(
                            'step' => 0.01,
                            'min' => 0
                        ),
                        'default' => 5
                    );

                    $fields[$group_name . '_service_price'] = array(
                        'title' => __('Use service price', 'omniva_global'),
                        'type' => 'select',
                        'description' => __('Which service price to use', 'omniva_global'),
                        'options' => array(
                            'total_incl_vat' => __('With included tax', 'omniva_global'),
                            'total_excl_vat' => __('Without tax', 'omniva_global'),
                        ),
                        'default' => 'total_excl_vat',
                    );
                    $this->add_toggle($fields[$group_name . '_service_price'], array(
                        'group' => $group_name,
                        'field' => 'price_type',
                        'show' => 'additional',
                    ));

                    $fields[$group_name . '_free_shipping'] = array(
                        'title' => __('Free shipping cart amount', 'omniva_global'),
                        'type' => 'number',
                        'description' => __('Enter 0 to disable', 'omniva_global'),
                        'custom_attributes' => array(
                            'step' => 0.01,
                            'min' => 0
                        ),
                        'default' => 0
                    );
                    
                    $fields[$group_name . '_services_start'] = array(
                        'type' => 'services_list_start',
                        'title' => __('Couriers', 'omniva_global'),
                    );
                    
                    foreach ($group_services as $service) {
                        $fields[$group_name . '_service_' . $service->service_code] = array(
                            'type' => 'service_item',
                            'service' => $service
                        );
                        /*    
                        $fields['service_' . $service->service_code] = array(
                            'title' => $service->name,
                            'type' => 'checkbox',
                            'description' => __(sprintf('Show %s service', $service->name), 'omniva_global'),
                            'class' => 'has-depends'
                        );
                        if ($this->core->has_own_login($service)) {
                            $fields['service_' . $service->service_code . '_own_login_user'] = array(
                                'title' => __('Own login user', 'omniva_global'),
                                'type' => 'text',
                                'custom_attributes' => array(
                                    'data-depends' => 'woocommerce_' . Helper::get_prefix() . '_service_' . $service->service_code
                                ),
                            );
                            $fields['service_' . $service->service_code . '_own_login_password'] = array(
                                'title' => __('Own login password', 'omniva_global'),
                                'type' => 'text',
                                'custom_attributes' => array(
                                    'data-depends' => 'woocommerce_' . Helper::get_prefix() . '_service_' . $service->service_code
                                ),
                            );
                        }
                        */
                    }
                    
                    $fields[$group_name . '_services_end'] = array(
                        'type' => 'services_list_end',
                    );
                    
                }
            }
            $fields['hr_measurements'] = array(
                'type' => 'hr'
            );
            $fields['product_weight'] = array(
                'title' => __('Default product weight (kg)', 'omniva_global'),
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => 0.001,
                    'min' => 0
                ),
                'default' => 1,
            );
            $fields['product_width'] = array(
                'title' => __('Default product width (cm)', 'omniva_global'),
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => 0.01,
                    'min' => 0
                ),
                'default' => 20,
            );
            $fields['product_height'] = array(
                'title' => __('Default product height (cm)', 'omniva_global'),
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => 0.01,
                    'min' => 0
                ),
                'default' => 20,
            );
            $fields['product_length'] = array(
                'title' => __('Default product length (cm)', 'omniva_global'),
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => 0.01,
                    'min' => 0
                ),
                'default' => 20,
            );

            $fields['hr_settings'] = array(
                'type' => 'hr'
            );
            $fields['weight'] = array(
                'title' => sprintf(__('Max cart weight (%s) for terminal', 'omniva_global'), 'kg'),
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => 0.001,
                    'min' => 0
                ),
                'description' => __('Maximum allowed all cart products weight for parcel terminals.', 'omniva_global'),
                'default' => 30,
                'class' => 'omniva_terminal'
            );
            $fields['weight_c'] = array(
                'title' => sprintf(__('Max cart weight (%s) for courier', 'omniva_global'), 'kg'),
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => 0.001,
                    'min' => 0
                ),
                'description' => __('Maximum allowed all cart products weight for courier.', 'omniva_global'),
                'default' => 100,
                'class' => 'omniva_courier'
            );
            /* $fields['size_c'] = array(
              'title' => sprintf(__('Max size (%s) for courier', 'omniva_global'),get_option('woocommerce_dimension_unit')),
              'type' => 'dimensions',
              'description' => __('Maximum product size for courier. Leave all empty to disable.', 'omniva_global') . '<br/>' . __('If the length, width or height of at least one product exceeds the specified values, then it will not be possible to select the courier delivery method for the whole cart.', 'omniva_global')
              ); */
            $fields['restricted_categories'] = array(
                'title' => __('Disable for specific categories', 'omniva_global'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'description' => __('Select categories you want to disable the Omniva method', 'omniva_global'),
                'options' => Helper::omniva_get_categories(),
                'desc_tip' => true,
                'required' => false,
                'custom_attributes' => array(
                    'data-placeholder' => __('Select Categories', 'omniva_global'),
                    'data-name' => 'restricted_categories'
                ),
            );
            /*
            $fields['show_map'] = array(
                'title' => __('Map', 'omniva_global'),
                'type' => 'checkbox',
                'description' => __('Show map of terminals.', 'omniva_global'),
                'default' => 'yes',
                'class' => 'omniva_terminal'
            );
            
            $fields['auto_select'] = array(
                'title' => __('Automatic terminal selection', 'omniva_global'),
                'type' => 'checkbox',
                'description' => __('Automatically select terminal by postcode.', 'omniva_global'),
                'default' => 'yes',
                'class' => 'omniva_terminal'
            );*/
            $fields['terminal_distance'] = array(
                'title' => __('Max terminal distance from receiver, km', 'omniva_global'),
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => 1,
                    'min' => 0
                ),
                'default' => 2
            );

            $fields['hr_advanced'] = array(
                'type' => 'hr'
            );

            $fields['free_items_price'] = array(
                'title' => __('Free items price', 'omniva_global'),
                'type' => 'number',
                'description' => sprintf(__('Some carriers do not allow the shipment to be registered if it contains items with a price of %1$s. In this setting, you can specify what price will be used when registering the shipment when the price of the item in the order is %1$s.', 'omniva_global'), '0 ' . $currency_symbol),
                'custom_attributes' => array(
                    'step' => 0.01,
                    'min' => 0.01
                ),
                'default' => ''
            );

            $fields['refresh_terminals'] = array(
                'title' => __('Update terminals database', 'omniva_global'),
                'type' => 'sync_button',
            );

            $fields['refresh_services'] = array(
                'title' => __('Update services', 'omniva_global'),
                'type' => 'services_sync_button',
            );

            $this->form_fields = $fields;
        }

        public function add_toggle(&$field_data, $params, $this_control = false)
        {
            $group_name = (isset($params['group'])) ? esc_attr($params['group']) : 'unknown';
            $field = (isset($params['field'])) ? esc_attr($params['field']) : 'unknown';
            $show = (isset($params['show'])) ? esc_attr($params['show']) : '';
            
            $field_data['class'] = (!empty($field_data['class'])) ? $field_data['class'] . ' ' : '';

            if ($this_control) {
                $field_data['class'] .= 'omniva-toggle_controller';
                $field_data['custom_attributes']['data-group'] = $group_name;
                $field_data['custom_attributes']['data-field'] = $field;
                $field_data['custom_attributes']['data-show'] = $show;
            } else {
                $field_data['class'] .= 'omniva-toggle_field omniva-toggle-' . $group_name . '-' . $field . ' omniva-toggle_show-' . $show;
            }
        }

        public function generate_hr_html($key, $value)
        {
            $class = (isset($value['class'])) ? $value['class'] : '';
            $html = '<tr valign="top"><td colspan="2"><hr class="' . $class . '"></td></tr>';
            return $html;
        }

        public function generate_sync_button_html($key, $value)
        {
            $class = (isset($value['class'])) ? $value['class'] : '';
            $html = '<tr valign="top"><th>' . ($value['title'] ?? '') . '</th><td colspan=""><button type = "button" class = "button-primary terminals-sync-btn">' . __('Update', 'omniva_global') . '</button></td></tr>';
            return $html;
        }

        public function generate_services_sync_button_html($key, $value)
        {
            $class = (isset($value['class'])) ? $value['class'] : '';
            $html = '<tr valign="top"><th>' . ($value['title'] ?? '') . '</th><td colspan=""><button type = "button" class = "button-primary services-sync-btn">' . __('Update', 'omniva_global') . '</button></td></tr>';
            return $html;
        }

        public function generate_fieldset_start_html($key, $value)
        {
            $service_key = $this->get_field_key( $key );
            $title = ($value['label'] ?? "");
            $html = '<tr valign="top"><th class="service-group-title">' . ucfirst($title) . '</th><td>';
            $html .= '<label for="' . $service_key . '"><input type="checkbox" name="' . $service_key . '" id="' . $service_key . '" style="" value="yes" ' . ( $this->get_option( $key ) == 'yes' ? 'checked' : '' ) . '>' . __('Enable', 'omniva_global') . '</label>';
            $html .= '</td></tr>';
            return $html;
        }

        public function generate_empty_html($key, $value)
        {
            $class = (isset($value['class'])) ? $value['class'] : '';
            $html = '<tr valign="top"><td colspan="2" class="' . $class . '"></td></tr>';
            return $html;
        }

        public function generate_services_list_start_html($key, $value)
        {
            $title = $value['title'] ?? '';
            $html = '<tr valign="top"><th class="titledesc">' . $title . '</th><td class = "services-container">';
            $html .= '<ul>';
            return $html;
        }

        public function generate_services_list_end_html($key, $value)
        {
            $html = '</ul></td></tr>';
            return $html;
        }

        public function generate_service_item_html($key, $value)
        {
            $service = (isset($value['service'])) ? $value['service'] : [];
            $service_key = $this->get_field_key( $key );
            $html = '<li>';
            $html .= '<label for="' . $service_key . '"><input type="checkbox" name="' . $service_key . '" id="' . $service_key . '" style="" value="yes" ' . ( $this->get_option( $key ) == 'yes' ? 'checked' : '' ) . '>' . $service->name . '</label>';
            $html .= '<div><small>' . __(sprintf('Show %s service', $service->name), 'omniva_global') . '</small></div>';
            $html .= '</li>';
            return $html;
        }

        public function generate_logo_html($key, $value)
        {
            $html = '<tr><th class="titledesc"><img src = "'.plugin_dir_url(__DIR__).'assets/images/logo.svg" style="width: 200px;"/></th></tr>';
            return $html;
        }

        private function has_restricted_cat()
        {
            global $woocommerce;
            $config = $this->core->get_config();
            $cart_categories_ids = array();
            foreach ($woocommerce->cart->get_cart() as $cart_item) {
                $cats = get_the_terms($cart_item['product_id'], 'product_cat');
                foreach ($cats as $cat) {
                    $cart_categories_ids[] = $cat->term_id;
                    if ($cat->parent != 0) {
                        $cart_categories_ids[] = $cat->parent;
                    }
                }
            }

            $cart_categories_ids = array_unique($cart_categories_ids);

            $restricted_categories = $config['restricted_categories'];
            if (!is_array($restricted_categories)) {
                $restricted_categories = array($restricted_categories);
            }

            foreach ($cart_categories_ids as $cart_product_categories_id) {
                if (in_array($cart_product_categories_id, $restricted_categories)) {
                    return true;
                }
            }
            return false;
        }

        public function calculate_shipping($package = array())
        {
            try {
                if ($this->has_restricted_cat()) {
                    return;
                }
                $cart_weight = WC()->cart->cart_contents_weight;
                $config = $this->core->get_config();
                
                $offers = $this->core->filter_enabled_offers($this->core->get_offers($package));
                $this->core->set_offers_price($offers);
                $this->core->sort_offers($offers);
                $this->core->show_offers($offers);

                $current_service = 0;
                //$services_limit = $config['services_limit'] ?? -1;
                //if (isset($config['courier_enable']) && $config['courier_enable'] == 'yes' && (!$config['weight_c'] || $config['weight_c'] > $cart_weight)) {
                    foreach ($offers as $offer) {
                        if ($this->core->is_offer_terminal($offer)) {
                            continue;
                        }
                        $group = $offer->group;
                        $courier_title = $config[$group .'_title'] ?? 'Courier';
                        $delivery_time = $this->get_delivery_time($offer->delivery_time);
                        if (!empty($delivery_time)) {
                            $delivery_time = ' (' . $delivery_time . ')';
                        }
                        $free_shipping = $this->core->is_free_shipping($offer->group);
                        $rate = array(
                            'id' => $this->id . '_service_' . $offer->service_code,
                            'label' => $courier_title . $delivery_time,
                            'cost' => $free_shipping ? 0 : $offer->price
                        );
                        $this->add_rate($rate);
                        $current_service++;
                        
                        /*if ($services_limit != '' && $services_limit > 0 && $services_limit <= $current_service) {
                            break;
                        }*/
                        
                    }
                //}

                //if (isset($config['terminal_enable']) && $config['terminal_enable'] == 'yes' && (!$config['weight'] || $config['weight'] > $cart_weight)) {
                    foreach ($offers as $offer) {
                        if (!$this->core->is_offer_terminal($offer)) {
                            continue;
                        }
                        $terminal_title = $config['terminals_title'] ?? 'Parcel terminal';
                        $delivery_time = $this->get_delivery_time($offer->delivery_time);
                        if (!empty($delivery_time)) {
                            $delivery_time = ' (' . $delivery_time . ')';
                        }
                        $free_shipping = $this->core->is_free_shipping($offer->group);
                        $rate = array(
                            'id' => $this->id . '_terminal_' . $this->core->get_offer_terminal_type($offer) . '_service_' . $offer->service_code,
                            'label' => $terminal_title . $delivery_time,
                            'cost' => $free_shipping ? 0 : $offer->price
                        );
                        $this->add_rate($rate);
                        break;
                    }
                //}
            } catch (\Exception $e) {
            }
        }

        private function get_delivery_time($delivery_time)
        {
            $config = $this->core->get_config();

            if (empty($delivery_time)) {
                return '';
            }

            if (strpos($delivery_time, '-') === false) {
                return $delivery_time;
            }

            $days = explode('-', $delivery_time);
            $day_from = preg_replace("/[^0-9]/", "", $days[0]);
            $day_to = preg_replace("/[^0-9]/", "", $days[1]);

            if (!empty($config['add_delivery_time'])) {
                $day_from += (int)$config['add_delivery_time'];
                $day_to += (int)$config['add_delivery_time'];
            }

            return sprintf('%d-%d d.d.', $day_from, $day_to);
            //return sprintf(_x('%d-%d days', 'Shipping time', 'omniva_global'), $day_from, $day_to); //Not working
        }

        public function process_admin_options()
        {
            update_option(Helper::get_prefix() . '_services_updated', 0);
            return parent::process_admin_options();
        }
    }
}
