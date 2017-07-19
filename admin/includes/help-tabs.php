<?php

class WELD_PRESS_Help_Tabs {

	private $screen;

	public function __construct( WP_Screen $screen ) {
		$this->screen = $screen;
	}

	public function set_help_tabs( $type ) {
		switch ( $type ) {
			case 'list':
				$this->screen->add_help_tab( array(
					'id' => 'list_overview',
					'title' => __( 'Overview', 'weld-press' ),
					'content' => $this->content( 'list_overview' ) ) );

				$this->screen->add_help_tab( array(
					'id' => 'list_available_actions',
					'title' => __( 'Available Actions', 'weld-press' ),
					'content' => $this->content( 'list_available_actions' ) ) );

				$this->sidebar();

				return;
			case 'edit':
				$this->screen->add_help_tab( array(
					'id' => 'edit_overview',
					'title' => __( 'Overview', 'weld-press' ),
					'content' => $this->content( 'edit_overview' ) ) );

				$this->screen->add_help_tab( array(
					'id' => 'edit_form_tags',
					'title' => __( 'Form-tags', 'weld-press' ),
					'content' => $this->content( 'edit_form_tags' ) ) );

				$this->screen->add_help_tab( array(
					'id' => 'edit_mail_tags',
					'title' => __( 'Mail-tags', 'weld-press' ),
					'content' => $this->content( 'edit_mail_tags' ) ) );

				$this->sidebar();

				return;
			case 'integration':
				$this->screen->add_help_tab( array(
					'id' => 'integration_overview',
					'title' => __( 'Overview', 'weld-press' ),
					'content' => $this->content( 'integration_overview' ) ) );

				$this->sidebar();

				return;
		}
	}

	private function content( $name ) {
		$content = array();

		$content['list_overview'] = '<p>' . __( "On this screen, you can manage WeldPress Solution provided by Weld Press. You can manage an unlimited number of WeldPress Solution. Each application has a unique ID and Weld Press shortcode ([weld-press ...]). To insert an application into a post or a text widget, insert the shortcode into the target.", 'weld-press' ) . '</p>';

		$content['list_available_actions'] = '<p>' . __( "Hovering over a row in the WeldPress Solution list will display action links that allow you to manage your contact form. You can perform the following actions:", 'weld-press' ) . '</p>';
		$content['list_available_actions'] .= '<p>' . __( "<strong>Edit</strong> - Navigates to the editing screen for that appliction. You can also reach that screen by clicking on the application title.", 'weld-press' ) . '</p>';
		$content['list_available_actions'] .= '<p>' . __( "<strong>Duplicate</strong> - Clones that application. A cloned application inherits all content from the original, but has a different ID.", 'weld-press' ) . '</p>';

		$content['edit_overview'] = '<p>' . __( "On this screen, you can edit a WeldPad application. An application is comprised of the following components:", 'weld-press' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( "<strong>Title</strong> is the title of an application. This title is only used for labeling an application, and can be edited.", 'weld-press' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( "<strong>Form</strong> is a content of HTML form. You can use arbitrary HTML, which is allowed edit the application.", 'weld-press' ) . '</p>';
		$content['edit_overview'] .= '<p>' . __( "<strong>Imports</strong> List if imports to load in order to support the application.", 'weld-press' ) . '</p>';


		$content['integration_overview'] = '<p>' . __( "On this screen, you can manage services that are available through Weld Press. Using API will allow you to collaborate with any services that are available.", 'weld-press' ) . '</p>';
		$content['integration_overview'] .= '<p>' . __( "You may need to first sign up for an account with the service that you plan to use. When you do so, you would need to authorize Weld Press to access the service with your account.", 'weld-press' ) . '</p>';
		$content['integration_overview'] .= '<p>' . __( "Any information you provide will not be shared with service providers without your authorization.", 'weld-press' ) . '</p>';

		if ( ! empty( $content[$name] ) ) {
			return $content[$name];
		}
	}

	public function sidebar() {
		$content = '<p><strong>' . __( 'For more information:', 'weld-press' ) . '</strong></p>';
		$content .= '<p>' . WELD_PRESS_link( __( 'http://ec2-52-56-176-30.eu-west-2.compute.amazonaws.com/getting-started/', 'weld-press' ), __( 'Getting started', 'weld-press' ) ) . '</p>';
		$content .= '<p>' . WELD_PRESS_link( __( 'http://ec2-52-56-176-30.eu-west-2.compute.amazonaws.com/blog/', 'weld-press' ), __( 'FAQ', 'weld-press' ) ) . '</p>';
		$content .= '<p>' . WELD_PRESS_link( __( 'http://ec2-52-56-176-30.eu-west-2.compute.amazonaws.com/', 'weld-press' ), __( 'Support', 'weld-press' ) ) . '</p>';

		$this->screen->set_help_sidebar( $content );
	}
}
