<?php

namespace OmnivaTarptautinesWoo;

use OmnivaTarptautinesWoo\Helper;

class Product {
    
    private $product_terms = [];
    
    private $config = [];
    
    public function __construct() {
        
    }
    
    public function set_config($config) {
        $this->config = $config;
    }
    
    public function get_weight($product){
        $weight = $product->get_weight();
        if (!$weight) {
            $weight = $this->get_param_from_cat($product, 'weight');
        }
        return $weight ? $weight : false;
    }
    
    public function get_width($product){
        $width = $product->get_width();
        if (!$width) {
            $width = $this->get_param_from_cat($product, 'width');
        }
        return $width ? $width : false;
    }
    
    public function get_height($product){
        $height = $product->get_height();
        if (!$height) {
            $height = $this->get_param_from_cat($product, 'height');
        }
        return $height ? $height : false;
    }
    
    public function get_length($product){
        $length = $product->get_length();
        if (!$length) {
            $length = $this->get_param_from_cat($product, 'length');
        }
        return $length ? $length : false;
    }
    
    
    private function get_param_from_cat($product, $param) {
        if (isset($product_terms[$product->get_id()])) {
            $terms = $product_terms[$product->get_id()];
        } else {
            $terms = get_the_terms( $product->get_id(), 'product_cat' );
            if (is_array($terms)) {
                $terms = $this->order_term_hierarchy($terms);
                $product_terms[$product->get_id()] = $terms;
            } else {
                return $this->get_param_from_config($param);
            }
        }
        foreach (array_reverse($terms) as $term){
            $param = get_term_meta($term->term_id, 'og_default_' . $param, true);
            if ($param) {
                return $param;
            }
        }
        return $this->get_param_from_config($param);
    }
    
    private function get_param_from_config($param) {
        return $this->config['product_' . $param ] ?? false;
    }
    
    private function order_term_hierarchy($terms, $parent = 0){
        $ordered = [];
        foreach ($terms as $term){
            if ($term->parent == $parent) {
                $ordered = $term;
                array_merge($terms, $term->term_id);
            }
        }
        return $ordered;
    }
    
}
