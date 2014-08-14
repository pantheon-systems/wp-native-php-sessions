<?php

class Test_Sessions extends WP_UnitTestCase {

	public function test_session_crud() {

		/*
		 * Create
		 */
		@session_start(); // Produces notice: session_start(): Cannot send session cookie - headers already sent by (output started at /tmp/wordpress-tests-lib/includes/bootstrap.php:53)
		$session_id = session_id();
		$this->assertNotEmpty( $session_id );

		/*
		 * Update / Read
		 */
		$_SESSION['foo'] = 'bar';
		session_commit();
		$session = \Pantheon_Sessions\Session::get_by_sid( $session_id );
		$data = $session->get_data();
		$this->assertEquals( 'foo|s:3:"bar";', $session->get_data() );

		/*
		 * Destroy
		 */
		@session_destroy(); // Produces notice: session_destroy(): Trying to destroy uninitialized session
		$session->destroy();
		$session = \Pantheon_Sessions\Session::get_by_sid( $session_id );
		$this->assertFalse( $session );
		$this->assertEmpty( $_SESSION );

	}

	public function test_session_name() {
		$session_name = session_name();
		$this->assertStringStartsWith( "SESS", $session_name );
	}

}
