(() => {
  const detail = document.querySelector('.transfer-detail');
  const tabs = [...document.querySelectorAll('[data-transfer-tab]')];
  const panes = [...document.querySelectorAll('[data-transfer-pane]')];
  const tabForAnchor = anchor => {
    if (anchor === 'autorizacion-externa') return 'oficio';
    if (anchor === 'documentos-expediente' || anchor.startsWith('documento-')) return 'documentos';
    if (anchor === 'transfer-history-pane') return 'historial';
    return 'resumen';
  };
  const activateTab = (name, updateHash = false) => {
    const tab = tabs.find(item => item.dataset.transferTab === name) || tabs[0];
    if (!tab) return;
    detail?.classList.add('transfer-tabs-enabled');
    tabs.forEach(item => {
      const active = item === tab;
      item.setAttribute('aria-selected', String(active));
      item.tabIndex = active ? 0 : -1;
    });
    panes.forEach(pane => {
      const active = pane.dataset.transferPane === tab.dataset.transferTab;
      pane.classList.toggle('is-active', active);
      pane.hidden = !active;
    });
    if (updateHash && window.history?.replaceState) window.history.replaceState(null, '', `#tab-${tab.dataset.transferTab}`);
  };
  tabs.forEach(tab => tab.addEventListener('click', () => activateTab(tab.dataset.transferTab, true)));
  tabs.forEach(tab => tab.addEventListener('keydown', event => {
    if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
    event.preventDefault();
    const index = tabs.indexOf(tab);
    const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length;
    tabs[next]?.focus();
    activateTab(tabs[next]?.dataset.transferTab || 'resumen', true);
  }));
  document.querySelectorAll('[data-transfer-tab-target]').forEach(link => link.addEventListener('click', () => activateTab(link.dataset.transferTabTarget, false)));
  const initialTab = window.location.hash.startsWith('#tab-') ? window.location.hash.slice(5) : null;
  activateTab(initialTab || 'resumen');

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
    const rawHash = decodeURIComponent(window.location.hash.slice(1));
    if (rawHash.startsWith('tab-')) activateTab(rawHash.slice(4));
    const hashAnchor = rawHash.startsWith('tab-') ? '' : rawHash;
    const anchor = hashAnchor || saved?.anchor || '';
    if (anchor) activateTab(tabForAnchor(anchor));
    const target = anchor ? document.getElementById(anchor) : null;
    const notice = document.querySelector('#mainContent > .alert.alert-dismissible');
    if (notice && (anchor || saved)) notice.classList.add('transfer-action-notice');
    if (target) target.scrollIntoView({ block: 'start' });
    else if (saved && Number.isFinite(saved.y)) window.scrollTo({ top: saved.y, behavior: 'auto' });
    if (saved) { try { sessionStorage.removeItem(contextKey); } catch (_) { /* ignore unavailable storage */ } }
  };
  window.addEventListener('load', () => requestAnimationFrame(() => requestAnimationFrame(restore)), { once: true });
})();
