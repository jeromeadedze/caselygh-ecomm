<?php

// Creates or updates a studio draft for the signed-in customer.
// Called by js/design.js after its photos are uploaded. Answers in JSON.
require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/DraftController.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed.'], 405);
}
if (!is_logged_in()) {
    json_response(['success' => false, 'login' => true, 'error' => 'Sign in to save drafts.'], 401);
}
verify_csrf(true);

$result = (new DraftController())->saveDraft(
    get_user_id(),
    (int) ($_POST['draft_id'] ?? 0) ?: null,
    $_POST['name'] ?? '',
    $_POST['model_id'] ?? 0,
    json_decode($_POST['settings'] ?? '', true),
    json_decode($_POST['photos'] ?? '[]', true),
    $_FILES['preview'] ?? null
);
json_response($result, $result['success'] ? 200 : 422);
