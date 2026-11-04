<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/CartController.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/shop.php');
}
verify_csrf();

// The Casely team manages the shop; they don't use a cart.
if (is_admin()) {
    $_SESSION['warning'] = "Admins cannot add items to the cart.";
    redirect_back('../views/shop.php');
}

$result = (new CartController())->addProduct(
    get_cart_key(),
    get_user_id(),
    (int) ($_POST['product_id'] ?? 0),
    (int) ($_POST['model_id'] ?? 0),
    $_POST['case_type'] ?? '',
    (int) ($_POST['qty'] ?? 1)
);

if ($result['success']) {
    $_SESSION['success'] = "Added to your cart!";
} else {
    $_SESSION['error'] = $result['error'];
}
redirect_back('../views/shop.php');
