<?php
// Case designs for the "On your phone" preview on the product page.
// js/main.js draws each design onto the chosen phone model (camera layout
// changes per model), so one entry here covers every phone.
//
// EDIT HERE, keyed by product slug. A product with no entry just shows its photo.
//   bg        - case colour
//   backdrop  - colour behind the phone in the preview
//   pattern   - solid | gingham | stripes | dots    (patternInk = colour of the pattern)
//   text      - optional: [content, font, colour, size, rotate (deg), x, y]
//               size/x/y are in a 100 x 205 phone; x/y is the centre of the text
//   charm     - optional: heart | none (drawn under the text)
$lilacGingham = [
    'bg' => '#ece4fa', 'backdrop' => '#ddd3f2', 'pattern' => 'gingham', 'patternInk' => '#b49fe6',
    'text' => ['Sophie', "'Great Vibes', cursive", '#7a5cc4', 21, -12, 48, 112], 'charm' => 'heart',
];
$pinkInitial = [
    'bg' => '#f3a9c1', 'backdrop' => '#f8d4df', 'pattern' => 'solid',
    'text' => ['G', "'Great Vibes', cursive", '#ffffff', 58, -6, 50, 112], 'charm' => 'none',
];

$caseDesigns = [
    'lilac-everyday' => $lilacGingham,
    'lilac-gingham-name' => $lilacGingham,
    'pink-everyday' => $pinkInitial,
    'pink-script-initial' => $pinkInitial,
    'butter-bold-name' => [
        'bg' => '#f6e68e', 'backdrop' => '#f6f0d8', 'pattern' => 'solid',
        'text' => ['OLIVIA', "Archivo, sans-serif", '#1d5fd6', 21, -90, 64, 128], 'charm' => 'none',
    ],
    'sky-gingham-tough' => [
        'bg' => '#e3effd', 'backdrop' => '#d6e8fb', 'pattern' => 'gingham', 'patternInk' => '#7fb2ef',
        'charm' => 'none',
    ],
];

function casely_case_design(string $slug): ?array
{
    global $caseDesigns;
    return $caseDesigns[$slug] ?? null;
}
