<?php
$page_title = 'Casely Community — real cases, real people';
$page_description = 'Casely customer photos, brand collabs and reviews. Share your case for a discount code.';
$active_nav = 'community';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/CommunityController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_once __DIR__ . '/../controllers/ReviewController.php';
require __DIR__ . '/layout/ig_posts.php';

// Demo rows from the seed data aren't real customers, so they stay off this page.
$posts = array_filter((new CommunityController())->getApprovedPosts(48), fn($p) => $p['caption'] !== 'Demo post');
$customerPhotos = array_filter($igPosts, fn($post) => $post[3]);

$reviewCtrl = new ReviewController();
$reviews = $reviewCtrl->getApproved();
$reviewSummary = $reviewCtrl->getSummary();
$canReview = is_logged_in() && !is_admin() && $reviewCtrl->canReview(get_user_id());

// PLACEHOLDER: replace with the real collab contact before going live.
$collabContact = 'XXXXXXXXXX';

// Real Casely collabs, sold in this shop.
$collabs = [
    ['collab-just-in-case.jpg', 'Just-In-Case', 'ModusArtem × Casely', 'A textured collab case with ModusArtem, in Titanium, Bronze and Scarlet.', $ROOT_DIR . '/views/shop.php?q=modusartem', 'Three black ModusArtem Just-In-Case cases in Titanium, Bronze and Scarlet'],
    ['collab-truth.jpg', 'Truth', 'Casely collab', 'Bold TRUTH lettering across the whole case, in White, Red and Gold.', $ROOT_DIR . '/views/shop.php?q=truth', 'Three Truth cases tucked into a black shoulder bag'],
];
$products = is_logged_in() ? (new ProductController())->getProducts() : [];
?>

<div class="container page">
    <span class="script script-lg">#CaselyCommunity</span>
    <h1 class="display-lg">Real cases, real people</h1>
    <p class="muted lead">See our customers' cases, the brands we've worked with and what buyers say about us.</p>
    <nav class="chip-row" aria-label="On this page">
        <a href="#customers" class="chip">Customers</a>
        <a href="#collabs" class="chip">Collabs</a>
        <a href="#reviews" class="chip">Reviews</a>
        <a href="#share" class="chip">Share your case</a>
    </nav>

    <section class="section" id="customers" data-reveal>
        <div class="ig-head">
            <div>
                <h2 class="section-title">Our customers</h2>
                <p class="muted">From <a href="<?php echo $igUrl; ?>" class="text-link" target="_blank" rel="noopener">@casely.gh<span class="visually-hidden"> on Instagram (opens in a new tab)</span></a>. Tag us to be featured.</p>
            </div>
            <a href="<?php echo $igUrl; ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener">See more on Instagram<span class="visually-hidden"> (opens in a new tab)</span></a>
        </div>
        <ul class="customer-photos">
            <?php foreach ($customerPhotos as [$file, $link, $alt]): ?>
                <li>
                    <a href="<?php echo $link; ?>" class="ig-card" target="_blank" rel="noopener">
                        <img src="<?php echo $ROOT_DIR; ?>/images/community/<?php echo $file; ?>" alt="<?php echo e($alt); ?>" loading="lazy" width="480" height="640">
                        <span class="visually-hidden">View this post on Instagram (opens in a new tab)</span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($posts): ?>
            <h3 class="section-title-sm community-subhead">Shared on Casely</h3>
            <div class="masonry">
                <?php foreach (array_values($posts) as $i => $post): ?>
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
        <?php endif; ?>
    </section>

    <section class="section" id="collabs" data-reveal>
        <h2 class="section-title">Collabs</h2>
        <p class="muted lead">Cases made with brands and creators.</p>
        <ul class="collab-grid">
            <?php foreach ($collabs as [$image, $name, $partner, $text, $link, $alt]): ?>
                <li class="collab-card">
                    <img src="<?php echo $ROOT_DIR; ?>/images/community/<?php echo $image; ?>" alt="<?php echo e($alt); ?>" loading="lazy" width="800" height="800">
                    <div class="collab-body">
                        <p class="eyebrow"><?php echo e($partner); ?></p>
                        <h3 class="section-title-sm"><?php echo e($name); ?></h3>
                        <p class="muted"><?php echo e($text); ?></p>
                        <a href="<?php echo e($link); ?>" class="text-link">Shop <?php echo e($name); ?> &rarr;</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="ig-reward collab-invite">
            <p>Have a brand or an idea for a case? Message us on Instagram, or send us a message at <?php echo e($collabContact); ?>.</p>
            <div class="collab-invite-actions">
                <a href="<?php echo $igUrl; ?>" class="btn btn-dark btn-sm" target="_blank" rel="noopener">Message on Instagram<span class="visually-hidden"> (opens in a new tab)</span></a>
            </div>
        </div>
    </section>

    <section class="section" id="reviews" data-reveal>
        <h2 class="section-title">Reviews</h2>
        <?php if ($reviews): ?>
            <?php $average = round((float) $reviewSummary['average'], 1); ?>
            <p class="review-summary"><strong><?php echo number_format($average, 1); ?></strong> out of 5 from <?php echo (int) $reviewSummary['total']; ?> <?php echo (int) $reviewSummary['total'] === 1 ? 'review' : 'reviews'; ?></p>
            <div class="customer-reviews">
                <?php foreach ($reviews as $r): ?>
                    <article class="customer-review">
                        <p class="review-stars" role="img" aria-label="Rated <?php echo (int) $r['rating']; ?> out of 5"><?php echo str_repeat('★', (int) $r['rating']) . str_repeat('☆', 5 - (int) $r['rating']); ?></p>
                        <p><?php echo nl2br(e($r['review_text'])); ?></p>
                        <p class="muted small"><?php echo e(ReviewController::displayName($r['customer_name'])); ?> · Paid customer · <?php echo date('j M Y', strtotime($r['created_at'])); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="muted">No reviews yet. Customers who've paid for an order can write the first one.</p>
        <?php endif; ?>

        <?php if ($canReview): ?>
            <?php include __DIR__ . '/layout/review_form.php'; ?>
        <?php elseif (!is_logged_in()): ?>
            <p class="muted review-note"><a href="<?php echo $ROOT_DIR; ?>/views/login.php" class="text-link">Log in</a> to review a paid order.</p>
        <?php elseif (!is_admin()): ?>
            <p class="muted review-note">You can review once for each paid order. <a href="<?php echo $ROOT_DIR; ?>/views/shop.php" class="text-link">Find a case</a>.</p>
        <?php endif; ?>
    </section>

    <section class="section form-card post-form" id="share">
        <h2 class="section-title">Share your case</h2>
        <p class="muted">Approved photos earn a discount code for your next case.</p>
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
