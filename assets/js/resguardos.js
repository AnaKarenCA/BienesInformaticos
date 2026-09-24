(() => {
  const search = document.querySelector('[data-resguardo-search]');
  const state = document.querySelector('[data-resguardo-state]');
  const cards = [...document.querySelectorAll('[data-resguardo-card]')];
  const empty = document.querySelector('[data-resguardo-empty]');
  const count = document.querySelector('[data-resguardo-count]');
  const normalize = value => (value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase().trim();
  let timer;

  const filter = () => {
    const query = normalize(search?.value || '');
    let visible = 0;
    cards.forEach(card => {
      const matches = normalize(card.dataset.search).includes(query)
        && (!state?.value || card.dataset.state === state.value);
      card.classList.toggle('d-none', !matches);
      if (matches) visible++;
    });
    empty?.classList.toggle('d-none', visible > 0 || cards.length === 0);
    if (count) count.textContent = `${visible} resguardantes`;
  };

  search?.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(filter, 120);
  });
  state?.addEventListener('change', filter);
  filter();
  document.addEventListener('DOMContentLoaded', () => {
    if (!window.bootstrap?.Tooltip) return;
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
      bootstrap.Tooltip.getOrCreateInstance(element);
    });
  });
})();
