<?php
/**
** A base module for [response]
**/

/* form_tag handler */

add_action( 'WELD_PRESS_init', 'WELD_PRESS_add_form_tag_response' );

function WELD_PRESS_add_form_tag_response() {
	WELD_PRESS_add_form_tag( 'response', 'WELD_PRESS_response_form_tag_handler' );
}

function WELD_PRESS_response_form_tag_handler( $tag ) {
	if ( $contact_form = WELD_PRESS_get_current_contact_form() ) {
		return $contact_form->form_response_output();
	}
}
