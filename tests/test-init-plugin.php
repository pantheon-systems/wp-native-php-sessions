<?php

class Test_Init_Plugin extends WP_UnitTestCase {

	public function test_plugin_loaded() {
		$this->assertTrue( class_exists( 'Pantheon_Sessions' ) );
	}

	public function test_database_created() {

	}

}

