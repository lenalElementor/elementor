<?php
namespace Elementor\Core\Breakpoints;

use Elementor\Core\Base\Module;
use Elementor\Core\Kits\Documents\Tabs\Settings_Layout;
use Elementor\Core\Responsive\Files\Frontend;
use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Manager extends Module {

	// The mobile and tablet keys are not semantic due to the need for backwards compatibility.
	const BREAKPOINT_OPTION_PREFIX = 'viewport_';
	const BREAKPOINT_KEY_MOBILE = 'md';
	const BREAKPOINT_KEY_MOBILE_EXTRA = 'mobile_extra';
	const BREAKPOINT_KEY_TABLET = 'lg';
	const BREAKPOINT_KEY_TABLET_EXTRA = 'tablet_extra';
	const BREAKPOINT_KEY_LAPTOP = 'laptop';
	const BREAKPOINT_KEY_WIDESCREEN = 'widescreen';

	private $config;
	private $breakpoints;
	private $breakpoint_keys;

	public function get_name() {
		return 'breakpoints';
	}

	/**
	 * Get Breakpoints
	 *
	 * Retrieve the array of --enabled-- breakpoints, or a single breakpoint if a name is passed.
	 *
	 * @since 3.2.0
	 *
	 * @param $breakpoint_name
	 * @return mixed
	 */
	public function get_breakpoints( $breakpoint_name = null ) {
		if ( ! $this->breakpoints ) {
			$this->breakpoints = $this->init_breakpoints();
		}
		return self::get_items( $this->breakpoints, $breakpoint_name );
	}

	/**
	 * Init Breakpoints
	 *
	 * Creates the breakpoints array, containing instances of each breakpoint. Returns an array of ALL breakpoints,
	 * both enabled and disabled.
	 *
	 * @return array
	 */
	private function init_breakpoints() {
		$breakpoints = [];
		$active_breakpoint_keys = Plugin::$instance->kits_manager->get_current_settings( Settings_Layout::BREAKPOINTS_SELECT_CONTROL_ID );
		$default_config = self::get_default_config();

		foreach ( $default_config as $name => $config ) {
			$args = [ 'name' => $name ] + $config;

			// Make sure each breakpoint knows whether it is enabled.
			// TODO: Adjust this if/else block once the Select2 control is implemented in Site Settings.
			// For Backwards Compatibility, enable the two default breakpoints (mobile, tablet).
			if ( ! $active_breakpoint_keys && ( self::BREAKPOINT_KEY_MOBILE === $name || self::BREAKPOINT_KEY_TABLET === $name ) ) {
				// Make sure the default Mobile and Tablet breakpoints are always enabled.
				$args['is_enabled'] = true;
			} elseif ( $active_breakpoint_keys ) {
				$args['is_enabled'] = in_array( $name, $active_breakpoint_keys['options'], true );
			} else {
				// TODO: Adjust this if/else statement once the Select2 control is implemented in Site Settings.
				$args['is_enabled'] = false;
			}

			$breakpoints[ $name ] = new Breakpoint( $args );
		}

		return $breakpoints;
	}

	/**
	 * Get Config
	 *
	 * Retrieve --Enabled-- Breakpoints Config
	 *
	 * @since 3.2.0
	 *
	 * @param string|null $breakpoint_name
	 * @return array All breakpoints config, or a specific breakpoint's config.
	 */
	public function get_config( $breakpoint_name = null ) {
		if ( ! $this->config ) {
			$this->config = $this->init_config();
		}

		return self::get_items( $this->config, $breakpoint_name );
	}

	/**
	 * Init Config
	 *
	 * Iterate over the breakpoint instances to create the breakpoints config array.
	 *
	 * @since 3.2.0
	 *
	 * @return array $config
	 */
	private function init_config() {
		$config = [];

		// Make sure breakpoint instances are initialized before fetching their config.
		$breakpoints = $this->get_breakpoints();

		// Iterate over the breakpoint instances and get each one's config.
		foreach ( $breakpoints as $name => $instance ) {
			/** @var Breakpoint $instance */
			$breakpoint_config = $instance->get_config();

			// Only add breakpoints to the config array if they are enabled.
			if ( $breakpoint_config['is_enabled'] ) {
				$config[ $name ] = $breakpoint_config;
			}
		}

		return $config;
	}

	/** Has Custom Breakpoints
	 *
	 * Checks whether there are currently custom breakpoints saved in the database.
	 * Returns true if there are breakpoint values saved in the active kit.
	 *
	 * @since 3.2.0
	 *
	 * @return boolean
	 */
	public function has_custom_breakpoints() {
		$breakpoints = $this->get_breakpoints();

		$has_custom_breakpoints = false;

		foreach ( $breakpoints as $breakpoint ) {
			/** @var Breakpoint $breakpoint */
			if ( $breakpoint->is_custom() ) {
				$has_custom_breakpoints = true;
				break;
			}
		}

		return $has_custom_breakpoints;
	}

	/**
	 * Get Device Min Breakpoint
	 *
	 * For a given device, return the minimum possible breakpoint. Except for the cases of mobile and widescreen
	 * devices, A device's min breakpoint is determined by the previous device's max breakpoint + 1px.
	 *
	 * @since 3.2.0
	 * @param string $device_name
	 * @return int the min breakpoint of the passed device
	 */
	public function get_device_min_breakpoint( $device_name ) {
		$breakpoints = $this->get_breakpoints();
		/** @var Breakpoint $current_device_breakpoint */
		$current_device_breakpoint = $breakpoints[ $device_name ];

		if ( ! $this->active_breakpoint_keys ) {
			// Since this method is called multiple times, usage of class variables is to memory and processing time.
			$this->active_breakpoint_keys = array_keys( $breakpoints );
		}

		if ( $this->active_breakpoint_keys[0] === $device_name ) {
			// For the lowest breakpoint, the min point is always 0.
			$min_breakpoint = 0;
		} elseif ( 'min' === $current_device_breakpoint->get_direction() ) {
			// 'min-width' breakpoints only have a minimum point. The breakpoint value itself the device min point.
			$min_breakpoint = $current_device_breakpoint->get_value();
		} else {
			// This block handles all other devices.
			$device_name_index = array_search( $device_name, $this->active_breakpoint_keys, true );

			$previous_index = $device_name_index - 1;
			$previous_breakpoint_key = $this->active_breakpoint_keys[ $previous_index ];
			/** @var Breakpoint $previous_breakpoint */
			$previous_breakpoint = $breakpoints[ $previous_breakpoint_key ];

			$min_breakpoint = $previous_breakpoint->get_value() + 1;
		}

		return $min_breakpoint;
	}

	/**
	 * Get Stylesheet Templates Path
	 *
	 * @since 3.2.0
	 * @access public
	 * @static
	 */
	public static function get_stylesheet_templates_path() {
		return ELEMENTOR_ASSETS_PATH . 'css/templates/';
	}

	/**
	 * Compile Stylesheet Templates
	 *
	 * @since 3.2.0
	 * @access public
	 * @static
	 */
	public static function compile_stylesheet_templates() {
		foreach ( self::get_stylesheet_templates() as $file_name => $template_path ) {
			$file = new Frontend( $file_name, $template_path );

			$file->update();
		}
	}

	/**
	 * Get Stylesheet Templates
	 *
	 * @since 3.2.0
	 * @access private
	 * @static
	 */
	private static function get_stylesheet_templates() {
		$templates_paths = glob( self::get_stylesheet_templates_path() . '*.css' );

		$templates = [];

		foreach ( $templates_paths as $template_path ) {
			$file_name = 'custom-' . basename( $template_path );

			$templates[ $file_name ] = $template_path;
		}

		$deprecated_hook = 'elementor/core/responsive/get_stylesheet_templates';
		$replacement_hook = 'elementor/core/breakpoints/get_stylesheet_template';

		Plugin::$instance->modules_manager->get_modules( 'dev-tools' )->deprecation->deprecated_hook( $deprecated_hook, '3.2.0', $replacement_hook );

		// TODO: REMOVE THIS DEPRECATED HOOK IN ELEMENTOR v3.10.0/v4.0.0
		$templates = apply_filters( $deprecated_hook, $templates );

		return apply_filters( $replacement_hook, $templates );
	}

	/**
	 * Get Default Config
	 *
	 * Retrieve the default breakpoints config array. The 'selector' property is used for CSS generation (the
	 * Stylesheet::add_device() method).
	 *
	 * @return array
	 */
	public static function get_default_config() {
		return [
			self::BREAKPOINT_KEY_MOBILE => [
				'label' => 'Mobile',
				'default_value' => 767,
				'direction' => 'max',
				'selector' => 'mobile',
			],
			self::BREAKPOINT_KEY_MOBILE_EXTRA => [
				'label' => 'Mobile Extra',
				'default_value' => 880,
				'direction' => 'max',
				'selector' => 'mobile_extra',
			],
			self::BREAKPOINT_KEY_TABLET => [
				'label' => 'Tablet',
				'default_value' => 1024,
				'direction' => 'max',
				'selector' => 'tablet',
			],
			self::BREAKPOINT_KEY_TABLET_EXTRA => [
				'label' => 'Tablet Extra',
				'default_value' => 1366,
				'direction' => 'max',
				'selector' => 'tablet_extra',
			],
			self::BREAKPOINT_KEY_LAPTOP => [
				'label' => 'Laptop',
				'default_value' => 1620,
				'direction' => 'max',
				'selector' => 'laptop',
			],
			self::BREAKPOINT_KEY_WIDESCREEN => [
				'label' => 'Widescreen',
				'default_value' => 2400,
				'direction' => 'min',
				'selector' => 'widescreen',
			],
		];
	}
}
