<?php

// Streams a draft photo or preview to the customer who owns it.
//   ?f=20261104-abc....jpg   (a file in uploads/private/drafts/<your id>/)
// Files are looked up only in the signed-in customer's own folder, so another
// customer's file name gets a 404 even if it is guessed.
require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/DraftController.php";

$name = $_GET['f'] ?? '';
if (!is_logged_in() || !DraftController::isSafeName($name)) {
    http_response_code(404);
    exit;
}
$path = DraftController::folderFor(get_user_id()) . '/' . $name;
if (!is_file($path)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . (new finfo(FILEINFO_MIME_TYPE))->file($path));
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
if (ob_get_level()) {
    ob_end_clean();
}
readfile($path);
exit;
