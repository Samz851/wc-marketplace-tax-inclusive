<?php
/**
 * Plugin Name: Samz Recalculator
 * Description: A special plugin to apply recalculated values to vendors table.
 * Author: Samer Alotaibi
 * Text Domain: samz-plugin
 * PHP Version 7
 * 
 * @category Plugin
 * @package  Samz/recalculaator
 * @author   Samer Alotabi <sam.otb@hotmail.ca>
 * @license  MIT 
 * @link     https://github.com/Samz851
 */

 /**
  * The init function to alter database on plugin activation
  *
  * @category Plugin
  * @package  Samz/recalculaator
  * @return   void
  */
function Samz_Options_install()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "wcmp_vendor_orders";
    $_sql = "ALTER TABLE $table_name
    ADD recalculated INT(1) NOT NULL DEFAULT 0";
    include_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    // Check if column exists
    $row = $wpdb->get_row('SELECT * FROM '.$table_name);
    set_transient('samz-admin-notice-activation', true, 5);

    if (!isset($row->recalculated)) {
        $wpdb->query($_sql);
    }
}

register_activation_hook(__FILE__, 'Samz_Options_install');

add_action('admin_notices', 'Samz_Admin_notice__info');
/**
 * Function to add Alert Notices to admin panel on plugin activation
 * 
 * @return void
 */
function Samz_Admin_notice__info()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "wcmp_vendor_orders";
    $row = $wpdb->get_row('SELECT * FROM '.$table_name);

    if(get_transient( 'samz-admin-notice-activation' ) && isset($row->recalculated))
    {
        ?>
        <div class="notice-info notice is-dismissible">
            <p>The table named [wp_wcmp_vendor_orders] 
            now have flag column [recalculated].</p>
        </div>
        <?php

        delete_transient('samz-admin-notice-activation');
    }
}

/**
 * Function to add action to order actions dropdown menu in order page
 * 
 * @param array $actions takes the array of actions available
 * 
 * @return void
 */
function Samz_Woocommerce_Order_actions($actions)
{
    $actions['wc_custom_order_action'] = __('Apply tax inclusive calculations',
     'samz-plugin');
    return $actions;
}

add_action('woocommerce_order_actions', 'Samz_Woocommerce_Order_actions');

/**
 * Function to apply tax inclusive calculations
 * 
 * @param object $order takes the order object
 * 
 * @return void
 */
function apply_tax_inclusive_calculations($order)
{
    global $wpdb;

    $table_name = $wpdb->prefix . "wcmp_vendor_orders";

    $postmeta_table = $wpdb->prefix . "postmeta";

    $order_id = $order->get_id();

    $row = $wpdb->get_row('SELECT * FROM ' . $table_name .
    ' WHERE order_id = ' . $order_id);

    $wcmp_sql = " UPDATE $table_name
    SET shipping = ROUND(shipping * 0.9, 2) ,
    tax= ROUND(tax * 0.9, 2),
    shipping_tax_amount = ROUND(shipping_tax_amount * 0.9, 2),recalculated = 1
    WHERE order_id = " . $order_id;

    if ($row->commission_id > 0 && $row->recalculated == 0){
        $id = $row->commission_id;
        $wpdb->query($wcmp_sql);
        if(metadata_exists( 'post', $id, '_recalculated') == false){
            $shipping = get_post_meta($id, '_shipping', true);
            $tax = get_post_meta($id, '_tax', true);
            update_post_meta($id, '_recalculated',1);
            update_post_meta($id, '_shipping', $shipping * 0.9);
            update_post_meta($id, '_tax', $tax * 0.9);

            set_transient('samz-admin-notice-success', true, 5);
        } else {
            set_transient('samz-admin-notice-success', true, 5);
        }
    } else {
        set_transient('samz-admin-notice-error', true, 5);
    }
}
add_action( 'woocommerce_order_action_wc_custom_order_action', 
'apply_tax_inclusive_calculations');

/**
 * Function to add Admin Notice to Order page
 * 
 * @return void
 */
function Samz_Admin_notice__order()
{
    if(get_transient( 'samz-admin-notice-error' ))
    {
        ?>
        <div class="notice-error notice is-dismissible">
            <p>Either no commissions have been proccessed OR 
            Tax Inclusive Calculations have been applied</p>
        </div>
        <?php

        delete_transient('samz-admin-notice-error');
    }
    elseif (get_transient( 'samz-admin-notice-success'))
    {
        ?>
        <div class="notice-info notice is-dismissible">
            <p>Your commission rate have been applied to taxes and shipping</p>
        </div>
        <?php

        delete_transient('samz-admin-notice-success');
    }
}
    add_action('admin_notices', 'Samz_Admin_notice__order');
