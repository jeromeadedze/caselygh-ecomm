<?php
require_once __DIR__ . '/../core/core.php';
require_login();

$page_title = 'Checkout';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

$items = $cartCtrl->getCartItems(get_cart_key());
if (!$items) {
    $_SESSION['warning'] = "Your cart is empty.";
    redirect($ROOT_DIR . '/views/cart.php');
}
if ($cartCtrl->findStockProblems($items)) {
    redirect($ROOT_DIR . '/views/cart.php');
}

$customer = (new CustomerController())->getCustomerById(get_user_id());
$old = $_SESSION['checkout_old'] ?? [];
unset($_SESSION['checkout_old']);
$subtotal = array_sum(array_column($items, 'line_total'));
?>

<div class="container container-narrow page">
    <h1 class="display-lg">Checkout</h1>

    <div class="cart-layout">
        <form action="<?php echo $ROOT_DIR; ?>/actions/checkout_action.php" method="POST" class="form-card" id="checkout-form">
            <?php echo csrf_field(); ?>
            <h2 class="section-title-sm">Delivery details</h2>
            <label class="field">
                <span>Full name</span>
                <input type="text" name="delivery_name" class="input" required autocomplete="name"
                       value="<?php echo e($old['name'] ?? $customer['customer_name']); ?>">
            </label>
            <label class="field">
                <span>Phone number (for the rider)</span>
                <input type="tel" name="delivery_phone" class="input" required autocomplete="tel" placeholder="024 123 4567"
                       value="<?php echo e($old['phone'] ?? $customer['customer_contact']); ?>">
            </label>
            <label class="field">
                <span>Address or landmark</span>
                <input type="text" name="delivery_address" class="input" required autocomplete="street-address" placeholder="House number, street, nearest landmark"
                       value="<?php echo e($old['address'] ?? ''); ?>">
            </label>
            <label class="field">
                <span>Town or city</span>
                <input type="text" name="delivery_city" class="input" required autocomplete="address-level2"
                       value="<?php echo e($old['city'] ?? $customer['customer_city']); ?>">
            </label>
            <p class="field-note">You'll choose Mobile Money (MTN, Telecel, AT) or card on Paystack's secure page. Casely never sees your card or MoMo PIN.</p>
            <button type="submit" class="btn btn-primary btn-block">Pay <?php echo ghs($subtotal + DELIVERY_FEE); ?> with MoMo or card</button>
        </form>

        <aside class="summary-card">
            <?php foreach ($items as $item): ?>
                <div class="summary-row small">
                    <span><?php echo (int) $item['qty']; ?> × <?php echo $item['is_custom'] ? 'Custom case' : e($item['item_name']); ?><br><span class="muted-on-dark"><?php echo e($item['model_name'] . ' · ' . $item['case_type']); ?></span></span>
                    <span><?php echo ghs($item['line_total']); ?></span>
                </div>
            <?php endforeach; ?>
            <div class="summary-row"><span>Delivery</span><span><?php echo ghs(DELIVERY_FEE); ?></span></div>
            <div class="summary-row summary-total"><span>Total</span><span><?php echo ghs($subtotal + DELIVERY_FEE); ?></span></div>
            <p class="summary-note">Payments secured by Paystack</p>
        </aside>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
