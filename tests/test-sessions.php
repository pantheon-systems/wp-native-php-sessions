<?php

class Test_Sessions extends WP_UnitTestCase {

	public function test_session_crud() {

		/*
		 * Create
		 */
		@session_start();
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
		@session_destroy();
		$session->destroy();
		$session = \Pantheon_Sessions\Session::get_by_sid( $session_id );
		$this->assertFalse( $session );
		$this->assertEmpty( $_SESSION );

	}

}
