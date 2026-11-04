<?php
// Admin sub-navigation. Expects $admin_page and $ROOT_DIR.
require_once __DIR__ . '/../../controllers/CustomDesignController.php';
require_once __DIR__ . '/../../controllers/CommunityController.php';
require_once __DIR__ . '/../../controllers/ReviewController.php';

$pendingDesigns = (new CustomDesignController())->countPendingPaid();
$pendingPosts = (new CommunityController())->countPending();
$pendingReviews = (new ReviewController())->countPending();
$adminLinks = [
    'dashboard' => ['Dashboard', 'dashboard.php', 0],
    'orders' => ['Orders', 'orders.php', 0],
    'designs' => ['Custom designs', 'designs.php', $pendingDesigns],
    'products' => ['Products', 'products.php', 0],
    'community' => ['Community', 'community.php', $pendingPosts],
    'reviews' => ['Reviews', 'reviews.php', $pendingReviews],
];
?>
<nav class="admin-nav" aria-label="Admin">
    <?php foreach ($adminLinks as $key => [$label, $file, $count]): ?>
        <a href="<?php echo $ROOT_DIR; ?>/views/admin/<?php echo $file; ?>" class="<?php echo $admin_page === $key ? 'is-active' : ''; ?>" <?php echo $admin_page === $key ? 'aria-current="page"' : ''; ?>>
            <?php echo $label; ?><?php if ($count): ?><span class="badge"><?php echo $count; ?><span class="visually-hidden"> waiting</span></span><?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>
