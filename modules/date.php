<?php
/**
** A base module for the following types of tags:
** 	[date] and [date*]		# Date
**/

/* form_tag handler */

add_action( 'WELD_PRESS_init', 'WELD_PRESS_add_form_tag_date' );

function WELD_PRESS_add_form_tag_date() {
	WELD_PRESS_add_form_tag( array( 'date', 'date*' ),
		'WELD_PRESS_date_form_tag_handler', true );
}

function WELD_PRESS_date_form_tag_handler( $tag ) {
	$tag = new WELD_PRESS_FormTag( $tag );

	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = WELD_PRESS_get_validation_error( $tag->name );

	$class = WELD_PRESS_form_controls_class( $tag->type );

	$class .= ' WELDPRESS-validates-as-date';

	if ( $validation_error ) {
		$class .= ' WELDPRESS-not-valid';
	}

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );
	$atts['min'] = $tag->get_date_option( 'min' );
	$atts['max'] = $tag->get_date_option( 'max' );
	$atts['step'] = $tag->get_option( 'step', 'int', true );

	if ( $tag->has_option( 'readonly' ) ) {
		$atts['readonly'] = 'readonly';
	}

	if ( $tag->is_required() ) {
		$atts['aria-required'] = 'true';
	}

	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$value = (string) reset( $tag->values );

	if ( $tag->has_option( 'placeholder' ) || $tag->has_option( 'watermark' ) ) {
		$atts['placeholder'] = $value;
		$value = '';
	}

	$value = $tag->get_default_option( $value );

	$value = WELD_PRESS_get_hangover( $tag->name, $value );

	$atts['value'] = $value;

	if ( WELD_PRESS_support_html5() ) {
		$atts['type'] = $tag->basetype;
	} else {
		$atts['type'] = 'text';
	}

	$atts['name'] = $tag->name;

	$atts = WELD_PRESS_format_atts( $atts );

	$html = sprintf(
		'<span class="WELDPRESS-form-control-wrap %1$s"><input %2$s />%3$s</span>',
		sanitize_html_class( $tag->name ), $atts, $validation_error );

	return $html;
}


/* Validation filter */

add_filter( 'WELD_PRESS_validate_date', 'WELD_PRESS_date_validation_filter', 10, 2 );
add_filter( 'WELD_PRESS_validate_date*', 'WELD_PRESS_date_validation_filter', 10, 2 );

function WELD_PRESS_date_validation_filter( $result, $tag ) {
	$tag = new WELD_PRESS_FormTag( $tag );

	$name = $tag->name;

	$min = $tag->get_date_option( 'min' );
	$max = $tag->get_date_option( 'max' );

	$value = isset( $_POST[$name] )
		? trim( strtr( (string) $_POST[$name], "\n", " " ) )
		: '';

	if ( $tag->is_required() && '' == $value ) {
		$result->invalidate( $tag, WELD_PRESS_get_message( 'invalid_required' ) );
	} elseif ( '' != $value && ! WELD_PRESS_is_date( $value ) ) {
		$result->invalidate( $tag, WELD_PRESS_get_message( 'invalid_date' ) );
	} elseif ( '' != $value && ! empty( $min ) && $value < $min ) {
		$result->invalidate( $tag, WELD_PRESS_get_message( 'date_too_early' ) );
	} elseif ( '' != $value && ! empty( $max ) && $max < $value ) {
		$result->invalidate( $tag, WELD_PRESS_get_message( 'date_too_late' ) );
	}

	return $result;
}


/* Messages */

add_filter( 'WELD_PRESS_messages', 'WELD_PRESS_date_messages' );

function WELD_PRESS_date_messages( $messages ) {
	return array_merge( $messages, array(
		'invalid_date' => array(
			'description' => __( "Date format that the sender entered is invalid", 'weld-press' ),
			'default' => __( "The date format is incorrect.", 'weld-press' )
		),

		'date_too_early' => array(
			'description' => __( "Date is earlier than minimum limit", 'weld-press' ),
			'default' => __( "The date is before the earliest one allowed.", 'weld-press' )
		),

		'date_too_late' => array(
			'description' => __( "Date is later than maximum limit", 'weld-press' ),
			'default' => __( "The date is after the latest one allowed.", 'weld-press' )
		) ) );
}


/* Tag generator */

add_action( 'WELD_PRESS_admin_init', 'WELD_PRESS_add_tag_generator_date', 19 );

function WELD_PRESS_add_tag_generator_date() {
	$tag_generator = WELD_PRESS_TagGenerator::get_instance();
	$tag_generator->add( 'date', __( 'date', 'weld-press' ),
		'WELD_PRESS_tag_generator_date' );
}

function WELD_PRESS_tag_generator_date( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = 'date';

	$description = __( "Generate a form-tag for a date input field. For more details, see %s.", 'weld-press' );

	$desc_link = WELD_PRESS_link( __( 'http://contactform7.com/date-field/', 'weld-press' ), __( 'Date Field', 'weld-press' ) );

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
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php echo esc_html( __( 'Default value', 'weld-press' ) ); ?></label></th>
	<td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" /><br />
	<label><input type="checkbox" name="placeholder" class="option" /> <?php echo esc_html( __( 'Use this text as the placeholder of the field', 'weld-press' ) ); ?></label></td>
	</tr>

	<tr>
	<th scope="row"><?php echo esc_html( __( 'Range', 'weld-press' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Range', 'weld-press' ) ); ?></legend>
		<label>
		<?php echo esc_html( __( 'Min', 'weld-press' ) ); ?>
		<input type="date" name="min" class="date option" />
		</label>
		&ndash;
		<label>
		<?php echo esc_html( __( 'Max', 'weld-press' ) ); ?>
		<input type="date" name="max" class="date option" />
		</label>
		</fieldset>
	</td>
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

	<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'weld-press' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
</div>
<?php
}
