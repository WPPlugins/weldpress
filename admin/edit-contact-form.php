<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

function WELD_PRESS_admin_save_button( $post_id ) {
	static $button = '';

	if ( ! empty( $button ) ) {
		echo $button;
		return;
	}

	$nonce = wp_create_nonce( 'WELDPRESS-save-application-form_' . $post_id );

	$onclick = sprintf(
		"this.form._wpnonce.value = '%s';"
		. " this.form.action.value = 'save';"
		. " return true;",
		$nonce );

	$button = sprintf(
		'<input type="submit" class="button-primary" name="WELDPRESS-save" value="%1$s" onclick="%2$s" />',
		esc_attr( __( 'Save', 'weld-press' ) ),
		$onclick );

	echo $button;
}

?><div class="wrap">

<h1><?php
	if ( $post->initial() ) {
		echo esc_html( __( 'Add WeldPress Solution', 'weld-press' ) );
	} else {
		echo esc_html( __( 'Edit Application', 'weld-press' ) );

		if ( current_user_can( 'WELD_PRESS_edit_applications' ) ) {
			echo ' <a href="' . esc_url( menu_page_url( 'WELDPRESS-new', false ) ) . '" class="add-new-h2">' . esc_html( __( 'Add New', 'weld-press' ) ) . '</a>';
		}
	}
?></h1>

<?php do_action( 'WELD_PRESS_admin_warnings' ); ?>
<?php do_action( 'WELD_PRESS_admin_notices' ); ?>

<?php
if ( $post ) :

	if ( current_user_can( 'WELD_PRESS_edit_application', $post_id ) ) {
		$disabled = '';
	} else {
		$disabled = ' disabled="disabled"';
	}
?>

<form method="post" action="<?php echo esc_url( add_query_arg( array( 'post' => $post_id ), menu_page_url( 'WELDPRESS', false ) ) ); ?>" id="WELDPRESS-admin-form-element"<?php do_action( 'WELD_PRESS_post_edit_form_tag' ); ?>>
<?php
	if ( current_user_can( 'WELD_PRESS_edit_application', $post_id ) ) {
		wp_nonce_field( 'WELDPRESS-save-application-form_' . $post_id );
	}
?>
<input type="hidden" id="post_ID" name="post_ID" value="<?php echo (int) $post_id; ?>" />
<input type="hidden" id="WELDPRESS-locale" name="WELDPRESS-locale" value="<?php echo esc_attr( $post->locale ); ?>" />
<input type="hidden" id="hiddenaction" name="action" value="save" />
<input type="hidden" id="active-tab" name="active-tab" value="<?php echo isset( $_GET['active-tab'] ) ? (int) $_GET['active-tab'] : '0'; ?>" />

<div id="poststuff">
<div id="post-body" class="metabox-holder columns-2">
<div id="post-body-content">
<div id="titlediv">
<div id="titlewrap">
	<label class="screen-reader-text" id="title-prompt-text" for="title"><?php echo esc_html( __( 'Enter title here', 'weld-press' ) ); ?></label>
<?php
	$posttitle_atts = array(
		'type' => 'text',
		'name' => 'post_title',
		'size' => 30,
		'value' => $post->initial() ? '' : $post->title(),
		'id' => 'title',
		'spellcheck' => 'true',
		'autocomplete' => 'off',
		'disabled' => current_user_can( 'WELD_PRESS_edit_application', $post_id )
			? '' : 'disabled' );

	echo sprintf( '<input %s />', WELD_PRESS_format_atts( $posttitle_atts ) );
?>
</div><!-- #titlewrap -->

<div class="inside">
<?php
	if ( ! $post->initial() ) :
?>
	<p class="description">
	<label for="WELDPRESS-shortcode"><?php echo esc_html( __( "Copy this shortcode and paste it into your post, page, or text widget content:", 'weld-press' ) ); ?></label>
	<span class="shortcode wp-ui-highlight"><input type="text" id="WELDPRESS-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value="<?php echo esc_attr( $post->shortcode() ); ?>" /></span>
	</p>
<?php
		if ( $old_shortcode = $post->shortcode( array( 'use_old_format' => true ) ) ) :
?>
	<p class="description">
	<label for="WELDPRESS-shortcode-old"><?php echo esc_html( __( "You can also use this old-style shortcode:", 'weld-press' ) ); ?></label>
	<span class="shortcode old"><input type="text" id="WELDPRESS-shortcode-old" onfocus="this.select();" readonly="readonly" class="large-text code" value="<?php echo esc_attr( $old_shortcode ); ?>" /></span>
	</p>
<?php
		endif;
	endif;
?>
</div>
</div><!-- #titlediv -->
</div><!-- #post-body-content -->

<div id="postbox-container-1" class="postbox-container">
<?php if ( current_user_can( 'WELD_PRESS_edit_application', $post_id ) ) : ?>
<div id="submitdiv" class="postbox">
<h3><?php echo esc_html( __( 'Status', 'weld-press' ) ); ?></h3>
<div class="inside">
<div class="submitbox" id="submitpost">

<div id="minor-publishing-actions">

<div class="hidden">
	<input type="submit" class="button-primary" name="WELDPRESS-save" value="<?php echo esc_attr( __( 'Save', 'weld-press' ) ); ?>" />
</div>

<?php
	if ( ! $post->initial() ) :
		$copy_nonce = wp_create_nonce( 'WELDPRESS-copy-contact-form_' . $post_id );
?>
	<input type="submit" name="WELDPRESS-copy" class="copy button" value="<?php echo esc_attr( __( 'Duplicate', 'weld-press' ) ); ?>" <?php echo "onclick=\"this.form._wpnonce.value = '$copy_nonce'; this.form.action.value = 'copy'; return true;\""; ?> />
<?php endif; ?>
</div><!-- #minor-publishing-actions -->

<div id="misc-publishing-actions">
<?php do_action( 'WELD_PRESS_admin_misc_pub_section', $post_id ); ?>
</div><!-- #misc-publishing-actions -->

<div id="major-publishing-actions">

<?php
	if ( ! $post->initial() ) :
		$delete_nonce = wp_create_nonce( 'WELDPRESS-delete-application_' . $post_id );
?>
<div id="delete-action">
	<input type="submit" name="WELDPRESS-delete" class="delete submitdelete" value="<?php echo esc_attr( __( 'Delete', 'weld-press' ) ); ?>" <?php echo "onclick=\"if (confirm('" . esc_js( __( "You are about to delete this contact form.\n  'Cancel' to stop, 'OK' to delete.", 'weld-press' ) ) . "')) {this.form._wpnonce.value = '$delete_nonce'; this.form.action.value = 'delete'; return true;} return false;\""; ?> />
</div><!-- #delete-action -->
<?php endif; ?>

<div id="publishing-action">
	<span class="spinner"></span>
	<?php WELD_PRESS_admin_save_button( $post_id ); ?>
</div>
<div class="clear"></div>
</div><!-- #major-publishing-actions -->
</div><!-- #submitpost -->
</div>
</div><!-- #submitdiv -->
<?php endif; ?>

<div id="informationdiv" class="postbox">
<h3><?php echo esc_html( __( 'Information', 'weld-press' ) ); ?></h3>
<div class="inside">
<ul>
<li><?php echo WELD_PRESS_link( __( 'http://ec2-52-56-176-30.eu-west-2.compute.amazonaws.com/getting-started/', 'weld-press' ), __( 'Docs', 'weld-press' ) ); ?></li>
<li><?php echo WELD_PRESS_link( __( 'http://ec2-52-56-176-30.eu-west-2.compute.amazonaws.com/blog/', 'weld-press' ), __( 'FAQ', 'weld-press' ) ); ?></li>
<li><?php echo WELD_PRESS_link( __( 'http://ec2-52-56-176-30.eu-west-2.compute.amazonaws.com/', 'weld-press' ), __( 'Support', 'weld-press' ) ); ?></li>
</ul>
</div>
</div><!-- #informationdiv -->

</div><!-- #postbox-container-1 -->

<div id="postbox-container-2" class="postbox-container">
<div id="contact-form-editor">
<div class="keyboard-interaction"><?php echo sprintf( esc_html( __( '%s keys switch panels', 'weld-press' ) ), '<span class="dashicons dashicons-leftright"></span>' ); ?></div>

<?php

	$editor = new WELD_PRESS_Editor( $post );
	$panels = array();

	if ( current_user_can( 'WELD_PRESS_edit_application', $post_id ) ) {
		$panels = array(
			'form-panel' => array(
				'title' => __( 'Application', 'weld-press' ),
				'callback' => 'WELD_PRESS_editor_panel_form' ),
			'imports-panel' => array(
				'title' => __( 'Imports', 'weld-press' ),
				'callback' => 'WELD_PRESS_editor_panel_imports' ));

		$additional_settings = trim( $post->prop( 'additional_settings' ) );
		$additional_settings = explode( "\n", $additional_settings );
		$additional_settings = array_filter( $additional_settings );
		$additional_settings = count( $additional_settings );

		$panels['additional-settings-panel'] = array(
			'title' => $additional_settings
				? sprintf(
					__( 'Additional Settings (%d)', 'weld-press' ),
					$additional_settings )
				: __( 'Additional Settings', 'weld-press' ),
			'callback' => 'WELD_PRESS_editor_panel_additional_settings' );
	}

	$panels = apply_filters( 'WELD_PRESS_editor_panels', $panels );

	foreach ( $panels as $id => $panel ) {
		$editor->add_panel( $id, $panel['title'], $panel['callback'] );
	}

	$editor->display();
?>
</div><!-- #contact-form-editor -->

<?php if ( current_user_can( 'WELD_PRESS_edit_application', $post_id ) ) : ?>
<p class="submit"><?php WELD_PRESS_admin_save_button( $post_id ); ?></p>
<?php endif; ?>

</div><!-- #postbox-container-2 -->

</div><!-- #post-body -->
<br class="clear" />
</div><!-- #poststuff -->
</form>

<?php endif; ?>

</div><!-- .wrap -->

<?php

	$tag_generator = WELD_PRESS_TagGenerator::get_instance();
	$tag_generator->print_panels( $post );

	do_action( 'WELD_PRESS_admin_footer', $post );
