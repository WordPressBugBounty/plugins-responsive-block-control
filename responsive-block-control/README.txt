=== Responsive Block Control - Hide blocks based on display width ===
Contributors: landwire
Donate link: https://saschapaukner.de
Tags: block, visibility, responsive, hide content, width
Requires at least: 5.2
Tested up to: 6.9
Stable tag: 1.3.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Security Contact: security@saschapaukner.de


Responsive Block Control adds responsive toggles to a "Visibility" panel of the block editor, to show or hide blocks according to screen width.

== Description ==
Responsive Block Control adds responsive toggles to a "Visibility" panel of the block editor, to show or hide blocks according to screen width.

== Security ==
**Version 1.3.1 resolves a stored cross‑site scripting (XSS) vulnerability (CVE‑2025‑62135) affecting earlier versions (<= 1.2.9).**
Users with contributor access or higher should update immediately.

If you discover a security vulnerability, please report it responsibly to: security@saschapaukner.de

= Limitations =
Does not work with the Classic Block, Widget Block or Widget Area Block ['core/freeform', 'core/legacy-widget', 'core/widget-area'], as the those blocks do not support block attributes. Does also not work with the HTML Block ['core/html'] inside the Widget Screen, as this one also does not support block attributes there.

== Installation ==
1. Upload `responsive-block-control.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Configuration ==

= Override existing breakpoints =

`function override_responsive_block_control_breakpoints($break_points) {
     $break_points['base'] = 0;
     $break_points['mobile'] = 400;
     $break_points['tablet'] = 800;
     $break_points['desktop'] = 1000;
     $break_points['wide'] = 1600;

     return $break_points;
 }

 add_filter('responsive_block_control_breakpoints', 'override_responsive_block_control_breakpoints');`

= Provide custom CSS =
You can provide your own CSS rules per breakpoint using the new filter responsive_block_control_custom_css_rules.

`add_filter('responsive_block_control_custom_css_rules', function($rules) {
    return [
        'mobile'  => 'display: none !important;',
        'tablet'  => 'display: none !important;',
        'desktop' => 'display: none !important;',
        'wide'    => 'display: none !important;',
    ];
});`

= Stop css output completely =

 `function override_responsive_block_control_add_css() {
     return false;
 }
 add_filter('responsive_block_control_breakpoints', 'override_responsive_block_control_add_css');`

== Frequently Asked Questions ==

= Is it possible to use different breakpoints? =

Yes, use the following code in your functions.php or similar.

`function override_responsive_block_control_breakpoints($break_points) {
     $break_points['base'] = 0;
     $break_points['mobile'] = 400;
     $break_points['tablet'] = 800;
     $break_points['desktop'] = 1000;
     $break_points['wide'] = 1600;

     return $break_points;
 }

 add_filter('responsive_block_control_breakpoints', 'override_responsive_block_control_breakpoints');`

= Can I provide custom CSS per breakpoint? I want to display: none instead of hiding just visually =

Yes, use the responsive_block_control_custom_css_rules filter:

`add_filter('responsive_block_control_custom_css_rules', function($rules) {
    return [
        'mobile'  => 'display: none !important;',
        'tablet'  => 'display: none !important;',
        'desktop' => 'display: none !important;',
        'wide'    => 'display: none !important;',
    ];
});`


This ensures the CSS respects the current breakpoints and applies to the correct .rbc-is-hidden-on-{breakpoint} classes.

= Can I write my own CSS and just use the classes? =

Yes, that is absolutely possible. Just use the filter below to stop the plugin from injecting its' styles:

`function override_responsive_block_control_add_css() {
     return false;
 }
 add_filter('responsive_block_control_breakpoints', 'override_responsive_block_control_add_css');`

== Screenshots ==

1. The 'Responsive Block Control' toggles at work in the block editor.

== Changelog ==
= 1.3.1 =
* Security fix: Mitigated authenticated Stored XSS (CVE‑2025‑62135).
* Added responsive_block_control_custom_css_rules filter to allow developers to provide their own CSS per breakpoint
* Fixed media query generation to match breakpoints correctly
* Ensured JS only injects CSS if customCss is present and sanitized

= 1.3.0 =
* Raised version after testing and package updates

= 1.2.9 =
* Added check to not load editor assets in the front end

= 1.2.8 =
* Updated asset loading for changes introduced in WordPress 6.3
* Added "Limitations" section to readme

= 1.2.7 =
Recompiled assets for distribution

= 1.2.6 =
* fixed translation for visibility information
* added check for Classic Block and disabled display of settings there

= 1.2.0 =
* fixed some JS logic
* added visibility information to blocks in Gutenberg editor

= 1.1.1 =
* fixed regex for adding classes in the frontend

= 1.1.0 =
* Removed the "blocks.getSaveContent.extraProps" JS filter as it causes block validation errors
* Instead we are using the recommended PHP filter "render_block" to add the necessary classes vie preg_replace()

= 1.0.0 =
* Initial Release of the plugin


== Upgrade Notice ==

Nothing to consider.
