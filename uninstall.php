<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

function WELD_PRESS_delete_plugin() {
	global $wpdb;

	delete_option( 'WELDPRESS' );

	$posts = get_posts( array(
		'numberposts' => -1,
		'post_type' => 'WELD_PRESS_application',
		'post_status' => 'any' ) );

	foreach ( $posts as $post ) {
		wp_delete_post( $post->ID, true );
	}

	$wpdb->query( sprintf( "DROP TABLE IF EXISTS %s",
		$wpdb->prefix . 'weld_press_solution' ) );
}

WELD_PRESS_delete_plugin();
