<?php
// Shared page header. Before including it a view may set:
//   $page_title       - text for the <title> tag
//   $page_description - meta description
//   $active_nav       - 'shop' | 'design' | 'community' (highlights the menu item)
require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/CartController.php';

$ROOT_DIR = root_dir();
$cartCtrl = new CartController();
$cartCount = $cartCtrl->getCartCount(get_cart_key());

$page_title = isset($page_title) ? $page_title . ' — Casely' : 'Casely — Custom phone cases in Ghana';
$page_description = $page_description ?? 'Design your own phone case or shop ready-made Casely cases. Pay with MoMo or card.';
$active_nav = $active_nav ?? '';

$navLinks = [
    'shop' => ['Shop', $ROOT_DIR . '/views/shop.php'],
    'design' => ['Customize', $ROOT_DIR . '/views/design.php'],
    'community' => ['Community', $ROOT_DIR . '/views/community.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($page_title); ?></title>
    <meta name="description" content="<?php echo e($page_description); ?>">
    <meta property="og:title" content="<?php echo e($page_title); ?>">
    <meta property="og:description" content="<?php echo e($page_description); ?>">
    <meta property="og:type" content="website">
    <?php if (!empty($page_image)): ?>
        <meta property="og:image" content="<?php echo e($page_image); ?>">
        <meta name="twitter:card" content="summary_large_image">
    <?php endif; ?>
    <link rel="icon" href="<?php echo $ROOT_DIR; ?>/images/site/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="<?php echo $ROOT_DIR; ?>/images/site/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="<?php echo $ROOT_DIR; ?>/images/site/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;800;900&family=Caveat:wght@600;700&family=DM+Sans:wght@400;500;700&family=Great+Vibes&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $ROOT_DIR; ?>/css/style.css?v=<?php echo filemtime(__DIR__ . '/../../css/style.css'); ?>">
    <script>
    // Only the case you opened travels between the shop and its page.
    (function () {
        function linkPhoto(url) {
            try {
                var slug = new URL(url).searchParams.get('slug');
                var card = slug && document.querySelector('a.product-card[href$="slug=' + encodeURIComponent(slug) + '"]');
                var photo = card && card.querySelector('.product-card-media');
                if (photo) photo.style.viewTransitionName = 'case-' + card.dataset.caseId;
                return photo;
            } catch (e) { /* the pages simply fade */ }
        }
        addEventListener('pageswap', function (e) {
            if (e.viewTransition && e.activation && e.activation.entry) linkPhoto(e.activation.entry.url);
        });
        addEventListener('pagereveal', function (e) {
            var from = window.navigation && navigation.activation && navigation.activation.from;
            if (!e.viewTransition || !from) return;
            var photo = linkPhoto(from.url);
            if (photo) e.viewTransition.finished.finally(function () { photo.style.viewTransitionName = ''; });
        });
    })();
    </script>
</head>
<body class="<?php echo isset($admin_page) ? 'admin-ui' : 'store-ui'; ?>">
    <a href="#main" class="skip-link">Skip to content</a>

    <header class="site-header">
        <div class="container header-inner">
            <a href="<?php echo $ROOT_DIR; ?>/index.php" class="logo"><?php include __DIR__ . '/logo_mark.php'; ?>Casely</a>

            <nav class="main-nav" aria-label="Main">
                <?php foreach ($navLinks as $key => [$label, $href]): ?>
                    <a href="<?php echo $href; ?>" class="<?php echo $active_nav === $key ? 'is-active' : ''; ?>" <?php echo $active_nav === $key ? 'aria-current="page"' : ''; ?>><?php echo $label; ?></a>
                <?php endforeach; ?>
            </nav>

            <div class="header-actions">
                <?php if (is_admin()): ?>
                    <a href="<?php echo $ROOT_DIR; ?>/views/admin/dashboard.php" class="header-link<?php echo isset($admin_page) ? ' is-active' : ''; ?>"<?php echo isset($admin_page) ? ' aria-current="true"' : ''; ?>>Admin</a>
                <?php endif; ?>
                <?php if (is_logged_in()): ?>
                    <a href="<?php echo $ROOT_DIR; ?>/views/account.php" class="header-link">Account</a>
                <?php else: ?>
                    <a href="<?php echo $ROOT_DIR; ?>/views/login.php" class="header-link">Log in</a>
                <?php endif; ?>
                <?php if (!is_admin()): ?>
                <a href="<?php echo $ROOT_DIR; ?>/views/cart.php" class="cart-pill">
                    <svg class="icon-bold" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    Cart
                    <span class="cart-count"><?php echo $cartCount; ?></span>
                    <span class="visually-hidden"><?php echo $cartCount === 1 ? 'item' : 'items'; ?></span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <nav class="mobile-nav" aria-label="Main (mobile)">
            <?php foreach ($navLinks as $key => [$label, $href]): ?>
                <a href="<?php echo $href; ?>" class="<?php echo $active_nav === $key ? 'is-active' : ''; ?>"><?php echo $label; ?></a>
            <?php endforeach; ?>
        </nav>
    </header>

    <?php include __DIR__ . '/flash.php'; ?>

    <main id="main" tabindex="-1">
