<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/CommunityController.php";

require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/community.php');
}
verify_csrf();

if (empty($_POST['consent'])) {
    $_SESSION['error'] = "Confirm you're happy for Casely to share this photo.";
    redirect('../views/community.php#share');
}

$result = (new CommunityController())->submitPost(
    get_user_id(),
    (int) ($_POST['product_id'] ?? 0),
    $_FILES['post_image'] ?? null,
    $_POST['caption'] ?? '',
    $_POST['handle'] ?? ''
);

if ($result['success']) {
    $_SESSION['success'] = "Thanks! Your photo will appear once the Casely team approves it. Your discount code will show in your account.";
    redirect('../views/account.php');
}
$_SESSION['error'] = $result['error'];
redirect('../views/community.php#share');
