<?php
require_once __DIR__ . '/../core/core.php';
require_login();
$page_title = 'My account';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/OrderController.php';
require_once __DIR__ . '/../controllers/CommunityController.php';

$orders = (new OrderController())->getOrdersByCustomer(get_user_id());
$posts = (new CommunityController())->getPostsByCustomer(get_user_id());
$rewardCodes = array_filter(array_column($posts, 'reward_code'));
?>

<div class="container container-narrow page">
    <div class="section-head">
        <div>
            <p class="script script-lg">Hi, <?php echo e(strtok($_SESSION['customer_name'], ' ')); ?></p>
            <h1 class="display-md">My account</h1>
        </div>
        <a href="<?php echo $ROOT_DIR; ?>/logout.php" class="btn btn-outline btn-sm">Log out</a>
    </div>

    <?php if ($rewardCodes): ?>
        <div class="notice notice-pink">
            <strong>Your Community rewards:</strong>
            <?php foreach ($rewardCodes as $code): ?>
                <code class="code-chip"><?php echo e($code); ?></code>
            <?php endforeach; ?>
            <span class="small">Mention your code to the Casely team on your next order.</span>
        </div>
    <?php endif; ?>

    <section class="section">
        <h2 class="section-title">My orders</h2>
        <?php if (!$orders): ?>
            <div class="soft-panel empty-state">
                <p class="muted">No orders yet.</p>
                <a href="<?php echo $ROOT_DIR; ?>/views/shop.php" class="btn btn-primary">Find a case</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th scope="col">Order</th><th scope="col">Date</th><th scope="col">Items</th><th scope="col">Total</th><th scope="col">Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><a href="<?php echo $ROOT_DIR; ?>/views/order_confirmation.php?order=<?php echo (int) $o['order_id']; ?>" class="text-link"><?php echo e($o['invoice_no']); ?></a></td>
                            <td><?php echo date('j M Y', strtotime($o['order_date'])); ?></td>
                            <td><?php echo (int) $o['item_count']; ?></td>
                            <td><?php echo ghs($o['total']); ?></td>
                            <td><span class="status status-<?php echo e($o['order_status']); ?>"><?php echo order_status_label($o['order_status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="section">
        <h2 class="section-title">My community posts</h2>
        <?php if (!$posts): ?>
            <p class="muted">You haven't shared a photo yet. <a href="<?php echo $ROOT_DIR; ?>/views/community.php#share" class="text-link">Share your case</a> and earn a reward.</p>
        <?php else: ?>
            <div class="post-strip">
                <?php foreach ($posts as $post): ?>
                    <figure>
                        <img src="<?php echo $ROOT_DIR; ?>/uploads/public/community/<?php echo e($post['post_image']); ?>" alt="Your community post" loading="lazy">
                        <figcaption><span class="status status-<?php echo e($post['status']); ?>"><?php echo ucfirst($post['status']); ?></span></figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
