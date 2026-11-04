<?php

require_once __DIR__ . "/../core/db_class.php";

/**
 * DraftClass (Model)
 * Unfinished studio designs saved by signed-in customers.
 * Every query is scoped to the customer, so nobody can read or change
 * another customer's draft by guessing an id.
 */
class DraftClass extends Database
{
    public function createDraft($customerId, $name, $modelId, $settingsJson, $photosJson, $preview)
    {
        $sql = "INSERT INTO design_drafts (customer_id, draft_name, model_id, settings, photos, preview_image)
                VALUES (?, ?, ?, ?, ?, ?)";
        $ok = $this->execute($sql, [$customerId, $name, $modelId, $settingsJson, $photosJson, $preview]);
        return $ok ? (int) $this->getLastInsertId() : false;
    }

    public function updateDraft($draftId, $customerId, $name, $modelId, $settingsJson, $photosJson, $preview)
    {
        $sql = "UPDATE design_drafts
                SET draft_name = ?, model_id = ?, settings = ?, photos = ?, preview_image = ?, updated_at = CURRENT_TIMESTAMP
                WHERE draft_id = ? AND customer_id = ?";
        return $this->execute($sql, [$name, $modelId, $settingsJson, $photosJson, $preview, $draftId, $customerId]);
    }

    public function getDraft($draftId, $customerId)
    {
        $sql = "SELECT d.*, m.model_name
                FROM design_drafts d
                LEFT JOIN phone_models m ON m.model_id = d.model_id
                WHERE d.draft_id = ? AND d.customer_id = ?";
        return $this->fetchOne($sql, [$draftId, $customerId]);
    }

    public function getDraftsByCustomer($customerId)
    {
        $sql = "SELECT d.draft_id, d.draft_name, d.preview_image, d.updated_at, m.model_name
                FROM design_drafts d
                LEFT JOIN phone_models m ON m.model_id = d.model_id
                WHERE d.customer_id = ?
                ORDER BY d.updated_at DESC";
        return $this->fetchAll($sql, [$customerId]);
    }

    public function deleteDraft($draftId, $customerId)
    {
        return $this->execute("DELETE FROM design_drafts WHERE draft_id = ? AND customer_id = ?", [$draftId, $customerId]);
    }

    // Every file name still used by any of this customer's drafts (photos and previews).
    public function getFilesInUse($customerId)
    {
        $rows = $this->fetchAll("SELECT photos, preview_image FROM design_drafts WHERE customer_id = ?", [$customerId]);
        $files = [];
        foreach ($rows as $r) {
            foreach ((array) json_decode($r['photos'], true) as $f) {
                $files[$f] = true;
            }
            if ($r['preview_image']) {
                $files[$r['preview_image']] = true;
            }
        }
        return $files;
    }
}
