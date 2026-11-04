<?php

// Paystack calls this URL in the background when a payment succeeds, even
// if the customer closed their browser before returning to the site.
// Set it in Paystack: Settings > API Keys & Webhooks > Webhook URL
//   https://YOUR-DOMAIN/actions/paystack_webhook_action.php
require_once __DIR__ . "/../core/app_config.php";
require_once __DIR__ . "/../controllers/PaymentController.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$body = file_get_contents('php://input');
$payments = new PaymentController();

// Reject anything Paystack did not sign with our secret key.
if (!$payments->isValidWebhook($body, $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? null)) {
    http_response_code(401);
    exit;
}

$event = json_decode($body, true);
if (($event['event'] ?? '') === 'charge.success' && !empty($event['data']['reference'])) {
    // verify() re-checks with Paystack and is safe to run twice.
    $payments->verify($event['data']['reference']);
}

http_response_code(200);
echo 'ok';
