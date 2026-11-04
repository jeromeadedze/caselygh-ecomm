<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/ProductController.php";

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/admin/products.php');
}
verify_csrf();

$active = !empty($_POST['active']);
(new ProductController())->setActive((int) ($_POST['product_id'] ?? 0), $active);
$_SESSION['success'] = $active ? "Case is now shown in the shop." : "Case hidden from the shop.";
redirect('../views/admin/products.php');
