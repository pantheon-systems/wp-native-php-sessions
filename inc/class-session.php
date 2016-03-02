<?php

namespace Pantheon_Sessions;

class Session {

	private static $sessions = array();
	private static $secure_sessions = array();

	private $sid;
	private $data;

	/**
	 * Get a session based on its ID.
	 *
	 * @param string $sid
	 * @return Session|false
	 */
	public static function get_by_sid( $sid ) {
		global $wpdb;

		$column_name = self::get_session_id_column();
		$session_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->pantheon_sessions} WHERE {$column_name}=%s", $sid ) );
		if ( ! $session_row ) {
			return false;
		}

		return new Session( $session_row->$column_name, $session_row->data );
	}

	/**
	 * Create a database entry for this session
	 *
	 * @param string $sid
	 * @return Session
	 */
	public static function create_for_sid( $sid ) {
		global $wpdb;

		$insert_data = array(
			'session_id'          => $sid,
			);
		if ( is_ssl() ) {
			$insert_data['secure_session_id'] = $sid;
		}
		$wpdb->insert( $wpdb->pantheon_sessions, $insert_data );
		return self::get_by_sid( $sid );
	}

	private function __construct( $sid, $data ) {
		$this->sid = $sid;
		$this->data = $data;
	}

	/**
	 * Get this session's ID
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->sid;
	}

	/**
	 * Get this session's data
	 *
	 * @return mixed
	 */
	public function get_data() {
		return maybe_unserialize( $this->data );
	}

	/**
	 * Set the session's data
	 *
	 * @param mixed $data
	 */
	public function set_data( $data ) {
		global $wpdb;

		if ( $data === $this->get_data() ) {
			return;
		}

		if ( ! isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		}

		$wpdb->update( $wpdb->pantheon_sessions, array(
			'user_id'         => (bool)get_current_user_id(),
			'datetime'        => date( 'Y-m-d H:i:s' ),
			'ip_address'      => preg_replace( '/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR'] ),
			'data'            => maybe_serialize( $data ),
			), array( self::get_session_id_column() => $this->get_id() ) );

		$this->data = maybe_serialize( $data );
	}

	/**
	 * Destroy this session
	 */
	public function destroy() {
		global $wpdb;

		$wpdb->delete( $wpdb->pantheon_sessions, array( self::get_session_id_column() => $this->get_id() ) );

		// Reset $_SESSION to prevent a new session from being started
		$_SESSION = array();

		$this->delete_cookies();

	}
  
	/**
	 * Determines if a session ID exists in the database.
	 *
	 * @param string $sid
	 * @param bool $secure
	 * @return true|false
	 */
	public function sid_exists( $sid, $secure = false ) {
		global $wpdb;
		$column_name = 'session_id';
		if($secure) {
			$column_name = 'secure_session_id';
		}
		$session_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->pantheon_sessions} WHERE {$column_name}=%s", $sid ) );
		if ( ! $session_row ) {
			return false;
		}
		return true;
	}

	/**
	 * Delete session cookies
	 */
	private function delete_cookies() {

		// Cookies don't exist on CLI
		if ( self::is_cli() ) {
			return;
		}

		$session_name = session_name();
		$cookies = array(
			$session_name,
			substr( $session_name, 1 ),
			'S' . $session_name,
			);

		foreach( $cookies as $cookie_name ) {

			if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
				continue;
			}

			$params = session_get_cookie_params();
			setcookie( $cookie_name, '', $_SERVER['REQUEST_TIME'] - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly'] );
			unset( $_COOKIE[ $cookie_name ] );
		}

	}

	/**
	 * Is this request via CLI?
	 *
	 * @return bool
	 */
	private static function is_cli() {
		return 'cli' === PHP_SAPI;
	}

	/**
	 * Get the session ID column name
	 *
	 * @return string
	 */
	private static function get_session_id_column() {
		if ( is_ssl() ) {
			return 'secure_session_id';
		} else {
			return 'session_id';
		}
	}

}
