<?php

namespace Pantheon_Sessions;

class Admin {

	private static $instance;

	private static $capability = 'manage_options';

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Admin;
			self::$instance->setup_actions();
			self::$instance->setup_filters();
		}
		return self::$instance;
	}

	/**
	 * Load admin actions
	 */
	private function setup_actions() {

		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );

	}

	/**
	 * Load admin filters
	 */
	private function setup_filters() {

	}

	/**
	 * Register the admin menu
	 */
	public function action_admin_menu() {

		add_management_page( __( 'Pantheon Sessions', 'pantheon-sessions' ), __( 'Sessions', 'pantheon-sessions' ), self::$capability, 'pantheon-sessions', array( $this, 'handle_page' ) );

	}

	/**
	 * Render the admin page
	 */
	public function handle_page() {

		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		require_once dirname( __FILE__ ) . '/class-list-table.php';

		echo '<div class="wrap">';

		echo '<h2>' . esc_html__( 'Pantheon Sessions', 'pantheon-sessions' ) . '</h2>';

		$wp_list_table = new List_Table;
		$wp_list_table->prepare_items();
		$wp_list_table->display();

		echo '</div>';

	}

}
