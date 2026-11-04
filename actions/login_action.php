<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/CustomerController.php";
require_once __DIR__ . "/../controllers/CartController.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/login.php');
}
verify_csrf();

$email = strtolower(trim($_POST['customer_email'] ?? ''));
$pass = $_POST['customer_pass'] ?? '';

if ($email === '' || $pass === '') {
    $_SESSION['error'] = 'Enter your email and password.';
    redirect('../views/login.php');
}

$result = (new CustomerController())->login($email, $pass);
if (!$result['success']) {
    $_SESSION['error'] = $result['error'];
    $_SESSION['old_email'] = $email;
    redirect('../views/login.php');
}

login_user($result['user']);
(new CartController())->attachCustomer(get_cart_key(), get_user_id());

// Admins go to the dashboard; customers go back to where they were heading.
if (is_admin()) {
    redirect('../views/admin/dashboard.php');
}
$next = $_SESSION['redirect_after_login'] ?? '../index.php';
unset($_SESSION['redirect_after_login']);
redirect($next);
