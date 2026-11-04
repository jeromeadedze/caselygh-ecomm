<?php
$page_title = 'Casely Community — real cases, real people';
$page_description = 'Photos of Casely cases from our community. Shop the look and share yours for a discount code.';
$active_nav = 'community';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/CommunityController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$posts = (new CommunityController())->getApprovedPosts(48);
$products = is_logged_in() ? (new ProductController())->getProducts() : [];
?>

<div class="container page">
    <span class="script script-lg">#CaselyCommunity</span>
    <h1 class="display-lg">Real cases, real people</h1>
    <p class="muted lead">Share a photo of your Casely case. Approved posts earn a discount code for your next case.</p>

    <?php if ($posts): ?>
        <div class="masonry">
            <?php foreach ($posts as $i => $post): ?>
                <figure class="post-card">
                    <img src="<?php echo $ROOT_DIR; ?>/uploads/public/community/<?php echo e($post['post_image']); ?>"
                         alt="Customer photo<?php echo $post['product_title'] ? ' of the ' . e($post['product_title']) . ' case' : ''; ?>"
                         loading="lazy" class="<?php echo $i % 3 === 1 ? 'square' : 'tall'; ?>">
                    <figcaption>
                        <span class="strong small post-handle"><?php echo e($post['handle'] ?: 'Casely customer'); ?></span>
                        <?php if ($post['product_slug']): ?>
                            <a href="<?php echo $ROOT_DIR; ?>/views/single_product.php?slug=<?php echo urlencode($post['product_slug']); ?>" class="tag tag-pink">Shop the look</a>
                        <?php endif; ?>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="muted">No photos yet. Share yours below and be the first on the wall.</p>
    <?php endif; ?>

    <section class="section form-card post-form" id="share">
        <h2 class="section-title">Share your case</h2>
        <?php if (is_logged_in()): ?>
            <form action="<?php echo $ROOT_DIR; ?>/actions/community_post_action.php" method="POST" enctype="multipart/form-data" class="stack" data-pending="Sending…">
                <?php echo csrf_field(); ?>
                <label class="field">
                    <span>Photo of your Casely case</span>
                    <input type="file" name="post_image" accept="image/jpeg,image/png,image/webp" class="input" required>
                </label>
                <div class="two-col">
                    <label class="field">
                        <span>Which case is it? (optional)</span>
                        <select name="product_id" class="input">
                            <option value="">A custom case / not listed</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?php echo (int) $p['product_id']; ?>"><?php echo e($p['product_title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Instagram handle (optional)</span>
                        <input type="text" name="handle" class="input" maxlength="40" placeholder="@yourname">
                    </label>
                </div>
                <label class="field">
                    <span>Caption (optional)</span>
                    <input type="text" name="caption" class="input" maxlength="150">
                </label>
                <label class="consent">
                    <input type="checkbox" name="consent" value="1" required>
                    <span>I took this photo, everyone in it agrees to it being shared, and Casely may show it on the website.</span>
                </label>
                <button type="submit" class="btn btn-primary">Submit for approval</button>
            </form>
        <?php else: ?>
            <p class="muted"><a href="<?php echo $ROOT_DIR; ?>/views/login.php" class="text-link">Log in</a> to share a photo of your case.</p>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
