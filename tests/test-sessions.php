<?php

class Test_Sessions extends WP_UnitTestCase {

	protected $mock_session_id = 'SESSabc123';

	public function setUp() {
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

	public function test_session_exists() {
		$_SESSION['foo'] = 'bar';
		session_commit();
		$session_id = session_id();
		$exists = \Pantheon_Sessions\Session::sid_exists( $session_id );
		$this->assertTrue( $exists );
		$session = \Pantheon_Sessions\Session::get_by_sid( $session_id );
		$session->destroy();
		$exists = \Pantheon_Sessions\Session::sid_exists( $session_id );
		$this->assertFalse( $exists );
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
		parent::tearDown();
	}

}
