<?php
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_admin();

$page_title = 'Reviews';
$admin_page = 'reviews';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../controllers/ReviewController.php';

$status = in_array($_GET['status'] ?? '', ['pending', 'approved', 'rejected'], true) ? $_GET['status'] : 'pending';
$reviews = (new ReviewController())->getByStatus($status);
?>

<div class="container page">
    <h1 class="display-md">Reviews</h1>
    <?php include __DIR__ . '/admin_nav.php'; ?>
    <p class="muted">Approving a review publishes it on the Community page with the customer's first name and last initial.</p>

    <nav class="chip-row" aria-label="Filter reviews">
        <?php foreach (['pending' => 'Waiting', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label): ?>
            <a href="reviews.php?status=<?php echo $key; ?>" class="chip <?php echo $status === $key ? 'is-on' : ''; ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if (!$reviews): ?>
        <p class="muted section"><?php echo [
            'pending' => 'No reviews waiting. Customers leave them from the Community page.',
            'approved' => 'No reviews published yet.',
            'rejected' => 'No rejected reviews.',
        ][$status]; ?></p>
    <?php else: ?>
        <div class="customer-reviews section">
            <?php foreach ($reviews as $r): ?>
                <article class="customer-review">
                    <p class="review-stars" role="img" aria-label="Rated <?php echo (int) $r['rating']; ?> out of 5"><?php echo str_repeat('★', (int) $r['rating']) . str_repeat('☆', 5 - (int) $r['rating']); ?></p>
                    <p><?php echo nl2br(e($r['review_text'])); ?></p>
                    <p class="muted small"><?php echo e($r['customer_name']); ?> · <?php echo e($r['invoice_no']); ?> · <?php echo date('j M Y', strtotime($r['created_at'])); ?></p>
                    <form action="<?php echo $ROOT_DIR; ?>/actions/moderate_review_action.php" method="POST" data-keep-place class="inline-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="review_id" value="<?php echo (int) $r['review_id']; ?>">
                        <?php if ($status !== 'approved'): ?>
                            <button type="submit" name="decision" value="approved" class="btn btn-dark btn-sm">Approve</button>
                        <?php endif; ?>
                        <?php if ($status !== 'rejected'): ?>
                            <button type="submit" name="decision" value="rejected" class="btn btn-outline btn-sm"><?php echo $status === 'approved' ? 'Remove' : 'Reject'; ?></button>
                        <?php endif; ?>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
