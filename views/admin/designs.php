<?php
define('ROOT_DIR', '../..');
require_once __DIR__ . '/../../core/core.php';
require_admin();

$page_title = 'Custom designs';
$admin_page = 'designs';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../../controllers/CustomDesignController.php';

$status = in_array($_GET['status'] ?? '', ['pending', 'approved', 'rejected'], true) ? $_GET['status'] : 'pending';
$designs = (new CustomDesignController())->getDesignsForReview($status);
?>

<div class="container page">
    <h1 class="display-md">Custom designs</h1>
    <?php include __DIR__ . '/admin_nav.php'; ?>
    <p class="muted">Designs from paid orders. Check each one for copyrighted characters, logos, offensive content and print quality before printing.</p>

    <nav class="chip-row" aria-label="Filter designs">
        <?php foreach (['pending' => 'Waiting', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label): ?>
            <a href="designs.php?status=<?php echo $key; ?>" class="chip <?php echo $status === $key ? 'is-on' : ''; ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if (!$designs): ?>
        <p class="muted section"><?php echo [
            'pending' => 'No designs waiting. New ones appear here once a customer pays for a custom case.',
            'approved' => 'No approved designs yet.',
            'rejected' => 'No rejected designs.',
        ][$status] ?? 'No designs here.'; ?></p>
    <?php else: ?>
        <div class="review-grid section">
            <?php foreach ($designs as $d): ?>
                <article class="review-card">
                    <img src="<?php echo $ROOT_DIR; ?>/actions/design_image_action.php?id=<?php echo (int) $d['design_id']; ?>" alt="Custom design #<?php echo (int) $d['design_id']; ?>">
                    <div>
                        <p class="strong"><?php echo e($d['model_name']); ?> · <?php echo e($d['case_type']); ?></p>
                        <p class="muted small">Order <?php echo e($d['invoice_no']); ?> · <?php echo e($d['customer_name'] ?? 'Guest'); ?></p>
                        <?php if ($d['design_text']): ?><p class="small">Text: “<?php echo e($d['design_text']); ?>”</p><?php endif; ?>
                        <?php if ($d['review_note']): ?><p class="small">Reason: <?php echo e($d['review_note']); ?></p><?php endif; ?>
                    </div>
                    <div class="inline-form">
                        <a href="<?php echo $ROOT_DIR; ?>/actions/design_image_action.php?id=<?php echo (int) $d['design_id']; ?>&amp;download=1" class="text-link small">Download print file</a>
                        <?php if ($d['source_image']): ?>
                            <a href="<?php echo $ROOT_DIR; ?>/actions/design_image_action.php?id=<?php echo (int) $d['design_id']; ?>&amp;source=1&amp;download=1" class="text-link small">Original photo</a>
                        <?php endif; ?>
                    </div>
                    <?php if ($status === 'pending'): ?>
                        <form action="<?php echo $ROOT_DIR; ?>/actions/review_design_action.php" method="POST" data-keep-place class="stack">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="design_id" value="<?php echo (int) $d['design_id']; ?>">
                            <label class="field" style="margin:0">
                                <span>Reason for rejecting (for your records)</span>
                                <input type="text" name="note" class="input" maxlength="255" placeholder="e.g. Contains a cartoon character">
                            </label>
                            <div class="inline-form">
                                <button type="submit" name="decision" value="approved" class="btn btn-dark btn-sm">Approve for printing</button>
                                <button type="submit" name="decision" value="rejected" class="btn btn-outline btn-sm">Reject</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
