<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/OrderController.php';

// Anyone with the link can open this page without logging in, so it shows
// progress and items only. Name, phone, address and prices stay on the receipt.
$orderCtrl = new OrderController();
$order = $orderCtrl->getOrderByToken($_GET['t'] ?? '');
if (!$order) {
    http_response_code(404);
}

$page_title = $order ? 'Track order ' . $order['invoice_no'] : 'Order not found';
$page_noindex = true;
require_once __DIR__ . '/layout/header.php';

if ($order) {
    $status = $order['order_status'];
    $items = $orderCtrl->getOrderItems($order['order_id']);
    $history = $orderCtrl->getStatusDates($order['order_id']);
    $hasCustom = (bool) array_filter($items, fn($i) => $i['design_id']);
    $isOwner = is_logged_in() && ((int) $order['customer_id'] === (int) get_user_id() || is_admin());
    $when = fn($date) => date('j M Y, H:i', strtotime($date));

    $messages = [
        'pending_payment' => ['Waiting for payment', 'We start on your order once your payment goes through.'],
        'paid' => ['We have your order', 'Your payment came through. Printing is next.'],
        'printing' => ['Your case is being printed', "We'll send it out as soon as it's ready."],
        'out_for_delivery' => ['Your case is on its way', 'Keep your phone close. The rider may call you.'],
        'delivered' => ['Your case has been delivered', 'Enjoy it. Thanks for shopping with Casely.'],
        'cancelled' => ['This order was cancelled', "If you didn't expect this, message us on WhatsApp at 0509868659."],
    ];
    [$headline, $message] = $messages[$status] ?? [order_status_label($status), ''];

    // Order placed is always done, then each status up to the current one.
    $steps = ['placed' => 'Order placed', 'paid' => 'Paid', 'printing' => 'Printing', 'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered'];
    $keys = array_keys($steps);
    $reached = $status === 'pending_payment' ? 0 : (int) array_search($status, $keys, true);
    $history['placed'] = $order['order_date'];
}
?>

<div class="container container-narrow page">
    <?php if (!$order): ?>
        <h1 class="display-md">We can't find that order</h1>
        <p class="muted">Check that you opened the full link from your receipt, or find the order in your account. You can also message us on WhatsApp at 0509868659.</p>
        <div class="button-row">
            <a href="<?php echo $ROOT_DIR; ?>/views/account.php" class="btn btn-dark">My orders</a>
        </div>
    <?php else: ?>
        <p class="muted">Order <strong><?php echo e($order['invoice_no']); ?></strong></p>
        <h1 class="display-md"><?php echo e($headline); ?></h1>
        <p class="muted"><?php echo e($message); ?></p>

        <?php if ($hasCustom && in_array($status, ['pending_payment', 'paid'], true)): ?>
            <p class="notice">Our team checks every custom design before printing. If anything needs changing, we'll contact you.</p>
        <?php endif; ?>

        <div class="order-box">
            <?php if ($status === 'cancelled'): ?>
                <p class="muted small">Placed <?php echo $when($order['order_date']); ?><?php if (!empty($history['cancelled'])): ?> · Cancelled <?php echo $when($history['cancelled']); ?><?php endif; ?></p>
            <?php else: ?>
                <ol class="track-steps" aria-label="Order progress">
                    <?php foreach ($keys as $i => $key):
                        $done = $i <= $reached;
                        $current = $i === $reached; ?>
                        <li class="track-step<?php echo $done ? ' is-done' : ''; ?><?php echo $current ? ' is-current' : ''; ?>"<?php echo $current ? ' aria-current="step"' : ''; ?>>
                            <span class="track-marker" aria-hidden="true">
                                <?php if ($done): ?>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                <?php endif; ?>
                            </span>
                            <span class="track-text">
                                <span class="track-label"><?php echo e($steps[$key]); ?><span class="visually-hidden"><?php echo $done ? ': done' : ': not yet'; ?></span></span>
                                <?php if ($done && !empty($history[$key])): ?>
                                    <span class="track-time"><?php echo $when($history[$key]); ?></span>
                                <?php endif; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>

        <section class="section">
            <h2 class="section-title-sm">In this order</h2>
            <ul class="track-items">
                <?php foreach ($items as $item): ?>
                    <li><?php echo (int) $item['qty']; ?> × <?php echo e($item['item_name']); ?></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <div class="button-row">
            <?php if ($isOwner): ?>
                <a href="<?php echo $ROOT_DIR; ?>/views/order_confirmation.php?order=<?php echo (int) $order['order_id']; ?>" class="btn btn-dark">View receipt</a>
            <?php endif; ?>
            <a href="<?php echo $ROOT_DIR; ?>/views/shop.php" class="btn btn-outline">Keep shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
