<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove Kadence settings meta box from 'Agents' post type
 *
 * @return void
 */
function remove_kadence_settings_for_agents() {
    // Replace 'your_cpt_slug' with the actual slug of your custom post type.
    remove_meta_box( '_kad_classic_meta_control', 'agents', 'side' );
}
add_action( 'add_meta_boxes', 'remove_kadence_settings_for_agents', 20 );

/**
 * Remove the content editor for the 'property' post type
 *
 * @return void
 */
function remove_editor_agents() {
    remove_post_type_support( 'agents', 'editor' );
}
add_action( 'admin_menu' , 'remove_editor_agents' );