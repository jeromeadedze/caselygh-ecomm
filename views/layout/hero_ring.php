<?php
// Home page hero: a ring of personalised Casely cases, drawn in code (no photo).
//
// EDIT THE CASES HERE. Each entry is one case in the ring, clockwise from the top.
//   name    - text printed on the case (a name or initials)
//   bg      - case colour
//   ink     - colour of the name, charm and pattern details
//   pattern - solid | stripes | gingham | dots | floral
//   charm   - heart | bow | none
//   camera  - dual (iPhone) | pro (iPhone Pro) | bar (iPhone 17 Pro) | air (iPhone Air) | galaxy (Samsung)
// Any number of cases works; 12 to 18 looks best.
$heroCases = [
    ['name' => 'Olivia', 'bg' => '#f7bfd0', 'ink' => '#d6336c', 'pattern' => 'solid',   'charm' => 'heart', 'camera' => 'pro'],
    ['name' => 'E M',    'bg' => '#d9cff5', 'ink' => '#6f4fc4', 'pattern' => 'gingham', 'charm' => 'heart', 'camera' => 'dual'],
    ['name' => 'Sophie', 'bg' => '#fbee9c', 'ink' => '#c99a06', 'pattern' => 'solid',   'charm' => 'heart', 'camera' => 'air'],
    ['name' => 'A B',    'bg' => '#bfe8cf', 'ink' => '#2f9e60', 'pattern' => 'solid',   'charm' => 'heart', 'camera' => 'galaxy'],
    ['name' => 'Luna',   'bg' => '#bcdcf7', 'ink' => '#2f6fc4', 'pattern' => 'gingham', 'charm' => 'heart', 'camera' => 'bar'],
    ['name' => 'Coco',   'bg' => '#ffd1ad', 'ink' => '#e2741d', 'pattern' => 'stripes', 'charm' => 'none', 'camera' => 'dual'],
    ['name' => 'Fiona',  'bg' => '#7a3c6b', 'ink' => '#f4d9ea', 'pattern' => 'solid',   'charm' => 'heart', 'camera' => 'pro'],
    ['name' => 'Hannah', 'bg' => '#fbf3ea', 'ink' => '#e0577f', 'pattern' => 'dots',    'charm' => 'heart', 'camera' => 'galaxy'],
    ['name' => 'T R',    'bg' => '#a9d8f7', 'ink' => '#2468b8', 'pattern' => 'solid',   'charm' => 'heart', 'camera' => 'air'],
    ['name' => 'Amelia', 'bg' => '#d9cff5', 'ink' => '#7a52c9', 'pattern' => 'gingham', 'charm' => 'bow', 'camera' => 'bar'],
    ['name' => 'C C',    'bg' => '#fbd0dc', 'ink' => '#d6336c', 'pattern' => 'dots',    'charm' => 'heart', 'camera' => 'dual'],
    ['name' => 'Ada',    'bg' => '#d3f0e1', 'ink' => '#2f9e60', 'pattern' => 'stripes', 'charm' => 'heart', 'camera' => 'galaxy'],
    ['name' => 'Emily',  'bg' => '#cfc4f2', 'ink' => '#6f4fc4', 'pattern' => 'solid',   'charm' => 'heart', 'camera' => 'pro'],
    ['name' => 'Rose',   'bg' => '#f6f1e6', 'ink' => '#8a6cc9', 'pattern' => 'floral',  'charm' => 'heart', 'camera' => 'air'],
    ['name' => 'J S',    'bg' => '#ffbf8f', 'ink' => '#d9600f', 'pattern' => 'solid',   'charm' => 'heart', 'camera' => 'bar'],
    ['name' => 'Chloe',  'bg' => '#fbd6e1', 'ink' => '#e0457b', 'pattern' => 'stripes', 'charm' => 'bow', 'camera' => 'dual'],
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

    // Samsung bodies are squarer than iPhones.
    $cameraType = $c['camera'] ?? 'dual';
    $rx = $cameraType === 'galaxy' ? 13 : 19;
    $patternRx = $rx - 2;
    $edgeRx = $rx - 0.5;
    $lens = fn($x, $y, $r) => "<circle cx=\"{$x}\" cy=\"{$y}\" r=\"{$r}\" fill=\"#1b1418\"/>";
    $flash = fn($x, $y) => "<circle cx=\"{$x}\" cy=\"{$y}\" r=\"2\" fill=\"#f7f1df\" stroke=\"#000\" stroke-opacity=\".15\" stroke-width=\".5\"/>";
    $plate = fn($x, $y, $w, $h, $r) => "<rect x=\"{$x}\" y=\"{$y}\" width=\"{$w}\" height=\"{$h}\" rx=\"{$r}\" fill=\"{$bg}\" stroke=\"#000\" stroke-opacity=\".12\"/>";
    $cameras = [
        'dual' => $plate(8, 8, 28, 28, 9) . $lens(16, 16, 5) . $lens(28, 28, 5) . $flash(29, 15),
        'pro' => $plate(7, 7, 38, 38, 11) . $lens(16, 16, 6.5) . $lens(16, 36, 6.5) . $lens(34, 26, 6.5) . $flash(35, 13),
        'bar' => $plate(5, 5, 80, 38, 11) . $lens(15, 15, 6.5) . $lens(15, 33, 6.5) . $lens(32, 24, 6.5) . $flash(75, 15),
        'air' => $plate(5, 5, 80, 22, 11) . $lens(16, 16, 6.5) . $flash(74, 16),
        'galaxy' => $lens(16, 15, 6) . $lens(16, 31, 6) . $lens(16, 47, 6) . $flash(29, 15),
    ];
    $camera = $cameras[$cameraType] ?? $cameras['dual'];
    $overlay = $pattern ? "<rect x=\"3\" y=\"3\" width=\"84\" height=\"174\" rx=\"{$patternRx}\" fill=\"url(#{$id}p)\"/>" : '';

    $charms = [
        'heart' => "<path d=\"M45 128c-4-3.5-7-6-7-9a3.5 3.5 0 0 1 7-1 3.5 3.5 0 0 1 7 1c0 3-3 5.5-7 9z\" fill=\"none\" stroke=\"{$ink}\" stroke-width=\"1.6\" stroke-linejoin=\"round\"/>",
        'bow'   => "<path d=\"M45 120l-8-5v10zM45 120l8-5v10zM45 120l-4 9M45 120l4 9\" fill=\"none\" stroke=\"{$ink}\" stroke-width=\"1.6\" stroke-linejoin=\"round\" stroke-linecap=\"round\"/>",
    ];
    $charm = $charms[$c['charm']] ?? '';
    $name = e($c['name']);

    return <<<SVG
<svg viewBox="0 0 90 180" aria-hidden="true" focusable="false">
  <defs>{$pattern}</defs>
  <rect x="1" y="1" width="88" height="178" rx="{$rx}" fill="{$bg}"/>
  {$overlay}
  <rect x="1.5" y="1.5" width="87" height="177" rx="{$edgeRx}" fill="none" stroke="#000" stroke-opacity=".08"/>
  {$camera}
  <text x="45" y="104" text-anchor="middle" transform="rotate(-14 45 100)" fill="{$ink}" font-family="Caveat, cursive" font-weight="700" font-size="21">{$name}</text>
  {$charm}
</svg>
SVG;
}
?>
<div class="hero-ring" role="img" aria-label="Ring of pastel cases for different phones, each personalised with a name or initials">
    <div class="hero-ring-spin" data-hero-ring>
        <?php foreach ($heroCases as $i => $case): ?>
            <div class="hero-slot" style="--i: <?php echo $i; ?>; --n: <?php echo count($heroCases); ?>;">
                <div class="hero-case"><?php echo casely_case_svg($case, $i); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="hero-ring-logo" aria-hidden="true"><?php include __DIR__ . '/logo_mark.php'; ?></div>
    <!-- Larger screens: the Casely C wraps around the headline, between it and the cases. -->
    <svg class="hero-ring-c" viewBox="0 0 200 200" aria-hidden="true" focusable="false"><path d="M181.77 45.26A98.4 98.4 0 1 0 181.77 154.74M176.95 48.49A92.6 92.6 0 1 0 176.95 151.51M172.13 51.71A86.8 86.8 0 1 0 172.13 148.29M167.31 54.94A81.0 81.0 0 1 0 167.31 145.06" fill="none" stroke="currentColor" stroke-width="3.2"/></svg>
</div>
