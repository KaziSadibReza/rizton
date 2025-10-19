<?php
/**
 * Elementor Dynamic Tag for Property Gallery First Image
 * This file handles custom meta field integration with Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Register Property Gallery Dynamic Tag with Elementor
 */
add_action( 'elementor/dynamic_tags/register', function( $dynamic_tags_manager ) {

    /**
     * Property Gallery First Image Dynamic Tag
     */
    class Elementor_Property_Gallery_First_Image extends \Elementor\Core\DynamicTags\Data_Tag {

        public function get_name() {
            return 'property-gallery-first-image';
        }

        public function get_title() {
            return esc_html__( 'Property Gallery First Image', 'kadence-child' );
        }

        public function get_group() {
            return 'post';
        }

        public function get_categories() {
            return [ \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY ];
        }

        protected function register_controls() {
            $this->add_control(
                'image_size',
                [
                    'label' => esc_html__( 'Image Size', 'kadence-child' ),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'default' => 'full',
                    'options' => $this->get_image_sizes(),
                ]
            );

            $this->add_control(
                'fallback_text',
                [
                    'label' => esc_html__( 'Fallback Text', 'kadence-child' ),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'placeholder' => esc_html__( 'No image available', 'kadence-child' ),
                    'condition' => [
                        '_skin' => '',
                    ],
                ]
            );
        }

        public function render() {
            // For Data_Tag, we don't need to echo JSON, just return the data
            return;
        }

        public function get_value( array $options = [] ) {
            return $this->get_image_data();
        }

        private function get_image_data() {
            $settings = $this->get_settings();
            $image_size = isset( $settings['image_size'] ) ? $settings['image_size'] : 'full';

            $post_id = get_the_ID();
            $gallery_images = get_post_meta( $post_id, '_property_gallery', true );

            if ( ! empty( $gallery_images ) && is_array( $gallery_images ) ) {
                $first_image_id = intval( $gallery_images[0] );
                
                if ( $first_image_id > 0 ) {
                    $image_src = wp_get_attachment_image_src( $first_image_id, $image_size );
                    $image_url = wp_get_attachment_image_url( $first_image_id, $image_size );
                    
                    if ( $image_src && $image_url ) {
                        return [
                            'id' => $first_image_id,
                            'url' => $image_url,
                            'width' => isset( $image_src[1] ) ? $image_src[1] : 0,
                            'height' => isset( $image_src[2] ) ? $image_src[2] : 0,
                        ];
                    }
                }
            }

            return [];
        }

        private function get_image_sizes() {
            $image_sizes = get_intermediate_image_sizes();
            $options = [];

            $options['full'] = esc_html__( 'Full', 'kadence-child' );

            foreach ( $image_sizes as $size ) {
                $options[ $size ] = ucwords( str_replace( [ '_', '-' ], ' ', $size ) );
            }

            return $options;
        }
    }

    // Register the property gallery dynamic tag
    $dynamic_tags_manager->register( new Elementor_Property_Gallery_First_Image() );

}, 10, 1 );

/**
 * Additional Dynamic Tag for Property Gallery URL (for background images, etc.)
 */
add_action( 'elementor/dynamic_tags/register', function( $dynamic_tags_manager ) {

    class Elementor_Property_Gallery_URL extends \Elementor\Core\DynamicTags\Tag {

        public function get_name() {
            return 'property-gallery-url';
        }

        public function get_title() {
            return esc_html__( 'Property Gallery First Image URL', 'kadence-child' );
        }

        public function get_group() {
            return 'post';
        }

        public function get_categories() {
            return [ \Elementor\Modules\DynamicTags\Module::URL_CATEGORY ];
        }

        protected function register_controls() {
            $this->add_control(
                'image_size',
                [
                    'label' => esc_html__( 'Image Size', 'kadence-child' ),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'default' => 'full',
                    'options' => $this->get_image_sizes(),
                ]
            );
        }

        public function render() {
            $settings = $this->get_settings();
            $image_size = isset( $settings['image_size'] ) ? $settings['image_size'] : 'full';

            $gallery_images = get_post_meta( get_the_ID(), '_property_gallery', true );

            if ( ! empty( $gallery_images ) && is_array( $gallery_images ) ) {
                $first_image_id = intval( $gallery_images[0] );
                
                if ( $first_image_id > 0 ) {
                    $image_url = wp_get_attachment_image_url( $first_image_id, $image_size );
                    if ( $image_url ) {
                        echo esc_url( $image_url );
                    }
                }
            }
        }

        private function get_image_sizes() {
            $image_sizes = get_intermediate_image_sizes();
            $options = [];

            $options['full'] = esc_html__( 'Full', 'kadence-child' );

            foreach ( $image_sizes as $size ) {
                $options[ $size ] = ucwords( str_replace( [ '_', '-' ], ' ', $size ) );
            }

            return $options;
        }
    }

    // Register the URL dynamic tag
    $dynamic_tags_manager->register( new Elementor_Property_Gallery_URL() );

}, 10, 1 );