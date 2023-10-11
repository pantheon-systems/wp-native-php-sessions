<?php

#####################
# This plugin is placed in mu-plugins by the testing scripts. Once in mu-plugins
# it will be loaded on all requests to WordPress and respond to informational
# GET requests. Behat can then sample this output.
#
# INSECURE. DO NOT USE WHERE SECURITY IS A CONCERN.
#####################

add_action( 'template_redirect', function() {
	if ( 'wpnps_get_session' !== $_GET['action'] ) {
		return;
	}
	session_start();
	echo "(" . esc_html($_GET['key']) . ':' . $_SESSION[ esc_html($_GET['key']) ] . ")";
	exit;
});

add_action( 'template_redirect', function() {
	if ( 'wpnps_set_session' !== $_GET['action'] ) {
		return;
	}
	session_start();
	$_SESSION[ $_GET['key'] ] = $_GET['value'];
	echo 'Session updated.';
	exit;
});

add_action( 'template_redirect', function() {
	if ( 'wpnps_delete_session' !== $_GET['action'] ) {
		return;
	}
	session_start();
	unset( $_SESSION[ $_GET['key'] ] );
	echo 'Session deleted.';
	exit;
});

add_action( 'template_redirect', function() {
	if ( 'wpnps_check_table' !== $_GET['action'] ) {
		return;
	}
	global $wpdb;
	$results = $wpdb->get_results( "SELECT user_id,data FROM {$wpdb->pantheon_sessions}" );
	foreach( $results as $result ) {
		echo esc_html($result->user_id) . '-' . esc_html($result->data) . PHP_EOL;
	}
	exit;
});

add_action( 'template_redirect', function() {
	if ( 'wpnps_plugin_loaded' !== $_GET['action'] ) {
		return;
	}
	session_start();
	if ( class_exists( 'Pantheon_Sessions' ) && PANTHEON_SESSIONS_ENABLED ) {
		echo 'Plugin is loaded.';
	} else {
		echo 'Plugin is not loaded.';
	}
	exit;
});
