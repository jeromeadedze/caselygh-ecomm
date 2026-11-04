<?php
// One product tile. Expects $product (a row from ProductController) and $ROOT_DIR.
?>
<a href="<?php echo $ROOT_DIR; ?>/views/single_product.php?slug=<?php echo urlencode($product['product_slug']); ?>" class="product-card">
    <div class="product-card-media">
        <?php if (!empty($product['product_image'])): ?>
            <img src="<?php echo $ROOT_DIR; ?>/images/products/<?php echo e($product['product_image']); ?>" alt="<?php echo e($product['product_title']); ?> phone case" loading="lazy" width="768" height="960">
        <?php else: ?>
            <span class="image-placeholder">No photo yet</span>
        <?php endif; ?>
    </div>
    <div class="product-card-body">
        <div class="product-card-text">
            <p class="product-card-title"><?php echo e($product['product_title']); ?></p>
            <p class="product-card-meta"><?php echo e($product['collection_name']); ?></p>
        </div>
        <p class="product-card-price"><?php echo ghs($product['product_price']); ?></p>
    </div>
</a>
