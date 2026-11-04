// Custom case studio.
// Keeps the customer's choices in `state`, updates the live preview, and on
// "Add" draws the design onto a canvas to produce a print-ready PNG that is
// uploaded (with the main photo) to actions/save_design_action.php.
(function () {
  const studio = document.getElementById('studio');
  if (!studio) return;

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const BASE = parseFloat(studio.dataset.basePrice);
  const TOUGH_EXTRA = parseFloat(studio.dataset.toughExtra);
  const MAX_PHOTOS = parseInt(studio.dataset.maxPhotos, 10) || 36;

  // Each filter is a list of [css function, full-strength value, neutral value, unit].
  // The strength slider mixes between neutral (0%) and full strength (100%).
  const FILTERS = {
    None: [],
    Mono: [['grayscale', 1, 0], ['contrast', 1.1, 1]],
    Warm: [['sepia', 0.35, 0], ['saturate', 1.3, 1]],
    Pastel: [['saturate', 0.6, 1], ['brightness', 1.15, 1]],
    Vintage: [['sepia', 0.6, 0], ['contrast', 0.9, 1], ['brightness', 1.05, 1]],
    Pop: [['saturate', 1.8, 1], ['contrast', 1.15, 1], ['hue-rotate', 12, 0, 'deg']],
    Film: [['contrast', 1.25, 1], ['saturate', 1.2, 1], ['brightness', 1.08, 1], ['sepia', 0.15, 0]],
  };
  const COLLAGES = { 'grid-2x6': [2, 6], 'grid-3x7': [3, 7], 'grid-4x9': [4, 9] };
  const STEP = { device: 0, type: 1, photo: 2, layout: 3, filters: 4, fonts: 5, text: 6, textLayout: 7 };
  const STEP_COUNT = $$('.step').length;
  const DEFAULT_STRENGTH = 80;

  const state = {
    step: 0,
    brand: $('[data-brand].is-on')?.dataset.brand || '',
    modelId: null,
    modelName: '',
    caseType: 'Standard',
    photos: [], // { file, url, hue, sat } — photos[0] is the main photo
    rot: 0,
    zoom: 100,
    pos: { x: 0, y: 0 },
    textPos: { x: 0, y: 0 }, // nudge from the text layout's spot, in preview pixels
    textScale: 1,            // on top of the layout's own size
    textRot: 0,              // degrees, on top of the layout's own angle
    textSelected: false,     // shows the move / resize / rotate box
    layout: 'full',
    filter: 'None',
    strength: DEFAULT_STRENGTH,
    font: "Georgia, 'Times New Roman', serif",
    text: 'CS',
    ink: '#FFFFFF',
    bg: '#1A1418',
    textLayout: 'vertical',
    consent: false,
    saving: false,
  };

  const preview = $('#case-preview');
  const photoBox = $('#photo-box');
  const photo = $('#photo');
  const collage = $('#collage');
  const caseText = $('#case-text');
  const errorBox = $('#studio-error');
  // The layout places #case-text; the customer's move / resize / rotate go on the inner span,
  // so they always turn and grow around the text's own centre.
  const textInner = $('#case-text-inner');
  const textBox = $('#text-box');
  const sizeIn = $('#text-size');
  const rotIn = $('#text-rot');
  function baseAngle() { return state.textLayout === 'vertical' ? 90 : 0; }

  // ---------- helpers ----------
  const ghs = (n) => 'GH₵' + (Number.isInteger(n) ? n : n.toFixed(2));
  const price = () => BASE + (state.caseType === 'Tough' ? TOUGH_EXTRA : 0);
  const hasPhotos = () => state.photos.length > 0;
  const isCollage = () => state.layout in COLLAGES;
  const hasText = () => state.font !== 'none' && state.text.trim() !== '';

  function filterCss() {
    const k = state.strength / 100;
    const parts = (FILTERS[state.filter] || []).map(([fn, full, neutral, unit = '']) =>
      fn + '(' + +(neutral + (full - neutral) * k).toFixed(3) + unit + ')');
    return parts.length ? parts.join(' ') : 'none';
  }

  // Mark one button in a group as chosen.
  function choose(buttons, chosen) {
    buttons.forEach((b) => {
      const on = b === chosen;
      b.classList.toggle('is-on', on);
      if (b.hasAttribute('role')) b.setAttribute('aria-checked', on ? 'true' : 'false');
    });
  }

  function showError(message) {
    errorBox.textContent = message;
    errorBox.hidden = !message;
  }

  // ---------- steps (accordion) ----------
  // Returns why a step is locked, or '' when it can be opened.
  function lockedReason(i) {
    if (i === STEP.layout && !hasPhotos()) return 'Add a photo first';
    if (i === STEP.filters && !hasPhotos()) return 'Add a photo first';
    if ((i === STEP.text || i === STEP.textLayout) && state.font === 'none') return 'Pick a font first';
    return '';
  }

  function openStep(i) {
    state.step = i;
    $$('.step').forEach((section, idx) => {
      const head = $('.step-head', section);
      const body = $('.step-body', section);
      const reason = lockedReason(idx);
      head.disabled = !!reason;
      section.classList.toggle('is-disabled', !!reason);
      const hint = $('[data-step-hint]', section);
      if (hint) hint.textContent = reason;
      const open = idx === i && !reason;
      head.setAttribute('aria-expanded', open ? 'true' : 'false');
      body.hidden = !open;
      section.classList.toggle('is-open', open);
      // Bring the opened step into view inside the scrolling steps list.
      if (open && state.ready) head.scrollIntoView({ block: 'nearest' });
      if (open && (idx === STEP.text || idx === STEP.textLayout) && state.ready) state.textSelected = true;
    });
    const shown = Math.max(i, 0) + 1;
    $('#step-count').textContent = String(shown).padStart(2, '0') + '/' + String(STEP_COUNT).padStart(2, '0');
    $('#step-prev').disabled = i <= 0;
    $('#step-next').disabled = i >= STEP_COUNT - 1;
  }
  const refreshSteps = () => openStep(state.step);

  function go(direction) {
    let n = state.step + direction;
    while (n > 0 && n < STEP_COUNT - 1 && lockedReason(n)) n += direction;
    n = Math.max(0, Math.min(STEP_COUNT - 1, n));
    if (lockedReason(n)) n = state.step;
    openStep(n);
  }

  $$('.step-head').forEach((head, i) => {
    head.addEventListener('click', () => openStep(state.step === i ? -1 : i));
  });
  $('#step-prev').addEventListener('click', () => go(-1));
  $('#step-next').addEventListener('click', () => go(1));

  // ---------- preview rendering ----------
  let collageKey = '';
  function renderCollage() {
    const [cols, rows] = COLLAGES[state.layout];
    const key = state.layout + '|' + state.photos.map((p) => p.url).join(',');
    if (key === collageKey) return; // only rebuild when photos or grid change
    collageKey = key;
    collage.style.setProperty('--cols', cols);
    collage.style.setProperty('--rows', rows);
    const cells = [];
    for (let i = 0; i < cols * rows; i++) {
      const img = document.createElement('img');
      img.src = state.photos[i % state.photos.length].url;
      img.alt = '';
      img.draggable = false;
      cells.push(img);
    }
    collage.replaceChildren(...cells);
  }

  function render() {
    preview.style.background = state.bg;

    const main = state.photos[0];
    photoBox.hidden = !main;
    photoBox.className = 'case-photo-box layout-' + (isCollage() ? 'full' : state.layout);
    photoBox.style.filter = filterCss();
    photo.hidden = !main || isCollage();
    collage.hidden = !main || !isCollage();
    if (main && isCollage()) {
      renderCollage();
    } else if (main) {
      if (photo.getAttribute('src') !== main.url) photo.src = main.url;
      photo.style.width = state.zoom + '%';
      photo.style.transform =
        'translate(calc(-50% + ' + state.pos.x + 'px), calc(-50% + ' + state.pos.y + 'px)) rotate(' + state.rot + 'deg)';
    }

    textInner.textContent = state.text;
    caseText.hidden = !hasText();
    caseText.className = 'case-text layout-' + state.textLayout;
    caseText.style.fontFamily = state.font;
    caseText.style.color = state.ink;
    // textPos is in screen pixels; turn it into the layout's own (possibly rotated) axes.
    const b = (baseAngle() * Math.PI) / 180;
    const lx = state.textPos.x * Math.cos(b) + state.textPos.y * Math.sin(b);
    const ly = -state.textPos.x * Math.sin(b) + state.textPos.y * Math.cos(b);
    textInner.style.translate = lx + 'px ' + ly + 'px';
    textInner.style.scale = state.textScale;
    textInner.style.rotate = state.textRot + 'deg';

    $('[data-summary-model]').textContent = state.modelName || 'Choose your phone';
    $('[data-summary-type]').textContent = state.caseType + ' case' + (state.filter !== 'None' ? ' · ' + state.filter + ' filter' : '');
    $('[data-summary-price]').textContent = ghs(price());

    const single = main && !isCollage();
    $('#drag-hint').hidden = !single && !hasText();
    $('#drag-hint').textContent = single && hasText() ? 'Drag the photo or the text on the case to move them.'
      : single ? 'Drag the photo on the case to move it.' : 'Drag the text on the case to move it.';
    $('#photo-controls').hidden = !single;
    preview.classList.toggle('is-draggable', !!single);
    caseText.classList.toggle('is-draggable', hasText());
    if (hasText()) $('#drag-hint').textContent = (single ? 'Drag the photo to move it. ' : '') + 'Tap the text to move, resize or rotate it.';
    syncTextControls();
    placeTextBox();
  }

  // The camera on the preview follows the chosen phone (js/case-mockup.js).
  function drawCamera() {
    const box = $('#case-camera');
    if (box && window.CaselyMockup) box.innerHTML = window.CaselyMockup.camera(state.modelName);
  }

  // ---------- step 1: device ----------
  const brandBtns = $$('[data-brand]');
  const modelBtns = $$('[data-model-id]');
  function selectModel(btn) {
    state.modelId = parseInt(btn.dataset.modelId, 10);
    state.modelName = btn.textContent.trim();
    choose(modelBtns, btn);
    drawCamera();
    render();
  }
  brandBtns.forEach((b) =>
    b.addEventListener('click', () => {
      state.brand = b.dataset.brand;
      choose(brandBtns, b);
      $$('[data-models-for]').forEach((g) => (g.hidden = g.dataset.modelsFor !== state.brand));
      const first = $('[data-models-for="' + CSS.escape(state.brand) + '"] [data-model-id]');
      if (first) selectModel(first);
    })
  );
  modelBtns.forEach((b) => b.addEventListener('click', () => selectModel(b)));
  // Start on the newest model of the first brand.
  const startGroup = $$('[data-models-for]:not([hidden]) [data-model-id]');
  if (startGroup.length) selectModel(startGroup[startGroup.length - 1]);

  // ---------- step 2: case type ----------
  const typeBtns = $$('[data-case-type]');
  typeBtns.forEach((b) =>
    b.addEventListener('click', () => {
      state.caseType = b.dataset.caseType;
      choose(typeBtns, b);
      render();
    })
  );

  // ---------- step 3: photos ----------
  const rot = $('#rot');
  const zoom = $('#zoom');
  const list = $('#photo-list');

  function loadImage(url) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.onerror = reject;
      img.src = url;
    });
  }

  // Average colour of a photo, used by "Sort by colour".
  async function measureColour(p) {
    try {
      const img = await loadImage(p.url);
      const c = document.createElement('canvas');
      c.width = c.height = 16;
      const ctx = c.getContext('2d', { willReadFrequently: true });
      ctx.drawImage(img, 0, 0, 16, 16);
      const d = ctx.getImageData(0, 0, 16, 16).data;
      let r = 0, g = 0, b = 0;
      for (let i = 0; i < d.length; i += 4) { r += d[i]; g += d[i + 1]; b += d[i + 2]; }
      const n = d.length / 4;
      [r, g, b] = [r / n / 255, g / n / 255, b / n / 255];
      const max = Math.max(r, g, b), min = Math.min(r, g, b), delta = max - min;
      let hue = 0;
      if (delta) {
        if (max === r) hue = ((g - b) / delta) % 6;
        else if (max === g) hue = (b - r) / delta + 2;
        else hue = (r - g) / delta + 4;
        hue = (hue * 60 + 360) % 360;
      }
      p.hue = hue;
      p.sat = max ? delta / max : 0;
    } catch (_) {
      p.hue = 0;
      p.sat = 0;
    }
  }

  function renderPhotoList() {
    $('#photo-tray').hidden = !hasPhotos();
    $('#photo-count').textContent = state.photos.length;
    $('#photo-sort').hidden = state.photos.length < 2;
    $('[data-upload-label]').textContent = hasPhotos() ? 'Add more photos' : 'Add photos';
    list.replaceChildren(
      ...state.photos.map((p, i) => {
        const li = document.createElement('li');
        li.className = 'photo-item' + (i === 0 ? ' is-main' : '');
        const pick = document.createElement('button');
        pick.type = 'button';
        pick.className = 'photo-pick';
        pick.setAttribute('aria-label', i === 0 ? 'Photo ' + (i + 1) + ', main photo' : 'Make photo ' + (i + 1) + ' the main photo');
        pick.innerHTML = '<img alt="" src="' + p.url + '">';
        pick.addEventListener('click', () => {
          if (i === 0) return;
          state.photos.unshift(state.photos.splice(i, 1)[0]);
          resetPosition();
          photosChanged();
        });
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'photo-remove';
        remove.setAttribute('aria-label', 'Remove photo ' + (i + 1));
        remove.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>';
        remove.addEventListener('click', () => {
          URL.revokeObjectURL(p.url);
          state.photos.splice(i, 1);
          if (i === 0) resetPosition();
          if (!hasPhotos() && isCollage()) setLayout('full');
          photosChanged();
          ($('.photo-pick', list) || $('#photo-input')).focus();
        });
        li.append(pick, remove);
        return li;
      })
    );
  }

  function photosChanged() {
    renderPhotoList();
    refreshSteps();
    render();
  }

  $('#photo-input').addEventListener('change', async (e) => {
    const files = Array.from(e.target.files || []);
    e.target.value = ''; // let the same file be picked again later
    if (!files.length) return;
    const ok = files.filter((f) => /^image\/(jpeg|png|webp)$/.test(f.type));
    const room = MAX_PHOTOS - state.photos.length;
    let message = '';
    if (ok.length < files.length) message = 'Some files were skipped. Use JPG, PNG or WebP photos.';
    if (ok.length > room) message = 'You can add up to ' + MAX_PHOTOS + ' photos, so we kept the first ' + Math.max(room, 0) + '.';
    showError(message);
    const added = ok.slice(0, Math.max(room, 0)).map((file) => ({ file, url: URL.createObjectURL(file), hue: 0, sat: 0 }));
    if (!added.length) return;
    const wasEmpty = !hasPhotos();
    state.photos.push(...added);
    // Several photos at once → start them on a collage that fits.
    if (wasEmpty && state.photos.length > 1 && !isCollage()) {
      setLayout(state.photos.length > 12 ? 'grid-3x7' : 'grid-2x6');
    }
    photosChanged();
    added.forEach(measureColour);
  });

  $('#photo-sort').addEventListener('click', async () => {
    await Promise.all(state.photos.map((p) => (p.hue || p.sat ? null : measureColour(p))));
    // Colourful photos by hue, then greys and near-whites at the end.
    state.photos.sort((a, b) => (b.sat > 0.12) - (a.sat > 0.12) || a.hue - b.hue);
    resetPosition();
    photosChanged();
  });

  function resetPosition() {
    state.pos = { x: 0, y: 0 };
    state.rot = 0;
    state.zoom = 100;
    rot.value = 0;
    zoom.value = 100;
    $('#rot-out').textContent = '0°';
    $('#zoom-out').textContent = '100%';
  }

  rot.addEventListener('input', () => {
    state.rot = parseInt(rot.value, 10);
    $('#rot-out').textContent = state.rot + '°';
    render();
  });
  zoom.addEventListener('input', () => {
    state.zoom = parseInt(zoom.value, 10);
    $('#zoom-out').textContent = state.zoom + '%';
    render();
  });
  $('#photo-reset').addEventListener('click', () => {
    resetPosition();
    render();
  });

  // Drag on the case: the text if you grab the text, otherwise the main photo
  // (single-photo layouts only).
  let drag = null;
  preview.addEventListener('pointerdown', (e) => {
    const onText = hasText() && caseText.contains(e.target);
    if (state.textSelected !== onText) { state.textSelected = onText; render(); }
    if (!onText && (!hasPhotos() || isCollage())) return;
    const from = onText ? state.textPos : state.pos;
    drag = { what: onText ? 'text' : 'photo', x: e.clientX - from.x, y: e.clientY - from.y };
    preview.setPointerCapture(e.pointerId);
    e.preventDefault();
  });
  preview.addEventListener('pointermove', (e) => {
    if (!drag) return;
    const next = { x: e.clientX - drag.x, y: e.clientY - drag.y };
    if (drag.what === 'text') moveText(next);
    else { state.pos = next; render(); }
  });
  ['pointerup', 'pointercancel'].forEach((t) => preview.addEventListener(t, () => (drag = null)));

  $('#consent').addEventListener('change', (e) => {
    state.consent = e.target.checked;
    if (state.consent) showError('');
  });

  // ---------- step 4: layout ----------
  const layoutBtns = $$('[data-layout]');
  function setLayout(key) {
    state.layout = key;
    choose(layoutBtns, layoutBtns.find((b) => b.dataset.layout === key));
  }
  layoutBtns.forEach((b) =>
    b.addEventListener('click', () => {
      setLayout(b.dataset.layout);
      render();
    })
  );

  // ---------- step 5: filters ----------
  const filterBtns = $$('[data-filter]');
  const strength = $('#strength');
  function setStrength(v) {
    state.strength = v;
    strength.value = v;
    $('#strength-out').textContent = v + '%';
  }
  filterBtns.forEach((b) =>
    b.addEventListener('click', () => {
      state.filter = b.dataset.filter;
      choose(filterBtns, b);
      render();
    })
  );
  strength.addEventListener('input', () => {
    setStrength(parseInt(strength.value, 10));
    render();
  });
  $('#filter-reset').addEventListener('click', () => {
    state.filter = 'None';
    choose(filterBtns, filterBtns.find((b) => b.dataset.filter === 'None'));
    setStrength(DEFAULT_STRENGTH);
    render();
  });

  // ---------- steps 6-8: text ----------
  const fontBtns = $$('[data-font]');
  fontBtns.forEach((b) =>
    b.addEventListener('click', () => {
      state.font = b.dataset.font;
      choose(fontBtns, b);
      refreshSteps();
      render();
    })
  );

  const bind = (attr, key) => {
    const btns = $$('[data-' + attr + ']');
    btns.forEach((b) =>
      b.addEventListener('click', () => {
        state[key] = b.dataset[attr.replace(/-([a-z])/g, (_, c) => c.toUpperCase())];
        choose(btns, b);
        render();
      })
    );
  };
  bind('ink', 'ink');
  bind('bg', 'bg');
  bind('text-layout', 'textLayout');
  // A new text layout starts from its own spot.
  $$('[data-text-layout]').forEach((b) => b.addEventListener('click', () => resetText()));

  // Keep the text's centre on the case so it can't be dragged out of reach.
  function moveText(next) {
    const prev = state.textPos;
    state.textPos = next;
    render();
    const c = preview.getBoundingClientRect();
    const t = textInner.getBoundingClientRect();
    const cx = t.left + t.width / 2, cy = t.top + t.height / 2;
    if (cx < c.left || cx > c.right || cy < c.top || cy > c.bottom) {
      state.textPos = prev;
      render();
    }
  }
  const NUDGE = 6;
  $$('[data-nudge]').forEach((b) =>
    b.addEventListener('click', () => {
      const [dx, dy] = b.dataset.nudge.split(',').map(Number);
      moveText({ x: state.textPos.x + dx * NUDGE, y: state.textPos.y + dy * NUDGE });
    })
  );
  // ---------- text box: move, resize, rotate ----------

  function syncTextControls() {
    sizeIn.value = Math.round(state.textScale * 100);
    rotIn.value = Math.round(state.textRot);
    $('#text-size-out').textContent = Math.round(state.textScale * 100) + '%';
    $('#text-rot-out').textContent = Math.round(state.textRot) + '°';
  }

  // Draw the dashed box over the text, turned to the same angle.
  function placeTextBox() {
    if (!hasText() || !state.textSelected) { textBox.hidden = true; return; }
    textBox.hidden = false;
    const stage = textBox.parentElement.getBoundingClientRect();
    const t = textInner.getBoundingClientRect();
    const w = textInner.offsetWidth * state.textScale + 12;
    const h = textInner.offsetHeight * state.textScale + 12;
    const cx = t.left + t.width / 2 - stage.left;
    const cy = t.top + t.height / 2 - stage.top;
    Object.assign(textBox.style, {
      width: w + 'px', height: h + 'px', left: cx - w / 2 + 'px', top: cy - h / 2 + 'px',
      transform: 'rotate(' + (baseAngle() + state.textRot) + 'deg)',
    });
  }

  function textCentre() {
    const t = textInner.getBoundingClientRect();
    return { x: t.left + t.width / 2, y: t.top + t.height / 2 };
  }
  const angleOf = (c, p) => (Math.atan2(p.y - c.y, p.x - c.x) * 180) / Math.PI;
  const wrap180 = (a) => ((((a + 180) % 360) + 360) % 360) - 180;

  let boxDrag = null;
  textBox.addEventListener('pointerdown', (e) => {
    const handle = e.target.closest('[data-handle]');
    const p = { x: e.clientX, y: e.clientY };
    const c = textCentre();
    boxDrag = {
      kind: handle ? handle.dataset.handle : 'move', start: p, centre: c,
      pos: { ...state.textPos }, scale: state.textScale, rot: state.textRot,
      dist: Math.hypot(p.x - c.x, p.y - c.y) || 1, angle: angleOf(c, p),
    };
    textBox.setPointerCapture(e.pointerId);
    textBox.classList.add('is-active');
    e.preventDefault();
  });
  textBox.addEventListener('pointermove', (e) => {
    if (!boxDrag) return;
    const p = { x: e.clientX, y: e.clientY };
    const d = boxDrag;
    if (d.kind === 'move') {
      moveText({ x: d.pos.x + p.x - d.start.x, y: d.pos.y + p.y - d.start.y });
    } else if (d.kind === 'scale') {
      const ratio = Math.hypot(p.x - d.centre.x, p.y - d.centre.y) / d.dist;
      state.textScale = Math.min(3, Math.max(0.3, d.scale * ratio));
      render();
    } else {
      let rot = wrap180(d.rot + angleOf(d.centre, p) - d.angle);
      // Snap to straight angles when close, so level text is easy to get back.
      const total = rot + baseAngle();
      const nearest = Math.round(total / 90) * 90;
      if (Math.abs(total - nearest) < 5) rot = wrap180(nearest - baseAngle());
      state.textRot = rot;
      render();
    }
  });
  ['pointerup', 'pointercancel'].forEach((t) =>
    textBox.addEventListener(t, () => { boxDrag = null; textBox.classList.remove('is-active'); }));

  sizeIn.addEventListener('input', () => { state.textScale = sizeIn.value / 100; render(); });
  rotIn.addEventListener('input', () => { state.textRot = parseInt(rotIn.value, 10); render(); });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && state.textSelected) { state.textSelected = false; render(); }
  });
  window.addEventListener('resize', placeTextBox);

  function resetText() {
    state.textScale = 1;
    state.textRot = 0;
    moveText({ x: 0, y: 0 });
  }
  $('#text-reset').addEventListener('click', resetText);

  const textInput = $('#text-input');
  const textCount = $('#text-count');
  const updateCount = () => (textCount.textContent = textInput.value.length + '/' + textInput.maxLength);
  textInput.addEventListener('input', () => {
    state.text = textInput.value.slice(0, 14);
    updateCount();
    render();
  });
  updateCount();

  const gridBtn = $('#grid-toggle');
  gridBtn.addEventListener('click', () => {
    const on = gridBtn.getAttribute('aria-pressed') !== 'true';
    gridBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
    gridBtn.classList.toggle('is-on', on);
    $('#case-grid').hidden = !on;
  });

  // ---------- print export ----------
  // Redraws the preview at print size on a canvas. Positions are taken from
  // the on-screen preview and scaled up, so what you see is what is printed.
  const EXPORT_W = 1200;

  function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
  }

  // Draw an image to fill a box, cropping the overflow (like object-fit: cover).
  function drawCover(ctx, img, x, y, w, h) {
    const s = Math.max(w / img.naturalWidth, h / img.naturalHeight);
    const sw = w / s, sh = h / s;
    ctx.drawImage(img, (img.naturalWidth - sw) / 2, (img.naturalHeight - sh) / 2, sw, sh, x, y, w, h);
  }

  async function exportDesign() {
    const rect = preview.getBoundingClientRect();
    const border = parseFloat(getComputedStyle(preview).borderLeftWidth) || 0;
    const innerW = rect.width - border * 2;
    const innerH = rect.height - border * 2;
    const scale = EXPORT_W / innerW;
    const W = EXPORT_W;
    const H = Math.round(innerH * scale);
    const toCanvas = (r) => ({
      x: (r.left - rect.left - border) * scale, y: (r.top - rect.top - border) * scale,
      w: r.width * scale, h: r.height * scale,
    });

    const canvas = document.createElement('canvas');
    canvas.width = W;
    canvas.height = H;
    const ctx = canvas.getContext('2d');

    // Case background
    ctx.save();
    roundRect(ctx, 0, 0, W, H, W * 0.15);
    ctx.clip();
    ctx.fillStyle = state.bg;
    ctx.fillRect(0, 0, W, H);

    if (hasPhotos()) {
      const imgs = await Promise.all(state.photos.map((p) => loadImage(p.url)));
      const box = toCanvas(photoBox.getBoundingClientRect());
      ctx.save();
      if ('filter' in ctx) ctx.filter = filterCss();
      if (isCollage()) {
        $$('img', collage).forEach((cell, i) => {
          const c = toCanvas(cell.getBoundingClientRect());
          drawCover(ctx, imgs[i % imgs.length], c.x, c.y, c.w, c.h);
        });
      } else {
        const img = imgs[0];
        roundRect(ctx, box.x, box.y, box.w, box.h, state.layout === 'framed' ? 16 * scale : 0);
        ctx.clip();
        ctx.translate(box.x + box.w / 2 + state.pos.x * scale, box.y + box.h / 2 + state.pos.y * scale);
        ctx.rotate((state.rot * Math.PI) / 180);
        const dw = (box.w * state.zoom) / 100;
        const dh = dw * (img.naturalHeight / img.naturalWidth);
        ctx.drawImage(img, -dw / 2, -dh / 2, dw, dh);
      }
      ctx.restore();
    }

    // Text: measured from the live preview element.
    if (hasText()) {
      const cs = getComputedStyle(caseText);
      const size = parseFloat(cs.fontSize) * scale * state.textScale;
      const family = state.font;
      try {
        await document.fonts.load('700 ' + Math.round(size) + 'px ' + family, state.text);
      } catch (_) { /* fall back to whatever font is available */ }
      const t = textInner.getBoundingClientRect();
      ctx.save();
      ctx.translate((t.left + t.width / 2 - rect.left - border) * scale, (t.top + t.height / 2 - rect.top - border) * scale);
      ctx.rotate(((baseAngle() + state.textRot) * Math.PI) / 180);
      ctx.font = '700 ' + size + 'px ' + family;
      ctx.fillStyle = state.ink;
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(state.text, 0, 0);
      ctx.restore();
    }
    ctx.restore();

    // Lossless PNG when it is small enough; otherwise a high-quality JPEG
    // (on white, since JPEG has no transparency) so uploads stay under ~2 MB.
    const png = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
    if (png && png.size <= 1.8 * 1048576) return { blob: png, name: 'design.png' };
    const flat = document.createElement('canvas');
    flat.width = W;
    flat.height = H;
    const fctx = flat.getContext('2d');
    fctx.fillStyle = '#ffffff';
    fctx.fillRect(0, 0, W, H);
    fctx.drawImage(canvas, 0, 0);
    const jpg = await new Promise((resolve) => flat.toBlob(resolve, 'image/jpeg', 0.92));
    return { blob: jpg, name: 'design.jpg' };
  }

  // Shrink the main photo before upload so it stays well under server limits.
  async function shrinkForUpload(file, maxSide = 2400) {
    try {
      const url = URL.createObjectURL(file);
      const img = await loadImage(url);
      URL.revokeObjectURL(url);
      const s = Math.min(1, maxSide / Math.max(img.naturalWidth, img.naturalHeight));
      if (s === 1 && file.size < 1.5 * 1048576) return file;
      const c = document.createElement('canvas');
      c.width = Math.round(img.naturalWidth * s);
      c.height = Math.round(img.naturalHeight * s);
      c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
      const blob = await new Promise((r) => c.toBlob(r, 'image/jpeg', 0.9));
      return blob || file;
    } catch (_) {
      return file;
    }
  }

  // ---------- add to cart ----------
  $('#add-btn').addEventListener('click', async () => {
    if (state.saving) return;
    if (!state.modelId) {
      openStep(STEP.device);
      showError('Choose your phone model.');
      return;
    }
    if (!hasPhotos() && !hasText()) {
      openStep(STEP.photo);
      showError('Add a photo or some text to design your case.');
      return;
    }
    if (!state.consent) {
      openStep(STEP.photo);
      showError('Tick the consent box under your photos first.');
      $('#consent').focus();
      return;
    }

    state.saving = true;
    const btn = $('#add-btn');
    btn.disabled = true;
    btn.lastChild.textContent = ' Saving…';
    showError('');

    try {
      const design = await exportDesign();
      const form = new FormData();
      form.append('csrf_token', studio.dataset.csrf);
      form.append('model_id', state.modelId);
      form.append('case_type', state.caseType);
      form.append('design_text', hasText() ? state.text : '');
      form.append('consent', state.consent ? '1' : '0');
      form.append('settings', JSON.stringify({
        layout: state.layout, photoCount: state.photos.length,
        filter: state.filter, filterStrength: state.strength, font: state.font,
        ink: state.ink, bg: state.bg, textLayout: state.textLayout,
        rotation: state.rot, zoom: state.zoom, offset: state.pos, textOffset: state.textPos, textScale: state.textScale, textRotation: state.textRot,
      }));
      form.append('design_png', design.blob, design.name);
      if (hasPhotos()) {
        const main = await shrinkForUpload(state.photos[0].file);
        form.append('source_photo', main, main.name || 'photo.jpg');
      }

      const res = await fetch(studio.dataset.saveUrl, { method: 'POST', body: form, credentials: 'same-origin' });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.success) throw new Error(data.error || 'We could not save your design. Check your connection and try again.');
      window.location.href = studio.dataset.cartUrl;
    } catch (err) {
      showError(err.message);
      state.saving = false;
      btn.disabled = false;
      btn.lastChild.textContent = ' Add';
    }
  });

  openStep(0);
  render();
  state.ready = true; // after the first paint, opening a step may scroll the list
})();
