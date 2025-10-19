<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove Kadence settings meta box from 'property' post type
 *
 * @return void
 */
function remove_kadence_settings_for_property() {
    // Replace 'your_cpt_slug' with the actual slug of your custom post type.
    remove_meta_box( '_kad_classic_meta_control', 'property', 'side' );
}
add_action( 'add_meta_boxes', 'remove_kadence_settings_for_property', 20 );

/**
 * Remove the content editor for the 'property' post type
 *
 * @return void
 */
function remove_editor() {
    remove_post_type_support( 'property', 'editor' );
}
add_action( 'admin_menu' , 'remove_editor' );

/**
 * Remove default Featured Image box
 * @return void
 */
add_action('do_meta_boxes', function() {
    remove_meta_box('postimagediv', 'property', 'side');
});

/**
 * Include custom field for property post type
 */
include_once get_stylesheet_directory() . '/include/admin/custom-field/property-post-gallery.php';  