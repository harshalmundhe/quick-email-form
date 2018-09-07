<?php

/*
Plugin Name: Quick Email Form 
Plugin URI: http://mumbaifreelancer.com/wordpress-quick-email-form/
Description: Form for adding quick contact form for sending emails.
Author: Mumbai Freelancer Team
Author URI: http://mumbaifreelancer.com
Version: 1.0
Uses:
[quick_email_form qef_toemail='xxx@example.com' qef_subject='xxx']

*/





class DomainContactForm{
	private $qef_version;
	/*
	 * Using constructor function to initailize all data
	 *
	 */
	public function __construct(){
		
		$this->qef_version = "1.0";
		register_activation_hook( __FILE__, array($this,'qef_addversion'));
		add_action('admin_menu', array($this, 'qef_adminmenu'));
		add_shortcode( 'quick_email_form', array($this,'qef_main') );
		add_action( 'wp_ajax_qef_main', array($this,'qef_sendmail') );
		add_action( 'wp_ajax_nopriv_qef_main', array($this,'qef_sendmail') );
		add_action('wp_head', array($this,'qef_submit') );
		add_action('admin_head', array($this,'qef_scripts') );
		add_filter("mce_external_plugins", array($this,"qef_enqueue_plugin_scripts"));
		add_filter("mce_buttons",  array($this,"qef_register_buttons_editor"));
	}

	/*
	 * Function qef_addversion()
	 * Add the latest version of the plugin to the db
	 *
	 */
	public function qef_addversion(){
		update_option("qef_version",$this->qef_version);
	}
	
	/*
	 * Function qef_adminmenu()
	 * Function to develop admin menu for setting page
	 *
	 */
	public function qef_adminmenu(){
		add_menu_page('Quick Email Form', //page title
            'Quick Email Form', //menu title
            'manage_options', //capabilities
            'quick-email-form', //menu slug
            array($this,'qef_recptchaform') //function
        );
	}
	
	function qef_enqueue_plugin_scripts($plugin_array)
	{
		//enqueue TinyMCE plugin script with its ID.
		$plugin_array["qef_shortcode_btn"] =  plugin_dir_url(__FILE__) . "index.js";
		return $plugin_array;
	}
	
	function qef_register_buttons_editor($buttons)
	{
		//register buttons with their id.
		array_push($buttons, "qef_shortcode");
		return $buttons;
	}
	
	/*
	 * Function qef_recptchaform()
	 * Function to create admin setting form
	 *
	 */
	public function qef_recptchaform(){
		$error = $success = "";
		$qef_settings = $this->qef_getsettings();
		if(isset($_POST['qef_createrecaptcha'])){
			
			if(trim($_POST['qef_from'])  == "" or !filter_var($_POST['qef_from'],FILTER_VALIDATE_EMAIL)){
				$error = "Please enter from email address";
			}
			
			if($error == ""){
				if(isset($_POST['qef_enable_recaptcha'])){
					if(trim($_POST['qef_recaptcha_key']) == ""){
						$error = "Please enter Recaptcha key";
					}elseif(trim($_POST['qef_recaptcha_secret'])  == ""){
						$error = "Please enter Recaptcha secret";
					}else{
						$recaptcha_det['key'] = trim($_POST['qef_recaptcha_key']);
						$recaptcha_det['secret'] = trim($_POST['qef_recaptcha_secret']);
						
						if(isset($qef_settings) && $qef_settings==""){
							add_option("qef_recaptcha_det",serialize($recaptcha_det));
							$success = "Setings saved successfully";
						}else{
							update_option( "qef_recaptcha_det", serialize($recaptcha_det));
							$success = "Setings updated successfully";
						}
						$qef_settings = $recaptcha_det;
					}
				}else{
					delete_option( "qef_recaptcha_det");
					$success = "Setings updated successfully";
					$qef_settings = [];
				}
				update_option( "qef_from_email", $_POST['qef_from']);
				update_option( "qef_default_to_email", $_POST['qef_default_to']);
				$qef_settings['qef_from'] = $_POST['qef_from'];
				$qef_settings['qef_default_to'] = $_POST['qef_default_to'];
			}
		}
		
		?>
		 <h1><?php _e( 'Quick Email Form - Settings', 'qef_message' ); ?></h1>
		 <?php
		 if($error != ""){
		 ?>
			<div id="setting-error-settings_updated" class="error settings-error notice is-dismissible"> 
			<p><strong><?php _e($error,'qef_message');?>.</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>
		 <?php
		 }
		 if($success != ""){
		 ?>
		 <div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"> 
			<p><strong><?php _e($success,'qef_message');?>.</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>
		 <?php
		 }
		 ?> 
		 <form method="post" action="">
			<table class='form-table'>
				<tr>
					<th scope="row">Enable Recaptcha</th>
					<td><input type="checkbox" name="qef_enable_recaptcha" id="qef_enable_recaptcha"
					<?php if(isset($qef_settings['key']) && !empty($qef_settings)){
						echo "checked='checked'";
					}?>
					<br>
					<p class="description">Please create keys for <a href="https://developers.google.com/recaptcha/" target="_blank">google recaptcha v2.</a></p>
					</td>
				</tr>
				<tr class="qef_set_row <?php if(!isset($qef_settings['key']) or empty($qef_settings['key'])){echo "qef_hide";}?>">
					<th scope="row">Recaptcha Key</th>
					<td><input type="text" class="regular-text code" name="qef_recaptcha_key"
					<?php if(isset($qef_settings['key']) && !empty($qef_settings)){
						echo "value='".$qef_settings['key']."'";
					}?>
					></td>
				</tr>
				<tr class="qef_set_row  <?php if(!isset($qef_settings['key']) or empty($qef_settings['key'])){echo "qef_hide";}?>">
					<th scope="row">Recaptcha Secret</th>
					<td><input type="text" class="regular-text code" name="qef_recaptcha_secret"
					<?php if(isset($qef_settings['secret']) && !empty($qef_settings)){
						echo "value='".$qef_settings['secret']."'";
					}?>
					></td>
				</tr>
				<tr>
					<th scope="row">Email From</th>
					<td><input type="text" class="regular-text code" name="qef_from" id="qef_from"
					<?php if(isset($qef_settings['qef_from']) && $qef_settings['qef_from']!=""){
						echo "value='".$qef_settings['qef_from']."'";
					}else{
						echo "value='".get_option('admin_email')."'";
					}?>
					<br>
					<p class="description">Sender Email Address.</br>NOTE: If left blank, your wordpress admin email address will be used.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Default Destination Email</th>
					<td><input type="text" class="regular-text code" name="qef_default_to" id="qef_default_to"
					<?php if(isset($qef_settings['qef_default_to']) && $qef_settings['qef_default_to']!=""){
						echo "value='".$qef_settings['qef_default_to']."'";
					}elseif(isset($qef_settings['qef_default_to']) && $qef_settings['qef_default_to']==""){
						echo "value=''";
					}else{
						echo "value='".get_option('admin_email')."'";
					}?>
					<br>
					<p class="description">Default Destination Email Address. If not passed in shortcode.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><input name="qef_createrecaptcha" id="qef_createrecaptcha" class="button button-primary" value="Save" type="submit"></th>
					<td></td>
				</tr>
			</table>
		 </form>
		 <h3>Description</h3>
		 <p>Quick Email Form is a simple email sending contact form that can be added to any post or page using a shortcode. </br>You can pass destination email address and email subject via short code parameters.</p>
		 <h3>Shortcode</h3>
		 <p>The use of the plugin is very simple just add the shortcode in you page or post.</br>
		 <kbd>[quick_email_form qef_toemail='YOUR DESTINATION EMAIL ADDRESS' qef_subject='YOUR EMAIL SUBJECT LINE']</kbd></br>
		 <u>EXAMPLE:</u> <strong>[quick_email_form qef_toemail='xxx@example.com' qef_subject='NEW EMAIL']</strong>
		 </p>
		<?php
	}
	/*
	 * Function qef_getsettings()
	 * Function get the setting saved in db
	 *
	 */
	
	public function qef_getsettings(){
		$site_set = array();
		
		$qef_entered = get_option("qef_recaptcha_det");
		$qef_from =  get_option("qef_from_email");
		
		if(isset($qef_from) && $qef_from!=""){
			$site_set['qef_from'] = $qef_from;
		}
		$qef_default_to = get_option("qef_default_to_email");
		if(isset($qef_default_to) && $qef_default_to!=""){
			$site_set['qef_default_to'] = $qef_default_to;
		}
		
		if(isset($qef_entered) && $qef_entered!=""){
			$site_cap_set = unserialize($qef_entered);
			$site_set = array_merge($site_set,$site_cap_set);
		}
		return $site_set;
	}
	
	/*
	 * Function qef_sendmail()
	 * Function to send email to user passed in the shortcode
	 *
	 */
	public function qef_sendmail(){
		if(isset($_POST['qef_submit'])){
			$response = array();
			$qef_settings = $this->qef_getsettings();
			$error = "";
			if(trim($_POST['qef_name']) == ""){
				$error = "Please enter a valid name";
			}
			elseif(trim($_POST['qef_email']) == "" or !filter_var($_POST['qef_email'],FILTER_VALIDATE_EMAIL)){
				$error = "Please enter a valid email";
			}
			elseif(trim($_POST['qef_desc']) == ""){
				$error = "Please enter a valid message";
			}elseif(isset($_POST['g-recaptcha-response'])){
				
				$captcha=$_POST['g-recaptcha-response'];
				$gresponse=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".$qef_settings['secret']."&response=".$captcha."&remoteip=".$_SERVER['REMOTE_ADDR']);
				$g_response = json_decode($gresponse);
				
				if($g_response->success!==true) {
					$error = "Please enter a valid captcha";
				}
			}
			if($error ==""){
				
				$message = "From: ".$_POST['qef_name']."\r\n";
				$message .= "Email: ".$_POST['qef_email']."\r\n";
				$message .= "Message:\r\n".$_POST['qef_desc'];
				$headers = 'From: '.$_POST['qef_admin_email'] . "\r\n" .
				'Reply-To: '.$_POST['qef_email'] . "\r\n" ;
				wp_mail($_POST['qef_toemail'],$_POST['qef_subject'],$message,$headers );
				$response['msg'] = "Email sent successfully";
				$response['success'] = true;
				
			}else{
				$response['msg'] = $error;
				$response['error'] = true;
			}
			
			
			echo json_encode($response);
			exit;
		}
	}
	/*
	 * Function qef_main()
	 * Function main function to execute the shortcode and display the form
	 *
	 */

	public function qef_main( $atts ){
		
		$html = "";
		$qef_settings = $this->qef_getsettings();
		
		$admin_email = get_option('admin_email');
		if(isset($atts['qef_toemail'])){
			$to = $atts['qef_toemail'];
		}elseif(isset($qef_settings['qef_default_to']) && $qef_settings['qef_default_to']!=""){
			$to = $qef_settings['qef_default_to'];
		}else{
			$to = $admin_email;
		}
		
		if(isset($qef_settings['qef_from']) && $qef_settings['qef_from']!=""){
			$admin_email = $qef_settings['qef_from'];
		}
		
		$subject = "";
		if(isset($atts['qef_subject'])){
			$subject = $atts['qef_subject'];
		}else{
			$subject = "New Enquiry";
		}
		
		$html .= "<form method='post' action='' class='qef_form'>";
		$html .= "<input type='hidden' name='qef_toemail' class='qef_toemail' value='".$to."'>";
		$html .= "<input type='hidden' name='qef_subject' class='qef_subject' value='".$subject."'>";
		$html .= "<input type='hidden' name='qef_admin_email' class='qef_admin_email' value='".$admin_email."'>";
		$html .= "<lable> Name </lable>
				<input type='text' name='qef_name' class='qef_name qef_marbottm' requried='required'>";
		$html .= "<lable> Email </lable>
				<input type='text' name='qef_email' class='qef_email qef_marbottm' requried='required'>";
		$html .= "<lable> Message </lable>
				<textarea name='qef_desc' class='qef_desc qef_marbottm' requried='required'></textarea>";
		if(isset($qef_settings['key']) && !empty($qef_settings)){
			$html.="<div class='g-recaptcha qef_marbottm' data-sitekey='".$qef_settings['key']."'></div>";
		}
		$html .="<input type='button' name='qef_submit' value='Send' onclick='qef_submitform();'>";
		$html .=  "</form>";
		$html .= "<div class='qef_spinner' style='display:none'>Sending...</div>";
		$html .= "<div class='qef_msg'></div>";
		
		return $html;
	}
	/*
	 * Function qef_submit()
	 * Function to send email using wpajax 
	 *
	 */
	
	public function qef_submit(){
		$admin_url = admin_url("admin-ajax.php");
		?>
		 <script src='https://www.google.com/recaptcha/api.js'></script>
		<script type="text/javascript">
		function qef_submitform() {
			var data = {
			'qef_name': jQuery('.qef_name').val(),
            'qef_email': jQuery('.qef_email').val(),
            'qef_desc': jQuery('.qef_desc').val(),
            'g-recaptcha-response': jQuery('.g-recaptcha-response').val(),
			'qef_toemail': jQuery('.qef_toemail').val(),
			'qef_subject': jQuery('.qef_subject').val(),
			'qef_admin_email': jQuery('.qef_admin_email').val(),
			'qef_submit':true,
            'action':'qef_main'
			};
			jQuery(".qef_spinner").show();
			jQuery.post("<?php echo $admin_url; ?>", data, function(response) {
				response = JSON.parse(response);
				jQuery(".qef_spinner").hide();
				if(response.success){
					jQuery(".qef_msg").html("<font color='green'>"+response.msg+"</font>");
					jQuery('.qef_name').val("");
					jQuery('.qef_email').val("");
					jQuery('.qef_desc').val("");
				}else{
					jQuery(".qef_msg").html("<font color='red'>"+response.msg+"</font>");
				}
				grecaptcha.reset();
			});
		}
		</script>
		<style>
			.qef_marbottm{
				margin-bottom: 20px;
			}
		</style>
		<?php
	}
	
	/*
	 * Function qef_scripts()
	 * Function to show hide settings
	 *
	 */
	public function qef_scripts(){
		?>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery("#qef_enable_recaptcha").on("click",function(){
					if(jQuery("#qef_enable_recaptcha").is(":checked")){
						jQuery(".qef_set_row").removeClass("qef_hide");
					}else{
						jQuery(".qef_set_row").addClass("qef_hide");
					}
				});
			});
		</script>
		<style>
			.qef_hide{
				display: none;
			}
		</style>
		<?php
	}
}

new DomainContactForm();





