<?php

// ============================================================
// core.php
// ------------------------------------------------------------
// Included at the top of every page and action. It handles the
// things that must happen on EVERY request: output buffering,
// a hardened session, login/role checks, the cart key, CSRF
// protection and a few small view helpers.
// Database access lives in core/db_class.php.
// ============================================================

require_once __DIR__ . "/app_config.php";

// Buffer output so header('Location: ...') redirects still work
// after a page has started printing.
ob_start();

// ---------- Secure session ----------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Session timeout: log out after SESSION_TIMEOUT seconds of inactivity.
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    secure_logout();
    $_SESSION['warning'] = "You were logged out after a period of inactivity.";
}
$_SESSION['last_activity'] = time();

// Session hijacking check: the browser that logged in must be the
// browser making this request.
if (is_logged_in() && ($_SESSION['user_agent'] ?? '') !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
    secure_logout();
    $_SESSION['error'] = "Your session ended for security reasons. Please log in again.";
}

// ---------- Login and roles ----------
function is_logged_in()
{
    return isset($_SESSION['customer_id']);
}

function is_admin()
{
    return isset($_SESSION['user_role']) && (int) $_SESSION['user_role'] === 1;
}

function get_user_id()
{
    return $_SESSION['customer_id'] ?? null;
}

function get_user_role()
{
    return $_SESSION['user_role'] ?? null;
}

// Remember the page the visitor wanted, then send them to log in.
function require_login()
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? null;
        $_SESSION['warning'] = "Please log in to continue.";
        header("Location: " . root_dir() . "/views/login.php");
        exit;
    }
}

function require_admin()
{
    if (!is_admin()) {
        $_SESSION['error'] = "Access denied. Admin privileges required.";
        header("Location: " . root_dir() . "/index.php");
        exit;
    }
}

// Store the logged-in user in the session (called after login/registration).
function login_user(array $user)
{
    session_regenerate_id(true);
    $_SESSION['customer_id'] = (int) $user['customer_id'];
    $_SESSION['customer_name'] = $user['customer_name'];
    $_SESSION['customer_email'] = $user['customer_email'];
    $_SESSION['user_role'] = (int) $user['user_role'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
}

// Clear the session, delete its cookie, and start a fresh one
// (the cart key survives so a logged-out shopper keeps their cart).
function secure_logout()
{
    $cartKey = $_SESSION['cart_key'] ?? null;
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    session_start();
    session_regenerate_id(true);
    if ($cartKey) {
        $_SESSION['cart_key'] = $cartKey;
    }
}

// ---------- Cart identity ----------
// Every visitor gets a random cart key stored in their session.
// (ecomlab used the IP address; on a shared campus network many
// students share one IP, so a session key is safer.)
function get_cart_key()
{
    if (empty($_SESSION['cart_key'])) {
        $_SESSION['cart_key'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['cart_key'];
}

// ---------- CSRF protection ----------
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Stop the request if the form's token does not match the session's.
function verify_csrf($json = false)
{
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        if ($json) {
            json_response(['success' => false, 'error' => 'Your session expired. Refresh the page and try again.'], 419);
        }
        $_SESSION['error'] = "Your session expired. Please try again.";
        redirect_back();
    }
}

// ---------- Small helpers used everywhere ----------
// Escape text for HTML output.
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Format a price in Ghana cedis, e.g. GH₵110 or GH₵92.50
function ghs($amount)
{
    $amount = (float) $amount;
    $decimals = (floor($amount) == $amount) ? 0 : 2;
    return 'GH₵' . number_format($amount, $decimals);
}

// Relative path from the current page back to the app root.
// index.php defines ROOT_DIR as '.', pages in views/ use '..',
// pages in views/admin/ use '../..'.
function root_dir()
{
    return defined('ROOT_DIR') ? ROOT_DIR : '..';
}

function redirect($location)
{
    header("Location: " . $location);
    exit;
}

function redirect_back($fallback = '../index.php')
{
    $referer = $_SERVER['HTTP_REFERER'] ?? $fallback;
    header("Location: " . $referer);
    exit;
}

function json_response(array $data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Price of a case type for a base price ("Tough" costs TOUGH_EXTRA more).
function price_for($base, $caseType)
{
    return (float) $base + ($caseType === 'Tough' ? TOUGH_EXTRA : 0);
}

// Human-readable order status labels.
function order_status_label($status)
{
    $labels = [
        'pending_payment' => 'Awaiting payment',
        'paid' => 'Paid',
        'printing' => 'Printing',
        'out_for_delivery' => 'Out for delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];
    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
}
