<?php

function WELD_PRESS_current_action() {
	if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) {
		return sanitize_text_field( $_REQUEST['action'] );
	}

	if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] ) {
		return sanitize_text_field( $_REQUEST['action2'] );
	}

	return false;
}

function WELD_PRESS_admin_has_edit_cap() {
	return current_user_can( 'WELD_PRESS_edit_applications' );
}

function WELD_PRESS_add_tag_generator( $name, $title, $elm_id, $callback, $options = array() ) {
	$tag_generator = WELD_PRESS_TagGenerator::get_instance();
	return $tag_generator->add( $name, $title, $callback, $options );
}

function WELD_PRESS_save_application( $post_id = -1 ) {
	if ( -1 != $post_id ) {
		$application_form = WELD_PRESS_application( $post_id );
	}

	if ( empty( $application_form ) ) {
		$application_form = WELD_PRESS_Application::get_template();
	}

	if ( isset( $_POST['post_title'] ) ) {
		$application_form->set_title( sanitize_text_field( $_POST['post_title'] ) );
	}

	if ( isset( $_POST['WELDPRESS-locale'] ) ) {
		$locale = trim( sanitize_text_field( $_POST['WELDPRESS-locale'] ) );

		if ( WELD_PRESS_is_valid_locale( $locale ) ) {
			$application_form->locale = $locale;
		}
	}

	$properties = $application_form->get_properties();

	if ( isset( $_POST['WELDPRESS-form'] ) ) {
		$properties['form'] = trim( wp_check_invalid_utf8( $_POST['WELDPRESS-form'], true ) );
	}

	$mail = $properties['mail'];

	if ( isset( $_POST['WELDPRESS-application-body'] ) ) {
	}

	if ( isset( $_POST['WELDPRESS-mail-body'] ) ) {
		$mail['body'] = trim( wp_check_invalid_utf8( $_POST['WELDPRESS-mail-body'] ) );
	}
	
	$properties['mail'] = $mail;


	foreach ( WELD_PRESS_messages() as $key => $arr ) {
		$field_name = 'WELDPRESS-message-' . strtr( $key, '_', '-' );

		if ( isset( $_POST[$field_name] ) ) {
			$properties['messages'][$key] = trim( sanitize_text_field( $_POST[$field_name] ) );
		}
	}

	if ( isset( $_POST['WELDPRESS-additional-settings'] ) ) {
		$properties['additional_settings'] = trim(
				sanitize_text_field( $_POST['WELDPRESS-additional-settings'] ) );
	}

	$application_form->set_properties( $properties );

	do_action( 'WELD_PRESS_save_application', $application_form );

	$post_id = $application_form->save();

	if ( WELD_PRESS_validate_configuration() ) {
		$config_validator = new WELD_PRESS_ConfigValidator( $application_form );
		$config_validator->validate();
	}

	return $post_id;
}
