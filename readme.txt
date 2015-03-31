=== Simple reCAPTCHA ===
Contributors: cdillon27
Donate link: http://www.wpmission.com/donate/
Tags: captcha, recaptcha, google captcha, reCAPTCHA, text captcha, spam, antispam
Requires at least: 3.0
Tested up to: 4.1
Stable tag: trunk
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Add Google's reCAPTCHA to any custom form.

== Description ==

= What this plugin does =

Provide a function to add reCAPTCHA to any custom form.

= What this plugin does NOT do =

Add reCAPTCHA to standard WordPress forms like logins or comments. For that, try [Google Captcha](https://wordpress.org/plugins/google-captcha/) by BestWebSoft.

= Features =

* A single lightweight module for use by other plugins and themes.
* Store the reCAPTCHA keys in one place, independent of any other plugin or theme.
* Display and validate the reCAPTCHA with a few lines of code.
* Four themes by Google (Red, White, BlackGlass, and Clean).
* Multisite compatible.

This plugin has room to grow and all ideas and feedback are welcome.

= How to use =

Step 1. Enter your reCAPTCHA keys in `Settings > Simple reCAPTCHA`.

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
	
		$errors['captcha'] = __( 'Please complete the CAPTCHA.', 'yourtextdomain' );
	
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

= Translations =

Since version 0.6:

* Serbo-Croatian (sr_RS) thanks to Andrijana Nikolic at [Web Hosting Geeks](http://webhostinggeeks.com/).

Can you help? [Contact me](http://www.wpmission.com/contact/).


== Installation ==

Option A: 

1. Go to `Plugins > Add New`.
1. Search for "simple recaptcha".
1. Click "Install Now".

Option B: 

1. Download the zip file.
1. Unzip it on your hard drive.
1. Upload the `simple-recaptcha` folder to the `/wp-content/plugins/` directory.

Option C:

1. Download the zip file.
1. Upload the zip file via `Plugins > Add New > Upload`.

Finally, activate the plugin.

If you need help, use the [support forum](http://wordpress.org/support/plugin/wider-admin-menu) or [contact me](http://www.wpmission.com/contact/).


== Frequently Asked Questions ==

= Is this multisite compatible? =

Yes, be sure to Network Activate. Super admins can manage the authentication keys and set the default theme, as well as any subsite's theme. Each subsite admin can change their theme.

= How do I get Google reCAPTCHA keys? =

[Sign up and manage your keys here](https://www.google.com/recaptcha/admin).

= How to change the style? =

Go to the Settings page and select a theme. Don't forget to `Save Changes`.

To customize it, select the Clean theme and style up from there.


== Changelog ==

= 0.6 =
* Add Serbo-Croatian translation thanks to Andrijana Nikolic at [Web Hosting Geeks](http://webhostinggeeks.com/).

= 0.5 =
* Add `uninstall.php`, a best practice.
* Leave No Trace.

= 0.4 =
* Fix bug in display function.

= 0.3 =
* Improve compatibility with earlier versions of PHP.

= 0.2 =
* Fix multisite compatibility.

= 0.1 =
* Initial release.

== Upgrade Notice ==

= 0.6 =
Add Serbo-Croatian translation.

= 0.5 =
Improve uninstall process.

= 0.4 =
Fix a bug in the display function.

= 0.3 =
Improve compatibility with earlier versions of PHP.

= 0.2 =
Definitely upgrade for multisite installations.
