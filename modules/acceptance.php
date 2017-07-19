<?php
/**
** A base module for [acceptance]
**/

/* form_tag handler */

add_action( 'WELD_PRESS_init', 'WELD_PRESS_add_form_tag_acceptance' );

function WELD_PRESS_add_form_tag_acceptance() {
	WELD_PRESS_add_form_tag( 'acceptance',
		'WELD_PRESS_acceptance_form_tag_handler', true );
}

function WELD_PRESS_acceptance_form_tag_handler( $tag ) {
	$tag = new WELD_PRESS_FormTag( $tag );

	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = WELD_PRESS_get_validation_error( $tag->name );

	$class = WELD_PRESS_form_controls_class( $tag->type );

	if ( $validation_error ) {
		$class .= ' WELDPRESS-not-valid';
	}

	if ( $tag->has_option( 'invert' ) ) {
		$class .= ' WELDPRESS-invert';
	}

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );

	if ( $tag->has_option( 'default:on' ) ) {
		$atts['checked'] = 'checked';
	}

	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$atts['type'] = 'checkbox';
	$atts['name'] = $tag->name;
	$atts['value'] = '1';

	$atts = WELD_PRESS_format_atts( $atts );

	$html = sprintf(
		'<span class="WELDPRESS-form-control-wrap %1$s"><input %2$s />%3$s</span>',
		sanitize_html_class( $tag->name ), $atts, $validation_error );

	return $html;
}


/* Validation filter */

add_filter( 'WELD_PRESS_validate_acceptance', 'WELD_PRESS_acceptance_validation_filter', 10, 2 );

function WELD_PRESS_acceptance_validation_filter( $result, $tag ) {
	if ( ! WELD_PRESS_acceptance_as_validation() ) {
		return $result;
	}

	$tag = new WELD_PRESS_FormTag( $tag );

	$name = $tag->name;
	$value = ( ! empty( $_POST[$name] ) ? 1 : 0 );

	$invert = $tag->has_option( 'invert' );

	if ( $invert && $value || ! $invert && ! $value ) {
		$result->invalidate( $tag, WELD_PRESS_get_message( 'accept_terms' ) );
	}

	return $result;
}


/* Acceptance filter */

add_filter( 'WELD_PRESS_acceptance', 'WELD_PRESS_acceptance_filter' );

function WELD_PRESS_acceptance_filter( $accepted ) {
	if ( ! $accepted )
		return $accepted;

	$fes = WELD_PRESS_scan_form_tags( array( 'type' => 'acceptance' ) );

	foreach ( $fes as $fe ) {
		$name = $fe['name'];
		$options = (array) $fe['options'];

		if ( empty( $name ) )
			continue;

		$value = ( ! empty( $_POST[$name] ) ? 1 : 0 );

		$invert = (bool) preg_grep( '%^invert$%', $options );

		if ( $invert && $value || ! $invert && ! $value )
			$accepted = false;
	}

	return $accepted;
}

add_filter( 'WELD_PRESS_form_class_attr', 'WELD_PRESS_acceptance_form_class_attr' );

function WELD_PRESS_acceptance_form_class_attr( $class ) {
	if ( WELD_PRESS_acceptance_as_validation() )
		return $class . ' WELDPRESS-acceptance-as-validation';

	return $class;
}

function WELD_PRESS_acceptance_as_validation() {
	if ( ! $contact_form = WELD_PRESS_get_current_contact_form() )
		return false;

	return $contact_form->is_true( 'acceptance_as_validation' );
}


/* Tag generator */

add_action( 'WELD_PRESS_admin_init', 'WELD_PRESS_add_tag_generator_acceptance', 35 );

function WELD_PRESS_add_tag_generator_acceptance() {
	$tag_generator = WELD_PRESS_TagGenerator::get_instance();
	$tag_generator->add( 'acceptance', __( 'acceptance', 'weld-press' ),
		'WELD_PRESS_tag_generator_acceptance' );
}

function WELD_PRESS_tag_generator_acceptance( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = 'acceptance';

	$description = __( "Generate a form-tag for an acceptance checkbox. For more details, see %s.", 'weld-press' );

	$desc_link = WELD_PRESS_link( __( 'http://contactform7.com/acceptance-checkbox/', 'weld-press' ), __( 'Acceptance Checkbox', 'weld-press' ) );

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

	<tr>
	<th scope="row"><?php echo esc_html( __( 'Options', 'weld-press' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Options', 'weld-press' ) ); ?></legend>
		<label><input type="checkbox" name="default:on" class="option" /> <?php echo esc_html( __( 'Make this checkbox checked by default', 'weld-press' ) ); ?></label><br />
		<label><input type="checkbox" name="invert" class="option" /> <?php echo esc_html( __( 'Make this work inversely', 'weld-press' ) ); ?></label>
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
</div>
<?php
}
