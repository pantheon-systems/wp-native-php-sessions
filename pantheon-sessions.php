<?php
/*
Plugin Name: Pantheon Sessions for WordPress
Version: 0.1-alpha
Description: Offload PHP sessions to your database for multi-server compatibility.
Author: Pantheon
Author URI: https://www.getpantheon.com/
Plugin URI: https://www.getpantheon.com/
Text Domain: pantheon-sessions
Domain Path: /languages
*/

class Pantheon_Sessions {

	private static $instance;

	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new Pantheon_Sessions;
			self::$instance->load();
		}

	}

	/**
	 * Load the plugin
	 */
	private function load() {

		$this->require_files();
		$this->setup_actions();

	}

	/**
	 * Load required files
	 */
	private function require_files() {

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once dirname( __FILE__ ) . '/inc/class-cli-command.php';
		}

	}

	/**
	 * Set up plugin actions
	 */
	private function setup_actions() {

	}

}

/**
 * Release the kraken!
 */
function Pantheon_Sessions() {
	return Pantheon_Sessions::get_instance();
}
add_action( 'muplugins_loaded', 'Pantheon_Sessions' );