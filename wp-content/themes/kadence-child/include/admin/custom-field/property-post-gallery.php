<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add custom image gallery meta box to 'property' post type
 *
 * @return void
 */
function add_property_gallery_meta_box() {
    add_meta_box(
        'property_gallery',
        'Property Gallery',
        'property_gallery_meta_box_callback',
        'property',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'add_property_gallery_meta_box');


/**
 * Callback function to render the property gallery meta box
 *
 * @param [type] $post
 * @return void
 */
function property_gallery_meta_box_callback($post) {
    wp_nonce_field('property_gallery_nonce', 'property_gallery_nonce');
    
    $gallery_images = get_post_meta($post->ID, '_property_gallery', true);
    if (!is_array($gallery_images)) {
        $gallery_images = array();
    }
    ob_start();
    require_once get_stylesheet_directory() . '/template/admin/property-gallery-meta-box-template.php';
    echo ob_get_clean();
}

/**
 * Save the property gallery meta box data
 *
 * @param [type] $post_id
 * @return void
 */
function save_property_gallery_meta($post_id) {
    if (!isset($_POST['property_gallery_nonce']) || !wp_verify_nonce($_POST['property_gallery_nonce'], 'property_gallery_nonce')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    $gallery_ids = isset($_POST['property_gallery']) ? $_POST['property_gallery'] : '';
    
    if (!empty($gallery_ids)) {
        $gallery_array = array_map('intval', explode(',', $gallery_ids));
        $gallery_array = array_filter($gallery_array); // Remove empty values
        update_post_meta($post_id, '_property_gallery', $gallery_array);
    } else {
        delete_post_meta($post_id, '_property_gallery');
    }
}
add_action('save_post', 'save_property_gallery_meta');