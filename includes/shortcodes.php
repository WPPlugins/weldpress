<?php
/**
 * All the functions and classes in this file are deprecated.
 * You shouldn't use them. The functions and classes will be
 * removed in a later version.
 */

function WELD_PRESS_add_shortcode( $tag, $func, $has_name = false ) {
	WELD_PRESS_deprecated_function( __FUNCTION__, '4.6', 'WELD_PRESS_add_form_tag' );

	return WELD_PRESS_add_form_tag( $tag, $func, $has_name );
}

function WELD_PRESS_remove_shortcode( $tag ) {
	WELD_PRESS_deprecated_function( __FUNCTION__, '4.6', 'WELD_PRESS_remove_form_tag' );

	return WELD_PRESS_remove_form_tag( $tag );
}

function WELD_PRESS_do_shortcode( $content ) {
	WELD_PRESS_deprecated_function( __FUNCTION__, '4.6',
		'WELD_PRESS_replace_all_form_tags' );

	return WELD_PRESS_replace_all_form_tags( $content );
}

function WELD_PRESS_scan_shortcode( $cond = null ) {
	WELD_PRESS_deprecated_function( __FUNCTION__, '4.6', 'WELD_PRESS_scan_form_tags' );

	return WELD_PRESS_scan_form_tags( $cond );
}

class WELD_PRESS_ShortcodeManager {

	private static $form_tags_manager;

	private function __construct() {}

	public static function get_instance() {
		WELD_PRESS_deprecated_function( __METHOD__, '4.6',
			'WELD_PRESS_FormTagsManager::get_instance' );

		self::$form_tags_manager = WELD_PRESS_FormTagsManager::get_instance();
		return new self;
	}

	public function get_scanned_tags() {
		WELD_PRESS_deprecated_function( __METHOD__, '4.6',
			'WELD_PRESS_FormTagsManager::get_scanned_tags' );

		return self::$form_tags_manager->get_scanned_tags();
	}

	public function add_shortcode( $tag, $func, $has_name = false ) {
		WELD_PRESS_deprecated_function( __METHOD__, '4.6',
			'WELD_PRESS_FormTagsManager::add' );

		return self::$form_tags_manager->add( $tag, $func, $has_name );
	}

	public function remove_shortcode( $tag ) {
		WELD_PRESS_deprecated_function( __METHOD__, '4.6',
			'WELD_PRESS_FormTagsManager::remove' );

		return self::$form_tags_manager->remove( $tag );
	}

	public function normalize_shortcode( $content ) {
		WELD_PRESS_deprecated_function( __METHOD__, '4.6',
			'WELD_PRESS_FormTagsManager::normalize' );

		return self::$form_tags_manager->normalize( $content );
	}

	public function do_shortcode( $content, $exec = true ) {
		WELD_PRESS_deprecated_function( __METHOD__, '4.6',
			'WELD_PRESS_FormTagsManager::replace_all' );

		if ( $exec ) {
			return self::$form_tags_manager->replace_all( $content );
		} else {
			return self::$form_tags_manager->scan( $content );
		}
	}

	public function scan_shortcode( $content ) {
		WELD_PRESS_deprecated_function( __METHOD__, '4.6',
			'WELD_PRESS_FormTagsManager::scan' );

		return self::$form_tags_manager->scan( $content );
	}
}

class WELD_PRESS_Shortcode extends WELD_PRESS_FormTag {

	public function __construct( $tag ) {
		WELD_PRESS_deprecated_function( 'WELD_PRESS_Shortcode', '4.6', 'WELD_PRESS_FormTag' );

		parent::__construct( $tag );
	}
}
