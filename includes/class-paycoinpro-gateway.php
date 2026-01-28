<?php
/**
 * PayCoinPro Payment Gateway
 *
 * @package PayCoinPro_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_Gateway_PayCoinPro class
 */
class WC_Gateway_PayCoinPro extends WC_Payment_Gateway {

    /**
     * API Key
     *
     * @var string
     */
    private $api_key;

    /**
     * API Secret
     *
     * @var string
     */
    private $api_secret;

    /**
     * Webhook Secret
     *
     * @var string
     */
    private $webhook_secret;

    /**
     * API URL
     *
     * @var string
     */
    private $api_url = 'https://paycoinpro.com/api/v1';

    /**
     * Constructor
     */
    public function __construct() {
        $this->id                 = 'paycoinpro';
        $this->icon               = PAYCOINPRO_PLUGIN_URL . 'assets/images/paycoinpro-icon.png';
        $this->has_fields         = false;
        $this->method_title       = 'PayCoinPro';
        $this->method_description = 'Accept cryptocurrency payments via PayCoinPro. Supports BTC, ETH, USDT, and more.';
        $this->supports           = array('products', 'refunds');

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        // Get settings
        $this->title          = $this->get_option('title');
        $this->description    = $this->get_option('description');
        $this->enabled        = $this->get_option('enabled');
        $this->api_key        = $this->get_option('api_key');
        $this->api_secret     = $this->get_option('api_secret');
        $this->webhook_secret = $this->get_option('webhook_secret');

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
    }

    /**
     * Initialize form fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => 'Enable/Disable',
                'type'    => 'checkbox',
                'label'   => 'Enable PayCoinPro Payment',
                'default' => 'no',
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'Payment method title shown to customers at checkout.',
                'default'     => 'Pay with Crypto',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'Payment method description shown to customers at checkout.',
                'default'     => 'Pay securely with Bitcoin, Ethereum, USDT, and other cryptocurrencies.',
                'desc_tip'    => true,
            ),
            'api_key' => array(
                'title'       => 'API Key',
                'type'        => 'text',
                'description' => 'Your PayCoinPro API key. Find it in your PayCoinPro dashboard.',
                'default'     => '',
                'desc_tip'    => true,
            ),
            'api_secret' => array(
                'title'       => 'API Secret',
                'type'        => 'password',
                'description' => 'Your PayCoinPro API secret.',
                'default'     => '',
                'desc_tip'    => true,
            ),
            'webhook_secret' => array(
                'title'       => 'Webhook Secret',
                'type'        => 'password',
                'description' => 'Your PayCoinPro webhook signing secret for verifying callbacks.',
                'default'     => '',
                'desc_tip'    => true,
            ),
            'webhook_url' => array(
                'title'       => 'Webhook URL',
                'type'        => 'title',
                'description' => '<strong>Configure this URL in your PayCoinPro dashboard:</strong><br><code>' . rest_url('paycoinpro/v1/webhook') . '</code>',
            ),
        );
    }

    /**
     * Process payment
     *
     * @param int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        if (!$order) {
            wc_add_notice('Order not found.', 'error');
            return array('result' => 'failure');
        }

        // Create invoice via PayCoinPro API
        $invoice = $this->create_invoice($order);

        if (is_wp_error($invoice)) {
            wc_add_notice($invoice->get_error_message(), 'error');
            return array('result' => 'failure');
        }

        // Store invoice ID in order meta
        $order->update_meta_data('_paycoinpro_invoice_id', $invoice['id']);
        $order->update_meta_data('_paycoinpro_payment_url', $invoice['payment_url']);
        $order->save();

        // Mark as pending payment
        $order->update_status('pending', 'Awaiting PayCoinPro cryptocurrency payment.');

        // Empty cart
        WC()->cart->empty_cart();

        // Redirect to PayCoinPro payment page
        return array(
            'result'   => 'success',
            'redirect' => $invoice['payment_url'],
        );
    }

    /**
     * Create invoice via PayCoinPro API
     *
     * @param WC_Order $order Order object.
     * @return array|WP_Error
     */
    private function create_invoice($order) {
        $payload = array(
            'amount'       => $order->get_total(),
            'currency'     => $order->get_currency(),
            'reference_id' => (string) $order->get_id(),
            'description'  => sprintf('Order #%s', $order->get_order_number()),
            'customer'     => array(
                'email' => $order->get_billing_email(),
                'name'  => $order->get_formatted_billing_full_name(),
            ),
            'redirect_url' => $this->get_return_url($order),
            'cancel_url'   => wc_get_checkout_url(),
            'metadata'     => array(
                'order_id'     => $order->get_id(),
                'order_key'    => $order->get_order_key(),
                'woocommerce'  => true,
                'plugin_version' => PAYCOINPRO_VERSION,
            ),
        );

        $response = $this->api_request('POST', '/invoices', $payload);

        if (is_wp_error($response)) {
            return $response;
        }

        if (empty($response['id']) || empty($response['payment_url'])) {
            return new WP_Error('paycoinpro_error', 'Invalid response from PayCoinPro API.');
        }

        return $response;
    }

    /**
     * Make API request to PayCoinPro
     *
     * @param string $method HTTP method.
     * @param string $endpoint API endpoint.
     * @param array  $data Request data.
     * @return array|WP_Error
     */
    private function api_request($method, $endpoint, $data = array()) {
        $url = $this->api_url . $endpoint;
        $body = !empty($data) ? wp_json_encode($data) : '';
        $timestamp = time();

        // Generate HMAC signature
        $signature_payload = $method . $endpoint . $body . $timestamp;
        $signature = hash_hmac('sha256', $signature_payload, $this->api_secret);

        $args = array(
            'method'  => $method,
            'timeout' => 30,
            'headers' => array(
                'Content-Type'       => 'application/json',
                'X-API-Key'          => $this->api_key,
                'X-Signature'        => $signature,
                'X-Timestamp'        => $timestamp,
            ),
        );

        if (!empty($body)) {
            $args['body'] = $body;
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status_code >= 400) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API error';
            return new WP_Error('paycoinpro_api_error', $error_message);
        }

        return $data;
    }

    /**
     * Handle webhook from PayCoinPro
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function handle_webhook($request) {
        $payload = $request->get_body();
        $signature = $request->get_header('X-Signature');
        $timestamp = $request->get_header('X-Timestamp');

        // Verify signature
        if (!$this->verify_webhook_signature($payload, $signature, $timestamp)) {
            return new WP_REST_Response(array('error' => 'Invalid signature'), 401);
        }

        $data = json_decode($payload, true);

        if (empty($data['event']) || empty($data['data'])) {
            return new WP_REST_Response(array('error' => 'Invalid payload'), 400);
        }

        $event = $data['event'];
        $invoice_data = $data['data'];

        // Get order from reference_id
        $order_id = isset($invoice_data['reference_id']) ? absint($invoice_data['reference_id']) : 0;
        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_REST_Response(array('error' => 'Order not found'), 404);
        }

        // Verify invoice ID matches
        $stored_invoice_id = $order->get_meta('_paycoinpro_invoice_id');
        if ($stored_invoice_id && $stored_invoice_id !== $invoice_data['id']) {
            return new WP_REST_Response(array('error' => 'Invoice ID mismatch'), 400);
        }

        // Process event
        switch ($event) {
            case 'invoice.paid':
                $this->handle_payment_complete($order, $invoice_data);
                break;

            case 'invoice.confirmed':
                $this->handle_payment_confirmed($order, $invoice_data);
                break;

            case 'invoice.expired':
                $this->handle_payment_expired($order, $invoice_data);
                break;

            case 'invoice.underpaid':
                $this->handle_payment_underpaid($order, $invoice_data);
                break;

            case 'invoice.overpaid':
                $this->handle_payment_overpaid($order, $invoice_data);
                break;

            default:
                // Log unknown event
                $order->add_order_note(sprintf('PayCoinPro: Received unknown event "%s"', $event));
        }

        return new WP_REST_Response(array('success' => true), 200);
    }

    /**
     * Verify webhook signature
     *
     * @param string $payload Request payload.
     * @param string $signature Signature header.
     * @param string $timestamp Timestamp header.
     * @return bool
     */
    private function verify_webhook_signature($payload, $signature, $timestamp) {
        if (empty($this->webhook_secret) || empty($signature) || empty($timestamp)) {
            return false;
        }

        // Check timestamp is within 5 minutes
        if (abs(time() - intval($timestamp)) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $this->webhook_secret);
        return hash_equals($expected, $signature);
    }

    /**
     * Handle payment complete event
     *
     * @param WC_Order $order Order object.
     * @param array    $invoice_data Invoice data.
     */
    private function handle_payment_complete($order, $invoice_data) {
        if ($order->has_status(array('processing', 'completed'))) {
            return;
        }

        // Store payment details
        $order->update_meta_data('_paycoinpro_crypto_amount', $invoice_data['crypto_amount'] ?? '');
        $order->update_meta_data('_paycoinpro_crypto_currency', $invoice_data['crypto_currency'] ?? '');
        $order->update_meta_data('_paycoinpro_transaction_id', $invoice_data['transaction_id'] ?? '');

        $order->payment_complete($invoice_data['transaction_id'] ?? '');
        $order->add_order_note(sprintf(
            'PayCoinPro payment complete. Amount: %s %s. TX: %s',
            $invoice_data['crypto_amount'] ?? 'N/A',
            $invoice_data['crypto_currency'] ?? 'N/A',
            $invoice_data['transaction_id'] ?? 'N/A'
        ));

        $order->save();
    }

    /**
     * Handle payment confirmed event (additional confirmations)
     *
     * @param WC_Order $order Order object.
     * @param array    $invoice_data Invoice data.
     */
    private function handle_payment_confirmed($order, $invoice_data) {
        $confirmations = $invoice_data['confirmations'] ?? 0;
        $order->add_order_note(sprintf('PayCoinPro: Payment has %d confirmations.', $confirmations));
        $order->save();
    }

    /**
     * Handle payment expired event
     *
     * @param WC_Order $order Order object.
     * @param array    $invoice_data Invoice data.
     */
    private function handle_payment_expired($order, $invoice_data) {
        if ($order->has_status(array('processing', 'completed', 'cancelled', 'failed'))) {
            return;
        }

        $order->update_status('failed', 'PayCoinPro: Payment expired - no payment received within time limit.');
    }

    /**
     * Handle underpaid event
     *
     * @param WC_Order $order Order object.
     * @param array    $invoice_data Invoice data.
     */
    private function handle_payment_underpaid($order, $invoice_data) {
        $order->update_status('on-hold', sprintf(
            'PayCoinPro: Underpaid - received %s %s, expected %s %s. Manual review required.',
            $invoice_data['received_amount'] ?? 'N/A',
            $invoice_data['crypto_currency'] ?? 'N/A',
            $invoice_data['expected_amount'] ?? 'N/A',
            $invoice_data['crypto_currency'] ?? 'N/A'
        ));
    }

    /**
     * Handle overpaid event
     *
     * @param WC_Order $order Order object.
     * @param array    $invoice_data Invoice data.
     */
    private function handle_payment_overpaid($order, $invoice_data) {
        $order->add_order_note(sprintf(
            'PayCoinPro: Customer overpaid - received %s %s, expected %s %s. Consider refunding the difference.',
            $invoice_data['received_amount'] ?? 'N/A',
            $invoice_data['crypto_currency'] ?? 'N/A',
            $invoice_data['expected_amount'] ?? 'N/A',
            $invoice_data['crypto_currency'] ?? 'N/A'
        ));

        // Still complete the payment
        $this->handle_payment_complete($order, $invoice_data);
    }

    /**
     * Process refund
     *
     * @param int    $order_id Order ID.
     * @param float  $amount Refund amount.
     * @param string $reason Refund reason.
     * @return bool|WP_Error
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_Error('paycoinpro_refund_error', 'Order not found.');
        }

        $invoice_id = $order->get_meta('_paycoinpro_invoice_id');

        if (empty($invoice_id)) {
            return new WP_Error('paycoinpro_refund_error', 'No PayCoinPro invoice ID found for this order.');
        }

        // Note: Full refund API integration would go here
        // For now, log the refund request for manual processing
        $order->add_order_note(sprintf(
            'PayCoinPro refund requested: %s %s. Reason: %s. Please process manually in PayCoinPro dashboard.',
            wc_price($amount),
            $order->get_currency(),
            $reason ?: 'No reason provided'
        ));

        return true;
    }

    /**
     * Thank you page content
     *
     * @param int $order_id Order ID.
     */
    public function thankyou_page($order_id) {
        $order = wc_get_order($order_id);

        if ($order && $order->has_status('pending')) {
            echo '<p class="paycoinpro-pending-notice">';
            echo 'Your payment is being processed. You will receive a confirmation email once the cryptocurrency payment is confirmed on the blockchain.';
            echo '</p>';
        }
    }

    /**
     * Email instructions
     *
     * @param WC_Order $order Order object.
     * @param bool     $sent_to_admin Sent to admin.
     * @param bool     $plain_text Plain text email.
     */
    public function email_instructions($order, $sent_to_admin, $plain_text = false) {
        if ($sent_to_admin || $order->get_payment_method() !== $this->id) {
            return;
        }

        if ($order->has_status('pending')) {
            $payment_url = $order->get_meta('_paycoinpro_payment_url');
            if ($payment_url) {
                if ($plain_text) {
                    echo "\n\nComplete your crypto payment here: " . $payment_url . "\n\n";
                } else {
                    echo '<p><strong>Complete your cryptocurrency payment:</strong> <a href="' . esc_url($payment_url) . '">' . esc_url($payment_url) . '</a></p>';
                }
            }
        }
    }
}
