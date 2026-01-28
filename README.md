# PayCoinPro for WooCommerce

Accept cryptocurrency payments on your WooCommerce store with PayCoinPro.

## Features

- **Multiple Cryptocurrencies** - Accept BTC, ETH, USDT, and more
- **Automatic Conversion** - Prices shown in customer's currency, paid in crypto
- **Instant Notifications** - Real-time webhook updates for payment status
- **Secure** - HMAC-signed API requests and webhook verification
- **WooCommerce HPOS Compatible** - Works with High-Performance Order Storage

## Requirements

- WordPress 5.0+
- WooCommerce 5.0+
- PHP 7.4+
- PayCoinPro merchant account

## Installation

### From WordPress Admin

1. Download the latest release ZIP
2. Go to **Plugins > Add New > Upload Plugin**
3. Upload the ZIP file and click **Install Now**
4. Activate the plugin

### Manual Installation

1. Download and extract the plugin
2. Upload the `paycoinpro-woocommerce` folder to `/wp-content/plugins/`
3. Activate via **Plugins** menu in WordPress

## Configuration

1. Go to **WooCommerce > Settings > Payments**
2. Click **PayCoinPro** to configure
3. Enter your API credentials from the [PayCoinPro Dashboard](https://paycoinpro.com)
4. Copy the **Webhook URL** shown and add it to your PayCoinPro dashboard
5. Save changes

### API Credentials

You'll need:
- **API Key** - Your public API key
- **API Secret** - Your secret key for signing requests
- **Webhook Secret** - For verifying incoming webhooks

Find these in your PayCoinPro dashboard under **Settings > API Keys**.

## Webhook Events

The plugin handles these webhook events:

| Event | Action |
|-------|--------|
| `invoice.paid` | Marks order as Processing |
| `invoice.confirmed` | Adds confirmation note |
| `invoice.expired` | Marks order as Failed |
| `invoice.underpaid` | Marks order as On Hold |
| `invoice.overpaid` | Completes order + adds note |

## Support

- Documentation: [docs.paycoinpro.com](https://docs.paycoinpro.com)
- Support: [support@paycoinpro.com](mailto:support@paycoinpro.com)
- Website: [paycoinpro.com](https://paycoinpro.com)

## License

GPL-2.0+
