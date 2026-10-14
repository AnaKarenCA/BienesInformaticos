(() => {
  const root = document.querySelector('[data-transfer-list]');
  const form = root?.querySelector('[data-transfer-filter-form]');
  const data = root?.querySelector('[data-transfer-units]');
  if (!root || !form || !data) return;
  let units = [];
  try { units = JSON.parse(data.textContent || '[]'); } catch (_) { units = []; }
  const close = box => { box.hidden = true; box.previousElementSibling?.setAttribute('aria-expanded', 'false'); };
  const show = (wrap, term) => {
    const input = wrap.querySelector('input'); const box = wrap.querySelector('.transfer-results');
    const q = term.trim().toLocaleLowerCase();
    const matches = q ? units.filter(unit => `${unit.nombre || ''} ${unit.codigo_ua || ''}`.toLocaleLowerCase().includes(q)).slice(0, 8) : [];
    box.replaceChildren();
    if (!matches.length) { close(box); return; }
    matches.forEach(unit => {
      const button = document.createElement('button'); button.type = 'button'; button.className = 'transfer-result'; button.setAttribute('role', 'option');
      const name = document.createElement('strong'); name.textContent = unit.nombre || 'Unidad administrativa';
      const code = document.createElement('small'); code.textContent = `Código: ${unit.codigo_ua || 'Sin código oficial'}`;
      button.append(name, code); button.addEventListener('click', () => { input.value = unit.nombre || unit.codigo_ua || ''; close(box); }); box.append(button);
    });
    box.hidden = false; input.setAttribute('aria-expanded', 'true');
  };
  root.querySelectorAll('[data-filter-unit]').forEach(wrap => {
    const input = wrap.querySelector('input'); const box = wrap.querySelector('.transfer-results');
    let debounceTimer;
    input.addEventListener('input', () => { window.clearTimeout(debounceTimer); debounceTimer = window.setTimeout(() => show(wrap, input.value), 180); });
    input.addEventListener('focus', () => show(wrap, input.value));
    input.addEventListener('blur', () => window.setTimeout(() => close(box), 160));
  });
  form.querySelectorAll('select,input[type="date"]').forEach(field => field.addEventListener('change', () => form.requestSubmit()));
})();
