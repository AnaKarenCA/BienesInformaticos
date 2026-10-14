(() => {
  const normalizar = texto => (texto || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();
  const search = document.querySelector('[data-global-catalog-search]');
  const groupPane = document.querySelector('[data-catalog-child-pane="grupos"]');
  const specificPane = document.querySelector('[data-catalog-child-pane="especificos"]');
  const breadcrumb = document.querySelector('[data-catalog-breadcrumb]');
  const lists = [...document.querySelectorAll('[data-catalog-list]')];
  let selectedGenericId = '';
  let selectedGenericName = '';
  let selectedGroupId = '';
  let selectedGroupName = '';

  const renderBreadcrumb = () => {
    if (!breadcrumb) return;
    breadcrumb.replaceChildren();
    const addCrumb = (label, {current = false, action = '', ariaLabel = ''} = {}) => {
      const item = document.createElement('li');
      item.className = `breadcrumb-item${current ? ' active' : ''}`;
      if (current) {
        const text = document.createElement('span');
        text.textContent = label;
        text.setAttribute('aria-current', 'page');
        item.append(text);
      } else {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset[action] = '';
        button.textContent = label;
        if (ariaLabel) button.setAttribute('aria-label', ariaLabel);
        item.append(button);
      }
      breadcrumb.append(item);
    };
    const hasSelection = !!selectedGenericId;
    addCrumb('Clasificación', {current: !hasSelection, action: 'catalogBreadcrumbHome', ariaLabel: 'Volver a Clasificación'});
    if (hasSelection) addCrumb(selectedGenericName, {current: !selectedGroupId, action: 'catalogBreadcrumbGeneric', ariaLabel: `Volver a ${selectedGenericName}`});
    if (selectedGroupId) addCrumb(selectedGroupName, {current: true});
  };

  const applyCatalogView = () => {
    const term = normalizar(search?.value.trim());
    if (!groupPane || !specificPane) {
      [...document.querySelectorAll('[data-catalog-list], [data-catalog-table]')].forEach(list => {
        let visibleRows = 0;
        list.querySelector('[data-catalog-no-records]')?.classList.toggle('d-none', !!term);
        list.querySelectorAll('[data-catalog-row]').forEach(row => {
          const visible = !term || normalizar(row.textContent).includes(term);
          row.classList.toggle('d-none', !visible);
          if (visible) visibleRows++;
        });
        list.querySelector('[data-search-empty]')?.classList.toggle('d-none', visibleRows > 0 || !term);
      });
      return;
    }
    groupPane.hidden = !term && !selectedGenericId;
    specificPane.hidden = !term && !selectedGroupId;
    renderBreadcrumb();
    groupPane.querySelector('[data-catalog-parent-label]').textContent = selectedGenericName || 'Selecciona un activo genérico';
    specificPane.querySelector('[data-catalog-parent-label]').textContent = selectedGroupName || 'Selecciona un grupo de activo';
    groupPane.querySelector('[data-catalog-selection-prompt]').hidden = !!term || !!selectedGenericId;
    specificPane.querySelector('[data-catalog-selection-prompt]').hidden = !!term || !!selectedGroupId;

    lists.filter(list => list.dataset.catalogLevel).forEach(list => {
      const level = list.dataset.catalogLevel;
      let visibleRows = 0;
      const rows = [...list.querySelectorAll('[data-catalog-row]')];
      rows.forEach(row => {
        const relationMatches = !!term || level === 'generico'
          || (level === 'grupo' && row.dataset.parentGenerico === selectedGenericId)
          || (level === 'especifico' && row.dataset.parentGrupo === selectedGroupId);
        const searchMatches = !term || normalizar(row.textContent).includes(term);
        const visible = relationMatches && searchMatches;
        row.classList.toggle('d-none', !visible);
        if (visible) visibleRows++;
      });
      const noRecords = list.querySelector('[data-catalog-no-records]');
      const searchEmpty = list.querySelector('[data-search-empty]');
      const relationEmpty = list.querySelector('[data-catalog-relation-empty]');
      const relevantLevel = level === 'generico' || level === 'grupo' && selectedGenericId || level === 'especifico' && selectedGroupId;
      noRecords?.classList.toggle('d-none', !!term || rows.length > 0 || !relevantLevel);
      searchEmpty?.classList.toggle('d-none', !term || visibleRows > 0);
      relationEmpty?.classList.toggle('d-none', !!term || !relevantLevel || rows.length === 0 || visibleRows > 0);
    });
  };

  search?.addEventListener('input', () => applyCatalogView());

  document.addEventListener('click', event => {
    const homeCrumb = event.target.closest('[data-catalog-breadcrumb-home]');
    if (homeCrumb) {
      selectedGenericId = selectedGenericName = selectedGroupId = selectedGroupName = '';
      document.querySelectorAll('[data-catalog-select-generico], [data-catalog-select-grupo]').forEach(button => button.setAttribute('aria-pressed', 'false'));
      applyCatalogView();
      return;
    }
    const genericCrumb = event.target.closest('[data-catalog-breadcrumb-generic]');
    if (genericCrumb) {
      selectedGroupId = selectedGroupName = '';
      document.querySelectorAll('[data-catalog-select-grupo]').forEach(button => button.setAttribute('aria-pressed', 'false'));
      document.querySelectorAll('[data-catalog-select-generico]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.catalogSelectGenerico === selectedGenericId)));
      applyCatalogView();
      return;
    }
    const genericButton = event.target.closest('[data-catalog-select-generico]');
    if (genericButton) {
      selectedGenericId = genericButton.dataset.catalogSelectGenerico;
      selectedGenericName = genericButton.querySelector('strong')?.textContent.trim() || '';
      selectedGroupId = '';
      selectedGroupName = '';
      document.querySelectorAll('[data-catalog-select-generico]').forEach(button => button.setAttribute('aria-pressed', String(button === genericButton)));
      document.querySelectorAll('[data-catalog-select-grupo]').forEach(button => button.setAttribute('aria-pressed', 'false'));
      applyCatalogView();
      return;
    }

    const groupButton = event.target.closest('[data-catalog-select-grupo]');
    if (groupButton) {
      selectedGenericId = groupButton.dataset.parent;
      selectedGenericName = groupButton.closest('[data-catalog-row]')?.dataset.parentName || '';
      selectedGroupId = groupButton.dataset.catalogSelectGrupo;
      selectedGroupName = groupButton.querySelector('strong')?.textContent.trim() || '';
      document.querySelectorAll('[data-catalog-select-generico]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.catalogSelectGenerico === selectedGenericId)));
      document.querySelectorAll('[data-catalog-select-grupo]').forEach(button => button.setAttribute('aria-pressed', String(button === groupButton)));
      applyCatalogView();
    }
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
    const typeSelect = form.querySelector('[data-unit-type-select]');
    if (typeSelect) typeSelect.required = !editing || !!trigger.getAttribute('data-tipo');
    modal.querySelector('[data-catalog-title]').textContent = `${editing ? 'Editar' : 'Agregar'} ${trigger.dataset.catalogLabel || tipo.replace(/^./, c => c.toUpperCase())}`;
    const submitLabel = modal.querySelector('[data-submit-label]');
    if (submitLabel) submitLabel.textContent = editing ? 'Actualizar' : 'Guardar';
    const parent = form.querySelector('[name="id_activo_generico"], [name="id_grupo_activo"], [name="id_marca"], [name="id_padre"]');
    parent?.querySelectorAll('option[data-active]').forEach(option => {
      option.disabled = option.dataset.active !== '1' && (!editing || option.value !== trigger.dataset.parent);
    });
    if (!editing) {
      const selectedParent = trigger.dataset.parent || (tipo === 'grupo' ? selectedGenericId : tipo === 'especifico' ? selectedGroupId : '');
      const selectableOption = [...(parent?.options || [])].find(option => option.value === selectedParent && !option.disabled);
      if (selectableOption) parent.value = selectableOption.value;
      return;
    }
    form.action = `${baseAction}/${trigger.dataset.id}`;
    form.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach(field => {
      const dataAttributeNames = {nombre: 'name', descripcion: 'description', codigo_ua: 'codigo-ua'};
      const attribute = `data-${dataAttributeNames[field.name] || field.name.replaceAll('_', '-')}`;
      const value = field.name === 'id_padre' || field.name.startsWith('id_') && field.name !== 'id_unidad'
        ? (trigger.dataset.parent ?? trigger.getAttribute(attribute))
        : trigger.getAttribute(attribute);
      if (value !== null && value !== undefined) field.value = value;
    });
  });

  applyCatalogView();
})();
