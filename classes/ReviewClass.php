<?php

require_once __DIR__ . "/../core/db_class.php";

/**
 * ReviewClass (Model)
 * Customer reviews for the Community page. One review per paid order,
 * shown only after the Casely team approves it.
 */
class ReviewClass extends Database
{
    public function addReview($customerId, $orderId, $rating, $text)
    {
        $sql = "INSERT INTO reviews (customer_id, order_id, rating, review_text) VALUES (?, ?, ?, ?)";
        return $this->execute($sql, [$customerId, $orderId, $rating, $text]);
    }

    // The customer's newest paid order that has no review yet.
    public function getReviewableOrderId($customerId)
    {
        $sql = "SELECT o.order_id FROM orders o
                LEFT JOIN reviews r ON r.order_id = o.order_id
                WHERE o.customer_id = ? AND r.review_id IS NULL
                  AND o.order_status NOT IN ('pending_payment', 'cancelled')
                ORDER BY o.order_date DESC LIMIT 1";
        $row = $this->fetchOne($sql, [$customerId]);
        return $row ? (int) $row['order_id'] : null;
    }

    public function getApproved($limit = 24)
    {
        $sql = "SELECT r.rating, r.review_text, r.created_at, c.customer_name
                FROM reviews r JOIN customer c ON c.customer_id = r.customer_id
                WHERE r.status = 'approved'
                ORDER BY r.created_at DESC, r.review_id DESC LIMIT " . (int) $limit;
        return $this->fetchAll($sql);
    }

    public function getSummary()
    {
        return $this->fetchOne("SELECT COUNT(*) AS total, AVG(rating) AS average FROM reviews WHERE status = 'approved'");
    }

    public function getByStatus($status)
    {
        $sql = "SELECT r.*, c.customer_name, o.invoice_no
                FROM reviews r
                JOIN customer c ON c.customer_id = r.customer_id
                JOIN orders o ON o.order_id = r.order_id
                WHERE r.status = ? ORDER BY r.created_at ASC";
        return $this->fetchAll($sql, [$status]);
    }

    public function countPending()
    {
        $row = $this->fetchOne("SELECT COUNT(*) AS total FROM reviews WHERE status = 'pending'");
        return (int) ($row['total'] ?? 0);
    }

    public function setStatus($reviewId, $status)
    {
        return $this->execute("UPDATE reviews SET status = ? WHERE review_id = ?", [$status, $reviewId]);
    }
}
