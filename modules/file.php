<?php
/**
** A base module for [file] and [file*]
**/

/* form_tag handler */

add_action( 'WELD_PRESS_init', 'WELD_PRESS_add_form_tag_file' );

function WELD_PRESS_add_form_tag_file() {
	WELD_PRESS_add_form_tag( array( 'file', 'file*' ),
		'WELD_PRESS_file_form_tag_handler', true );
}

function WELD_PRESS_file_form_tag_handler( $tag ) {
	$tag = new WELD_PRESS_FormTag( $tag );

	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = WELD_PRESS_get_validation_error( $tag->name );

	$class = WELD_PRESS_form_controls_class( $tag->type );

	if ( $validation_error ) {
		$class .= ' WELDPRESS-not-valid';
	}

	$atts = array();

	$atts['size'] = $tag->get_size_option( '40' );
	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );

	if ( $tag->is_required() ) {
		$atts['aria-required'] = 'true';
	}

	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$atts['type'] = 'file';
	$atts['name'] = $tag->name;

	$atts = WELD_PRESS_format_atts( $atts );

	$html = sprintf(
		'<span class="WELDPRESS-form-control-wrap %1$s"><input %2$s />%3$s</span>',
		sanitize_html_class( $tag->name ), $atts, $validation_error );

	return $html;
}


/* Encode type filter */

add_filter( 'WELD_PRESS_form_enctype', 'WELD_PRESS_file_form_enctype_filter' );

function WELD_PRESS_file_form_enctype_filter( $enctype ) {
	$multipart = (bool) WELD_PRESS_scan_form_tags( array( 'type' => array( 'file', 'file*' ) ) );

	if ( $multipart ) {
		$enctype = 'multipart/form-data';
	}

	return $enctype;
}


/* Validation + upload handling filter */

add_filter( 'WELD_PRESS_validate_file', 'WELD_PRESS_file_validation_filter', 10, 2 );
add_filter( 'WELD_PRESS_validate_file*', 'WELD_PRESS_file_validation_filter', 10, 2 );

function WELD_PRESS_file_validation_filter( $result, $tag ) {
	$tag = new WELD_PRESS_FormTag( $tag );

	$name = $tag->name;
	$id = $tag->get_id_option();

	$file = isset( $_FILES[$name] ) ? $_FILES[$name] : null;

	if ( $file['error'] && UPLOAD_ERR_NO_FILE != $file['error'] ) {
		$result->invalidate( $tag, WELD_PRESS_get_message( 'upload_failed_php_error' ) );
		return $result;
	}

	if ( empty( $file['tmp_name'] ) && $tag->is_required() ) {
		$result->invalidate( $tag, WELD_PRESS_get_message( 'invalid_required' ) );
		return $result;
	}

	if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
		return $result;
	}

	$allowed_file_types = array();

	if ( $file_types_a = $tag->get_option( 'filetypes' ) ) {
		foreach ( $file_types_a as $file_types ) {
			$file_types = explode( '|', $file_types );

			foreach ( $file_types as $file_type ) {
				$file_type = trim( $file_type, '.' );
				$file_type = str_replace( array( '.', '+', '*', '?' ),
					array( '\.', '\+', '\*', '\?' ), $file_type );
				$allowed_file_types[] = $file_type;
			}
		}
	}

	$allowed_file_types = array_unique( $allowed_file_types );
	$file_type_pattern = implode( '|', $allowed_file_types );

	$allowed_size = 1048576; // default size 1 MB

	if ( $file_size_a = $tag->get_option( 'limit' ) ) {
		$limit_pattern = '/^([1-9][0-9]*)([kKmM]?[bB])?$/';

		foreach ( $file_size_a as $file_size ) {
			if ( preg_match( $limit_pattern, $file_size, $matches ) ) {
				$allowed_size = (int) $matches[1];

				if ( ! empty( $matches[2] ) ) {
					$kbmb = strtolower( $matches[2] );

					if ( 'kb' == $kbmb ) {
						$allowed_size *= 1024;
					} elseif ( 'mb' == $kbmb ) {
						$allowed_size *= 1024 * 1024;
					}
				}

				break;
			}
		}
	}

	/* File type validation */

	// Default file-type restriction
	if ( '' == $file_type_pattern ) {
		$file_type_pattern = 'jpg|jpeg|png|gif|pdf|doc|docx|ppt|pptx|odt|avi|ogg|m4a|mov|mp3|mp4|mpg|wav|wmv';
	}

	$file_type_pattern = trim( $file_type_pattern, '|' );
	$file_type_pattern = '(' . $file_type_pattern . ')';
	$file_type_pattern = '/\.' . $file_type_pattern . '$/i';

	if ( ! preg_match( $file_type_pattern, $file['name'] ) ) {
		$result->invalidate( $tag, WELD_PRESS_get_message( 'upload_file_type_invalid' ) );
		return $result;
	}

	/* File size validation */

	if ( $file['size'] > $allowed_size ) {
		$result->invalidate( $tag, WELD_PRESS_get_message( 'upload_file_too_large' ) );
		return $result;
	}

	WELD_PRESS_init_uploads(); // Confirm upload dir
	$uploads_dir = WELD_PRESS_upload_tmp_dir();
	$uploads_dir = WELD_PRESS_maybe_add_random_dir( $uploads_dir );

	$filename = $file['name'];
	$filename = WELD_PRESS_canonicalize( $filename, 'as-is' );
	$filename = sanitize_file_name( $filename );
	$filename = WELD_PRESS_antiscript_file_name( $filename );
	$filename = wp_unique_filename( $uploads_dir, $filename );

	$new_file = trailingslashit( $uploads_dir ) . $filename;

	if ( false === @move_uploaded_file( $file['tmp_name'], $new_file ) ) {
		$result->invalidate( $tag, WELD_PRESS_get_message( 'upload_failed' ) );
		return $result;
	}

	// Make sure the uploaded file is only readable for the owner process
	@chmod( $new_file, 0400 );

	if ( $submission = WELD_PRESS_Submission::get_instance() ) {
		$submission->add_uploaded_file( $name, $new_file );
	}

	return $result;
}


/* Messages */

add_filter( 'WELD_PRESS_messages', 'WELD_PRESS_file_messages' );

function WELD_PRESS_file_messages( $messages ) {
	return array_merge( $messages, array(
		'upload_failed' => array(
			'description' => __( "Uploading a file fails for any reason", 'weld-press' ),
			'default' => __( "There was an unknown error uploading the file.", 'weld-press' )
		),

		'upload_file_type_invalid' => array(
			'description' => __( "Uploaded file is not allowed for file type", 'weld-press' ),
			'default' => __( "You are not allowed to upload files of this type.", 'weld-press' )
		),

		'upload_file_too_large' => array(
			'description' => __( "Uploaded file is too large", 'weld-press' ),
			'default' => __( "The file is too big.", 'weld-press' )
		),

		'upload_failed_php_error' => array(
			'description' => __( "Uploading a file fails for PHP error", 'weld-press' ),
			'default' => __( "There was an error uploading the file.", 'weld-press' )
		)
	) );
}


/* Tag generator */

add_action( 'WELD_PRESS_admin_init', 'WELD_PRESS_add_tag_generator_file', 50 );

function WELD_PRESS_add_tag_generator_file() {
	$tag_generator = WELD_PRESS_TagGenerator::get_instance();
	$tag_generator->add( 'file', __( 'file', 'weld-press' ),
		'WELD_PRESS_tag_generator_file' );
}

function WELD_PRESS_tag_generator_file( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = 'file';

	$description = __( "Generate a form-tag for a file uploading field. For more details, see %s.", 'weld-press' );

	$desc_link = WELD_PRESS_link( __( 'http://contactform7.com/file-uploading-and-attachment/', 'weld-press' ), __( 'File Uploading and Attachment', 'weld-press' ) );

?>
<div class="control-box">
<fieldset>
<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

<table class="form-table">
<tbody>
	<tr>
	<th scope="row"><?php echo esc_html( __( 'Field type', 'weld-press' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'weld-press' ) ); ?></legend>
		<label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'weld-press' ) ); ?></label>
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'weld-press' ) ); ?></label></th>
	<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-limit' ); ?>"><?php echo esc_html( __( "File size limit (bytes)", 'weld-press' ) ); ?></label></th>
	<td><input type="text" name="limit" class="filesize oneline option" id="<?php echo esc_attr( $args['content'] . '-limit' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-filetypes' ); ?>"><?php echo esc_html( __( 'Acceptable file types', 'weld-press' ) ); ?></label></th>
	<td><input type="text" name="filetypes" class="filetype oneline option" id="<?php echo esc_attr( $args['content'] . '-filetypes' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'weld-press' ) ); ?></label></th>
	<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'weld-press' ) ); ?></label></th>
	<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
	</tr>

</tbody>
</table>
</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
	<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'weld-press' ) ); ?>" />
	</div>

	<br class="clear" />

	<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To attach the file uploaded through this field to mail, you need to insert the corresponding mail-tag (%s) into the File Attachments field on the Mail tab.", 'weld-press' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
</div>
<?php
}


/* Warning message */

add_action( 'WELD_PRESS_admin_warnings', 'WELD_PRESS_file_display_warning_message' );

function WELD_PRESS_file_display_warning_message() {
	if ( ! $contact_form = WELD_PRESS_get_current_contact_form() ) {
		return;
	}

	$has_tags = (bool) $contact_form->scan_form_tags(
		array( 'type' => array( 'file', 'file*' ) ) );

	if ( ! $has_tags ) {
		return;
	}

	$uploads_dir = WELD_PRESS_upload_tmp_dir();
	WELD_PRESS_init_uploads();

	if ( ! is_dir( $uploads_dir ) || ! wp_is_writable( $uploads_dir ) ) {
		$message = sprintf( __( 'This contact form contains file uploading fields, but the temporary folder for the files (%s) does not exist or is not writable. You can create the folder or change its permission manually.', 'weld-press' ), $uploads_dir );

		echo '<div class="notice notice-warning"><p>' . esc_html( $message ) . '</p></div>';
	}
}


/* File uploading functions */

function WELD_PRESS_init_uploads() {
	$dir = WELD_PRESS_upload_tmp_dir();
	wp_mkdir_p( $dir );

	$htaccess_file = trailingslashit( $dir ) . '.htaccess';

	if ( file_exists( $htaccess_file ) ) {
		return;
	}

	if ( $handle = @fopen( $htaccess_file, 'w' ) ) {
		fwrite( $handle, "Deny from all\n" );
		fclose( $handle );
	}
}

function WELD_PRESS_maybe_add_random_dir( $dir ) {
	do {
		$rand_max = mt_getrandmax();
		$rand = zeroise( mt_rand( 0, $rand_max ), strlen( $rand_max ) );
		$dir_new = path_join( $dir, $rand );
	} while ( file_exists( $dir_new ) );

	if ( wp_mkdir_p( $dir_new ) ) {
		return $dir_new;
	}

	return $dir;
}

function WELD_PRESS_upload_tmp_dir() {
	if ( defined( 'WELD_PRESS_UPLOADS_TMP_DIR' ) )
		return WELD_PRESS_UPLOADS_TMP_DIR;
	else
		return WELD_PRESS_upload_dir( 'dir' ) . '/WELD_PRESS_uploads';
}

add_action( 'template_redirect', 'WELD_PRESS_cleanup_upload_files', 20 );

function WELD_PRESS_cleanup_upload_files( $seconds = 60, $max = 100 ) {
	if ( is_admin() || 'GET' != $_SERVER['REQUEST_METHOD']
	|| is_robots() || is_feed() || is_trackback() ) {
		return;
	}

	$dir = trailingslashit( WELD_PRESS_upload_tmp_dir() );

	if ( ! is_dir( $dir ) || ! is_readable( $dir ) || ! wp_is_writable( $dir ) ) {
		return;
	}

	$seconds = absint( $seconds );
	$max = absint( $max );
	$count = 0;

	if ( $handle = @opendir( $dir ) ) {
		while ( false !== ( $file = readdir( $handle ) ) ) {
			if ( $file == "." || $file == ".." || $file == ".htaccess" ) {
				continue;
			}

			$mtime = @filemtime( $dir . $file );

			if ( $mtime && time() < $mtime + $seconds ) { // less than $seconds old
				continue;
			}

			WELD_PRESS_rmdir_p( path_join( $dir, $file ) );
			$count += 1;

			if ( $max <= $count ) {
				break;
			}
		}

		closedir( $handle );
	}
}
