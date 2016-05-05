=== Inspect Gravity Forms ===
Contributors: webaware
Plugin Name: Inspect Gravity Forms
Plugin URI: https://wordpress.org/plugins/inspect-gravityforms/
Author URI: http://webaware.com.au/
Donate link: http://shop.webaware.com.au/donations/?donation_for=Inspect+Gravity+Forms
Tags: gravityforms, gravity forms, gravity
Requires at least: 4.2
Tested up to: 4.5.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add-on to Gravity Forms that shows some form info in the forms list

== Description ==

[Gravity Forms](http://webaware.com.au/get-gravity-forms) is such a versatile form builder that you can quickly accumulate a big list of forms on a busy website. Some of those forms will integrate with mailing lists, some with credit card payment gateways, some with Help Scout, some manage user registrations, some... and before you know it, you can't easily find that form you're looking for.

This add-on lets you quickly see what a form is doing, whether it has:

* credit card field
* user registration feed
* PayPal
* Authorize.Net
* Stripe
* DPS PxPay
* eWAY
* MailChimp
* Campaign Monitor
* Help Scout

With more tests to come. It does it by adding a set of icons next to the actions links under each form name in the forms list.

= Requirements =

* you need to install the [Gravity Forms](http://webaware.com.au/get-gravity-forms) plugin

== Installation ==

1. Either install automatically through the WordPress admin, or download the .zip file, unzip to a folder, and upload the folder to your /wp-content/plugins/ directory. Read [Installing Plugins](https://codex.wordpress.org/Managing_Plugins#Installing_Plugins) in the WordPress Codex for details.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Install and activate the [Gravity Forms](http://webaware.com.au/get-gravity-forms) plugin.

== Frequently Asked Questions ==

= Why don't you have an icon for my favorite integration? =

Because you haven't asked me for it. So [ask me](https://wordpress.org/support/plugin/inspect-gravityforms)!

If you want to add a custom integration icon, you can hook the filter hook `inspect_gravityforms_icon_list` and add it yourself.

= Will this plugin work without installing Gravity Forms? =

No. This plugin integrates with Gravity Forms. You must purchase and install a copy of the [Gravity Forms](http://webaware.com.au/get-gravity-forms) plugin too.

== Screenshots ==

1. A sample list of forms with one form showing information icons

== Filter hooks ==

* `inspect_gravityforms_icon_list` for modifying the icon list (e.g. add your own ones)

== Contributions ==

* [Fork me on GitHub](https://github.com/webaware/inspect-gravityforms/)

== Changelog ==

### 1.0.0, 2016-05-05

* initial public release