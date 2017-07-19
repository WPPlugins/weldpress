<?php

require_once WELD_PRESS_PLUGIN_DIR . '/admin/includes/admin-functions.php';
require_once WELD_PRESS_PLUGIN_DIR . '/admin/includes/help-tabs.php';
require_once WELD_PRESS_PLUGIN_DIR . '/admin/includes/tag-generator.php';
require_once WELD_PRESS_PLUGIN_DIR . '/admin/includes/welcome-panel.php';

add_action( 'admin_init', 'WELD_PRESS_admin_init' );

function WELD_PRESS_admin_init() {
	do_action( 'WELD_PRESS_admin_init' );
}

add_action( 'admin_menu', 'WELD_PRESS_admin_menu', 9 );

function WELD_PRESS_admin_menu() {
	global $_wp_last_object_menu;

	$_wp_last_object_menu++;

	add_menu_page( __( 'Weld Press', 'weld-press' ),
		__( 'WeldPress', 'weld-press' ),
		'WELD_PRESS_read_applications', 'WELDPRESS',
		'WELD_PRESS_admin_management_page', 'dashicons-editor-code',
		$_wp_last_object_menu );

	$edit = add_submenu_page( 'WELDPRESS',
		__( 'Edit Application', 'weld-press' ),
		__( 'WeldPress Solution', 'weld-press' ),
		'WELD_PRESS_read_applications', 'WELDPRESS',
		'WELD_PRESS_admin_management_page' );

	add_action( 'load-' . $edit, 'WELD_PRESS_load_application_admin' );

	$addnew = add_submenu_page( 'WELDPRESS',
		__( 'Add WeldPress Solution', 'weld-press' ),
		__( 'Add New', 'weld-press' ),
		'WELD_PRESS_edit_applications', 'WELDPRESS-new',
		'WELD_PRESS_admin_add_new_page' );

	add_action( 'load-' . $addnew, 'WELD_PRESS_load_application_admin' );

	$integration = WELD_PRESS_Integration::get_instance();

	if ( $integration->service_exists() ) {
		$integration = add_submenu_page( 'WELDPRESS',
			__( 'Integration with Other Services', 'weld-press' ),
			__( 'Integration', 'weld-press' ),
			'WELD_PRESS_manage_integration', 'WELDPRESS-integration',
			'WELD_PRESS_admin_integration_page' );

		add_action( 'load-' . $integration, 'WELD_PRESS_load_integration_page' );
	}
}

add_filter( 'set-screen-option', 'WELD_PRESS_set_screen_options', 10, 3 );

function WELD_PRESS_set_screen_options( $result, $option, $value ) {
	$WELD_PRESS_screens = array(
		'cfseven_applications_per_page' );

	if ( in_array( $option, $WELD_PRESS_screens ) )
		$result = $value;

	return $result;
}

function WELD_PRESS_load_application_admin() {
	global $plugin_page;

	$action = WELD_PRESS_current_action();

	if ( 'save' == $action ) {
		$id = sanitize_text_field($_POST['post_ID']);
		$activetab = $_POST['active-tab'];
		
		//validate activetab value
		if ( $activetab != "0" && $activetab != "1" && $activetab != "2" && $activetab != "3" ) {
			wp_die( __( 'Active Tab is not valid.', 'weld-press' ) );
		}
		
		check_admin_referer( 'WELDPRESS-save-application-form_' . $id );

		if ( ! current_user_can( 'WELD_PRESS_edit_application', $id ) )
			wp_die( __( 'You are not allowed to edit this item.', 'weld-press' ) );

		$id = WELD_PRESS_save_application( $id );

		$query = array(
			'message' => ( -1 == sanitize_text_field( $_POST['post_ID'] )) ? 'created' : 'saved',
			'post' => $id,
			'active-tab' => isset( $activetab ) ? (int) $activetab : 0 );

		$redirect_to = add_query_arg( $query, menu_page_url( 'WELDPRESS', false ) );
		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'copy' == $action ) {
		$id = empty( sanitize_text_field( $_POST['post_ID'] ) )
			? absint( sanitize_text_field( $_REQUEST['post'] ) )
			: absint( sanitize_text_field( $_POST['post_ID'] ));

		check_admin_referer( 'WELDPRESS-copy-contact-form_' . $id );

		if ( ! current_user_can( 'WELD_PRESS_edit_application', $id ) )
			wp_die( __( 'You are not allowed to edit this item.', 'weld-press' ) );

		$query = array();

		if ( $application_form = WELD_PRESS_application( $id ) ) {
			$new_contact_form = $application_form->copy();
			$new_contact_form->save();

			$query['post'] = $new_contact_form->id();
			$query['message'] = 'created';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'WELDPRESS', false ) );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'delete' == $action ) {
		if ( ! empty( sanitize_text_field( $_POST['post_ID'] ) ) )
			check_admin_referer( 'WELDPRESS-delete-application_' . sanitize_text_field( $_POST['post_ID'] ) );
		elseif ( ! is_array( sanitize_text_field( $_REQUEST['post'] ) ) )
			check_admin_referer( 'WELDPRESS-delete-application_' . sanitize_text_field( $_REQUEST['post'] ) );
		else
			check_admin_referer( 'bulk-posts' );

		$posts = empty( sanitize_text_field( $_POST['post_ID'] ) )
			? (array) sanitize_text_field( $_REQUEST['post'] )
			: (array) sanitize_text_field( $_POST['post_ID'] );

		$deleted = 0;

		foreach ( $posts as $post ) {
			$post = WELD_PRESS_Application::get_instance( $post );

			if ( empty( $post ) )
				continue;

			if ( ! current_user_can( 'WELD_PRESS_delete_application', $post->id() ) )
				wp_die( __( 'You are not allowed to delete this item.', 'weld-press' ) );

			if ( ! $post->delete() )
				wp_die( __( 'Error in deleting.', 'weld-press' ) );

			$deleted += 1;
		}

		$query = array();

		if ( ! empty( $deleted ) )
			$query['message'] = 'deleted';

		$redirect_to = add_query_arg( $query, menu_page_url( 'WELDPRESS', false ) );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'validate' == $action && WELD_PRESS_validate_configuration() ) {
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'WELDPRESS-bulk-validate' );

			if ( ! current_user_can( 'WELD_PRESS_edit_applications' ) ) {
				wp_die( __( "You are not allowed to validate configuration.", 'weld-press' ) );
			}

			$application_formWELD_PRESS_Application::find();
			$result = array(
				'timestamp' => current_time( 'timestamp' ),
				'version' => WELDPRESS_VERSION,
				'count_valid' => 0,
				'count_invalid' => 0 );

			foreach ( $application_forms as $application_form ) {
				$config_validator = new WELD_PRESS_ConfigValidator( $application_form );
				$config_validator->validate();

				if ( $config_validator->is_valid() ) {
					$result['count_valid'] += 1;
				} else {
					$result['count_invalid'] += 1;
				}
			}

			WELDPRESS::update_option( 'bulk_validate', $result );

			$query = array(
				'message' => 'validated' );

			$redirect_to = add_query_arg( $query, menu_page_url( 'WELDPRESS', false ) );
			wp_safe_redirect( $redirect_to );
			exit();
		}
	}

	$_GET['post'] = isset( $_GET['post'] ) ? sanitize_text_field( $_GET['post'] ) : '';

	$post = null;

	if ( 'WELDPRESS-new' == $plugin_page ) {
		$post = WELD_PRESS_Application::get_template( array(
			'locale' => isset( $_GET['locale'] ) ? sanitize_text_field( $_GET['locale'] ) : null ) );
	} elseif ( ! empty( $_GET['post'] ) ) {
		$post = WELD_PRESS_Application::get_instance( sanitize_text_field( $_GET['post'] ) );
	}

	$current_screen = get_current_screen();

	$help_tabs = new WELD_PRESS_Help_Tabs( $current_screen );

	if ( $post && current_user_can( 'WELD_PRESS_edit_application', $post->id() ) ) {
		$help_tabs->set_help_tabs( 'edit' );
	} else {
		$help_tabs->set_help_tabs( 'list' );

		if ( ! class_exists( 'WELD_PRESS_Application_List_Table' ) ) {
			require_once WELD_PRESS_PLUGIN_DIR . '/admin/includes/class-contact-forms-list-table.php';
		}

		add_filter( 'manage_' . $current_screen->id . '_columns',
			array( 'WELD_PRESS_Application_List_Table', 'define_columns' ) );

		add_screen_option( 'per_page', array(
			'default' => 20,
			'option' => 'cfseven_applications_per_page' ) );
	}
}

add_action( 'admin_enqueue_scripts', 'WELD_PRESS_admin_enqueue_scripts' );

function WELD_PRESS_admin_enqueue_scripts( $hook_suffix ) {
	if ( false === strpos( $hook_suffix, 'WELDPRESS' ) ) {
		return;
	}

	wp_enqueue_style( 'weld-press-admin',
		WELD_PRESS_plugin_url( 'admin/css/styles.css' ),
		array(), WELDPRESS_VERSION, 'all' );

	if ( WELD_PRESS_is_rtl() ) {
		wp_enqueue_style( 'weld-press-admin-rtl',
			WELD_PRESS_plugin_url( 'admin/css/styles-rtl.css' ),
			array(), WELDPRESS_VERSION, 'all' );
	}

	wp_enqueue_script( 'WELDPRESS-admin',
		WELD_PRESS_plugin_url( 'admin/js/scripts.js' ),
		array( 'jquery', 'jquery-ui-tabs' ),
			WELDPRESS_VERSION, true );

	$args = array(
		'pluginUrl' => WELD_PRESS_plugin_url(),
		'saveAlert' => __(
			"The changes you made will be lost if you navigate away from this page.",
			'weld-press' ),
		'activeTab' => isset( $_GET['active-tab'] )
			? (int) $_GET['active-tab'] : 0,
		'howToCorrectLink' => __( "How to correct this?", 'weld-press' ),
		'configErrors' => array() );

	if ( ( $post = WELD_PRESS_get_current_contact_form() )
	&& current_user_can( 'WELD_PRESS_edit_application', $post->id() )
	&& WELD_PRESS_validate_configuration() ) {
		$config_validator = new WELD_PRESS_ConfigValidator( $post );
		$error_messages = $config_validator->collect_error_messages();

		foreach ( $error_messages as $section => $errors ) {
			$args['configErrors'][$section] = array();

			foreach ( $errors as $error ) {
				$args['configErrors'][$section][] = array(
					'message' => esc_html( $error['message'] ),
					'link' => esc_url( $error['link'] ) );
			}
		}
	}

	wp_localize_script( 'WELDPRESS-admin', '_WELDPRESS', $args );

	add_thickbox();

	wp_enqueue_script( 'WELDPRESS-admin-taggenerator',
		WELD_PRESS_plugin_url( 'admin/js/tag-generator.js' ),
		array( 'jquery', 'thickbox', 'WELDPRESS-admin' ), WELDPRESS_VERSION, true );
}

function WELD_PRESS_admin_management_page() {
	if ( $post = WELD_PRESS_get_current_contact_form() ) {
		$post_id = $post->initial() ? -1 : $post->id();

		require_once WELD_PRESS_PLUGIN_DIR . '/admin/includes/editor.php';
		require_once WELD_PRESS_PLUGIN_DIR . '/admin/edit-contact-form.php';
		return;
	}

	if ( 'validate' == WELD_PRESS_current_action()
	&& WELD_PRESS_validate_configuration()
	&& current_user_can( 'WELD_PRESS_edit_applications' ) ) {
		WELD_PRESS_admin_bulk_validate_page();
		return;
	}

	$list_table = new WELD_PRESS_Application_List_Table();
	$list_table->prepare_items();

?>
<div class="wrap">

<h1><?php
	echo esc_html( __( 'WeldPress Solution', 'weld-press' ) );

	if ( current_user_can( 'WELD_PRESS_edit_applications' ) ) {
		echo ' <a href="' . esc_url( menu_page_url( 'WELDPRESS-new', false ) ) . '" class="add-new-h2">' . esc_html( __( 'Add New', 'weld-press' ) ) . '</a>';
	}

	if ( ! empty( $_REQUEST['s'] ) ) {
		echo sprintf( '<span class="subtitle">'
			. __( 'Search results for &#8220;%s&#8221;', 'weld-press' )
			. '</span>', esc_html( $_REQUEST['s'] ) );
	}
?></h1>

<?php do_action( 'WELD_PRESS_admin_warnings' ); ?>
<?php WELD_PRESS_welcome_panel(); ?>
<?php do_action( 'WELD_PRESS_admin_notices' ); ?>

<form method="get" action="">
	<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
	<?php $list_table->search_box( __( 'Search WeldPress Solution', 'weld-press' ), 'WELDPRESS-application' ); ?>
	<?php $list_table->display(); ?>
</form>

</div>
<?php
}

function WELD_PRESS_admin_bulk_validate_page() {
	$application_forms = WELD_PRESS_Application::find();
	$count = WELD_PRESS_Application::count();

	$submit_text = sprintf(
		_n(
			"Validate %s Application Now",
			"Validate %s WeldPress Solution Now",
			$count, 'weld-press' ),
		number_format_i18n( $count ) );

?>
<div class="wrap">

<h1><?php echo esc_html( __( 'Validate Configuration', 'weld-press' ) ); ?></h1>

<form method="post" action="">
	<input type="hidden" name="action" value="validate" />
	<?php wp_nonce_field( 'WELDPRESS-bulk-validate' ); ?>
	<p><input type="submit" class="button" value="<?php echo esc_attr( $submit_text ); ?>" /></p>
</form>

<?php echo WELD_PRESS_link( __( 'http://ec2-52-56-176-30.eu-west-2.compute.amazonaws.com/configuration-validator-faq/', 'weld-press' ), __( 'FAQ about Configuration Validator', 'weld-press' ) ); ?>

</div>
<?php
}

function WELD_PRESS_admin_add_new_page() {
	$post = WELD_PRESS_get_current_contact_form();

	if ( ! $post ) {
		$post = WELD_PRESS_Application::get_template();
	}

	$post_id = -1;

	require_once WELD_PRESS_PLUGIN_DIR . '/admin/includes/editor.php';
	require_once WELD_PRESS_PLUGIN_DIR . '/admin/edit-contact-form.php';
}

function WELD_PRESS_load_integration_page() {
	$integration = WELD_PRESS_Integration::get_instance();

	if ( isset( $_REQUEST['service'] )
	&& $integration->service_exists( $_REQUEST['service'] ) ) {
		$service = $integration->get_service( $_REQUEST['service'] );
		$service->load( WELD_PRESS_current_action() );
	}

	$help_tabs = new WELD_PRESS_Help_Tabs( get_current_screen() );
	$help_tabs->set_help_tabs( 'integration' );
}

function WELD_PRESS_admin_integration_page() {
	$integration = WELD_PRESS_Integration::get_instance();

?>
<div class="wrap">

<h1><?php echo esc_html( __( 'Integration with Other Services', 'weld-press' ) ); ?></h1>

<?php do_action( 'WELD_PRESS_admin_warnings' ); ?>
<?php do_action( 'WELD_PRESS_admin_notices' ); ?>

<?php
	if ( isset( $_REQUEST['service'] )
	&& $service = $integration->get_service( $_REQUEST['service'] ) ) {
		$message = isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : '';
		$service->admin_notice( $message );
		$integration->list_services( array( 'include' => $_REQUEST['service'] ) );
	} else {
		$integration->list_services();
	}
?>

</div>
<?php
}

/* Misc */

add_action( 'WELD_PRESS_admin_notices', 'WELD_PRESS_admin_updated_message' );

function WELD_PRESS_admin_updated_message() {
	if ( empty( $_REQUEST['message'] ) ) {
		return;
	}

	if ( 'created' == $_REQUEST['message'] ) {
		$updated_message = __( "Application created.", 'weld-press' );
	} elseif ( 'saved' == $_REQUEST['message'] ) {
		$updated_message = __( "Application saved.", 'weld-press' );
	} elseif ( 'deleted' == $_REQUEST['message'] ) {
		$updated_message = __( "Application deleted.", 'weld-press' );
	}

	if ( ! empty( $updated_message ) ) {
		echo sprintf( '<div id="message" class="updated notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $updated_message ) );
		return;
	}

	if ( 'validated' == $_REQUEST['message'] ) {
		$bulk_validate = WELDPRESS::get_option( 'bulk_validate', array() );
		$count_invalid = isset( $bulk_validate['count_invalid'] )
			? absint( $bulk_validate['count_invalid'] ) : 0;

		if ( $count_invalid ) {
			$updated_message = sprintf(
				_n(
					"Configuration validation completed. An invalid contact form was found.",
					"Configuration validation completed. %s invalid WeldPress Solution were found.",
					$count_invalid, 'weld-press' ),
				number_format_i18n( $count_invalid ) );

			echo sprintf( '<div id="message" class="notice notice-warning is-dismissible"><p>%s</p></div>', esc_html( $updated_message ) );
		} else {
			$updated_message = __( "Configuration validation completed. No invalid contact form was found.", 'weld-press' );

			echo sprintf( '<div id="message" class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $updated_message ) );
		}

		return;
	}
}

add_filter( 'plugin_action_links', 'WELD_PRESS_plugin_action_links', 10, 2 );

function WELD_PRESS_plugin_action_links( $links, $file ) {
	if ( $file != WELD_PRESS_PLUGIN_BASENAME )
		return $links;

	$settings_link = '<a href="' . menu_page_url( 'WELDPRESS', false ) . '">'
		. esc_html( __( 'Settings', 'weld-press' ) ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}

add_action( 'WELD_PRESS_admin_warnings', 'WELD_PRESS_old_wp_version_error' );

function WELD_PRESS_old_wp_version_error() {
	$wp_version = get_bloginfo( 'version' );

	if ( ! version_compare( $wp_version, WELD_PRESS_REQUIRED_WP_VERSION, '<' ) ) {
		return;
	}

?>
<div class="notice notice-warning">
<p><?php echo sprintf( __( '<strong>Weld Press %1$s requires WordPress %2$s or higher.</strong> Please <a href="%3$s">update WordPress</a> first.', 'weld-press' ), WELDPRESS_VERSION, WELD_PRESS_REQUIRED_WP_VERSION, admin_url( 'update-core.php' ) ); ?></p>
</div>
<?php
}

add_action( 'WELD_PRESS_admin_warnings', 'WELD_PRESS_not_allowed_to_edit' );

function WELD_PRESS_not_allowed_to_edit() {
	if ( ! $contact_form = WELD_PRESS_get_current_contact_form() ) {
		return;
	}

	$post_id = $contact_form->id();

	if ( current_user_can( 'WELD_PRESS_edit_application', $post_id ) ) {
		return;
	}

	$message = __( "You are not allowed to edit this application.",
		'weld-press' );

	echo sprintf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html( $message ) );
}

add_action( 'WELD_PRESS_admin_misc_pub_section', 'WELD_PRESS_notice_config_errors' );

function WELD_PRESS_notice_config_errors() {
	if ( ! $contact_form = WELD_PRESS_get_current_contact_form() ) {
		return;
	}

	if ( ! WELD_PRESS_validate_configuration()
	|| ! current_user_can( 'WELD_PRESS_edit_application', $contact_form->id() ) ) {
		return;
	}

	$config_validator = new WELD_PRESS_ConfigValidator( $contact_form );

	if ( $count_errors = $config_validator->count_errors() ) {
		$message = sprintf(
			_n(
				'%s configuration error found',
				'%s configuration errors found',
				$count_errors, 'weld-press' ),
			number_format_i18n( $count_errors ) );

		$link = WELD_PRESS_link(
			__( 'http://contactform7.com/configuration-validator-faq/',
				'weld-press' ),
			__( "What's this?", 'weld-press' ),
			array( 'class' => 'external' ) );

		echo sprintf(
			'<div class="misc-pub-section warning">%1$s<br />%2$s</div>',
			$message, $link );
	}
}

add_action( 'WELD_PRESS_admin_warnings', 'WELD_PRESS_notice_bulk_validate_config', 5 );

function WELD_PRESS_notice_bulk_validate_config() {
	if ( ! WELD_PRESS_validate_configuration()
	|| ! current_user_can( 'WELD_PRESS_edit_applications' ) ) {
		return;
	}

	if ( isset( $_GET['page'] ) && 'WELDPRESS' == $_GET['page']
	&& isset( $_GET['action'] ) && 'validate' == $_GET['action'] ) {
		return;
	}

	if ( WELDPRESS::get_option( 'bulk_validate' ) ) { // already done.
		return;
	}

	$link = add_query_arg(
		array( 'action' => 'validate' ),
		menu_page_url( 'WELDPRESS', false ) );

	$link = sprintf( '<a href="%s">%s</a>', $link, esc_html( __( 'Validate Weld Press Configuration', 'weld-press' ) ) );

	$message = __( "Misconfiguration leads to mail delivery failure or other troubles. Validate your WeldPress Solution now.", 'weld-press' );

	echo sprintf( '<div class="notice notice-warning"><p>%s &raquo; %s</p></div>',
		esc_html( $message ), $link );
}
