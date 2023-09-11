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
				WP_CLI::warning( sprintf( "Session doesn't exist: %s",
					$session_id ) );
			}
		}
	}

	/**
	 * Set id as primary key in the Native PHP Sessions plugin table.
	 *
	 * [<session-id>...]
	 * : One or more session IDs
	 *
	 * [--all]
	 * : Delete all sessions.
	 *
	 * @subcommand add-index
	 */

	public function add_index( $args, $assoc_arc ) {
		global $wpdb;

		if ( ! PANTHEON_SESSIONS_ENABLED ) {
			WP_CLI::error( 'Pantheon Sessions is currently disabled.' );
		}

		// Verify that the ID column/primary key does not already exist.
		$query         = "SHOW KEYS FROM wp_pantheon_sessions WHERE key_name = 'PRIMARY';";
		$key_existence = $wpdb->get_results( $query );

		if ( ! empty( $key_existence ) ) {
			WP_CLI::error( 'ID column already exists and does not need to be
			added to the table.' );
		}

		// If there isn't an ID column, add one.
		$query   = "ALTER TABLE wp_pantheon_sessions ADD COLUMN id BIGINT AUTO_INCREMENT PRIMARY KEY FIRST";
		$results = $wpdb->query( $query );

		WP_CLI::log( sprintf( 'Primary key added, total rows affected: %s',
			$results ) );

	}

}

\WP_CLI::add_command( 'pantheon session', '\Pantheon_Sessions\CLI_Command' );
