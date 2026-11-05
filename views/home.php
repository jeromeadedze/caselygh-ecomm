<?php
// Loaded by index.php (ROOT_DIR = '.')
// The landing page gets visitors shopping fast, in AIDA order: the hero grabs
// attention, products and the three steps build interest, real customers and
// benefits build desire, and the FAQ and final buttons lead to action.
// Prices come from core/app_config.php and the FAQ from data/policies, so the
// page always says what the shop actually does.
$page_description = 'Design a phone case with your photos or name and see it live on your exact phone. From ' . ghs(CUSTOM_CASE_PRICE) . ', pay with MoMo or card, delivered within Ghana.';
$page_image = APP_URL . '/images/site/hero_case_ring.jpg';
require_once __DIR__ . '/layout/header.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$productCtrl = new ProductController();
$collections = $productCtrl->getCollections();
$bestSellers = $productCtrl->getFeaturedProducts(4);
$modelsByBrand = $productCtrl->getModelsByBrand();

$designUrl = $ROOT_DIR . '/views/design.php';
$modelNames = [];
foreach ($modelsByBrand as $models) {
    $modelNames = array_merge($modelNames, array_column($models, 'model_name'));
}

$steps = [
    ['Pick your phone', 'Choose your exact model, then a Standard or Tough case.'],
    ['Make it yours', 'Add your photos, pick a layout and filter, then type your name right on the case.'],
    ['We print and deliver', 'Pay with MoMo or card. Our team checks your design before printing, then delivers it within Ghana.'],
];

$benefits = [
    ['See it before you pay', 'The live preview uses your phone\'s own camera layout. Move, resize and rotate your photo and text until it looks right.'],
    ['Up to 36 photos on one case', 'Use one favourite photo, or fill a 2 × 6, 3 × 7 or 4 × 9 collage.'],
    ['Slim or extra protection', 'Standard is slim and light for every day. Tough adds double layer drop protection for ' . ghs(TOUGH_EXTRA) . ' more.'],
    ['Save it and finish later', 'Not ready yet? Log in and keep your design as a draft in your account.'],
];

// Every answer comes from the shop's settings or its published policies.
$faqs = [
    ['How much does a custom case cost?',
     'A custom Standard case is ' . ghs(CUSTOM_CASE_PRICE) . ' and a Tough case is ' . ghs(price_for(CUSTOM_CASE_PRICE, 'Tough')) . '. Delivery is ' . ghs(DELIVERY_FEE) . ' and is shown at checkout before you pay.'],
    ['Which phones do you make cases for?',
     'Right now: ' . implode(', ', $modelNames) . '. Not on the list? Message us on WhatsApp at 0509868659 and ask.'],
    ['What can I put on my case?',
     'Your own JPG, PNG or WebP photos, plus a name or initials of up to 14 characters. Photos must be yours and free of copyrighted characters, logos and offensive content.'],
    ['Do you check my design before printing?',
     'Yes. The Casely team reviews every custom design for content and print quality before it is printed.'],
    ['How do I pay?',
     'With MTN MoMo, Telecel Cash, AT Money or card on Paystack\'s secure page. Casely never sees your card details or MoMo PIN.'],
    ['How long does delivery take?',
     'Custom cases are made to order, so production starts after your payment is confirmed. Timing depends on your order and location within Ghana, and we keep you updated by WhatsApp or phone. You can also follow your order in your account.'],
    ['What if my case arrives damaged or wrong?',
     'Contact us within 48 hours with a photo or video of the problem. We review each case and arrange an exchange or refund where it qualifies. Custom cases cannot be returned for a change of mind.'],
    ['Can I change my order after paying?',
     'Custom orders cannot be changed or cancelled once production has started, so check your phone model and design before you pay. Saving a draft gives you time to get it right.'],
];
$faqSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn($f) => [
        '@type' => 'Question',
        'name' => $f[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
    ], $faqs),
];
?>

<section class="container hero">
    <div class="hero-media">
        <?php include __DIR__ . '/layout/hero_ring.php'; ?>
        <div class="hero-copy">
            <span class="script hero-script">Custom cases</span>
            <h1>A phone case<br>made just for you</h1>
            <p class="hero-sub">Add your photos or name and see it live on your exact phone. From <?php echo ghs(CUSTOM_CASE_PRICE); ?>.</p>
            <div class="hero-actions">
                <a href="<?php echo $designUrl; ?>" class="btn btn-primary">Design your case</a>
                <a href="#shop" class="btn btn-outline">Shop our designs</a>
            </div>
            <p class="hero-proof"><a href="https://www.instagram.com/casely.gh/" target="_blank" rel="noopener">2,300+ followers on Instagram<span class="visually-hidden"> (opens in a new tab)</span></a></p>
        </div>
    </div>
</section>

<section class="container section" id="shop" data-reveal>
    <h2 class="section-title">Or choose one of our designs</h2>
    <form action="<?php echo $ROOT_DIR; ?>/views/shop.php" method="GET" class="phone-finder">
        <p class="phone-finder-title">Find cases for your phone</p>
        <label class="field">
            <span>Phone model</span>
            <select name="model" class="input" required>
                <option value="">Choose your phone</option>
                <?php foreach ($modelsByBrand as $brand => $models): ?>
                    <optgroup label="<?php echo e($brand); ?>">
                        <?php foreach ($models as $m): ?>
                            <option value="<?php echo (int) $m['model_id']; ?>"><?php echo e($m['model_name']); ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn btn-dark">Show my cases</button>
    </form>

    <div class="section-head shop-best">
        <h3 class="section-title-sm">Latest designs</h3>
        <a href="<?php echo $ROOT_DIR; ?>/views/shop.php" class="text-link">See all cases &rarr;</a>
    </div>
    <div class="product-grid cols-4 shop-grid">
        <?php foreach ($bestSellers as $product): ?>
            <?php include __DIR__ . '/layout/product_card.php'; ?>
        <?php endforeach; ?>
    </div>

    <h3 class="section-title-sm shop-collections">Shop by collection</h3>
    <div class="collection-grid">
        <?php foreach ($collections as $c): ?>
            <a href="<?php echo $ROOT_DIR; ?>/views/shop.php?collection=<?php echo urlencode($c['collection_slug']); ?>" class="collection-tile tone-<?php echo e($c['collection_tone']); ?>">
                <span><?php echo e($c['collection_name']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="container section" id="how" data-reveal>
    <h2 class="section-title center">Three steps to your own case</h2>
    <ol class="how-steps">
        <?php foreach ($steps as $i => [$title, $text]): ?>
            <li class="how-step tone-<?php echo ['pink', 'lilac', 'butter'][$i]; ?>">
                <span class="how-number" aria-hidden="true"><?php echo $i + 1; ?></span>
                <h3 class="how-title"><?php echo e($title); ?></h3>
                <p><?php echo e($text); ?></p>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

<?php
// Instagram strip. The posts are listed in layout/ig_posts.php.
require __DIR__ . '/layout/ig_posts.php';
?>
<section class="container section ig-community" data-reveal aria-labelledby="ig-title">
    <div class="ig-head">
        <div>
            <h2 class="section-title ig-title" id="ig-title">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                Casely Community
            </h2>
            <p class="muted">Real customers, real cases. Tag <a href="<?php echo $igUrl; ?>" class="text-link" target="_blank" rel="noopener">@casely.gh</a> to be featured.</p>
        </div>
        <a href="<?php echo $igUrl; ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener">Follow @casely.gh<span class="visually-hidden"> on Instagram (opens in a new tab)</span></a>
    </div>

    <ul class="ig-strip">
        <?php foreach ($igPosts as [$file, $link, $alt]): ?>
            <li>
                <a href="<?php echo $link; ?>" class="ig-card" target="_blank" rel="noopener">
                    <img src="<?php echo $ROOT_DIR; ?>/images/community/<?php echo $file; ?>" alt="<?php echo e($alt); ?>" loading="lazy" width="480" height="640">
                    <span class="ig-card-tag" aria-hidden="true">
                        <svg class="icon-bold" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/></svg>
                        @casely.gh
                    </span>
                    <span class="visually-hidden">View this post on Instagram (opens in a new tab)</span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="ig-reward">
        <p><span class="script ig-reward-script">#CaselyCommunity</span> Post a photo of your Casely case. Once approved, you get a discount code for your next one.</p>
        <a href="<?php echo $ROOT_DIR; ?>/views/community.php" class="btn btn-dark btn-sm">Share yours</a>
    </div>
</section>

<section class="container section benefits" data-reveal>
    <div class="benefits-visual" aria-hidden="true">
        <?php foreach ([0, 9, 2] as $n => $caseIndex): ?>
            <div class="benefits-case"><?php echo casely_case_svg($heroCases[$caseIndex], 100 + $n); ?></div>
        <?php endforeach; ?>
    </div>
    <div>
        <h2 class="section-title">Yours from the first photo to the final print</h2>
        <ul class="benefit-list">
            <?php foreach ($benefits as [$title, $text]): ?>
                <li>
                    <h3 class="benefit-title"><?php echo e($title); ?></h3>
                    <p class="muted"><?php echo e($text); ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="<?php echo $designUrl; ?>" class="btn btn-primary">Design your case</a>
    </div>
</section>

<section class="container tagline">
    <p class="tagline-text" data-word-reveal>
        <span class="tagline-line">Your photos, your name,</span>
        <span class="tagline-line">on a case no one else has.</span>
    </p>
</section>

<section class="container container-prose section faq" id="faq" data-reveal>
    <h2 class="section-title">Questions before you start</h2>
    <div class="faq-list">
        <?php foreach ($faqs as [$q, $a]): ?>
            <details class="faq-item">
                <summary><?php echo e($q); ?><span class="faq-icon" aria-hidden="true"></span></summary>
                <p class="muted"><?php echo e($a); ?></p>
            </details>
        <?php endforeach; ?>
    </div>
    <p class="muted small faq-more">Read the full <a href="<?php echo $ROOT_DIR; ?>/views/policy.php?page=shipping" class="text-link">shipping policy</a> and <a href="<?php echo $ROOT_DIR; ?>/views/policy.php?page=refund" class="text-link">refund policy</a>.</p>
</section>
<script type="application/ld+json"><?php echo json_encode($faqSchema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?></script>

<section class="container section" data-reveal>
    <div class="final-cta">
        <?php include __DIR__ . '/layout/logo_mark.php'; ?>
        <h2 class="section-title">Your case is one design away</h2>
        <p>Our team checks every design before printing. If your case arrives damaged or wrong, tell us within 48 hours and we will review it for an exchange or refund.</p>
        <div class="hero-actions">
            <a href="<?php echo $designUrl; ?>" class="btn btn-primary">Design your case</a>
            <a href="<?php echo $ROOT_DIR; ?>/views/shop.php" class="btn btn-outline">Shop our designs</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
