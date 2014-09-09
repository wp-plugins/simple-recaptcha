<?php
/**
 * Simple reCAPTCHA uninstall
 *
 * @since 0.5
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) 
	exit();

// main site
$options = get_option( 'wpmsrc_options' );
if ( $options['lnt'] )
	delete_option( 'wpmsrc_options' );

// multisite
if ( is_multisite() ) {
	$options = get_site_option( 'wpmsrc_options' );
	if ( $options['lnt'] ) {
		delete_site_option( 'wpmsrc_options' );
		foreach ( wp_get_sites() as $site ) {
			switch_to_blog( $site['blog_id'] );
			delete_option( 'wpmsrc_options' );
			restore_current_blog();
		}
	}
}
