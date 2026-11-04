<?php

// Called by js/design.js with fetch(). Answers in JSON.
require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/CustomDesignController.php";
require_once __DIR__ . "/../controllers/CartController.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed.'], 405);
}
verify_csrf(true);

if (is_admin()) {
    json_response(['success' => false, 'error' => 'Admins cannot add items to the cart.'], 403);
}

$settings = json_decode($_POST['settings'] ?? '{}', true);
$cartKey = get_cart_key();

$result = (new CustomDesignController())->saveDesign(
    $cartKey,
    get_user_id(),
    (int) ($_POST['model_id'] ?? 0),
    $_POST['case_type'] ?? '',
    $_POST['design_text'] ?? '',
    $settings,
    ($_POST['consent'] ?? '') === '1',
    $_FILES['design_png'] ?? null,
    $_FILES['source_photo'] ?? null
);

if (!$result['success']) {
    json_response($result, 422);
}

(new CartController())->addDesign($cartKey, get_user_id(), $result['design_id']);
$_SESSION['success'] = "Your custom case is in your cart!";
json_response(['success' => true, 'design_id' => $result['design_id']]);
