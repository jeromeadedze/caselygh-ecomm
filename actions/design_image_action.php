<?php

// Streams a custom design (or its original photo) from the private uploads
// folder, but only to the customer who made it or to an admin.
//   ?id=12            -> the print-ready design PNG
//   ?id=12&source=1   -> the customer's original photo (admin only)
require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/CustomDesignController.php";

$controller = new CustomDesignController();
$design = $controller->getDesign((int) ($_GET['id'] ?? 0));
$wantSource = !empty($_GET['source']);

if (!$design || !$controller->canView($design, get_cart_key(), get_user_id(), is_admin()) || ($wantSource && !is_admin())) {
    http_response_code(404);
    exit;
}

$file = $wantSource ? $design['source_image'] : $design['design_image'];
$path = PRIVATE_UPLOAD_DIR . ($wantSource ? '/sources/' : '/designs/') . basename((string) $file);
if (!$file || !is_file($path)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . (new finfo(FILEINFO_MIME_TYPE))->file($path));
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
if (isset($_GET['download']) && is_admin()) {
    header('Content-Disposition: attachment; filename="casely-design-' . (int) $design['design_id'] . ($wantSource ? '-photo' : '') . '.' . pathinfo($path, PATHINFO_EXTENSION) . '"');
}
ob_end_clean();
readfile($path);
exit;
