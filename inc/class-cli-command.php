<?php
/**
 * CLI interface to interact with Pantheon sessions.
 *
 * @package WPNPS
 */

namespace Pantheon_Sessions;

use WP_CLI;

/**
 * Interact with Pantheon Sessions
 */
class CLI_Command extends \WP_CLI_Command {

	/**
	 * List all registered sessions.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, csv, json, count, ids. Default: table
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		global $wpdb;

		if ( ! PANTHEON_SESSIONS_ENABLED ) {
			WP_CLI::error( 'Pantheon Sessions is currently disabled.' );
		}

		$defaults   = [
			'format' => 'table',
			'fields' => 'session_id,user_id,datetime,ip_address,data',
		];
		$assoc_args = array_merge( $defaults, $assoc_args );

		$sessions = [];
		foreach ( new \WP_CLI\Iterators\Query( "SELECT * FROM {$wpdb->pantheon_sessions} ORDER BY datetime DESC" ) as $row ) {
			$sessions[] = $row;
		}

		\WP_CLI\Utils\Format_Items( $assoc_args['format'], $sessions, $assoc_args['fields'] );
	}

	/**
	 * Delete one or more sessions.
	 *
	 * [<session-id>...]
	 * : One or more session IDs
	 *
	 * [--all]
	 * : Delete all sessions.
	 *
	 * @subcommand delete
	 */
	public function delete( $args, $assoc_args ) {
		global $wpdb;

		if ( ! PANTHEON_SESSIONS_ENABLED ) {
			WP_CLI::error( 'Pantheon Sessions is currently disabled.' );
		}

		if ( isset( $assoc_args['all'] ) ) {
			$args = $wpdb->get_col( "SELECT session_id FROM {$wpdb->pantheon_sessions}" );
			if ( empty( $args ) ) {
				WP_CLI::warning( 'No sessions to delete.' );
			}
		}

		foreach ( $args as $session_id ) {
			$session = \Pantheon_Sessions\Session::get_by_sid( $session_id );
			if ( $session ) {
				$session->destroy();
				WP_CLI::log( sprintf( 'Session destroyed: %s', $session_id ) );
			} else {
				WP_CLI::warning( sprintf( "Session doesn't exist: %s", $session_id ) );
			}
		}
	}

	/**
	 * Set id as primary key in the Native PHP Sessions plugin table.
	 *
	 * @subcommand add-index
	 */
	public function add_index( $args, $assoc_arc ) {
		global $wpdb;
		$unprefixed_table = 'pantheon_sessions';
		$table            = $wpdb->base_prefix . $unprefixed_table;
		$temp_clone_table = $wpdb->base_prefix . 'sessions_temp_clone';

		// If the command has been run multiple times and there is already a
		// temp_clone table, drop it.
		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $temp_clone_table ) );

		if ( $wpdb->get_var( $query ) == $temp_clone_table ) {
			$query = "DROP TABLE {$temp_clone_table};";
			$wpdb->query( $query );
		}

		if ( ! PANTHEON_SESSIONS_ENABLED ) {
			WP_CLI::error( 'Pantheon Sessions is currently disabled.' );
		}

		// Verify that the ID column/primary key does not already exist.
		$query         = "SHOW KEYS FROM {$table} WHERE key_name = 'PRIMARY';";
		$key_existence = $wpdb->get_results( $query );

		// Avoid errors by not attempting to add a column that already exists.
		if ( ! empty( $key_existence ) ) {
			WP_CLI::error( __( 'ID column already exists and does not need to be added to the table.', 'wp-native-php-sessions' ) );
		}

		// Alert the user that the action is going to go through.
		WP_CLI::log( __( 'Primary Key does not exist, resolution starting.', 'wp-native-php-sessions' ) );

		$count_query = "SELECT COUNT(*) FROM {$table};";
		$count_total = $wpdb->get_results( $count_query );
		$count_total = $count_total[0]->{'COUNT(*)'};

		if ( $count_total >= 20000 ) {
			WP_CLI::log( __( 'A total of ', 'wp-native-php-sessions' ) . $count_total . __( ' rows exist. To avoid service interruptions, this operation will be run in batches. Any sessions created between now and when operation completes may need to be recreated.', 'wp-native-php-sessions' ) );
		}
		// Create temporary table to copy data into in batches.
		$query = "CREATE TABLE {$temp_clone_table} LIKE {$table};";
		$wpdb->query( $query );
		$query = "ALTER TABLE {$temp_clone_table} ADD COLUMN id BIGINT AUTO_INCREMENT PRIMARY KEY FIRST";
		$wpdb->query( $query );

		$batch_size = 20000;
		$loops      = ceil( $count_total / $batch_size );

		for ( $i = 0; $i < $loops; $i ++ ) {
			$offset = $i * $batch_size;

			$query           = sprintf( "INSERT INTO {$temp_clone_table} 
(user_id, session_id, secure_session_id, ip_address, datetime, data) 
SELECT user_id,session_id,secure_session_id,ip_address,datetime,data 
FROM %s ORDER BY user_id LIMIT %d OFFSET %d", $table, $batch_size, $offset );
			$results         = $wpdb->query( $query );
			$current_results = $results + ( $batch_size * $i );

			WP_CLI::log( __( 'Updated ', 'wp-native-php-sessions' ) . $current_results . ' / ' . $count_total . __( ' rows.', 'wp-native-php-sessions' ) );
		}

		// Hot swap the old table and the new table, deleting a previous old
		// table if necessary.
		$old_table = $wpdb->base_prefix . 'old_' . $unprefixed_table;
		$query     = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $old_table ) );

		if ( $wpdb->get_var( $query ) == $old_table ) {
			$query = "DROP TABLE {$old_table};";
			$wpdb->query( $query );
		}
		$query = "ALTER TABLE {$table} RENAME {$old_table};";
		$wpdb->query( $query );
		$query = "ALTER TABLE {$temp_clone_table} RENAME {$table};";
		$wpdb->query( $query );

		WP_CLI::log( __( 'Operation complete, please verify that your site is working as expected. When ready, run terminus wp {site_name}.{env} pantheon session primary-key-finalize to clean up old data, or run terminus wp {site_name}.{env} pantheon session primary-key-revert if there were issues.', 'wp-native-php-sessions' ) );
	}

	/**
	 * Finalizes the creation of a primary key by deleting the old data.
	 *
	 * @subcommand primary-key-finalize
	 */
	public function primary_key_finalize() {
		global $wpdb;
		$table = $wpdb->base_prefix . 'old_pantheon_sessions';

		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) );

		// Check for table existence and delete if present.
		if ( ! $wpdb->get_var( $query ) == $table ) {
			WP_CLI::error( __( 'Old table does not exist to be removed.', 'wp-native-php-sessions' ) );
		} else {
			$query = "DROP TABLE {$table};";
			$wpdb->query( $query );

			WP_CLI::log( __( 'Old table has been successfully removed, process complete.', 'wp-native-php-sessions' ) );
		}
	}

	/**
	 * Reverts addition of primary key.
	 *
	 * @subcommand primary-key-revert
	 */
	public function primary_key_revert() {
		global $wpdb;
		$old_clone_table  = $wpdb->base_prefix . 'old_pantheon_sessions';
		$temp_clone_table = $wpdb->base_prefix . 'temp_pantheon_sessions';
		$table            = $wpdb->base_prefix . 'pantheon_sessions';

		// If there is no old table to roll back to, error.
		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $old_clone_table ) );

		if ( ! $wpdb->get_var( $query ) == $old_clone_table ) {
			WP_CLI::error( __( 'There is no old table to roll back to.', 'wp-native-php-sessions' ) );
		}

		// Swap old table and new one.
		$query = "ALTER TABLE {$table} RENAME {$temp_clone_table};";
		$wpdb->query( $query );
		$query = "ALTER TABLE {$old_clone_table} RENAME {$table};";
		$wpdb->query( $query );
		WP_CLI::log( __( 'Rolled back to previous state successfully, dropping corrupt table.', 'wp-native-php-sessions' ) );

		// Remove table which did not function.
		$query = "DROP TABLE {$temp_clone_table}";
		$wpdb->query( $query );
		WP_CLI::log( __( 'Process complete.', 'wp-native-php-sessions' ) );
	}
}

\WP_CLI::add_command( 'pantheon session', '\Pantheon_Sessions\CLI_Command' );
