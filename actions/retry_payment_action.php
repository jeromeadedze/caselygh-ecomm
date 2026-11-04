<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/OrderController.php";
require_once __DIR__ . "/../controllers/PaymentController.php";

require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/account.php');
}
verify_csrf();

$order = (new OrderController())->getOrder((int) ($_POST['order_id'] ?? 0));
if (!$order || (int) $order['customer_id'] !== (int) get_user_id() || $order['order_status'] !== 'pending_payment') {
    $_SESSION['error'] = "That order can't be paid for again. Check its status in My orders.";
    redirect('../views/account.php');
}

$payment = (new PaymentController())->initialize($order, $_SESSION['customer_email']);
if (!$payment['success']) {
    $_SESSION['error'] = $payment['error'];
    redirect('../views/order_confirmation.php?order=' . (int) $order['order_id']);
}
redirect($payment['url']);
