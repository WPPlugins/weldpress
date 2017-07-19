<?php
/**
** A base module for [count], Twitter-like character count
**/

/* form_tag handler */

add_action( 'WELD_PRESS_init', 'WELD_PRESS_add_form_tag_count' );

function WELD_PRESS_add_form_tag_count() {
	WELD_PRESS_add_form_tag( 'count', 'WELD_PRESS_count_form_tag_handler', true );
}

function WELD_PRESS_count_form_tag_handler( $tag ) {
	$tag = new WELD_PRESS_FormTag( $tag );

	if ( empty( $tag->name ) ) {
		return '';
	}

	$targets = WELD_PRESS_scan_form_tags( array( 'name' => $tag->name ) );
	$maxlength = $minlength = null;

	while ( $targets ) {
		$target = array_shift( $targets );
		$target = new WELD_PRESS_FormTag( $target );

		if ( 'count' != $target->type ) {
			$maxlength = $target->get_maxlength_option();
			$minlength = $target->get_minlength_option();
			break;
		}
	}

	if ( $maxlength && $minlength && $maxlength < $minlength ) {
		$maxlength = $minlength = null;
	}

	if ( $tag->has_option( 'down' ) ) {
		$value = (int) $maxlength;
		$class = 'WELDPRESS-character-count down';
	} else {
		$value = '0';
		$class = 'WELDPRESS-character-count up';
	}

	$atts = array();
	$atts['id'] = $tag->get_id_option();
	$atts['class'] = $tag->get_class_option( $class );
	$atts['data-target-name'] = $tag->name;
	$atts['data-starting-value'] = $value;
	$atts['data-current-value'] = $value;
	$atts['data-maximum-value'] = $maxlength;
	$atts['data-minimum-value'] = $minlength;
	$atts = WELD_PRESS_format_atts( $atts );

	$html = sprintf( '<span %1$s>%2$s</span>', $atts, $value );

	return $html;
}
