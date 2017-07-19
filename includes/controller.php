<?php

add_action( 'wp_loaded', 'WELD_PRESS_control_init' );

function WELD_PRESS_control_init() {
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}

	if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
		if ( isset( $_GET['_WELD_PRESS_is_ajax_call'] ) ) {
			WELD_PRESS_ajax_onload();
		}
	}

	if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
		if ( isset( $_POST['_WELD_PRESS_is_ajax_call'] ) ) {
			WELD_PRESS_ajax_json_echo();
		}

		WELD_PRESS_submit_nonajax();
	}
}

function WELD_PRESS_ajax_onload() {
	$echo = '';
	$items = array();

	if ( isset( $_GET['_WELDPRESS'] )
	&& $contact_form = WELD_PRESS_application( (int) $_GET['_WELDPRESS'] ) ) {
		$items = apply_filters( 'WELD_PRESS_ajax_onload', $items );
	}

	$echo = wp_json_encode( $items );

	if ( WELD_PRESS_is_xhr() ) {
		@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		echo $echo;
	}

	exit();
}

function WELD_PRESS_ajax_json_echo() {
	$echo = '';

	if ( isset( $_POST['_WELDPRESS'] ) ) {
		$id = (int) $_POST['_WELDPRESS'];
		$unit_tag = WELD_PRESS_sanitize_unit_tag( $_POST['_WELD_PRESS_unit_tag'] );

		if ( $contact_form = WELD_PRESS_application( $id ) ) {
			$items = array(
				'mailSent' => false,
				'into' => '#' . $unit_tag,
				'captcha' => null );

			$result = $contact_form->submit( true );

			if ( ! empty( $result['message'] ) ) {
				$items['message'] = $result['message'];
			}

			if ( 'mail_sent' == $result['status'] ) {
				$items['mailSent'] = true;
			}

			if ( 'validation_failed' == $result['status'] ) {
				$invalids = array();

				foreach ( $result['invalid_fields'] as $name => $field ) {
					$invalids[] = array(
						'into' => 'span.WELDPRESS-form-control-wrap.'
							. sanitize_html_class( $name ),
						'message' => $field['reason'],
						'idref' => $field['idref'] );
				}

				$items['invalids'] = $invalids;
			}

			if ( 'spam' == $result['status'] ) {
				$items['spam'] = true;
			}

			if ( ! empty( $result['scripts_on_sent_ok'] ) ) {
				$items['onSentOk'] = $result['scripts_on_sent_ok'];
			}

			if ( ! empty( $result['scripts_on_submit'] ) ) {
				$items['onSubmit'] = $result['scripts_on_submit'];
			}

			$items = apply_filters( 'WELD_PRESS_ajax_json_echo', $items, $result );
		}
	}

	$echo = wp_json_encode( $items );

	if ( WELD_PRESS_is_xhr() ) {
		@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		echo $echo;
	} else {
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		echo '<textarea>' . $echo . '</textarea>';
	}

	exit();
}

function WELD_PRESS_is_xhr() {
	if ( ! isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) )
		return false;

	return $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
}

function WELD_PRESS_submit_nonajax() {
	if ( ! isset( $_POST['_WELDPRESS'] ) )
		return;

	if ( $contact_form = WELD_PRESS_application( (int) $_POST['_WELDPRESS'] ) ) {
		$contact_form->submit();
	}
}

add_filter( 'widget_text', 'WELD_PRESS_widget_text_filter', 9 );

function WELD_PRESS_widget_text_filter( $content ) {
	if ( ! preg_match( '/\[[\r\n\t ]*contact-form(-7)?[\r\n\t ].*?\]/', $content ) )
		return $content;

	$content = do_shortcode( $content );

	return $content;
}

add_action( 'wp_enqueue_scripts', 'WELD_PRESS_do_enqueue_scripts' );

function WELD_PRESS_do_enqueue_scripts() {
	if ( WELD_PRESS_load_js() ) {
		WELD_PRESS_enqueue_scripts();
	}

	if ( WELD_PRESS_load_css() ) {
		WELD_PRESS_enqueue_styles();
	}
}

function WELD_PRESS_enqueue_scripts() {
	// jquery.form.js originally bundled with WordPress is out of date and deprecated
	// so we need to deregister it and re-register the latest one
	wp_deregister_script( 'jquery-form' );
	wp_register_script( 'jquery-form',
		WELD_PRESS_plugin_url( 'includes/js/jquery.form.min.js' ),
		array( 'jquery' ), '3.51.0-2014.06.20', true );

	$in_footer = true;

	if ( 'header' === WELD_PRESS_load_js() ) {
		$in_footer = false;
	}

	wp_enqueue_script( 'weld-press',
		WELD_PRESS_plugin_url( 'includes/js/scripts.js' ),
		array( 'jquery', 'jquery-form' ), WELDPRESS_VERSION, $in_footer );

	$_WELDPRESS = array(
		'recaptcha' => array(
			'messages' => array(
				'empty' => __( 'Please verify that you are not a robot.',
					'weld-press' ) ) ) );

	if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
		$_WELDPRESS['cached'] = 1;
	}

	if ( WELD_PRESS_support_html5_fallback() ) {
		$_WELDPRESS['jqueryUi'] = 1;
	}

	wp_localize_script( 'weld-press', '_WELDPRESS', $_WELDPRESS );

	do_action( 'WELD_PRESS_enqueue_scripts' );
}

function WELD_PRESS_script_is() {
	return wp_script_is( 'weld-press' );
}

function WELD_PRESS_enqueue_styles() {
	wp_enqueue_style( 'weld-press',
		WELD_PRESS_plugin_url( 'includes/css/styles.css' ),
		array(), WELDPRESS_VERSION, 'all' );

	if ( WELD_PRESS_is_rtl() ) {
		wp_enqueue_style( 'weld-press-rtl',
			WELD_PRESS_plugin_url( 'includes/css/styles-rtl.css' ),
			array(), WELDPRESS_VERSION, 'all' );
	}

	do_action( 'WELD_PRESS_enqueue_styles' );
}

function WELD_PRESS_style_is() {
	return wp_style_is( 'weld-press' );
}

/* HTML5 Fallback */

add_action( 'wp_enqueue_scripts', 'WELD_PRESS_html5_fallback', 20 );

function WELD_PRESS_html5_fallback() {
	if ( ! WELD_PRESS_support_html5_fallback() ) {
		return;
	}

	if ( WELD_PRESS_script_is() ) {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-ui-spinner' );
	}

	if ( WELD_PRESS_style_is() ) {
		wp_enqueue_style( 'jquery-ui-smoothness',
			WELD_PRESS_plugin_url( 'includes/js/jquery-ui/themes/smoothness/jquery-ui.min.css' ), array(), '1.10.3', 'screen' );
	}
}
