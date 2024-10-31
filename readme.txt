=== Presentation Toolkit ===
Contributors: tompahoward
Donate link: http://windyroad.org/software/wordpress/presentation-toolkit-plugin/#donate
Tags: theme, skin, admin, Windy Road
Requires at least: 2.1
Tested up to: 2.2
Stable tag: 0.0.9

The Presentation Toolkit plugin adds an administration page under the 'Presentation' menu for Presentation Toolkit compatible themes and skins.

== Description ==

The Presentation Toolkit plugin adds an administration page under the 'Presentation' menu for [Presentation Toolkit compatible themes and skins](http://windyroad.org/software/wordpress/presentation-toolkit-plugin/#ptk-themes).  The Presentation Toolkit plugin is based on [the WordPress Theme Toolkit](http://frenchfragfactory.net/ozh/my-projects/wordpress-theme-toolkit-admin-menu/) by [Ozh](http://planetozh.com).

== Installation ==

1. copy the 'presentationtoolkit' directory to your 'wp-contents/plugins' directory.
1. Activate the Presentation Toolkit in your plugins administration page.
1. Install and activate a [Presentation Toolkit compatible theme or skin](http://windyroad.org/software/wordpress/presentation-toolkit-plugin/#ptk-themes).
1. You will now see a menu for the theme or skin in the 'Presentation' menu.

== Screenshots ==

1. Changing options

== Frequently Asked Questions ==

= How do I Use the Presentation Toolkit in my theme/skin? =

In the `functions.php` file for your theme or skin add the following:
	if( function_exists( 'presentationtoolkit' ) ) {
		presentationtoolkit(
			array( 'option1' => 'Text for Option One',
				 'option2' => 'Text for Option Two',
				 'option3' => 'Text for Option Three' ),
			\_\_FILE\_\_
		);
	}
You will now have an admin page for your theme or skin.

= How do I access the theme's/skin's options? =

To access your theme or skin options simply use
	get_theme_option('option')
or 
	get_skin_options('option')
respectively.  If the option is not set, or doesn't exist, then `null` will be returned.

= What is the format of the various options? =

You'll find the format of the various options at [frenchfragfactory](http://frenchfragfactory.net/ozh/my-projects/wordpress-theme-toolkit-admin-menu/3/ ).

== Release Notes ==
* 0.0.9
	* Removed html encoding of skin options for the skinner plugin. If you are using skinner, please upgrade to 0.1.2.
* 0.0.8
	* The Presentation Toolkit is now compatible with the [Theme Switcher plugin](http://wordpress.org/extend/plugins/theme-switcher/ ) and also supports the new skin switching feature of the [Skinner plugin](http://windyroad.org/software/wordpress/skinner/ ).
* 0.0.7
	* Added [BeNice](http://wordpress.org/extend/plugins/be-nice/ ) support.
	* Fixed error in redirect after storing options.
* 0.0.6
	* Another attempt to fix plugin activation/deactivation issues
* 0.0.5
	* Trying to fix plugin activation/deactivation issues
* 0.0.4 
	* Fixed some more validation issues
* 0.0.3 
	* Added `get_theme_option()` and `get_skin_option()` functions
* 0.0.2 
	* Fixed validation issues
* 0.0.1 
	* Fixed generated skin URLS
* 0.0.0 
	* Initial Release