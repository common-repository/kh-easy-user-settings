<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://knowhalim.com
 * @since             1.0.0
 * @package           Kh_User_Settings
 *
 * @wordpress-plugin
 * Plugin Name:       KH Easy User Settings
 * Plugin URI:        https://knowhalim.com/app/kh-easy-user-settings/
 * Description:       Creates a simple frontend user interface that allows your user to update their details such as email, password and name is the frontend using AJAX. Just use the shortcode [ez_settings] on your frontend.
 * Version:           1.0.0
 * Author:            Halim
 * Author URI:        https://knowhalim.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       kh-user-settings
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'KH_USER_SETTINGS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-kh-user-settings-activator.php
 */
function activate_kh_user_settings() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-kh-user-settings-activator.php';
	Kh_User_Settings_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-kh-user-settings-deactivator.php
 */
function deactivate_kh_user_settings() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-kh-user-settings-deactivator.php';
	Kh_User_Settings_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_kh_user_settings' );
register_deactivation_hook( __FILE__, 'deactivate_kh_user_settings' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-kh-user-settings.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_kh_user_settings() {

	$plugin = new Kh_User_Settings();
	$plugin->run();

}
run_kh_user_settings();
add_filter('ez_settings_label','khalim_label_default',10,1);
add_filter('ez_set_label','khalim_label_set',10,1);
function khalim_label_default($label){
	$new_text = ucfirst(str_replace('_',' ',$label));

	return apply_filters('ez_set_label',$new_text);
}
function khalim_label_set($label){
	if ($label=="User url"){
		return "Website";
	}
	if ($label=="User pass"){
		return "Password";
	}
	return $label;
}
add_filter('ez_settings_description','khalim_description_set',10,2);
function khalim_description_set($text,$field){
	if ($field=="user_url"){
		$text = __("(It needs to start with 'https://' or 'http:')");
	}
	if ($field=="user_pass"){
		$text = __("(Leave blank if no change)");
	}
	return $text;
}

add_shortcode('ez_settings','khalim_simple_frontend');
function khalim_simple_frontend($atts){
	$atts = shortcode_atts(array(
		"user_info"=>"first_name|text,last_name|text,user_email|text,user_url|text,user_pass|password,display_name|text"
	),$atts,'easy-user-settings');
	if (is_user_logged_in()){
	$userstring = esc_html($atts['user_info']);
	if (strpos($userstring,',')!==FALSE and strpos($userstring,'|')!==FALSE){
		
		$fields = explode(',',$userstring);
		$form = '<div class="kh_user_settings">';
		$ajax_field='';
		$ajax_save = '';
		foreach ($fields as $field){
			$user_data = get_userdata(get_current_user_id());
			$item = explode('|',$field);
			$current_value = $user_data->{$item[0]} ? $user_data->{$item[0]}:'';
				$new_text = ucfirst(str_replace('_',' ',$item[0]));
				$form .= '<div class="kh_row_field"><label>'.apply_filters('ez_settings_label',$item[0])." : </label>";
				
				
				if ($item[1]=="select"){
					$form .= "<select id='kh_".$item[0]."'>";
					$options = explode('-',$item[2]);
					if (count($options)>0){
						$opt_form = '';
						foreach ($options as $option){
							$select='';
							if ($current_value!='' and $current_value==$option){
								$select = 'selected';
							}
							
							$opt_form .= '<option value="'.$option.'" '.$select.'>'.$option.'</option>';
						}
						$form .= $opt_form;
					}
					$ajax_field .='var kh_'.$item[0].' =jQuery("#kh_'.$item[0].'").find(":selected").text();';
					
					$form .= "</select>";
				}
		
				if ($item[1]=="text" ){
					$form .= "<input id='kh_".$item[0]."' type='".$item[1]."' value='".$current_value."'/>";
					$ajax_field .='var kh_'.$item[0].' =jQuery("#kh_'.$item[0].'").val();';
				}
				if ($item[1]=="password"){
					$form .= "<input id='kh_".$item[0]."' type='".$item[1]."' value=''/>";
					$ajax_field .='var kh_'.$item[0].' =jQuery("#kh_'.$item[0].'").val();';
				}
				
				if ($item[1]=="textarea"){
					$form .= "<textarea id='kh_".$item[0]."'>'.$current_value.'</textarea>";
					$ajax_field .='var kh_'.$item[0].' =jQuery("#kh_'.$item[0].'").val();';
				}
				$form .= "<span class='ez_settings_description'>".apply_filters('ez_settings_description','',$item[0])."</span>";
				$ajax_save .=  '\''.$item[0].'\':kh_'.$item[0].',';
				$form .= '</div>';
				
				
			
		}
		$ajax_save = rtrim($ajax_save,',');
		$savebtn = '<div class="kh_savebtn"><button id="kh_savebtn">'.__("Save").'</button><span class="kh_loading_bar"></span></div>';
		$fail ='<div class="kh_fail_bar">'.__("Sorry, saving of data failed").'<button class="kh_user_back_okay">Back</button></div>';
		$success ='<div class="kh_success_bar">'.__("Successfully saved settings!").'<button class="kh_user_back_okay">'.__("Back").'</button></div>';
		$form .= $savebtn."</div>".$fail.$success;
	}
	$style='<style>
	.kh_row_field{
	margin-bottom:10px;
	}
	
	.kh_row_field label{
		min-width:150px;
		display: inline-block;
	}
	.kh_success_bar {
    background-color: #a9c528;
    display: inline-block;
    padding: 12px 15px;
    border-radius: 10px;
    color: #000;
}
.kh_fail_bar {
    background-color: #cc1818;
    display: inline-block;
    padding: 12px 15px;
    border-radius: 10px;
    color: #fff5f5;
}
.kh_success_bar,.kh_fail_bar{
	display:none;
	}
	span.ez_settings_description {
    padding: 10px;
    color: #515151;
    font-size: 14px;
    display: inline-block;
}
	</style>';
	
	$ajax="
	<script>
	
	jQuery(\".kh_user_back_okay\").click(function(e){
		jQuery('.kh_user_settings').show();
		jQuery('.kh_success_bar').hide();
		jQuery('.kh_fail_bar').hide();
		jQuery('#kh_savebtn').attr(\"disabled\", false);
	});
jQuery(\"#kh_savebtn\").click(function(e){

	var thisbtn=jQuery(this);
	jQuery('.kh_loading_bar').show();
	
	".$ajax_field."
	thisbtn.attr(\"disabled\", true);
	
    
	var data = { 'action':'kh_ez_settings_save',".$ajax_save."};

    jQuery.ajax({
		url : '".admin_url( 'admin-ajax.php' )."',
		type: \"POST\",
	  	data,
		dataType: \"json\",
		success: function(response) {
			jQuery('.loading_bar').hide();
		   	
			if (response.status==\"success\"){
				jQuery('.kh_success_bar').show();
				jQuery('.kh_user_settings').hide();
			}else{
				jQuery('.kh_fail_bar').show();
				jQuery('.kh_user_settings').hide();
			}
		}
		
    });
	

}
);
</script>";
	
	return $style.$form.$ajax;
	}else{
		return ;
	}
}

add_action('wp_ajax_nopriv_kh_ez_settings_save', 'khalim_ez_settings_do_save');
add_action('wp_ajax_kh_ez_settings_save', 'khalim_ez_settings_do_save');

function khalim_ez_settings_do_save(){
	if (isset($_POST)){
		$uid = get_current_user_id();
		$user_data = array('ID' => $uid);
		foreach ($_POST as $key=>$value){
			if ($key=="user_pass"){
				$user_check = get_userdata(get_current_user_id());
				if (sanitize_text_field($value)!="" and $user_check->user_pass!=sanitize_text_field($value)) {
					$user_data[$key]=sanitize_text_field($value);
				}
			}else{
				$user_data[$key]=sanitize_text_field($value);
			}
		}
		$user_update = wp_update_user( $user_data );

		if ( is_wp_error( $user_update ) ) {
			// There was an error; possibly this user doesn't exist.
			$res= array('status'=>'error');
		} else {
			// Success!
			$res= array('status'=>'success');
		}
		echo json_encode($res);
		die();
	}
	$res= array('status'=>'success');
	echo json_encode($res);
	die();
}




