<?php
// Loaded by index.php (ROOT_DIR = '.')
$page_description = 'Design a custom phone case with your name or photo, or shop ready-made Casely cases. MoMo checkout in Ghana.';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$productCtrl = new ProductController();
$collections = $productCtrl->getCollections();
$bestSellers = $productCtrl->getFeaturedProducts(4);
$modelsByBrand = $productCtrl->getModelsByBrand();
?>

<section class="container hero">
    <div class="hero-media">
        <?php include __DIR__ . '/layout/hero_ring.php'; ?>
        <div class="hero-copy">
            <span class="script hero-script">New!</span>
            <h1>Custom Cases</h1>
            <p>Your case. Your way.</p>
            <div class="hero-actions">
                <a href="<?php echo $ROOT_DIR; ?>/views/design.php" class="btn btn-primary">Design yours</a>
                <a href="<?php echo $ROOT_DIR; ?>/views/shop.php" class="btn btn-outline">Shop cases</a>
            </div>
        </div>
    </div>
</section>

<section class="container section">
    <form action="<?php echo $ROOT_DIR; ?>/views/shop.php" method="GET" class="phone-finder">
        <p class="phone-finder-title">Find cases for your phone</p>
        <label class="field">
            <span>Phone model</span>
            <select name="model" class="input" required>
                <option value="">Choose your phone</option>
                <?php foreach ($modelsByBrand as $brand => $models): ?>
                    <optgroup label="<?php echo e($brand); ?>">
                        <?php foreach ($models as $m): ?>
                            <option value="<?php echo (int) $m['model_id']; ?>"><?php echo e($m['model_name']); ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn btn-dark">Show my cases</button>
    </form>
</section>

<section class="container section">
    <h2 class="section-title">Shop by collection</h2>
    <div class="collection-grid">
        <?php foreach ($collections as $c): ?>
            <a href="<?php echo $ROOT_DIR; ?>/views/shop.php?collection=<?php echo urlencode($c['collection_slug']); ?>" class="collection-tile tone-<?php echo e($c['collection_tone']); ?>">
                <span><?php echo e($c['collection_name']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="container section">
    <div class="section-head">
        <h2 class="section-title">Best sellers</h2>
        <a href="<?php echo $ROOT_DIR; ?>/views/shop.php" class="text-link">See all &rarr;</a>
    </div>
    <div class="product-grid cols-4">
        <?php foreach ($bestSellers as $product): ?>
            <?php include __DIR__ . '/layout/product_card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>

<?php
// Instagram strip. To change a card, drop a photo in images/community/ and
// update its line here (file, link to the post, and a short description).
$igPosts = [
    ['ig-mirror-selfie.jpg', 'https://www.instagram.com/casely.gh/p/DfIUBhqjdpo/', 'Customer mirror selfie holding a pink Casely case'],
    ['ig-green-top.jpg',     'https://www.instagram.com/casely.gh/p/Ddml1aGNeFt/', 'Customer in a green top holding up her Casely case'],
    ['ig-crossbody.jpg',     'https://www.instagram.com/casely.gh/p/De6Wcj6jf8d/', 'Red Casely case worn on a crossbody strap'],
    ['ig-red-selfie.jpg',    'https://www.instagram.com/casely.gh/p/Dd9f5CIjRoj/', 'Customer mirror selfie with her Casely case'],
    ['ig-pink-case.jpg',     'https://www.instagram.com/casely.gh/p/DezMYrOjQG3/', 'Pink custom case reading With God all things are possible'],
];
$igUrl = 'https://www.instagram.com/casely.gh/';
?>
<section class="container section ig-community" aria-labelledby="ig-title">
    <div class="ig-head">
        <div>
            <h2 class="section-title ig-title" id="ig-title">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                Casely Community
            </h2>
            <p class="muted">Real customers, real cases. Tag <a href="<?php echo $igUrl; ?>" class="text-link" target="_blank" rel="noopener">@casely.gh</a> to be featured.</p>
        </div>
        <a href="<?php echo $igUrl; ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener">Follow @casely.gh<span class="visually-hidden"> on Instagram (opens in a new tab)</span></a>
    </div>

    <ul class="ig-strip">
        <?php foreach ($igPosts as [$file, $link, $alt]): ?>
            <li>
                <a href="<?php echo $link; ?>" class="ig-card" target="_blank" rel="noopener">
                    <img src="<?php echo $ROOT_DIR; ?>/images/community/<?php echo $file; ?>" alt="<?php echo e($alt); ?>" loading="lazy" width="480" height="640">
                    <span class="ig-card-tag" aria-hidden="true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/></svg>
                        @casely.gh
                    </span>
                    <span class="visually-hidden">View this post on Instagram (opens in a new tab)</span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="ig-reward">
        <p><span class="script ig-reward-script">#CaselyCommunity</span> Post a photo of your Casely case. Once approved, you get a discount code for your next one.</p>
        <a href="<?php echo $ROOT_DIR; ?>/views/community.php" class="btn btn-dark btn-sm">Share yours</a>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
