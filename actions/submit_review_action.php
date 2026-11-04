<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/ReviewController.php";

require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/community.php#reviews');
}
verify_csrf();

$result = (new ReviewController())->submit(get_user_id(), $_POST['rating'] ?? 0, $_POST['review_text'] ?? '');
if ($result['success']) {
    $_SESSION['success'] = "Thanks for your review. It will appear once the Casely team approves it.";
} else {
    $_SESSION['error'] = $result['error'];
}
redirect('../views/community.php#reviews');
