(() => {
  const form = document.querySelector('#bienForm');
  if (!form) return;
  const generico = document.querySelector('#activoGenerico');
  const grupo = document.querySelector('#grupoActivo');
  const especifico = document.querySelector('#activoEspecifico');
  const gruposData = document.querySelector('#gruposData');
  const especificosData = document.querySelector('#especificosData');
  const marca = document.querySelector('#marcaBien');
  const modelo = document.querySelector('#modeloBien');
  const todosLosGrupos = [...(gruposData?.options || []), ...(grupo?.options || [])];
  const todosLosEspecificos = [...(especificosData?.options || []), ...(especifico?.options || [])];
  const todosLosModelos = [...(modelo?.options || [])];
  const uniques = options => [...new Map(options.filter(o => o.value).map(o => [o.value, o.cloneNode(true)])).values()];
  const opcionesGrupo = uniques(todosLosGrupos);
  const opcionesEspecifico = uniques(todosLosEspecificos);
  const opcionesModelo = uniques(todosLosModelos);
  const placeholder = select => select?.options[0]?.cloneNode(true) || new Option('Seleccionar...', '');
  const poblar = (select, source, predicate, keep) => {
    if (!select) return;
    const selected = keep ? select.value : '';
    select.replaceChildren(placeholder(select), ...source.filter(predicate).map(option => option.cloneNode(true)));
    if (selected && [...select.options].some(option => option.value === selected)) select.value = selected;
    else if (!keep) select.value = '';
  };
  const cargarGrupos = keep => {
    const selected = keep ? grupo?.value : '';
    poblar(grupo, opcionesGrupo, option => option.dataset.generico === generico?.value && (option.dataset.activo !== '0' || option.value === selected), keep);
    if (grupo) { grupo.disabled = !generico?.value; grupo.options[0].textContent = generico?.value ? 'Seleccionar...' : 'Seleccione primero un Activo Genérico'; }
    const genericActive = generico?.selectedOptions[0]?.dataset.activo !== '0';
    const add = document.querySelector('#agregarGrupoBtn'); if (add) add.disabled = !generico?.value || !genericActive;
  };
  const cargarEspecificos = keep => {
    const selected = keep ? especifico?.value : '';
    poblar(especifico, opcionesEspecifico, option => option.dataset.grupo === grupo?.value && (option.dataset.activo !== '0' || option.value === selected), keep);
    if (especifico) { especifico.disabled = !grupo?.value; especifico.options[0].textContent = grupo?.value ? 'Seleccionar...' : 'Seleccione primero un Grupo del Activo'; }
    const groupActive = grupo?.selectedOptions[0]?.dataset.activo !== '0';
    const add = document.querySelector('#agregarEspecificoBtn'); if (add) add.disabled = !grupo?.value || !groupActive;
  };
  const cargarModelos = keep => {
    const selected = keep ? modelo?.value : '';
    poblar(modelo, opcionesModelo, option => option.dataset.marca === marca?.value && (option.dataset.activo !== '0' || option.value === selected), keep);
    const add = document.querySelector('#agregarModeloBtn');
    if (add) add.disabled = !marca?.value || marca.selectedOptions[0]?.dataset.activo === '0';
  };
  generico?.addEventListener('change', () => { cargarGrupos(false); cargarEspecificos(false); });
  grupo?.addEventListener('change', () => cargarEspecificos(false));
  marca?.addEventListener('change', () => cargarModelos(false));
  cargarGrupos(true); cargarEspecificos(true); cargarModelos(true);

  const modalGrupoGenerico = document.querySelector('#nuevoGrupoGenerico');
  const modalEspecificoGenerico = document.querySelector('#nuevoEspecificoGenerico');
  const modalEspecificoGrupo = document.querySelector('#nuevoEspecificoGrupo');
  const filtrarGruposModal = () => {
    const actual = modalEspecificoGrupo?.value;
    poblar(modalEspecificoGrupo, opcionesGrupo, option => option.dataset.generico === modalEspecificoGenerico?.value && option.dataset.activo !== '0', true);
    if (actual && [...modalEspecificoGrupo.options].some(o => o.value === actual)) modalEspecificoGrupo.value = actual;
  };
  modalEspecificoGenerico?.addEventListener('change', filtrarGruposModal);
  document.addEventListener('show.bs.modal', event => {
    if (event.target.id === 'nuevoGrupoModal' && modalGrupoGenerico) {
      modalGrupoGenerico.value = generico?.value || '';
      [...modalGrupoGenerico.options].forEach(option => { option.disabled = option.value !== '' && option.dataset.activo !== '1'; });
    }
    if (event.target.id === 'nuevoEspecificoModal') {
      if (modalEspecificoGenerico) modalEspecificoGenerico.value = generico?.value || '';
      [...modalEspecificoGenerico.options].forEach(option => { option.disabled = option.value !== '' && option.dataset.activo !== '1'; });
      filtrarGruposModal();
      if (modalEspecificoGrupo) modalEspecificoGrupo.value = grupo?.value || '';
    }
    if (event.target.id === 'nuevoModeloModal') {
      const modalMarca = document.querySelector('#nuevoModeloMarca');
      if (modalMarca) modalMarca.value = marca?.value || '';
      [...(modalMarca?.options || [])].forEach(option => { option.disabled = option.value !== '' && option.dataset.activo === '0'; });
    }
    if (event.target.id === 'nuevaLocalidadModal') {
      const municipioActual = document.querySelector('#municipioUbicacion')?.value || '';
      const municipioInput = document.querySelector('#nuevaLocalidadMunicipio');
      const municipioLabel = document.querySelector('#nuevaLocalidadMunicipioLabel');
      if (municipioInput) municipioInput.value = municipioActual;
      if (municipioLabel) municipioLabel.textContent = municipioActual;
    }
  });

  const appendOption = (select, id, name, attributes = {}) => {
    if (!select || [...select.options].some(option => option.value === String(id))) return;
    const option = new Option(name, id);
    Object.entries(attributes).forEach(([key, value]) => { option.dataset[key] = value; });
    select.add(option);
  };
  const makeOption = (id, name, attributes = {}) => {
    const option = new Option(name, id);
    Object.entries(attributes).forEach(([key, value]) => { option.dataset[key] = value; });
    return option;
  };
  const addSimpleCatalogEntry = (select, id, name, parentKey, parentValue) => {
    const option = makeOption(id, name, parentKey ? { [parentKey]: parentValue, activo: '1' } : { activo: '1' });
    if (select && ![...select.options].some(item => item.value === String(id))) select.add(option);
    if (select) select.value = String(id);
    return option;
  };
  const municipality = document.querySelector('#municipioUbicacion');
  const locality = document.querySelector('#localidadUbicacion');
  const addLocation = (row, selectMunicipality) => {
    if (!row?.id_ubicacion || !row.municipio || !row.localidad) return;
    if (municipality && ![...municipality.options].some(option => option.value === row.municipio)) municipality.add(new Option(row.municipio, row.municipio));
    if (selectMunicipality && municipality) municipality.value = row.municipio;
    const option = makeOption(row.id_ubicacion, row.localidad, { municipio: row.municipio, activo: '1' });
    if (locality && ![...locality.options].some(item => item.value === String(row.id_ubicacion))) locality.add(option.cloneNode(true));
    document.dispatchEvent(new CustomEvent('bien:ubicacion-agregada', { detail: { option, selectMunicipality } }));
    if (selectMunicipality && municipality) municipality.dispatchEvent(new Event('change', { bubbles: true }));
    if (locality) locality.value = String(row.id_ubicacion);
  };
  document.querySelectorAll('[data-bien-quick-add]').forEach(quickForm => {
    quickForm.addEventListener('submit', async event => {
      event.preventDefault();
      const submit = quickForm.querySelector('[type="submit"]');
      const error = quickForm.querySelector('[data-quick-error]');
      if (error) error.textContent = '';
      if (submit) submit.disabled = true;
      try {
        const response = await fetch(quickForm.action, { method: 'POST', body: new FormData(quickForm), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.error || 'No fue posible guardar el registro.');
        const row = result.registro;
        switch (quickForm.dataset.bienQuickAdd) {
          case 'generico':
            appendOption(generico, row.id_activo_generico, row.nombre);
            generico.value = row.id_activo_generico;
            cargarGrupos(false); cargarEspecificos(false);
            break;
          case 'grupo':
            if (!opcionesGrupo.some(option => option.value === String(row.id_grupo_activo))) opcionesGrupo.push(makeOption(row.id_grupo_activo, row.nombre, { generico: row.id_activo_generico }));
            appendOption(gruposData, row.id_grupo_activo, row.nombre, { generico: row.id_activo_generico });
            appendOption(grupo, row.id_grupo_activo, row.nombre, { generico: row.id_activo_generico });
            generico.value = row.id_activo_generico;
            cargarGrupos(false); grupo.value = row.id_grupo_activo; cargarEspecificos(false);
            break;
          case 'especifico':
            if (!opcionesEspecifico.some(option => option.value === String(row.id_activo_especifico))) opcionesEspecifico.push(makeOption(row.id_activo_especifico, row.nombre, { grupo: row.id_grupo_activo }));
            appendOption(especificosData, row.id_activo_especifico, row.nombre, { grupo: row.id_grupo_activo });
            appendOption(especifico, row.id_activo_especifico, row.nombre, { grupo: row.id_grupo_activo });
            generico.value = modalEspecificoGenerico.value;
            cargarGrupos(false); grupo.value = row.id_grupo_activo; cargarEspecificos(false); especifico.value = row.id_activo_especifico;
            break;
          case 'marca':
            addSimpleCatalogEntry(marca, row.id_marca, row.nombre);
            cargarModelos(false);
            break;
          case 'modelo':
            if (!opcionesModelo.some(option => option.value === String(row.id_modelo))) opcionesModelo.push(makeOption(row.id_modelo, row.nombre, { marca: row.id_marca, activo: '1' }));
            addSimpleCatalogEntry(modelo, row.id_modelo, row.nombre, 'marca', row.id_marca);
            cargarModelos(true);
            modelo.value = String(row.id_modelo);
            break;
          case 'material':
            addSimpleCatalogEntry(document.querySelector('#materialBien'), row.id_material, row.nombre);
            break;
          case 'color':
            addSimpleCatalogEntry(document.querySelector('#colorBien'), row.id_color, row.nombre);
            break;
          case 'municipio':
            addLocation(row, true);
            break;
          case 'localidad':
            addLocation(row, false);
            break;
        }
        quickForm.reset();
        bootstrap.Modal.getOrCreateInstance(quickForm.closest('.modal')).hide();
        if (result.duplicado) window.alert(result.mensaje);
      } catch (requestError) {
        if (error) error.textContent = requestError.message;
      } finally {
        if (submit) submit.disabled = false;
      }
    });
  });
})();
