<?php

define( 'WPNPS_RUNNING_TESTS', true );

// Replicates some behavior in 'Pantheon_Sessions' that doesn't fire when headers_sent().
session_name( 'SESS' . substr( hash( 'sha256', 'wordpress-develop.test' ), 0, 32 ) );
ini_set( 'session.use_cookies', '1' );
ini_set( 'session.use_only_cookies', '1' );
ini_set( 'session.use_trans_sid', '0' );
ini_set( 'session.cache_limiter', '' );
ini_set( 'session.cookie_httponly', '1' );
ini_set( 'session.cookie_lifetime', 0 );
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/callbacks.php';
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/inc/class-session.php';
session_set_save_handler( '_pantheon_session_open', '_pantheon_session_close', '_pantheon_session_read', '_pantheon_session_write', '_pantheon_session_destroy', '_pantheon_session_garbage_collection' );
session_start();

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/../pantheon-sessions.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

