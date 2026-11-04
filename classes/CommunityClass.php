<?php

require_once __DIR__ . "/../core/db_class.php";

/**
 * CommunityClass (Model)
 * Customer photos for the Casely Community wall. Posts only appear
 * after the Casely team approves them.
 */
class CommunityClass extends Database
{
    public function addPost($customerId, $productId, $image, $caption, $handle)
    {
        $sql = "INSERT INTO community_posts (customer_id, product_id, post_image, caption, handle) VALUES (?, ?, ?, ?, ?)";
        return $this->execute($sql, [$customerId, $productId ?: null, $image, $caption, $handle]);
    }

    // Approved posts, newest first. Pass a product id to get that product's photos.
    public function getApprovedPosts($limit = 24, $productId = null)
    {
        $sql = "SELECT cp.*, p.product_title, p.product_slug
                FROM community_posts cp
                LEFT JOIN products p ON p.product_id = cp.product_id
                WHERE cp.status = 'approved'";
        $params = [];
        if ($productId) {
            $sql .= " AND cp.product_id = ?";
            $params[] = $productId;
        }
        $sql .= " ORDER BY cp.created_at DESC, cp.post_id DESC LIMIT " . (int) $limit;
        return $this->fetchAll($sql, $params);
    }

    public function getPostsByStatus($status)
    {
        $sql = "SELECT cp.*, p.product_title, c.customer_name, c.customer_email
                FROM community_posts cp
                JOIN customer c ON c.customer_id = cp.customer_id
                LEFT JOIN products p ON p.product_id = cp.product_id
                WHERE cp.status = ? ORDER BY cp.created_at ASC";
        return $this->fetchAll($sql, [$status]);
    }

    public function getPostsByCustomer($customerId)
    {
        $sql = "SELECT cp.*, p.product_title FROM community_posts cp
                LEFT JOIN products p ON p.product_id = cp.product_id
                WHERE cp.customer_id = ? ORDER BY cp.created_at DESC";
        return $this->fetchAll($sql, [$customerId]);
    }

    public function getPost($postId)
    {
        return $this->fetchOne("SELECT * FROM community_posts WHERE post_id = ?", [$postId]);
    }

    public function setStatus($postId, $status, $rewardCode = null)
    {
        return $this->execute("UPDATE community_posts SET status = ?, reward_code = COALESCE(?, reward_code) WHERE post_id = ?", [$status, $rewardCode, $postId]);
    }

    public function countPending()
    {
        $row = $this->fetchOne("SELECT COUNT(*) AS n FROM community_posts WHERE status = 'pending'");
        return (int) $row['n'];
    }
}
