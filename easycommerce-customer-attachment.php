<?php
/*
* Plugin Name:       EasyCommerce - Customer Attachments
* Plugin URI:        https://github.com/Unstable-Woofer/easycommerce-customer-attachments
* Description:       Allows customer to attach custom files when adding products to cart
* Version:           1.0.0
* Requires at least: 6.0
* Requires PHP:      8.1
* Author:            Unstable Woofer
* Author URI:        https://github.com/Unstable-Woofer
* License:           GPL v2 or later
* License URI:       https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain:       easycommerce-customer-attachments
* Domain Path:       /languages
*/

namespace EasyCommerce\Customer_Attachments;

defined('ABSPATH') || exit;

if (!class_exists('EasyCommerce_Core'))
    require_once(plugin_dir_path(__FILE__) . '/libaries/easycommerce.php');

easycommerce_core()->check_requirements(__FILE__) || exit;

require_once(plugin_dir_path(__FILE__) . '/inc/admin.php');

class EasyCommerce_Customer_Attachment {
    public function __construct() {
        if (is_admin()) :
            $plugin_admin = \EasyCommerce\Customer_Attachments\Admin::get_instance();
            $plugin_admin->hooks();
        endif;

        add_action('woocommerce_before_add_to_cart_button', array($this, 'add_product_attachment_field'));
        add_action('woocommerce_before_add_to_cart_button', array($this, 'add_product_customer_notes_field'));
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_easycommerce_data_to_order'), 10, 4);
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_easycommerce_data_to_cart_item'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_cart_customer_notes'), 10, 2);
    }

    /**
     * Add the attachment file field to the product page
     *
     */
    public function add_product_attachment_field() {
        global $product;
        $product_has_attachment = get_post_meta($product->get_id(), 'product_has_attachment', true) ?? 'no';

        if ($product_has_attachment === 'no' || !$product_has_attachment)
            return;

        ob_start();
        ?>
        <table class="variations">
            <tr>
                <th class="label">
                    <label for="product-customer-attachments"><?php _e('Your Design', 'easycommerce-customer-attachments'); ?></label>
                    <span>Allowed types: (jpg,png,pdf)</span>
                </th>
                <td class="value">
                    <input name="product-customer-attachments[]" id="product-customer-attachments" type="file" multiple="multiple"/>
                </td>
            </tr>
        </table>
        <?php
        _e(ob_get_clean());
    }

    /**
     * Add the customer notes text area to the product page
     *
     */
    public function add_product_customer_notes_field() {
        global $product;
        $product_has_customer_notes = get_post_meta($product->get_id(), 'product_has_customer_notes', true) ?? 'no';

        if ($product_has_customer_notes === 'no' || !$product_has_customer_notes)
            return;

        ob_start();
        ?>
        <table class="variations">
            <tr>
                <th class="label">
                    <label for="product-customer-notes"><?php _e('Notes', 'easycommerce-customer-attachments'); ?></label>
                </th>
                <td class="value">
                    <textarea name="product-customer-notes"></textarea>
                </td>
            </tr>
        </table>
        <?php
        _e(ob_get_clean());
    }

    /**
     * Add values from the easycommerce fields to the cart item
     * 
     * @since 1.0.0
     * 
     * @param array $cart_item_data
     * @param int   $product_id
     * @param int   $variation_id
     * 
     * @return array
     */
    public function add_easycommerce_data_to_cart_item($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['product-customer-notes']) && !empty($_POST['product-customer-notes'])) :
            $cart_item_data['customer-note'] = sanitize_textarea_field($_POST['product-customer-notes']);
        endif;

        if (isset($_FILES['product-customer-attachments']) && !empty($_FILES['product-customer-attachments']['tmp_name'][0])) :
            $file_urls = array();

            foreach($_FILES['product-customer-attachments']['tmp_name'] as $index => $tmp_name) :
                $filename = $_FILES['product-customer-attachments']['name'][$index];
                $upload = wp_upload_bits($_FILES['product-customer-attachments']['name'][$index], null, file_get_contents($tmp_name));
                $file_urls[] = array(
                    'url' => $upload['url'],
                    'name' => $filename
                );
            endforeach;

            $cart_item_data['customer-attachments'] = $file_urls;
        endif;

        return $cart_item_data;
    }

    /**
     * Add the easycommerce field values to the order
     * 
     * @since 1.0.0
     * 
     * @param WC_OrderItem_Product  $item
     * @param string                $cart_item_key
     * @param array                 $values
     * @param WC_Order              $order
     */
    public function add_easycommerce_data_to_order($item, $cart_item_key, $values, $order) {
        if (isset($values['customer-note'])) :
            $item->add_meta_data(__('Customer Notes', 'easycommerce-customer-attachments'), $values['customer-note']);
        endif;
        
        if (isset($values['customer-attachments'])) :
            foreach ($values['customer-attachments'] as $attachment) :
                $item->add_meta_data(__('Uploaded File', 'easycommerce-customer-attachments'), $attachment['name']);
                $item->add_meta_data(__('_Uploaded File Download', 'easycommerce-customer-attachments'), $attachment['url']);// Private meta data for the admin page
            endforeach;
        endif;
    }

    /**
     * Display the easycommerce field values on the cart page
     * 
     * @since 1.0.0
     * 
     * @param array $item_data
     * @param array $cart_item
     * 
     * @return array
     */
    public function display_cart_customer_notes($item_data, $cart_item) {
        if (!empty($cart_item['customer-note'])) :
            $item_data[] = array(
                'key' => __('Customer Notes', 'easycommerce-customer-attachments'),
                'value' => $cart_item['customer-note'],
                'display' => ''
            );
        endif;

        if (!empty($cart_item['customer-attachments'])) :
            foreach ($cart_item['customer-attachments'] as $attachment) :
                $item_data[] = array(
                    'key' => __('Uploaded Files', 'easycommerce-customer-attachments'),
                    'value' => $attachment['name'],
                    'display' => ''
                );
            endforeach;
        endif;

        return $item_data;
    }
}

$ezyc_customer_attachments = new EasyCommerce_Customer_Attachment();
?>