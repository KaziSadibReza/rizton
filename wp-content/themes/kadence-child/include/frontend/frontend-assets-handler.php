<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Professional Frontend Assets Handler
 * Manages all frontend CSS and JS assets for property widgets
 */
class Rizton_Frontend_Assets_Handler {

	private $version;
	private $theme_uri;

	public function __construct() {
		$this->version = '1.2.2';
		$this->theme_uri = get_stylesheet_directory_uri();
		
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Enqueue all frontend assets
	 */
	public function enqueue_frontend_assets() {
		// Property Filter CSS (existing file)
		wp_enqueue_style(
			'rizton-property-filter-styles',
			$this->theme_uri . '/assets/frontend/css/property-filter.css',
			array(),
			$this->version,
			'all'
		);

		// Property Sort CSS (professional version)
		wp_enqueue_style(
			'rizton-property-sort-styles',
			$this->theme_uri . '/assets/frontend/css/property-sort.css',
			array(),
			$this->version,
			'all'
		);

		// Property Agents CSS (existing file)
		wp_enqueue_style(
			'rizton-property-agents-styles',
			$this->theme_uri . '/assets/frontend/css/property-agents.css',
			array(),
			$this->version,
			'all'
		);

		// Property Slider CSS (only load when needed)
		// Note: This will be loaded conditionally by the gallery frontend handler

		// Property Filter JS (existing file)
		wp_enqueue_script(
			'rizton-property-filter-scripts',
			$this->theme_uri . '/assets/frontend/js/property-filter.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// Property Sort JS (professional version)
		wp_enqueue_script(
			'rizton-property-sort-scripts',
			$this->theme_uri . '/assets/frontend/js/property-sort.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// Property Slider JS (only load when needed)
		// Note: This will be loaded conditionally by the gallery frontend handler
	}
}

// Initialize the assets handler
new Rizton_Frontend_Assets_Handler();