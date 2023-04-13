<?php

namespace OmnivaTarptautinesWoo;

use OmnivaTarptautinesWoo\Helper;

class Category {
    private $_fields = array();
    
    public function __construct()
    {
        $this->_fields = array(
            'weight' => __('Weight', 'omniva_global'),
            'width' => __('Width', 'omniva_global'),
            'height' => __('Height', 'omniva_global'),
            'length' => __('Length', 'omniva_global'),
        );

        add_action('product_cat_add_form_fields', array($this, 'add_fields'), 99, 1);
        add_action('product_cat_edit_form_fields', array($this, 'edit_fields'), 99, 1);
        add_action('edited_product_cat', array($this, 'save_fields'), 10, 1);
        add_action('create_product_cat', array($this, 'save_fields'), 10, 1);
    }
    
    public function add_fields()
    {
        ?>   
        <div class="form-field">
            <?php
            echo $this->get_label_html(__('Default weight', 'omniva_global') . ', ' . _x('kg', 'Unit', 'omniva_global'), 'og_default_weight');
            echo $this->get_input_field_html(array(
                'type' => 'number',
                'id' => 'og_default_weight',
                'name' => 'og_default_weight',
                'placeholder' => $this->_fields['weight'],
                'step' => 0.001,
                'min' => 0,
            ));
            ?>
        </div>
        <div class="form-field">
            <?php
            echo $this->get_label_html(__('Default size', 'omniva_global') . ', ' . _x('cm', 'Unit', 'omniva_global'), 'og_default_width');
            echo $this->get_size_field_html(array('prefix' => 'og_default'));
            ?>          
        </div>
        <?php
    }
    
    public function edit_fields($term)
    {
        $term_id = $term->term_id;
        $weight = get_term_meta($term_id, 'og_default_weight', true);
        $width = get_term_meta($term_id, 'og_default_width', true);
        $height = get_term_meta($term_id, 'og_default_height', true);
        $length = get_term_meta($term_id, 'og_default_length', true);
        ?>
        <tr class="form-field">
            <th scope="row" valign="top">
                <?php echo $this->get_label_html(__('Default weight', 'omniva_global') . ', ' . _x('kg', 'Unit', 'omniva_global'), 'og_default_weight'); ?>
            </th>
            <td>
                <?php
                echo $this->get_input_field_html(array(
                    'type' => 'number',
                    'id' => 'og_default_weight',
                    'name' => 'og_default_weight',
                    'placeholder' => $this->_fields['weight'],
                    'step' => 0.001,
                    'min' => 0,
                    'value' => esc_attr($weight) ? esc_attr($weight) : '',
                ));
                ?>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row" valign="top">
                <?php echo $this->get_label_html(__('Default size', 'omniva_global') . ', ' . _x('cm', 'Unit', 'omniva_global'), 'og_default_width'); ?>
            </th>
            <td>
                <?php
                echo $this->get_size_field_html(array(
                    'prefix' => 'og_default',
                    'values' => array(
                        'width' => esc_attr($width) ? esc_attr($width) : '',
                        'height' => esc_attr($height) ? esc_attr($height) : '',
                        'length' => esc_attr($length) ? esc_attr($length) : '',
                    ),
                ));
                ?>        
            </td>
        </tr>
        <?php
    }

    private function get_label_html($label, $for = '')
    {
        ob_start();
        ?>
        <label for="<?php echo $for; ?>"><?php echo $label; ?></label>
        <?php
        return ob_get_clean();
    }

    private function get_input_field_html($params)
    {
        $this->check_params($params);
        $params['type'] = $params['type'] ?? 'text';
        $params['value'] = $params['value'] ?? '';
        
        $html = '<input';
        foreach ( $params as $key => $param ) {
            $html .= ' ' . esc_html($key) . '="' . esc_html($param) . '"';
        }
        $html .= '>';

        return $html;
    }

    private function get_size_field_html($params)
    {
        $this->check_params($params);
        $values = $params['values'] ?? array();
        if ( ! is_array($values) ) {
            $values = array();
        }
        $prefix = $params['prefix'] ?? '';

        $html = '';
        foreach ( $this->_fields as $key => $title ) {
            if ( $key == 'weight' ) {
                continue;
            }
            if ( ! empty($html) ) {
                $html .= ' × ';
            }
            $html .= $this->get_input_field_html(array(
                'type' => 'number',
                'id' => $prefix . '_' . $key,
                'name' => $prefix . '_' . $key,
                'value' => $values[$key] ?? '',
                'class' => 'category_size',
                'placeholder' => $title,
                'step' => 0.1,
                'min' => 0,
            ));
        }

        return $html;
    }

    private function check_params(&$params)
    {
        if ( ! is_array($params) ) {
            $params = array();
        }
    }
    
    // Save extra taxonomy fields callback function.
    public function save_fields($term_id)
    {
        foreach ($this->_fields as $field => $title){
            $data = filter_input(INPUT_POST, 'og_default_' . $field);
            update_term_meta($term_id, 'og_default_' . $field, $data);
        }
    }
}
