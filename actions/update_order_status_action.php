<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/OrderController.php";

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/admin/orders.php');
}
verify_csrf();

$status = $_POST['order_status'] ?? '';
if ((new OrderController())->updateStatus((int) ($_POST['order_id'] ?? 0), $status)) {
    $_SESSION['success'] = "Order marked as " . strtolower(order_status_label($status)) . ".";
} else {
    $_SESSION['error'] = "Unable to save that status. Try again.";
}
redirect_back('../views/admin/orders.php');
