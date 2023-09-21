<?php
/**
 * Tests plugin initialization.
 *
 * @package WPNPS
 */

/**
 * Tests plugin initialization.
 */
class Test_Init_Plugin extends WP_UnitTestCase {

	/**
	 * Ensures the plugin is loaded when the test suite runs.
	 */
	public function test_plugin_loaded() {
		$this->assertTrue( class_exists( 'Pantheon_Sessions' ) );
	}

	/**
	 * Ensures the database is created when the test suite runs.
	 */
	public function test_database_created() {
		global $wpdb, $table_prefix;

		$table_name = "{$table_prefix}pantheon_sessions";
		$this->assertEquals( $table_name, $wpdb->pantheon_sessions );

		// phpcs:ignore
		$column_data = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name}" );
		$columns     = wp_list_pluck( $column_data, 'Field' );
		$this->assertEquals(
			[
				'id',
				'user_id',
				'session_id',
				'secure_session_id',
				'ip_address',
				'datetime',
				'data',
			],
			$columns
		);
	}

	/**
	 * Ensure that the primary key addition command works.
	 */
	public function test_primary_key_addition() {
		global $wpdb, $table_prefix;
		require_once __DIR__ . '/../../inc/class-cli-command.php';

		$table_name = "{$table_prefix}pantheon_sessions";
		$command = new CLI_Command();

		$query = "ALTER TABLE {$table_name} DROP COLUMN id";
		$wpdb->query( $query );

		$column_data = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name}" );
		$columns     = wp_list_pluck( $column_data, 'Field' );
		$this->assertEquals(
			[
				'user_id',
				'session_id',
				'secure_session_id',
				'ip_address',
				'datetime',
				'data',
			],
			$columns
		);

		$command->add_index( '', '' );
		$column_data = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name}" );
		$columns     = wp_list_pluck( $column_data, 'Field' );
		$this->assertEquals(
			[
				'id',
				'user_id',
				'session_id',
				'secure_session_id',
				'ip_address',
				'datetime',
				'data',
			],
			$columns
		);
	}
}
