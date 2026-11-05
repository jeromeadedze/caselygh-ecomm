<?php
$page_title = 'Your cart';
$page_description = 'Review your Casely cases and check out with MoMo or card.';
require_once __DIR__ . '/layout/header.php';

$items = $cartCtrl->getCartItems(get_cart_key());
$subtotal = array_sum(array_column($items, 'line_total'));
$delivery = $items ? DELIVERY_FEE : 0;
$problems = $cartCtrl->findStockProblems($items);
?>

<div class="container container-narrow page">
    <h1 class="display-lg">Your cart</h1>

    <?php if (!$items): ?>
        <div class="empty-state soft-panel">
            <p class="script script-lg">It's empty!</p>
            <a href="<?php echo $ROOT_DIR; ?>/views/shop.php" class="btn btn-primary">Find a case</a>
        </div>
    <?php else: ?>
        <?php if ($problems): ?>
            <p class="notice notice-warning">Not enough stock for: <?php echo e(implode(', ', $problems)); ?>. Lower the quantity to check out.</p>
        <?php endif; ?>

        <div class="cart-layout">
            <ul class="cart-list">
                <?php foreach ($items as $item): ?>
                    <li class="cart-item">
                        <div class="cart-thumb">
                            <?php if ($item['is_custom']): ?>
                                <img src="<?php echo $ROOT_DIR; ?>/actions/design_image_action.php?id=<?php echo (int) $item['design_id']; ?>" alt="Your custom design">
                            <?php elseif ($item['product_image']): ?>
                                <img src="<?php echo $ROOT_DIR; ?>/images/products/<?php echo e($item['product_image']); ?>" alt="<?php echo e($item['item_name']); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="cart-info">
                            <p class="cart-name">
                                <?php if ($item['is_custom']): ?>
                                    Custom case<?php echo $item['design_text'] ? ' · ' . e($item['design_text']) : ''; ?>
                                <?php else: ?>
                                    <a href="<?php echo $ROOT_DIR; ?>/views/single_product.php?slug=<?php echo urlencode($item['product_slug']); ?>"><?php echo e($item['item_name']); ?></a>
                                <?php endif; ?>
                            </p>
                            <p class="muted small"><?php echo e($item['model_name']); ?> · <?php echo e($item['case_type']); ?></p>
                            <?php if ($item['is_custom']): ?>
                                <span class="tag tag-butter">Custom · checked before printing</span>
                            <?php endif; ?>
                            <form action="<?php echo $ROOT_DIR; ?>/actions/update_cart_action.php" method="POST" data-keep-place class="qty-stepper qty-sm">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="cart_id" value="<?php echo (int) $item['cart_id']; ?>">
                                <button type="submit" name="qty" value="<?php echo (int) $item['qty'] - 1; ?>" aria-label="Decrease quantity" <?php echo $item['qty'] <= 1 ? 'disabled' : ''; ?>>&minus;</button>
                                <span aria-label="Quantity"><?php echo (int) $item['qty']; ?></span>
                                <button type="submit" name="qty" value="<?php echo (int) $item['qty'] + 1; ?>" aria-label="Increase quantity" <?php echo $item['qty'] >= 10 ? 'disabled' : ''; ?>>+</button>
                            </form>
                        </div>
                        <div class="cart-side">
                            <p class="strong"><?php echo ghs($item['line_total']); ?></p>
                            <form action="<?php echo $ROOT_DIR; ?>/actions/remove_from_cart_action.php" method="POST" data-keep-place<?php if ($item['is_custom']): ?> data-confirm-title="Remove your custom case?" data-confirm-body="You'll need to design it again unless you saved a draft." data-confirm-yes="Remove case" data-confirm-no="Keep case"<?php endif; ?>>
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="cart_id" value="<?php echo (int) $item['cart_id']; ?>">
                                <button type="submit" class="icon-btn" aria-label="Remove <?php echo e($item['item_name']); ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <aside class="summary-card">
                <div class="summary-row"><span>Subtotal</span><span><?php echo ghs($subtotal); ?></span></div>
                <div class="summary-row"><span>Delivery</span><span><?php echo ghs($delivery); ?></span></div>
                <div class="summary-row summary-total"><span>Total</span><span><?php echo ghs($subtotal + $delivery); ?></span></div>
                <?php if ($problems): ?>
                    <span class="btn btn-primary btn-block is-disabled" aria-disabled="true">Fix quantities to check out</span>
                <?php else: ?>
                    <a href="<?php echo $ROOT_DIR; ?>/views/checkout.php" class="btn btn-primary btn-block">Check out</a>
                <?php endif; ?>
                <p class="summary-note"><?php echo is_logged_in()
                    ? 'Pay with MoMo or card on the next step.'
                    : 'Log in or create an account next. Your cart stays with you, then you pay with MoMo or card.'; ?></p>
                <a href="<?php echo $ROOT_DIR; ?>/views/design.php" class="text-link small">Design another case &rarr;</a>
            </aside>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
