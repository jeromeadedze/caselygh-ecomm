<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/CommunityController.php";

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/admin/community.php');
}
verify_csrf();

$decision = $_POST['decision'] ?? '';
if ((new CommunityController())->moderate((int) ($_POST['post_id'] ?? 0), $decision)) {
    $_SESSION['success'] = $decision === 'approved' ? "Post approved and reward code created." : "Post removed from the wall.";
} else {
    $_SESSION['error'] = "That decision could not be saved.";
}
redirect_back('../views/admin/community.php');
