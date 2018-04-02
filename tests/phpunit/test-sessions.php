<?php

use \Pantheon_Sessions\Session;

class Test_Sessions extends WP_UnitTestCase {

	protected $table_name;
	protected $suppress_errors;
	protected $mock_session_id = 'SESSabc123';

	public function setUp() {
		global $wpdb;
		if ( ! isset( $this->table_name ) ) {
			$this->table_name = $wpdb->pantheon_sessions;
		}
		$wpdb->pantheon_sessions = $this->table_name;
		$this->suppress_errors = $wpdb->suppress_errors();
		parent::setUp();
		ob_start();
		@session_start();
	}

	public function test_session_id() {
		$session_id = session_id();
		$this->assertNotEmpty( $session_id );
	}

	public function test_session_name() {
		$session_name = session_name();
		$this->assertStringStartsWith( "SESS", $session_name );
	}

	public function test_session_write_read() {
		$_SESSION['foo'] = 'bar';
		session_commit();
		$session = \Pantheon_Sessions\Session::get_by_sid( session_id() );
		$data = $session->get_data();
		$this->assertEquals( 'foo|s:3:"bar";', $session->get_data() );
		return $session;
	}

	/**
	 * @expectedException PHPUnit_Framework_Error_Warning
	 */
	public function test_session_write_error() {
		global $wpdb;
		// Set an invalid table to fail queries
		$backup_table = $wpdb->pantheon_sessions;
		$wpdb->pantheon_sessions = 'foobar1235';
		$wpdb->suppress_errors( true );
		$_SESSION['foo'] = 'bar';
		session_commit();
		// Error is triggered.
	}

	/**
	 * @depends test_session_write_read
	 */
	public function test_session_destroy( $session ) {
		$session->destroy();
		$session = \Pantheon_Sessions\Session::get_by_sid( session_id() );
		$this->assertFalse( $session );
		$this->assertEmpty( $_SESSION );
	}

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

	public function test_session_write_serialized_data() {
		$_SESSION['login_status'] = 'logged_out';
		$_SESSION['login_data'] = serialize( array(
			'count' => 1,
			'foo'   => 'bar',
		) );
		session_commit();
		$session = Session::get_by_sid( session_id() );
		$this->assertEquals( 'login_status|s:10:"logged_out";login_data|s:42:"a:2:{s:5:"count";i:1;s:3:"foo";s:3:"bar";}";', $session->get_data() );
		$this->assertEquals( 'logged_out', $_SESSION['login_status'] );
		$this->assertEquals( array(
			'count' => 1,
			'foo'   => 'bar',
		), $_SESSION['login_data'] ) ;
	}

	public function test_session_garbage_collection() {
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

	public function tearDown() {
		global $wpdb;
		ob_get_clean();
		$wpdb->pantheon_sessions = $this->table_name;
		$wpdb->suppress_errors( $this->suppress_errors );
		$results = $wpdb->query( "DELETE FROM {$wpdb->pantheon_sessions}" );
		parent::tearDown();
	}

}
