=== WooCommerce Simplify Commerce Gateway ===
Contributors: automattic, woothemes, akeda, allendav, royho, slash1andy, woosteve, spraveenitpro, mikedmoore, fernashes, shellbeezy, mikejolley
Tags: credit card, simplify commerce, woocommerce, mastercard
Requires at least: 4.4
Tested up to: 4.5
Stable tag: 1.0.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

The Simplify Commerce gateway for WooCommerce 2.6+ lets you to take credit card payments directly on your store.

== Description ==

Start accepting credit card payments now. It’s that simple.

Picking the right payments extension for your WooCommerce store is important. You need online payments that are easy to set up and use, reliable, and don’t get in the way of making a sale.

Simplify Commerce by MasterCard gives you a merchant account and payment gateway in a single, secure package that takes just a few minutes to set up.

=== Why choose Simplify Commerce? ===

* Low, flat rate of 2.85% + 30¢ per transaction
* No hidden fees, no setup fees, no monthly fees
* Funds are deposited in your merchant bank account in two business days
* Accept MasterCard, Visa, American Express, Discover, JCB and Diners Club transactions
* Enjoy peace of mind from knowing your transactions are secure – all sensitive data is managed by Simplify Commerce

=== How to get started ===

1. [Sign up for and confirm your Simplify Commerce live account here](https://www.simplify.com/commerce/partners/woocommerce#/).
2. Get your live API key from Simplify at [Settings/API Keys](https://www.simplify.com/commerce/login/auth#/account/apiKeys).
3. Follow setup instructions from the [Simplify Commerce Documentation](http://docs.woothemes.com/document/simplify-commerce/).
4. You're ready to take payments in your WooCommerce store!

== Installation ==

Please note, this gateway requires WooCommerce 2.6 and above. Prior to 2.6, Simplify Commerce was bundled with WooCommerce core.

= Minimum Requirements =

* WordPress 4.4 or greater
* PHP version 5.3 or greater
* cURL

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To
do an automatic install of, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “WooCommerce Simplify Commerce Gateway” and click Search Plugins. Once you’ve found our plugin you can view details
about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your webserver via your favourite FTP application. The
WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

If on the off-chance you do encounter issues with the shop/category pages after an update you simply need to flush the permalinks by going
to WordPress > Settings > Permalinks and hitting 'save'. That should return things to normal.

== Frequently Asked Questions ==

= What countries does this plugin support? =

Simplify Commerce currently supports the US and Ireland.

= Does this support recurring payments, like for subscriptions? =

Yes!

= Does this support both production mode and sandbox mode for testing? =

Yes it does - production and sandbox mode is driven by the API keys you use.

= Where can I find documentation? =

For help setting up and configuring, please refer to our [user guide](https://docs.woothemes.com/document/simplify-commerce/)

= Where can I get support or talk to other users? =

If you get stuck, you can ask for help in the Plugin Forum.

== Screenshots ==

1. The settings panel used to configure the gateway.
2. Normal checkout with Simplify commerce.

== Changelog ==

= 1.0.2 =
* Fix javascript tokenization on guest checkout.

= 1.0.1 =
* Fixed uncaught exception.

= 1.0.0 =
* Initial release
