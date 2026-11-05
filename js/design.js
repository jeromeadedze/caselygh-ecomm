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
  const STEP = { device: 0, type: 1, photo: 2, layout: 3, filters: 4, text: 5 };
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
    selected: null,          // 'text' | 'photo' | null: which one shows the resize / rotate box
    layout: 'full',
    filter: 'None',
    strength: DEFAULT_STRENGTH,
    font: "Georgia, 'Times New Roman', serif",
    text: '', // no text until the customer adds some
    ink: '#FFFFFF',
    bg: '#1A1418',
    textLayout: 'centre', // horizontal by default; Vertical is one of the Text layout options
    saving: false,
  };

  const preview = $('#case-preview');
  const photoBox = $('#photo-box');
  const photo = $('#photo');
  const collage = $('#collage');
  const caseText = $('#case-text');
  const errorBox = $('#studio-error');
  // Undo / redo history: snapshots of every studio choice (see "undo / redo" below).
  const hist = { past: [], future: [], current: null, lastKey: '', timer: 0, applying: false };
  // The layout places #case-text; the customer's move / resize / rotate go on the inner span,
  // so they always turn and grow around the text's own centre.
  const textInner = $('#case-text-inner');
  const textBox = $('#text-box');
  const sizeIn = $('#text-size');
  const rotIn = $('#text-rot');
  const rot = $('#rot');   // photo rotate slider
  const zoom = $('#zoom'); // photo zoom slider
  function baseAngle() { return state.textLayout === 'vertical' ? 90 : 0; }

  // ---------- helpers ----------
  const ghs = (n) => 'GHS\u00a0' + n.toLocaleString('en-US', { minimumFractionDigits: Number.isInteger(n) ? 0 : 2, maximumFractionDigits: 2 });
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
      b.setAttribute('aria-pressed', on ? 'true' : 'false'); // screen readers hear "selected"
    });
  }
  // Every option starts with its selected state announced too.
  $$('.pick, .swatch').forEach((b) => b.setAttribute('aria-pressed', b.classList.contains('is-on') ? 'true' : 'false'));

  function showError(message) {
    errorBox.textContent = message;
    errorBox.hidden = !message;
  }

  // ---------- steps (accordion) ----------
  // Returns why a step is locked, or '' when it can be opened.
  function lockedReason(i) {
    if (i === STEP.layout && !hasPhotos()) return 'Add a photo first';
    if (i === STEP.filters && !hasPhotos()) return 'Add a photo first';
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
      if (open && state.ready) head.scrollIntoView({ block: 'nearest', behavior: 'instant' });
      if (open && state.ready && idx === STEP.text && hasText()) state.selected = 'text';
      if (open && state.ready && (idx === STEP.photo || idx === STEP.layout) && hasPhotos() && !isCollage()) state.selected = 'photo';
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

    if (!state.editing && textInner.textContent !== state.text) textInner.textContent = state.text;
    caseText.hidden = !hasText();
    caseText.className = 'case-text layout-' + state.textLayout + (state.editing ? ' is-editing' : '');
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
    $('#drag-hint').textContent = state.editing ? 'Type on the case. Press Enter when you are done.'
      : single && hasText() ? 'Select the photo or the text to adjust it. Select the text again to type. Arrow keys move it.'
      : single ? 'Select the photo to move, zoom or rotate it. Arrow keys move it.' : 'Select the text to adjust it. Select it again to type. Arrow keys move it.';
    if (state.selected === 'photo' && !single) state.selected = null;
    if (state.selected === 'text' && !hasText()) state.selected = null;
    $('#add-text-btn').hidden = hasText();
    $('#text-remove').hidden = !hasText();
    $('#text-optional-note').hidden = hasText();
    syncTextControls();
    syncPhotoControls();
    placeSelBox();
    scheduleCommit();
  }

  // The camera on the preview follows the chosen phone (js/case-mockup.js).
  function drawCamera() {
    const box = $('#case-camera');
    if (box && window.CaselyMockup) box.innerHTML = window.CaselyMockup.camera(state.modelName);
  }

  // Record a change for undo once the studio has been still for a moment.
  function scheduleCommit() {
    if (!state.ready || hist.applying) return;
    clearTimeout(hist.timer);
    hist.timer = setTimeout(() => commit(), 350);
    if (typeof updateHistoryButtons === 'function') { try { updateHistoryButtons(); } catch (_) { /* not ready yet */ } }
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
  // Start on the phone they chose elsewhere on the site, else the newest listed.
  let savedPhoneId = '';
  try { savedPhoneId = localStorage.getItem('casely-phone') || ''; } catch (e) { /* not remembered */ }
  const savedPhoneBtn = savedPhoneId && $('[data-model-id="' + CSS.escape(savedPhoneId) + '"]');
  if (savedPhoneBtn) {
    const brandOfPhone = savedPhoneBtn.closest('[data-models-for]').dataset.modelsFor;
    const brandBtn = brandBtns.find((b) => b.dataset.brand === brandOfPhone);
    if (brandBtn) brandBtn.click();
    selectModel(savedPhoneBtn);
  } else if (startGroup.length) {
    selectModel(startGroup[startGroup.length - 1]);
  }

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
    // While typing on the case, taps on the text place the caret instead of dragging.
    if (state.editing && caseText.contains(e.target)) return;
    if (state.editing) finishEditing();
    const onText = hasText() && caseText.contains(e.target);
    const wasSelected = onText && state.selected === 'text';
    const canPhoto = hasPhotos() && !isCollage();
    const pick = onText ? 'text' : canPhoto ? 'photo' : null;
    if (state.selected !== pick) { state.selected = pick; render(); }
    if (!pick) return;
    const from = onText ? state.textPos : state.pos;
    drag = { what: onText ? 'text' : 'photo', x: e.clientX - from.x, y: e.clientY - from.y, sx: e.clientX, sy: e.clientY, wasSelected };
    preview.setPointerCapture(e.pointerId);
    e.preventDefault();
  });
  preview.addEventListener('pointermove', (e) => {
    if (!drag) return;
    const next = { x: e.clientX - drag.x, y: e.clientY - drag.y };
    if (drag.what === 'text') moveText(next);
    else { state.pos = next; render(); }
  });
  preview.addEventListener('pointerup', (e) => {
    const d = drag;
    drag = null;
    if (d && d.what === 'text' && d.wasSelected && Math.hypot(e.clientX - d.sx, e.clientY - d.sy) < 5) startEditing();
  });
  preview.addEventListener('pointercancel', () => (drag = null));
  caseText.addEventListener('dblclick', () => { if (hasText()) startEditing(); });

  // ---------- typing straight onto the case ----------
  function placeCaretAtEnd(selectAll) {
    const range = document.createRange();
    range.selectNodeContents(textInner);
    if (!selectAll) range.collapse(false);
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
  }
  function startEditing(selectAll = false) {
    if (state.editing) return;
    state.editing = true;
    state.selected = 'text';
    textInner.contentEditable = 'true';
    textInner.setAttribute('role', 'textbox');
    textInner.setAttribute('aria-label', 'Text on the case');
    caseText.classList.add('is-editing');
    textInner.focus();
    placeCaretAtEnd(selectAll);
    if (state.step !== STEP.text) openStep(STEP.text);
    render();
  }
  function finishEditing() {
    if (!state.editing) return;
    state.editing = false;
    textInner.contentEditable = 'false';
    textInner.removeAttribute('role');
    textInner.removeAttribute('aria-label');
    caseText.classList.remove('is-editing');
    window.getSelection().removeAllRanges();
    if (!hasText()) state.selected = null;
    textInner.textContent = state.text;
    render();
  }
  // Keep the case, the Text step's field and the counter in step while typing.
  textInner.addEventListener('input', () => {
    let txt = textInner.textContent.replace(/[\r\n]+/g, ' ');
    if (txt.length > textInput.maxLength) {
      txt = txt.slice(0, textInput.maxLength);
      textInner.textContent = txt;
      placeCaretAtEnd(false);
    }
    textInput.value = txt;
    textInput.dispatchEvent(new Event('input'));
  });
  textInner.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === 'Escape') { e.preventDefault(); finishEditing(); }
  });
  textInner.addEventListener('blur', () => finishEditing());
  // Paste as plain text only.
  textInner.addEventListener('paste', (e) => {
    e.preventDefault();
    const plain = (e.clipboardData || window.clipboardData).getData('text').replace(/[\r\n]+/g, ' ');
    document.execCommand('insertText', false, plain);
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
  // ---------- text box: move, resize, rotate ----------

  function syncTextControls() {
    sizeIn.value = Math.round(state.textScale * 100);
    rotIn.value = Math.round(state.textRot);
    $('#text-size-out').textContent = Math.round(state.textScale * 100) + '%';
    $('#text-rot-out').textContent = Math.round(state.textRot) + '°';
  }

  function syncPhotoControls() {
    rot.value = Math.round(state.rot);
    zoom.value = Math.round(state.zoom);
    $('#rot-out').textContent = Math.round(state.rot) + '°';
    $('#zoom-out').textContent = Math.round(state.zoom) + '%';
  }

  // Draw the dashed box over the selected text or photo, turned to the same angle.
  function placeSelBox() {
    const sel = state.selected;
    if (!sel) { textBox.hidden = true; return; }
    const el = sel === 'text' ? textInner : photo;
    const scale = sel === 'text' ? state.textScale : 1;
    const pad = sel === 'text' ? 12 : 0;
    textBox.hidden = false;
    textBox.dataset.target = sel;
    const stage = textBox.parentElement.getBoundingClientRect();
    const r = el.getBoundingClientRect();
    const w = el.offsetWidth * scale + pad;
    const h = el.offsetHeight * scale + pad;
    const cx = r.left + r.width / 2 - stage.left;
    const cy = r.top + r.height / 2 - stage.top;
    Object.assign(textBox.style, {
      width: w + 'px', height: h + 'px', left: cx - w / 2 + 'px', top: cy - h / 2 + 'px',
      transform: 'rotate(' + (sel === 'text' ? baseAngle() + state.textRot : state.rot) + 'deg)',
    });
  }

  function centreOf(el) {
    const t = el.getBoundingClientRect();
    return { x: t.left + t.width / 2, y: t.top + t.height / 2 };
  }
  const angleOf = (c, p) => (Math.atan2(p.y - c.y, p.x - c.x) * 180) / Math.PI;
  const wrap180 = (a) => ((((a + 180) % 360) + 360) % 360) - 180;

  let boxDrag = null;
  textBox.addEventListener('pointerdown', (e) => {
    const handle = e.target.closest('[data-handle]');
    if (!handle || !state.selected) return;
    const isText = state.selected === 'text';
    const p = { x: e.clientX, y: e.clientY };
    const c = centreOf(isText ? textInner : photo);
    boxDrag = {
      kind: handle.dataset.handle, isText, start: p, centre: c,
      scale: isText ? state.textScale : state.zoom, rot: isText ? state.textRot : state.rot,
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
    if (d.kind === 'scale') {
      const ratio = Math.hypot(p.x - d.centre.x, p.y - d.centre.y) / d.dist;
      if (d.isText) state.textScale = Math.min(3, Math.max(0.1, d.scale * ratio));
      else state.zoom = Math.min(300, Math.max(50, d.scale * ratio)); // same range as the Zoom slider
    } else {
      const base = d.isText ? baseAngle() : 0;
      let angle = wrap180(d.rot + angleOf(d.centre, p) - d.angle);
      // Snap to straight angles when close, so level is easy to get back.
      const total = angle + base;
      const nearest = Math.round(total / 90) * 90;
      if (Math.abs(total - nearest) < 5) angle = wrap180(nearest - base);
      if (d.isText) state.textRot = angle;
      else state.rot = angle;
    }
    render();
  });
  ['pointerup', 'pointercancel'].forEach((t) =>
    textBox.addEventListener(t, () => { boxDrag = null; textBox.classList.remove('is-active'); }));

  sizeIn.addEventListener('input', () => { state.textScale = sizeIn.value / 100; render(); });
  rotIn.addEventListener('input', () => { state.textRot = parseInt(rotIn.value, 10); render(); });
  document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && /^[zy]$/i.test(e.key)) {
      const el = document.activeElement;
      const inField = el && (el.isContentEditable || el.tagName === 'TEXTAREA' ||
        (el.tagName === 'INPUT' && !['range', 'checkbox', 'radio', 'button'].includes(el.type)));
      if (inField) return; // let text fields use their own undo
      e.preventDefault();
      if (e.key.toLowerCase() === 'y' || e.shiftKey) redo();
      else undo();
      return;
    }
    if (e.key === 'Escape' && state.selected && !state.editing) { state.selected = null; render(); }
    // Arrow keys nudge whatever is selected (6px, or 24px with Shift), for anyone who can't drag.
    const dir = { ArrowUp: [0, -1], ArrowDown: [0, 1], ArrowLeft: [-1, 0], ArrowRight: [1, 0] }[e.key];
    const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName || '') || document.activeElement?.isContentEditable;
    if (!dir || !state.selected || typing) return;
    e.preventDefault();
    const step = e.shiftKey ? 24 : 6;
    if (state.selected === 'text') {
      moveText({ x: state.textPos.x + dir[0] * step, y: state.textPos.y + dir[1] * step });
    } else {
      state.pos = { x: state.pos.x + dir[0] * step, y: state.pos.y + dir[1] * step };
      render();
    }
  });
  window.addEventListener('resize', placeSelBox);
  photo.addEventListener('load', placeSelBox); // its size is only known once the image has loaded

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
    const had = hasText();
    state.text = textInput.value.slice(0, 14);
    updateCount();
    if (had !== hasText()) {
      state.selected = hasText() ? 'text' : null;
      refreshSteps();
    }
    render();
  });

  // "Add text" under the case opens the Text step; "Remove text" clears it.
  $('#add-text-btn').addEventListener('click', () => {
    openStep(STEP.text);
    textInput.value = 'Your text';
    textInput.dispatchEvent(new Event('input'));
    startEditing(true); // "Your text" is selected, so typing replaces it
  });
  $('#text-remove').addEventListener('click', () => {
    textInput.value = '';
    textInput.dispatchEvent(new Event('input'));
    textInput.focus();
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

  async function exportDesign(exportW = EXPORT_W) {
    const rect = preview.getBoundingClientRect();
    const border = parseFloat(getComputedStyle(preview).borderLeftWidth) || 0;
    const innerW = rect.width - border * 2;
    const innerH = rect.height - border * 2;
    const scale = exportW / innerW;
    const W = exportW;
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

    state.saving = true;
    const btn = $('#add-btn');
    btn.disabled = true;
    btn.classList.add('is-busy');
    btn.querySelector('.btn-label').textContent = 'Saving…';
    showError('');

    try {
      const design = await exportDesign();
      const form = new FormData();
      form.append('csrf_token', studio.dataset.csrf);
      form.append('model_id', state.modelId);
      form.append('case_type', state.caseType);
      form.append('design_text', hasText() ? state.text : '');
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
      btn.classList.remove('is-busy');
      btn.querySelector('.btn-label').innerHTML = 'Add<span class="btn-extra"> to cart</span>';
    }
  });

  // ---------- drafts ----------
  // Signed-in customers can save the design and come back to it later.
  // Guests are asked to sign in; their design waits in this browser (IndexedDB)
  // and is restored and saved as a draft straight after they sign in.
  const LOGGED_IN = studio.dataset.loggedIn === '1';
  const draftBtn = $('#draft-btn');
  const draftStatus = $('#draft-status');
  const signinDialog = $('#draft-signin');
  const setDraftStatus = (msg) => {
    draftStatus.textContent = msg;
    $('#draft-status-row').hidden = !msg;
    $('#draft-rename').hidden = !state.draftId;
  };

  function draftSettings() {
    return {
      brand: state.brand, modelId: state.modelId, caseType: state.caseType,
      layout: state.layout, filter: state.filter, strength: state.strength,
      font: state.font, text: state.text, ink: state.ink, bg: state.bg, textLayout: state.textLayout,
      rot: state.rot, zoom: state.zoom, pos: state.pos,
      textPos: state.textPos, textScale: state.textScale, textRot: state.textRot,
    };
  }
  const draftName = () => (state.modelName || 'Custom case') + (hasText() ? ' · ' + state.text : '');

  async function postForm(url, form) {
    const res = await fetch(url, { method: 'POST', body: form, credentials: 'same-origin' });
    const data = await res.json().catch(() => ({}));
    if (res.status === 401) {
      const err = new Error(data.error || 'Log in to save drafts.');
      err.login = true;
      throw err;
    }
    if (!res.ok || !data.success) throw new Error(data.error || 'Your draft could not be saved. Check your connection and try again.');
    return data;
  }

  async function saveDraft() {
    if (!LOGGED_IN) { signinDialog.showModal(); return; }
    if (state.savingDraft) return;
    state.savingDraft = true;
    draftBtn.classList.add('is-busy');
    draftBtn.disabled = true;
    showError('');
    try {
      // Photos go up one at a time (each shrunk), and only once per draft.
      for (let i = 0; i < state.photos.length; i++) {
        const p = state.photos[i];
        if (p.serverName) continue;
        setDraftStatus('Saving photo ' + (i + 1) + ' of ' + state.photos.length + '…');
        const small = await shrinkForUpload(p.file, 2000);
        const f = new FormData();
        f.append('csrf_token', studio.dataset.csrf);
        f.append('photo', small, small.name || 'photo.jpg');
        p.serverName = (await postForm(studio.dataset.draftUploadUrl, f)).file;
      }
      setDraftStatus('Saving draft…');
      const preview = await exportDesign(360);
      const f = new FormData();
      f.append('csrf_token', studio.dataset.csrf);
      f.append('draft_id', state.draftId || '');
      f.append('name', state.draftTitle || draftName());
      f.append('model_id', state.modelId || '');
      f.append('settings', JSON.stringify(draftSettings()));
      f.append('photos', JSON.stringify(state.photos.map((p) => p.serverName)));
      f.append('preview', preview.blob, preview.name);
      const data = await postForm(studio.dataset.draftSaveUrl, f);
      state.draftId = data.draft_id;
      state.draftTitle = data.name || state.draftTitle;
      history.replaceState(null, '', '?draft=' + data.draft_id);
      const time = new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
      setDraftStatus('"' + state.draftTitle + '" saved at ' + time + '. Find it under My account.');
      draftBtn.innerHTML = '<span>Save</span>';
      markSaved();
      draftBtn.setAttribute('aria-label', 'Save changes to draft');
    } catch (err) {
      setDraftStatus('');
      if (err.login) signinDialog.showModal();
      else showError(err.message);
    } finally {
      state.savingDraft = false;
      draftBtn.classList.remove('is-busy');
      draftBtn.disabled = false;
    }
  }
  // The first save asks for a name. Later saves keep it, and Rename changes it.
  const nameDialog = $('#draft-name-dialog');
  const nameInput = $('#draft-name-input');
  function askForName() {
    const renaming = Boolean(state.draftId);
    $('#draft-name-title').textContent = renaming ? 'Rename this draft' : 'Name this design';
    $('#draft-name-save').textContent = renaming ? 'Save name' : 'Save draft';
    nameInput.value = state.draftTitle || draftName();
    nameDialog.showModal();
    nameInput.select();
  }
  draftBtn.addEventListener('click', () => {
    if (!LOGGED_IN) { signinDialog.showModal(); return; }
    if (state.draftId || state.draftTitle) saveDraft();
    else askForName();
  });
  $('#draft-rename').addEventListener('click', askForName);
  $('#draft-name-cancel').addEventListener('click', () => nameDialog.close());
  $('#draft-name-form').addEventListener('submit', (e) => {
    e.preventDefault();
    state.draftTitle = nameInput.value.trim() || draftName();
    nameDialog.close();
    saveDraft();
  });

  // Put a saved design back into the studio. photoEntries: [{ file, serverName? }]
  function applyDesign(s, photoEntries, photoObjects) {
    const click = (sel) => { const b = $(sel); if (b) b.click(); };
    const q = (v) => '"' + CSS.escape(String(v)) + '"';
    if (s.brand) click('[data-brand=' + q(s.brand) + ']');
    if (s.modelId) click('[data-model-id=' + q(s.modelId) + ']');
    if (s.caseType) click('[data-case-type=' + q(s.caseType) + ']');
    if (photoObjects) {
      state.photos = photoObjects.slice();
    } else {
      state.photos = photoEntries.map((e) => ({ file: e.file, url: URL.createObjectURL(e.file), serverName: e.serverName || null, hue: 0, sat: 0 }));
      state.photos.forEach(measureColour);
    }
    if (s.layout && (s.layout in COLLAGES || ['full', 'framed', 'split'].includes(s.layout))) setLayout(s.layout);
    if (s.filter) click('[data-filter=' + q(s.filter) + ']');
    if (Number.isFinite(s.strength)) setStrength(Math.max(0, Math.min(100, s.strength)));
    if (s.font) click('[data-font=' + q(s.font) + ']');
    if (typeof s.text === 'string') { textInput.value = s.text.slice(0, 14); textInput.dispatchEvent(new Event('input')); }
    if (s.ink) click('[data-ink=' + q(s.ink) + ']');
    if (s.bg) click('[data-bg=' + q(s.bg) + ']');
    if (s.textLayout) click('[data-text-layout=' + q(s.textLayout) + ']'); // also resets the text box
    const num = (v, d) => (Number.isFinite(v) ? v : d);
    const pt = (v) => ({ x: num(v && v.x, 0), y: num(v && v.y, 0) });
    state.rot = num(s.rot, 0);
    state.zoom = num(s.zoom, 100);
    state.pos = pt(s.pos);
    state.textPos = pt(s.textPos);
    state.textScale = num(s.textScale, 1);
    state.textRot = num(s.textRot, 0);
    state.selected = null;
    photosChanged();
  }

  // Keep the guest's design in this browser while they go to sign in.
  function idb() {
    return new Promise((resolve, reject) => {
      const r = indexedDB.open('casely-studio', 1);
      r.onupgradeneeded = () => r.result.createObjectStore('pending');
      r.onsuccess = () => resolve(r.result);
      r.onerror = () => reject(r.error);
    });
  }
  async function keepPending(extra = {}) {
    try {
      const db = await idb();
      await new Promise((resolve, reject) => {
        const tx = db.transaction('pending', 'readwrite');
        tx.objectStore('pending').put({ settings: draftSettings(), photos: state.photos.map((p) => p.file), at: Date.now(), ...extra }, 'design');
        tx.oncomplete = resolve;
        tx.onerror = () => reject(tx.error);
      });
      return true;
    } catch (_) { /* private mode etc.: the customer simply starts again */
      return false;
    }
  }
  async function putPendingBack(pending) {
    try {
      const db = await idb();
      db.transaction('pending', 'readwrite').objectStore('pending').put(pending, 'design');
    } catch (_) { /* nothing more to do */ }
  }

  // Closing compresses the studio into a small tab on the page you came from.
  // The design waits in this browser, and the tab brings it back.
  const closeLink = $('#studio-close');
  let closeAnim = null;
  closeLink.addEventListener('click', async (e) => {
    e.preventDefault();
    if (closeLink.dataset.closing) return;
    closeLink.dataset.closing = '1';
    const kept = await keepPending({ minimized: true, draftId: state.draftId || null, draftTitle: state.draftTitle || null });
    try {
      if (kept) {
        localStorage.setItem('casely-minimized', JSON.stringify({ model: state.modelName, at: Date.now() }));
        sessionStorage.setItem('casely-tab-new', '1');
      }
    } catch (_) { /* the design still reopens from Custom design */ }
    let target = closeLink.href;
    try {
      const from = new URL(document.referrer);
      if (from.origin === location.origin && !/\/(design|login|register)\.php$/.test(from.pathname)) target = from.href;
    } catch (_) { /* no usable previous page: go home */ }
    const panel = $('.studio');
    const moving = window.matchMedia('(prefers-reduced-motion: no-preference)').matches;
    panel.style.transformOrigin = '0 ' + (window.scrollY + window.innerHeight) + 'px';
    closeAnim = panel.animate(
      moving
        ? [{ transform: 'scale(1)', opacity: 1 }, { transform: 'scale(.16)', opacity: 0 }]
        : [{ opacity: 1 }, { opacity: 0 }],
      { duration: moving ? 240 : 150, easing: getComputedStyle(document.documentElement).getPropertyValue('--ease-settle').trim() || 'ease-out', fill: 'forwards' }
    );
    await closeAnim.finished.catch(() => {});
    window.location.href = target;
  });
  // Coming back with the browser's Back button shows the studio, not the end of the animation.
  window.addEventListener('pageshow', (e) => {
    if (e.persisted && closeAnim) {
      closeAnim.cancel();
      delete closeLink.dataset.closing;
    }
  });
  async function takePending() {
    try {
      const db = await idb();
      return await new Promise((resolve) => {
        const tx = db.transaction('pending', 'readwrite');
        const store = tx.objectStore('pending');
        const get = store.get('design');
        get.onsuccess = () => { store.delete('design'); resolve(get.result || null); };
        get.onerror = () => resolve(null);
      });
    } catch (_) {
      return null;
    }
  }
  $$('[data-keep-design]').forEach((a) =>
    a.addEventListener('click', async (e) => {
      e.preventDefault();
      await keepPending();
      window.location.href = a.href;
    })
  );

  openStep(0);
  render();
  state.ready = true; // after the first paint, opening a step may scroll the list

  // ---------- undo / redo ----------
  // A change is recorded once things settle for a moment, so a whole drag or a
  // typed word is one step. Snapshots share photo objects, so they stay small.
  const undoBtn = $('#undo-btn');
  const redoBtn = $('#redo-btn');
  const HISTORY_MAX = 60;
  const snapshot = () => ({ settings: draftSettings(), photos: state.photos.slice() });
  const snapKey = (snap) => JSON.stringify([snap.settings, snap.photos.map((p) => p.url)]);

  function updateHistoryButtons() {
    undoBtn.disabled = !(hist.past.length || hist.timer);
    redoBtn.disabled = !hist.future.length;
  }
  function commit() {
    clearTimeout(hist.timer);
    hist.timer = 0;
    const snap = snapshot();
    const key = snapKey(snap);
    if (key !== hist.lastKey) {
      if (hist.current) hist.past.push(hist.current);
      if (hist.past.length > HISTORY_MAX) hist.past.shift();
      hist.current = snap;
      hist.lastKey = key;
      hist.future = [];
    }
    updateHistoryButtons();
  }
  function resetHistory() {
    hist.past = [];
    hist.future = [];
    hist.current = snapshot();
    hist.lastKey = snapKey(hist.current);
    updateHistoryButtons();
  }
  function restoreSnapshot(snap) {
    if (state.editing) finishEditing();
    hist.applying = true;
    applyDesign(snap.settings, null, snap.photos);
    hist.applying = false;
    hist.current = snap;
    hist.lastKey = snapKey(snapshot());
    updateHistoryButtons();
  }
  function undo() {
    if (hist.timer) commit(); // record the change still waiting, so it can be undone too
    if (!hist.past.length) return;
    hist.future.push(hist.current);
    restoreSnapshot(hist.past.pop());
  }
  function redo() {
    if (!hist.future.length) return;
    hist.past.push(hist.current);
    restoreSnapshot(hist.future.pop());
  }
  undoBtn.addEventListener('click', undo);
  redoBtn.addEventListener('click', redo);
  resetHistory();

  // Start another design. Unsaved work gets a warning first.
  const designFingerprint = () => {
    const { bg, ...settings } = draftSettings(); // bg can be set by the photo's colour once it loads
    return JSON.stringify([settings, state.photos.map((p) => p.serverName || (p.file && p.file.name))]);
  };
  let savedFingerprint = designFingerprint();
  function markSaved() { savedFingerprint = designFingerprint(); }
  const newDesignDialog = $('#new-design-dialog');
  // Leaving this design with unsaved changes asks first, for a new design or another draft.
  function confirmLeaving(e, href, title, yes) {
    if (designFingerprint() === savedFingerprint) return;
    e.preventDefault();
    $('#new-design-title').textContent = title;
    const go = $('#new-design-go');
    go.href = href;
    go.textContent = yes;
    newDesignDialog.showModal();
    $('#new-design-keep').focus();
  }
  $('#new-design').addEventListener('click', (e) => confirmLeaving(e, e.currentTarget.href, 'Start a new design?', 'Start new design'));

  // My designs: a drawer of drafts to reopen and designs made before.
  const designsDrawer = $('#designs-drawer');
  $('#designs-open').addEventListener('click', () => {
    designsDrawer.showModal();
    $('#designs-close').focus();
  });
  $('#designs-close').addEventListener('click', () => designsDrawer.close());
  designsDrawer.addEventListener('click', (e) => {
    if (e.target === designsDrawer) designsDrawer.close(); // a click on the backdrop
  });
  $$('[data-open-draft]').forEach((a) =>
    a.addEventListener('click', (e) => confirmLeaving(e, a.href, 'Open this draft?', 'Open draft'))
  );
  $('#new-design-keep').addEventListener('click', () => newDesignDialog.close());

  // Reopen a design: one kept while signing in, or a saved draft (?draft=ID).
  (async function restore() {
    let pending = await takePending();
    // Opening a different saved draft leaves the design in the tab where it is.
    const requested = studio.dataset.draft ? JSON.parse(studio.dataset.draft).id : null;
    if (pending && pending.minimized && requested && pending.draftId !== requested) {
      await putPendingBack(pending);
      pending = null;
    }
    if (pending && Date.now() - pending.at < 24 * 3600 * 1000) {
      applyDesign(pending.settings || {}, (pending.photos || []).map((file) => ({ file })));
      resetHistory();
      if (pending.minimized) {
        try { localStorage.removeItem('casely-minimized'); } catch (_) { /* nothing to clear */ }
        state.draftId = pending.draftId || null;
        state.draftTitle = pending.draftTitle || null;
        if (state.draftId) {
          draftBtn.innerHTML = '<span>Save</span>';
          draftBtn.setAttribute('aria-label', 'Save changes to draft');
          history.replaceState(null, '', '?draft=' + state.draftId);
          setDraftStatus('Picked up where you left off on "' + (state.draftTitle || 'your draft') + '".');
        }
        return;
      }
      markSaved();
      if (LOGGED_IN) saveDraft();
      return;
    }
    if (!studio.dataset.draft) return;
    const draft = JSON.parse(studio.dataset.draft);
    setDraftStatus('Opening your draft…');
    try {
      const entries = await Promise.all(draft.photos.map(async (p) => {
        const res = await fetch(p.url, { credentials: 'same-origin' });
        if (!res.ok) throw new Error('missing');
        const blob = await res.blob();
        return { file: new File([blob], p.name, { type: blob.type }), serverName: p.name };
      }));
      applyDesign(draft.settings || {}, entries);
      state.draftId = draft.id;
      state.draftTitle = draft.name;
      resetHistory();
      markSaved();
      draftBtn.innerHTML = '<span>Save</span>';
      draftBtn.setAttribute('aria-label', 'Save changes to draft');
      setDraftStatus('Editing your draft "' + draft.name + '".');
    } catch (_) {
      setDraftStatus('');
      showError('Some photos in this draft could not be loaded. Add them again, then save.');
    }
  })();
})();
