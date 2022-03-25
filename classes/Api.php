<?php
namespace OmnivaTarptautinesWoo;

if (!defined('ABSPATH')) {
  exit;
}

use OmnivaTarptautinesWoo\Helper;
use OmnivaTarptautinesWoo\Terminal;
use OmnivaApi\API as Omniva_api;

class Api {
    
    private $omniva_api;
    private $prefix;
    private $config = [];
    
    public function __construct($config = array()) {
        $this->config = $config;
        $this->omniva_api = new Omniva_api(Helper::get_config_value('api_token', $config, "no_token"), false, false);
        $this->omniva_api->setUrl(Helper::get_config_value('api_url', $config) . "/api/v1/");
        $this->prefix = Helper::get_prefix() . '_api';
    }
    
    public function get_services(){
        try {
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
        } catch (\Exception $e) {
            echo $e->getMessage();
            $data = [];
        }
        return $data;
    }
    
    public function get_countries(){
        try {
            $cache_name = $this->prefix . '_countries';
            $data = get_transient($cache_name);
            if ($data === false) {
                $data = $this->omniva_api->listAllCountries();
                set_transient($cache_name, $data, 3600 * 24 * 3);
            }
        } catch (\Exception $e) {
            $data = [];
        }
        return $data;
    }
    
    public function update_terminals(){
        try {
            $this->omniva_api->setTimeout(30);
            $data = $this->omniva_api->getTerminals('ALL');
        } catch (\Exception $e) {
            $data = [];
        }
            
        if (isset($data->parcel_machines) && is_array($data->parcel_machines)) {
            Terminal::delete();
            foreach ($data->parcel_machines as $terminal) {
                Terminal::insert($terminal);
            }
        }
    }
    
    public function get_terminals($country = null, $identifier = null){
        if (!Terminal::count()) {
            $this->update_terminals();
        }
        
        return Terminal::get($country, $identifier);
    }
    
    public function get_offers($sender, $receiver, $parcels){
        $hash = md5(json_encode(array(
            'sender' => $sender->generateSenderOffers(),
            'receiver' => $receiver->generateReceiverOffers(),
            'parcels' => $parcels
        )));
        $data = get_transient($hash);
        if ($data === false) {
            $data = $this->omniva_api->getOffers($sender, $receiver, $parcels);
            set_transient($hash, $data, 600);
        }
        return $data;
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
