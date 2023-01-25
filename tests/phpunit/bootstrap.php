<?php
/**
 * Bootstrap file for the PHPUNit test suite.
 *
 * @package WPNPS
 */

define( 'WPNPS_RUNNING_TESTS', true );

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', __DIR__ . '/../../vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php' );

// Replicates some behavior in 'Pantheon_Sessions' that doesn't fire when headers_sent().
session_name( 'SESS' . substr( hash( 'sha256', 'wordpress-develop.test' ), 0, 32 ) );
ini_set( 'session.use_cookies', '1' );
ini_set( 'session.use_only_cookies', '1' );
ini_set( 'session.use_trans_sid', '0' );
ini_set( 'session.cache_limiter', '' );
ini_set( 'session.cookie_httponly', '1' );
ini_set( 'session.cookie_lifetime', 0 );
require_once dirname( dirname( __DIR__ ) ) . '/inc/class-session-handler.php';
require_once dirname( dirname( __DIR__ ) ) . '/inc/class-session.php';
$session_handler = new Pantheon_Sessions\Session_Handler();
session_set_save_handler( $session_handler, false );
session_start();

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Loads the plugin to be tested.
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/../pantheon-sessions.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

