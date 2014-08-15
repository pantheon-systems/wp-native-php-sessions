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

	/**
	 * @depends test_session_write_read
	 */
	public function test_session_destroy( $session ) {
		$session->destroy();
		$session = \Pantheon_Sessions\Session::get_by_sid( session_id() );
		$this->assertFalse( $session );
		$this->assertEmpty( $_SESSION );
	}

	public function tearDown() {
		parent::tearDown();
	}

}
