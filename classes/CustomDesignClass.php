<?php

require_once __DIR__ . "/../core/db_class.php";

/**
 * CustomDesignClass (Model)
 * Designs made in the custom case studio. Every design waits for the
 * Casely team's approval before it is printed.
 */
class CustomDesignClass extends Database
{
    public function createDesign($cartKey, $customerId, $modelId, $caseType, $designImage, $sourceImage, $text, $settingsJson, $price)
    {
        $sql = "INSERT INTO custom_designs
                (cart_key, customer_id, model_id, case_type, design_image, source_image, design_text, settings, price, consent_given)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $ok = $this->execute($sql, [$cartKey, $customerId, $modelId, $caseType, $designImage, $sourceImage, $text, $settingsJson, $price]);
        return $ok ? (int) $this->getLastInsertId() : false;
    }

    // A customer's past designs, newest first. "ordered" means it reached a paid order.
    public function getDesignsByCustomer($customerId, $limit = 50)
    {
        $sql = "SELECT d.design_id, d.case_type, d.design_text, d.price, d.status, d.created_at, m.model_name,
                       EXISTS (SELECT 1 FROM orderdetails od JOIN orders o ON o.order_id = od.order_id
                               WHERE od.design_id = d.design_id AND o.order_status NOT IN ('pending_payment', 'cancelled')) AS ordered
                FROM custom_designs d
                JOIN phone_models m ON m.model_id = d.model_id
                WHERE d.customer_id = ?
                ORDER BY d.created_at DESC, d.design_id DESC LIMIT " . (int) $limit;
        return $this->fetchAll($sql, [$customerId]);
    }

    public function getDesign($designId)
    {
        $sql = "SELECT d.*, m.model_name, b.brand_name, c.customer_name, c.customer_email
                FROM custom_designs d
                JOIN phone_models m ON m.model_id = d.model_id
                JOIN phone_brands b ON b.brand_id = m.brand_id
                LEFT JOIN customer c ON c.customer_id = d.customer_id
                WHERE d.design_id = ?";
        return $this->fetchOne($sql, [$designId]);
    }

    /**
     * Designs for the admin approval queue. Only designs that are part of a
     * paid order are worth reviewing, so those come with their invoice number.
     */
    public function getDesignsForReview($status = 'pending')
    {
        $sql = "SELECT d.*, m.model_name, c.customer_name, o.invoice_no, o.order_id, o.order_status
                FROM custom_designs d
                JOIN phone_models m ON m.model_id = d.model_id
                LEFT JOIN customer c ON c.customer_id = d.customer_id
                JOIN orderdetails od ON od.design_id = d.design_id
                JOIN orders o ON o.order_id = od.order_id AND o.order_status <> 'pending_payment'
                WHERE d.status = ?
                ORDER BY d.created_at ASC";
        return $this->fetchAll($sql, [$status]);
    }

    public function countPendingPaid()
    {
        $sql = "SELECT COUNT(DISTINCT d.design_id) AS n FROM custom_designs d
                JOIN orderdetails od ON od.design_id = d.design_id
                JOIN orders o ON o.order_id = od.order_id AND o.order_status <> 'pending_payment'
                WHERE d.status = 'pending'";
        $row = $this->fetchOne($sql);
        return (int) $row['n'];
    }

    public function setStatus($designId, $status, $note = null)
    {
        return $this->execute("UPDATE custom_designs SET status = ?, review_note = ? WHERE design_id = ?", [$status, $note, $designId]);
    }
}
