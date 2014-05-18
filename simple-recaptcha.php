<?php
/*
	Plugin Name: Simple reCAPTCHA
	Plugin URI: http://www.wpmission.com/
	Description: A simple implementation of Google's reCAPTCHA suitable for any custom form.
	Author: Chris Dillon
	Version: 0.1
	Author URI: http://www.wpmission.com/
	Text Domain: wpmsrc
	Requires: 3.0 or higher
	License: GPLv3 or later

	Copyright 2014  Chris Dillon  chris@wpmission.com

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


function wpmsrc_init() {
	load_plugin_textdomain( 'wpmsrc', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	// Add hooks
	add_action( 'wpmsrc_display', 'wpmsrc_display' );
	// add_filter( 'wpmsrc_check', 'wpmsrc_check' );
}
add_action( 'init', 'wpmsrc_init' );


/*
	Plugin list action links
*/
function wpmsrc_action_links( $links, $file ) {
	$this_plugin = plugin_basename(__FILE__);

	if ( $file == $this_plugin ){
		$settings_link = '<a href="options-general.php?page=simple-recaptcha.php">' . __( 'Settings', 'wpmsrc' ) . '</a>';
		array_unshift( $links, $settings_link );
	}
	return $links;
}
add_filter( 'plugin_action_links', 'wpmsrc_action_links', 10, 2 );


/*
	Uninstall
*/
function wpmsrc_delete_options() {
	delete_option( 'wpmsrc_options' );
	delete_site_option( 'wpmsrc_options' );
}
register_uninstall_hook( __FILE__, 'wpmsrc_delete_options' );


/*
	Check WordPress version
*/
function wpmsrc_version_check() {
	global $wp_version;
	$wpmsrc_plugin_info = get_plugin_data( __FILE__, false );
	$wpmsrc_options = wpmsrc_get_options();
	$require_wp = "3.0";  // Wordpress at least requires version
	$plugin = plugin_basename( __FILE__ );
	
	if ( version_compare( $wp_version, $require_wp, "<" ) ) {
		if ( is_plugin_active( $plugin ) ) {
			deactivate_plugins( $plugin );
			wp_die( "<strong>" . $wpmsrc_plugin_info['Name'] . " </strong> " . __( 'requires', 'wpmsrc' ) . " <strong>WordPress " . $require_wp . "</strong> " . __( 'or higher so it has been deactivated. Please upgrade WordPress and try again.', 'wpmsrc') . "<br /><br />" . __( 'Back to the WordPress', 'wpmsrc') . " <a href='" . get_admin_url( null, 'plugins.php' ) . "'>" . __( 'Plugins page', 'wpmsrc') . "</a>." );
		}
	}
}

/*
	Get options
*/
function wpmsrc_get_options() {
	if ( is_multisite() ) {
		return get_site_option( 'wpmsrc_options' );
	} else {
		return get_option( 'wpmsrc_options' );
	}
}


  /*===========*/
 /*   Admin   */
/*===========*/

/*
	Options page
*/
function wpmsrc_add_options_page() {
	add_options_page( 'Simple reCAPTCHA', 'Simple reCAPTCHA', 'manage_options', basename( __FILE__ ), 'wpmsrc_settings_page' );
}

function wpmsrc_admin_init() {
	// Check WordPress version
	wpmsrc_version_check();

	// Register settings
	wpmsrc_register_settings();
}

if ( is_admin() ) {
	add_action( 'admin_menu', 'wpmsrc_add_options_page' );
	add_action( 'admin_init', 'wpmsrc_admin_init' );
}

/*
	Load admin styles & scripts
*/
function wpmsrc_add_style( $hook ) {
	if ( 'settings_page_simple-recaptcha' == $hook ) {
		wp_enqueue_style( 'wpmsrc-admin-style', plugins_url( 'css/admin-style.css', __FILE__ ) );
		// wp_enqueue_script( 'wpmsrc-admin-script', plugins_url( 'js/admin-script.js', __FILE__ ), array( 'jquery' ) );
	}
}
add_action( 'admin_enqueue_scripts', 'wpmsrc_add_style' );


  /*==============*/
 /*   Settings   */
/*==============*/

/*
	Register settings
*/
function wpmsrc_register_settings() {
	$wpmsrc_plugin_info = get_plugin_data( __FILE__, false );
	$wpmsrc_options = wpmsrc_get_options();
	
	register_setting( 'wpmsrc-settings-group', 'wpmsrc_options', 'wpmsrc_sanitize_options' );

	$wpmsrc_default_options = array(
			'public_key'     => '',
			'private_key'    => '',
			'theme'          => 'red',
			'plugin_version' => $wpmsrc_plugin_info["Version"]
	);

	// Install the option defaults
	if ( is_multisite() ) {
		if ( ! get_site_option( 'wpmsrc_options' ) ) {
			add_site_option( 'wpmsrc_options', $wpmsrc_default_options, '', 'yes' );
		}
	} else {
		if ( ! get_option( 'wpmsrc_options' ) ) {
			add_option( 'wpmsrc_options', $wpmsrc_default_options, '', 'yes' );
		}
	}

	// Merge options in case this version has added new options
	if ( ! isset( $wpmsrc_options['plugin_version'] ) 
			|| $wpmsrc_options['plugin_version'] != $wpmsrc_plugin_info["Version"] ) {
			
		$wpmsrc_options = array_merge( $wpmsrc_default_options, $wpmsrc_options );
		$wpmsrc_options['plugin_version'] = $wpmsrc_plugin_info["Version"];
		update_option( 'wpmsrc_options', $wpmsrc_options );
		
	}
}

/*
	Sanitize settings
*/
function wpmsrc_sanitize_options( $input ) {
	$new_input['public_key']  = sanitize_text_field( $input['public_key'] );
	$new_input['private_key'] = sanitize_text_field( $input['private_key'] );
	$new_input['theme']       = sanitize_text_field( $input['theme'] );
	
	return $new_input;
}

/*
	Settings page
*/
function wpmsrc_settings_page() {
	$wpmsrc_options = wpmsrc_get_options();

	// Google captcha themes
	$wpmsrc_themes = array(
			array( 'red', 'Red' ),
			array( 'white', 'White' ),
			array( 'blackglass', 'Blackglass' ),
			array( 'clean', 'Clean' ),
	);
	?>
	
	<div class="wrap">
	
		<h2><?php _e( 'Simple reCAPTCHA Settings', 'wpmsrc' ); ?></h2>
		
		<form method="post" action="options.php">
		
			<?php wp_nonce_field( plugin_basename( __FILE__ ), 'wpmsrc_nonce' ); ?>
			<?php settings_fields( 'wpmsrc-settings-group' ); ?>
			<?php do_settings_sections( 'wpmsrc-settings-group' ); ?>
		
			<h3><?php _e( 'Authentication', 'wpmsrc' ); ?></h3>
			
			<p><?php _e( 'To use reCAPTCHA:', 'wpmsrc' ); ?></p>
			
			<ol>
				<li><a href="https://www.google.com/recaptcha/admin" target="_blank"><?php _e( 'Register with Google' ); ?></a>.</li>
				<li><?php _e( 'Get your keys' ); ?>.</li>
				<li><?php _e( 'Enter your keys here' ); ?>.</li>
			</ol>
			
			<p><em><?php _e( "Note: The captcha will not appear without valid keys.", 'wpmsrc' ); ?></em></p>
			
			<table id="wpmsrc-keys" class="form-table">
				<tr valign="top">
					<th scope="row">Public key</th>
					<td>
						<input type="text" class="code" name="wpmsrc_options[public_key]"
										value="<?php echo $wpmsrc_options['public_key'] ?>">
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Private key</th>
					<td>
						<input type="text" class="code" name="wpmsrc_options[private_key]"
										value="<?php echo $wpmsrc_options['private_key'] ?>">
					</td>
				</tr>
			</table>
			
			<h3><?php _e( 'Options', 'wpmsrc' ); ?></h3>
			
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'Theme', 'wpmsrc' ); ?></th>
					<td>
						<select id="wpmsrc-theme" name="wpmsrc_options[theme]">
							<?php foreach ( $wpmsrc_themes as $theme ) : ?>
								<option value=<?php echo $theme[0]; selected( $theme[0], $wpmsrc_options['theme'] ); ?>><?php echo $theme[1]; ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
			
			<div id="wpmsrc-example"><?php echo wpmsrc_display(); ?></div>
			
			<?php submit_button(); ?>
			
		</form>
		
	</div><!-- wrap -->
<?php }


  /*=====================*/
 /*   Display Captcha   */
/*=====================*/

function wpmsrc_display() {
	require_once( 'lib/recaptchalib.php' );
	$wpmsrc_options = wpmsrc_get_options();
	$publickey  = $wpmsrc_options['public_key'];
	$privatekey = $wpmsrc_options['private_key']; 

	if ( ! $privatekey || ! $publickey ) {
		if ( current_user_can( 'manage_options' ) ) {
			?>
			<div>
				<strong>
					<?php _e( 'To use Google Captcha you must get the keys from', 'wpmsrc' ); ?> <a target="_blank" href="https://www.google.com/recaptcha/admin/create"><?php _e ( 'here', 'wpmsrc' ); ?></a> <?php _e ( 'and enter them on the', 'wpmsrc' ); ?> <a target="_blank" href="<?php echo admin_url( '/options-general.php?page=simple-recaptcha.php' ); ?>"><?php _e ( 'plugin setting page', 'wpmsrc' ); ?></a>.
				</strong>
			</div>
			<?php
		}
		return false;
	}
	?>
	<script type='text/javascript'>
		var RecaptchaOptions = { theme : "<?php echo $wpmsrc_options['theme']; ?>" };
	</script>
	<input type="hidden" name="wpmsrc-display" value="true">
	<?php
	return recaptcha_get_html( $publickey );
}


  /*===================*/
 /*   Check Captcha   */
/*===================*/

function wpmsrc_check() {
	if ( isset( $_POST['wpmsrc-display'] ) ) {
		require_once( 'lib/recaptchalib.php' );
		$wpmsrc_options = wpmsrc_get_options();
		$publickey  = $wpmsrc_options['public_key'];
		$privatekey = $wpmsrc_options['private_key'];

		if ( ! $privatekey || ! $publickey ) {
			return false;
		}
		
		$resp = recaptcha_check_answer( $privatekey,
								$_SERVER['REMOTE_ADDR'],
								$_POST['recaptcha_challenge_field'],
								$_POST['recaptcha_response_field'] );
		return $resp;
	}
}
