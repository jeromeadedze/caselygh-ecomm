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
$caseDesigns = [];

function casely_case_design(string $slug): ?array
{
    global $caseDesigns;
    return $caseDesigns[$slug] ?? null;
}
