<?php
require_once __DIR__ . '/../core/core.php';
// Arriving from the studio's "Save draft": go back to the studio after signing in.
if (($_GET['next'] ?? '') === 'studio') {
    $_SESSION['redirect_after_login'] = '../views/design.php';
}
if (is_logged_in()) {
    redirect(root_dir() . '/views/account.php');
}
$page_title = 'Create an account';
require_once __DIR__ . '/layout/header.php';
$old = $_SESSION['old_register'] ?? [];
unset($_SESSION['old_register']);
?>

<div class="container auth-page">
    <form action="<?php echo $ROOT_DIR; ?>/actions/register_action.php" method="POST" class="form-card auth-card">
        <?php echo csrf_field(); ?>
        <p class="script script-lg">Join Casely</p>
        <h1 class="display-md">Create an account</h1>
        <label class="field">
            <span>Full name</span>
            <input type="text" name="customer_name" class="input" required maxlength="100" autocomplete="name" value="<?php echo e($old['name'] ?? ''); ?>">
        </label>
        <label class="field">
            <span>Email</span>
            <input type="email" name="customer_email" class="input" required maxlength="100" autocomplete="email" value="<?php echo e($old['email'] ?? ''); ?>">
        </label>
        <div class="two-col">
            <label class="field">
                <span>Phone number</span>
                <input type="tel" name="customer_contact" class="input" required placeholder="024 123 4567" autocomplete="tel" value="<?php echo e($old['contact'] ?? ''); ?>">
            </label>
            <label class="field">
                <span>Town or city</span>
                <input type="text" name="customer_city" class="input" required maxlength="60" autocomplete="address-level2" value="<?php echo e($old['city'] ?? ''); ?>">
            </label>
        </div>
        <label class="field">
            <span>Password</span>
            <input type="password" name="customer_pass" class="input" required minlength="8" autocomplete="new-password" aria-describedby="pass-hint">
        </label>
        <p class="field-note" id="pass-hint">At least 8 characters, with a letter and a number.</p>
        <label class="field">
            <span>Confirm password</span>
            <input type="password" name="confirm_pass" class="input" required minlength="8" autocomplete="new-password">
        </label>
        <button type="submit" class="btn btn-primary btn-block">Create account</button>
        <p class="muted small center">Already have an account? <a href="<?php echo $ROOT_DIR; ?>/views/login.php" class="text-link">Log in</a></p>
    </form>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
