<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue frontend scripts and styles for property features
 */
function enqueue_property_frontend_assets() {
    // Enqueue property slider CSS and JS
    wp_enqueue_style(
        'property-slider-css',
        get_stylesheet_directory_uri() . '/assets/frontend/css/property-slider.css',
        array(),
        filemtime(get_stylesheet_directory() . '/assets/frontend/css/property-slider.css')
    );
    
    wp_enqueue_script(
        'property-slider-js',
        get_stylesheet_directory_uri() . '/assets/frontend/js/property-slider.js',
        array('jquery'),
        filemtime(get_stylesheet_directory() . '/assets/frontend/js/property-slider.js'),
        true
    );
    
    // Enqueue property agents CSS
    wp_enqueue_style(
        'property-agents-css',
        get_stylesheet_directory_uri() . '/assets/frontend/css/property-agents.css',
        array(),
        filemtime(get_stylesheet_directory() . '/assets/frontend/css/property-agents.css')
    );
}
add_action('wp_enqueue_scripts', 'enqueue_property_frontend_assets');
/**
 * Property agents functionality 
 *
 * @return void
 */

function display_related_agent_shortcode() {
    // Get the current post (the 'Property') ID
    $property_id = get_the_ID();

    // Get the array of related agent post objects from your ACF Relationship field.
    $related_agents = get_field('agents', $property_id);

    // Check if any agents were selected
    if ( $related_agents ) {
        // Start output buffering to capture the HTML
        ob_start();

        // Include the template
        include get_stylesheet_directory() . '/template/frontend/property-agents-template.php';


        return ob_get_clean();
    }
    // Return the captured HTML
    // If no agent is found, return nothing.
    return '';
}

// Register the shortcode so you can use [related_agent_details] in your posts
add_shortcode( 'related_agent_details', 'display_related_agent_shortcode' );