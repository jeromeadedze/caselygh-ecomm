<?php
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_admin();

$page_title = 'Admin dashboard';
$admin_page = 'dashboard';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../controllers/OrderController.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../controllers/CustomerController.php';

$orderCtrl = new OrderController();
$summary = $orderCtrl->getSalesSummary();
$bestSellers = $orderCtrl->getBestSellers(5);
$recent = array_slice($orderCtrl->getAllOrders(), 0, 6);
$lowStock = (new ProductController())->getLowStockVariants(3);
$customers = (new CustomerController())->countCustomers();
?>

<div class="container page">
    <h1 class="display-md">Casely admin</h1>
    <?php include __DIR__ . '/admin_nav.php'; ?>

    <div class="stat-grid">
        <div class="stat"><p class="stat-label">Revenue today</p><p class="stat-value"><?php echo ghs($summary['revenue_today']); ?></p></div>
        <div class="stat"><p class="stat-label">Total revenue</p><p class="stat-value"><?php echo ghs($summary['revenue']); ?></p></div>
        <div class="stat"><p class="stat-label">Orders to fulfil</p><p class="stat-value"><?php echo (int) $summary['to_fulfil']; ?></p></div>
        <div class="stat"><p class="stat-label">Designs to review</p><p class="stat-value"><?php echo $pendingDesigns; ?></p></div>
        <div class="stat"><p class="stat-label">Customers</p><p class="stat-value"><?php echo $customers; ?></p></div>
    </div>

    <div class="section split-2">
        <section>
            <div class="section-head">
                <h2 class="section-title">Recent orders</h2>
                <a href="orders.php" class="text-link">All orders &rarr;</a>
            </div>
            <?php if (!$recent): ?>
                <p class="muted">No orders yet.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th scope="col">Order</th><th scope="col">Customer</th><th scope="col">Total</th><th scope="col">Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($recent as $o): ?>
                            <tr>
                                <td><?php echo e($o['invoice_no']); ?></td>
                                <td><?php echo e($o['customer_name']); ?></td>
                                <td><?php echo ghs($o['total']); ?></td>
                                <td><span class="status status-<?php echo e($o['order_status']); ?>"><?php echo order_status_label($o['order_status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section>
            <h2 class="section-title">Best sellers</h2>
            <?php if (!$bestSellers): ?>
                <p class="muted">Best sellers appear once orders are paid.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th scope="col">Case</th><th scope="col">Sold</th></tr></thead>
                        <tbody>
                        <?php foreach ($bestSellers as $b): ?>
                            <tr><td><?php echo e($b['product_title']); ?></td><td><?php echo (int) $b['sold']; ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <h2 class="section-title" style="margin-top:2.5rem">Low stock</h2>
            <?php if (!$lowStock): ?>
                <p class="muted">Every variant has more than 3 in stock.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th scope="col">Case</th><th scope="col">Phone</th><th scope="col">Left</th></tr></thead>
                        <tbody>
                        <?php foreach ($lowStock as $v): ?>
                            <tr><td><?php echo e($v['product_title']); ?> (<?php echo e($v['case_type']); ?>)</td><td><?php echo e($v['model_name']); ?></td><td><?php echo (int) $v['stock']; ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
