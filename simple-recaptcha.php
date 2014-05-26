<?php
/*
	Plugin Name: Simple reCAPTCHA
	Plugin URI: http://www.wpmission.com/
	Description: A simple implementation of Google's reCAPTCHA suitable for any custom form.
	Author: Chris Dillon
	Version: 0.2
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


/*
	Not using activation hook because plugin requires admin settings before being used.
*/
/*
function wpmsrc_activation() {
}
register_activation_hook( __FILE__, 'wpmsrc_activation' );
*/


function wpmsrc_init() {
	load_plugin_textdomain( 'wpmsrc', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	// Add hooks
	add_action( 'wpmsrc_display', 'wpmsrc_display' );
	// add_filter( 'wpmsrc_check', 'wpmsrc_check' );
}
add_action( 'init', 'wpmsrc_init' );


/*
	Uninstall
*/
function wpmsrc_delete_options() {
	// main site
	delete_option( 'wpmsrc_options' );
	// multisite
	if ( is_multisite() ) {
		delete_site_option( 'wpmsrc_options' );
		$subsites = wpmsrc_subsites();
		foreach ( $subsites as $id => $site ) {
			switch_to_blog( $id );
			delete_option( 'wpmsrc_options' );
			restore_current_blog();
		}
	}
}
register_uninstall_hook( __FILE__, 'wpmsrc_delete_options' );


/*
	Check WordPress version
*/
function wpmsrc_version_check() {
	global $wp_version;
	$wpmsrc_plugin_info = get_plugin_data( __FILE__, false );
	$require_wp = "3.0";  // least required Wordpress version
	$plugin = plugin_basename( __FILE__ );
	
	if ( version_compare( $wp_version, $require_wp, "<" ) ) {
		if ( is_plugin_active( $plugin ) ) {
			deactivate_plugins( $plugin );
			wp_die( "<strong>" . $wpmsrc_plugin_info['Name'] . " </strong> " . __( 'requires', 'wpmsrc' ) . " <strong>WordPress " . $require_wp . "</strong> " . __( 'or higher so it has been deactivated. Please upgrade WordPress and try again.', 'wpmsrc') . "<br /><br />" . __( 'Back to the WordPress', 'wpmsrc') . " <a href='" . get_admin_url( null, 'plugins.php' ) . "'>" . __( 'Plugins page', 'wpmsrc') . "</a>." );
		}
	}
}


/*===========*/
/*   Admin   */
/*===========*/


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
	Settings page
*/
function wpmsrc_add_settings_page() {
	if ( is_multisite() )
		add_options_page( 'Simple reCAPTCHA', 'Simple reCAPTCHA', 'manage_options', basename( __FILE__ ), 'wpmsrc_ms_settings_page' );
	else 
		add_options_page( 'Simple reCAPTCHA', 'Simple reCAPTCHA', 'manage_options', basename( __FILE__ ), 'wpmsrc_settings_page' );
}

function wpmsrc_admin_init() {
	// Check WordPress version
	wpmsrc_version_check();

	// Register settings
	if ( is_multisite() )
		wpmsrc_register_ms_settings();
	else
		wpmsrc_register_settings();
}

if ( is_admin() ) {
	add_action( 'admin_menu', 'wpmsrc_add_settings_page' );
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
	Register multisite settings
	Double duty: Plugin activation and upgrade.
*/
function wpmsrc_register_ms_settings() {

	// -1- DEFAULTS
	$plugin_version = get_plugin_data( __FILE__, false )['Version'];
	// for a plugin upgrade, add new options here
	$default_ms_options = array(
			'public_key'     => '',
			'private_key'    => '',
			'theme'          => 'red',
			'plugin_version' => $plugin_version,
	);
	$default_options = array(
			'theme'          => $default_ms_options['theme'],
	);

	// -2- MULTISITE OPTIONS
	$ms_options = get_site_option( 'wpmsrc_options' );
	if ( ! $ms_options ) {
	
		// -2A- ACTIVATION
		update_site_option( 'wpmsrc_options', $default_ms_options );
		
	} else {
	
		// -2B- UPGRADE?
		if ( ! isset( $ms_options['plugin_version'] ) || $ms_options['plugin_version'] != $plugin_version ) {
			// merge in any new options: arg#2 [options] populates + overwrites arg#1 [defaults]
			$ms_options = array_merge( $default_ms_options, $ms_options ); 
			$ms_options['plugin_version'] = $plugin_version;
			update_site_option( 'wpmsrc_options', $ms_options );
		}
		
	}

	// -3- SUBSITE OPTIONS
	$options    = get_option( 'wpmsrc_options' );
	if ( ! $options ) {
	
		// -3A- ACTIVATION
		update_option( 'wpmsrc_options', $default_options );
		
	} else {
	
		// -3B- UPGRADE?
		if ( ! isset( $ms_options['plugin_version'] ) || $ms_options['plugin_version'] != $plugin_version ) {
			// merge in any new options: arg#2 [options] populates + overwrites arg#1 [defaults]
			$options = array_merge( $default_options, $options );
			update_option( 'wpmsrc_options', $options );
		}
		
	}
	
}


/*
	Register single site settings
	Double duty: Plugin activation and upgrade.
*/
function wpmsrc_register_settings() {

	// -1- DEFAULTS
	$plugin_version = get_plugin_data( __FILE__, false )['Version'];
	// add plugin-upgrade options here
	$default_options = array(
			'public_key'     => '',
			'private_key'    => '',
			'theme'          => 'red',
			'plugin_version' => $plugin_version,
	);

	// -2- GET OPTIONS
	$options = get_option( 'wpmsrc_options' );

	if ( ! $options ) {
	
		// -2A- ACTIVATION
		update_option( 'wpmsrc_options', $options );
	
	} else {
	
		// -2B- UPGRADE?
		if ( ! isset( $options['plugin_version'] ) || $options['plugin_version'] != $plugin_version ) {
			// merge in any new options - arg#2 [options] populates/overwrites arg#1 [defaults]
			$options = array_merge( $default_options, $options );
			$options['plugin_version'] = $plugin_version;
			update_option( 'wpmsrc_options', $options );
		}
		
	}

}


/*
	Multisite settings page
*/
function wpmsrc_ms_settings_page() {

	$ms_options = get_site_option( 'wpmsrc_options' );
	$options = get_option( 'wpmsrc_options' );

	if ( isset( $_POST['submit'] ) ) {
		if ( ! wp_verify_nonce( $_REQUEST['wpmsrc_nonce_orange'], plugin_basename( __FILE__ ) ) )
			die( '<h1 style="color: red;">Security breach. Initiating self destruct in 5 . . .</h1>' );
		
		/* 
		$_POST : array (
			'wpmsrc_ms_options' => 
				array (
					'public_key' => string '123'
					'private_key' => string '456'
					'theme' => string 'clean'
				)
			'wpmsrc_options' => 
				array (
					'theme' => string 'red'
				)
			'submit' => string 'Save Changes'
		)
		*/

		// sanitize
		$input = $_POST;
		foreach ( $input as $key => $option_group ) {
			if ( is_array( $option_group ) ) {
				foreach ( $option_group as $name => $value ) {
					$input[$key][$name] = sanitize_text_field( $value );
				}
			}
		}
		
		if ( is_super_admin() ) {
			$ms_options = $input['wpmsrc_ms_options'];
			update_site_option( 'wpmsrc_options', $ms_options ); 
		}
		
		$options = $input['wpmsrc_options'];
		update_option( 'wpmsrc_options', $options );
		
		?>
		<div id="message" class="updated">
			<p><strong><?php _e('Settings saved.') ?></strong></p>
    </div>
		<?php
	}
	
	// Google captcha themes	// ~!~ move to plugin default options ~!~
	$wpmsrc_themes = array(
			array( 'red', 'Red' ),
			array( 'white', 'White' ),
			array( 'blackglass', 'Blackglass' ),
			array( 'clean', 'Clean' ),
	);
	?>
	
	<div class="wrap">
	
		<h2><?php _e( 'Simple reCAPTCHA Settings', 'wpmsrc' ); ?></h2>
		
		<form method="post">
		
			<?php wp_nonce_field( plugin_basename( __FILE__ ), 'wpmsrc_nonce_orange' ); ?>
			
			<h3><?php _e( 'Authentication', 'wpmsrc' ); ?></h3>
		
			<?php if ( is_super_admin() ) : ?>
			
				<p><?php _e( 'To use reCAPTCHA:', 'wpmsrc' ); ?></p>
				
				<ol>
					<li><a href="https://www.google.com/recaptcha/admin" target="_blank"><?php _e( 'Register with Google' ); ?></a>.</li>
					<li><?php _e( 'Get your keys' ); ?>.</li>
					<li><?php _e( 'Enter your keys here' ); ?>.</li>
				</ol>
				
				<p><em><?php _e( "Note: The CAPTCHA will not appear without valid keys.", 'wpmsrc' ); ?></em></p>
				
				<table id="wpmsrc-keys" class="form-table">
					<tr valign="top">
						<th scope="row">Public key</th>
						<td>
							<input type="text" class="code" name="wpmsrc_ms_options[public_key]" value="<?php echo $ms_options['public_key'] ?>">
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Private key</th>
						<td>
							<input type="text" class="code" name="wpmsrc_ms_options[private_key]" value="<?php echo $ms_options['private_key'] ?>">
						</td>
					</tr>
				</table>
			
			<?php else : ?>
			
				<p><?php _e( 'Keys are managed by your site administrator', 'wpmsrc' ); ?>.</p>
			
			<?php endif; // is_super_admin ?>
			
			<hr>
			
			<h3><?php _e( 'Options', 'wpmsrc' ); ?></h3>
			
			<table class="form-table">
				<?php if ( is_super_admin() ) : ?>
				<tr valign="top">
					<th scope="row"><?php _e( 'Default Theme', 'wpmsrc' ); ?></th>
					<td>
						<select id="wpmsrc-default-theme" name="wpmsrc_ms_options[theme]">
							<?php foreach ( $wpmsrc_themes as $theme ) : ?>
								<option value="<?php echo $theme[0]; ?>"<?php selected( $theme[0], $ms_options['theme'] ); ?>><?php echo $theme[1]; ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<?php endif; // is_super_admin ?>
				<tr valign="top">
					<?php if ( is_super_admin() ) : ?>
						<th scope="row" style="white-space: nowrap;"><?php _e( 'Theme for this site', 'wpmsrc' ); ?></th>
					<?php else : ?>
						<th scope="row" style="white-space: nowrap;"><?php _e( 'Theme', 'wpmsrc' ); ?></th>
					<?php endif; ?>
					<td>
						<select id="wpmsrc-theme" name="wpmsrc_options[theme]">
							<?php foreach ( $wpmsrc_themes as $theme ) : ?>
								<option value="<?php echo $theme[0]; ?>"<?php selected( $theme[0], $options['theme'] ); ?>><?php echo $theme[1]; ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
			
			<div id="wpmsrc-example"><?php echo wpmsrc_display(); ?></div>
			
			<?php submit_button(); ?>
			
		</form>
		
	</div><!-- wrap -->
	<?php 
}


function wpmsrc_settings_page() {

	$options = get_option( 'wpmsrc_options' );

	if ( isset( $_POST['submit'] ) ) {
		if ( ! wp_verify_nonce( $_REQUEST['wpmsrc_nonce_orange'], plugin_basename( __FILE__ ) ) )
			die( '<h1 style="color: red;">Security breach. Initiating self destruct in 5 . . .</h1>' );
		
		/* 
		$_POST : array (
			'wpmsrc_options' => 
				array (
					'public_key' => string '123'
					'private_key' => string '456'
					'theme' => string 'clean'
					'theme' => string 'red'
				)
			'submit' => string 'Save Changes'
		)
		*/

		// sanitize
		$input = $_POST;
		foreach ( $input as $key => $option_group ) {
			if ( is_array( $option_group ) ) {
				foreach ( $option_group as $name => $value ) {
					$input[$key][$name] = sanitize_text_field( $value );
				}
			}
		}
		
		$options = $input['wpmsrc_options'];
	
		update_option( 'wpmsrc_options', $options );
		
		?>
		<div id="message" class="updated">
			<p><strong><?php _e('Settings saved.') ?></strong></p>
    </div>
		<?php
	}
	
	// Google captcha themes	// ~!~ move to plugin default options ~!~
	$wpmsrc_themes = array(
			array( 'red', 'Red' ),
			array( 'white', 'White' ),
			array( 'blackglass', 'Blackglass' ),
			array( 'clean', 'Clean' ),
	);
	?>
	
	<div class="wrap">
	
		<h2><?php _e( 'Simple reCAPTCHA Settings', 'wpmsrc' ); ?></h2>
		
		<form method="post">
		
			<?php wp_nonce_field( plugin_basename( __FILE__ ), 'wpmsrc_nonce_orange' ); ?>
			
			<h3><?php _e( 'Authentication', 'wpmsrc' ); ?></h3>
		
			<p><?php _e( 'To use reCAPTCHA:', 'wpmsrc' ); ?></p>
			
			<ol>
				<li><a href="https://www.google.com/recaptcha/admin" target="_blank"><?php _e( 'Register with Google' ); ?></a>.</li>
				<li><?php _e( 'Get your keys' ); ?>.</li>
				<li><?php _e( 'Enter your keys here' ); ?>.</li>
			</ol>
			
			<p><em><?php _e( "Note: The CAPTCHA will not appear without valid keys.", 'wpmsrc' ); ?></em></p>
			
			<table id="wpmsrc-keys" class="form-table">
				<tr valign="top">
					<th scope="row">Public key</th>
					<td>
						<input type="text" class="code" name="wpmsrc_options[public_key]" value="<?php echo $options['public_key'] ?>">
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Private key</th>
					<td>
						<input type="text" class="code" name="wpmsrc_options[private_key]" value="<?php echo $options['private_key'] ?>">
					</td>
				</tr>
			</table>
			
			<hr>
			
			<h3><?php _e( 'Options', 'wpmsrc' ); ?></h3>
			
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'Theme', 'wpmsrc' ); ?></th>
					<td>
						<select id="wpmsrc-theme" name="wpmsrc_options[theme]">
							<?php foreach ( $wpmsrc_themes as $theme ) : ?>
								<option value="<?php echo $theme[0]; ?>"<?php selected( $theme[0], $options['theme'] ); ?>><?php echo $theme[1]; ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
			
			<div id="wpmsrc-example"><?php echo wpmsrc_display(); ?></div>
			
			<?php submit_button(); ?>
			
		</form>
		
	</div><!-- wrap -->
	<?php 
}


/*=====================*/
/*   Display Captcha   */
/*=====================*/

function wpmsrc_display() {
	require_once( 'lib/recaptchalib.php' );
	
	if ( is_multisite() )
		$wpmsrc_options = array_merge( get_site_option( 'wpmsrc_options' ), get_option( 'wpmsrc_options' ) );
	else
		$wpmsrc_options = get_option( 'wpmsrc_options' );
		
	if ( ! $wpmsrc_options['private_key'] || ! $wpmsrc_options['public_key'] ) {
		// Keys missing. Show message to admin...
		if ( current_user_can( 'manage_options' ) ) {
			if ( ! is_admin() ) {  // ...except on admin page demo.
				?>
				<div>
					<strong>
						<?php _e( 'To use Google reCAPTCHA you must ', 'wpmsrc' ); ?> <a target="_blank" href="https://www.google.com/recaptcha/admin/create"><?php _e ( 'get your keys', 'wpmsrc' ); ?></a> <?php _e ( 'and enter them on the', 'wpmsrc' ); ?> <a target="_blank" href="<?php echo admin_url( '/options-general.php?page=simple-recaptcha.php' ); ?>"><?php _e ( 'plugin setting page', 'wpmsrc' ); ?></a>.
					</strong>
				</div>
				<?php
			}
		}
		return false;
	}
	?>
	<script type='text/javascript'>
		var RecaptchaOptions = { theme : "<?php echo $wpmsrc_options['theme']; ?>" };
	</script>
	<input type="hidden" name="wpmsrc-display" value="true">
	<?php
	return recaptcha_get_html( $wpmsrc_options['public_key'] );
}


/*===================*/
/*   Check Captcha   */
/*===================*/

function wpmsrc_check() {
	if ( isset( $_POST['wpmsrc-display'] ) ) {
		require_once( 'lib/recaptchalib.php' );
		
		if ( is_multisite() )
			$wpmsrc_options = array_merge( get_site_option( 'wpmsrc_options' ), get_option( 'wpmsrc_options' ) );
		else
			$wpmsrc_options = get_option( 'wpmsrc_options' );
		
		if ( ! $wpmsrc_options['private_key'] || ! $wpmsrc_options['public_key'] )
			return false;
		
		$resp = recaptcha_check_answer( 
								$wpmsrc_options['private_key'],
								$_SERVER['REMOTE_ADDR'],
								$_POST['recaptcha_challenge_field'],
								$_POST['recaptcha_response_field']
						);
		return $resp;
	}
}
