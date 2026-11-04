<?php
// Home page hero: a ring of personalised Casely cases, drawn in code (no photo).
//
// EDIT THE CASES HERE. Each entry is one case in the ring, clockwise from the top.
//   name    - text printed on the case (a name or initials)
//   bg      - case colour
//   ink     - colour of the name, charm and pattern details
//   pattern - solid | stripes | gingham | dots | floral
//   charm   - heart | bow | none
// Any number of cases works; 12 to 18 looks best.
$heroCases = [
    ['name' => 'Olivia', 'bg' => '#f7bfd0', 'ink' => '#d6336c', 'pattern' => 'solid',   'charm' => 'heart'],
    ['name' => 'E M',    'bg' => '#d9cff5', 'ink' => '#6f4fc4', 'pattern' => 'gingham', 'charm' => 'heart'],
    ['name' => 'Sophie', 'bg' => '#fbee9c', 'ink' => '#c99a06', 'pattern' => 'solid',   'charm' => 'heart'],
    ['name' => 'A B',    'bg' => '#bfe8cf', 'ink' => '#2f9e60', 'pattern' => 'solid',   'charm' => 'heart'],
    ['name' => 'Luna',   'bg' => '#bcdcf7', 'ink' => '#2f6fc4', 'pattern' => 'gingham', 'charm' => 'heart'],
    ['name' => 'Coco',   'bg' => '#ffd1ad', 'ink' => '#e2741d', 'pattern' => 'stripes', 'charm' => 'none'],
    ['name' => 'Fiona',  'bg' => '#7a3c6b', 'ink' => '#f4d9ea', 'pattern' => 'solid',   'charm' => 'heart'],
    ['name' => 'Hannah', 'bg' => '#fbf3ea', 'ink' => '#e0577f', 'pattern' => 'dots',    'charm' => 'heart'],
    ['name' => 'T R',    'bg' => '#a9d8f7', 'ink' => '#2468b8', 'pattern' => 'solid',   'charm' => 'heart'],
    ['name' => 'Amelia', 'bg' => '#d9cff5', 'ink' => '#7a52c9', 'pattern' => 'gingham', 'charm' => 'bow'],
    ['name' => 'C C',    'bg' => '#fbd0dc', 'ink' => '#d6336c', 'pattern' => 'dots',    'charm' => 'heart'],
    ['name' => 'Ada',    'bg' => '#d3f0e1', 'ink' => '#2f9e60', 'pattern' => 'stripes', 'charm' => 'heart'],
    ['name' => 'Emily',  'bg' => '#cfc4f2', 'ink' => '#6f4fc4', 'pattern' => 'solid',   'charm' => 'heart'],
    ['name' => 'Rose',   'bg' => '#f6f1e6', 'ink' => '#8a6cc9', 'pattern' => 'floral',  'charm' => 'heart'],
    ['name' => 'J S',    'bg' => '#ffbf8f', 'ink' => '#d9600f', 'pattern' => 'solid',   'charm' => 'heart'],
    ['name' => 'Chloe',  'bg' => '#fbd6e1', 'ink' => '#e0457b', 'pattern' => 'stripes', 'charm' => 'bow'],
];

// Draws one case as an SVG (90 x 180 units). Top of the SVG = camera end.
function casely_case_svg(array $c, int $i): string
{
    $id = 'hc' . $i;
    $bg = e($c['bg']);
    $ink = e($c['ink']);
    $w = '#ffffff';

    $patterns = [
        'stripes' => "<pattern id=\"{$id}p\" width=\"16\" height=\"16\" patternUnits=\"userSpaceOnUse\"><rect width=\"8\" height=\"16\" fill=\"{$w}\" fill-opacity=\".6\"/></pattern>",
        'gingham' => "<pattern id=\"{$id}p\" width=\"14\" height=\"14\" patternUnits=\"userSpaceOnUse\"><rect width=\"7\" height=\"14\" fill=\"{$w}\" fill-opacity=\".45\"/><rect width=\"14\" height=\"7\" fill=\"{$w}\" fill-opacity=\".45\"/></pattern>",
        'dots'    => "<pattern id=\"{$id}p\" width=\"14\" height=\"14\" patternUnits=\"userSpaceOnUse\"><circle cx=\"3.5\" cy=\"3.5\" r=\"1.8\" fill=\"{$ink}\" fill-opacity=\".7\"/><circle cx=\"10.5\" cy=\"10.5\" r=\"1.8\" fill=\"{$ink}\" fill-opacity=\".7\"/></pattern>",
        'floral'  => "<pattern id=\"{$id}p\" width=\"20\" height=\"20\" patternUnits=\"userSpaceOnUse\"><g fill=\"{$ink}\" fill-opacity=\".45\"><circle cx=\"5\" cy=\"5\" r=\"1.6\"/><circle cx=\"8\" cy=\"5\" r=\"1.6\"/><circle cx=\"6.5\" cy=\"7.5\" r=\"1.6\"/><circle cx=\"15\" cy=\"15\" r=\"1.6\"/><circle cx=\"18\" cy=\"15\" r=\"1.6\"/><circle cx=\"16.5\" cy=\"17.5\" r=\"1.6\"/></g></pattern>",
    ];
    $pattern = $patterns[$c['pattern']] ?? '';
    $overlay = $pattern ? "<rect x=\"3\" y=\"3\" width=\"84\" height=\"174\" rx=\"17\" fill=\"url(#{$id}p)\"/>" : '';

    $charms = [
        'heart' => "<path d=\"M45 128c-4-3.5-7-6-7-9a3.5 3.5 0 0 1 7-1 3.5 3.5 0 0 1 7 1c0 3-3 5.5-7 9z\" fill=\"none\" stroke=\"{$ink}\" stroke-width=\"1.6\" stroke-linejoin=\"round\"/>",
        'bow'   => "<path d=\"M45 120l-8-5v10zM45 120l8-5v10zM45 120l-4 9M45 120l4 9\" fill=\"none\" stroke=\"{$ink}\" stroke-width=\"1.6\" stroke-linejoin=\"round\" stroke-linecap=\"round\"/>",
    ];
    $charm = $charms[$c['charm']] ?? '';
    $name = e($c['name']);

    return <<<SVG
<svg viewBox="0 0 90 180" aria-hidden="true" focusable="false">
  <defs>{$pattern}</defs>
  <rect x="1" y="1" width="88" height="178" rx="19" fill="{$bg}"/>
  {$overlay}
  <rect x="1.5" y="1.5" width="87" height="177" rx="18.5" fill="none" stroke="#000" stroke-opacity=".08"/>
  <rect x="8" y="8" width="34" height="34" rx="10" fill="{$bg}" stroke="#000" stroke-opacity=".12"/>
  <circle cx="18" cy="18" r="6.5" fill="#1b1418"/><circle cx="18" cy="32" r="6.5" fill="#1b1418"/><circle cx="32" cy="25" r="4" fill="#1b1418" fill-opacity=".85"/>
  <text x="45" y="104" text-anchor="middle" transform="rotate(-14 45 100)" fill="{$ink}" font-family="Caveat, cursive" font-weight="700" font-size="21">{$name}</text>
  {$charm}
</svg>
SVG;
}
?>
<div class="hero-ring" role="img" aria-label="Ring of pastel phone cases, each personalised with a name or initials">
    <div class="hero-ring-spin" data-hero-ring>
        <?php foreach ($heroCases as $i => $case): ?>
            <div class="hero-slot" style="--i: <?php echo $i; ?>; --n: <?php echo count($heroCases); ?>;">
                <div class="hero-case"><?php echo casely_case_svg($case, $i); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
