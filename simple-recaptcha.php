<?php
/**
 * Plugin Name: Simple reCAPTCHA
 * Plugin URI: http://www.wpmission.com/wordpress-plugins/simple-recaptcha
 * Description: Add Google's reCAPTCHA to any custom form.
 * Author: Chris Dillon
 * Version: 0.6
 * Author URI: http://www.wpmission.com/
 * Text Domain: simple-recaptcha
 * Requires: 3.0 or higher
 * License: GPLv3 or later
 *
 * Copyright 2014  Chris Dillon  chris@wpmission.com
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 3, as 
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


function wpmsrc_init() {
	load_plugin_textdomain( 'simple-recaptcha', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	add_action( 'wpmsrc_display', 'wpmsrc_display' );
}
add_action( 'init', 'wpmsrc_init' );


/*
 * Load admin styles & scripts
 */
function wpmsrc_add_style( $hook ) {
	if ( 'settings_page_simple-recaptcha' == $hook ) {
		wp_enqueue_style( 'wpmsrc-admin-style', plugins_url( 'css/admin.css', __FILE__ ) );
		wp_enqueue_style( 'wpmsrc-lnt-style', plugins_url( 'css/lnt-option.css', __FILE__ ) );
	}
	elseif ( 'plugins.php' == $hook ) {
		if ( ! wp_style_is( 'lnt', 'enqueued' ) )
			wp_enqueue_style( 'lnt', plugins_url( '/css/lnt.css', __FILE__ ) );
	}
}
add_action( 'admin_enqueue_scripts', 'wpmsrc_add_style' );


/*
 * Check WordPress version
 */
function wpmsrc_version_check() {
	global $wp_version;
	$wpmsrc_plugin_info = get_plugin_data( __FILE__, false );
	$require_wp = "3.0";  // minimum Wordpress version required
	$plugin = plugin_basename( __FILE__ );
	
	if ( version_compare( $wp_version, $require_wp, "<" ) ) {
		if ( is_plugin_active( $plugin ) ) {
			deactivate_plugins( $plugin );
			wp_die(
				sprintf( __( '<strong>%s</strong> requires WordPress <strong>%s</strong> or higher so it has been deactivated. Please upgrade WordPress and try again.', 'simple-recaptcha' ), 
					$wpmsrc_plugin_info['Name'], $require_wp )
				. '<br /><br />'
				. sprintf( __( 'Back to the WordPress <a href="%s">Plugins page</a>.', 'simple-recaptcha' ), get_admin_url( null, 'plugins.php' ) )
			);
		}
	}
}


/*
 * Plugin list action links
 */
function wpmsrc_action_links( $links, $file ) {
	if ( $file == plugin_basename( __FILE__ ) ){
		$settings_link = '<a href="options-general.php?page=simple-recaptcha.php">' . __( 'Settings', 'simple-recaptcha' ) . '</a>';
		array_unshift( $links, $settings_link );
	}
	return $links;
}
add_filter( 'plugin_action_links', 'wpmsrc_action_links', 10, 2 );


/*
 * Plugin meta row
 */
function wpmsrc_plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
	if ( $plugin_file == plugin_basename( __FILE__ ) ) {
		$plugin_meta[] = '<span class="lnt">Leave No Trace</span>';
	}
	return $plugin_meta;
}
add_filter( 'plugin_row_meta', 'wpmsrc_plugin_row_meta', 10, 4 );

	
/*
 * Settings page
 */
function wpmsrc_add_settings_page() {
	add_options_page( 'Simple reCAPTCHA', 'Simple reCAPTCHA', 'manage_options', basename( __FILE__ ), 'wpmsrc_settings_page' );
}
add_action( 'admin_menu', 'wpmsrc_add_settings_page' );

function wpmsrc_admin_init() {
	wpmsrc_version_check();

	// Register settings. Keep these separate.
	if ( is_multisite() )
		wpmsrc_register_ms_settings();
	else
		wpmsrc_register_settings();
}
add_action( 'admin_init', 'wpmsrc_admin_init' );


/*
 * Register multisite settings
 * Double duty: Plugin activation and update.
 */
function wpmsrc_register_ms_settings() {

	// -1- DEFAULTS
	$plugin_data = get_plugin_data( __FILE__, false );
	$plugin_version = $plugin_data['Version'];
	// for a plugin update, add new options here
	$default_ms_options = array(
			'public_key'     => '',
			'private_key'    => '',
			'theme'          => 'red',
			'lnt'            => 1,
			'plugin_version' => $plugin_version,
	);

	// -2- MULTISITE OPTIONS
	$ms_options = get_site_option( 'wpmsrc_options' );
	if ( ! $ms_options ) {
	
		// -2A- ACTIVATION
		update_site_option( 'wpmsrc_options', $default_ms_options );
		// use default theme for new subsites
		$default_options = array(
				'theme' => $default_ms_options['theme'],
		);
		
	} else {
	
		// -2B- UPDATE?
		if ( ! isset( $ms_options['plugin_version'] ) || $ms_options['plugin_version'] != $plugin_version ) {
			// merge in new options
			$ms_options = array_merge( $default_ms_options, $ms_options ); 
			$ms_options['plugin_version'] = $plugin_version;
			update_site_option( 'wpmsrc_options', $ms_options );
		}
		// use theme chosen by super admin for new subsites
		$default_options = array(
				'theme' => $ms_options['theme'],
		);
		
	}

	// -3- SUBSITE OPTIONS
	$options = get_option( 'wpmsrc_options' );
	if ( ! $options ) {
	
		// -3A- ACTIVATION
		update_option( 'wpmsrc_options', $default_options );
		
	} else {
	
		// -3B- UPGRADE?
		if ( ! isset( $ms_options['plugin_version'] ) || $ms_options['plugin_version'] != $plugin_version ) {
			// merge in any new options
			$options = array_merge( $default_options, $options );
			update_option( 'wpmsrc_options', $options );
		}
		
	}
	
}


/*
 * Register single site settings
 * Double duty: Plugin activation and update.
 */
function wpmsrc_register_settings() {

	// -1- DEFAULTS
	$plugin_data = get_plugin_data( __FILE__, false );
	$plugin_version = $plugin_data['Version'];
	// add plugin update options here
	$default_options = array(
			'public_key'     => '',
			'private_key'    => '',
			'theme'          => 'red',
			'lnt'            => 1,
			'plugin_version' => $plugin_version,
	);

	// -2- GET OPTIONS
	$options = get_option( 'wpmsrc_options' );

	if ( ! $options ) {
	
		// -2A- ACTIVATION
		update_option( 'wpmsrc_options', $default_options );
	
	} else {
	
		// -2B- UPDATE?
		if ( ! isset( $options['plugin_version'] ) || $options['plugin_version'] != $plugin_version ) {
			// merge in new options
			$options = array_merge( $default_options, $options );
			$options['plugin_version'] = $plugin_version;
			update_option( 'wpmsrc_options', $options );
		}
		
	}

}


/*
 * Settings page
 */
function wpmsrc_settings_page() {

	$options = get_option( 'wpmsrc_options' );
	if ( is_multisite() )
		$ms_options = get_site_option( 'wpmsrc_options' );

	if ( isset( $_POST['submit'] ) ) {
		if ( ! wp_verify_nonce( $_REQUEST['wpmsrc_nonce_orange'], plugin_basename( __FILE__ ) ) )
			die( '<h1 style="color: red;">Security breach.</h1>' );
		
		/* 
		 * POST :
		 * 'wpmsrc_ms_options' => 
		 *       array (  'public_key' => string '123'
		 *                'private_key' => string '456'
		 *                'theme' => string 'clean'
		 *                'lnt' => 1  )
		 * 'wpmsrc_options' => 
		 *       array (  'theme' => string 'red'  )
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
		
		if ( isset( $input['wpmsrc_ms_options'] ) ) {
			$ms_options = $input['wpmsrc_ms_options'];
			$ms_options['lnt'] = isset( $ms_options['lnt'] ) ? 1 : 0;
			update_site_option( 'wpmsrc_options', $ms_options ); 
		}
		
		$options = $input['wpmsrc_options'];
		$options['lnt'] = isset( $options['lnt'] ) ? 1 : 0;
		update_option( 'wpmsrc_options', $options );
		
		?>
		<div id="message" class="updated">
			<p><strong><?php _e( 'Settings saved.' ) ?></strong></p>
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
	
		<h2><?php _e( 'Simple reCAPTCHA Settings', 'simple-recaptcha' ); ?></h2>
		
		<form method="post">
		
			<?php wp_nonce_field( plugin_basename( __FILE__ ), 'wpmsrc_nonce_orange' ); ?>
			
			<h3><?php _e( 'Authentication', 'simple-recaptcha' ); ?></h3>
		
		
			<?php if ( ! is_multisite() || is_multisite() && is_super_admin() && is_main_site() ) : ?>
			
			<p><?php _e( 'To use reCAPTCHA:', 'simple-recaptcha' ); ?></p>
			
			<ol>
				<li><a href="https://www.google.com/recaptcha/admin" target="_blank"><?php _e( 'Register with Google', 'simple-recaptcha' ); ?></a>.</li>
				<li><?php _e( 'Get your keys.', 'simple-recaptcha' ); ?></li>
				<li><?php _e( 'Enter your keys here.', 'simple-recaptcha' ); ?></li>
			</ol>
			
			<p><em><?php _e( "Note: The CAPTCHA will not appear without valid keys.", 'simple-recaptcha' ); ?></em></p>
			
			<table id="wpmsrc-keys" class="form-table">
				<tr valign="top">
					<th scope="row">Public key</th>
					<td>
						<?php if ( is_multisite() ) : ?>
						<input type="text" class="code" name="wpmsrc_ms_options[public_key]" value="<?php echo $ms_options['public_key'] ?>">
						<?php else : ?>
						<input type="text" class="code" name="wpmsrc_options[public_key]" value="<?php echo $options['public_key'] ?>">
						<?php endif; ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Private key</th>
					<td>
						<?php if ( is_multisite() ) : ?>
						<input type="text" class="code" name="wpmsrc_ms_options[private_key]" value="<?php echo $ms_options['private_key'] ?>">
						<?php else : ?>
						<input type="text" class="code" name="wpmsrc_options[private_key]" value="<?php echo $options['private_key'] ?>">
						<?php endif; ?>
					</td>
				</tr>
			</table>

			<?php else : ?>
					
			<?php if ( ! is_super_admin() ) : ?>
			<p><?php _e( 'Keys are managed by your site administrator.', 'simple-recaptcha' ); ?></p>
			<?php elseif ( ! is_main_site() ) : ?>
			<p><?php _e( 'Manage keys on your main site.', 'simple-recaptcha' ); ?></p>
			<?php endif; ?>
				
			<?php endif; ?>
		
		
			<hr>

			
			<h3><?php _e( 'Options', 'simple-recaptcha' ); ?></h3>

			
			<?php if ( is_multisite() && is_super_admin() && is_main_site() ) : ?>
			
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'Network default theme', 'simple-recaptcha' ); ?></th>
					<td>
						<select id="wpmsrc-default-theme" name="wpmsrc_ms_options[theme]">
							<?php foreach ( $wpmsrc_themes as $theme ) : ?>
								<option value="<?php echo $theme[0]; ?>"<?php selected( $theme[0], $ms_options['theme'] ); ?>><?php echo $theme[1]; ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
		
			<?php endif; ?>

			
			<table class="form-table">
				<tr valign="top">
					<?php
					if ( is_multisite() && is_super_admin() ) {
						if ( is_main_site() )
							$label = __( 'Main site theme', 'simple-recaptcha' );
						else
							$label = __( 'Theme for this site', 'simple-recaptcha' );
					}
					else {
						$label = __( 'Theme', 'simple-recaptcha' );
					}
					?>
					<th scope="row" style="white-space: nowrap;"><?php echo $label; ?></th>
					<td>
						<select id="wpmsrc-theme" name="wpmsrc_options[theme]">
							<?php foreach ( $wpmsrc_themes as $theme ) : ?>
								<option value="<?php echo $theme[0]; ?>"<?php selected( $theme[0], $options['theme'] ); ?>><?php echo $theme[1]; ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
			<?php echo wpmsrc_theme_demo(); ?>
			
			
			<?php if ( ! is_multisite() || ( is_multisite() && is_super_admin() && is_main_site() ) ) : ?>
			<div class="option leave-no-trace">
				<div class="onoffswitch">
					<?php if ( is_multisite() ) : ?>
					<input id="myonoffswitch" type="checkbox" name="wpmsrc_ms_options[lnt]" class="onoffswitch-checkbox" value="1" <?php checked( 1, $ms_options['lnt'] ); ?>>
					<?php else : ?>
					<input id="myonoffswitch" type="checkbox" name="wpmsrc_options[lnt]" class="onoffswitch-checkbox" value="1" <?php checked( 1, $options['lnt'] ); ?>>
					<?php endif; ?>
					<label class="onoffswitch-label" for="myonoffswitch">
						<div class="onoffswitch-inner"></div>
						<div class="onoffswitch-switch"></div>
					</label>
				</div>
				<label for="myonoffswitch"><div class="option-label"><?php _e( 'Leave No Trace', 'simple-recaptcha' ); ?></div></label>
				<div class="option-desc">
					<?php _e( 'Deleting this plugin will also delete these settings.', 'simple-recaptcha' ); ?><br>
					<?php _e( 'Deactivating it will <strong>not</strong> delete these settings.', 'simple-recaptcha' ); ?>
				</div>
			</div>
			<?php endif; ?>

			
			<?php submit_button(); ?>
			
		</form>
		
	</div><!-- wrap -->
	<?php 
}


/*
 * Theme demo
 */
function wpmsrc_theme_demo() {
	require_once( 'lib/recaptchalib.php' );
	
	if ( is_multisite() )
		$options = array_merge( get_site_option( 'wpmsrc_options' ), get_option( 'wpmsrc_options' ) );
	else
		$options = get_option( 'wpmsrc_options' );
		
	if ( ! $options['private_key'] || ! $options['public_key'] )
		return false;

	$html = '<script type="text/javascript">var RecaptchaOptions = { theme : "' . $options['theme'] . '" }</script>';
	$html .= recaptcha_get_html( $options['public_key'] );
	
	return $html;
}


/*
 * Display Captcha
 */
function wpmsrc_display() {
	$html = '';
	require_once( 'lib/recaptchalib.php' );
	
	if ( is_multisite() )
		$wpmsrc_options = array_merge( get_site_option( 'wpmsrc_options' ), get_option( 'wpmsrc_options' ) );
	else
		$wpmsrc_options = get_option( 'wpmsrc_options' );
		
	if ( ! $wpmsrc_options['private_key'] || ! $wpmsrc_options['public_key'] ) {
		// Keys missing. Show message to admin, except on admin page demo.
		if ( current_user_can( 'manage_options' ) && ! is_admin() ) {
			$html .= '<div>';
			$html .= sprintf( 
				__( 'To use Google reCAPTCHA you must <a target="_blank" href="%s">get your keys</a> and enter them on the <a href="%s">plugin setting page</a>.', 'simple-recaptcha' ),
				'https://www.google.com/recaptcha/admin/create', 
				admin_url( '/options-general.php?page=simple-recaptcha.php' )
			);
			$html .= '</div>';
			return $html;
		}
		else {
			return false;	
		}
	}
	$html .= '<script type="text/javascript">var RecaptchaOptions = { theme : "' . $wpmsrc_options['theme'] . '" }</script>';
	$html .= '<input type="hidden" name="wpmsrc-display" value="true">';
	$html .= recaptcha_get_html( $wpmsrc_options['public_key'] );
	return $html;
}


/*
 * Check Captcha
 *
 * sample POST values:
 *    [_wp_http_referer] => /form/
 *    [wpmsrc-display] => true
 *    [recaptcha_challenge_field] => 03AHJ_VuvsJylbRy3KAm0Q8buA3hITtgnV[...]
 *    [recaptcha_response_field] => 1138
 */
function wpmsrc_check() {
	if ( ! isset( $_POST['wpmsrc-display'] ) )
		return false;
		
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
