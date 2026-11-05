<?php
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_admin();

$page_title = 'Orders';
$admin_page = 'orders';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../controllers/OrderController.php';

$orderCtrl = new OrderController();
$status = $_GET['status'] ?? '';
$perPage = 25;
$pages = max(1, (int) ceil($orderCtrl->countAllOrders($status ?: null) / $perPage));
$page = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
$orders = $orderCtrl->getAllOrders($status ?: null, $perPage, ($page - 1) * $perPage);
?>

<div class="container page">
    <h1 class="display-md">Orders</h1>
    <?php include __DIR__ . '/admin_nav.php'; ?>

    <nav class="chip-row tight" aria-label="Filter by status">
        <a href="orders.php" class="chip <?php echo $status === '' ? 'is-on' : ''; ?>">All</a>
        <?php foreach (OrderController::STATUSES as $s): ?>
            <a href="orders.php?status=<?php echo $s; ?>" class="chip <?php echo $status === $s ? 'is-on' : ''; ?>"><?php echo order_status_label($s); ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if (!$orders): ?>
        <p class="muted"><?php echo $status ? 'No orders with this status.' : 'No orders yet. Paid orders appear here after Paystack confirms the payment.'; ?></p>
    <?php else: ?>
        <div class="table-wrap" tabindex="0" role="region" aria-label="Orders table">
            <table class="table">
                <thead>
                <tr><th scope="col">Order</th><th scope="col">Customer &amp; delivery</th><th scope="col">Items</th><th scope="col">Total</th><th scope="col">Status</th></tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr id="order-<?php echo (int) $o['order_id']; ?>">
                        <td>
                            <strong><?php echo e($o['invoice_no']); ?></strong><br>
                            <span class="muted small"><?php echo date('j M Y, H:i', strtotime($o['order_date'])); ?></span><br>
                            <a href="<?php echo e($orderCtrl->trackingUrl($o, $ROOT_DIR)); ?>" class="text-link small">Tracking page<span class="visually-hidden"> for <?php echo e($o['invoice_no']); ?></span></a>
                        </td>
                        <td>
                            <?php echo e($o['customer_name']); ?><br>
                            <span class="muted small"><?php echo e($o['delivery_phone']); ?> · <?php echo e($o['delivery_address']); ?>, <?php echo e($o['delivery_city']); ?></span>
                        </td>
                        <td>
                            <details class="order-details">
                                <summary><?php echo (int) $o['item_count']; ?> item<?php echo $o['item_count'] == 1 ? '' : 's'; ?></summary>
                                <ul>
                                    <?php foreach ($orderCtrl->getOrderItems($o['order_id']) as $item): ?>
                                        <li>
                                            <?php echo (int) $item['qty']; ?> × <?php echo e($item['item_name']); ?>
                                            <?php if ($item['design_id']): ?>
                                                — <a href="designs.php?status=<?php echo e($item['design_status']); ?>" class="text-link">design <?php echo e($item['design_status']); ?></a>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        </td>
                        <td><?php echo ghs($o['total']); ?></td>
                        <td>
                            <form action="<?php echo $ROOT_DIR; ?>/actions/update_order_status_action.php" method="POST" data-keep-place class="inline-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="order_id" value="<?php echo (int) $o['order_id']; ?>">
                                <label class="visually-hidden" for="status-<?php echo (int) $o['order_id']; ?>">Status for <?php echo e($o['invoice_no']); ?></label>
                                <select name="order_status" id="status-<?php echo (int) $o['order_id']; ?>" class="input">
                                    <?php foreach (OrderController::STATUSES as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo $o['order_status'] === $s ? 'selected' : ''; ?>><?php echo order_status_label($s); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-dark btn-sm">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $pageUrl = fn($n) => 'orders.php?' . http_build_query(array_filter(['status' => $status, 'page' => $n > 1 ? $n : '']));
        include __DIR__ . '/../layout/pagination.php';
        ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
