<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/CartController.php";
require_once __DIR__ . "/../controllers/OrderController.php";
require_once __DIR__ . "/../controllers/PaymentController.php";

require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/checkout.php');
}
verify_csrf();

$cartCtrl = new CartController();
$items = $cartCtrl->getCartItems(get_cart_key());
if ($problems = $cartCtrl->findStockProblems($items)) {
    $_SESSION['error'] = "Not enough stock for: " . implode(', ', $problems) . ". Lower the quantity to check out.";
    redirect('../views/cart.php');
}

$delivery = [
    'name' => $_POST['delivery_name'] ?? '',
    'phone' => $_POST['delivery_phone'] ?? '',
    'address' => $_POST['delivery_address'] ?? '',
    'city' => $_POST['delivery_city'] ?? '',
];

$orderCtrl = new OrderController();
$created = $orderCtrl->createFromCart(get_user_id(), $delivery, $items);
if (!$created['success']) {
    $_SESSION['error'] = $created['error'];
    $_SESSION['checkout_old'] = $delivery;
    redirect('../views/checkout.php');
}

// The cart is cleared only after Paystack confirms payment, so a customer
// who backs out of payment still has their cart.
$order = $orderCtrl->getOrder($created['order_id']);
$payment = (new PaymentController())->initialize($order, $_SESSION['customer_email']);
if (!$payment['success']) {
    $_SESSION['error'] = $payment['error'];
    redirect('../views/order_confirmation.php?order=' . (int) $order['order_id']);
}

// Off to Paystack's secure checkout page.
redirect($payment['url']);
