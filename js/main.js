// Small enhancements for the storefront. Every page still works without JS.
(function () {
  // Auto-submit filter dropdowns on the shop page.
  document.querySelectorAll('[data-autosubmit]').forEach((el) => {
    el.addEventListener('change', () => el.form && el.form.submit());
  });

  // Hide success toasts after a few seconds. Warnings and errors stay until dismissed.
  const dismiss = (t) => (window.caselyDismissToast ? window.caselyDismissToast(t) : t.remove());
  document.querySelectorAll('.toast-success').forEach((toast) => {
    setTimeout(() => dismiss(toast), 6000);
  });

  // Something just went into the cart: give the header's cart count a quick bump
  // so it's clear where it went.
  if (document.querySelector('.toast-success .toast-link')) {
    const count = document.querySelector('.cart-count');
    if (count) count.classList.add('is-bumped');
  }

  // Buttons that send a form to the server show they're working, and a second
  // click can't send it twice (e.g. two orders from a double-clicked Pay).
  document.querySelectorAll('form[data-pending]').forEach((f) => {
    f.addEventListener('submit', (e) => {
      if (f.dataset.busy === '1') { e.preventDefault(); return; }
      f.dataset.busy = '1';
      const btn = e.submitter || f.querySelector('[type="submit"]');
      if (!btn) return;
      btn.dataset.idleHtml = btn.innerHTML;
      btn.classList.add('is-busy');
      btn.setAttribute('aria-disabled', 'true');
      btn.textContent = f.dataset.pending;
    });
  });
  // Coming back with the browser's Back button: the form is ready to use again.
  window.addEventListener('pageshow', (e) => {
    if (!e.persisted) return;
    document.querySelectorAll('form[data-busy="1"]').forEach((f) => {
      f.dataset.busy = '';
      f.querySelectorAll('.is-busy').forEach((b) => {
        b.classList.remove('is-busy');
        b.removeAttribute('aria-disabled');
        if (b.dataset.idleHtml) b.innerHTML = b.dataset.idleHtml;
      });
    });
  });

  // Actions that can't be undone ask first, naming what will be lost. Without
  // JavaScript the form simply submits.
  let confirmBox = null;
  function askToConfirm(form) {
    if (!confirmBox) {
      confirmBox = document.createElement('dialog');
      confirmBox.className = 'signin-dialog';
      confirmBox.setAttribute('aria-labelledby', 'confirm-title');
      confirmBox.innerHTML = '<h2 class="section-title-sm" id="confirm-title"></h2><p class="muted"></p>' +
        '<div class="button-row" style="margin-top: 1.25rem;"><button type="button" class="btn btn-danger" data-yes></button><button type="button" class="btn btn-outline" data-no></button></div>';
      document.body.append(confirmBox);
      confirmBox.querySelector('[data-no]').addEventListener('click', () => confirmBox.close());
    }
    confirmBox.querySelector('h2').textContent = form.dataset.confirmTitle;
    confirmBox.querySelector('p').textContent = form.dataset.confirmBody;
    const yes = confirmBox.querySelector('[data-yes]');
    const no = confirmBox.querySelector('[data-no]');
    yes.textContent = form.dataset.confirmYes;
    no.textContent = form.dataset.confirmNo;
    yes.onclick = () => {
      confirmBox.close();
      form.dataset.confirmed = '1';
      form.requestSubmit();
    };
    confirmBox.showModal();
    no.focus(); // the safe choice is the default
  }
  document.querySelectorAll('form[data-confirm-title]').forEach((form) => {
    form.addEventListener('submit', (e) => {
      if (form.dataset.confirmed === '1') return;
      e.preventDefault();
      askToConfirm(form);
    });
  });

  // Remember the customer's phone in this browser, so they only choose it once.
  const savedPhone = {
    get() { try { return localStorage.getItem('casely-phone') || ''; } catch (e) { return ''; } },
    set(id) { try { if (id) localStorage.setItem('casely-phone', id); } catch (e) { /* not remembered */ } },
  };
  const finderSelect = document.querySelector('.phone-finder select[name="model"]');
  if (finderSelect && [...finderSelect.options].some((o) => o.value && o.value === savedPhone.get())) {
    finderSelect.value = savedPhone.get();
  }
  const shopModel = document.getElementById('shop-model');
  if (shopModel) savedPhone.set(shopModel.value);

  // ---------- Home page ----------
  // Sections rise into place once as they enter the screen. The class is added
  // from here, so without JavaScript everything is simply visible.
  const reveals = document.querySelectorAll('[data-reveal]');
  if (reveals.length && 'IntersectionObserver' in window) {
    document.documentElement.classList.add('js-reveal');
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        io.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
    reveals.forEach((el) => io.observe(el));
  }

  // Tagline: each word lights up in reading order as the section scrolls in.
  const tagline = document.querySelector('[data-word-reveal]');
  if (tagline && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    tagline.querySelectorAll('.tagline-line').forEach((line) => {
      const words = line.textContent.trim().split(/\s+/);
      line.textContent = '';
      words.forEach((word, i) => {
        const span = document.createElement('span');
        span.className = 'tagline-word';
        span.textContent = word;
        line.append(span, i < words.length - 1 ? ' ' : '');
      });
    });
    const words = Array.from(tagline.querySelectorAll('.tagline-word'));
    let lit = 0;
    let queued = false;
    const light = () => {
      queued = false;
      const r = tagline.getBoundingClientRect();
      const vh = window.innerHeight;
      // Starts as the text enters the lower part of the screen, done by the middle.
      const progress = (vh * 0.85 - r.top) / (vh * 0.85 - vh * 0.4);
      const next = Math.round(Math.max(0, Math.min(1, progress)) * words.length);
      for (; lit < next; lit++) words[lit].classList.add('is-lit');
      if (lit >= words.length) window.removeEventListener('scroll', onScroll);
    };
    const onScroll = () => {
      if (queued) return;
      queued = true;
      requestAnimationFrame(light);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    light();
  }

  // ---------- Product page: phone model, case type, price, stock ----------
  const form = document.getElementById('buy-form');
  if (!form) return;

  const variants = JSON.parse(form.dataset.variants || '{}');
  const brandSelect = document.getElementById('brand-select');
  const modelSelect = document.getElementById('model-select');
  const qtyInput = form.querySelector('input[name="qty"]');
  const priceEl = document.querySelector('[data-price]');
  const totalEl = form.querySelector('[data-total]');
  const stockEl = form.querySelector('[data-stock]');
  const addBtn = form.querySelector('[data-add-btn]');
  const ghs = (n) => 'GH₵' + (Number.isInteger(n) ? n : n.toFixed(2));

  // ---------- "On your phone" preview (js/case-mockup.js) ----------
  const media = document.getElementById('product-media');
  const design = media && media.dataset.design ? JSON.parse(media.dataset.design) : null;
  const mockupBox = media && media.querySelector('[data-mockup]');
  const photoEl = media && media.querySelector('.pdm-photo');
  const viewBtns = Array.from(document.querySelectorAll('[data-view]'));
  const modelName = () => (modelSelect.selectedOptions[0] ? modelSelect.selectedOptions[0].textContent.trim() : '');

  function drawMockup() {
    if (!design || !mockupBox || !window.CaselyMockup) return;
    const name = modelName();
    mockupBox.innerHTML = window.CaselyMockup.render(design, name);
    mockupBox.setAttribute('aria-label', media.dataset.title + ' case on ' + (name || 'your phone'));
    document.querySelectorAll('[data-mockup-model]').forEach((el) => (el.textContent = name || 'your phone'));
  }
  function setView(view) {
    if (!design || !mockupBox) return;
    const mock = view === 'mockup';
    media.classList.toggle('is-mockup', mock);
    mockupBox.setAttribute('aria-hidden', mock ? 'false' : 'true');
    if (photoEl) photoEl.setAttribute('aria-hidden', mock ? 'true' : 'false');
    viewBtns.forEach((b) => b.setAttribute('aria-pressed', b.dataset.view === view ? 'true' : 'false'));
  }
  if (design && media) {
    media.style.setProperty('--mockup-backdrop', design.backdrop || '');
    viewBtns.forEach((b) => b.addEventListener('click', () => setView(b.dataset.view)));
    drawMockup();
    if (!photoEl) setView('mockup');
  }
  // Picking a phone shows the case on that phone.
  const showOnModel = () => { drawMockup(); setView('mockup'); };

  // Phones: a bar with the next step until the buy form scrolls into view.
  const bar = document.getElementById('buy-bar');
  let phoneChosen = false;
  function updateBar(v) {
    if (!bar) return;
    bar.querySelector('[data-bar-phone]').textContent = phoneChosen ? modelName() : 'Which phone is yours?';
    bar.querySelector('[data-bar-price]').textContent = v ? ghs(v.price) : '';
    const add = bar.querySelector('[data-bar-add]');
    add.hidden = !phoneChosen;
    add.disabled = !v || v.stock < 1;
    bar.querySelector('[data-bar-choose]').hidden = phoneChosen;
  }
  if (bar && 'IntersectionObserver' in window) {
    bar.querySelector('[data-bar-choose]').addEventListener('click', () => {
      form.scrollIntoView({ block: 'start' });
      brandSelect.focus({ preventScroll: true });
    });
    new IntersectionObserver(([entry]) => {
      bar.hidden = entry.isIntersecting || entry.boundingClientRect.top < 0;
    }).observe(form.querySelector('.buy-row'));
  }

  function current() {
    const brand = variants[brandSelect.value] || {};
    const model = brand[modelSelect.value] || {};
    const type = form.querySelector('input[name="case_type"]:checked').value;
    return model[type] || null;
  }

  function update() {
    const v = current();
    const qty = parseInt(qtyInput.value, 10) || 1;
    updateBar(v);
    if (!v) {
      stockEl.textContent = 'Not available for this phone in that case type.';
      stockEl.classList.add('is-low');
      addBtn.disabled = true;
      return;
    }
    priceEl.textContent = ghs(v.price);
    totalEl.textContent = ghs(v.price * qty);
    addBtn.disabled = v.stock < 1;
    stockEl.classList.toggle('is-low', v.stock < 4);
    stockEl.textContent = v.stock < 1 ? 'Sold out for this phone.' : v.stock < 4 ? 'Only ' + v.stock + ' left for this phone.' : '';
    qtyInput.max = Math.max(1, Math.min(10, v.stock));
    if (qty > qtyInput.max) qtyInput.value = qtyInput.max;
  }

  function fillModels() {
    const models = variants[brandSelect.value] || {};
    modelSelect.innerHTML = '';
    Object.keys(models).forEach((id) => {
      const opt = document.createElement('option');
      opt.value = id;
      opt.textContent = models[id].name;
      modelSelect.appendChild(opt);
    });
  }
  brandSelect.addEventListener('change', () => {
    fillModels();
    phoneChosen = true;
    savedPhone.set(modelSelect.value);
    update();
    showOnModel();
  });
  modelSelect.addEventListener('change', () => {
    phoneChosen = true;
    savedPhone.set(modelSelect.value);
    update();
    showOnModel();
  });
  form.querySelectorAll('input[name="case_type"]').forEach((r) => r.addEventListener('change', update));
  form.querySelectorAll('[data-qty]').forEach((btn) =>
    btn.addEventListener('click', () => {
      const next = (parseInt(qtyInput.value, 10) || 1) + parseInt(btn.dataset.qty, 10);
      qtyInput.value = Math.max(1, Math.min(parseInt(qtyInput.max, 10) || 10, next));
      update();
    })
  );

  // Start on the phone they already chose, if this case is made for it.
  const savedBrand = Object.keys(variants).find((b) => variants[b][savedPhone.get()]);
  if (savedBrand) {
    brandSelect.value = savedBrand;
    fillModels();
    modelSelect.value = savedPhone.get();
    phoneChosen = true;
    showOnModel();
  }
  update();
})();
