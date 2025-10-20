<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Property Sort Handler
 * Professional property sorting functionality for Elementor loop widgets
 * 
 * @package Property_Sort
 * @version 1.0.0
 */
class Property_Sort_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode( 'property_sort', array( $this, 'render_sort_widget' ) );
        add_action( 'elementor/query/6969', array( $this, 'modify_elementor_query' ), 10, 2 );
        
        // AJAX handlers
        add_action( 'wp_ajax_ajax_property_sort', array( $this, 'ajax_property_sort' ) );
        add_action( 'wp_ajax_nopriv_ajax_property_sort', array( $this, 'ajax_property_sort' ) );
        
        // Backward compatibility
        add_shortcode( 'property_sort_widget', array( $this, 'render_sort_widget' ) );
    }

    /**
     * Render the sort dropdown widget
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_sort_widget( $atts ) {
        // Localize script with AJAX data (script is already enqueued by assets handler)
        wp_localize_script( 'rizton-property-sort-scripts', 'ajax_object', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'property_sort_nonce' )
        ) );
        
        $current_sort = isset( $_GET['sort_by'] ) ? sanitize_text_field( $_GET['sort_by'] ) : '';
        
        ob_start();
        ?>
<div class="property-sort-widget">
    <label for="property-sort-select">Sort by</label>
    <select id="property-sort-select" class="property-sort-select" name="sort_by">
        <option value="">Default Property</option>
        <option value="date_new" <?php selected( $current_sort, 'date_new' ); ?>>Date (Newest)</option>
        <option value="date_old" <?php selected( $current_sort, 'date_old' ); ?>>Date (Oldest)</option>
        <option value="price_low" <?php selected( $current_sort, 'price_low' ); ?>>Price (Low-High)</option>
        <option value="price_high" <?php selected( $current_sort, 'price_high' ); ?>>Price (High-Low)</option>
        <option value="suburb_az" <?php selected( $current_sort, 'suburb_az' ); ?>>Suburb (A-Z)</option>
        <option value="suburb_za" <?php selected( $current_sort, 'suburb_za' ); ?>>Suburb (Z-A)</option>
        <option value="type_az" <?php selected( $current_sort, 'type_az' ); ?>>Property Type (A-Z)</option>
        <option value="type_za" <?php selected( $current_sort, 'type_za' ); ?>>Property Type (Z-A)</option>
        <option value="size_low" <?php selected( $current_sort, 'size_low' ); ?>>Land Size (Low-High)</option>
        <option value="size_high" <?php selected( $current_sort, 'size_high' ); ?>>Land Size (High-Low)</option>
    </select>
</div>
<?php
        return ob_get_clean();
    }

    /**
     * Modify Elementor query based on sort parameter
     * 
     * @param WP_Query $query The query object
     * @param object $widget The Elementor widget instance
     */
    public function modify_elementor_query( $query, $widget ) {
        $sort_by = isset( $_GET['sort_by'] ) ? sanitize_text_field( $_GET['sort_by'] ) : '';
        
        if ( empty( $sort_by ) ) {
            return;
        }

        switch ( $sort_by ) {
            case 'date_new':
                $query->set( 'orderby', 'date' );
                $query->set( 'order', 'DESC' );
                break;
                
            case 'date_old':
                $query->set( 'orderby', 'date' );
                $query->set( 'order', 'ASC' );
                break;
                
            case 'price_low':
                $query->set( 'orderby', 'meta_value_num' );
                $query->set( 'meta_key', 'lease_details_deposit' );
                $query->set( 'order', 'ASC' );
                break;
                
            case 'price_high':
                $query->set( 'orderby', 'meta_value_num' );
                $query->set( 'meta_key', 'lease_details_deposit' );
                $query->set( 'order', 'DESC' );
                break;
                
            case 'suburb_az':
                $query->set( 'orderby', 'meta_value' );
                $query->set( 'meta_key', 'what_is_the_street_address_city' );
                $query->set( 'order', 'ASC' );
                break;
                
            case 'suburb_za':
                $query->set( 'orderby', 'meta_value' );
                $query->set( 'meta_key', 'what_is_the_street_address_city' );
                $query->set( 'order', 'DESC' );
                break;
                
            case 'type_az':
                $query->set( 'orderby', 'meta_value' );
                $query->set( 'meta_key', 'property_type' );
                $query->set( 'order', 'ASC' );
                break;
                
            case 'type_za':
                $query->set( 'orderby', 'meta_value' );
                $query->set( 'meta_key', 'property_type' );
                $query->set( 'order', 'DESC' );
                break;
                
            case 'size_low':
                $this->sort_by_land_size( $query, 'ASC' );
                break;
                
            case 'size_high':
                $this->sort_by_land_size( $query, 'DESC' );
                break;
        }
    }

    /**
     * Sort by land size - handles "5x7 m²" format
     * 
     * @param WP_Query $query The query object
     * @param string $order Sort order (ASC/DESC)
     */
    private function sort_by_land_size( $query, $order ) {
        $query->set( 'meta_query', array(
            array(
                'key' => 'property_details_land_size_sq_feet',
                'compare' => 'EXISTS'
            )
        ) );
        
        $query->set( 'orderby', 'meta_value' );
        $query->set( 'meta_key', 'property_details_land_size_sq_feet' );
        $query->set( 'order', $order );
        
        add_filter( 'posts_orderby', array( $this, 'custom_land_size_orderby' ), 10, 2 );
    }

    /**
     * Custom orderby for land size to handle "5x7 m²" format
     * 
     * @param string $orderby The ORDER BY clause
     * @param WP_Query $query The query object
     * @return string Modified ORDER BY clause
     */
    public function custom_land_size_orderby( $orderby, $query ) {
        global $wpdb;
        
        if ( isset( $_GET['sort_by'] ) && ( $_GET['sort_by'] === 'size_low' || $_GET['sort_by'] === 'size_high' ) ) {
            $order = $query->get( 'order' );
            
            $orderby = "
                CASE 
                    WHEN {$wpdb->postmeta}.meta_value REGEXP '^[0-9]+\\.?[0-9]*\\s*x\\s*[0-9]+\\.?[0-9]*'
                    THEN (
                        CAST(SUBSTRING_INDEX({$wpdb->postmeta}.meta_value, 'x', 1) AS DECIMAL(10,2)) * 
                        CAST(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX({$wpdb->postmeta}.meta_value, 'x', -1), ' ', 1)) AS DECIMAL(10,2))
                    )
                    WHEN {$wpdb->postmeta}.meta_value REGEXP '^[0-9]+\\.?[0-9]*'
                    THEN CAST({$wpdb->postmeta}.meta_value AS DECIMAL(10,2))
                    ELSE 0
                END {$order}
            ";
            
            remove_filter( 'posts_orderby', array( $this, 'custom_land_size_orderby' ), 10 );
        }
        
        return $orderby;
    }

    /**
     * AJAX handler for property sorting
     */
    public function ajax_property_sort() {
        // Debug logging
        error_log('AJAX property sort called');
        error_log('POST data: ' . print_r($_POST, true));
        
        // Verify nonce for security
        if ( ! wp_verify_nonce( $_POST['nonce'], 'property_sort_nonce' ) ) {
            error_log('Nonce verification failed');
            wp_send_json_error( 'Security check failed' );
            return;
        }

        error_log('Nonce verification passed');

        $sort_by = isset( $_POST['sort_by'] ) ? sanitize_text_field( $_POST['sort_by'] ) : '';
        $paged = isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1;

        // Get existing URL parameters to preserve filters
        $preserved_params = array();
        if ( isset( $_POST['current_params'] ) ) {
            parse_str( $_POST['current_params'], $preserved_params );
        }

        // Build query args
        $args = array(
            'post_type' => 'property',
            'post_status' => 'publish',
            'posts_per_page' => 12, // Adjust as needed
            'paged' => $paged,
        );

        // Apply preserved filters (like property_for, location, etc.)
        $meta_query = array( 'relation' => 'AND' );
        
        if ( isset( $preserved_params['property_for'] ) && ! empty( $preserved_params['property_for'] ) ) {
            $meta_query[] = array(
                'key' => 'property_for',
                'value' => sanitize_text_field( $preserved_params['property_for'] ),
                'compare' => 'LIKE'
            );
        }

        if ( isset( $preserved_params['property_type'] ) && ! empty( $preserved_params['property_type'] ) ) {
            $meta_query[] = array(
                'key' => 'property_type',
                'value' => sanitize_text_field( $preserved_params['property_type'] ),
                'compare' => 'LIKE'
            );
        }

        if ( isset( $preserved_params['location'] ) && ! empty( $preserved_params['location'] ) ) {
            $location = sanitize_text_field( $preserved_params['location'] );
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
                ),
                array(
                    'key' => 'what_is_the_street_address_region',
                    'value' => $location,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'what_is_the_street_address_country_',
                    'value' => $location,
                    'compare' => 'LIKE'
                )
            );
        }

        if ( count( $meta_query ) > 1 ) {
            $args['meta_query'] = $meta_query;
        }

        // Apply sorting
        if ( ! empty( $sort_by ) ) {
            switch ( $sort_by ) {
                case 'date_new':
                    $args['orderby'] = 'date';
                    $args['order'] = 'DESC';
                    break;
                    
                case 'date_old':
                    $args['orderby'] = 'date';
                    $args['order'] = 'ASC';
                    break;
                    
                case 'price_low':
                    $args['orderby'] = 'meta_value_num';
                    $args['meta_key'] = 'lease_details_deposit';
                    $args['order'] = 'ASC';
                    break;
                    
                case 'price_high':
                    $args['orderby'] = 'meta_value_num';
                    $args['meta_key'] = 'lease_details_deposit';
                    $args['order'] = 'DESC';
                    break;
                    
                case 'suburb_az':
                    $args['orderby'] = 'meta_value';
                    $args['meta_key'] = 'what_is_the_street_address_city';
                    $args['order'] = 'ASC';
                    break;
                    
                case 'suburb_za':
                    $args['orderby'] = 'meta_value';
                    $args['meta_key'] = 'what_is_the_street_address_city';
                    $args['order'] = 'DESC';
                    break;
                    
                case 'type_az':
                    $args['orderby'] = 'meta_value';
                    $args['meta_key'] = 'property_type';
                    $args['order'] = 'ASC';
                    break;
                    
                case 'type_za':
                    $args['orderby'] = 'meta_value';
                    $args['meta_key'] = 'property_type';
                    $args['order'] = 'DESC';
                    break;
                    
                case 'size_low':
                case 'size_high':
                    // Handle land size sorting with custom orderby
                    $args['meta_query'][] = array(
                        'key' => 'property_details_land_size_sq_feet',
                        'compare' => 'EXISTS'
                    );
                    $args['orderby'] = 'meta_value';
                    $args['meta_key'] = 'property_details_land_size_sq_feet';
                    $args['order'] = ( $sort_by === 'size_low' ) ? 'ASC' : 'DESC';
                    break;
            }
        }

        // For Elementor loop widgets, we need to work with the widget directly
        // Return success and let JavaScript handle the widget refresh
        $response = array(
            'success' => true,
            'sort_by' => $sort_by,
            'message' => 'Sort parameters validated'
        );

        error_log('AJAX response: ' . print_r($response, true));
        wp_send_json( $response );
    }

}

// Initialize the Property Sort Handler
new Property_Sort_Handler();