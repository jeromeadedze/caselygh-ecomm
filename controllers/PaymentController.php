<?php

require_once __DIR__ . "/OrderController.php";
require_once __DIR__ . "/ProductController.php";
require_once __DIR__ . "/CartController.php";

/**
 * PaymentController
 * Talks to Paystack. Customers pay on Paystack's own secure page
 * (MoMo or card), so card and MoMo details never touch our server.
 *
 * Flow: initialize() -> customer pays on Paystack -> Paystack redirects to
 * actions/paystack_callback_action.php -> verify() confirms with Paystack's
 * server before the order is marked paid. The webhook does the same check
 * in the background in case the customer closes the browser.
 */
class PaymentController
{
    const API = 'https://api.paystack.co';

    private $orders;

    public function __construct()
    {
        $this->orders = new OrderController();
    }

    /**
     * Start a payment for an order. Returns ['success' => bool, 'url' => Paystack checkout URL, 'error' => string]
     */
    public function initialize(array $order, $email)
    {
        $reference = $order['invoice_no'] . '-' . bin2hex(random_bytes(4));
        $this->orders->createPayment($order['order_id'], $reference, $order['total']);

        $response = $this->request('POST', '/transaction/initialize', [
            'email' => $email,
            'amount' => (int) round($order['total'] * 100), // pesewas
            'currency' => 'GHS',
            'reference' => $reference,
            'callback_url' => APP_URL . '/actions/paystack_callback_action.php',
            'channels' => ['mobile_money', 'card'],
            'metadata' => [
                'order_id' => $order['order_id'],
                'invoice_no' => $order['invoice_no'],
            ],
        ]);

        if (!$response || empty($response['status']) || empty($response['data']['authorization_url'])) {
            $this->orders->markPaymentFailed($reference);
            // Paystack's own wording goes to the log, not to the customer.
            error_log('Paystack could not start payment: ' . ($response['message'] ?? 'no response'));
            return ['success' => false, 'error' => 'Unable to start payment. Your cart is safe, so try again in a moment.'];
        }
        return ['success' => true, 'url' => $response['data']['authorization_url'], 'reference' => $reference];
    }

    /**
     * Ask Paystack whether a payment really succeeded and for the right amount,
     * then mark the order paid and reduce stock.
     * Returns ['success' => bool, 'order_id' => int, 'error' => string]
     */
    public function verify($reference)
    {
        $payment = $this->orders->getPaymentByReference($reference);
        if (!$payment) {
            return ['success' => false, 'error' => 'Unable to find that payment. Check My orders, and message us on WhatsApp at 0509868659 if you were charged.'];
        }
        if ($payment['status'] === 'success') {
            return ['success' => true, 'order_id' => (int) $payment['order_id']];
        }

        $response = $this->request('GET', '/transaction/verify/' . rawurlencode($reference));
        $data = $response['data'] ?? null;

        $expected = (int) round($payment['amount'] * 100);
        $paid = $data
            && ($data['status'] ?? '') === 'success'
            && (int) ($data['amount'] ?? 0) === $expected
            && strtoupper($data['currency'] ?? '') === 'GHS';

        if (!$paid) {
            if ($data && in_array($data['status'] ?? '', ['failed', 'abandoned'], true)) {
                $this->orders->markPaymentFailed($reference);
            }
            return ['success' => false, 'order_id' => (int) $payment['order_id'], 'error' => 'Your payment was not completed.'];
        }

        // markPaid returns true only the first time, so stock is reduced once.
        if ($this->orders->markPaid($reference, $data['channel'] ?? null)) {
            $this->afterPaid((int) $payment['order_id']);
        }
        return ['success' => true, 'order_id' => (int) $payment['order_id']];
    }

    // Paystack signs webhooks with our secret key; reject anything unsigned.
    public function isValidWebhook($body, $signature)
    {
        return is_string($signature) && hash_equals(hash_hmac('sha512', $body, PAYSTACK_SECRET_KEY), $signature);
    }

    private function afterPaid($orderId)
    {
        $products = new ProductController();
        foreach ($this->orders->getOrderItems($orderId) as $item) {
            if ($item['variant_id']) {
                $products->decreaseStock($item['variant_id'], $item['qty']);
            }
        }
    }

    protected function request($method, $path, ?array $body = null)
    {
        $ch = curl_init(self::API . $path);
        $headers = ['Authorization: Bearer ' . PAYSTACK_SECRET_KEY, 'Content-Type: application/json'];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $raw = curl_exec($ch);
        if ($raw === false) {
            error_log('Paystack request failed: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }
        curl_close($ch);
        return json_decode($raw, true);
    }
}
