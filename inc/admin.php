<?php

namespace EasyCommerce\Customer_Attachments;

class Admin {
    private static $instance = null;

    public function upload_dir($upload_dir) {
        if (!is_dir($upload_dir)) :
            mkdir($upload_dir, 0750, true);
        endif;
    }

    public function hooks() {
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_attachment_setting'));
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_customer_notes_setting'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_attachment_setting'));
    }

    public static function get_instance() {
        if (self::$instance === null)
            self::$instance = new static();

        return self::$instance;
    }
    
    public function add_product_customer_notes_setting() {
        woocommerce_wp_checkbox(array(
            'id'            => 'product_has_customer_notes',
            'label'         => __('Customer Notes', 'easycommerce-customer-attachment'),
            'description'   => __('Enable this to allow customers to add notes when adding to the basket', 'easycommerce-customer-attachment')
        ));
    }
    
    public function add_product_attachment_setting() {
        woocommerce_wp_checkbox(array(
            'id'            => 'product_has_attachment',
            'label'         => __('Product Attach Field', 'easycommerce-customer-attachment'),
            'description'   => __('Enable this to allow customers to add files when adding to the basket', 'easycommerce-customer-attachment')
        ));
    }

    public function save_product_attachment_setting($product_id) {
        $product_has_attachment = isset($_POST['product_has_attachment']) ? 'yes' : 'no';
        $product_has_customer_notes = isset($_POST['product_has_customer_notes']) ? 'yes' : 'no';
        update_post_meta($product_id, 'product_has_attachment', $product_has_attachment);
        update_post_meta($product_id, 'product_has_customer_notes', $product_has_customer_notes);
    }
}
?>