<?php

namespace Pantheon_Sessions;

/**
 * Interact with Pantheon Sessions
 */
class CLI_Command extends \WP_CLI_Command {

	/**
	 * List all registered sessions.
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {

		// @todo get and list all sessions

	}

	/**
	 * Delete one or more sessions.
	 *
	 * [--all]
	 * : Delete all sessions.
	 *
	 * @subcommand delete
	 */
	public function delete( $args, $assoc_args ) {

	}

}

\WP_CLI::add_command( 'pantheon sessions', '\Pantheon_Sessions\CLI_Command' );
