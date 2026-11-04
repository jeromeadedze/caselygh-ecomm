<?php
// Custom case studio: a full-screen page without the normal header/footer.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

$ROOT_DIR = root_dir();
$modelsByBrand = (new ProductController())->getModelsByBrand();
$firstBrand = array_key_first($modelsByBrand);

$steps = ['Device model', 'Case type', 'Photo', 'Layout', 'Filters', 'Fonts', 'Personalize', 'Text layout'];
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
    <title>Custom Studio — Design your phone case | Casely</title>
    <meta name="description" content="Step-by-step custom case studio: pick your phone, upload a photo, add filters, fonts and your name.">
    <link rel="icon" href="<?php echo $ROOT_DIR; ?>/images/site/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="<?php echo $ROOT_DIR; ?>/images/site/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="<?php echo $ROOT_DIR; ?>/images/site/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;800;900&family=Caveat:wght@600;700&family=DM+Sans:wght@400;500;700&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $ROOT_DIR; ?>/css/style.css?v=<?php echo filemtime(__DIR__ . '/../css/style.css'); ?>">
</head>
<body class="studio-body">
<?php include __DIR__ . '/layout/flash.php'; ?>

<div class="studio" id="studio"
     data-base-price="<?php echo (float) CUSTOM_CASE_PRICE; ?>"
     data-tough-extra="<?php echo (float) TOUGH_EXTRA; ?>"
     data-save-url="<?php echo $ROOT_DIR; ?>/actions/save_design_action.php"
     data-cart-url="<?php echo $ROOT_DIR; ?>/views/cart.php"
     data-max-photos="<?php echo MAX_STUDIO_PHOTOS; ?>"
     data-csrf="<?php echo e(csrf_token()); ?>">

    <!-- Canvas -->
    <div class="studio-canvas">
        <div class="studio-topbar">
            <a href="<?php echo $ROOT_DIR; ?>/views/shop.php" class="round-btn" aria-label="Close studio">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </a>
            <span class="studio-badge">CASELY</span>
            <button type="button" class="round-btn is-on" id="grid-toggle" aria-pressed="true" aria-label="Toggle alignment grid">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/></svg>
            </button>
        </div>

        <div class="studio-stage">
            <div class="case-preview" id="case-preview" style="background:#1A1418">
                <div class="case-photo-box layout-full" id="photo-box" hidden>
                    <img id="photo" alt="Your main photo" draggable="false">
                    <div class="case-collage" id="collage" hidden></div>
                </div>
                <p class="case-text layout-vertical" id="case-text" style="font-family: Georgia, 'Times New Roman', serif; color:#FFFFFF"><span class="case-text-inner" id="case-text-inner">CS</span></p>
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
    </div>

    <!-- Panel -->
    <aside class="studio-panel">
        <div class="studio-summary">
            <div class="studio-summary-text">
                <p class="studio-summary-model" data-summary-model>iPhone</p>
                <p data-summary-type>Standard case</p>
                <p data-summary-price><?php echo ghs(CUSTOM_CASE_PRICE); ?></p>
            </div>
            <button type="button" class="btn btn-dark" id="add-btn">
                <svg class="icon-bold" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Add
            </button>
        </div>

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
                                <div class="chip-row tight" role="radiogroup" aria-label="Phone brand">
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
                                    <button type="button" class="pick pick-card" data-case-type="Tough"><strong>Tough</strong><span>+<?php echo ghs(TOUGH_EXTRA); ?> · drop-tested</span></button>
                                </div>

                            <?php elseif ($i === 2): ?>
                                <label class="upload-box">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></svg>
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
                                    <p class="field-note">Tap a photo to make it the main one. Single-photo layouts use the main photo.</p>
                                </div>

                                <div class="photo-controls" id="photo-controls" hidden>
                                    <label class="slider-field"><span>Rotate <output id="rot-out">0°</output></span>
                                        <input type="range" id="rot" min="-180" max="180" value="0"></label>
                                    <label class="slider-field"><span>Zoom <output id="zoom-out">100%</output></span>
                                        <input type="range" id="zoom" min="50" max="300" value="100"></label>
                                    <button type="button" class="text-link" id="photo-reset">Reset position</button>
                                </div>

                                <label class="consent">
                                    <input type="checkbox" id="consent">
                                    <span>I own these photos and they have no copyrighted characters, logos or offensive content. My design will be checked by the Casely team before printing.</span>
                                </label>

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
                                <div class="pick-grid">
                                    <?php foreach ($fonts as [$label2, $css]): ?>
                                        <button type="button" class="pick font-pick <?php echo $label2 === 'Bold serif' ? 'is-on' : ''; ?>" data-font="<?php echo e($css); ?>" style="font-family: <?php echo e($css); ?>"><?php echo $label2; ?></button>
                                    <?php endforeach; ?>
                                    <button type="button" class="pick" data-font="none">No text</button>
                                </div>

                            <?php elseif ($i === 6): ?>
                                <label class="field">
                                    <span class="field-row">Name or initials <output id="text-count" class="muted">2/14</output></span>
                                    <input type="text" id="text-input" class="input" maxlength="14" value="CS" autocomplete="off">
                                </label>
                                <p class="swatch-label">Text colour</p>
                                <div class="swatches" role="radiogroup" aria-label="Text colour">
                                    <?php foreach ($inks as $name => $hex): ?>
                                        <button type="button" class="swatch <?php echo $name === 'White' ? 'is-on' : ''; ?>" data-ink="<?php echo $hex; ?>" style="background: <?php echo $hex; ?>" aria-label="<?php echo $name; ?>" role="radio" aria-checked="<?php echo $name === 'White' ? 'true' : 'false'; ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                                <p class="swatch-label">Background</p>
                                <div class="swatches" role="radiogroup" aria-label="Background colour">
                                    <?php foreach ($backgrounds as $name => $hex): ?>
                                        <button type="button" class="swatch <?php echo $name === 'Black' ? 'is-on' : ''; ?>" data-bg="<?php echo $hex; ?>" style="background: <?php echo $hex; ?>" aria-label="<?php echo $name; ?>" role="radio" aria-checked="<?php echo $name === 'Black' ? 'true' : 'false'; ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                                <p class="swatch-label">Size, angle and position</p>
                                <p class="field-note" style="margin: 0 0 .75rem;">Tap the text on the case: drag it to move, pull a corner to resize, use the round handle to rotate.</p>
                                <label class="slider-field"><span>Size <output id="text-size-out">100%</output></span>
                                    <input type="range" id="text-size" min="30" max="300" value="100"></label>
                                <label class="slider-field" style="margin-top: .75rem;"><span>Angle <output id="text-rot-out">0°</output></span>
                                    <input type="range" id="text-rot" min="-180" max="180" value="0"></label>
                                <div class="text-position" style="margin-top: 1rem;">
                                    <div class="nudge-pad" role="group" aria-label="Move text">
                                        <button type="button" class="nudge-btn nudge-up" data-nudge="0,-1" aria-label="Move text up"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg></button>
                                        <button type="button" class="nudge-btn nudge-left" data-nudge="-1,0" aria-label="Move text left"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></button>
                                        <button type="button" class="nudge-btn nudge-right" data-nudge="1,0" aria-label="Move text right"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button>
                                        <button type="button" class="nudge-btn nudge-down" data-nudge="0,1" aria-label="Move text down"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>
                                    </div>
                                    <div>
                                        <p class="field-note" style="margin:0 0 .5rem;">Move the text a little at a time.</p>
                                        <button type="button" class="text-link small" id="text-reset">Reset size, angle and position</button>
                                    </div>
                                </div>

                            <?php else: ?>
                                <div class="pick-grid">
                                    <?php foreach (['Vertical', 'Centre', 'Bottom', 'Corner'] as $tl): ?>
                                        <button type="button" class="pick <?php echo $tl === 'Vertical' ? 'is-on' : ''; ?>" data-text-layout="<?php echo strtolower($tl); ?>"><?php echo $tl; ?></button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>

            <div class="step-nav">
                <button type="button" class="step-arrow" id="step-prev" aria-label="Previous step">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <span id="step-count">01/08</span>
                <button type="button" class="step-arrow" id="step-next" aria-label="Next step">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>
        </div>
    </aside>
</div>

<script src="<?php echo $ROOT_DIR; ?>/js/case-mockup.js?v=<?php echo filemtime(__DIR__ . '/../js/case-mockup.js'); ?>"></script>
<script src="<?php echo $ROOT_DIR; ?>/js/design.js?v=<?php echo filemtime(__DIR__ . '/../js/design.js'); ?>"></script>
</body>
</html>
