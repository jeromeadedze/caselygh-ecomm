<?php
require_once __DIR__ . '/../core/core.php';
require_login();
require_once __DIR__ . '/../controllers/OrderController.php';

$orderCtrl = new OrderController();
$order = $orderCtrl->getOrder((int) ($_GET['order'] ?? 0));
// Customers may only see their own orders.
if (!$order || ((int) $order['customer_id'] !== (int) get_user_id() && !is_admin())) {
    $_SESSION['error'] = "We could not find that order.";
    redirect(root_dir() . '/views/account.php');
}
$items = $orderCtrl->getOrderItems($order['order_id']);
$paid = $order['order_status'] !== 'pending_payment';
$hasCustom = (bool) array_filter($items, fn($i) => $i['design_id']);

$page_title = 'Order ' . $order['invoice_no'];
require_once __DIR__ . '/layout/header.php';
?>

<div class="container container-narrow page">
    <?php if ($paid): ?>
        <p class="script script-lg">Thank you!</p>
        <h1 class="display-md">Your order is in.</h1>
        <p class="muted">Order <strong><?php echo e($order['invoice_no']); ?></strong> is paid. We'll update you on <?php echo e($order['delivery_phone']); ?> by WhatsApp or phone.</p>
        <?php if ($hasCustom): ?>
            <p class="notice">Our team checks every custom design before printing. If anything needs changing, we'll contact you.</p>
        <?php endif; ?>
    <?php else: ?>
        <h1 class="display-md">Payment not completed</h1>
        <p class="muted">Order <strong><?php echo e($order['invoice_no']); ?></strong> is waiting for payment. Your cart has been kept.</p>
        <form action="<?php echo $ROOT_DIR; ?>/actions/retry_payment_action.php" method="POST" data-pending="Taking you to payment…">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="order_id" value="<?php echo (int) $order['order_id']; ?>">
            <button type="submit" class="btn btn-primary">Try paying again</button>
        </form>
    <?php endif; ?>

    <div class="order-box">
        <div class="order-steps" aria-label="Order progress">
            <?php
            $flow = ['paid', 'printing', 'out_for_delivery', 'delivered'];
            $reached = array_search($order['order_status'], $flow, true);
            foreach ($flow as $i => $s): ?>
                <span class="order-step <?php echo ($reached !== false && $i <= $reached) ? 'is-done' : ''; ?>"><?php echo order_status_label($s); ?></span>
            <?php endforeach; ?>
        </div>
        <?php foreach ($items as $item): ?>
            <div class="summary-row light"><span><?php echo (int) $item['qty']; ?> × <?php echo e($item['item_name']); ?></span><span><?php echo ghs($item['unit_price'] * $item['qty']); ?></span></div>
        <?php endforeach; ?>
        <div class="summary-row light"><span>Delivery</span><span><?php echo ghs($order['delivery_fee']); ?></span></div>
        <div class="summary-row light summary-total"><span>Total</span><span><?php echo ghs($order['total']); ?></span></div>
        <p class="muted small">Delivering to <?php echo e($order['delivery_name']); ?>, <?php echo e($order['delivery_address']); ?>, <?php echo e($order['delivery_city']); ?></p>
    </div>

    <div class="button-row">
        <a href="<?php echo e($orderCtrl->trackingUrl($order, $ROOT_DIR)); ?>" class="btn btn-dark">Track this order</a>
        <a href="<?php echo $ROOT_DIR; ?>/views/account.php" class="btn btn-outline">My orders</a>
        <a href="<?php echo $ROOT_DIR; ?>/views/shop.php" class="btn btn-outline">Keep shopping</a>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
