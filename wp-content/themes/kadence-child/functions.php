<?php
/**
 * Enqueue child theme styles
 */
function kadence_child_enqueue_styles() {
    // Load Parent theme css 
    wp_enqueue_style( 'kadence-parent-style', get_template_directory_uri() . '/style.css' );

    // Load Child theme css
    wp_enqueue_style( 'kadence-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('kadence-parent-style')
    );
}
add_action( 'wp_enqueue_scripts', 'kadence_child_enqueue_styles' );

/**
 * Include admin assets handler
 */
require_once get_stylesheet_directory() . '/include/admin/admin-assets-handler.php';

/**
 * Include property post type features
 */
require_once get_stylesheet_directory() . '/include/admin/property-post-type.php';

/**
 * Include Agents post type features
 */
require_once get_stylesheet_directory() . '/include/admin/agents-post-type.php';

/**
 * Include frontend property gallery features
 */
require_once get_stylesheet_directory() . '/include/frontend/property-gallery-frontend.php';

/**
 * Include enhanced ACF integration for Elementor ACF group field support
 */
require_once get_stylesheet_directory() . '/include/elementor/elementor-group-fild-support.php';

/**
 * Include Property Gallery Dynamic Tag for Elementor
 */
require_once get_stylesheet_directory() . '/include/elementor/property-gallery-dynamic-tag.php';

/**
 * Include Property Agents Features
 */
require_once get_stylesheet_directory() . '/include/frontend/property-agents-frontend.php';

/**
 * Include Simple Property Filter for Elementor
 */
require_once get_stylesheet_directory() . '/include/frontend/simple-property-filter.php';

/**
 * Include AJAX Property Sort Widget (Separate from filter)
 */
require_once get_stylesheet_directory() . '/include/frontend/ajax-property-sort.php';




// function inspect_all_meta_boxes() {
//     global $wp_meta_boxes;
    
//     // This prints the array of all meta boxes for the current screen
//     echo '<pre>';
//     print_r( $wp_meta_boxes );
//     echo '</pre>';
// }
// add_action( 'add_meta_boxes', 'inspect_all_meta_boxes', 9999 );