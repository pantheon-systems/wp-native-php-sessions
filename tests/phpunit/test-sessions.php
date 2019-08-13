<?php
/**
 * Tests plugin interactions with sessions.
 *
 * @package WPNPS
 */

use \Pantheon_Sessions\Session;

/**
 * Tests plugin interactions with sessions.
 */
class Test_Sessions extends WP_UnitTestCase {

	/**
	 * Name of the table in the tests.
	 *
	 * @var string
	 */
	protected $table_name;

	/**
	 * State of the $wpdb->suppress_errors attribute.
	 *
	 * @var boolean
	 */
	protected $suppress_errors;

	/**
	 * Name of a mocked session id.
	 *
	 * @var string
	 */
	protected $mock_session_id = 'SESSabc123';

	/**
	 * Sets up the test suite prior to every test.
	 */
	public function setUp() {
		global $wpdb;
		if ( ! isset( $this->table_name ) ) {
			$this->table_name = $wpdb->pantheon_sessions;
		}
		$wpdb->pantheon_sessions = $this->table_name;
		$this->suppress_errors   = $wpdb->suppress_errors();
		if ( ! Session::get_by_sid( session_id() ) ) {
			Session::create_for_sid( session_id() );
		}
		parent::setUp();
	}

	/**
	 * Ensures a session ID is generated and not empty.
	 */
	public function test_session_id() {
		$session_id = session_id();
		$this->assertNotEmpty( $session_id );
	}

	/**
	 * Ensures the session is named correctly.
	 */
	public function test_session_name() {
		$session_name = session_name();
		$this->assertStringStartsWith( 'SESS', $session_name );
	}

	/**
	 * Ensures that a session can be written to and then read from.
	 */
	public function test_session_write_read() {
		$_SESSION['foo'] = 'bar';
		session_commit();
		$session = \Pantheon_Sessions\Session::get_by_sid( session_id() );
		$data    = $session->get_data();
		$this->assertEquals( 'foo|s:3:"bar";', $session->get_data() );
		return $session;
	}

	/**
	 * Ensures a warning is triggered when a session fails to write.
	 */
	public function test_session_write_error() {
		$this->markTestSkipped( 'Fails to trigger warning when entire suite is run.' );

		global $wpdb;
		// Set an invalid table to fail queries.
		$backup_table            = $wpdb->pantheon_sessions;
		$wpdb->pantheon_sessions = 'foobar1235';
		$wpdb->suppress_errors( true );
		$_SESSION['foo'] = 'bar';
		session_commit();
		// Error is triggered.
	}

	/**
	 * Ensures a session is destroyed.
	 *
	 * @depends test_session_write_read
	 *
	 * @param object $session Existing session instance.
	 */
	public function test_session_destroy( $session ) {
		$session->destroy();
		$session = \Pantheon_Sessions\Session::get_by_sid( session_id() );
		$this->assertFalse( $session );
		$this->assertEmpty( $_SESSION );
	}

	/**
	 * Ensures the user ID stays in sync with the session.
	 */
	public function test_session_sync_user_id_login_logout() {
		$_SESSION['foo'] = 'bar';
		session_commit();
		$session = Session::get_by_sid( session_id() );
		$this->assertEquals( 0, $session->get_user_id() );
		// Mock the user logging in.
		do_action( 'set_logged_in_cookie', null, null, null, 1 );
		$session = Session::get_by_sid( session_id() );
		$this->assertEquals( 1, $session->get_user_id() );
		// Mock the user logging out.
		do_action( 'clear_auth_cookie' );
		$session = Session::get_by_sid( session_id() );
		$this->assertEquals( 0, $session->get_user_id() );
	}

	/**
	 * Ensures the garbage collection function wroks as expected.
	 */
	public function test_session_garbage_collection() {
		$this->markTestSkipped( 'ini_set() never works once headers have been set' );

		global $wpdb;
		$_SESSION['foo'] = 'bar';
		session_commit();
		$this->assertEquals( 1, $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->pantheon_sessions" ) );
		$current_val = ini_get( 'session.gc_maxlifetime' );
		ini_set( 'session.gc_maxlifetime', 100000000 );
		_pantheon_session_garbage_collection( ini_get( 'session.gc_maxlifetime' ) );
		$this->assertEquals( 1, $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->pantheon_sessions" ) );
		ini_set( 'session.gc_maxlifetime', 0 );
		_pantheon_session_garbage_collection( ini_get( 'session.gc_maxlifetime' ) );
		$this->assertEquals( 0, $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->pantheon_sessions" ) );
		ini_set( 'session.gc_maxlifetime', $current_val );
	}

	/**
	 * Ensures $wpdb can be restored when missing.
	 */
	public function test_restore_wpdb_when_missing() {
		$this->assertInstanceOf( 'wpdb', Session::restore_wpdb_if_null( null ) );
	}

	/**
	 * Ensures order respected for getting client IP server.
	 */
	public function test_get_client_ip_server() {
		// Default behavior should be localhost.
		$this->assertEquals( '127.0.0.1', Session::get_client_ip_server() );
		// First $_SERVER instance should override.
		$_SERVER['HTTP_CLIENT_IP']   = '192.168.1.2';
		$_SERVER['HTTP_X_FORWARDED'] = '192.168.1.3';
		$this->assertEquals( '192.168.1.2', Session::get_client_ip_server() );
	}

	/**
	 * Runs at the end of every test.
	 */
	public function tearDown() {
		global $wpdb;
		$wpdb->pantheon_sessions = $this->table_name;
		$wpdb->suppress_errors( $this->suppress_errors );
		$results = $wpdb->query( "DELETE FROM {$wpdb->pantheon_sessions}" );
		parent::tearDown();
	}

}
