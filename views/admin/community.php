<?php
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_admin();

$page_title = 'Community posts';
$admin_page = 'community';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../controllers/CommunityController.php';

$status = in_array($_GET['status'] ?? '', ['pending', 'approved', 'rejected'], true) ? $_GET['status'] : 'pending';
$posts = (new CommunityController())->getPostsByStatus($status);
?>

<div class="container page">
    <h1 class="display-md">Community posts</h1>
    <?php include __DIR__ . '/admin_nav.php'; ?>
    <p class="muted">Approving a post publishes it on the Community wall and gives the customer a reward code.</p>

    <nav class="chip-row" aria-label="Filter posts">
        <?php foreach (['pending' => 'Waiting', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label): ?>
            <a href="community.php?status=<?php echo $key; ?>" class="chip <?php echo $status === $key ? 'is-on' : ''; ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if (!$posts): ?>
        <p class="muted section"><?php echo [
            'pending' => 'No posts waiting. Customers share photos from the Community page.',
            'approved' => 'No posts on the wall yet.',
            'rejected' => 'No rejected posts.',
        ][$status] ?? 'No posts here.'; ?></p>
    <?php else: ?>
        <div class="review-grid section">
            <?php foreach ($posts as $p): ?>
                <article class="review-card">
                    <img class="photo" src="<?php echo $ROOT_DIR; ?>/uploads/public/community/<?php echo e($p['post_image']); ?>" alt="Post by <?php echo e($p['customer_name']); ?>">
                    <div>
                        <p class="strong"><?php echo e($p['customer_name']); ?> <?php echo $p['handle'] ? '· ' . e($p['handle']) : ''; ?></p>
                        <p class="muted small"><?php echo e($p['product_title'] ?? 'Custom / not listed'); ?></p>
                        <?php if ($p['caption']): ?><p class="small">“<?php echo e($p['caption']); ?>”</p><?php endif; ?>
                        <?php if ($p['reward_code']): ?><p class="small">Reward: <code class="code-chip"><?php echo e($p['reward_code']); ?></code></p><?php endif; ?>
                    </div>
                    <?php if ($status !== 'approved'): ?>
                        <form action="<?php echo $ROOT_DIR; ?>/actions/moderate_post_action.php" method="POST" class="inline-form">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="post_id" value="<?php echo (int) $p['post_id']; ?>">
                            <button type="submit" name="decision" value="approved" class="btn btn-dark btn-sm">Approve</button>
                            <?php if ($status === 'pending'): ?>
                                <button type="submit" name="decision" value="rejected" class="btn btn-outline btn-sm">Reject</button>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                        <form action="<?php echo $ROOT_DIR; ?>/actions/moderate_post_action.php" method="POST">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="post_id" value="<?php echo (int) $p['post_id']; ?>">
                            <button type="submit" name="decision" value="rejected" class="btn btn-outline btn-sm">Remove from wall</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
