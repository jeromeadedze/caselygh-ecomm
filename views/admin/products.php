<?php
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_admin();

$page_title = 'Products';
$admin_page = 'products';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

$productCtrl = new ProductController();
$collections = $productCtrl->getCollections();
$modelsByBrand = $productCtrl->getModelsByBrand();
$brands = $productCtrl->getBrands();

// ?edit=ID opens a product for editing; otherwise the form adds a new one.
$editing = isset($_GET['edit']) ? $productCtrl->getProductById((int) $_GET['edit']) : null;
$variants = $editing ? $productCtrl->getVariantsForProduct($editing['product_id']) : [];
$chosenModels = array_unique(array_map('intval', array_column($variants, 'model_id')));
$products = $productCtrl->getAllProductsAdmin();
$latestCount = $productCtrl->countFeatured();
$latestMax = ProductController::LATEST_DESIGNS;
?>

<div class="container page">
    <h1 class="display-md">Products</h1>
    <?php include __DIR__ . '/admin_nav.php'; ?>

    <div class="cart-layout" style="grid-template-columns: minmax(0, 1fr)">
        <section class="form-card" id="case-form">
            <h2 class="section-title-sm"><?php echo $editing ? 'Edit ' . e($editing['product_title']) : 'Add a case'; ?></h2>
            <form action="<?php echo $ROOT_DIR; ?>/actions/save_product_action.php" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <?php if ($editing): ?>
                    <input type="hidden" name="product_id" value="<?php echo (int) $editing['product_id']; ?>">
                <?php endif; ?>
                <div class="two-col">
                    <label class="field"><span>Name</span>
                        <input type="text" name="title" class="input" required maxlength="150" value="<?php echo e($editing['product_title'] ?? ''); ?>"></label>
                    <label class="field"><span>Collection</span>
                        <select name="collection_id" class="input" required>
                            <?php foreach ($collections as $c): ?>
                                <option value="<?php echo (int) $c['collection_id']; ?>" <?php echo ($editing['collection_id'] ?? 0) == $c['collection_id'] ? 'selected' : ''; ?>><?php echo e($c['collection_name']); ?></option>
                            <?php endforeach; ?>
                        </select></label>
                    <label class="field"><span>Standard price (GH₵) — Tough adds <?php echo ghs(TOUGH_EXTRA); ?></span>
                        <input type="number" name="price" class="input" required min="1" step="0.01" value="<?php echo e($editing['product_price'] ?? ''); ?>"></label>
                    <label class="field"><span>Starting stock for newly ticked phones</span>
                        <input type="number" name="stock" class="input" min="0" value="10"></label>
                </div>
                <label class="field"><span>Description</span>
                    <textarea name="desc" class="input" rows="3"><?php echo e($editing['product_desc'] ?? ''); ?></textarea></label>
                <label class="field"><span>Search keywords</span>
                    <input type="text" name="keywords" class="input" maxlength="255" value="<?php echo e($editing['product_keywords'] ?? ''); ?>" placeholder="e.g. pink floral name"></label>
                <label class="field"><span>Photo <?php echo $editing ? '(leave empty to keep the current one)' : ''; ?></span>
                    <input type="file" name="image" class="input" accept="image/jpeg,image/png,image/webp" <?php echo $editing ? '' : 'required'; ?>></label>

                <fieldset class="form-block">
                    <legend>Made for these phones</legend>
                    <?php foreach ($modelsByBrand as $brand => $models): ?>
                        <p class="small strong" style="margin:.5rem 0 .25rem"><?php echo e($brand); ?></p>
                        <div class="check-grid">
                            <?php foreach ($models as $m): ?>
                                <label><input type="checkbox" name="model_ids[]" value="<?php echo (int) $m['model_id']; ?>" <?php echo in_array((int) $m['model_id'], $chosenModels, true) ? 'checked' : ''; ?>> <?php echo e($m['model_name']); ?></label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </fieldset>

                <div class="button-row" style="margin-top:0">
                    <button type="submit" class="btn btn-primary"><?php echo $editing ? 'Save changes' : 'Add case'; ?></button>
                    <?php if ($editing): ?><a href="products.php#case-<?php echo (int) $editing['product_id']; ?>" class="btn btn-outline">Cancel</a><?php endif; ?>
                </div>
            </form>
        </section>

        <section class="form-card">
            <h2 class="section-title-sm">Phone models</h2>
            <p class="muted small" style="margin-bottom: .75rem;">New phone launched? Add it here, then tick it on each case that fits.</p>
            <form action="<?php echo $ROOT_DIR; ?>/actions/add_model_action.php" method="POST" data-keep-place class="inline-form">
                <?php echo csrf_field(); ?>
                <label class="visually-hidden" for="new-model-brand">Brand</label>
                <select name="brand_id" id="new-model-brand" class="input">
                    <?php foreach ($brands as $b): ?>
                        <option value="<?php echo (int) $b['brand_id']; ?>"><?php echo e($b['brand_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="visually-hidden" for="new-model-name">Model name</label>
                <input type="text" name="model_name" id="new-model-name" class="input" placeholder="e.g. iPhone 18 Pro" required maxlength="60">
                <button type="submit" class="btn btn-outline btn-sm">Add phone model</button>
            </form>
        </section>

        <?php if ($editing && $variants): ?>
            <section class="form-card">
                <h2 class="section-title-sm">Stock per phone</h2>
                <form action="<?php echo $ROOT_DIR; ?>/actions/update_stock_action.php" method="POST" data-keep-place>
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="product_id" value="<?php echo (int) $editing['product_id']; ?>">
                    <div class="table-wrap" tabindex="0" role="region" aria-label="Stock per phone table">
                        <table class="table">
                            <thead><tr><th scope="col">Phone</th><th scope="col">Type</th><th scope="col">Price</th><th scope="col">In stock</th></tr></thead>
                            <tbody>
                            <?php foreach ($variants as $v): ?>
                                <tr>
                                    <td><?php echo e($v['model_name']); ?></td>
                                    <td><?php echo e($v['case_type']); ?></td>
                                    <td><?php echo ghs($v['variant_price']); ?></td>
                                    <td><label class="visually-hidden" for="stock-<?php echo (int) $v['variant_id']; ?>">Stock for <?php echo e($v['model_name'] . ' ' . $v['case_type']); ?></label>
                                        <input type="number" min="0" id="stock-<?php echo (int) $v['variant_id']; ?>" name="stock[<?php echo (int) $v['variant_id']; ?>]" value="<?php echo (int) $v['stock']; ?>" class="input" style="max-width:6rem"></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-dark" style="margin-top:1rem">Save stock</button>
                </form>
            </section>
        <?php endif; ?>

        <section id="all-cases">
            <h2 class="section-title-sm">All cases</h2>
            <p class="muted small latest-note"><strong>Latest designs on the home page: <?php echo $latestCount; ?> of <?php echo $latestMax; ?> chosen.</strong> The newest cases fill any empty places.</p>
            <div class="table-wrap" tabindex="0" role="region" aria-label="All cases table">
                <table class="table">
                    <thead><tr><th scope="col"><span class="visually-hidden">Photo</span></th><th scope="col">Case</th><th scope="col">Price</th><th scope="col">Phones</th><th scope="col">Stock</th><th scope="col">Shown</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
                    <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr id="case-<?php echo (int) $p['product_id']; ?>">
                            <td><?php if ($p['product_image']): ?><img class="admin-thumb" src="<?php echo $ROOT_DIR; ?>/images/products/<?php echo e($p['product_image']); ?>" alt=""><?php endif; ?></td>
                            <td><strong><?php echo e($p['product_title']); ?></strong><br><span class="muted small"><?php echo e($p['collection_name']); ?></span><?php if ($p['featured_at'] && $p['is_active']): ?><br><span class="status status-approved">Latest design</span><?php endif; ?></td>
                            <td><?php echo ghs($p['product_price']); ?></td>
                            <td><?php echo (int) $p['model_count']; ?></td>
                            <td><?php echo (int) $p['total_stock']; ?></td>
                            <td><span class="status <?php echo $p['is_active'] ? 'status-approved' : 'status-hidden'; ?>"><?php echo $p['is_active'] ? 'Shown' : 'Hidden'; ?></span></td>
                            <td>
                                <div class="row-actions">
                                    <?php if ($p['is_active']): ?>
                                        <form action="<?php echo $ROOT_DIR; ?>/actions/toggle_latest_action.php" method="POST" data-inline="#all-cases">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="product_id" value="<?php echo (int) $p['product_id']; ?>">
                                            <input type="hidden" name="featured" value="<?php echo $p['featured_at'] ? 0 : 1; ?>">
                                            <button type="submit" class="btn btn-outline btn-xs"><?php echo $p['featured_at'] ? 'Remove from latest' : 'Add to latest'; ?><span class="visually-hidden"> <?php echo e($p['product_title']); ?></span></button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="products.php?edit=<?php echo (int) $p['product_id']; ?>#case-form" class="btn btn-outline btn-xs">Edit<span class="visually-hidden"> <?php echo e($p['product_title']); ?></span></a>
                                    <form action="<?php echo $ROOT_DIR; ?>/actions/toggle_product_action.php" method="POST" data-inline="#all-cases">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="product_id" value="<?php echo (int) $p['product_id']; ?>">
                                        <input type="hidden" name="active" value="<?php echo $p['is_active'] ? 0 : 1; ?>">
                                        <button type="submit" class="btn btn-outline btn-xs"><?php echo $p['is_active'] ? 'Hide' : 'Show'; ?><span class="visually-hidden"> <?php echo e($p['product_title']); ?> in the shop</span></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
