<?php

// Puts one of the customer's past custom designs back in their cart.
require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/CustomDesignController.php";
require_once __DIR__ . "/../controllers/CartController.php";

require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/design.php');
}
verify_csrf();

$design = (new CustomDesignController())->getDesign((int) ($_POST['design_id'] ?? 0));
if (!$design || (int) $design['customer_id'] !== (int) get_user_id()) {
    $_SESSION['error'] = "We could not find that design. Check My account for your orders.";
    redirect('../views/design.php');
}
if ($design['status'] === 'rejected') {
    $_SESSION['error'] = "This design wasn't approved for printing. Start a new design instead.";
    redirect('../views/design.php');
}
(new CartController())->addDesign(get_cart_key(), get_user_id(), (int) $design['design_id']);
$_SESSION['success'] = "Added to your cart.";
redirect('../views/cart.php');
