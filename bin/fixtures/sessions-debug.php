<?php

#####################
# This plugin is placed in mu-plugins by the testing scripts. Once in mu-plugins
# it will be loaded on all requests to WordPress and respond to informational
# GET requests. Behat can then sample this output.
#
# INSECURE. DO NOT USE WHERE SECURITY IS A CONCERN.
#####################

add_action( 'wp_ajax_nopriv_wpnps_get_session', function() {
	session_start();
	echo "(" . $_GET['key'] . ':' . $_SESSION[ $_GET['key'] ] . ")";
	exit;
});

add_action( 'wp_ajax_nopriv_wpnps_set_session', function() {
	session_start();
	$_SESSION[ $_GET['key'] ] = $_GET['value'];
	echo 'Session updated.';
	exit;
});

add_action( 'wp_ajax_nopriv_wpnps_delete_session', function() {
	session_start();
	unset( $_SESSION[ $_GET['key'] ] );
	echo 'Session deleted.';
	exit;
});

add_action( 'wp_ajax_nopriv_wpnps_check_table', function() {
	global $wpdb;
	$results = $wpdb->get_results( "SELECT user_id,data FROM {$wpdb->pantheon_sessions}" );
	foreach( $results as $result ) {
		echo $result->user_id . '-' . $result->data . PHP_EOL;
	}
	exit;
});

add_action( 'wp_ajax_nopriv_wpnps_plugin_loaded', function() {
	session_start();
	if ( class_exists( 'Pantheon_Sessions' ) && PANTHEON_SESSIONS_ENABLED ) {
		echo 'Plugin is loaded.';
	} else {
		echo 'Plugin is not loaded.';
	}
	exit;
});
