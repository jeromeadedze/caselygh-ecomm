<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../controllers/CommunityController.php';

$productCtrl = new ProductController();
$product = $productCtrl->getProductBySlug($_GET['slug'] ?? '');
if (!$product) {
    http_response_code(404);
    $page_title = 'Case not found';
    require_once __DIR__ . '/layout/header.php';
    echo '<div class="container page empty-state"><h1 class="display-md">We could not find that case.</h1>'
        . '<a href="' . $ROOT_DIR . '/views/shop.php" class="btn btn-primary">Back to the shop</a></div>';
    require_once __DIR__ . '/layout/footer.php';
    exit;
}

$page_title = $product['product_title'] . ' phone case';
$page_description = $product['product_desc'] ?: 'Casely phone case';
$active_nav = 'shop';
require_once __DIR__ . '/layout/header.php';

// Variants -> { brand: { modelId: {name, Standard: {price, stock}, Tough: {...}} } }
$variants = $productCtrl->getVariantsForProduct($product['product_id']);
$byBrand = [];
foreach ($variants as $v) {
    $byBrand[$v['brand_name']][$v['model_id']]['name'] = $v['model_name'];
    $byBrand[$v['brand_name']][$v['model_id']][$v['case_type']] = [
        'price' => (float) $v['variant_price'],
        'stock' => (int) $v['stock'],
    ];
}
$defaultType = $product['collection_slug'] === 'tough' ? 'Tough' : 'Standard';
$startPrice = price_for($product['product_price'], $defaultType);
$posts = (new CommunityController())->getApprovedPosts(3, $product['product_id']);
require_once __DIR__ . '/layout/case_designs.php';
$caseDesign = casely_case_design($product['product_slug']);
?>

<div class="container page">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?php echo $ROOT_DIR; ?>/views/shop.php">Shop</a> /
        <a href="<?php echo $ROOT_DIR; ?>/views/shop.php?collection=<?php echo urlencode($product['collection_slug']); ?>"><?php echo e($product['collection_name']); ?></a> /
        <span aria-current="page"><?php echo e($product['product_title']); ?></span>
    </nav>

    <div class="product-detail">
        <div class="product-media-col">
            <div class="product-detail-media" id="product-media" style="view-transition-name: case-<?php echo (int) $product['product_id']; ?>"
                 <?php if ($caseDesign): ?>data-design="<?php echo e(json_encode($caseDesign)); ?>" data-title="<?php echo e($product['product_title']); ?>"<?php endif; ?>>
                <?php if ($product['product_image']): ?>
                    <img class="pdm-photo" src="<?php echo $ROOT_DIR; ?>/images/products/<?php echo e($product['product_image']); ?>" alt="<?php echo e($product['product_title']); ?> phone case" width="768" height="960">
                <?php else: ?>
                    <span class="image-placeholder">No photo yet</span>
                <?php endif; ?>
                <?php if ($caseDesign): ?>
                    <div class="pdm-mockup" data-mockup role="img" aria-hidden="true"></div>
                <?php endif; ?>
            </div>
            <?php if ($caseDesign && $product['product_image']): ?>
                <div class="view-toggle" role="group" aria-label="Preview">
                    <button type="button" class="view-toggle-btn" data-view="photo" aria-pressed="true">Product photo</button>
                    <button type="button" class="view-toggle-btn" data-view="mockup" aria-pressed="false">On <span data-mockup-model>your phone</span></button>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <p class="eyebrow"><?php echo e($product['collection_name']); ?></p>
            <h1 class="display-md"><?php echo e($product['product_title']); ?></h1>
            <p class="price-lg" data-price><?php echo ghs($startPrice); ?></p>
            <?php if ($product['product_desc']): ?>
                <p class="muted"><?php echo nl2br(e($product['product_desc'])); ?></p>
            <?php endif; ?>

            <?php if (!$byBrand): ?>
                <p class="notice">This case is not available for any phone right now.</p>
            <?php else: ?>
            <form action="<?php echo $ROOT_DIR; ?>/actions/add_to_cart_action.php" method="POST" class="buy-form" id="buy-form" data-pending="Adding…"
                  data-variants="<?php echo e(json_encode($byBrand)); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="product_id" value="<?php echo (int) $product['product_id']; ?>">

                <fieldset class="form-block">
                    <legend>Your phone</legend>
                    <div class="two-col">
                        <label class="field">
                            <span>Brand</span>
                            <select class="input" id="brand-select">
                                <?php foreach (array_keys($byBrand) as $brand): ?>
                                    <option><?php echo e($brand); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field">
                            <span>Model</span>
                            <select class="input" name="model_id" id="model-select" required>
                                <?php foreach (reset($byBrand) as $modelId => $m): ?>
                                    <option value="<?php echo (int) $modelId; ?>"><?php echo e($m['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <?php if (!$caseDesign): ?>
                        <p class="field-note">The photo shows one phone. Your case is made for the phone you choose, with the right camera cutout.</p>
                    <?php endif; ?>
                </fieldset>

                <fieldset class="form-block">
                    <legend>Case type</legend>
                    <div class="option-grid">
                        <?php foreach (['Standard' => 'Slim, light everyday case', 'Tough' => 'Double layer drop protection, +' . ghs(TOUGH_EXTRA)] as $type => $note): ?>
                            <label class="option-card">
                                <input type="radio" name="case_type" value="<?php echo $type; ?>" <?php echo $type === $defaultType ? 'checked' : ''; ?>>
                                <span class="option-name"><?php echo $type; ?></span>
                                <span class="option-note"><?php echo $note; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <p class="stock-note" data-stock aria-live="polite"></p>

                <div class="buy-row">
                    <div class="qty-stepper">
                        <button type="button" data-qty="-1" aria-label="Decrease quantity">&minus;</button>
                        <input type="number" name="qty" value="1" min="1" max="10" aria-label="Quantity" readonly>
                        <button type="button" data-qty="1" aria-label="Increase quantity">+</button>
                    </div>
                    <button type="submit" class="btn btn-primary grow" data-add-btn><span>Add to cart · <span data-total><?php echo ghs($startPrice); ?></span></span></button>
                </div>
            </form>
            <!-- Phones: keeps the next step in reach until the buy form scrolls into view. Shown by js/main.js. -->
            <div class="buy-bar" id="buy-bar" hidden>
                <p class="buy-bar-text"><span class="buy-bar-phone" data-bar-phone></span> <strong data-bar-price></strong></p>
                <button type="submit" form="buy-form" class="btn btn-primary btn-sm" data-bar-add>Add to cart</button>
                <button type="button" class="btn btn-primary btn-sm" data-bar-choose>Choose phone</button>
            </div>
            <?php endif; ?>

            <p class="assurance">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Raised camera lip and screen edges. Pay with MoMo or card.
            </p>
        </div>
    </div>

    <section class="section">
        <div>
            <h2 class="section-title">From the community</h2>
            <?php if ($posts): ?>
                <div class="mini-wall">
                    <?php foreach ($posts as $post): ?>
                        <img src="<?php echo $ROOT_DIR; ?>/uploads/public/community/<?php echo e($post['post_image']); ?>" alt="Customer photo of <?php echo e($product['product_title']); ?>" loading="lazy">
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted">No photos of this case yet. Bought it? Share yours and earn a discount code.</p>
            <?php endif; ?>
            <a href="<?php echo $ROOT_DIR; ?>/views/community.php" class="text-link">See the community &rarr;</a>
        </div>
    </section>
</div>

<?php if ($caseDesign): ?>
<script src="<?php echo $ROOT_DIR; ?>/js/case-mockup.js?v=<?php echo filemtime(__DIR__ . '/../js/case-mockup.js'); ?>"></script>
<?php endif; ?>
<?php require_once __DIR__ . '/layout/footer.php'; ?>
