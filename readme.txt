=== WooCommerce Spam Filter ===
Contributors: marcuszeal
Tags: woocommerce, spam, security, paypal, checkout, bot protection, card testing
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Blocks card-testing bot attacks on WooCommerce/PayPal checkout endpoints with zero false positives for legitimate customers.

== Description ==

WooCommerce Spam Filter protects your store from card-testing bot attacks that exploit checkout endpoints. These bots hit your PayPal and WooCommerce checkout APIs directly without loading the checkout page, testing stolen credit card numbers.

= How It Works =

The plugin uses multiple detection layers that **cannot produce false positives** for legitimate customers:

* **JavaScript Token Verification** - A unique token is set when customers visit your checkout page. Bots hitting the API directly won't have this token.
* **WooCommerce Session Validation** - Legitimate customers have a session from browsing your store. API-only bots don't.
* **Rate Limiting** - IPs making excessive checkout attempts are flagged.
* **Referer Validation** - Requests should originate from your website.

= Key Features =

* **Zero False Positives** - Detection methods are based on signals that are always present for legitimate checkouts
* **Test Mode** - Log suspicious requests without blocking to verify accuracy first
* **Comprehensive Logging** - View all flagged requests with IP geolocation, user agent, and request details
* **Dashboard Widget** - Quick status overview on your WordPress dashboard
* **Admin Bar Indicator** - See protection status at a glance
* **WP-CLI Support** - Manage the plugin from command line
* **Export to CSV** - Export filtered logs for analysis
* **Auto-Cleanup** - Automatically delete old logs to save database space
* **IP Whitelist** - Never block specific IP addresses
* **HPOS Compatible** - Works with WooCommerce High-Performance Order Storage

= Protected Endpoints =

* `/?wc-ajax=ppc-create-order` - PayPal order creation
* `/?wc-ajax=ppc-approve-order` - PayPal order approval
* `/?wc-ajax=checkout` - WooCommerce AJAX checkout
* `/wp-json/wc/store/checkout` - WooCommerce Store API

= Requirements =

* WordPress 5.8 or higher
* WooCommerce 6.0 or higher
* PHP 7.4 or higher

== Installation ==

1. Upload the `woo-spam-filter` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooCommerce > Spam Filter to configure settings
4. **Important**: Leave Test Mode enabled for the first week to verify detection accuracy
5. Review the logs to ensure no legitimate customers are being flagged
6. Once confident, disable Test Mode to enable blocking

== Frequently Asked Questions ==

= Will this block legitimate customers? =

No. The detection methods rely on signals that are always present for legitimate checkouts:
- Customers always load the checkout page first (sets the token)
- Customers browse your store first (creates a session)
- Customers don't attempt checkout 10+ times per hour

= What is Test Mode? =

Test Mode logs suspicious requests without blocking them. This lets you verify the plugin is detecting bots correctly before enabling blocking. We recommend running in Test Mode for at least a week on new installations.

= How do I know if I'm under attack? =

Common signs include:
- Multiple failed PayPal transactions with `PAYEE_NOT_ENABLED_FOR_CARD_PROCESSING` errors
- Orders with fake names and email patterns like `firstname.lastname.######@gmail.com`
- Many small-value transactions in a short period

= Can I whitelist specific IPs? =

Yes. Go to WooCommerce > Spam Filter > Settings and add IP addresses to the whitelist. These IPs will never be blocked or logged.

= Does this work with PayPal for WooCommerce? =

Yes. The plugin specifically protects PayPal checkout endpoints that are commonly targeted by card-testing bots.

== Screenshots ==

1. Dashboard showing attack statistics and trend chart
2. Logs viewer with filtering and bulk actions
3. Settings page with Test Mode and detection sensitivity
4. Admin bar indicator showing protection status

== Changelog ==

= 1.1.0 =
* Added JavaScript token verification for zero false positives
* Added Test Mode (enabled by default for new installs)
* Added scoring system with configurable threshold
* Added rate limiting detection
* Added IP geolocation display
* Added attack trend chart visualization
* Added WP-CLI commands
* Added dashboard widget
* Added admin bar indicator
* Added bulk IP whitelist from logs
* Added copy IP button
* Added expandable log rows for full details
* Added date range and IP search filters
* Added CSV export with filters
* Added auto-cleanup for old logs
* Refactored admin interface with template system

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.1.0 =
Major update with improved detection that eliminates false positives. Test Mode is enabled by default - review logs before enabling blocking.
