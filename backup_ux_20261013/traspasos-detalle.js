(() => {
  const toggle = document.querySelector('[data-history-toggle]');
  const timeline = document.querySelector('[data-transfer-timeline]');
  if (toggle && timeline) {
    toggle.addEventListener('click', () => {
      const expanded = toggle.getAttribute('aria-expanded') === 'true';
      timeline.querySelectorAll('[data-history-extra]').forEach((event) => {
        event.hidden = expanded;
      });
      toggle.setAttribute('aria-expanded', String(!expanded));
      toggle.textContent = expanded ? 'Mostrar historial completo' : 'Contraer historial';
    });
  }

  const contextKey = `traspaso-context:${window.location.pathname}`;
  document.querySelectorAll('[data-evidence-trigger]').forEach((button) => {
    button.addEventListener('click', () => document.getElementById(button.dataset.evidenceTrigger)?.click());
  });
  document.querySelectorAll('.transfer-evidence-upload input[type="file"]').forEach((input) => {
    input.addEventListener('change', () => {
      if (!input.files?.length) return;
      const form = input.form;
      if (form?.reportValidity()) form.requestSubmit();
    });
  });

  document.querySelectorAll('[data-equipment-state-select]').forEach((select) => {
    const custom = document.getElementById(select.dataset.customTarget || '');
    if (!custom) return;
    const sync = () => {
      if (select.value === '__custom__') {
        if (['completo', 'incompleto'].includes(custom.value.trim().toLowerCase())) custom.value = '';
        custom.type = 'text';
        custom.required = true;
        custom.focus();
      } else {
        custom.value = select.value;
        custom.type = 'hidden';
        custom.required = false;
      }
    };
    select.addEventListener('change', sync);
  });

  const forms = document.querySelectorAll('.transfer-detail form[method="post"]');
  forms.forEach((form) => {
    form.addEventListener('submit', () => {
      const anchor = form.querySelector('input[name="return_anchor"]')?.value || '';
      try { sessionStorage.setItem(contextKey, JSON.stringify({ anchor, y: window.scrollY })); } catch (_) { /* anchor redirect remains the primary restore path */ }
    });
  });

  const restore = () => {
    let saved = null;
    try { saved = JSON.parse(sessionStorage.getItem(contextKey) || 'null'); } catch (_) { /* ignore invalid old state */ }
    const hashAnchor = decodeURIComponent(window.location.hash.slice(1));
    const anchor = hashAnchor || saved?.anchor || '';
    const target = anchor ? document.getElementById(anchor) : null;
    const notice = document.querySelector('#mainContent > .alert.alert-dismissible');
    if (notice && (anchor || saved)) notice.classList.add('transfer-action-notice');
    if (target) target.scrollIntoView({ block: 'start' });
    else if (saved && Number.isFinite(saved.y)) window.scrollTo({ top: saved.y, behavior: 'auto' });
    if (saved) { try { sessionStorage.removeItem(contextKey); } catch (_) { /* ignore unavailable storage */ } }
  };
  window.addEventListener('load', () => requestAnimationFrame(() => requestAnimationFrame(restore)), { once: true });
})();
