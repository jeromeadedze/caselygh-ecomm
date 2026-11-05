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
$recent = $orderCtrl->getAllOrders(null, 6);
$productCtrl = new ProductController();
$lowStock = $productCtrl->getLowStockVariants(3);
$lowStockTotal = $productCtrl->countLowStockVariants(3);
$customers = (new CustomerController())->countCustomers();
?>

<div class="container page">
    <h1 class="display-md">Dashboard</h1>
    <?php include __DIR__ . '/admin_nav.php'; ?>

    <div class="stat-grid">
        <div class="stat"><p class="stat-label">Revenue today</p><p class="stat-value"><?php echo ghs($summary['revenue_today']); ?></p></div>
        <div class="stat"><p class="stat-label">Total revenue</p><p class="stat-value"><?php echo ghs($summary['revenue']); ?></p></div>
        <a href="orders.php?status=paid" class="stat stat-link"><span class="stat-label">Orders to fulfil</span><span class="stat-value"><?php echo (int) $summary['to_fulfil']; ?></span><span class="stat-action">View orders &rarr;</span></a>
        <a href="designs.php" class="stat stat-link"><span class="stat-label">Designs to review</span><span class="stat-value"><?php echo $pendingDesigns; ?></span><span class="stat-action">Review designs &rarr;</span></a>
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
                <div class="table-wrap" tabindex="0" role="region" aria-label="Recent orders table">
                    <table class="table">
                        <thead><tr><th scope="col">Order</th><th scope="col">Customer</th><th scope="col">Total</th><th scope="col">Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($recent as $o): ?>
                            <tr>
                                <td class="nowrap"><a href="orders.php#order-<?php echo (int) $o['order_id']; ?>" class="text-link"><?php echo e($o['invoice_no']); ?></a></td>
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
                <div class="table-wrap" tabindex="0" role="region" aria-label="Best sellers table">
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

            <div class="section-head low-stock-head">
                <h2 class="section-title">Low stock</h2>
                <a href="products.php#all-cases" class="text-link">Manage stock &rarr;</a>
            </div>
            <?php if (!$lowStock): ?>
                <p class="muted">Every case has more than 3 in stock for each phone.</p>
            <?php else: ?>
                <p class="muted small low-stock-note">Showing <?php echo count($lowStock); ?> of <?php echo $lowStockTotal; ?> with 3 or fewer left, lowest first.</p>
                <div class="table-wrap" tabindex="0" role="region" aria-label="Low stock table">
                    <table class="table">
                        <thead><tr><th scope="col">Case</th><th scope="col">Phone</th><th scope="col">Left</th></tr></thead>
                        <tbody>
                        <?php foreach ($lowStock as $v): ?>
                            <tr><td><a href="products.php?edit=<?php echo (int) $v['product_id']; ?>" class="text-link"><?php echo e($v['product_title']); ?></a> (<?php echo e($v['case_type']); ?>)</td><td><?php echo e($v['model_name']); ?></td><td><?php echo (int) $v['stock']; ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
