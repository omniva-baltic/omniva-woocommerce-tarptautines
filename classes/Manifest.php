<?php

namespace OmnivaTarptautinesWoo;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use OmnivaTarptautinesWoo\Helper;

class Manifest {

    private $tab_strings = array();
    private $filter_keys = array();
    private $max_per_page = 25;
    private $api;
    private $core;

    public function __construct($api, $core) {
        $this->tab_strings = array(
            'all_orders' => __('All orders', 'omniva_global'),
            'new_orders' => __('New orders', 'omniva_global'),
            'completed_orders' => __('Completed orders', 'omniva_global')
        );

        $this->filter_keys = array(
            'customer',
            'status',
            'barcode',
            'manifest',
            'id',
            'start_date',
            'end_date'
        );

        $this->api = $api;
        $this->core = $core;
        add_action('admin_menu', array($this, 'register_omniva_manifest_menu_page'));
        add_action('omniva_admin_manifest_head', array($this, 'omniva_global_admin_manifest_scripts'));
        add_filter('woocommerce_order_data_store_cpt_get_orders_query', array($this, 'handle_custom_omniva_query_var'), 10, 2);
        add_action('init', array($this, 'generate_manifest'));
    }
    
    public function generate_manifest() {
        if (current_user_can( 'edit_shop_orders' ) && is_admin() && isset($_GET['omniva_global_manifest'])) {
            try {
                $labels = false;
                if (isset($_GET['labels']) && $_GET['labels'] == 1) {
                    $labels = true;
                }
                if (!empty($_GET['omniva_global_manifest'])){
                    $response = $this->api->generate_manifest($_GET['omniva_global_manifest']);
                } else {
                    $response = $this->api->generate_latest_manifest();
                }
                
                $orders = get_posts(array(
                    'numberposts'   => -1,
                    'post_type'     => 'shop_order',
                    'post_status'   => 'any',
                    'meta_query'        => array(
                        array(
                            'key'       => '_omniva_global_cart_id',
                            'value'     => $response->cart_id
                        )
                    )
                ));
                foreach ($orders as $order){
                    $date = get_post_meta($order->ID, '_omniva_global_manifest_date', true );
                    if (!$date) {
                        update_post_meta($order->ID, '_omniva_global_manifest_date', date('Y-m-d H:i:s') );
                    }
                }
                if ($labels) {
                    $pdf = base64_decode($response->labels);
                } else {
                    $pdf = base64_decode($response->manifest);
                }
                header('Content-type:application/pdf');
                header('Content-disposition: inline; filename="'.$response->cart_id.'"');
                header('content-Transfer-Encoding:binary');
                header('Accept-Ranges:bytes');
                echo $pdf;
                exit;
            } catch (\Exception $e) {
                echo $e->getMessage();
                exit;
            }
        }
    }

    public function omniva_global_admin_manifest_scripts() {
        wp_enqueue_style('omniva_global_admin_manifest', plugin_dir_url(__DIR__) . 'assets/css/admin_manifest.css');
        wp_enqueue_style('bootstrap-datetimepicker', plugin_dir_url(__DIR__) . 'assets/js/datetimepicker/bootstrap-datetimepicker.min.css');
        wp_enqueue_script('moment', plugin_dir_url(__DIR__) . 'assets/js/moment.min.js', array(), null, true);
        wp_enqueue_script('bootstrap-datetimepicker', plugin_dir_url(__DIR__) . 'assets/js/datetimepicker/bootstrap-datetimepicker.min.js', array('jquery', 'moment'), null, true);
    }

    public function register_omniva_manifest_menu_page() {
        add_submenu_page(
                'woocommerce',
                __('Omniva international', 'omniva_global'),
                __('Omniva international', 'omniva_global'),
                'manage_woocommerce',
                'omniva-global-manifest',
                array($this, 'render_page'),
                //plugins_url('omniva-woocommerce/images/icon.png'),
                1
        );
    }

    public function handle_custom_omniva_query_var($query, $query_vars) {
        if (!empty($query_vars['omniva_global_method'])) {
            $query['meta_query'][] = array(
                'key' => '_omniva_global_method',
                'value' => $query_vars['omniva_global_method']//esc_attr( $query_vars['omniva_global_method'] ),
            );
        }

        if (isset($query_vars['omniva_global_barcode'])) {
            $query['meta_query'][] = array(
                'key' => '_omniva_global_tracking_numbers',
                'value' => $query_vars['omniva_global_barcode'],
                'compare' => 'LIKE'
            );
        }

        if (isset($query_vars['omniva_global_customer'])) {
            $query['meta_query'][] = array(
                'relation' => 'OR',
                array(
                    'key' => '_billing_first_name',
                    'value' => $query_vars['omniva_global_customer'],
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => '_billing_last_name',
                    'value' => $query_vars['omniva_global_customer'],
                    'compare' => 'LIKE'
                )
            );
        }

        if (isset($query_vars['omniva_global_manifest'])) {
            $query['meta_query'][] = array(
                'key' => '_omniva_global_cart_id',
                'value' => $query_vars['omniva_global_manifest'],
            );
        }

        if (isset($query_vars['omniva_global_manifest_date'])) {
            $filter_by_date = false;
            if ($query_vars['omniva_global_manifest_date'][0] && $query_vars['omniva_global_manifest_date'][1]) {
                $filter_by_date = array(
                    'key' => '_omniva_global_manifest_date',
                    'value' => $query_vars['omniva_global_manifest_date'],
                    'compare' => 'BETWEEN'
                );
            } elseif ($query_vars['omniva_global_manifest_date'][0] && !$query_vars['omniva_global_manifest_date'][1]) {
                $filter_by_date = array(
                    'key' => '_omniva_global_manifest_date',
                    'value' => $query_vars['omniva_global_manifest_date'][0],
                    'compare' => '>='
                );
            } elseif (!$query_vars['omniva_global_manifest_date'][0] && $query_vars['omniva_global_manifest_date'][1]) {
                $filter_by_date = array(
                    'key' => '_omniva_global_manifest_date',
                    'value' => $query_vars['omniva_global_manifest_date'][1],
                    'compare' => '<='
                );
            }

            if ($filter_by_date) {
                $query['meta_query'][] = $filter_by_date;
            }
        }

        return $query;
    }

    /**
     * helper function to create links
     */
    public function make_link($args) {
        $query_args = array('page' => 'omniva-global-manifest');
        $query_args = array_merge($query_args, $args);
        return add_query_arg($query_args, admin_url('/admin.php'));
    }

    public function render_page() {
        // append custom css and js
        do_action('omniva_admin_manifest_head');
        ?>

        <div class="wrap page-omniva_manifest">
            
            <img src = "<?php echo plugin_dir_url(__DIR__); ?>assets/images/logo.svg" style="width: 100px;"/>
            <h1><?php _e('International manifest', 'omniva_global'); ?></h1>

            <?php
            $paged = 1;
            if (isset($_GET['paged']))
                $paged = filter_input(INPUT_GET, 'paged');

            $action = 'all_orders';
            if (isset($_GET['action'])) {
                $action = filter_input(INPUT_GET, 'action');
            }

            $filters = array();
            foreach ($this->filter_keys as $filter_key) {
                if (isset($_POST['filter_' . $filter_key]) && intval($_POST['filter_' . $filter_key]) !== -1) {
                    $filters[$filter_key] = filter_input(INPUT_POST, 'filter_' . $filter_key); //$_POST['filter_' . $filter_key];
                } else {
                    $filters[$filter_key] = false;
                }
            }

            // Handle query variables depending on selected tab
            switch ($action) {
                case 'new_orders':
                    $page_title = $this->tab_strings[$action];
                    $args = array(
                        'status' => array('wc-processing', 'wc-on-hold', 'wc-pending'),
                    );
                    break;
                case 'completed_orders':
                    $page_title = $this->tab_strings[$action];
                    $args = array(
                        'status' => array('wc-completed'),
                    );
                    break;
                case 'all_orders':
                default:
                    $action = 'all_orders';
                    $page_title = $this->tab_strings['all_orders'];
                    $args = array();
                    break;
            }

            foreach ($filters as $key => $filter) {
                if ($filter) {
                    switch ($key) {
                        case 'status':
                            $args = array_merge(
                                    $args,
                                    array('status' => $filter)
                            );
                            break;
                        case 'barcode':
                            $args = array_merge(
                                    $args,
                                    array('omniva_global_barcode' => $filter)
                            );
                            break;
                        case 'manifest':
                            $args = array_merge(
                                    $args,
                                    array('omniva_global_manifest' => $filter)
                            );
                            break;
                        case 'customer':
                            $args = array_merge(
                                    $args,
                                    array('omniva_global_customer' => $filter)
                            );
                            break;
                    }
                }
            }
            // date filter is a special case
            if ($filters['start_date'] || $filters['end_date']) {
                $args = array_merge(
                        $args,
                        array('omniva_global_manifest_date' => array($filters['start_date'], $filters['end_date']))
                );
            }

            // Get orders with extra info about the results.
            $args = array_merge(
                    $args,
                    array(
                        'omniva_global_method' => 1,
                        'paginate' => true,
                        'limit' => $this->max_per_page,
                        'paged' => $paged,
                    )
            );
            
            // Searching by ID takes priority
            $singleOrder = false;
            if ($filters['id']) {
                $singleOrder = wc_get_order($filters['id']);
                if ($singleOrder) {
                    $orders = array($singleOrder); // table printer expects array
                    $paged = 1;
                }
            }

            // if there is no search by ID use to custom query
            $results = false;
            if (!$singleOrder) {
                $results = wc_get_orders($args);
                $orders = $results->orders;
            }

            $thereIsOrders = ($singleOrder || ($results && $results->total > 0));

            // make pagination
            $page_links = false;
            if ($results) {
                $page_links = paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '?paged=%#%',
                    'prev_text' => __('&laquo;', 'text-domain'),
                    'next_text' => __('&raquo;', 'text-domain'),
                    'total' => $results->max_num_pages,
                    'current' => $paged,
                    'type' => 'plain'
                ));
            }

            $order_statuses = wc_get_order_statuses();
            ?>
            <ul class="nav nav-tabs">
                <?php foreach ($this->tab_strings as $tab => $tab_title) : ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $action == $tab ? 'active' : ''; ?>" href="<?php echo $this->make_link(array('paged' => ($action == $tab ? $paged : 1), 'action' => $tab)); ?>"><?php echo $tab_title; ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($page_links) : ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php echo $page_links; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($thereIsOrders) : ?>
                <div class="mass-print-container">
                    <a title="<?php echo __('Generate latest manifest', 'omniva_global'); ?>" href="<?php echo Helper::generate_manifest_url();?>" target ="_blank" class="generate_manifest button action">
                        <?php echo __('Generate latest manifest', 'omniva_global'); ?>
                    </a>
                    <a title="<?php echo __('Generate and print manifest labels', 'omniva_global'); ?>" href="<?php echo Helper::print_manifest_labels_url();?>" target ="_blank" class="generate_manifest button action">
                        <?php echo __('Generate and print manifest labels', 'omniva_global'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <form id="filter-form" class="" action="<?php echo $this->make_link(array('action' => $action)); ?>" method="POST">
                    <?php wp_nonce_field('omniva_global_labels', 'omniva_global_labels_nonce'); ?>
                    <table class="wp-list-table widefat fixed striped posts">
                        <thead>

                            <tr class="omniva-filter">
                                <td class="manage-column column-cb check-column"><input type="checkbox" class="check-all" /></td>
                                <th class="manage-column column-order_id">
                                    <input type="text" class="d-inline" name="filter_id" id="filter_id" value="<?php echo $filters['id']; ?>" placeholder="<?php echo __('ID', 'omniva_global'); ?>" aria-label="Order ID filter">
                                </th>
                                <th class="manage-column">
                                    <input type="text" class="d-inline" name="filter_customer" id="filter_customer" value="<?php echo $filters['customer']; ?>" placeholder="<?php echo __('Customer', 'omniva_global'); ?>" aria-label="Order ID filter">
                                </th>
                                <th class="column-order_status">
                                    <select class="d-inline" name="filter_status" id="filter_status" aria-label="Order status filter">
                                        <option value="-1" selected><?php echo _x('All', 'All status', 'omniva_global'); ?></option>
                                        <?php foreach ($order_statuses as $status_key => $status) : ?>
                                            <option value="<?php echo $status_key; ?>" <?php echo ($status_key == $filters['status'] ? 'selected' : ''); ?>><?php echo $status; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </th>
                                <th class="column-order_date">
                                </th>
                                <th class="manage-column">
                                </th>
                                <th class="manage-column">
                                    <input type="text" class="d-inline" name="filter_barcode" id="filter_barcode" value="<?php echo $filters['barcode']; ?>" placeholder="<?php echo __('Barcode', 'omniva_global'); ?>" aria-label="Order barcode filter">
                                </th>
                                <th class="manage-column">
                                    <input type="text" class="d-inline" name="filter_manifest" id="filter_manifest" value="<?php echo $filters['manifest']; ?>" placeholder="<?php echo __('Manifest ID', 'omniva_global'); ?>" aria-label="Manifest ID filter">
                                </th>
                                <th class="column-manifest_date">
                                    <div class='datetimepicker'>
                                        <div>
                                            <input name="filter_start_date" type='text' class="" id='datetimepicker1' data-date-format="YYYY-MM-DD" value="<?php echo $filters['start_date']; ?>" placeholder="<?php echo __('From', 'omniva_global'); ?>" autocomplete="off" />
                                        </div>
                                        <div>
                                            <input name="filter_end_date" type='text' class="" id='datetimepicker2' data-date-format="YYYY-MM-DD" value="<?php echo $filters['end_date']; ?>" placeholder="<?php echo __('To', 'omniva_global'); ?>" autocomplete="off" />
                                        </div>
                                    </div>
                                </th>
                                <th class="manage-column">
                                    <div class="omniva-action-buttons-container">
                                        <button class="button action" type="submit"><?php echo __('Filter', 'omniva_global'); ?></button>
                                        <button id="clear_filter_btn" class="button action" type="submit"><?php echo __('Reset', 'omniva_global'); ?></button>
                                    </div>
                                </th>
                            </tr>

                            <tr class="table-header">
                                <td class="manage-column column-cb check-column"></td>
                                <th scope="col" class="column-order_id"><?php echo __('ID', 'omniva_global'); ?></th>
                                <th scope="col" class="manage-column"><?php echo __('Customer', 'omniva_global'); ?></th>
                                <th scope="col" class="column-order_status"><?php echo __('Order Status', 'omniva_global'); ?></th>
                                <th scope="col" class="column-order_date"><?php echo __('Order Date', 'omniva_global'); ?></th>
                                <th scope="col" class="manage-column"><?php echo __('Service', 'omniva_global'); ?></th>
                                <th scope="col" class="manage-column"><?php echo __('Barcode', 'omniva_global'); ?></th>
                                <th scope="col" class="manage-column"><?php echo __('Manifest ID', 'omniva_global'); ?></th>
                                <th scope="col" class="column-manifest_date"><?php echo __('Manifest date', 'omniva_global'); ?></th>
                                <th scope="col" class="manage-column"><?php echo __('Actions', 'omniva_global'); ?></th>
                            </tr>

                        </thead>
                        <tbody>
                            <?php $date_tracker = false; ?>
                            <?php foreach ($orders as $order) : ?>
                                <?php
                                $manifest_date = $order->get_meta('_omniva_global_manifest_date');
                                $cart_id = $order->get_meta('_omniva_global_cart_id');
                                $date = date('Y-m-d H:i', strtotime($manifest_date));
                                ?>
                                <?php if ($action == 'completed_orders' && $date_tracker !== $date) : ?>
                                    <tr>
                                        <td colspan="9" class="manifest-date-title">
                                            <?php echo $date_tracker = $date; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="data-row">
                                    <th scope="row" class="check-column"><input type="checkbox" name="items[]" class="manifest-item" value="<?php echo $order->get_id(); ?>" /></th>
                                    <td class="manage-column column-order_id">
                                        <a href="<?php echo $order->get_edit_order_url(); ?>">#<?php echo $order->get_order_number(); ?></a>
                                    </td>
                                    <td class="column-order_number">
                                        <div class="data-grid-cell-content">
                                            <?php echo $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(); ?>
                                        </div>
                                    </td>
                                    <td class="column-order_status">
                                        <div class="data-grid-cell-content">
                                            <?php echo wc_get_order_status_name($order->get_status()); ?>
                                        </div>
                                    </td>
                                    <td class="column-order_date">
                                        <div class="data-grid-cell-content">
                                            <?php echo $order->get_date_created()->format('Y-m-d H:i:s'); ?>
                                        </div>
                                    </td>
                                    <td class="manage-column">
                                        <div class="data-grid-cell-content">
                                            <?php //omniva_terminal_field_display_admin_order_meta($order, false);   ?>
                                        </div>
                                    </td>
                                    <td class="manage-column">
                                        <div class="data-grid-cell-content">
                                            <?php $barcode = $order->get_meta('_omniva_global_tracking_numbers'); ?>
                                            <?php $shipment_id = $order->get_meta('_omniva_global_shipment_id'); ?>
                                            <?php if ($barcode) : ?>
                                                <?php echo implode(', ', $barcode);  ?>
                                            <?php endif; ?>
                                            <?php $error = $order->get_meta('_omniva_global_error'); ?>
                                            <?php if ($error) : ?>
                                                <?php if ($barcode) : ?><br /><?php endif; ?>
                                                <span><?php echo '<b>' . __('Error', 'omniva_global') . ':</b> ' . $error; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="column-manifest_id">
                                        <div class="data-grid-cell-content">
                                            <?php echo $cart_id; ?>
                                        </div>
                                    </td>
                                    <td class="column-manifest_date">
                                        <div class="data-grid-cell-content">
                                            <?php echo $manifest_date; ?>
                                        </div>
                                    </td>
                                    <td class="manage-column">
                                        <?php if ($barcode && $shipment_id) : ?>
                                        <a href="<?php echo Helper::print_label_url($shipment_id);?>" target = "_blank" class="button action">
                                            <?php echo __('Print label', 'omniva_global'); ?>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($manifest_date && $cart_id) : ?>
                                        <a href="<?php echo Helper::print_manifest_labels_url($cart_id);?>" target = "_blank" class="button action">
                                            <?php echo __('Print labels', 'omniva_global'); ?>
                                        </a>
                                        <a href="<?php echo Helper::generate_manifest_url($cart_id);?>" target = "_blank" class="button action">
                                            <?php echo __('Print manifest', 'omniva_global'); ?>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$orders) : ?>
                                <tr>
                                    <td colspan="10">
                                        <?php echo __('No orders found', 'woocommerce'); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </form>
            </div>

            <script>
                jQuery('document').ready(function ($) {
                    // "From" date picker
                    $('#datetimepicker1').datetimepicker({
                        pickTime: false,
                        useCurrent: false
                    });
                    // "To" date picker
                    $('#datetimepicker2').datetimepicker({
                        pickTime: false,
                        useCurrent: false
                    });

                    // Set limits depending on date picker selections
                    $("#datetimepicker1").on("dp.change", function (e) {
                        $('#datetimepicker2').data("DateTimePicker").setMinDate(e.date);
                    });
                    $("#datetimepicker2").on("dp.change", function (e) {
                        $('#datetimepicker1').data("DateTimePicker").setMaxDate(e.date);
                    });

                    // Pass on filters to pagination links
                    $('.tablenav-pages').on('click', 'a', function (e) {
                        e.preventDefault();
                        var form = document.getElementById('filter-form');
                        form.action = e.target.href;
                        form.submit();
                    });

                    // Filter cleanup and page reload
                    $('#clear_filter_btn').on('click', function (e) {
                        e.preventDefault();
                        $('#filter_id, #filter_customer, #filter_barcode, #filter_manifest, #datetimepicker1, #datetimepicker2').val('');
                        $('#filter_status').val('-1');
                        document.getElementById('filter-form').submit();
                    });

                    $('.check-all').on('click', function () {
                        var checked = $(this).prop('checked');
                        $(this).parents('table').find('.manifest-item').each(function () {
                            $(this).prop('checked', checked);
                        });
                    });
                    
                    $('.generate_manifest').on('click', function () {
                        setTimeout(function() {
                            location.reload();//reload page
                        }, 5000);
                    });
                     
                });
            </script>
            <?php
        }

    }
    