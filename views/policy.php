<?php
// Privacy, terms, refund and shipping pages. The text lives in
// data/policies/<page>.json (ported from the Lovable prototype) so the
// owner's wording can be updated without touching this layout.
require_once __DIR__ . '/../core/core.php';

$pages = ['privacy', 'terms', 'refund', 'shipping'];
$slug = in_array($_GET['page'] ?? '', $pages, true) ? $_GET['page'] : 'privacy';
$policy = json_decode((string) @file_get_contents(__DIR__ . '/../data/policies/' . $slug . '.json'), true);
if (!$policy) {
    $policy = ['eyebrow' => 'The fine print', 'title' => ucfirst($slug) . ' policy', 'sections' => [['paragraphs' => ['This policy is being updated.']]]];
}

$page_title = $policy['title'];
require_once __DIR__ . '/layout/header.php';
?>

<article class="container container-prose page policy">
    <p class="script script-lg"><?php echo e($policy['eyebrow'] ?? ''); ?></p>
    <h1 class="display-lg"><?php echo e($policy['title']); ?></h1>
    <?php if (!empty($policy['updated'])): ?>
        <p class="muted small">Last updated: <?php echo e($policy['updated']); ?></p>
    <?php endif; ?>

    <?php foreach ($policy['lead'] ?? [] as $p): ?>
        <p><?php echo e($p); ?></p>
    <?php endforeach; ?>

    <?php foreach ($policy['sections'] ?? [] as $section): ?>
        <section>
            <?php if (!empty($section['heading'])): ?>
                <h2><span class="dot" aria-hidden="true">·</span> <?php echo e($section['heading']); ?></h2>
            <?php endif; ?>
            <?php foreach ($section['paragraphs'] ?? [] as $p): ?>
                <?php if (is_array($p)): ?>
                    <p><strong><?php echo e($p['lead']); ?></strong> <?php echo e($p['text']); ?></p>
                <?php else: ?>
                    <p><?php echo e($p); ?></p>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!empty($section['bullets'])): ?>
                <ul>
                    <?php foreach ($section['bullets'] as $b): ?>
                        <li><?php echo e($b); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <div class="button-row">
        <a href="<?php echo $ROOT_DIR; ?>/views/shop.php" class="btn btn-primary">Shop cases</a>
        <a href="<?php echo $ROOT_DIR; ?>/views/design.php" class="btn btn-outline">Design yours</a>
    </div>
</article>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
