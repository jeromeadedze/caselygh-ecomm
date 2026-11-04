<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/ProductController.php";
require_once __DIR__ . "/../functions/upload_functions.php";

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/admin/products.php');
}
verify_csrf();

$productId = (int) ($_POST['product_id'] ?? 0);
$back = '../views/admin/products.php' . ($productId ? '?edit=' . $productId : '');

// Photo is required for a new product, optional when editing.
$image = null;
if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $upload = save_uploaded_image($_FILES['image'], __DIR__ . '/../images/products');
    if (!$upload['success']) {
        $_SESSION['error'] = $upload['error'];
        redirect($back);
    }
    $image = $upload['filename'];
} elseif (!$productId) {
    $_SESSION['error'] = "Add a photo of the case.";
    redirect($back);
}

$result = (new ProductController())->saveProduct([
    'title' => $_POST['title'] ?? '',
    'collection_id' => $_POST['collection_id'] ?? 0,
    'price' => $_POST['price'] ?? 0,
    'desc' => $_POST['desc'] ?? '',
    'keywords' => $_POST['keywords'] ?? '',
    'stock' => $_POST['stock'] ?? 10,
    'model_ids' => $_POST['model_ids'] ?? [],
], $image, $productId);

if (!$result['success']) {
    $_SESSION['error'] = $result['error'];
    redirect($back);
}
$_SESSION['success'] = $productId ? "Changes saved." : "Case added to the shop.";
redirect('../views/admin/products.php?edit=' . (int) $result['product_id']);
