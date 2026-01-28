=== PayCoinPro for WooCommerce ===
Contributors: paycoinpro
Tags: crypto, cryptocurrency, bitcoin, ethereum, usdt, woocommerce, payment gateway, btc, eth, crypto payments
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept cryptocurrency payments on your WooCommerce store with PayCoinPro. Support for BTC, ETH, USDT, and more.

== Description ==

PayCoinPro for WooCommerce allows you to accept cryptocurrency payments directly on your online store. Customers can pay with Bitcoin, Ethereum, USDT, and other popular cryptocurrencies while you receive settlements in your preferred currency.

= Features =

* **Multiple Cryptocurrencies** - Accept BTC, ETH, USDT, USDC, and more
* **Automatic Conversion** - Display prices in fiat, receive crypto payments
* **Real-time Notifications** - Instant webhook updates for payment status
* **Secure Authentication** - HMAC-signed API requests
* **WooCommerce HPOS Compatible** - Works with High-Performance Order Storage
* **Refund Support** - Process refunds through PayCoinPro dashboard

= How It Works =

1. Customer selects "Pay with Crypto" at checkout
2. They're redirected to PayCoinPro's secure payment page
3. Customer sends cryptocurrency to the provided address
4. Once confirmed on blockchain, order is automatically marked as paid
5. You receive the funds in your PayCoinPro account

= Requirements =

* WooCommerce 5.0 or higher
* PHP 7.4 or higher
* PayCoinPro merchant account ([Sign up free](https://paycoinpro.com))

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/paycoinpro-woocommerce/` or install through WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to WooCommerce > Settings > Payments and click on "PayCoinPro"
4. Enter your API Key, API Secret, and Webhook Secret from your PayCoinPro dashboard
5. Copy the Webhook URL shown and add it to your PayCoinPro account settings
6. Save changes and start accepting crypto!

== Frequently Asked Questions ==

= Do I need a PayCoinPro account? =

Yes, you need a free PayCoinPro merchant account. Sign up at [paycoinpro.com](https://paycoinpro.com).

= Which cryptocurrencies are supported? =

PayCoinPro supports Bitcoin (BTC), Ethereum (ETH), Tether (USDT), USD Coin (USDC), and many more. Check your PayCoinPro dashboard for the full list.

= How long do payments take to confirm? =

Confirmation times depend on the cryptocurrency and network congestion. Bitcoin typically takes 10-60 minutes, while Ethereum and stablecoins are usually faster.

= Are there any fees? =

PayCoinPro charges a small percentage fee per transaction. See [paycoinpro.com/pricing](https://paycoinpro.com/pricing) for current rates.

= Is it secure? =

Yes. All API requests are signed with HMAC-SHA256, and webhooks are verified using your secret key. No sensitive payment data is stored on your WordPress site.

== Screenshots ==

1. Payment method at checkout
2. PayCoinPro payment page
3. Plugin settings in WooCommerce
4. Order with crypto payment details

== Changelog ==

= 1.0.0 =
* Initial release
* WooCommerce payment gateway integration
* Webhook support for payment notifications
* HPOS compatibility

== Upgrade Notice ==

= 1.0.0 =
Initial release of PayCoinPro for WooCommerce.
