<?php
require_once __DIR__ . '/../core/core.php';
require_login();
$page_title = 'My account';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/OrderController.php';
require_once __DIR__ . '/../controllers/CommunityController.php';
require_once __DIR__ . '/../controllers/DraftController.php';

$orderCtrl = new OrderController();
$orders = $orderCtrl->getOrdersByCustomer(get_user_id());
$posts = (new CommunityController())->getPostsByCustomer(get_user_id());
$rewardCodes = array_filter(array_column($posts, 'reward_code'));
$drafts = (new DraftController())->getDrafts(get_user_id());
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
            <strong>Your discount codes:</strong>
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
            <div class="table-wrap" tabindex="0" role="region" aria-label="My orders table">
                <table class="table">
                    <thead><tr><th scope="col">Order</th><th scope="col">Date</th><th scope="col">Items</th><th scope="col">Total</th><th scope="col">Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><a href="<?php echo $ROOT_DIR; ?>/views/order_confirmation.php?order=<?php echo (int) $o['order_id']; ?>" class="text-link"><?php echo e($o['invoice_no']); ?></a></td>
                            <td><?php echo date('j M Y', strtotime($o['order_date'])); ?></td>
                            <td><?php echo (int) $o['item_count']; ?></td>
                            <td><?php echo ghs($o['total']); ?></td>
                            <td>
                                <span class="status status-<?php echo e($o['order_status']); ?>"><?php echo order_status_label($o['order_status']); ?></span>
                                <a href="<?php echo e($orderCtrl->trackingUrl($o, $ROOT_DIR)); ?>" class="text-link small">Track<span class="visually-hidden"> order <?php echo e($o['invoice_no']); ?></span></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="section" id="drafts">
        <div class="section-head">
            <h2 class="section-title">Drafts</h2>
            <a href="<?php echo $ROOT_DIR; ?>/views/design.php" class="text-link">Start a new design &rarr;</a>
        </div>
        <?php if (!$drafts): ?>
            <p class="muted">No drafts yet. In the <a href="<?php echo $ROOT_DIR; ?>/views/design.php" class="text-link">custom case studio</a>, press <strong>Save draft</strong> to keep a design and finish it later.</p>
        <?php else: ?>
            <ul class="draft-grid">
                <?php foreach ($drafts as $d): ?>
                    <li class="draft-card">
                        <a href="<?php echo $ROOT_DIR; ?>/views/design.php?draft=<?php echo (int) $d['draft_id']; ?>" class="draft-thumb">
                            <?php if ($d['preview_image']): ?>
                                <img src="<?php echo $ROOT_DIR; ?>/actions/draft_file_action.php?f=<?php echo e($d['preview_image']); ?>" alt="" loading="lazy">
                            <?php endif; ?>
                            <span class="visually-hidden">Continue editing <?php echo e($d['draft_name']); ?></span>
                        </a>
                        <div class="draft-info">
                            <p class="strong draft-name"><?php echo e($d['draft_name']); ?></p>
                            <p class="muted small">Saved <?php echo date('j M Y, H:i', strtotime($d['updated_at'])); ?></p>
                        </div>
                        <div class="draft-actions">
                            <a href="<?php echo $ROOT_DIR; ?>/views/design.php?draft=<?php echo (int) $d['draft_id']; ?>" class="btn btn-dark btn-xs">Continue</a>
                            <form action="<?php echo $ROOT_DIR; ?>/actions/delete_draft_action.php" method="POST" data-keep-place
                                  data-confirm-title="Delete the draft “<?php echo e($d['draft_name']); ?>”?" data-confirm-body="This design and its photos will be deleted. This can't be undone." data-confirm-yes="Delete draft" data-confirm-no="Keep draft">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="draft_id" value="<?php echo (int) $d['draft_id']; ?>">
                                <button type="submit" class="btn btn-outline btn-xs">Delete<span class="visually-hidden"> <?php echo e($d['draft_name']); ?></span></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="section">
        <h2 class="section-title">My community posts</h2>
        <?php if (!$posts): ?>
            <p class="muted">You haven't shared a photo yet. <a href="<?php echo $ROOT_DIR; ?>/views/community.php#share" class="text-link">Share your case</a> and earn a discount code.</p>
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
