// Small enhancements for the storefront. Every page still works without JS.
(function () {
  // Auto-submit filter dropdowns on the shop page.
  document.querySelectorAll('[data-autosubmit]').forEach((el) => {
    el.addEventListener('change', () => el.form && el.form.submit());
  });

  // Hide success toasts after a few seconds. Warnings and errors stay until dismissed.
  document.querySelectorAll('.toast-success').forEach((toast) => {
    setTimeout(() => toast.remove(), 6000);
  });

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

  function current() {
    const brand = variants[brandSelect.value] || {};
    const model = brand[modelSelect.value] || {};
    const type = form.querySelector('input[name="case_type"]:checked').value;
    return model[type] || null;
  }

  function update() {
    const v = current();
    const qty = parseInt(qtyInput.value, 10) || 1;
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

  brandSelect.addEventListener('change', () => {
    const models = variants[brandSelect.value] || {};
    modelSelect.innerHTML = '';
    Object.keys(models).forEach((id) => {
      const opt = document.createElement('option');
      opt.value = id;
      opt.textContent = models[id].name;
      modelSelect.appendChild(opt);
    });
    update();
    showOnModel();
  });
  modelSelect.addEventListener('change', () => { update(); showOnModel(); });
  form.querySelectorAll('input[name="case_type"]').forEach((r) => r.addEventListener('change', update));
  form.querySelectorAll('[data-qty]').forEach((btn) =>
    btn.addEventListener('click', () => {
      const next = (parseInt(qtyInput.value, 10) || 1) + parseInt(btn.dataset.qty, 10);
      qtyInput.value = Math.max(1, Math.min(parseInt(qtyInput.max, 10) || 10, next));
      update();
    })
  );
  update();
})();
