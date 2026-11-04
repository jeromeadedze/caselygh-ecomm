<?php

// Deletes one of the signed-in customer's drafts (from the account page).
require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/DraftController.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/account.php');
}
require_login();
verify_csrf();

$ok = (new DraftController())->deleteDraft((int) ($_POST['draft_id'] ?? 0), get_user_id());
$_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Draft deleted.' : 'That draft could not be deleted. Try again.';
redirect('../views/account.php#drafts');
