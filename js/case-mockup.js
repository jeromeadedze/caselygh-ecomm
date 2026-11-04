// Draws a Casely case design onto a phone model as an SVG (100 x 205 units).
// The camera layout is picked from the model name, so new models of a known
// family (e.g. "iPhone 18 Pro") get a sensible shape automatically.
// Flat shading only: the case gets a raised rim and a lip around the camera,
// the camera gets metal-ringed lenses, all from solid colours.
(function () {
  const NS = 'http://www.w3.org/2000/svg';
  let uid = 0;

  // Which camera layout a model uses.
  function cameraFor(name) {
    const n = (name || '').toLowerCase();
    if (/iphone\s*1[7-9]\s*pro/.test(n)) return 'plateau';   // full-width camera bar
    if (/iphone.*pro/.test(n)) return 'pro';                  // square bump, 3 lenses
    if (/iphone/.test(n)) return 'dual';                      // square bump, 2 lenses
    if (/galaxy\s*a/.test(n)) return 'galaxy-a';              // 3 lenses, black rings
    if (/galaxy|samsung/.test(n)) return 'galaxy';            // 3 floating lenses
    return 'pro';
  }

  const GLASS = '#d7d4da';      // frosted camera glass
  const METAL = '#b9b7bd';      // lens rings
  const LIP = 'rgba(0,0,0,.13)'; // the case's raised edge around the camera opening
  const HIGHLIGHT = 'rgba(255,255,255,.55)';

  // One lens: metal ring, black barrel, blue glass, a small catch-light.
  const lens = (cx, cy, r, ring = METAL) =>
    `<circle cx="${cx}" cy="${cy}" r="${r + Math.max(1.1, r * 0.17)}" fill="${ring}"/>` +
    `<circle cx="${cx}" cy="${cy}" r="${r + 0.35}" fill="#0b0b0e"/>` +
    `<circle cx="${cx}" cy="${cy}" r="${r * 0.62}" fill="#1b2235"/>` +
    `<circle cx="${cx}" cy="${cy}" r="${r * 0.3}" fill="#0b0b0e"/>` +
    `<circle cx="${cx - r * 0.32}" cy="${cy - r * 0.34}" r="${r * 0.14}" fill="#fff" fill-opacity=".55"/>`;
  const flash = (cx, cy, r) => `<circle cx="${cx}" cy="${cy}" r="${r}" fill="#f4efe2"/><circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="rgba(0,0,0,.15)" stroke-width=".3"/>`;
  const sensor = (cx, cy, r) => `<circle cx="${cx}" cy="${cy}" r="${r}" fill="#1d1d22"/>`;

  // Camera opening: case lip (darker band), then the phone's glass module with a thin highlight.
  const module = (x, y, w, h, rx) =>
    `<rect x="${x - 1.8}" y="${y - 1.8}" width="${w + 3.6}" height="${h + 3.6}" rx="${rx + 1.8}" fill="${LIP}"/>` +
    `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${rx}" fill="${GLASS}"/>` +
    `<rect x="${x + 0.4}" y="${y + 0.4}" width="${w - 0.8}" height="${h - 0.8}" rx="${rx - 0.4}" fill="none" stroke="${HIGHLIGHT}" stroke-width=".5"/>`;
  const hole = (cx, cy, r) => `<circle cx="${cx}" cy="${cy}" r="${r}" fill="${LIP}"/>`;

  // Sizes follow the real phones: the phone is 100 units wide (≈1.4 units per mm).
  //   iPhone 13/14/15   ~27 mm square bump, two ~12 mm lenses on the diagonal
  //   iPhone 15/16 Pro  ~36 mm square bump, three ~14.5 mm lenses in a triangle
  //   iPhone 17 Pro     edge-to-edge camera bar ~35 mm tall, same three lenses
  //   Galaxy S/A        three ~12 mm lenses in a column, no bump
  const CAMERAS = {
    dual: () =>
      module(5, 5, 38, 38, 10.5) +
      lens(15.6, 15.6, 6.6) + lens(32.4, 32.4, 6.6) +
      flash(33.2, 14.6, 2.3) + sensor(14.8, 33.2, 0.9),
    pro: () =>
      module(4.5, 4.5, 51, 51, 13.5) +
      lens(16.2, 16.2, 8.4) + lens(16.2, 43.8, 8.4) + lens(40.6, 30, 8.4) +
      flash(41.4, 11.4, 2.6) + sensor(41.6, 48.4, 2.4) + sensor(29, 30, 0.9),
    plateau: () =>
      module(3, 3, 94, 49, 13.5) +
      lens(15.6, 15.8, 8.4) + lens(15.6, 40.2, 8.4) + lens(38.6, 28, 8.4) +
      flash(85, 16.5, 2.7) + sensor(85, 39.5, 2.5) + sensor(72, 28, 0.9),
    galaxy: () =>
      hole(16, 15.5, 9.6) + hole(16, 35.5, 9.6) + hole(16, 55.5, 9.6) +
      lens(16, 15.5, 6.9) + lens(16, 35.5, 6.9) + lens(16, 55.5, 6.9) + flash(31.5, 15.5, 2),
    'galaxy-a': () =>
      hole(16, 15.5, 10) + hole(16, 35, 10) + hole(16, 54.5, 10) +
      '<circle cx="16" cy="15.5" r="8.6" fill="#232327"/><circle cx="16" cy="35" r="8.6" fill="#232327"/><circle cx="16" cy="54.5" r="8.6" fill="#232327"/>' +
      lens(16, 15.5, 6.2, '#3a3a40') + lens(16, 35, 6.2, '#3a3a40') + lens(16, 54.5, 6.2, '#3a3a40') + flash(31.5, 15.5, 2),
  };

  function patternDef(id, d) {
    const ink = d.patternInk || '#ffffff';
    if (d.pattern === 'gingham')
      return `<pattern id="${id}" width="11" height="11" patternUnits="userSpaceOnUse"><rect width="5.5" height="11" fill="${ink}" fill-opacity=".38"/><rect width="11" height="5.5" fill="${ink}" fill-opacity=".38"/></pattern>`;
    if (d.pattern === 'stripes')
      return `<pattern id="${id}" width="11" height="11" patternUnits="userSpaceOnUse"><rect width="5.5" height="11" fill="${ink}" fill-opacity=".5"/></pattern>`;
    if (d.pattern === 'dots')
      return `<pattern id="${id}" width="10" height="10" patternUnits="userSpaceOnUse"><circle cx="2.5" cy="2.5" r="1.2" fill="${ink}"/><circle cx="7.5" cy="7.5" r="1.2" fill="${ink}"/></pattern>`;
    return '';
  }

  const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

  function render(design, modelName) {
    const id = 'cm' + ++uid;
    const pat = patternDef(id + 'p', design);
    const [txt, font, color, size, rot, x, y] = design.text || [];
    const heavy = /archivo/i.test(font || '');
    const textSvg = txt
      ? `<text x="${x}" y="${y}" text-anchor="middle" dominant-baseline="central" transform="rotate(${rot} ${x} ${y})" fill="${esc(color)}" font-family="${esc(font)}" font-weight="${heavy ? 900 : 400}" font-size="${size}"${heavy ? ' letter-spacing=".5"' : ''}>${esc(txt)}</text>`
      : '';
    // A small hand-drawn heart after the name.
    const charm = design.charm === 'heart' && txt
      ? `<path transform="translate(${x + size * 0.95} ${y + size * 0.42}) rotate(${rot}) scale(${size / 34})" d="M0 6.5C-3.8 3.4-6.6 1-6.6-1.9a3.3 3.3 0 0 1 6.6-.9 3.3 3.3 0 0 1 6.6.9c0 2.9-2.8 5.3-6.6 8.4z" fill="none" stroke="${esc(color)}" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"/>`
      : '';
    const camera = (CAMERAS[cameraFor(modelName)] || CAMERAS.pro)();
    const button = (bx, by, h) =>
      `<rect x="${bx}" y="${by}" width="2.2" height="${h}" rx="1.1" fill="${esc(design.bg)}"/>` +
      `<rect x="${bx}" y="${by}" width="2.2" height="${h}" rx="1.1" fill="rgba(0,0,0,.16)"/>`;

    return `<svg viewBox="-3 -2 106 209" xmlns="${NS}" focusable="false" aria-hidden="true">
  <defs><clipPath id="${id}c"><rect width="100" height="205" rx="15.5"/></clipPath>${pat}</defs>
  ${button(99.4, 42, 24)}${button(-1.6, 34, 13)}${button(-1.6, 52, 13)}
  <g clip-path="url(#${id}c)">
    <rect width="100" height="205" fill="${esc(design.bg)}"/>
    ${pat ? `<rect width="100" height="205" fill="url(#${id}p)"/>` : ''}
    ${textSvg}${charm}
    ${camera}
  </g>
  <rect x=".9" y=".9" width="98.2" height="203.2" rx="14.6" fill="none" stroke="rgba(0,0,0,.09)" stroke-width="1.8"/>
  <rect x="2.2" y="2.2" width="95.6" height="200.6" rx="13.4" fill="none" stroke="${HIGHLIGHT}" stroke-width=".5" stroke-opacity=".7"/>
</svg>`;
  }

  // Flat artwork of a design (no phone, no camera), e.g. to place in a photo mockup.
  function artwork(design) {
    const id = 'ca' + ++uid;
    const pat = patternDef(id + 'p', design);
    const [txt, font, color, size, rot, x, y] = design.text || [];
    const heavy = /archivo/i.test(font || '');
    const textSvg = txt
      ? `<text x="${x}" y="${y}" text-anchor="middle" dominant-baseline="central" transform="rotate(${rot} ${x} ${y})" fill="${esc(color)}" font-family="${esc(font)}" font-weight="${heavy ? 900 : 400}" font-size="${size}"${heavy ? ' letter-spacing=".5"' : ''}>${esc(txt)}</text>`
      : '';
    const charm = design.charm === 'heart' && txt
      ? `<path transform="translate(${x + size * 0.95} ${y + size * 0.42}) rotate(${rot}) scale(${size / 34})" d="M0 6.5C-3.8 3.4-6.6 1-6.6-1.9a3.3 3.3 0 0 1 6.6-.9 3.3 3.3 0 0 1 6.6.9c0 2.9-2.8 5.3-6.6 8.4z" fill="none" stroke="${esc(color)}" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"/>`
      : '';
    return `<svg viewBox="0 0 100 205" xmlns="${NS}"><defs>${pat}</defs><rect width="100" height="205" fill="${esc(design.bg)}"/>${pat ? `<rect width="100" height="205" fill="url(#${id}p)"/>` : ''}${textSvg}${charm}</svg>`;
  }

  // Just the camera of a model (no case), drawn over another preview such as the studio.
  // The viewBox matches a 100-unit-wide phone; the camera sits in its top-left area.
  function camera(modelName, height = 211) {
    return `<svg viewBox="0 0 100 ${height}" preserveAspectRatio="xMidYMin meet" xmlns="${NS}" focusable="false" aria-hidden="true">${(CAMERAS[cameraFor(modelName)] || CAMERAS.pro)()}</svg>`;
  }

  window.CaselyMockup = { render, cameraFor, artwork, camera };
})();
