<?php

class Test_Init_Plugin extends WP_UnitTestCase {

	public function test_plugin_loaded() {
		$this->assertTrue( class_exists( 'Pantheon_Sessions' ) );
	}

	public function test_database_created() {
		global $wpdb, $table_prefix;

		$table_name = "{$table_prefix}pantheon_sessions";
		$this->assertEquals( $table_name, $wpdb->pantheon_sessions );

		$column_data = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name}" );
		$columns = wp_list_pluck( $column_data, 'Field' );
		$this->assertEquals( array(
			'user_id',
			'session_id',
			'secure_session_id',
			'ip_address',
			'datetime',
			'data',
			), $columns );

	}

}

