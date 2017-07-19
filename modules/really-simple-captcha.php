<?php
/**
** A base module for [captchac] and [captchar]
**/

/* form_tag handler */

add_action( 'WELD_PRESS_init', 'WELD_PRESS_add_form_tag_captcha' );

function WELD_PRESS_add_form_tag_captcha() {
	WELD_PRESS_add_form_tag( array( 'captchac', 'captchar' ),
		'WELD_PRESS_captcha_form_tag_handler', true );
}

function WELD_PRESS_captcha_form_tag_handler( $tag ) {
	$tag = new WELD_PRESS_FormTag( $tag );

	if ( 'captchac' == $tag->type && ! class_exists( 'ReallySimpleCaptcha' ) ) {
		return '<em>' . __( 'To use CAPTCHA, you need <a href="http://wordpress.org/extend/plugins/really-simple-captcha/">Really Simple CAPTCHA</a> plugin installed.', 'weld-press' ) . '</em>';
	}

	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = WELD_PRESS_get_validation_error( $tag->name );

	$class = WELD_PRESS_form_controls_class( $tag->type );

	if ( 'captchac' == $tag->type ) { // CAPTCHA-Challenge (image)
		$class .= ' WELDPRESS-captcha-' . $tag->name;

		$atts = array();

		$atts['class'] = $tag->get_class_option( $class );
		$atts['id'] = $tag->get_id_option();

		$op = array( // Default
			'img_size' => array( 72, 24 ),
			'base' => array( 6, 18 ),
			'font_size' => 14,
			'font_char_width' => 15 );

		$op = array_merge( $op, WELD_PRESS_captchac_options( $tag->options ) );

		if ( ! $filename = WELD_PRESS_generate_captcha( $op ) ) {
			return '';
		}

		if ( ! empty( $op['img_size'] ) ) {
			if ( isset( $op['img_size'][0] ) ) {
				$atts['width'] = $op['img_size'][0];
			}

			if ( isset( $op['img_size'][1] ) ) {
				$atts['height'] = $op['img_size'][1];
			}
		}

		$atts['alt'] = 'captcha';
		$atts['src'] = WELD_PRESS_captcha_url( $filename );

		$atts = WELD_PRESS_format_atts( $atts );

		$prefix = substr( $filename, 0, strrpos( $filename, '.' ) );

		$html = sprintf(
			'<input type="hidden" name="_WELD_PRESS_captcha_challenge_%1$s" value="%2$s" /><img %3$s />',
			$tag->name, $prefix, $atts );

		return $html;

	} elseif ( 'captchar' == $tag->type ) { // CAPTCHA-Response (input)
		if ( $validation_error ) {
			$class .= ' WELDPRESS-not-valid';
		}

		$atts = array();

		$atts['size'] = $tag->get_size_option( '40' );
		$atts['maxlength'] = $tag->get_maxlength_option();
		$atts['minlength'] = $tag->get_minlength_option();

		if ( $atts['maxlength'] && $atts['minlength']
		&& $atts['maxlength'] < $atts['minlength'] ) {
			unset( $atts['maxlength'], $atts['minlength'] );
		}

		$atts['class'] = $tag->get_class_option( $class );
		$atts['id'] = $tag->get_id_option();
		$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );
		$atts['autocomplete'] = 'off';
		$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

		$value = (string) reset( $tag->values );

		if ( WELD_PRESS_is_posted() ) {
			$value = '';
		}

		if ( $tag->has_option( 'placeholder' )
		|| $tag->has_option( 'watermark' ) ) {
			$atts['placeholder'] = $value;
			$value = '';
		}

		$atts['value'] = $value;
		$atts['type'] = 'text';
		$atts['name'] = $tag->name;

		$atts = WELD_PRESS_format_atts( $atts );

		$html = sprintf(
			'<span class="WELDPRESS-form-control-wrap %1$s"><input %2$s />%3$s</span>',
			sanitize_html_class( $tag->name ), $atts, $validation_error );

		return $html;
	}
}


/* Validation filter */

add_filter( 'WELD_PRESS_validate_captchar', 'WELD_PRESS_captcha_validation_filter', 10, 2 );

function WELD_PRESS_captcha_validation_filter( $result, $tag ) {
	$tag = new WELD_PRESS_FormTag( $tag );

	$type = $tag->type;
	$name = $tag->name;

	$captchac = '_WELD_PRESS_captcha_challenge_' . $name;

	$prefix = isset( $_POST[$captchac] ) ? (string) $_POST[$captchac] : '';
	$response = isset( $_POST[$name] ) ? (string) $_POST[$name] : '';
	$response = WELD_PRESS_canonicalize( $response );

	if ( 0 == strlen( $prefix ) || ! WELD_PRESS_check_captcha( $prefix, $response ) ) {
		$result->invalidate( $tag, WELD_PRESS_get_message( 'captcha_not_match' ) );
	}

	if ( 0 != strlen( $prefix ) ) {
		WELD_PRESS_remove_captcha( $prefix );
	}

	return $result;
}


/* Ajax echo filter */

add_filter( 'WELD_PRESS_ajax_onload', 'WELD_PRESS_captcha_ajax_refill' );
add_filter( 'WELD_PRESS_ajax_json_echo', 'WELD_PRESS_captcha_ajax_refill' );

function WELD_PRESS_captcha_ajax_refill( $items ) {
	if ( ! is_array( $items ) )
		return $items;

	$fes = WELD_PRESS_scan_form_tags( array( 'type' => 'captchac' ) );

	if ( empty( $fes ) )
		return $items;

	$refill = array();

	foreach ( $fes as $fe ) {
		$name = $fe['name'];
		$options = $fe['options'];

		if ( empty( $name ) )
			continue;

		$op = WELD_PRESS_captchac_options( $options );
		if ( $filename = WELD_PRESS_generate_captcha( $op ) ) {
			$captcha_url = WELD_PRESS_captcha_url( $filename );
			$refill[$name] = $captcha_url;
		}
	}

	if ( ! empty( $refill ) )
		$items['captcha'] = $refill;

	return $items;
}


/* Messages */

add_filter( 'WELD_PRESS_messages', 'WELD_PRESS_captcha_messages' );

function WELD_PRESS_captcha_messages( $messages ) {
	return array_merge( $messages, array( 'captcha_not_match' => array(
		'description' => __( "The code that sender entered does not match the CAPTCHA", 'weld-press' ),
		'default' => __( 'Your entered code is incorrect.', 'weld-press' )
	) ) );
}


/* Tag generator */

add_action( 'WELD_PRESS_admin_init', 'WELD_PRESS_add_tag_generator_captcha', 46 );

function WELD_PRESS_add_tag_generator_captcha() {
	if ( ! WELD_PRESS_use_really_simple_captcha() ) {
		return;
	}

	$tag_generator = WELD_PRESS_TagGenerator::get_instance();
	$tag_generator->add( 'captcha',
		__( 'CAPTCHA (Really Simple CAPTCHA)', 'weld-press' ),
		'WELD_PRESS_tag_generator_captcha' );
}

function WELD_PRESS_tag_generator_captcha( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );

	if ( ! class_exists( 'ReallySimpleCaptcha' ) ) {
?>
<div class="control-box">
<fieldset>
<legend><?php echo sprintf( esc_html( __( "To use CAPTCHA, you first need to install and activate %s plugin.", 'weld-press' ) ), WELD_PRESS_link( 'http://wordpress.org/extend/plugins/really-simple-captcha/', 'Really Simple CAPTCHA' ) ); ?></legend>
</fieldset>
</div>
<?php

		return;
	}

	$description = __( "Generate form-tags for a CAPTCHA image and corresponding response input field. For more details, see %s.", 'weld-press' );

	$desc_link = WELD_PRESS_link( __( 'http://contactform7.com/captcha/', 'weld-press' ), __( 'CAPTCHA', 'weld-press' ) );

?>
<div class="control-box">
<fieldset>
<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

<table class="form-table">
<tbody>
	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'weld-press' ) ); ?></label></th>
	<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
	</tr>
</tbody>
</table>

<table class="form-table scope captchac">
<caption><?php echo esc_html( __( "Image settings", 'weld-press' ) ); ?></caption>
<tbody>
	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-captchac-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'weld-press' ) ); ?></label></th>
	<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-captchac-id' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-captchac-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'weld-press' ) ); ?></label></th>
	<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-captchac-class' ); ?>" /></td>
	</tr>
</tbody>
</table>

<table class="form-table scope captchar">
<caption><?php echo esc_html( __( "Input field settings", 'weld-press' ) ); ?></caption>
<tbody>
	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-captchar-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'weld-press' ) ); ?></label></th>
	<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-captchar-id' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-captchar-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'weld-press' ) ); ?></label></th>
	<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-captchar-class' ); ?>" /></td>
	</tr>
</tbody>
</table>
</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="captcha" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
	<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'weld-press' ) ); ?>" />
	</div>
</div>
<?php
}


/* Warning message */

add_action( 'WELD_PRESS_admin_warnings', 'WELD_PRESS_captcha_display_warning_message' );

function WELD_PRESS_captcha_display_warning_message() {
	if ( ! $contact_form = WELD_PRESS_get_current_contact_form() ) {
		return;
	}

	$has_tags = (bool) $contact_form->scan_form_tags(
		array( 'type' => array( 'captchac' ) ) );

	if ( ! $has_tags ) {
		return;
	}

	if ( ! class_exists( 'ReallySimpleCaptcha' ) ) {
		return;
	}

	$uploads_dir = WELD_PRESS_captcha_tmp_dir();
	WELD_PRESS_init_captcha();

	if ( ! is_dir( $uploads_dir ) || ! wp_is_writable( $uploads_dir ) ) {
		$message = sprintf( __( 'This contact form contains CAPTCHA fields, but the temporary folder for the files (%s) does not exist or is not writable. You can create the folder or change its permission manually.', 'weld-press' ), $uploads_dir );

		echo '<div class="notice notice-warning"><p>' . esc_html( $message ) . '</p></div>';
	}

	if ( ! function_exists( 'imagecreatetruecolor' ) || ! function_exists( 'imagettftext' ) ) {
		$message = __( 'This contact form contains CAPTCHA fields, but the necessary libraries (GD and FreeType) are not available on your server.', 'weld-press' );

		echo '<div class="notice notice-warning"><p>' . esc_html( $message ) . '</p></div>';
	}
}


/* CAPTCHA functions */

function WELD_PRESS_init_captcha() {
	static $captcha = null;

	if ( $captcha ) {
		return $captcha;
	}

	if ( class_exists( 'ReallySimpleCaptcha' ) ) {
		$captcha = new ReallySimpleCaptcha();
	} else {
		return false;
	}

	$dir = trailingslashit( WELD_PRESS_captcha_tmp_dir() );

	$captcha->tmp_dir = $dir;

	if ( is_callable( array( $captcha, 'make_tmp_dir' ) ) ) {
		$result = $captcha->make_tmp_dir();

		if ( ! $result ) {
			return false;
		}

		return $captcha;
	}

	if ( wp_mkdir_p( $dir ) ) {
		$htaccess_file = $dir . '.htaccess';

		if ( file_exists( $htaccess_file ) ) {
			return $captcha;
		}

		if ( $handle = @fopen( $htaccess_file, 'w' ) ) {
			fwrite( $handle, 'Order deny,allow' . "\n" );
			fwrite( $handle, 'Deny from all' . "\n" );
			fwrite( $handle, '<Files ~ "^[0-9A-Za-z]+\\.(jpeg|gif|png)$">' . "\n" );
			fwrite( $handle, '    Allow from all' . "\n" );
			fwrite( $handle, '</Files>' . "\n" );
			fclose( $handle );
		}
	} else {
		return false;
	}

	return $captcha;
}

function WELD_PRESS_captcha_tmp_dir() {
	if ( defined( 'WELD_PRESS_CAPTCHA_TMP_DIR' ) )
		return WELD_PRESS_CAPTCHA_TMP_DIR;
	else
		return WELD_PRESS_upload_dir( 'dir' ) . '/WELD_PRESS_captcha';
}

function WELD_PRESS_captcha_tmp_url() {
	if ( defined( 'WELD_PRESS_CAPTCHA_TMP_URL' ) )
		return WELD_PRESS_CAPTCHA_TMP_URL;
	else
		return WELD_PRESS_upload_dir( 'url' ) . '/WELD_PRESS_captcha';
}

function WELD_PRESS_captcha_url( $filename ) {
	$url = trailingslashit( WELD_PRESS_captcha_tmp_url() ) . $filename;

	if ( is_ssl() && 'http:' == substr( $url, 0, 5 ) ) {
		$url = 'https:' . substr( $url, 5 );
	}

	return apply_filters( 'WELD_PRESS_captcha_url', esc_url_raw( $url ) );
}

function WELD_PRESS_generate_captcha( $options = null ) {
	if ( ! $captcha = WELD_PRESS_init_captcha() ) {
		return false;
	}

	if ( ! is_dir( $captcha->tmp_dir ) || ! wp_is_writable( $captcha->tmp_dir ) )
		return false;

	$img_type = imagetypes();
	if ( $img_type & IMG_PNG )
		$captcha->img_type = 'png';
	elseif ( $img_type & IMG_GIF )
		$captcha->img_type = 'gif';
	elseif ( $img_type & IMG_JPG )
		$captcha->img_type = 'jpeg';
	else
		return false;

	if ( is_array( $options ) ) {
		if ( isset( $options['img_size'] ) )
			$captcha->img_size = $options['img_size'];
		if ( isset( $options['base'] ) )
			$captcha->base = $options['base'];
		if ( isset( $options['font_size'] ) )
			$captcha->font_size = $options['font_size'];
		if ( isset( $options['font_char_width'] ) )
			$captcha->font_char_width = $options['font_char_width'];
		if ( isset( $options['fg'] ) )
			$captcha->fg = $options['fg'];
		if ( isset( $options['bg'] ) )
			$captcha->bg = $options['bg'];
	}

	$prefix = wp_rand();
	$captcha_word = $captcha->generate_random_word();
	return $captcha->generate_image( $prefix, $captcha_word );
}

function WELD_PRESS_check_captcha( $prefix, $response ) {
	if ( ! $captcha = WELD_PRESS_init_captcha() ) {
		return false;
	}

	return $captcha->check( $prefix, $response );
}

function WELD_PRESS_remove_captcha( $prefix ) {
	if ( ! $captcha = WELD_PRESS_init_captcha() ) {
		return false;
	}

	if ( preg_match( '/[^0-9]/', $prefix ) ) // Weld Press generates $prefix with wp_rand()
		return false;

	$captcha->remove( $prefix );
}

add_action( 'template_redirect', 'WELD_PRESS_cleanup_captcha_files', 20 );

function WELD_PRESS_cleanup_captcha_files() {
	if ( ! $captcha = WELD_PRESS_init_captcha() ) {
		return false;
	}

	if ( is_callable( array( $captcha, 'cleanup' ) ) )
		return $captcha->cleanup();

	$dir = trailingslashit( WELD_PRESS_captcha_tmp_dir() );

	if ( ! is_dir( $dir ) || ! is_readable( $dir ) || ! wp_is_writable( $dir ) )
		return false;

	if ( $handle = @opendir( $dir ) ) {
		while ( false !== ( $file = readdir( $handle ) ) ) {
			if ( ! preg_match( '/^[0-9]+\.(php|txt|png|gif|jpeg)$/', $file ) )
				continue;

			$stat = @stat( $dir . $file );
			if ( $stat['mtime'] + 3600 < time() ) // 3600 secs == 1 hour
				@unlink( $dir . $file );
		}
		closedir( $handle );
	}
}

function WELD_PRESS_captchac_options( $options ) {
	if ( ! is_array( $options ) )
		return array();

	$op = array();
	$image_size_array = preg_grep( '%^size:[smlSML]$%', $options );

	if ( $image_size = array_shift( $image_size_array ) ) {
		preg_match( '%^size:([smlSML])$%', $image_size, $is_matches );
		switch ( strtolower( $is_matches[1] ) ) {
			case 's':
				$op['img_size'] = array( 60, 20 );
				$op['base'] = array( 6, 15 );
				$op['font_size'] = 11;
				$op['font_char_width'] = 13;
				break;
			case 'l':
				$op['img_size'] = array( 84, 28 );
				$op['base'] = array( 6, 20 );
				$op['font_size'] = 17;
				$op['font_char_width'] = 19;
				break;
			case 'm':
			default:
				$op['img_size'] = array( 72, 24 );
				$op['base'] = array( 6, 18 );
				$op['font_size'] = 14;
				$op['font_char_width'] = 15;
		}
	}

	$fg_color_array = preg_grep( '%^fg:#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$%', $options );
	if ( $fg_color = array_shift( $fg_color_array ) ) {
		preg_match( '%^fg:#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$%', $fg_color, $fc_matches );
		if ( 3 == strlen( $fc_matches[1] ) ) {
			$r = substr( $fc_matches[1], 0, 1 );
			$g = substr( $fc_matches[1], 1, 1 );
			$b = substr( $fc_matches[1], 2, 1 );
			$op['fg'] = array( hexdec( $r . $r ), hexdec( $g . $g ), hexdec( $b . $b ) );
		} elseif ( 6 == strlen( $fc_matches[1] ) ) {
			$r = substr( $fc_matches[1], 0, 2 );
			$g = substr( $fc_matches[1], 2, 2 );
			$b = substr( $fc_matches[1], 4, 2 );
			$op['fg'] = array( hexdec( $r ), hexdec( $g ), hexdec( $b ) );
		}
	}

	$bg_color_array = preg_grep( '%^bg:#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$%', $options );
	if ( $bg_color = array_shift( $bg_color_array ) ) {
		preg_match( '%^bg:#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$%', $bg_color, $bc_matches );
		if ( 3 == strlen( $bc_matches[1] ) ) {
			$r = substr( $bc_matches[1], 0, 1 );
			$g = substr( $bc_matches[1], 1, 1 );
			$b = substr( $bc_matches[1], 2, 1 );
			$op['bg'] = array( hexdec( $r . $r ), hexdec( $g . $g ), hexdec( $b . $b ) );
		} elseif ( 6 == strlen( $bc_matches[1] ) ) {
			$r = substr( $bc_matches[1], 0, 2 );
			$g = substr( $bc_matches[1], 2, 2 );
			$b = substr( $bc_matches[1], 4, 2 );
			$op['bg'] = array( hexdec( $r ), hexdec( $g ), hexdec( $b ) );
		}
	}

	return $op;
}
