(() => {
  const body = document.body;
  const desktop = window.matchMedia('(min-width: 992px)');
  const storage = (key, value) => {
    try {
      if (value === undefined) return localStorage.getItem(key) === 'true';
      localStorage.setItem(key, value ? 'true' : 'false');
    } catch (error) { return value === undefined ? false : undefined; }
  };

  const setupSidebar = ({ nav, toggle, backdrop, bodyCollapsed, bodyOpen, storageKey }) => {
    if (!nav || !toggle) return null;
    let collapsed = storage(storageKey);
    let flyoutButton = null;
    const isCompact = () => desktop.matches && collapsed;
    const groups = [...nav.querySelectorAll('.bi-nav-section')];
    const expandedStates = new Map(groups.map(button => [button, button.getAttribute('aria-expanded')]));
    const panelFor = button => {
      const id = button.getAttribute('data-bs-target') || button.getAttribute('data-target');
      return id ? nav.querySelector(id) : null;
    };
    const closeFlyouts = () => {
      groups.forEach(button => panelFor(button)?.classList.remove('bi-flyout-active'));
      flyoutButton?.setAttribute('aria-expanded', 'false');
      flyoutButton = null;
    };
    const openFlyout = button => {
      if (flyoutButton === button) { closeFlyouts(); return; }
      closeFlyouts();
      const panel = panelFor(button);
      if (!panel) return;
      panel.classList.add('bi-flyout-active');
      const top = Math.max(8, Math.min(button.getBoundingClientRect().top, window.innerHeight - panel.offsetHeight - 8));
      panel.style.setProperty('--bi-flyout-top', `${top}px`);
      button.setAttribute('aria-expanded', 'true');
      flyoutButton = button;
    };
    const update = () => {
      body.classList.toggle(bodyCollapsed, desktop.matches && collapsed);
      toggle.setAttribute('aria-expanded', String(desktop.matches ? !collapsed : body.classList.contains(bodyOpen)));
      groups.forEach(button => {
        if (isCompact()) button.setAttribute('aria-expanded', button === flyoutButton ? 'true' : 'false');
        else if (expandedStates.has(button)) button.setAttribute('aria-expanded', expandedStates.get(button));
      });
    };
    const closeDrawer = () => {
      body.classList.remove(bodyOpen);
      update();
    };
    toggle.addEventListener('click', () => {
      closeFlyouts();
      if (desktop.matches) {
        collapsed = !collapsed;
        storage(storageKey, collapsed);
        update();
      } else {
        body.classList.toggle(bodyOpen);
        update();
        if (body.classList.contains(bodyOpen)) nav.querySelector('a[href], .bi-nav-section')?.focus();
      }
    });
    backdrop?.addEventListener('click', closeDrawer);
    nav.addEventListener('click', event => {
      const section = event.target.closest('.bi-nav-section');
      if (section && isCompact()) {
        event.preventDefault();
        event.stopImmediatePropagation();
        openFlyout(section);
      } else if (section) {
        const willOpen = !panelFor(section)?.classList.contains('show');
        groups.forEach(other => expandedStates.set(other, other === section && willOpen ? 'true' : 'false'));
        groups.forEach(other => {
          if (other === section) return;
          const panel = panelFor(other);
          if (!panel?.classList.contains('show')) return;
          if (window.bootstrap?.Collapse) window.bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false }).hide();
          else if (window.jQuery) window.jQuery(panel).collapse('hide');
        });
      }
      if (event.target.closest('a[href]') && !desktop.matches) closeDrawer();
    }, true);
    document.addEventListener('click', event => {
      if (flyoutButton && !nav.contains(event.target)) closeFlyouts();
    }, true);
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && flyoutButton) {
        const prior = flyoutButton;
        closeFlyouts();
        prior.focus();
      } else if (event.key === 'Escape' && body.classList.contains(bodyOpen)) {
        closeDrawer();
        toggle.focus();
      }
      if (event.key === 'Tab' && body.classList.contains(bodyOpen)) {
        const focusable = [...nav.querySelectorAll('a[href], button:not(:disabled)')].filter(el => el.offsetParent !== null);
        const first = focusable[0], last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
      }
    });
    nav.querySelectorAll('a').forEach(link => link.addEventListener('click', () => { if (!desktop.matches) closeDrawer(); }));
    desktop.addEventListener('change', () => { closeFlyouts(); closeDrawer(); collapsed = storage(storageKey); update(); });
    update();
    return { closeFlyouts };
  };

  const inventory = setupSidebar({
    nav: document.querySelector('#accordionSidebar:not(.bi-legacy-sidebar)'),
    toggle: document.querySelector('#biNavToggle'),
    backdrop: document.querySelector('[data-bi-sidebar-backdrop]'),
    bodyCollapsed: 'bi-sidebar-collapsed', bodyOpen: 'bi-sidebar-open', storageKey: 'bi-sidebar-collapsed'
  });
  const legacy = setupSidebar({
    nav: document.querySelector('#accordionSidebar.bi-legacy-sidebar'),
    toggle: document.querySelector('#biLegacyNavToggle'),
    backdrop: document.querySelector('[data-bi-legacy-backdrop]'),
    bodyCollapsed: 'bi-legacy-collapsed', bodyOpen: 'bi-legacy-sidebar-open', storageKey: 'bi-legacy-sidebar-collapsed'
  });

  const standardIcons = {
    agregar: 'fa-plus', editar: 'fa-pen-to-square', eliminar: 'fa-trash-can', borrar: 'fa-trash-can',
    ver: 'fa-eye', buscar: 'fa-magnifying-glass', guardar: 'fa-floppy-disk', cancelar: 'fa-xmark',
    cerrar: 'fa-xmark', descargar: 'fa-download', imprimir: 'fa-print', filtrar: 'fa-filter',
    limpiar: 'fa-eraser', regresar: 'fa-arrow-left', configuracion: 'fa-gear', usuario: 'fa-user',
    inventario: 'fa-boxes-stacked', movimiento: 'fa-clock-rotate-left', resguardo: 'fa-file-signature', baja: 'fa-file-circle-minus',
    logout: 'fa-right-from-bracket'
  };
  const actionAliases = [
    [/\b(?:agregar|registrar|nuevo|nueva|crear)\b/i, 'agregar'],
    [/\b(?:editar|modificar)\b/i, 'editar'], [/\b(?:eliminar|borrar)\b/i, 'eliminar'],
    [/\b(?:ver|consultar|detalle|identificar)\b/i, 'ver'], [/\b(?:buscar|busqueda)\b/i, 'buscar'],
    [/\b(?:guardar|actualizar|capturar)\b/i, 'guardar'], [/\b(?:cancelar|cerrar|quitar)\b/i, 'cancelar'],
    [/\bdescargar\b/i, 'descargar'], [/\bimprimir\b/i, 'imprimir'], [/\bfiltrar\b/i, 'filtrar'],
    [/\blimpiar\b/i, 'limpiar'], [/\b(?:regresar|volver)\b/i, 'regresar'],
    [/\b(?:configuracion|ajustes)\b/i, 'configuracion'], [/\busuario\b/i, 'usuario'],
    [/\binventario\b/i, 'inventario'], [/\bmovimiento\b/i, 'movimiento'], [/\bresguardo\b/i, 'resguardo'], [/\bbaja\b/i, 'baja']
  ];
  const normalize = root => {
    const controls = root.matches?.('button, a.btn') ? [root] : [...(root.querySelectorAll?.('button, a.btn') || [])];
    controls.forEach(control => {
      if (control.classList.contains('bi-nav-section') || control.matches('[role="tab"], .navbar-toggler, .bi-topbar-toggle, .bi-legacy-sidebar-toggle')) return;
      if (control.classList.contains('catalog-tree-toggle')) {
        control.setAttribute('title', control.getAttribute('aria-label') || 'Expandir sección');
        return;
      }
      if (control.classList.contains('close')) {
        control.setAttribute('title', control.getAttribute('aria-label') || 'Cerrar');
        return;
      }
      const visibleText = [...control.childNodes].filter(node => node.nodeType === Node.TEXT_NODE).map(node => node.textContent).join(' ').trim();
      const fullText = `${control.getAttribute('aria-label') || ''} ${control.getAttribute('title') || ''} ${control.textContent || ''}`.replace(/\s+/g, ' ').trim();
      const stateControl = control.classList.contains('bi-state-action') || /fa-user-(?:slash|check)|fa-toggle-(?:on|off)/.test(control.innerHTML) || /^(?:Desactivar|Inactivar|Activar)$/i.test(fullText);
      if (stateControl) {
        const current = /\bInactivo\b/i.test(fullText) || /\bActivar\b/i.test(fullText) && !/\b(?:Desactivar|Inactivar)\b/i.test(fullText)
          ? 'Inactivo'
          : (/\bActivo\b/i.test(fullText) || /\b(?:Desactivar|Inactivar)\b/i.test(fullText) || control.getAttribute('aria-checked') === 'true' ? 'Activo' : 'Inactivo');
        control.classList.add('bi-state-switch');
        control.setAttribute('role', 'switch');
        control.setAttribute('aria-checked', String(current === 'Activo'));
        control.setAttribute('aria-label', current);
        control.setAttribute('title', current);
        control.setAttribute('data-bs-title', current);
        if (!control.dataset.biSwitchKeyboardBound) {
          control.addEventListener('keydown', event => {
            if (event.key === ' ' && !control.hasAttribute('aria-readonly')) { event.preventDefault(); control.click(); }
          });
          control.dataset.biSwitchKeyboardBound = 'true';
        }
        control.querySelectorAll('i').forEach(icon => icon.remove());
        control.querySelectorAll('span:not(.visually-hidden)').forEach(span => span.classList.add('visually-hidden'));
        control.childNodes.forEach(node => { if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) node.textContent = ''; });
        return;
      }

      if (!control.matches('.btn, a.btn')) return;
      if (control.classList.contains('bi-button-label')) {
        const accessibleLabel = control.dataset.biLabel || control.getAttribute('aria-label') || control.textContent.trim();
        const tooltipLabel = control.getAttribute('title') || accessibleLabel;
        if (accessibleLabel) control.setAttribute('aria-label', accessibleLabel);
        if (tooltipLabel) {
          control.setAttribute('title', tooltipLabel);
          control.setAttribute('data-bs-title', tooltipLabel);
        }
        control.classList.add('bi-action-with-label');
        return;
      }
      const actionMatch = fullText.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
      const iconMatch = control.querySelector('i')?.className || '';
      const iconAliases = [
        [/fa-(?:plus|user-plus|circle-plus|folder-plus)/, 'agregar'], [/fa-(?:pen|pen-to-square|edit)/, 'editar'],
        [/fa-(?:trash|trash-can|delete)/, 'eliminar'], [/fa-eye/, 'ver'], [/fa-(?:magnifying-glass|search)/, 'buscar'],
        [/fa-(?:floppy-disk|save)/, 'guardar'], [/fa-(?:xmark|times|close)/, 'cancelar'], [/fa-download/, 'descargar'],
        [/fa-print/, 'imprimir'], [/fa-filter/, 'filtrar'], [/fa-eraser/, 'limpiar'], [/fa-arrow-left/, 'regresar'],
        [/fa-(?:gear|sliders)/, 'configuracion'], [/fa-users?\b/, 'usuario'], [/fa-(?:boxes-stacked|laptop|box)/, 'inventario'],
        [/fa-clock-rotate-left/, 'movimiento'], [/fa-file-signature/, 'resguardo'], [/fa-file-circle-minus/, 'baja']
      ];
      const action = /\bcerrar\s+sesion\b/i.test(actionMatch) ? 'logout'
        : actionAliases.find(([pattern]) => pattern.test(actionMatch))?.[1]
        || iconAliases.find(([pattern]) => pattern.test(iconMatch))?.[1];
      const currentLabel = control.getAttribute('aria-label') || control.getAttribute('title') || (visibleText || fullText);
      const label = action ? ({ agregar: 'Agregar', editar: 'Editar', eliminar: 'Eliminar', ver: 'Ver', buscar: 'Buscar', guardar: 'Guardar', cancelar: 'Cancelar', descargar: 'Descargar', imprimir: 'Imprimir', filtrar: 'Filtrar', limpiar: 'Limpiar filtros', regresar: 'Regresar', configuracion: 'Configuración', usuario: 'Usuarios', inventario: 'Inventario', movimiento: 'Movimientos', resguardo: 'Resguardos', baja: 'Bajas', logout: 'Cerrar sesión' }[action]) : currentLabel;
      if (label) {
        control.setAttribute('aria-label', label);
        control.setAttribute('title', label);
        control.setAttribute('data-bs-title', label);
      } else {
        control.setAttribute('aria-label', 'Acción');
        control.setAttribute('title', 'Acción');
        control.setAttribute('data-bs-title', 'Acción');
      }
      if (action) {
        let icon = control.querySelector('i');
        if (!icon) { icon = document.createElement('i'); control.prepend(icon); }
        icon.className = `fa-solid ${standardIcons[action]}`;
        icon.setAttribute('aria-hidden', 'true');
      }
      control.classList.add('bi-icon-action');
      control.querySelectorAll('span:not(.visually-hidden)').forEach(span => span.classList.add('visually-hidden'));
      control.childNodes.forEach(node => { if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) node.textContent = ''; });
    });
    if (window.bootstrap?.Tooltip) root.querySelectorAll?.('[title]:not([data-bs-toggle="popover"]):not([data-bs-toggle="dropdown"]), [data-bs-toggle="tooltip"]:not([data-bs-toggle="popover"]):not([data-bs-toggle="dropdown"])')?.forEach(el => bootstrap.Tooltip.getOrCreateInstance(el, { trigger: 'hover focus' }));
  };
  normalize(document);
  new MutationObserver(records => records.forEach(record => record.addedNodes.forEach(node => { if (node.nodeType === Node.ELEMENT_NODE) normalize(node); })))
    .observe(document.body, { childList: true, subtree: true });
})();
