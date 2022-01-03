<?php
namespace OmnivaTarptautinesWoo;

if (!defined('ABSPATH')) {
  exit;
}

use OmnivaTarptautinesWoo\Helper;
use OmnivaApi\API as Omniva_api;

class Api {
    
    private $omniva_api;
    private $prefix;
    
    public function __construct($config = array()) {
        $this->omniva_api = new Omniva_api(Helper::get_config_value('api_token', $config, "no_token"), true, false);
        $this->omniva_api->setUrl(Helper::get_config_value('api_url', $config) . "/api/v1/");
        $this->prefix = Helper::get_prefix() . '_api';
    }
    
    public function get_services(){
        $cache_name = $this->prefix . '_services';
        $data = get_transient($cache_name);
        if ($data === false) {
            $data = $this->omniva_api->listAllServices();
            set_transient($cache_name, $data, 1800);
            $last_count = get_option(Helper::get_prefix() . '_total_services', 0);
            if ($last_count != count($data) && $last_count > 0){
                update_option(Helper::get_prefix() . '_services_updated', 1);
            }
            update_option(Helper::get_prefix() . '_total_services', count($data));
        }
        return $data;
    }
    
    public function get_countries(){
        $cache_name = $this->prefix . '_countries';
        $data = get_transient($cache_name);
        if ($data === false) {
            $data = $this->omniva_api->listAllCountries();
            set_transient($cache_name, $data, 1800);
        }
        return $data;
    }
    
    public function get_terminals($country = "ALL"){
        $cache_name = $this->prefix . '_terminals_' . $country;
        $data = get_transient($cache_name);
        if ($data === false) {
            $data = $this->omniva_api->getTerminals($country);
            set_transient($cache_name, $data, 1800);
        }
        return $data;
    }
    
    public function get_offers($sender, $receiver, $parcels){
        return $this->omniva_api->getOffers($sender, $receiver, $parcels);
    }
    
    public function create_order($order){
        return $this->omniva_api->generateOrder($order);
    }
    
    public function cancel_order($shipment_id){
        return $this->omniva_api->cancelOrder($shipment_id);
    }
    
    public function get_label($shipment_id){
        return $this->omniva_api->getLabel($shipment_id);
    }
    
    public function generate_manifest($cart_id){
        return $this->omniva_api->generateManifest($cart_id);
    }
    
    public function generate_latest_manifest(){
        return $this->omniva_api->generateManifestLatest();
    }
}
