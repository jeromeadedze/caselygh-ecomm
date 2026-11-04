<?php

// Paystack sends the customer back here after they pay (or cancel).
// We never trust the redirect alone: PaymentController::verify() asks
// Paystack's server whether the payment succeeded and for the right amount.
require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/PaymentController.php";
require_once __DIR__ . "/../controllers/CartController.php";

$reference = preg_replace('/[^A-Za-z0-9\-]/', '', $_GET['reference'] ?? ($_GET['trxref'] ?? ''));
if ($reference === '') {
    redirect('../views/account.php');
}

$result = (new PaymentController())->verify($reference);

if ($result['success']) {
    (new CartController())->clearCart(get_cart_key());
    $_SESSION['success'] = "Payment received. Thank you!";
} else {
    $_SESSION['error'] = $result['error'];
}

if (!empty($result['order_id'])) {
    redirect('../views/order_confirmation.php?order=' . (int) $result['order_id']);
}
redirect('../views/account.php');
