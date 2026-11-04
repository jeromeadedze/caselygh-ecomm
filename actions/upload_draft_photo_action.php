<?php

// Stores one photo for a studio draft. Called by js/design.js before it saves
// the draft, one photo per request so each upload stays small. Answers in JSON.
require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/DraftController.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed.'], 405);
}
if (!is_logged_in()) {
    json_response(['success' => false, 'login' => true, 'error' => 'Log in to save drafts.'], 401);
}
verify_csrf(true);

$result = (new DraftController())->storePhoto(get_user_id(), $_FILES['photo'] ?? null);
json_response($result, $result['success'] ? 200 : 422);
