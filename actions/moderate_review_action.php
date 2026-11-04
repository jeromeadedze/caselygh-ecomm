<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/ReviewController.php";

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/admin/reviews.php');
}
verify_csrf();

$decision = $_POST['decision'] ?? '';
if ((new ReviewController())->moderate((int) ($_POST['review_id'] ?? 0), $decision)) {
    $_SESSION['success'] = $decision === 'approved' ? "Review approved." : "Review removed.";
} else {
    $_SESSION['error'] = "Unable to save that decision. Try again.";
}
redirect_back('../views/admin/reviews.php');
