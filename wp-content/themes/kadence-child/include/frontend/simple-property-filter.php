<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Simple Property Filter for Elementor Loop Widget
 * Targets widget with query ID 6969
 */
class Simple_Property_Filter {

    public function __construct() {
        // Add the filter shortcode
        add_shortcode( 'property_filter', array( $this, 'render_filter' ) );
        
        // Hook into Elementor queries
        add_action( 'elementor/query/6969', array( $this, 'filter_elementor_query' ), 10, 2 );
        
        // Enqueue scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Render the property filter form
     */
    public function render_filter( $atts ) {
        $archive_url = get_post_type_archive_link( 'property' );
        
        ob_start();
        ?>
<div class="property-filter-container">
    <div class="filter-tabs">
        <button
            class="tab-link <?php echo ( isset($_GET['property_for']) && $_GET['property_for'] == 'buy' ) ? 'active' : ''; ?>"
            data-filter-type="buy">Buy</button>
        <button
            class="tab-link <?php echo ( !isset($_GET['property_for']) || $_GET['property_for'] == 'rent' ) ? 'active' : ''; ?>"
            data-filter-type="rent">Rent</button>
    </div>

    <div class="filter-form-wrapper">
        <form id="property-filter-form" action="<?php echo esc_url( $archive_url ); ?>" method="GET">
            <input type="hidden" id="filter-type-input" name="property_for"
                value="<?php echo esc_attr( isset($_GET['property_for']) ? $_GET['property_for'] : 'rent' ); ?>">

            <div class="form-field location-field">
                <label for="filter-location">Location</label>
                <input type="text" id="filter-location" name="location" placeholder="Area name or Location"
                    value="<?php echo esc_attr( isset( $_GET['location'] ) ? $_GET['location'] : '' ); ?>">
            </div>

            <div class="form-field property-type-field">
                <label for="filter-property-type">Types of properties</label>
                <select id="filter-property-type" name="property_type">
                    <option value="">All Property Types</option>
                    <?php
                            $property_types = $this->get_property_types();
                            foreach ( $property_types as $value => $label ) :
                            ?>
                    <option value="<?php echo esc_attr( $value ); ?>"
                        <?php selected( isset( $_GET['property_type'] ) ? $_GET['property_type'] : '', $value ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field submit-field">
                <button type="submit" class="search-button">Search</button>
            </div>
        </form>
    </div>
</div>
<?php
        return ob_get_clean();
    }

    /**
     * Filter the Elementor query for widget ID 6969
     */
    public function filter_elementor_query( $query, $widget ) {
        $meta_query = array( 'relation' => 'AND' );

        // Filter by property_for (rent/buy)
        if ( isset( $_GET['property_for'] ) && ! empty( $_GET['property_for'] ) ) {
            $meta_query[] = array(
                'key' => 'property_for',
                'value' => sanitize_text_field( $_GET['property_for'] ),
                'compare' => 'LIKE'
            );
        }

        // Filter by property_type
        if ( isset( $_GET['property_type'] ) && ! empty( $_GET['property_type'] ) ) {
            $meta_query[] = array(
                'key' => 'property_type',
                'value' => sanitize_text_field( $_GET['property_type'] ),
                'compare' => 'LIKE'
            );
        }

        // Filter by location (search in address fields)
        if ( isset( $_GET['location'] ) && ! empty( $_GET['location'] ) ) {
            $location = sanitize_text_field( $_GET['location'] );
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

        // Apply meta query if we have filters
        if ( count( $meta_query ) > 1 ) {
            $query->set( 'meta_query', $meta_query );
        }
    }

    /**
     * Get property types (you can customize this based on your ACF field)
     */
    private function get_property_types() {
        // Method 1: Try to get field by key/name from ACF field groups
        if ( function_exists( 'acf_get_field' ) ) {
            $field = acf_get_field( 'property_type' );
            if ( $field && isset( $field['choices'] ) && ! empty( $field['choices'] ) ) {
                return $field['choices'];
            }
        }

        // Method 2: Try get_field_object with a sample post
        if ( function_exists( 'get_field_object' ) ) {
            // Get a sample property post to get field structure
            $sample_post = get_posts( array(
                'post_type' => 'property',
                'posts_per_page' => 1,
                'post_status' => 'publish'
            ) );
            
            if ( ! empty( $sample_post ) ) {
                $field = get_field_object( 'property_type', $sample_post[0]->ID );
                if ( $field && isset( $field['choices'] ) && ! empty( $field['choices'] ) ) {
                    return $field['choices'];
                }
            }
        }

        // Method 3: Try to get from field groups directly
        if ( function_exists( 'acf_get_field_groups' ) ) {
            $field_groups = acf_get_field_groups( array( 'post_type' => 'property' ) );
            foreach ( $field_groups as $group ) {
                if ( function_exists( 'acf_get_fields' ) ) {
                    $fields = acf_get_fields( $group );
                    if ( $fields ) {
                        foreach ( $fields as $field ) {
                            if ( $field['name'] === 'property_type' && isset( $field['choices'] ) ) {
                                return $field['choices'];
                            }
                        }
                    }
                }
            }
        }

        // Method 4: Get distinct values from database
        global $wpdb;
        $meta_values = $wpdb->get_results( $wpdb->prepare(
            "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} 
             WHERE meta_key = %s 
             AND meta_value != '' 
             ORDER BY meta_value ASC",
            'property_type'
        ) );

        if ( ! empty( $meta_values ) ) {
            $dynamic_types = array();
            foreach ( $meta_values as $value ) {
                $clean_value = $value->meta_value;
                $dynamic_types[ $clean_value ] = ucwords( str_replace( array( '-', '_' ), ' ', $clean_value ) );
            }
            return $dynamic_types;
        }
        
        // Final fallback options (only if everything else fails)
        return array(
            'residential-vacant-land' => 'Residential vacant land',
            'apartment' => 'Apartment',
            'house' => 'House',
            'office' => 'Office',
            'commercial' => 'Commercial',
            'mixed-use' => 'Mixed-use'
        );
    }

    /**
     * Enqueue necessary scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style( 
            'simple-property-filter-style', 
            get_stylesheet_directory_uri() . '/assets/frontend/css/property-filter.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script( 
            'simple-property-filter-script', 
            get_stylesheet_directory_uri() . '/assets/frontend/js/property-filter.js',
            array( 'jquery' ),
            '1.0.0',
            true 
        );
    }

    /**
     * Debug function to check what property types are being loaded
     * Add ?debug_property_types=1 to your URL to see this
     */
    public function debug_property_types() {
        if ( isset( $_GET['debug_property_types'] ) && current_user_can( 'manage_options' ) ) {
            echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border: 1px solid #ccc;">';
            echo '<h3>Property Types Debug Info:</h3>';
            
            $types = $this->get_property_types();
            echo '<strong>Found Types:</strong><br>';
            echo '<pre>' . print_r( $types, true ) . '</pre>';
            
            // Check if ACF functions exist
            echo '<strong>ACF Functions Available:</strong><br>';
            echo 'acf_get_field: ' . ( function_exists( 'acf_get_field' ) ? 'YES' : 'NO' ) . '<br>';
            echo 'get_field_object: ' . ( function_exists( 'get_field_object' ) ? 'YES' : 'NO' ) . '<br>';
            echo 'acf_get_field_groups: ' . ( function_exists( 'acf_get_field_groups' ) ? 'YES' : 'NO' ) . '<br>';
            
            // Check property posts
            $property_count = wp_count_posts( 'property' );
            echo '<strong>Property Posts:</strong> ' . $property_count->publish . ' published<br>';
            
            echo '</div>';
        }
    }
}

// Initialize the filter
$property_filter = new Simple_Property_Filter();

// Add debug info to wp_footer if needed
add_action( 'wp_footer', array( $property_filter, 'debug_property_types' ) );