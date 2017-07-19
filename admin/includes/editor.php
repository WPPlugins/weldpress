<?php

class WELD_PRESS_Editor {

	private $application_form;
	private $panels = array();

	public function __construct( WELD_PRESS_Application $application_form ) {
		$this->application_form = $application_form;
	}

	public function add_panel( $id, $title, $callback ) {
		if ( WELD_PRESS_is_name( $id ) ) {
			$this->panels[$id] = array(
				'title' => $title,
				'callback' => $callback );
		}
	}

	public function display() {
		if ( empty( $this->panels ) ) {
			return;
		}

		echo '<ul id="contact-form-editor-tabs">';

		foreach ( $this->panels as $id => $panel ) {
			echo sprintf( '<li id="%1$s-tab"><a href="#%1$s">%2$s</a></li>',
				esc_attr( $id ), esc_html( $panel['title'] ) );
		}

		echo '</ul>';

		foreach ( $this->panels as $id => $panel ) {
			echo sprintf( '<div class="contact-form-editor-panel" id="%1$s">',
				esc_attr( $id ) );
			call_user_func( $panel['callback'], $this->application_form );
			echo '</div>';
		}
	}
}


function WPFWPloadContainerContent($containerid) {
	// container 61301
	$service_url = 'http://weldpad.com/?containerId='.$containerid;
	$curl = curl_init($service_url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$curl_response = curl_exec($curl);

	if ($curl_response === false) {
		$info = curl_getinfo($curl);
		curl_close($curl);
		die('error occured during curl exec. Additioanl info: ' . var_export($info));
	}
	curl_close($curl);

	echo $curl_response;

}

function WPFWPloadImports($containerid) {
	// container 61310
	$service_url = 'http://weldpad.com/?containerId='.$containerid;
	$curl = curl_init($service_url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$curl_response = curl_exec($curl);

	if ($curl_response === false) {
		$info = curl_getinfo($curl);
		curl_close($curl);
		die('error occured during curl exec. Additioanl info: ' . var_export($info));
	}
	curl_close($curl);

	$xml = new SimpleXMLElement($curl_response);

	/* Search for <a><b><c> */
	$result = $xml->xpath('/x-meta/template[@id="imports"]/script');

	foreach ($result as &$value) {
		wp_enqueue_script($value["data-meta"], $value["src"], array('jquery'), null, false);
	}

	$result = $xml->xpath('/x-meta/template[@id="imports"]/link');

	foreach ($result as &$value) {
		wp_enqueue_style( $value["data-meta"],
				$value["href"],
				array() );
	}

}


function WELD_PRESS_editor_panel_form( $post ) {
	WPFWPloadImports(61408);
//	WPFWPloadContainerContent(61402);
	
?>

<?php
	$tag_generator = WELD_PRESS_TagGenerator::get_instance();
?>

<div ng-controller="AppCtrl" ng-app="MyApp" ng-cloak>
	<div>
     <login>
        <div ng-if="!!oSession">
          <p>LoggdIn</p>
          <p>userdata:{{oSession}}</p>
        </div>
        <div ng-if="!oSession">
          Not LoggedIn

          <fblogin>
            <a ng-click="FBPopup()"><button>Login with Facebook!</button> </a>
          </fblogin>

        </div>

      </login>
		<div style="padding: 10px;border: 1px solid #cdc1c1;display: inline-block;width: -webkit-calc(100% - 175px);width:    -moz-calc(100% - 175px);width:calc(100% - 175px);">
           <div  id="cloneParameters">
              <a ng-disabled="caretselected.length==0 || oSession==undefined || cloneMeta.directiveName.length==0" ng-click="actions.create()" class="button-primary">
                Clone
              </a>
              	<input ng-model="cloneMeta.directiveName" class="ng-pristine ng-valid md-input ng-not-empty ng-touched" id="input_0" aria-invalid="false">
              <a ng-disabled="caretselected.length!=0 || oSession==undefined" ng-click="actions.create()" class="button-primary">
                Create
              </a>
			  <a ng-disabled="caretselected.length==0" target="_blank" href="http://weldpad.com/?containerId=47901&editcontainer={{caretselected}}" class="button-primary">View {{oContextual.tag}}</a>
           </div>
		
			<textarea id="WELDPRESS-form" name="WELDPRESS-form" cols="100" rows="100" style="display: inline-block;" class="large-text code" data-config-field="form.body"><?php echo esc_textarea( $post->prop( 'form' ) ); ?></textarea>
		</div>
		<div style="display: inline-block;width: 173px;position: absolute;">
			<div style="background: rgba(209, 209, 209, 0.46);padding: 3px;border: 2px solid #cfcfcf;border-radius: 2px;">
              <input style="width:134px" type="text" ng-change="browseDirectives()" id="testInput" ng-model="searchTerm" md-autofocus="">
              
              <div id="browseres"  style="display: none;border: 1px solid;position: absolute;background-color: rgb(200, 198, 211);box-shadow: 0px 2px 12px 19px rgba(142, 163, 175, 0.68);padding: 2px;height: 600px;overflow: auto;">
                <div ng-repeat="asscontainer in browseRes">
                  <loadcontainermeta container="asscontainer.container">
	                  <div style="box-shadow: 0px 2px 9px 3px rgba(5, 7, 8, 0.34);padding: 6px;">
	                    <div>
	                      <div layout="row">
	                        <img src="{{containermeta.metaimage}}" width="80px" height="54px"style="padding: 5px;"/>
	                        <h2>{{containermeta.tagname}}</h2>
	                      </div>
	                      <a href="http://weldpad.com/?containerId=47901&editcontainer={{containermeta.container}}" target="_blank">{{containermeta.shortdesc}}</a>
	                    </div>
	                    <div layout="row" style="padding: 2px;">
						  <a class="button-primary" value="Info" target="_blank" href="http://weldpad.com/?containerId=57019#displaycomponent={{containermeta.container}}">Info</a>
						  <a class="button-primary" ng-click="embedDirective()">Embed</a>
	                    </div>
	                  </div>                    
                  </loadcontainermeta>
                
                </div>
                <a class="button-primary" style="margin: 10px;float: right;color: black;" value="Close" ng-click="hideBrowsResSet()">Close</a>
                
              </div>
			
	           <loadassosiatedcontainer container="{{oContextual.container}}">
	           <div style="height: 714px;overflow: overlay;width: 160px;box-shadow: 0px 2px 9px 3px rgba(67, 171, 226, 0.68);padding: 2px;">
	              <div ng-repeat="asscontainer in assosiatedContainer">
	                <loadcontainermeta container="asscontainer">
	
	                  <div ng-if="containermeta.tagname" style="box-shadow: 0px 1px 1px 1px rgba(67, 171, 226, 0.28);padding: 2px;width: 135px;">
	                    <div>
	                      <div layout="row">
	                        <img src="{{containermeta.metaimage}}" width="80px" height="54px"style="padding: 5px;"/>
	                        <b>{{containermeta.tagname}}</b>
	                      </div>
	                      <a href="http://weldpad.com/?containerId=47901&editcontainer={{containermeta.container}}" target="_blank">{{containermeta.shortdesc}}</a>
	                    </div>
	                    <div layout="row" style="padding: 2px;">
						  <a class="button-primary" value="Info" target="_blank" href="http://weldpad.com/?containerId=57019#displaycomponent={{containermeta.container}}">Info</a>
						  <a class="button-primary" ng-click="embedDirective()">Embed</a>
	                    </div>
	                  </div>                    
	
	                </loadcontainermeta>
	              	</div>
	              </div>
	            </loadassosiatedcontainer>
			</div>
	            
		</div>
	
	</div>
  
  
</div>
<?php
}

function WELD_PRESS_editor_panel_imports( $post ) {
	WELD_PRESS_editor_box_imports( $post );

	echo '<br class="clear" />';
}

function WELD_PRESS_editor_box_imports( $post, $args = '' ) {
	$args = wp_parse_args( $args, array(
		'id' => 'WELDPRESS-mail',
		'name' => 'mail',
		'title' => __( 'Mail', 'weld-press' ),
		'use' => null ) );

	$id = esc_attr( $args['id'] );

	$mail = wp_parse_args( $post->prop( $args['name'] ), array(
		'active' => false, 'recipient' => '', 'sender' => '',
		'subject' => '', 'body' => '', 'additional_headers' => '',
		'attachments' => '', 'use_html' => false, 'exclude_blank' => false ) );

?>
<div class="contact-form-editor-box-mail" id="<?php echo $id; ?>">


<fieldset>

<legend>

<?php $post->suggest_mail_tags( $args['name'] ); ?></legend>
		<label for="<?php echo $id; ?>-body"><?php echo esc_html( __( 'Script', 'weld-press' ) ); ?></label>

		<textarea id="<?php echo $id; ?>-body" name="<?php echo $id; ?>-body" cols="100" rows="18" class="large-text code" data-config-field="<?php echo sprintf( '%s.body', esc_attr( $args['name'] ) ); ?>"><?php echo esc_textarea( $mail['body'] ); ?></textarea>


</fieldset>
</div>
<?php
}

function WELD_PRESS_editor_panel_messages( $post ) {
	$messages = WELD_PRESS_messages();

	if ( isset( $messages['captcha_not_match'] )
	&& ! WELD_PRESS_use_really_simple_captcha() ) {
		unset( $messages['captcha_not_match'] );
	}

?>
<h2><?php echo esc_html( __( 'Messages', 'weld-press' ) ); ?></h2>
<fieldset>
<legend><?php echo esc_html( __( 'Edit messages used in the following situations.', 'weld-press' ) ); ?></legend>
<?php

	foreach ( $messages as $key => $arr ) {
		$field_name = 'WELDPRESS-message-' . strtr( $key, '_', '-' );

?>
<p class="description">
<label for="<?php echo $field_name; ?>"><?php echo esc_html( $arr['description'] ); ?><br />
<input type="text" id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" class="large-text" size="70" value="<?php echo esc_attr( $post->message( $key, false ) ); ?>" data-config-field="<?php echo sprintf( 'messages.%s', esc_attr( $key ) ); ?>" />
</label>
</p>
<?php
	}
?>
</fieldset>
<?php
}

function WELD_PRESS_editor_panel_additional_settings( $post ) {
	$desc_link = WELD_PRESS_link(
		__( 'http://contactform7.com/additional-settings/', 'weld-press' ),
		__( 'Additional Settings', 'weld-press' ) );
	$description = __( "You can add customization code snippets here. For details, see %s.", 'weld-press' );
	$description = sprintf( esc_html( $description ), $desc_link );

?>
<h2><?php echo esc_html( __( 'Additional Settings', 'weld-press' ) ); ?></h2>
<fieldset>
<legend><?php echo $description; ?></legend>
<textarea id="WELDPRESS-additional-settings" name="WELDPRESS-additional-settings" cols="100" rows="8" class="large-text" data-config-field="additional_settings.body"><?php echo esc_textarea( $post->prop( 'additional_settings' ) ); ?></textarea>
</fieldset>
<?php
}
