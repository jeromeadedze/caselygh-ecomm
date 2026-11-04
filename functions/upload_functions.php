<?php

// Helpers for saving uploaded images safely.
// Public uploads (community photos) can be shown directly in the browser.
// Private uploads (custom designs and customers' original photos) sit in a
// folder the web server refuses to serve; they are only shown through
// actions/design_image_action.php, which checks who is asking.

if (!defined('PUBLIC_UPLOAD_DIR')) {
    define('PUBLIC_UPLOAD_DIR', __DIR__ . '/../uploads/public');
    define('PRIVATE_UPLOAD_DIR', __DIR__ . '/../uploads/private');
}

/**
 * Check and store one uploaded image.
 * - checks the real file type from its content (not the name the browser sent)
 * - rejects files over MAX_UPLOAD_BYTES
 * - gives the file a new random name so it cannot overwrite anything
 * Returns ['success' => true, 'filename' => '...'] or ['success' => false, 'error' => '...']
 */
function save_uploaded_image($file, $targetDir, array $allowed = ['image/jpeg', 'image/png', 'image/webp'])
{
    if (!$file || !isset($file['error'])) {
        return ['success' => false, 'error' => 'Add a photo, then try again.'];
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || $file['size'] > MAX_UPLOAD_BYTES) {
        return ['success' => false, 'error' => 'That photo is too large. Use one under ' . round(MAX_UPLOAD_BYTES / 1048576) . ' MB.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'The upload did not finish. Try again.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!in_array($mime, $allowed, true) || !isset($extensions[$mime]) || !getimagesize($file['tmp_name'])) {
        return ['success' => false, 'error' => 'Use a JPG, PNG or WebP photo.'];
    }

    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
        return ['success' => false, 'error' => 'Unable to save your photo. Try again in a moment.'];
    }

    $filename = date('Ymd') . '-' . bin2hex(random_bytes(10)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $targetDir . '/' . $filename)) {
        return ['success' => false, 'error' => 'Unable to save your photo. Try again in a moment.'];
    }
    return ['success' => true, 'filename' => $filename];
}
