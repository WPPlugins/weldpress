<?php
/*
Plugin Name: WeldPress
Plugin URI: http://weldpress.org/
Description: WeldPad plugin for WordPress, WeldPress is a mobile platform that helps you quickly develop high-quality apps. WeldPress is made up of complementary features that you can mix-and-match to fit your needs..
Author: Eran Lavi
Author URI: http://eranlavi.wordpress.com/
Text Domain: weldpad
Domain Path: /languages/
Version: 4.8-dev
*/

define( 'WELDPRESS_VERSION', '4.8.1' );

define( 'WELD_PRESS_REQUIRED_WP_VERSION', '4.5' );

define( 'WELD_PRESS_PLUGIN', __FILE__ );

define( 'WELD_PRESS_PLUGIN_BASENAME', plugin_basename( WELD_PRESS_PLUGIN ) );

define( 'WELD_PRESS_PLUGIN_NAME', trim( dirname( WELD_PRESS_PLUGIN_BASENAME ), '/' ) );

define( 'WELD_PRESS_PLUGIN_DIR', untrailingslashit( dirname( WELD_PRESS_PLUGIN ) ) );

define( 'WELD_PRESS_PLUGIN_MODULES_DIR', WELD_PRESS_PLUGIN_DIR . '/modules' );

if ( ! defined( 'WELD_PRESS_LOAD_JS' ) ) {
	define( 'WELD_PRESS_LOAD_JS', true );
}

if ( ! defined( 'WELD_PRESS_LOAD_CSS' ) ) {
	define( 'WELD_PRESS_LOAD_CSS', true );
}

if ( ! defined( 'WELD_PRESS_AUTOP' ) ) {
	define( 'WELD_PRESS_AUTOP', true );
}

if ( ! defined( 'WELD_PRESS_USE_PIPE' ) ) {
	define( 'WELD_PRESS_USE_PIPE', true );
}

if ( ! defined( 'WELD_PRESS_ADMIN_READ_CAPABILITY' ) ) {
	define( 'WELD_PRESS_ADMIN_READ_CAPABILITY', 'edit_posts' );
}

if ( ! defined( 'WELD_PRESS_ADMIN_READ_WRITE_CAPABILITY' ) ) {
	define( 'WELD_PRESS_ADMIN_READ_WRITE_CAPABILITY', 'publish_pages' );
}

if ( ! defined( 'WELD_PRESS_VERIFY_NONCE' ) ) {
	define( 'WELD_PRESS_VERIFY_NONCE', true );
}

if ( ! defined( 'WELD_PRESS_USE_REALLY_SIMPLE_CAPTCHA' ) ) {
	define( 'WELD_PRESS_USE_REALLY_SIMPLE_CAPTCHA', false );
}

if ( ! defined( 'WELD_PRESS_VALIDATE_CONFIGURATION' ) ) {
	define( 'WELD_PRESS_VALIDATE_CONFIGURATION', true );
}

// Deprecated, not used in the plugin core. Use WELD_PRESS_plugin_url() instead.
define( 'WELD_PRESS_PLUGIN_URL', untrailingslashit( plugins_url( '', WELD_PRESS_PLUGIN ) ) );

require_once WELD_PRESS_PLUGIN_DIR . '/settings.php';
