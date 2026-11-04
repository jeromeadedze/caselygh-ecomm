<?php

require_once __DIR__ . "/../classes/CommunityClass.php";
require_once __DIR__ . "/../functions/upload_functions.php";

/**
 * CommunityController
 * Customer photo posts and their moderation.
 */
class CommunityController
{
    private $communityModel;

    public function __construct()
    {
        $this->communityModel = new CommunityClass();
    }

    public function submitPost($customerId, $productId, $file, $caption, $handle)
    {
        $upload = save_uploaded_image($file, PUBLIC_UPLOAD_DIR . '/community');
        if (!$upload['success']) {
            return ['success' => false, 'error' => $upload['error']];
        }
        $handle = trim((string) $handle);
        if ($handle !== '' && $handle[0] !== '@') {
            $handle = '@' . $handle;
        }
        $ok = $this->communityModel->addPost(
            $customerId,
            (int) $productId,
            $upload['filename'],
            mb_substr(trim((string) $caption), 0, 150),
            mb_substr($handle, 0, 40)
        );
        return $ok ? ['success' => true] : ['success' => false, 'error' => 'Your photo could not be posted.'];
    }

    public function getApprovedPosts($limit = 24, $productId = null)
    {
        return $this->communityModel->getApprovedPosts($limit, $productId);
    }

    public function getPostsByStatus($status)
    {
        return $this->communityModel->getPostsByStatus($status);
    }

    public function getPostsByCustomer($customerId)
    {
        return $this->communityModel->getPostsByCustomer($customerId);
    }

    public function countPending()
    {
        return $this->communityModel->countPending();
    }

    /**
     * Approve or reject a post. Approving creates the customer's reward
     * code (shown in their account) for their next order.
     */
    public function moderate($postId, $decision)
    {
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            return false;
        }
        $code = $decision === 'approved' ? 'COMMUNITY-' . strtoupper(bin2hex(random_bytes(3))) : null;
        return $this->communityModel->setStatus($postId, $decision, $code);
    }
}
