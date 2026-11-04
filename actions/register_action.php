<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/CustomerController.php";
require_once __DIR__ . "/../controllers/CartController.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/register.php');
}
verify_csrf();

$name = trim($_POST['customer_name'] ?? '');
$email = strtolower(trim($_POST['customer_email'] ?? ''));
$contact = trim($_POST['customer_contact'] ?? '');
$city = trim($_POST['customer_city'] ?? '');

$result = (new CustomerController())->register(
    $name, $email, $_POST['customer_pass'] ?? '', $_POST['confirm_pass'] ?? '', $contact, $city
);

if (!$result['success']) {
    $_SESSION['error'] = $result['error'];
    $_SESSION['old_register'] = compact('name', 'email', 'contact', 'city');
    redirect('../views/register.php');
}

// Log the new customer straight in and keep their cart.
login_user($result['user']);
(new CartController())->attachCustomer(get_cart_key(), get_user_id());
$_SESSION['success'] = "Welcome to Casely, " . strtok($result['user']['customer_name'], ' ') . "!";

$next = $_SESSION['redirect_after_login'] ?? '../index.php';
unset($_SESSION['redirect_after_login']);
redirect($next);
