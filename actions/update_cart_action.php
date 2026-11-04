<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/CartController.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/cart.php');
}
verify_csrf();

$qty = (int) ($_POST['qty'] ?? 0);
if ($qty >= 1) {
    (new CartController())->updateQty((int) ($_POST['cart_id'] ?? 0), $qty, get_cart_key());
}
redirect('../views/cart.php');
