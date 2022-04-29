<?php

namespace OmnivaTarptautinesWoo;

use OmnivaTarptautinesWoo\Category;
use OmnivaTarptautinesWoo\Product;
use OmnivaTarptautinesWoo\Api;
use OmnivaTarptautinesWoo\Helper;
use OmnivaApi\Sender;
use OmnivaApi\Receiver;
use OmnivaApi\Parcel;
use OmnivaApi\Item;

class Core {

    private $api;
    private $config;

    public function __construct() {
        $this->api = new Api($this->get_config());
    }

    public function get_api() {
        return $this->api;
    }

    public function get_config() {
        if (empty($this->config)) {
            $this->config = get_option('woocommerce_' . Helper::get_prefix() . '_settings', array());
        }
        return $this->config;
    }

    public function get_country_id($country_code) {
        foreach ($this->api->get_countries() as $id => $country) {
            if ($country->code == $country_code) {
                return $country->id;
            }
        }
    }

    public function get_sender() {
        $config = $this->get_config();
        //$send_off = $config['send_off'];
        $send_off = 'courier';

        $sender = new Sender($send_off);
        $sender->setCompanyName($config['company']);
        $sender->setContactName($config['company']);
        $sender->setStreetName($config['shop_address']);
        $sender->setZipcode($config['shop_postcode']);
        $sender->setCity($config['shop_city']);
        $sender->setCountryId($this->get_country_id($config['shop_countrycode']));
        $sender->setPhoneNumber($config['shop_phone']);
        return $sender;
    }

    public function get_receiver($package) {
        $config = $this->get_config();
        //$send_off = $config['send_off'];
        $send_off = 'courier';

        if (is_array($package)) {
            //create from array at checkout
            $user = false;
            if ($package['user']['ID']) {
                $user = get_userdata($package['user']['ID']);
            }
            $receiver = new Receiver($send_off);
            if ($user) {
                $receiver->setContactName($user->first_name . ' ' . $user->last_name);
            } else {
                $customer = WC()->session->get('customer');
                if ($customer) {
                    $receiver->setContactName($customer['first_name'] . ' ' . $customer['last_name']);
                } else {
                    $receiver->setContactName("");
                }
            }
            $receiver->setStreetName($package['destination']['address']);
            $receiver->setZipcode($package['destination']['postcode']);
            $receiver->setCity($package['destination']['city']);
            $receiver->setCountryId($this->get_country_id($package['destination']['country']));
            $receiver->setStateCode($package['destination']['state'] ?? null);
            $receiver->setPhoneNumber((string) WC()->checkout->get_value('shipping_phone') ?? WC()->checkout->get_value('billing_phone'));
            return $receiver;
        } elseif (is_object($package)) {
            //create from object on order
            $receiver = new Receiver($send_off);
            $receiver->setCompanyName($package->get_shipping_company());
            $receiver->setContactName($package->get_shipping_first_name() . ' ' . $package->get_shipping_last_name());
            $receiver->setStreetName($package->get_shipping_address_1());
            $receiver->setZipcode($package->get_shipping_postcode());
            $receiver->setCity($package->get_shipping_city());
            $receiver->setCountryId($this->get_country_id($package->get_shipping_country()));
            $receiver->setStateCode($package->get_shipping_state());
            $receiver->setPhoneNumber((string)$package->get_billing_phone());
            return $receiver;
        }

        return false;
    }

    //TO DO calculate dimensions, weight
    public function get_parcels($order = false) {
        global $woocommerce;
        $product = new Product();
        $product->set_config($this->get_config());
        $parcels = [];
        if ($order) {
            $items = $order->get_items();
        } else {
            $items = $woocommerce->cart->get_cart();
        }
        foreach ($items as $id => $data) {
            $parcel = new Parcel();
            if ($order) {
                $_product = $data->get_product();
                $parcel->setAmount($data->get_quantity());
            } else {
                $_product = $data['data'];
                $parcel->setAmount($data['quantity']);
            }
            if ($product->get_virtual($_product)) {
                continue;
            }
            $parcel->setUnitWeight($product->get_weight($_product));
            $parcel->setHeight($product->get_height($_product));
            $parcel->setWidth($product->get_width($_product));
            $parcel->setLength($product->get_length($_product));
            $parcels[] = $parcel->generateParcel();
        }
        return $parcels;
    }

    public function get_items($order) {
        $config = $this->get_config();
        $items = [];
        $order_items = $order->get_items();
        foreach ($order_items as $id => $data) {
            $item = new Item();
            $item->setItemAmount($data->get_quantity());
            $item->setDescription($data->get_name());
            $item->setItemPrice($data->get_total() / $data->get_quantity());
            $item->setCountryId($this->get_country_id($config['shop_countrycode']));
            $items[] = $item->generateItem();
        }
        return $items;
    }

    public function get_offers($package) {
        $parcels = $this->get_parcels();
        return $this->api->get_offers($this->get_sender(), $this->get_receiver($package), $parcels);
    }

    private function selected_services() {
        $selected = [];
        foreach ($this->config as $key => $value) {
            if ($value == "yes" && stripos($key, '_service_') !== false) {
                $data = explode('_', $key);
                if (count($data) == 3) {
                    $group = $data[0];
                    $service_code = $data[2];
                    $group_enabled = isset($this->config[$group . '_enable']) && $this->config[$group . '_enable'] == 'yes' ? true : false;
                    if ($group_enabled) {
                        $selected[$service_code] = $group;
                    }
                }
            }
        }
        return $selected;
    }

    public function filter_enabled_offers($offers) {
        $config = $this->get_config();
        $own_login = isset($config['own_login']) && $config['own_login'] == 'yes' ? true : false;
        $filtered_offers = [];
        $selected_services = $this->selected_services();
        foreach ($offers as $offer) {
            if (isset($selected_services[$offer->service_code])) {
                //check if has own login and info is entered in settings
                if (!$this->is_own_login_ok($offer)) {
                    continue;
                }
                $offer->group = $selected_services[$offer->service_code];
                $filtered_offers[] = $offer;
            }
        }
        return $filtered_offers;
    }

    public function set_offers_price(&$offers) {

        foreach ($offers as $offer) {
            $offer->org_price = $offer->price;
            
            $type = $this->config[$offer->group . '_price_type'];
            $value = $this->config[$offer->group . '_price_value'];
            $offer->price = $this->calculate_price($offer->price, $type, $value);
        }
    }

    public function set_offers_name(&$offers) {
        $name = $this->config['courier_title'];

        foreach ($offers as $offer) {
            $offer->org_name = $offer->name;
            $offer->name = $name;
        }
    }

    public function sort_offers(&$offers) {
        $grouped = array();
        foreach ($offers as $offer) {
            if (!isset($grouped[$offer->group])) {
                $grouped[$offer->group] = [];
            }
            $grouped[$offer->group][] = $offer;
        }
        foreach ($grouped as $group => $grouped_offers) {
            $sort_by = $this->config[$group . '_sort_by'] ?? "default";
            if ($sort_by == "fastest") {
                usort($grouped[$group], function ($v, $k) {
                    return $this->get_offer_delivery($k) <= $this->get_offer_delivery($v);
                });
            } elseif ($sort_by == "cheapest") {
                usort($grouped[$group], function ($v, $k) {
                    return $k->price <= $v->price;
                });
            }
        }
    }

    public function get_offer_terminal_type($offer) {
        $services = $this->api->get_services();
        foreach ($services as $service) {
            if ($offer->service_code == $service->service_code) {
                if (isset($service->parcel_terminal_type)) {
                    return $service->parcel_terminal_type;
                }
                return '';
            }
        }
        return '';
    }

    public function get_identifier_form_method($title) {
        $title = str_ireplace('omniva_global_terminal_', '', $title);
        $data = explode('_service_', $title);
        return $data[0] ?? '';
    }

    public function get_service_form_method($title) {
        $title = str_ireplace('omniva_global_terminal_', '', $title);
        $data = explode('_service_', $title);
        return $data[1] ?? '';
    }

    public function get_offer_delivery($offer) {
        $re = '/^[^\d]*(\d+)/';
        preg_match($re, $offer->delivery_time, $matches, PREG_OFFSET_CAPTURE, 0);
        return $matches[0] ?? 1;
    }

    public function is_offer_terminal($offer) {
        $services = $this->api->get_services();
        foreach ($services as $service) {
            if ($offer->service_code == $service->service_code) {
                if ($service->delivery_to_address == false) {
                    return true;
                }
                return false;
            }
        }
        return false;
    }

    private function calculate_price($price, $type, $value) {
        if (!$value) {
            return $price;
        }
        if ($type == "fixed") {
            $price = $value;
        } else if ($type == "addition_percent") {
            $price += round($price * $value / 100, 2);
        } else if ($type == "addition_eur") {
            $price += $value;
        }
        return $price;
    }

    public function is_free_shipping() {
        $cart_total = WC()->cart->get_cart_contents_total();
        $free_ship = $this->config['free_shipping'] ?? 0;
        if ($free_ship > 0 && $free_ship <= $cart_total) {
            return true;
        }
        return false;
    }

    public function get_additional_services($service_code) {
        $services = $this->api->get_services();
        $allowed_services = [];
        foreach ($services as $service) {
            if ($service->service_code == $service_code) {
                if (isset($service->additional_services)) {
                    foreach ($service->additional_services as $add_service => $status) {
                        if ($status == true) {
                            $allowed_services[] = $add_service;
                        }
                    }
                }
                break;
            }
        }
        return $allowed_services;
    }

    public function has_own_login($service) {
        if (isset($service->additional_services)) {
            foreach ($service->additional_services as $add_service => $status) {
                if ($add_service == 'own_login') {
                    return $status;
                }
            }
        }
        return false;
    }
    
    public function is_own_login_ok($offer) {
        $services = $this->api->get_services();
        foreach ($services as $service) {
            if ($service->service_code == $offer->service_code) {
                if (isset($service->additional_services)) {
                    foreach ($service->additional_services as $add_service => $status) {
                        if ($add_service == 'own_login' && $status == true && (empty('service_' . $service->service_code . '_own_login_user') || empty('service_' . $service->service_code . '_own_login_password'))) {
                            return false;
                        } else {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

}
