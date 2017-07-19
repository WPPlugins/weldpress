<?php

function WELD_PRESS_welcome_panel() {
	$classes = 'welcome-panel';

	$vers = (array) get_user_meta( get_current_user_id(),
		'WELD_PRESS_hide_welcome_panel_on', true );

	if ( WELD_PRESS_version_grep( WELD_PRESS_version( 'only_major=1' ), $vers ) ) {
		$classes .= ' hidden';
	}

?>
<div id="welcome-panel" class="<?php echo esc_attr( $classes ); ?>">
	<?php wp_nonce_field( 'WELDPRESS-welcome-panel-nonce', 'welcomepanelnonce', false ); ?>
	<a class="welcome-panel-close" href="<?php echo esc_url( menu_page_url( 'WELDPRESS', false ) ); ?>"><?php echo esc_html( __( 'Dismiss', 'weld-press' ) ); ?></a>

	<div class="welcome-panel-content">
		<div class="welcome-panel-column-container">

			<div class="welcome-panel-column">
				<h3><span class="dashicons dashicons-shield"></span> <?php echo esc_html( __( "WeldPress Component Driven Development.", 'weld-press' ) ); ?></h3>

				<p><?php echo esc_html( __( "WeldPress is a mobile platform that helps you quickly develop high-quality apps. WeldPress is made up of complementary features that you can mix-and-match to fit your needs.", 'weld-press' ) ); ?></p>

				<p><?php echo sprintf( esc_html( __( 'Weld Press read more %1$s.', 'weld-press' ) ), WELD_PRESS_link( __( 'http://ec2-52-56-176-30.eu-west-2.compute.amazonaws.com/', 'weld-press' ), __( 'read more...', 'weld-press' ) ), WELD_PRESS_link( __( 'http://contactform7.com/recaptcha/', 'weld-press' ), __( 'reCAPTCHA', 'weld-press' ) ), WELD_PRESS_link( __( 'http://contactform7.com/comment-blacklist/', 'weld-press' ), __( 'comment blacklist', 'weld-press' ) ) ); ?></p>
			</div>

			<div class="welcome-panel-column">
				<h3><span class="dashicons dashicons-editor-help"></span> <?php echo esc_html( __( "Getting Started&#8230;", 'weld-press' ) ); ?></h3>

				<p><?php echo esc_html( __( "This tutorial is a step by step guide on how to create chat application that displays messages on the map, the messages will be displayed according to the sender geo location", 'weld-press' ) ); ?></p>

				<p><?php echo sprintf( esc_html( __( 'Lets start: %s ', 'weld-press' ) ), WELD_PRESS_link( __( 'http://ec2-52-56-176-30.eu-west-2.compute.amazonaws.com/getting-started/', 'weld-press' ), __( 'getting started', 'weld-press' ) ) ); ?></p>
			</div>

		</div>
	</div>
</div>
<?php
}

add_action( 'wp_ajax_WELDPRESS-update-welcome-panel', 'WELD_PRESS_admin_ajax_welcome_panel' );

function WELD_PRESS_admin_ajax_welcome_panel() {
	check_ajax_referer( 'WELDPRESS-welcome-panel-nonce', 'welcomepanelnonce' );

	$vers = get_user_meta( get_current_user_id(),
		'WELD_PRESS_hide_welcome_panel_on', true );

	if ( empty( $vers ) || ! is_array( $vers ) ) {
		$vers = array();
	}

	if ( empty( $_POST['visible'] ) ) {
		$vers[] = WELD_PRESS_version( 'only_major=1' );
	}

	$vers = array_unique( $vers );

	update_user_meta( get_current_user_id(), 'WELD_PRESS_hide_welcome_panel_on', $vers );

	wp_die( 1 );
}
