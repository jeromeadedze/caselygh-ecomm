<?php
// Custom case studio: a full-screen page without the normal header/footer.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$ROOT_DIR = root_dir();
$modelsByBrand = (new ProductController())->getModelsByBrand();
$firstBrand = array_key_first($modelsByBrand);

// ?draft=ID reopens one of the signed-in customer's saved drafts.
require_once __DIR__ . '/../controllers/DraftController.php';
$draftData = null;
if (isset($_GET['draft'])) {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = '../views/design.php?draft=' . (int) $_GET['draft'];
        $_SESSION['warning'] = 'Log in to open your draft.';
        redirect($ROOT_DIR . '/views/login.php');
    }
    $draft = (new DraftController())->getDraft((int) $_GET['draft'], get_user_id());
    if ($draft) {
        $draftData = [
            'id' => (int) $draft['draft_id'],
            'name' => $draft['draft_name'],
            'settings' => $draft['settings'],
            'photos' => array_map(fn($f) => ['name' => $f, 'url' => $ROOT_DIR . '/actions/draft_file_action.php?f=' . $f], $draft['photos']),
        ];
    } else {
        $_SESSION['error'] = 'We could not find that draft. It may have been deleted.';
    }
}

// The "My designs" drawer: drafts to reopen and designs made before.
$myDrafts = is_logged_in() ? (new DraftController())->getDrafts(get_user_id()) : [];
$myDesigns = [];
if (is_logged_in()) {
    require_once __DIR__ . '/../controllers/CustomDesignController.php';
    $myDesigns = (new CustomDesignController())->getDesignsByCustomer(get_user_id());
}
$myDesignCount = count($myDrafts) + count($myDesigns);

$steps = ['Device model', 'Case type', 'Photo', 'Layout', 'Filters', 'Text'];
$fonts = [
    ['Script', "Pacifico, cursive"],
    ['Handwritten', "Caveat, cursive"],
    ['Bold serif', "Georgia, 'Times New Roman', serif"],
    ['Bold sans', "Archivo, sans-serif"],
    ['Typewriter', "'Courier New', Courier, monospace"],
];
// Colours as hex so the print export (canvas) can use them too.
$backgrounds = ['Black' => '#1A1418', 'Blush' => '#F9D3DF', 'Lilac' => '#C9A0DC', 'Butter' => '#F3E98A', 'Mint' => '#BFE3C8', 'Sky' => '#BFD4F2', 'Plum' => '#5B2246', 'White' => '#FFFFFF'];
$inks = ['White' => '#FFFFFF', 'Black' => '#1A1418', 'Pink' => '#E2559A', 'Plum' => '#5B2246', 'Butter' => '#F3E98A'];
$filters = ['None', 'Mono', 'Warm', 'Pastel', 'Vintage', 'Pop', 'Film'];
// Collage grids: key => [columns, rows]
$collages = ['grid-2x6' => [2, 6], 'grid-3x7' => [3, 7], 'grid-4x9' => [4, 9]];
define('MAX_STUDIO_PHOTOS', 36);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Custom case studio — Design your phone case | Casely</title>
    <meta name="description" content="Step-by-step custom case studio: pick your phone, upload a photo, add filters, fonts and your name.">
    <link rel="icon" href="<?php echo $ROOT_DIR; ?>/images/site/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="<?php echo $ROOT_DIR; ?>/images/site/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="<?php echo $ROOT_DIR; ?>/images/site/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;800;900&family=Caveat:wght@600;700&family=DM+Sans:wght@400;500;700&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $ROOT_DIR; ?>/css/style.css?v=<?php echo filemtime(__DIR__ . '/../css/style.css'); ?>">
</head>
<body class="studio-body store-ui">
<?php include __DIR__ . '/layout/flash.php'; ?>

<main class="studio" id="studio"
     data-base-price="<?php echo (float) CUSTOM_CASE_PRICE; ?>"
     data-tough-extra="<?php echo (float) TOUGH_EXTRA; ?>"
     data-save-url="<?php echo $ROOT_DIR; ?>/actions/save_design_action.php"
     data-cart-url="<?php echo $ROOT_DIR; ?>/views/cart.php"
     data-max-photos="<?php echo MAX_STUDIO_PHOTOS; ?>"
     data-logged-in="<?php echo is_logged_in() ? '1' : '0'; ?>"
     data-draft-upload-url="<?php echo $ROOT_DIR; ?>/actions/upload_draft_photo_action.php"
     data-draft-save-url="<?php echo $ROOT_DIR; ?>/actions/save_draft_action.php"
     <?php if ($draftData): ?>data-draft="<?php echo e(json_encode($draftData)); ?>"<?php endif; ?>
     data-csrf="<?php echo e(csrf_token()); ?>">

    <h1 class="visually-hidden">Custom case studio</h1>

    <!-- Canvas -->
    <div class="studio-canvas">
        <div class="studio-topbar">
            <a href="<?php echo $ROOT_DIR; ?>/index.php" class="round-btn" id="studio-close" aria-label="Close studio and keep your design" title="Close and keep your design">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </a>
            <span class="studio-badge">CASELY</span>
            <div class="studio-topbar-actions">
                <a href="<?php echo $ROOT_DIR; ?>/views/design.php" class="round-btn studio-new" id="new-design" aria-label="Start a new design" title="Start a new design">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    <span class="studio-new-label">New design</span>
                </a>
                <button type="button" class="round-btn" id="undo-btn" aria-label="Undo" title="Undo (Ctrl+Z)" disabled><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg></button>
                <button type="button" class="round-btn" id="redo-btn" aria-label="Redo" title="Redo (Ctrl+Shift+Z)" disabled><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 14 5-5-5-5"/><path d="M20 9H9.5a5.5 5.5 0 0 0 0 11H13"/></svg></button>
                <button type="button" class="round-btn is-on" id="grid-toggle" aria-pressed="true" aria-label="Toggle alignment grid">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/></svg>
                </button>
            </div>
        </div>

        <div class="studio-stage">
            <div class="case-preview" id="case-preview" style="background:#1A1418">
                <div class="case-photo-box layout-full" id="photo-box" hidden>
                    <img id="photo" alt="Your main photo" draggable="false">
                    <div class="case-collage" id="collage" hidden></div>
                </div>
                <p class="case-text layout-centre" id="case-text" style="font-family: Georgia, 'Times New Roman', serif; color:#FFFFFF"><span class="case-text-inner" id="case-text-inner"></span></p>
                <div class="case-camera-svg" id="case-camera" aria-hidden="true"></div>
                <div class="case-grid" id="case-grid" aria-hidden="true"></div>
            </div>
            <!-- Selection box around the text: drag to move, corners to resize, top handle to rotate.
                 Keyboard users have the same controls as sliders and arrows in the Personalize step. -->
            <div class="text-box" id="text-box" hidden aria-hidden="true">
                <span class="tb-corner tb-nw" data-handle="scale"></span>
                <span class="tb-corner tb-ne" data-handle="scale"></span>
                <span class="tb-corner tb-sw" data-handle="scale"></span>
                <span class="tb-corner tb-se" data-handle="scale"></span>
                <span class="tb-rotate" data-handle="rotate"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/></svg></span>
            </div>
        </div>
        <p class="studio-hint" id="drag-hint" hidden>Drag the photo on the case to move it.</p>
        <button type="button" class="btn btn-outline btn-sm studio-add-text" id="add-text-btn">
            <svg class="icon-bold" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Add text
        </button>
    </div>

    <!-- Panel -->
    <aside class="studio-panel">
        <div class="studio-summary">
            <div class="studio-summary-text">
                <p class="studio-summary-model" data-summary-model>iPhone</p>
                <p data-summary-type>Standard case</p>
                <p data-summary-price><?php echo ghs(CUSTOM_CASE_PRICE); ?></p>
            </div>
            <div class="studio-summary-actions">
                <button type="button" class="btn btn-ghost-light" id="draft-btn" aria-label="Save draft"><span>Save<span class="btn-extra"> draft</span></span></button>
                <button type="button" class="btn btn-dark" id="add-btn" aria-label="Add to cart">
                    <svg class="icon-bold" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    <span class="btn-label">Add<span class="btn-extra"> to cart</span></span>
                </button>
            </div>
        </div>
        <div class="draft-status-row" id="draft-status-row" hidden>
            <p class="draft-status" id="draft-status" role="status"></p>
            <button type="button" class="text-link small" id="draft-rename" hidden>Rename</button>
        </div>

        <button type="button" class="designs-open" id="designs-open" aria-haspopup="dialog">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            <span class="designs-open-label">My designs</span>
            <?php if ($myDesignCount): ?><span class="designs-open-count"><?php echo $myDesignCount; ?></span><?php endif; ?>
            <svg class="designs-open-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
        </button>

        <p class="studio-error" id="studio-error" role="alert" hidden></p>
        <div class="studio-steps">
            <div class="steps-list">
                <?php foreach ($steps as $i => $label): ?>
                    <section class="step" data-step="<?php echo $i; ?>">
                        <h2 class="step-head-wrap">
                            <button type="button" class="step-head" aria-expanded="<?php echo $i === 0 ? 'true' : 'false'; ?>" aria-controls="step-body-<?php echo $i; ?>">
                                <span><?php echo str_pad($i + 1, 2, '0', STR_PAD_LEFT); ?>. <?php echo $label; ?></span>
                                <span class="step-hint" data-step-hint></span>
                                <span class="step-icon" aria-hidden="true"></span>
                            </button>
                        </h2>
                        <div class="step-body" id="step-body-<?php echo $i; ?>" <?php echo $i === 0 ? '' : 'hidden'; ?>>
                            <?php if ($i === 0): ?>
                                <div class="chip-row tight" role="group" aria-label="Phone brand">
                                    <?php foreach (array_keys($modelsByBrand) as $brand): ?>
                                        <button type="button" class="pick <?php echo $brand === $firstBrand ? 'is-on' : ''; ?>" data-brand="<?php echo e($brand); ?>"><?php echo e($brand); ?></button>
                                    <?php endforeach; ?>
                                </div>
                                <?php foreach ($modelsByBrand as $brand => $models): ?>
                                    <div class="pick-grid" data-models-for="<?php echo e($brand); ?>" <?php echo $brand === $firstBrand ? '' : 'hidden'; ?>>
                                        <?php foreach ($models as $m): ?>
                                            <button type="button" class="pick" data-model-id="<?php echo (int) $m['model_id']; ?>"><?php echo e($m['model_name']); ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>

                            <?php elseif ($i === 1): ?>
                                <div class="pick-grid">
                                    <button type="button" class="pick pick-card is-on" data-case-type="Standard"><strong>Standard</strong><span>Slim everyday</span></button>
                                    <button type="button" class="pick pick-card" data-case-type="Tough"><strong>Tough</strong><span>+<?php echo ghs(TOUGH_EXTRA); ?> · double layer protection</span></button>
                                </div>
                                <p class="swatch-label">Case colour</p>
                                <div class="swatches" role="group" aria-label="Case colour">
                                    <?php foreach ($backgrounds as $name => $hex): ?>
                                        <button type="button" class="swatch <?php echo $name === 'Black' ? 'is-on' : ''; ?>" data-bg="<?php echo $hex; ?>" style="background: <?php echo $hex; ?>" aria-label="<?php echo $name; ?>"></button>
                                    <?php endforeach; ?>
                                </div>

                            <?php elseif ($i === 2): ?>
                                <label class="upload-box">
                                    <svg class="icon-bold" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></svg>
                                    <span data-upload-label>Add photos</span>
                                    <input type="file" id="photo-input" accept="image/jpeg,image/png,image/webp" multiple class="visually-hidden">
                                </label>
                                <p class="field-note">Add 1 photo, or up to <?php echo MAX_STUDIO_PHOTOS; ?> for a collage. JPG, PNG or WebP. Bigger photos print sharper.</p>

                                <div class="photo-tray" id="photo-tray" hidden>
                                    <div class="photo-tray-head">
                                        <p class="small strong" aria-live="polite"><span id="photo-count">0</span>/<?php echo MAX_STUDIO_PHOTOS; ?> photos</p>
                                        <button type="button" class="text-link small" id="photo-sort">Sort by colour</button>
                                    </div>
                                    <ul class="photo-list" id="photo-list"></ul>
                                    <p class="field-note">Select a photo to make it the main one. Single-photo layouts use the main photo.</p>
                                </div>

                                <div class="photo-controls" id="photo-controls" hidden>
                                    <label class="slider-field"><span>Rotate <output id="rot-out">0°</output></span>
                                        <input type="range" id="rot" min="-180" max="180" value="0"></label>
                                    <label class="slider-field"><span>Zoom <output id="zoom-out">100%</output></span>
                                        <input type="range" id="zoom" min="50" max="300" value="100"></label>
                                    <button type="button" class="text-link" id="photo-reset">Reset position</button>
                                </div>

                            <?php elseif ($i === 3): ?>
                                <p class="pick-group-label">One photo</p>
                                <div class="pick-grid cols-3">
                                    <?php foreach (['Full bleed' => 'full', 'Framed' => 'framed', 'Split' => 'split'] as $label2 => $key): ?>
                                        <button type="button" class="pick <?php echo $key === 'full' ? 'is-on' : ''; ?>" data-layout="<?php echo $key; ?>"><?php echo $label2; ?></button>
                                    <?php endforeach; ?>
                                </div>
                                <p class="pick-group-label">Collage</p>
                                <div class="pick-grid cols-3">
                                    <?php foreach ($collages as $key => [$cols, $rows]): ?>
                                        <button type="button" class="pick pick-collage" data-layout="<?php echo $key; ?>">
                                            <span class="collage-icon" style="--cols: <?php echo $cols; ?>; --rows: <?php echo $rows; ?>;" aria-hidden="true"><?php echo str_repeat('<i></i>', $cols * $rows); ?></span>
                                            <?php echo $cols; ?> × <?php echo $rows; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <p class="field-note" style="margin-top: .75rem;">Collages repeat your photos to fill every square.</p>

                            <?php elseif ($i === 4): ?>
                                <div class="pick-grid cols-3">
                                    <?php foreach ($filters as $f): ?>
                                        <button type="button" class="pick <?php echo $f === 'None' ? 'is-on' : ''; ?>" data-filter="<?php echo $f; ?>"><?php echo $f; ?></button>
                                    <?php endforeach; ?>
                                </div>
                                <div class="filter-strength">
                                    <label class="slider-field"><span>Strength <output id="strength-out">80%</output></span>
                                        <input type="range" id="strength" min="0" max="100" value="80"></label>
                                    <button type="button" class="text-link" id="filter-reset">Reset</button>
                                </div>
                            <?php elseif ($i === 5): ?>
                                <label class="field">
                                    <span class="field-row">Name or initials <output id="text-count" class="muted">0/14</output></span>
                                    <input type="text" id="text-input" class="input" maxlength="14" value="" placeholder="e.g. Ama or A.K." autocomplete="off">
                                </label>
                                <p class="field-note" id="text-optional-note">Text is optional. Leave this empty for a case with no text.</p>
                                <button type="button" class="text-link small" id="text-remove" hidden>Remove text</button>
                                <p class="swatch-label">Font</p>
                                <div class="pick-grid">
                                    <?php foreach ($fonts as [$label2, $css]): ?>
                                        <button type="button" class="pick font-pick <?php echo $label2 === 'Bold serif' ? 'is-on' : ''; ?>" data-font="<?php echo e($css); ?>" style="font-family: <?php echo e($css); ?>"><?php echo $label2; ?></button>
                                    <?php endforeach; ?>
                                </div>
                                <p class="swatch-label">Text layout</p>
                                <div class="pick-grid">
                                    <?php foreach (['Centre', 'Bottom', 'Corner', 'Vertical'] as $tl): ?>
                                        <button type="button" class="pick <?php echo $tl === 'Centre' ? 'is-on' : ''; ?>" data-text-layout="<?php echo strtolower($tl); ?>"><?php echo $tl; ?></button>
                                    <?php endforeach; ?>
                                </div>
                                <p class="swatch-label">Text colour</p>
                                <div class="swatches" role="group" aria-label="Text colour">
                                    <?php foreach ($inks as $name => $hex): ?>
                                        <button type="button" class="swatch <?php echo $name === 'White' ? 'is-on' : ''; ?>" data-ink="<?php echo $hex; ?>" style="background: <?php echo $hex; ?>" aria-label="<?php echo $name; ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                                <p class="swatch-label">Size, angle and position</p>
                                <p class="field-note" style="margin: 0 0 .75rem;">Select the text on the case: drag it to move, pull a corner to resize, use the round handle to rotate.</p>
                                <label class="slider-field"><span>Size <output id="text-size-out">100%</output></span>
                                    <input type="range" id="text-size" min="10" max="300" value="100"></label>
                                <label class="slider-field" style="margin-top: .75rem;"><span>Angle <output id="text-rot-out">0°</output></span>
                                    <input type="range" id="text-rot" min="-180" max="180" value="0"></label>
                                <button type="button" class="text-link small" id="text-reset" style="margin-top: 1rem;">Reset size, angle and position</button>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>

            <div class="step-nav">
                <button type="button" class="step-arrow" id="step-prev" aria-label="Previous step">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <span id="step-count">01/06</span>
                <button type="button" class="step-arrow" id="step-next" aria-label="Next step">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>
        </div>
    </aside>
</main>

<!-- Guests: drafts need an account. The design is kept in this browser while they log in. -->
<dialog class="signin-dialog" id="draft-signin" aria-labelledby="draft-signin-title">
    <h2 class="section-title-sm" id="draft-signin-title">Log in to save your design</h2>
    <p class="muted">Drafts are kept in your Casely account, so you can finish your case later on any device.</p>
    <p class="small muted" style="margin-top: .5rem;">We'll hold on to this design while you log in and save it for you straight after.</p>
    <div class="button-row" style="margin-top: 1.25rem;">
        <a href="<?php echo $ROOT_DIR; ?>/views/login.php?next=studio" class="btn btn-primary" data-keep-design>Log in</a>
        <a href="<?php echo $ROOT_DIR; ?>/views/register.php?next=studio" class="btn btn-outline" data-keep-design>Create account</a>
    </div>
    <form method="dialog"><button class="signin-dialog-close text-link small" value="cancel">Not now</button></form>
</dialog>

<dialog class="designs-drawer" id="designs-drawer" aria-labelledby="designs-title">
    <div class="designs-head">
        <h2 class="section-title-sm" id="designs-title">My designs</h2>
        <button type="button" class="round-btn" id="designs-close" aria-label="Close My designs">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <?php if (!is_logged_in()): ?>
        <p class="muted">Log in to see your drafts and past designs. We'll hold on to the design you're working on.</p>
        <div class="button-row" style="margin-top: 1rem;">
            <a href="<?php echo $ROOT_DIR; ?>/views/login.php?next=studio" class="btn btn-primary" data-keep-design>Log in</a>
            <a href="<?php echo $ROOT_DIR; ?>/views/register.php?next=studio" class="btn btn-outline" data-keep-design>Create account</a>
        </div>
    <?php else: ?>
        <section class="designs-group" aria-labelledby="drafts-heading">
            <h3 class="designs-group-title" id="drafts-heading">Drafts</h3>
            <?php if (!$myDrafts): ?>
                <p class="muted small">No drafts yet. Press Save draft to keep a design and finish it later.</p>
            <?php else: ?>
                <ul class="designs-list">
                    <?php foreach ($myDrafts as $d): ?>
                        <?php $isCurrent = $draftData && (int) $d['draft_id'] === $draftData['id']; ?>
                        <li class="designs-item" data-draft-row="<?php echo (int) $d['draft_id']; ?>">
                            <span class="designs-thumb"><?php if ($d['preview_image']): ?><img src="<?php echo $ROOT_DIR; ?>/actions/draft_file_action.php?f=<?php echo e($d['preview_image']); ?>" alt="" loading="lazy" onerror="this.remove()"><?php endif; ?></span>
                            <span class="designs-info">
                                <strong><?php echo e($d['draft_name']); ?></strong>
                                <span class="muted small">Saved <?php echo date('j M Y, H:i', strtotime($d['updated_at'])); ?></span>
                            </span>
                            <?php if ($isCurrent): ?>
                                <span class="status status-approved">Editing</span>
                            <?php else: ?>
                                <a href="<?php echo $ROOT_DIR; ?>/views/design.php?draft=<?php echo (int) $d['draft_id']; ?>" class="btn btn-outline btn-xs" data-open-draft>Open<span class="visually-hidden"> <?php echo e($d['draft_name']); ?></span></a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <section class="designs-group" aria-labelledby="past-heading">
            <h3 class="designs-group-title" id="past-heading">Past designs</h3>
            <?php if (!$myDesigns): ?>
                <p class="muted small">Designs you add to your cart appear here.</p>
            <?php else: ?>
                <ul class="designs-list">
                    <?php foreach ($myDesigns as $d): ?>
                        <?php
                        $state = $d['status'] === 'rejected' ? ['Not approved', 'status-rejected']
                            : ($d['status'] === 'approved' ? ['Approved', 'status-approved']
                            : ($d['ordered'] ? ['In review', 'status-pending'] : ['Not ordered yet', 'status-hidden']));
                        ?>
                        <li class="designs-item">
                            <span class="designs-thumb"><img src="<?php echo $ROOT_DIR; ?>/actions/design_image_action.php?id=<?php echo (int) $d['design_id']; ?>" alt="" loading="lazy" onerror="this.remove()"></span>
                            <span class="designs-info">
                                <strong>Custom case<?php echo $d['design_text'] ? ' · ' . e($d['design_text']) : ''; ?></strong>
                                <span class="muted small"><?php echo e($d['model_name']); ?> · <?php echo e($d['case_type']); ?> · <?php echo ghs($d['price']); ?></span>
                                <span class="status <?php echo $state[1]; ?>"><?php echo $state[0]; ?></span>
                            </span>
                            <?php if ($d['status'] !== 'rejected'): ?>
                                <form action="<?php echo $ROOT_DIR; ?>/actions/reorder_design_action.php" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="design_id" value="<?php echo (int) $d['design_id']; ?>">
                                    <button type="submit" class="btn btn-outline btn-xs"><?php echo $d['ordered'] ? 'Order again' : 'Add to cart'; ?><span class="visually-hidden">: <?php echo e($d['design_text'] ?: 'custom case'); ?></span></button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</dialog>

<dialog class="signin-dialog" id="draft-name-dialog" aria-labelledby="draft-name-title">
    <form id="draft-name-form">
        <h2 class="section-title-sm" id="draft-name-title">Name this design</h2>
        <label class="field">
            <span>Design name</span>
            <input type="text" class="input" id="draft-name-input" maxlength="80" autocomplete="off">
        </label>
        <p class="small muted">This is how it appears under Drafts in your account.</p>
        <div class="button-row" style="margin-top: 1.25rem;">
            <button type="submit" class="btn btn-primary" id="draft-name-save">Save draft</button>
            <button type="button" class="btn btn-outline" id="draft-name-cancel">Cancel</button>
        </div>
    </form>
</dialog>

<dialog class="signin-dialog" id="new-design-dialog" aria-labelledby="new-design-title">
    <h2 class="section-title-sm" id="new-design-title">Start a new design?</h2>
    <p class="muted">Changes you haven't saved to this design will be lost. Save a draft first to keep them.</p>
    <div class="button-row" style="margin-top: 1.25rem;">
        <a href="<?php echo $ROOT_DIR; ?>/views/design.php" class="btn btn-danger" id="new-design-go">Start new design</a>
        <button type="button" class="btn btn-outline" id="new-design-keep">Keep editing</button>
    </div>
</dialog>

<script src="<?php echo $ROOT_DIR; ?>/js/case-mockup.js?v=<?php echo filemtime(__DIR__ . '/../js/case-mockup.js'); ?>"></script>
<script src="<?php echo $ROOT_DIR; ?>/js/design.js?v=<?php echo filemtime(__DIR__ . '/../js/design.js'); ?>"></script>
</body>
</html>
