<?php

namespace OmnivaTarptautinesWoo;

if (!defined('ABSPATH')) {
    exit;
}

class Helper {

    static function get_prefix() {
        return "omniva_global";
    }

    static function domain() {
        return "omniva_global";
    }

    /**
     * Use default value, when config not exists
     * 
     * @param string $key - Config key
     * @param array $config - All configs
     * @param mixed $default - Default value
     * @param boolean $not_allow_empty - Use default value when config exists, but value is empty
     * 
     * @return mixed - Config value or default value
     */
    static function get_config_value($key, $config, $default = '', $not_allow_empty = false) {
        return ( !isset($config[$key]) || ($not_allow_empty && empty($config[$key])) ) ? $default : $config[$key];
    }

    static function get_settings_url() {
        return get_admin_url() . 'admin.php?page=wc-settings&tab=shipping&section=' . self::get_prefix();
    }

    static function print_label_url($shipment_id) {
        return get_admin_url() . 'admin.php?omniva_global_label=' . $shipment_id;
    }

    static function print_manifest_labels_url($cart_id = false) {
        $url = self::generate_manifest_url($cart_id);
        $url .= '&labels=1';
        return $url;
    }

    static function generate_manifest_url($cart_id = false) {
        $url = get_admin_url() . 'admin.php?omniva_global_manifest';
        if ($cart_id) {
            $url .= '=' . $cart_id;
        }
        return $url;
    }

    static function additional_services() {
        return [
            'cod' => __('C.O.D.', 'omniva_global'),
            'return' => __('Return', 'omniva_global'),
            'ukd' => __('U.K.D', 'omniva_global'),
            'doc_return' => __('Document return', 'omniva_global'),
            'insurance' => __('Insurance', 'omniva_global'),
            'carry_service' => __('Carry service', 'omniva_global'),
            'fragile' => __('Fragile', 'omniva_global')
        ];
    }

    static function omniva_get_categories() {
        $cats = self::get_categories_hierarchy();
        $result = [];
        
        foreach ($cats as $item) {
            self::create_categories_list('', $item, $result);
        }

        return $result;
    }

    /**
     * Makes a list of categories to select from in settings page. array(lowest cat id => full cat path name)
     */
    static function create_categories_list($prefix, $data, &$results) {
        if ($prefix) {
            $prefix = $prefix . ' &gt; ';
            $results[$data->term_id] = $prefix . $data->name;
        }
        if (!$data->children) {
            $results[$data->term_id] = $prefix . $data->name;

            return true;
        }

        foreach ($data->children as $child) {
            self::create_categories_list($prefix . $data->name, $child, $results);
        }
    }

    static function get_categories_hierarchy($parent = 0) {
        $taxonomy = 'product_cat';
        $orderby = 'name';
        $hide_empty = 0;

        $args = array(
            'taxonomy' => $taxonomy,
            'parent' => $parent,
            'orderby' => $orderby,
            'hide_empty' => $hide_empty,
            'supress_filter' => true
        );

        $cats = get_categories($args);
        $children = array();
        
        foreach ($cats as $cat) {
            $cat->children = self::get_categories_hierarchy($cat->term_id);
            $children[$cat->term_id] = $cat;
        }

        return $children;
    }

}
