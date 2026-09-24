(() => {
  const normalizar = texto => (texto || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();
  const search = document.querySelector('[data-global-catalog-search]');
  let timer;
  search?.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      const termino = normalizar(search.value.trim());
      document.querySelectorAll('[data-catalog-list]').forEach(list => {
        let visibles = 0;
        list.querySelector('[data-catalog-no-records]')?.classList.toggle('d-none', !!termino);
        list.querySelectorAll('[data-catalog-row]').forEach(row => {
          const coincide = normalizar(row.textContent).includes(termino);
          row.classList.toggle('d-none', !coincide);
          if (coincide) visibles++;
        });
        list.querySelector('[data-search-empty]')?.classList.toggle('d-none', visibles > 0 || !termino);
      });
      document.querySelectorAll('[data-catalog-table]').forEach(table => {
        let visibles = 0;
        table.querySelector('[data-catalog-no-records]')?.classList.toggle('d-none', !!termino);
        table.querySelectorAll('[data-catalog-row]').forEach(row => {
          const coincide = normalizar(row.textContent).includes(termino);
          row.classList.toggle('d-none', !coincide);
          if (coincide) visibles++;
        });
        table.querySelector('[data-search-empty]')?.classList.toggle('d-none', visibles > 0 || !termino);
      });
    }, 180);
  });

  document.addEventListener('show.bs.modal', event => {
    const trigger = event.relatedTarget;
    const tipo = trigger?.dataset.catalogAdd || trigger?.dataset.catalogEdit;
    if (!tipo) return;
    const modal = event.target;
    const form = modal.querySelector(`[data-catalog-form="${tipo}"]`);
    if (!form) return;
    const baseAction = form.dataset.baseAction || form.action;
    form.dataset.baseAction = baseAction;
    form.reset();
    form.action = baseAction;
    const editing = trigger.hasAttribute('data-catalog-edit');
    modal.querySelector('[data-catalog-title]').textContent = `${editing ? 'Editar' : 'Agregar'} ${trigger.dataset.catalogLabel || tipo.replace(/^./, c => c.toUpperCase())}`;
    const submitLabel = modal.querySelector('[data-submit-label]');
    if (submitLabel) submitLabel.textContent = editing ? 'Actualizar' : 'Guardar';
    const parent = form.querySelector('[name="id_activo_generico"], [name="id_grupo_activo"], [name="id_marca"], [name="id_padre"]');
    parent?.querySelectorAll('option[data-active]').forEach(option => {
      option.disabled = option.dataset.active !== '1' && (!editing || option.value !== trigger.dataset.parent);
    });
    if (!editing) return;
    form.action = `${baseAction}/${trigger.dataset.id}`;
    form.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach(field => {
      const attribute = `data-${field.name.replaceAll('_', '-')}`;
      const value = field.name === 'id_padre' || field.name.startsWith('id_') && field.name !== 'id_unidad'
        ? (trigger.dataset.parent ?? trigger.getAttribute(attribute))
        : trigger.getAttribute(attribute);
      if (value !== null && value !== undefined) field.value = value;
    });
  });
})();
