<?php

add_action( 'WELD_PRESS_init', 'WELD_PRESS_add_form_tag_hidden' );

function WELD_PRESS_add_form_tag_hidden() {
	WELD_PRESS_add_form_tag( 'hidden', 'WELD_PRESS_hidden_form_tag_handler', true );
}

function WELD_PRESS_hidden_form_tag_handler( $tag ) {
	$tag = new WELD_PRESS_FormTag( $tag );

	if ( empty( $tag->name ) ) {
		return '';
	}

	$atts = array();

	$class = WELD_PRESS_form_controls_class( $tag->type );
	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();

	$value = (string) reset( $tag->values );
	$value = $tag->get_default_option( $value );
	$atts['value'] = $value;

	$atts['type'] = 'hidden';
	$atts['name'] = $tag->name;
	$atts = WELD_PRESS_format_atts( $atts );

	$html = sprintf( '<input %s />', $atts );
	return $html;
}
