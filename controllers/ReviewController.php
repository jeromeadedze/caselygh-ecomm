<?php

require_once __DIR__ . "/../classes/ReviewClass.php";

/**
 * ReviewController
 * Lets customers who paid for an order leave a review, and the team moderate them.
 */
class ReviewController
{
    const MIN_LENGTH = 10;
    const MAX_LENGTH = 500;

    private $model;

    public function __construct()
    {
        $this->model = new ReviewClass();
    }

    public function canReview($customerId)
    {
        return $this->model->getReviewableOrderId($customerId) !== null;
    }

    public function submit($customerId, $rating, $text)
    {
        $orderId = $this->model->getReviewableOrderId($customerId);
        if (!$orderId) {
            return ['success' => false, 'error' => 'You can review once for each paid order. Place an order to leave a review.'];
        }
        $rating = (int) $rating;
        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'error' => 'Choose a rating from 1 to 5 stars.'];
        }
        $text = trim((string) $text);
        if (mb_strlen($text) < self::MIN_LENGTH || mb_strlen($text) > self::MAX_LENGTH) {
            return ['success' => false, 'error' => 'Write between ' . self::MIN_LENGTH . ' and ' . self::MAX_LENGTH . ' characters.'];
        }
        return $this->model->addReview($customerId, $orderId, $rating, $text)
            ? ['success' => true]
            : ['success' => false, 'error' => 'Unable to save your review. Try again.'];
    }

    // Reviews show a first name and last initial only.
    public static function displayName($fullName)
    {
        $parts = preg_split('/\s+/', trim((string) $fullName));
        // "III" or "Jr" is not a surname.
        while (count($parts) > 1 && preg_match('/^(jr|sr|ii|iii|iv|phd)\.?$/i', end($parts))) {
            array_pop($parts);
        }
        $first = $parts[0] ?? 'Casely customer';
        $last = count($parts) > 1 ? ' ' . mb_strtoupper(mb_substr(end($parts), 0, 1)) . '.' : '';
        return $first . $last;
    }

    public function getApproved($limit = 24)
    {
        return $this->model->getApproved($limit);
    }

    public function getSummary()
    {
        return $this->model->getSummary();
    }

    public function getByStatus($status)
    {
        return $this->model->getByStatus($status);
    }

    public function countPending()
    {
        return $this->model->countPending();
    }

    public function moderate($reviewId, $decision)
    {
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            return false;
        }
        return $this->model->setStatus((int) $reviewId, $decision);
    }
}
