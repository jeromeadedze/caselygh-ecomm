<?php

require_once __DIR__ . "/../classes/CustomDesignClass.php";
require_once __DIR__ . "/../classes/ProductClass.php";
require_once __DIR__ . "/../functions/upload_functions.php";

/**
 * CustomDesignController
 * Saves designs from the custom case studio and handles the
 * team's approve / reject decisions.
 */
class CustomDesignController
{
    private $designModel;
    private $productModel;

    public function __construct()
    {
        $this->designModel = new CustomDesignClass();
        $this->productModel = new ProductClass();
    }

    /**
     * Save a studio design.
     * $designFile = the PNG the studio exported; $sourceFile = the customer's photo (optional).
     * Returns ['success' => bool, 'design_id' => int, 'error' => string]
     */
    public function saveDesign($cartKey, $customerId, $modelId, $caseType, $text, $settings, $designFile, $sourceFile)
    {
        if (!in_array($caseType, ['Standard', 'Tough'], true)) {
            return ['success' => false, 'error' => 'Choose a case type.'];
        }
        if (!$this->productModel->getModelById($modelId)) {
            return ['success' => false, 'error' => 'Choose your phone model.'];
        }

        // PNG normally; large collages arrive as high-quality JPEG to stay under upload limits.
        $design = save_uploaded_image($designFile, PRIVATE_UPLOAD_DIR . '/designs', ['image/png', 'image/jpeg']);
        if (!$design['success']) {
            return ['success' => false, 'error' => 'Your design could not be saved: ' . $design['error']];
        }

        $sourceName = null;
        if ($sourceFile && ($sourceFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $source = save_uploaded_image($sourceFile, PRIVATE_UPLOAD_DIR . '/sources');
            if (!$source['success']) {
                return ['success' => false, 'error' => 'Your photo could not be saved: ' . $source['error']];
            }
            $sourceName = $source['filename'];
        }

        $text = mb_substr(trim((string) $text), 0, 14);
        $price = price_for(CUSTOM_CASE_PRICE, $caseType);
        $settingsJson = json_encode(is_array($settings) ? $settings : []);

        $id = $this->designModel->createDesign($cartKey, $customerId, $modelId, $caseType, $design['filename'], $sourceName, $text, $settingsJson, $price);
        return $id ? ['success' => true, 'design_id' => $id] : ['success' => false, 'error' => 'Unable to save your design. Try again.'];
    }

    public function getDesignsByCustomer($customerId, $limit = 50)
    {
        return $this->designModel->getDesignsByCustomer($customerId, $limit);
    }

    public function getDesign($designId)
    {
        return $this->designModel->getDesign($designId);
    }

    public function getDesignsForReview($status = 'pending')
    {
        return $this->designModel->getDesignsForReview($status);
    }

    public function countPendingPaid()
    {
        return $this->designModel->countPendingPaid();
    }

    public function review($designId, $decision, $note)
    {
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            return false;
        }
        if ($decision === 'rejected' && trim($note) === '') {
            return false;
        }
        return $this->designModel->setStatus($designId, $decision, trim($note) ?: null);
    }

    // Who may see a design image: its owner (same cart or account) or an admin.
    public function canView(array $design, $cartKey, $customerId, $isAdmin)
    {
        return $isAdmin
            || hash_equals($design['cart_key'], (string) $cartKey)
            || ($customerId && (int) $design['customer_id'] === (int) $customerId);
    }
}
