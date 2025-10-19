<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frontend Property Gallery functionality
 */

/**
 * Enqueue frontend assets for property gallery slider
 */
function enqueue_property_gallery_frontend_assets() {
    // Only enqueue if we're on a page that might have the shortcode or property content
    global $post;
    
    $should_enqueue = false;
    
    // Check if we're on a property single page
    if (is_singular('property')) {
        $should_enqueue = true;
    }
    
    // Check if the current post content contains the shortcode
    if (isset($post->post_content) && has_shortcode($post->post_content, 'property_gallery')) {
        $should_enqueue = true;
    }
    
    // Force enqueue if property gallery shortcode is registered (for widgets, etc.)
    if (has_shortcode($post->post_content ?? '', 'property_gallery') || is_singular('property')) {
        $should_enqueue = true;
    }
    
    if ($should_enqueue) {
        // Enqueue SwiperJS CSS
        wp_enqueue_style(
            'swiper-css',
            'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
            array(),
            '11.0.0'
        );
        
        // Enqueue CSS
        wp_enqueue_style(
            'property-slider-css',
            get_stylesheet_directory_uri() . '/assets/frontend/css/property-slider.css',
            array('swiper-css'),
            filemtime(get_stylesheet_directory() . '/assets/frontend/css/property-slider.css')
        );
        
        // Enqueue SwiperJS JavaScript
        wp_enqueue_script(
            'swiper-js',
            'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
            array(),
            '11.0.0',
            true
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'property-slider-js',
            get_stylesheet_directory_uri() . '/assets/frontend/js/property-slider.js',
            array('jquery', 'swiper-js'),
            filemtime(get_stylesheet_directory() . '/assets/frontend/js/property-slider.js'),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_property_gallery_frontend_assets');

/**
 * Get property gallery images for a given post ID
 *
 * @param int|null $post_id
 * @return array
 */
function get_property_gallery_images($post_id = null) {
    if (!$post_id) {
        global $post;
        $post_id = $post ? $post->ID : 0;
    }
    
    if (!$post_id) {
        return array();
    }
    
    $gallery_images = get_post_meta($post_id, '_property_gallery', true);
    return is_array($gallery_images) ? $gallery_images : array();
}


/**
 * Property Gallery Shortcode
 * Usage: [property_gallery id="123" size="large" height="400" navigation="true" pagination="true" thumbnails="true"]
 *
 * @param array $atts
 * @return string
 */
function property_gallery_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => null,
        'size' => 'large',
        'height' => 400,
        'navigation' => 'true',
        'pagination' => 'true',
        'thumbnails' => 'true',
        'class' => '',
        'thumbnail_size' => 'medium'
    ), $atts, 'property_gallery');
    
    // Get post ID
    $post_id = $atts['id'] ? intval($atts['id']) : get_the_ID();
    
    if (!$post_id) {
        return '<p><em>No property ID specified for gallery.</em></p>';
    }
    
    // Verify it's a property post type
    if (get_post_type($post_id) !== 'property') {
        return '<p><em>The specified post is not a property.</em></p>';
    }
    
    // Get gallery images
    $gallery_images = get_property_gallery_images($post_id);
    
    if (empty($gallery_images)) {
        return '<div class="property-gallery-slider no-images"></div>';
    }
    
    // Set template variables from shortcode attributes
    $show_navigation = $atts['navigation'] === 'true';
    $show_pagination = $atts['pagination'] === 'true';
    $show_thumbnails = $atts['thumbnails'] === 'true';
    $slider_height = intval($atts['height']);
    $image_size = $atts['size'];
    $thumbnail_size = $atts['thumbnail_size'];
    $container_class = 'property-gallery-slider' . ($atts['class'] ? ' ' . esc_attr($atts['class']) : '');
    
    // Start output buffering
    ob_start();
    
    // Include the template
    include get_stylesheet_directory() . '/template/frontend/property-gallery-slider-template.php';
    
    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('property_gallery', 'property_gallery_shortcode');