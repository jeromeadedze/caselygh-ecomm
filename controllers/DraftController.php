<?php

require_once __DIR__ . "/../classes/DraftClass.php";
require_once __DIR__ . "/../classes/ProductClass.php";
require_once __DIR__ . "/../functions/upload_functions.php";

/**
 * DraftController
 * Saves, loads and deletes studio drafts for signed-in customers.
 * Draft photos live in uploads/private/drafts/<customer_id>/ and are only
 * served to their owner by actions/draft_file_action.php.
 */
class DraftController
{
    const MAX_PHOTOS = 36;
    const MAX_SETTINGS_BYTES = 20000;

    private $model;

    public function __construct()
    {
        $this->model = new DraftClass();
    }

    public static function folderFor($customerId)
    {
        return PRIVATE_UPLOAD_DIR . '/drafts/' . (int) $customerId;
    }

    // A stored file name looks like 20261104-<20 hex>.<ext>; anything else is rejected.
    public static function isSafeName($name)
    {
        return is_string($name) && preg_match('/^\d{8}-[a-f0-9]{20}\.(jpg|png|webp)$/', $name) === 1;
    }

    /** Store one photo for a draft. Returns ['success' => bool, 'file' => name, 'error' => msg]. */
    public function storePhoto($customerId, $file)
    {
        $saved = save_uploaded_image($file, self::folderFor($customerId));
        return $saved['success']
            ? ['success' => true, 'file' => $saved['filename']]
            : ['success' => false, 'error' => 'Your photo could not be saved: ' . $saved['error']];
    }

    /**
     * Create a draft (no $draftId) or update one the customer owns.
     * $photos is the ordered list of file names already stored with storePhoto().
     */
    public function saveDraft($customerId, $draftId, $name, $modelId, $settings, $photos, $previewFile)
    {
        if (!is_array($settings)) {
            return ['success' => false, 'error' => 'Your design could not be read. Try saving again.'];
        }
        $settingsJson = json_encode($settings);
        if (strlen($settingsJson) > self::MAX_SETTINGS_BYTES) {
            return ['success' => false, 'error' => 'This design has too many settings to save.'];
        }

        $photos = is_array($photos) ? array_values($photos) : [];
        if (count($photos) > self::MAX_PHOTOS) {
            return ['success' => false, 'error' => 'A draft can hold up to ' . self::MAX_PHOTOS . ' photos.'];
        }
        $folder = self::folderFor($customerId);
        foreach ($photos as $p) {
            if (!self::isSafeName($p) || !is_file($folder . '/' . $p)) {
                return ['success' => false, 'error' => 'One of your photos is missing. Add it again and save.'];
            }
        }

        $modelId = (int) $modelId;
        if ($modelId && !(new ProductClass())->getModelById($modelId)) {
            $modelId = null;
        }
        $name = mb_substr(trim((string) $name), 0, 80) ?: 'My custom case';

        $existing = $draftId ? $this->model->getDraft((int) $draftId, $customerId) : null;
        if ($draftId && !$existing) {
            return ['success' => false, 'error' => 'That draft no longer exists. Save it as a new draft.'];
        }

        // A small preview image for the account page (optional).
        $preview = $existing['preview_image'] ?? null;
        if ($previewFile && ($previewFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $saved = save_uploaded_image($previewFile, $folder, ['image/png', 'image/jpeg']);
            if ($saved['success']) {
                $preview = $saved['filename'];
            }
        }

        $photosJson = json_encode($photos);
        if ($existing) {
            $this->model->updateDraft($existing['draft_id'], $customerId, $name, $modelId ?: null, $settingsJson, $photosJson, $preview);
            $id = (int) $existing['draft_id'];
        } else {
            $id = $this->model->createDraft($customerId, $name, $modelId ?: null, $settingsJson, $photosJson, $preview);
        }
        if (!$id) {
            return ['success' => false, 'error' => 'Your draft could not be saved. Try again.'];
        }
        $this->removeUnusedFiles($customerId);
        return ['success' => true, 'draft_id' => $id, 'name' => $name];
    }

    public function getDraft($draftId, $customerId)
    {
        $draft = $this->model->getDraft((int) $draftId, $customerId);
        if (!$draft) {
            return null;
        }
        $draft['settings'] = json_decode($draft['settings'], true) ?: [];
        $draft['photos'] = array_values(array_filter((array) json_decode($draft['photos'], true), [self::class, 'isSafeName']));
        return $draft;
    }

    public function getDrafts($customerId)
    {
        return $this->model->getDraftsByCustomer($customerId);
    }

    public function deleteDraft($draftId, $customerId)
    {
        $ok = $this->model->deleteDraft((int) $draftId, $customerId);
        $this->removeUnusedFiles($customerId);
        return $ok;
    }

    // Delete stored files no draft points to any more, e.g. photos removed
    // before saving or the old preview of a re-saved draft. Files younger than
    // a day are kept: they may be photos of a save that is still uploading.
    private function removeUnusedFiles($customerId)
    {
        $folder = self::folderFor($customerId);
        if (!is_dir($folder)) {
            return;
        }
        $inUse = $this->model->getFilesInUse($customerId);
        foreach (scandir($folder) as $f) {
            $path = $folder . '/' . $f;
            if (self::isSafeName($f) && !isset($inUse[$f]) && is_file($path) && filemtime($path) < time() - 86400) {
                unlink($path);
            }
        }
    }
}
