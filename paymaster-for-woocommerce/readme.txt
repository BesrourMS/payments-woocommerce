=== PayMaster for WooCommerce ===
Contributors: alexsaab
Tags: paymaster, payment getaway, woo commerce, woocommerce, ecommerce
Requires at least: 3.0
Tested up to: 3.3.2
Stable tag: trunk

Allows you to use Paymaster payment gateway with the WooCommerce plugin.

== Description ==
After activating the plugin through the control panel in WooCommerce, enter Merchant Login, secret phrase, encryption method, etc., you can find them in [paymaster personal account](https://psp.paymaster.tn/cpl)
In Paymaster, we prescribe as POST requests:
<ul style="list-style:none;">
<li>Result URL: http://your_domain/?wc-api=wc_paymaster&paymaster=result</li
<li>Success URL: http://your_domain/?wc-api=wc_paymaster&paymaster=success</li>
<li>Fail URL: http://your_domain/?wc-api=wc_paymaster&paymaster=fail</li>
<li>Sending data method: POST</li>
</ul>
Next, put the checkboxes:
<ul style="list-style:none;">
<li>Don't check account number uniqueness for declined payments</li>
<li>Resend Payment Notification on failures</li>
</ul>
More details on [plugin page](https://github.com/alexsaab/woocommerce-paymaster)
== Installation ==
1. Make sure you installed the latest version of [WooCommerce] (/www.woothemes.com/woocommerce)
2. Unpack archive and download "paymaster-for-woocommerce" in your domain/wp-content/plugins folder
Activate plugin.
== Changelog ==

== Changelog ==
= 1.4 =
* Bugs fixed + compatibility with php 7.3

= 1.3 =
* Bug fixes

= 1.2 =
* Bugs fixed

= 1.1 =
* Bug fixes

= 1.0 =
* Plugin release