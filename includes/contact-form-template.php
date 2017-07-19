<?php

class WELD_PRESS_ApplicationTemplate {

	public static function get_default( $prop = 'form' ) {
		if ( 'form' == $prop ) {
			$template = self::form();
		} elseif ( 'mail' == $prop ) {
			$template = self::mail();
		} elseif ( 'mail_2' == $prop ) {
			$template = self::mail_2();
		} elseif ( 'messages' == $prop ) {
			$template = self::messages();
		} else {
			$template = null;
		}

		return apply_filters( 'WELD_PRESS_default_template', $template, $prop );
	}

	public static function form() {
		$template = sprintf(
			'
<div ng-controller="AppCtrl" ng-app="MyApp">
    <h4>Your - Starter Kit.</h4>
  
    
</div>' );

		return trim( $template );
	}

	public static function mail() {
		$template = array(
			'subject' => sprintf(
				_x( '%1$s "%2$s"', 'mail subject', 'weld-press' ),
				get_bloginfo( 'name' ), '[your-subject]' ),
			'sender' => sprintf( '[your-name] <%s>', self::from_email() ),
			'body' =>
				sprintf(
						'
<x-meta tagname="xxx">
  <template>

  </template>

  <template id="imports">
      <script data-meta="61300" src="https://www.weldpad.com/globalvers.js?containerId=61300"></script>

    <script data-meta="angmin" src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.5/angular.min.js"></script>
    <script data-meta="anganim" src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.5/angular-animate.min.js"></script>
    <script data-meta="angaria" src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.5/angular-aria.min.js"></script>

    <script data-meta="60515" src="https://www.weldpad.com/starterkit.js?containerId=60515"></script> 
    
  
                            
  </template>
</x-meta>'
						),
			'recipient' => get_option( 'admin_email' ),
			'additional_headers' => 'Reply-To: [your-email]',
			'attachments' => '',
			'use_html' => 0,
			'exclude_blank' => 0 );

		return $template;
	}

	public static function mail_2() {
		$template = array(
			'active' => false,
			'subject' => sprintf(
				_x( '%1$s "%2$s"', 'mail subject', 'weld-press' ),
				get_bloginfo( 'name' ), '[your-subject]' ),
			'sender' => sprintf( '%s <%s>',
				get_bloginfo( 'name' ), self::from_email() ),
			'body' =>
				__( 'Message Body:', 'weld-press' )
					. "\n" . '[your-message]' . "\n\n"
				. '--' . "\n"
				. sprintf( __( 'This e-mail was sent from a contact form on %1$s (%2$s)',
					'weld-press' ), get_bloginfo( 'name' ), get_bloginfo( 'url' ) ),
			'recipient' => '[your-email]',
			'additional_headers' => sprintf( 'Reply-To: %s',
				get_option( 'admin_email' ) ),
			'attachments' => '',
			'use_html' => 0,
			'exclude_blank' => 0 );

		return $template;
	}

	public static function from_email() {
		$admin_email = get_option( 'admin_email' );
		$sitename = strtolower( sanitize_text_field( $_SERVER['SERVER_NAME'] ) );

		if ( WELD_PRESS_is_localhost() ) {
			return $admin_email;
		}

		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		if ( strpbrk( $admin_email, '@' ) == '@' . $sitename ) {
			return $admin_email;
		}

		return 'wordpress@' . $sitename;
	}

	public static function messages() {
		$messages = array();

		foreach ( WELD_PRESS_messages() as $key => $arr ) {
			$messages[$key] = $arr['default'];
		}

		return $messages;
	}
}

function WELD_PRESS_messages() {
	$messages = array(
		'mail_sent_ok' => array(
			'description'
				=> __( "Sender's message was sent successfully", 'weld-press' ),
			'default'
				=> __( "Thank you for your message. It has been sent.", 'weld-press' )
		),

		'mail_sent_ng' => array(
			'description'
				=> __( "Sender's message failed to send", 'weld-press' ),
			'default'
				=> __( "There was an error trying to send your message. Please try again later.", 'weld-press' )
		),

		'validation_error' => array(
			'description'
				=> __( "Validation errors occurred", 'weld-press' ),
			'default'
				=> __( "One or more fields have an error. Please check and try again.", 'weld-press' )
		),

		'spam' => array(
			'description'
				=> __( "Submission was referred to as spam", 'weld-press' ),
			'default'
				=> __( "There was an error trying to send your message. Please try again later.", 'weld-press' )
		),

		'accept_terms' => array(
			'description'
				=> __( "There are terms that the sender must accept", 'weld-press' ),
			'default'
				=> __( "You must accept the terms and conditions before sending your message.", 'weld-press' )
		),

		'invalid_required' => array(
			'description'
				=> __( "There is a field that the sender must fill in", 'weld-press' ),
			'default'
				=> __( "The field is required.", 'weld-press' )
		),

		'invalid_too_long' => array(
			'description'
				=> __( "There is a field with input that is longer than the maximum allowed length", 'weld-press' ),
			'default'
				=> __( "The field is too long.", 'weld-press' )
		),

		'invalid_too_short' => array(
			'description'
				=> __( "There is a field with input that is shorter than the minimum allowed length", 'weld-press' ),
			'default'
				=> __( "The field is too short.", 'weld-press' )
		)
	);

	return apply_filters( 'WELD_PRESS_messages', $messages );
}
