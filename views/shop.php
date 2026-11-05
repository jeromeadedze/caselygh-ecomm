<?php
$page_title = 'Shop phone cases';
$page_description = 'Shop Casely cases by phone brand, model and collection.';
$active_nav = 'shop';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$productCtrl = new ProductController();
$collections = $productCtrl->getCollections();
$modelsByBrand = $productCtrl->getModelsByBrand();

// Read the filters from the URL (?collection=&model=&q=&sort=)
$filters = [
    'collection' => trim($_GET['collection'] ?? ''),
    'model_id' => (int) ($_GET['model'] ?? 0),
    'q' => trim($_GET['q'] ?? ''),
    'sort' => in_array($_GET['sort'] ?? '', ['newest', 'low', 'high'], true) ? $_GET['sort'] : 'newest',
];
$perPage = 24;
$totalProducts = $productCtrl->countProducts($filters);
$pages = max(1, (int) ceil($totalProducts / $perPage));
$page = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
$products = $productCtrl->getProducts($filters + ['limit' => $perPage, 'offset' => ($page - 1) * $perPage]);
$chosenModel = $filters['model_id'] ? $productCtrl->getModelById($filters['model_id']) : null;

// Build a shop URL that keeps the other filters but changes one.
function shop_url($ROOT_DIR, array $filters, array $change)
{
    $params = array_merge([
        'collection' => $filters['collection'],
        'model' => $filters['model_id'] ?: '',
        'q' => $filters['q'],
        'sort' => $filters['sort'] === 'newest' ? '' : $filters['sort'],
    ], $change);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return $ROOT_DIR . '/views/shop.php' . ($params ? '?' . http_build_query($params) : '');
}
?>

<div class="container page">
    <h1 class="display-lg">Shop <span class="script">all cases</span></h1>
    <?php if ($chosenModel): ?>
        <p class="muted">Showing cases made for your <strong><?php echo e($chosenModel['model_name']); ?></strong> · <a href="<?php echo e(shop_url($ROOT_DIR, $filters, ['model' => ''])); ?>" class="text-link">show all phones</a></p>
    <?php endif; ?>

    <nav class="chip-row" aria-label="Collections">
        <a href="<?php echo e(shop_url($ROOT_DIR, $filters, ['collection' => ''])); ?>" class="chip <?php echo $filters['collection'] === '' ? 'is-on' : ''; ?>">All</a>
        <?php foreach ($collections as $c): ?>
            <a href="<?php echo e(shop_url($ROOT_DIR, $filters, ['collection' => $c['collection_slug']])); ?>" class="chip <?php echo $filters['collection'] === $c['collection_slug'] ? 'is-on' : ''; ?>"><?php echo e($c['collection_name']); ?></a>
        <?php endforeach; ?>
    </nav>

    <form method="GET" class="shop-toolbar" role="search">
        <?php if ($filters['collection']): ?>
            <input type="hidden" name="collection" value="<?php echo e($filters['collection']); ?>">
        <?php endif; ?>
        <label class="visually-hidden" for="shop-q">Search cases</label>
        <input type="search" id="shop-q" name="q" value="<?php echo e($filters['q']); ?>" placeholder="Search cases…" class="input input-pill grow">
        <label class="visually-hidden" for="shop-model">Phone model</label>
        <select name="model" id="shop-model" class="input input-pill" data-autosubmit>
            <option value="">All phones</option>
            <?php foreach ($modelsByBrand as $brand => $models): ?>
                <optgroup label="<?php echo e($brand); ?>">
                    <?php foreach ($models as $m): ?>
                        <option value="<?php echo (int) $m['model_id']; ?>" <?php echo $filters['model_id'] === (int) $m['model_id'] ? 'selected' : ''; ?>><?php echo e($m['model_name']); ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
        <label class="visually-hidden" for="shop-sort">Sort by</label>
        <select name="sort" id="shop-sort" class="input input-pill" data-autosubmit>
            <option value="newest" <?php echo $filters['sort'] === 'newest' ? 'selected' : ''; ?>>Newest</option>
            <option value="low" <?php echo $filters['sort'] === 'low' ? 'selected' : ''; ?>>Price: low to high</option>
            <option value="high" <?php echo $filters['sort'] === 'high' ? 'selected' : ''; ?>>Price: high to low</option>
        </select>
        <button type="submit" class="btn btn-dark btn-sm">Search</button>
    </form>

    <?php if ($products): ?>
        <div class="product-grid cols-3">
            <?php foreach ($products as $product): ?>
                <?php include __DIR__ . '/layout/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php
        $pageUrl = fn($n) => shop_url($ROOT_DIR, $filters, ['page' => $n > 1 ? $n : '']);
        include __DIR__ . '/layout/pagination.php';
        ?>
    <?php else: ?>
        <div class="empty-state">
            <p class="script script-lg">Nothing here yet</p>
            <p class="muted">No cases match those filters. Try another phone or collection, or design your own.</p>
            <a href="<?php echo $ROOT_DIR; ?>/views/design.php" class="btn btn-primary">Design a custom case</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
