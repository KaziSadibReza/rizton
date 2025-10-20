<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX Property Sort Widget
 * Simply modifies Elementor query without custom templates
 */
class AJAX_Property_Sort_Widget {

    public function __construct() {
        // Add the sort widget shortcode
        add_shortcode( 'property_sort', array( $this, 'render_sort_widget' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_property_sort_ajax', array( $this, 'handle_ajax_sort' ) );
        add_action( 'wp_ajax_nopriv_property_sort_ajax', array( $this, 'handle_ajax_sort' ) );
        
        // Enqueue scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Render the sort widget
     */
    public function render_sort_widget( $atts ) {
        $atts = shortcode_atts( array(
            'target_container' => '.elementor-loop-container',
            'query_id' => '6969'
        ), $atts, 'property_sort' );

        ob_start();
        ?>
        <div class="ajax-property-sort-widget" 
             data-target="<?php echo esc_attr( $atts['target_container'] ); ?>" 
             data-query-id="<?php echo esc_attr( $atts['query_id'] ); ?>">
             
            <div class="sort-widget-wrapper">
                <div class="sort-control-group">
                    <label for="ajax-sort-dropdown" class="sort-label">Sort by</label>
                    <select id="ajax-sort-dropdown" class="sort-dropdown">
                        <?php foreach ( $this->get_sort_options() as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>">
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="sort-loading-indicator" style="display: none;">
                    <div class="spinner"></div>
                    <span>Sorting...</span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle AJAX sort request
     */
    public function handle_ajax_sort() {
        // Security check
        if ( ! wp_verify_nonce( $_POST['nonce'], 'property_sort_nonce' ) ) {
            wp_die( 'Security check failed' );
        }

        $sort_type = sanitize_text_field( $_POST['sort_type'] ?? '' );
        $query_id = sanitize_text_field( $_POST['query_id'] ?? '6969' );

        // Get current URL filters to maintain them
        $url_filters = array();
        if ( isset( $_POST['url_filters'] ) ) {
            $url_filters = json_decode( stripslashes( $_POST['url_filters'] ), true );
        }

        // Build query args
        $query_args = array(
            'post_type' => 'property',
            'post_status' => 'publish',
            'posts_per_page' => -1, // Get all posts first, then we can add pagination later
            'paged' => 1,
        );

        // Apply existing filters from URL
        $meta_query = array( 'relation' => 'AND' );
        
        if ( ! empty( $url_filters['property_for'] ) ) {
            $meta_query[] = array(
                'key' => 'property_for',
                'value' => sanitize_text_field( $url_filters['property_for'] ),
                'compare' => 'LIKE'
            );
        }

        if ( ! empty( $url_filters['property_type'] ) ) {
            $meta_query[] = array(
                'key' => 'property_type',
                'value' => sanitize_text_field( $url_filters['property_type'] ),
                'compare' => 'LIKE'
            );
        }

        if ( ! empty( $url_filters['location'] ) ) {
            $location = sanitize_text_field( $url_filters['location'] );
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key' => 'what_is_the_street_address_street_address_',
                    'value' => $location,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'what_is_the_street_address_city',
                    'value' => $location,
                    'compare' => 'LIKE'
                )
            );
        }

        if ( count( $meta_query ) > 1 ) {
            $query_args['meta_query'] = $meta_query;
        }

        // Apply sorting with correct ACF field names
        $this->apply_sort_to_query_args( $query_args, $sort_type );

        // Execute query
        $query = new WP_Query( $query_args );

        // Custom post-processing for land size sorting if needed
        if ( strpos( $sort_type, 'land_size' ) !== false && $query->have_posts() ) {
            $posts = $query->posts;
            $size_field = $this->get_existing_meta_key( array( 'land_size_sq_feet', 'property_size', 'land_size', 'size', 'area' ) );
            
            if ( $size_field === 'land_size_sq_feet' ) {
                // Custom sorting for "5x7 m²" format
                $sort_direction = ( $sort_type === 'land_size_low_high' ) ? 1 : -1;
                
                usort( $posts, function( $a, $b ) use ( $size_field, $sort_direction ) {
                    $size_a = get_field( $size_field, $a->ID );
                    $size_b = get_field( $size_field, $b->ID );
                    
                    $area_a = $this->extract_land_size_area( $size_a );
                    $area_b = $this->extract_land_size_area( $size_b );
                    
                    // Handle cases where area calculation fails
                    if ( $area_a == 0 && $area_b == 0 ) {
                        return 0;
                    } elseif ( $area_a == 0 ) {
                        return 1; // Put zero values at the end
                    } elseif ( $area_b == 0 ) {
                        return -1; // Put zero values at the end
                    }
                    
                    return ( $area_a <=> $area_b ) * $sort_direction;
                });
                
                // Update the query posts
                $query->posts = $posts;
                $query->post_count = count( $posts );
            }
        }

        // Prepare response
        $response = array(
            'success' => true,
            'data' => array(
                'html' => '',
                'found_posts' => $query->found_posts,
                'debug' => array(
                    'sort_type' => $sort_type,
                    'query_args' => $query_args,
                    'post_count' => $query->post_count
                )
            )
        );

        // Generate HTML content
        if ( $query->have_posts() ) {
            ob_start();
            while ( $query->have_posts() ) {
                $query->the_post();
                
                // Use simple fallback template for now to ensure it works
                echo '<div class="elementor-post elementor-grid-item">';
                $this->render_simple_property_item();
                echo '</div>';
            }
            wp_reset_postdata();
            $response['data']['html'] = ob_get_clean();
        } else {
            // Add debug info when no posts found
            $response['data']['debug']['message'] = 'No posts found with current query';
        }

        wp_send_json( $response );
    }

    /**
     * Apply sorting to query arguments
     */
    private function apply_sort_to_query_args( &$query_args, $sort_type ) {
        // Let's try to detect the actual ACF field names dynamically
        $price_field_names = array( 'price', 'property_price', 'listing_price', 'sale_price', 'rent_price' );
        $size_field_names = array( 'land_size_sq_feet', 'property_size', 'land_size', 'size', 'area', 'land_area', 'square_footage' );
        
        switch ( $sort_type ) {
            case 'date_published':
            case 'date_oldest':
                $query_args['orderby'] = 'date';
                $query_args['order'] = ( $sort_type === 'date_oldest' ) ? 'ASC' : 'DESC';
                break;
                
            case 'price_low_high':
                $price_field = $this->get_existing_meta_key( $price_field_names );
                if ( $price_field ) {
                    $query_args['orderby'] = 'meta_value_num';
                    $query_args['meta_key'] = $price_field;
                    $query_args['order'] = 'ASC';
                } else {
                    // Fallback to date if no price field found
                    $query_args['orderby'] = 'date';
                    $query_args['order'] = 'DESC';
                }
                break;
                
            case 'price_high_low':
                $price_field = $this->get_existing_meta_key( $price_field_names );
                if ( $price_field ) {
                    $query_args['orderby'] = 'meta_value_num';
                    $query_args['meta_key'] = $price_field;
                    $query_args['order'] = 'DESC';
                } else {
                    // Fallback to date if no price field found
                    $query_args['orderby'] = 'date';
                    $query_args['order'] = 'DESC';
                }
                break;
                
            case 'suburb_a_z':
                $query_args['orderby'] = 'meta_value';
                $query_args['meta_key'] = 'what_is_the_street_address_city';
                $query_args['order'] = 'ASC';
                break;
                
            case 'suburb_z_a':
                $query_args['orderby'] = 'meta_value';
                $query_args['meta_key'] = 'what_is_the_street_address_city';
                $query_args['order'] = 'DESC';
                break;
                
            case 'property_type_a_z':
                $query_args['orderby'] = 'meta_value';
                $query_args['meta_key'] = 'property_type';
                $query_args['order'] = 'ASC';
                break;
                
            case 'property_type_z_a':
                $query_args['orderby'] = 'meta_value';
                $query_args['meta_key'] = 'property_type';
                $query_args['order'] = 'DESC';
                break;
                
            case 'land_size_low_high':
                $size_field = $this->get_existing_meta_key( $size_field_names );
                if ( $size_field ) {
                    // For land_size_sq_feet with format like "5x7 m²", we need custom sorting
                    if ( $size_field === 'land_size_sq_feet' ) {
                        $query_args['orderby'] = 'meta_value';
                        $query_args['meta_key'] = $size_field;
                        $query_args['order'] = 'ASC';
                        
                        // Add custom meta query to ensure we only get posts with this field
                        if ( ! isset( $query_args['meta_query'] ) ) {
                            $query_args['meta_query'] = array();
                        }
                        $query_args['meta_query'][] = array(
                            'key' => $size_field,
                            'compare' => 'EXISTS'
                        );
                    } else {
                        $query_args['orderby'] = 'meta_value_num';
                        $query_args['meta_key'] = $size_field;
                        $query_args['order'] = 'ASC';
                    }
                }
                break;
                
            case 'land_size_high_low':
                $size_field = $this->get_existing_meta_key( $size_field_names );
                if ( $size_field ) {
                    // For land_size_sq_feet with format like "5x7 m²", we need custom sorting
                    if ( $size_field === 'land_size_sq_feet' ) {
                        $query_args['orderby'] = 'meta_value';
                        $query_args['meta_key'] = $size_field;
                        $query_args['order'] = 'DESC';
                        
                        // Add custom meta query to ensure we only get posts with this field
                        if ( ! isset( $query_args['meta_query'] ) ) {
                            $query_args['meta_query'] = array();
                        }
                        $query_args['meta_query'][] = array(
                            'key' => $size_field,
                            'compare' => 'EXISTS'
                        );
                    } else {
                        $query_args['orderby'] = 'meta_value_num';
                        $query_args['meta_key'] = $size_field;
                        $query_args['order'] = 'DESC';
                    }
                }
                break;
                
            default:
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                break;
        }
    }

    /**
     * Find which meta key actually exists in the database
     */
    private function get_existing_meta_key( $possible_keys ) {
        global $wpdb;
        
        foreach ( $possible_keys as $key ) {
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
                 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
                 WHERE pm.meta_key = %s 
                 AND p.post_type = 'property' 
                 AND pm.meta_value != '' 
                 LIMIT 1",
                $key
            ) );
            
            if ( $exists > 0 ) {
                return $key;
            }
        }
        
        return false;
    }

    /**
     * Extract numeric value from land size string like "5x7 m²"
     */
    private function extract_land_size_area( $land_size_value ) {
        if ( empty( $land_size_value ) ) {
            return 0;
        }
        
        // Remove m², sq ft, etc. and extract dimensions
        $cleaned = preg_replace( '/[^\d\.\sx]/i', '', $land_size_value );
        $cleaned = trim( $cleaned );
        
        // Check if it's in format like "5x7" or "5 x 7"
        if ( preg_match( '/(\d+(?:\.\d+)?)\s*x\s*(\d+(?:\.\d+)?)/i', $cleaned, $matches ) ) {
            $width = floatval( $matches[1] );
            $height = floatval( $matches[2] );
            return $width * $height; // Calculate area
        }
        
        // If it's just a single number, return it
        if ( preg_match( '/(\d+(?:\.\d+)?)/', $cleaned, $matches ) ) {
            return floatval( $matches[1] );
        }
        
        return 0;
    }

    /**
     * Simple property item fallback
     */
    private function render_simple_property_item() {
        ?>
        <div class="property-item">
            <?php if ( has_post_thumbnail() ) : ?>
                <div class="property-thumbnail">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail( 'medium' ); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="property-content">
                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                
                <?php 
                // Try different possible price field names
                $price = get_field( 'price' ) ?: get_field( 'property_price' ) ?: get_field( 'listing_price' );
                if ( $price ) :
                ?>
                    <div class="property-price">$<?php echo number_format( $price ); ?></div>
                <?php endif; ?>
                
                <?php 
                $city = get_field( 'what_is_the_street_address_city' ) ?: get_field( 'city' ) ?: get_field( 'location' );
                if ( $city ) :
                ?>
                    <div class="property-location"><?php echo esc_html( $city ); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Get sort options
     */
    private function get_sort_options() {
        return array(
            '' => 'Select Sort Option',
            'date_published' => 'Date Published (Newest)',
            'date_oldest' => 'Date Published (Oldest)',
            'price_low_high' => 'Price (Low-High)',
            'price_high_low' => 'Price (High-Low)',
            'suburb_a_z' => 'Suburb (A-Z)',
            'suburb_z_a' => 'Suburb (Z-A)',
            'property_type_a_z' => 'Property Type (A-Z)',
            'property_type_z_a' => 'Property Type (Z-A)',
            'land_size_low_high' => 'Land Size (Low-High)',
            'land_size_high_low' => 'Land Size (High-Low)'
        );
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        wp_enqueue_style( 
            'ajax-property-sort-style', 
            get_stylesheet_directory_uri() . '/assets/frontend/css/ajax-property-sort.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script( 
            'ajax-property-sort-script', 
            get_stylesheet_directory_uri() . '/assets/frontend/js/ajax-property-sort.js',
            array( 'jquery' ),
            '1.0.0',
            true 
        );

        // Localize for AJAX
        wp_localize_script( 'ajax-property-sort-script', 'ajaxPropertySort', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'property_sort_nonce' ),
            'loading_text' => 'Loading...',
            'error_text' => 'Error occurred. Please try again.'
        ) );
    }
}

// Initialize the widget
new AJAX_Property_Sort_Widget();