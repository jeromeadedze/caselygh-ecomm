<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/CartController.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/cart.php');
}
verify_csrf();

if ((new CartController())->removeItem((int) ($_POST['cart_id'] ?? 0), get_cart_key())) {
    $_SESSION['success'] = "Item removed.";
}
redirect('../views/cart.php');
