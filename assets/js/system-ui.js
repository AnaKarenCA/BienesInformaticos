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

  if (window.bootstrap?.Tooltip) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => bootstrap.Tooltip.getOrCreateInstance(element, { trigger: 'hover focus' }));
  }
})();
