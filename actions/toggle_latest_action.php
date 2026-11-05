<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/ProductController.php";

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/admin/products.php');
}
verify_csrf();

$featured = !empty($_POST['featured']);
$result = (new ProductController())->setFeatured((int) ($_POST['product_id'] ?? 0), $featured);
if ($result['success']) {
    $_SESSION['success'] = $featured ? "Case added to Latest designs." : "Case removed from Latest designs.";
} else {
    $_SESSION['error'] = $result['error'];
}
redirect('../views/admin/products.php#all-cases');
