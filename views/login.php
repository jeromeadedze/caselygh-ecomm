<?php
require_once __DIR__ . '/../core/core.php';
if (is_logged_in()) {
    redirect(root_dir() . '/views/account.php');
}
$page_title = 'Log in';
require_once __DIR__ . '/layout/header.php';
$oldEmail = $_SESSION['old_email'] ?? '';
unset($_SESSION['old_email']);
?>

<div class="container auth-page">
    <form action="<?php echo $ROOT_DIR; ?>/actions/login_action.php" method="POST" class="form-card auth-card">
        <?php echo csrf_field(); ?>
        <p class="script script-lg">Welcome back</p>
        <h1 class="display-md">Log in</h1>
        <label class="field">
            <span>Email</span>
            <input type="email" name="customer_email" class="input" required autocomplete="email" value="<?php echo e($oldEmail); ?>">
        </label>
        <label class="field">
            <span>Password</span>
            <input type="password" name="customer_pass" class="input" required autocomplete="current-password">
        </label>
        <button type="submit" class="btn btn-primary btn-block">Log in</button>
        <p class="muted small center">New to Casely? <a href="<?php echo $ROOT_DIR; ?>/views/register.php" class="text-link">Create an account</a></p>
    </form>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
