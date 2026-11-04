<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/ProductController.php";

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/admin/products.php');
}
verify_csrf();

if ((new ProductController())->addModel((int) ($_POST['brand_id'] ?? 0), $_POST['model_name'] ?? '')) {
    $_SESSION['success'] = "Phone model added. Tick it on each case that fits it.";
} else {
    $_SESSION['error'] = "Enter a model name.";
}
redirect_back('../views/admin/products.php');
