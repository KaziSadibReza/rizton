<?php
/**
 * Add a custom Elementor dynamic tag for ACF Group Sub Fields.
 * @var mixed $dynamic_tags_manager
 */
add_action( 'elementor/dynamic_tags/register', function( $dynamic_tags_manager ) {

	if ( ! class_exists( 'ACF' ) ) {
		return;
	}

	class Elementor_Dynamic_Tag_ACF_Group_Field extends \Elementor\Core\DynamicTags\Tag {

		// The unique name of the tag
		public function get_name() {
			return 'acf-group-field';
		}

		// The title that will appear in the dynamic tag list
		public function get_title() {
			return esc_html__( 'ACF Group Field', 'elementor-pro' );
		}

		// The group this tag belongs to
		public function get_group() {
			return 'acf';
		}

		// The categories this tag belongs to
		public function get_categories() {
			return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY, \Elementor\Modules\DynamicTags\Module::URL_CATEGORY, \Elementor\Modules\DynamicTags\Module::POST_META_CATEGORY ];
		}
        
        // This is where you define the controls for your tag
		protected function register_controls() {
			$this->add_control(
				'key',
				[
					'label' => esc_html__( 'Key', 'elementor-pro' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options' => $this->get_acf_group_sub_fields(),
				]
			);
		}

        // This is the function that outputs the data on the frontend
		public function render() {
			$key = $this->get_settings( 'key' );

			if ( empty( $key ) ) {
				return;
			}
            
            // The key is combined, e.g., "parent_group_name|sub_field_name". We need to split it.
			list( $parent_field_key, $sub_field_key ) = explode( '|', $key );

			if ( empty( $parent_field_key ) || empty( $sub_field_key ) ) {
				return;
			}
            
            // Get the entire group field value, which is an array
			$group_field_data = get_field( $parent_field_key );
            
            // Check if the group has data and the specific sub-field exists
			if ( is_array( $group_field_data ) && isset( $group_field_data[ $sub_field_key ] ) ) {
				$value = $group_field_data[ $sub_field_key ];
				echo wp_kses_post( $value );
			}
		}


		/**
		 * Helper function to get all ACF group fields and their sub-fields.
		 * This populates the dropdown in the editor.
		 *
		 * @return array
		 */
		private function get_acf_group_sub_fields() {
			$options = [ '' => esc_html__( 'Select...', 'elementor-pro' ) ];

			if ( ! function_exists( 'acf_get_field_groups' ) ) {
				return $options;
			}
            
            // Get all field groups
			$field_groups = acf_get_field_groups();

			if ( empty( $field_groups ) ) {
				return $options;
			}
            
            // Loop through each group to find its fields
			foreach ( $field_groups as $field_group ) {
				$fields = acf_get_fields( $field_group['key'] );

				if ( ! empty( $fields ) ) {
                    // Loop through the fields within the group
					foreach ( $fields as $field ) {
                        // Check if the field is a 'group' type
						if ( 'group' === $field['type'] ) {
							$parent_field_label = $field['label'];
							$parent_field_name = $field['name'];

							// If it's a group, loop through its sub-fields
							if ( ! empty( $field['sub_fields'] ) ) {
								foreach ( $field['sub_fields'] as $sub_field ) {
                                    // Create a combined key and a user-friendly label
									$option_key = $parent_field_name . '|' . $sub_field['name'];
									$option_label = $parent_field_label . ': ' . $sub_field['label'];
									$options[ $option_key ] = $option_label;
								}
							}
						}
					}
				}
			}

			return $options;
		}

	}

	// Register the new tag
	$dynamic_tags_manager->register( new Elementor_Dynamic_Tag_ACF_Group_Field() );

} );