<?php $ROOT_DIR = $ROOT_DIR ?? root_dir(); ?>
    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <p class="footer-logo">Casely</p>
                <p class="script footer-tagline">Your case. Your way.</p>
            </div>
            <div class="footer-links">
                <a href="<?php echo $ROOT_DIR; ?>/views/shop.php">Shop all cases</a>
                <a href="<?php echo $ROOT_DIR; ?>/views/design.php">Custom case builder</a>
                <a href="<?php echo $ROOT_DIR; ?>/views/community.php">Casely Community</a>
                <a href="<?php echo $ROOT_DIR; ?>/views/account.php">Track my order</a>
            </div>
            <div class="footer-links">
                <a href="https://www.instagram.com/casely.gh/" target="_blank" rel="noopener" class="footer-ig">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    @casely.gh
                </a>
                <p class="muted-on-dark">Pay with MTN MoMo, Telecel Cash, AT Money or card. Secured by Paystack.</p>
                <p class="faint-on-dark">&copy; <?php echo date('Y'); ?> Casely, Accra, Ghana</p>
            </div>
        </div>
        <nav class="container footer-policies" aria-label="Policies">
            <a href="<?php echo $ROOT_DIR; ?>/views/policy.php?page=privacy">Privacy policy</a>
            <a href="<?php echo $ROOT_DIR; ?>/views/policy.php?page=terms">Terms of service</a>
            <a href="<?php echo $ROOT_DIR; ?>/views/policy.php?page=refund">Refund policy</a>
            <a href="<?php echo $ROOT_DIR; ?>/views/policy.php?page=shipping">Shipping policy</a>
        </nav>
    </footer>
    <script src="<?php echo $ROOT_DIR; ?>/js/main.js?v=<?php echo filemtime(__DIR__ . '/../../js/main.js'); ?>"></script>
</body>
</html>
