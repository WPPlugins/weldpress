<?php

add_filter( 'map_meta_cap', 'WELD_PRESS_map_meta_cap', 10, 4 );

function WELD_PRESS_map_meta_cap( $caps, $cap, $user_id, $args ) {
	$meta_caps = array(
		'WELD_PRESS_edit_application' => WELD_PRESS_ADMIN_READ_WRITE_CAPABILITY,
		'WELD_PRESS_edit_applications' => WELD_PRESS_ADMIN_READ_WRITE_CAPABILITY,
		'WELD_PRESS_read_applications' => WELD_PRESS_ADMIN_READ_CAPABILITY,
		'WELD_PRESS_delete_application' => WELD_PRESS_ADMIN_READ_WRITE_CAPABILITY,
		'WELD_PRESS_manage_integration' => 'manage_options' );

	$meta_caps = apply_filters( 'WELD_PRESS_map_meta_cap', $meta_caps );

	$caps = array_diff( $caps, array_keys( $meta_caps ) );

	if ( isset( $meta_caps[$cap] ) ) {
		$caps[] = $meta_caps[$cap];
	}

	return $caps;
}
