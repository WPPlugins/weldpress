<?php

require_once WELD_PRESS_PLUGIN_DIR . '/includes/functions.php';
require_once WELD_PRESS_PLUGIN_DIR . '/includes/l10n.php';
require_once WELD_PRESS_PLUGIN_DIR . '/includes/formatting.php';
require_once WELD_PRESS_PLUGIN_DIR . '/includes/pipe.php';
require_once WELD_PRESS_PLUGIN_DIR . '/includes/form-tag.php';
require_once WELD_PRESS_PLUGIN_DIR . '/includes/form-tags-manager.php';
require_once WELD_PRESS_PLUGIN_DIR . '/includes/shortcodes.php';
require_once WELD_PRESS_PLUGIN_DIR . '/includes/capabilities.php';
require_once WELD_PRESS_PLUGIN_DIR . '/includes/contact-form-template.php';
require_once WELD_PRESS_PLUGIN_DIR . '/includes/contact-form.php';
require_once WELD_PRESS_PLUGIN_DIR . '/includes/mail.php';
require_once WELD_PRESS_PLUGIN_DIR . '/includes/submission.php';
require_once WELD_PRESS_PLUGIN_DIR . '/includes/upgrade.php';
require_once WELD_PRESS_PLUGIN_DIR . '/includes/integration.php';
require_once WELD_PRESS_PLUGIN_DIR . '/includes/config-validator.php';

if ( is_admin() ) {
	require_once WELD_PRESS_PLUGIN_DIR . '/admin/admin.php';
} else {
	require_once WELD_PRESS_PLUGIN_DIR . '/includes/controller.php';
}

class WELDPRESS {

	public static function load_modules() {
		self::load_module( 'acceptance' );
		self::load_module( 'akismet' );
		self::load_module( 'checkbox' );
		self::load_module( 'count' );
		self::load_module( 'date' );
		self::load_module( 'file' );
		self::load_module( 'flamingo' );
		self::load_module( 'listo' );
		self::load_module( 'number' );
		self::load_module( 'quiz' );
		self::load_module( 'really-simple-captcha' );
		self::load_module( 'recaptcha' );
		self::load_module( 'response' );
		self::load_module( 'select' );
		self::load_module( 'submit' );
		self::load_module( 'text' );
		self::load_module( 'textarea' );
		self::load_module( 'hidden' );		
	}

	protected static function load_module( $mod ) {
		$dir = WELD_PRESS_PLUGIN_MODULES_DIR;

		if ( empty( $dir ) || ! is_dir( $dir ) ) {
			return false;
		}

		$file = path_join( $dir, $mod . '.php' );

		if ( file_exists( $file ) ) {
			include_once $file;
		}
	}

	public static function get_option( $name, $default = false ) {
		$option = get_option( 'WELDPRESS' );

		if ( false === $option ) {
			return $default;
		}

		if ( isset( $option[$name] ) ) {
			return $option[$name];
		} else {
			return $default;
		}
	}

	public static function update_option( $name, $value ) {
		$option = get_option( 'WELDPRESS' );
		$option = ( false === $option ) ? array() : (array) $option;
		$option = array_merge( $option, array( $name => $value ) );
		update_option( 'WELDPRESS', $option );
	}
}

add_action( 'plugins_loaded', 'WELDPRESS' );

function WELDPRESS() {
	WELD_PRESS_load_textdomain();
	WELDPRESS::load_modules();

	/* Shortcodes */
	add_shortcode( 'weld-press', 'WELD_PRESS_application_tag_func' );
	add_shortcode( 'contact-form', 'WELD_PRESS_application_tag_func' );
}

add_action( 'init', 'WELD_PRESS_init' );

function WELD_PRESS_init() {
	WELD_PRESS_get_request_uri();
	WELD_PRESS_register_post_types();

	do_action( 'WELD_PRESS_init' );
}

add_action( 'admin_init', 'WELD_PRESS_upgrade' );

function WELD_PRESS_upgrade() {
	$old_ver = WELDPRESS::get_option( 'version', '0' );
	$new_ver = WELDPRESS_VERSION;

	if ( $old_ver == $new_ver ) {
		return;
	}

	do_action( 'WELD_PRESS_upgrade', $new_ver, $old_ver );

	WELDPRESS::update_option( 'version', $new_ver );
}

/* Install and default settings */

add_action( 'activate_' . WELD_PRESS_PLUGIN_BASENAME, 'WELD_PRESS_install' );

function WELD_PRESS_install() {
	if ( $opt = get_option( 'WELDPRESS' ) ) {
		return;
	}

	WELD_PRESS_load_textdomain();
	WELD_PRESS_register_post_types();
	WELD_PRESS_upgrade();

	if ( get_posts( array( 'post_type' => 'WELD_PRESS_application' ) ) ) {
		return;
	}

	$contact_form = WELD_PRESS_Application::get_template( array(
		'title' => sprintf( __( 'Solution %d', 'weld-press' ), 1 ) ) );

	$contact_form->save();

	WELDPRESS::update_option( 'bulk_validate', array(
		'timestamp' => current_time( 'timestamp' ),
		'version' => WELDPRESS_VERSION,
		'count_valid' => 1,
		'count_invalid' => 0 ) );
}
