<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Enqueue admin scripts and styles for property post type
 */
function enqueue_property_admin_assets($hook) {
    global $post_type;
    
    // Only load on property post type edit screens
    if (($hook == 'post-new.php' || $hook == 'post.php') && $post_type == 'property') {
        // Enqueue CSS
        wp_enqueue_style(
            'property-gallery-admin-css',
            get_stylesheet_directory_uri() . '/assets/admin/css/property-gallery.css',
            array(),
            filemtime(get_stylesheet_directory() . '/assets/admin/css/property-gallery.css')
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'property-gallery-admin-js',
            get_stylesheet_directory_uri() . '/assets/admin/js/property-gallery.js',
            array('jquery', 'media-upload', 'thickbox'),
            filemtime(get_stylesheet_directory() . '/assets/admin/js/property-gallery.js'),
            true
        );
        
        // Enqueue WordPress media scripts
        wp_enqueue_media();
        
        // Localize script with AJAX URL and nonces
        wp_localize_script('property-gallery-admin-js', 'propertyGallery', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('property_gallery_nonce')
        ));
    }
}
add_action('admin_enqueue_scripts', 'enqueue_property_admin_assets');