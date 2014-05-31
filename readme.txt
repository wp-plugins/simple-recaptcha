=== Simple reCAPTCHA ===
Contributors: cdillon27
Donate link: http://www.wpmission.com/donate/
Tags: captcha, recaptcha, google captcha, reCAPTCHA, text captcha, spam, antispam
Requires at least: 3.0
Tested up to: 3.9.1
Stable tag: trunk
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Use Google's reCAPTCHA on any custom form.

== Description ==

What this plugin does: Add reCAPTCHA to any custom form.

What this plugin does NOT do: Add reCAPTCHA to standard WordPress forms like logins or comments. For that, try [Google Captcha](https://wordpress.org/plugins/google-captcha/) by BestWebSoft.

= Purpose =

1. To provide a single lightweight module for use by other plugins and themes.
1. To store the reCAPTCHA keys in one place, whether single site or multisite, independent of any other plugin or theme.
1. To encourage other plugin developers to decouple and modularize.

= How To Use =

Step 1. Enter your reCAPTCHA keys in Settings > Simple reCAPTCHA.

Step 2. Add the reCAPTCHA box to your form:
`
<?php
// is Simple reCAPTCHA active?
if ( function_exists( 'wpmsrc_display' ) ) { 
	echo wpmsrc_display();
}
?>
`

Step 3. Check the reCAPTCHA response in your server-side form validation:
`
<?php
// is Simple reCAPTCHA active?
if ( function_exists( 'wpmsrc_check' ) ) {

	// check for empty user response first (optional)
	if ( empty( $_POST['recaptcha_response_field'] ) ) {
	
		$errors['captcha'] = _( 'Please complete the CAPTCHA.', 'yourtextdomain' );
	
	} else {
	
		// check captcha
		$response = wpmsrc_check();
		if ( ! $response->is_valid ) {
			$errors['captcha'] = __( 'The CAPTCHA was not entered correctly. Please try again.', 'yourtextdomain' );
			// $response['error'] contains the actual error message, e.g. "incorrect-captcha-sol"
		}
		
	}
	
}
?>
`

= Features =

* Display and validate the reCAPTCHA with a few lines of code.
* Four themes by Google (Red, White, BlackGlass, and Clean).
* Multisite compatible.

= Development =

This plugin is under active development and all ideas and feedback are welcome.

The next major component will be internationalization (I18n) both in WordPress and in the reCAPTCHA itself.

Other component candidates include client-side validation and custom theming.

= Translations =

Can you help? [Contact me](http://www.wpmission.com/contact/).

== Installation ==

1. Upload the `simple-recaptcha` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin via the `Plugins` menu in WordPress.
3. Go to `Settings > Simple reCAPTCHA`.

== Frequently Asked Questions ==

= Is this multisite compatible? =

Yes, be sure to Network Activate. Super admins can manage the authentication keys and set the default theme, as well as any subsite's theme. Each subsite admin can change their theme.

= How do I get Google reCAPTCHA keys? =

[Sign up and manage your keys here](https://www.google.com/recaptcha/admin). You can also find this link on the Settings page.

= How to change the style? =

Go to the Settings page and select a theme. Don't forget to `Save Changes`.

To customize it, select the Clean theme and style up from there.

= How to use the other language files with reCAPTCHA? = 

I am working on this.

== Changelog ==

= 0.3 =
* Improved compatibility with earlier versions of PHP.

= 0.2 =
* Fixed multisite compatibility.

= 0.1 =
* Initial release.

== Upgrade Notice ==

= 0.3 =
Improved compatibility with earlier versions of PHP.

= 0.2 =
Definitely upgrade for multisite installations.