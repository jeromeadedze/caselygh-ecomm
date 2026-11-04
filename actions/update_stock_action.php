<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/ProductController.php";

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/admin/products.php');
}
verify_csrf();

$productCtrl = new ProductController();
$productId = (int) ($_POST['product_id'] ?? 0);
$allowed = array_column($productCtrl->getVariantsForProduct($productId), 'variant_id');

foreach ((array) ($_POST['stock'] ?? []) as $variantId => $stock) {
    // Only update variants that belong to this product.
    if (in_array((int) $variantId, array_map('intval', $allowed), true)) {
        $productCtrl->updateVariantStock((int) $variantId, (int) $stock);
    }
}
$_SESSION['success'] = "Stock updated.";
redirect('../views/admin/products.php?edit=' . $productId);
