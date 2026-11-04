<?php
// Toast messages set by actions via $_SESSION['success'|'warning'|'error'].
// Shown once, then removed from the session.
$ROOT_DIR = $ROOT_DIR ?? root_dir();
$flashes = [];
foreach (['success', 'warning', 'error'] as $type) {
    if (!empty($_SESSION[$type])) {
        $flashes[$type] = $_SESSION[$type];
        unset($_SESSION[$type]);
    }
}
?>
<?php if ($flashes): ?>
    <div class="toast-stack" aria-live="polite">
        <?php foreach ($flashes as $type => $message): ?>
            <div class="toast toast-<?php echo $type; ?>" role="<?php echo $type === 'error' ? 'alert' : 'status'; ?>">
                <p><?php echo e($message); ?></p>
                <?php if ($type === 'success' && stripos($message, 'cart') !== false): ?>
                    <a href="<?php echo $ROOT_DIR; ?>/views/cart.php" class="toast-link">View cart</a>
                <?php endif; ?>
                <button type="button" class="toast-close" aria-label="Dismiss message" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
