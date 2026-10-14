(() => {
  const filterForm = document.querySelector('#inventarioFiltros');
  if (filterForm) {
    const queryInput = filterForm.querySelector('[name="q"]');
    const statusSelect = filterForm.querySelector('[name="activo"]');
    const results = document.querySelector('#inventarioResultados');
    let debounceTimer;
    let currentRequest;
    const updateResults = async (updateHistory = true) => {
      currentRequest?.abort();
      currentRequest = new AbortController();
      const params = new URLSearchParams(new FormData(filterForm));
      const visibleParams = new URLSearchParams(params);
      for (const [key, value] of [...visibleParams.entries()]) if (!value) visibleParams.delete(key);
      params.set('ajax', '1');
      try {
        const response = await fetch(`${filterForm.action}?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: currentRequest.signal });
        if (!response.ok) throw new Error('No fue posible actualizar los resultados.');
        const markup = await response.text();
        if (!/<tr[\s>]/i.test(markup)) throw new Error('La respuesta de búsqueda no contiene resultados válidos.');
        if (results) results.innerHTML = markup;
        if (updateHistory) history.replaceState(null, '', `${filterForm.action}${visibleParams.size ? `?${visibleParams}` : ''}`);
      } catch (error) {
        if (error.name !== 'AbortError') console.error(error);
      }
    };
    queryInput?.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => updateResults(), 300);
    });
    statusSelect?.addEventListener('change', () => updateResults());
    filterForm.addEventListener('submit', event => { event.preventDefault(); updateResults(); });
    window.addEventListener('popstate', () => {
      const params = new URLSearchParams(location.search);
      if (queryInput) queryInput.value = params.get('q') || '';
      if (statusSelect) statusSelect.value = params.get('activo') || '';
      updateResults(false);
    });
  }

  document.addEventListener('show.bs.modal', event => {
    if (event.target.id !== 'codigoCiModal') return;
    const trigger = event.relatedTarget;
    const value = trigger?.dataset.ciCode || '';
    const output = document.querySelector('#codigoCiValor');
    if (output) output.textContent = value;
  });
  document.querySelector('[data-print-ci]')?.addEventListener('click', () => window.print());
  document.querySelectorAll('[data-print-qr]').forEach(button => button.addEventListener('click', () => window.print()));

  document.querySelector('[data-print-inventory-report]')?.addEventListener('click', () => {
    const sourceTable = document.querySelector('.bi-inventory-table');
    const sourceBody = document.querySelector('#inventarioResultados');
    const matchingRows = sourceBody ? [...sourceBody.querySelectorAll('tr')].filter(row =>
      !row.hidden && getComputedStyle(row).display !== 'none' && !row.querySelector('[colspan]')
    ) : [];
    if (!sourceTable || matchingRows.length === 0) {
      window.alert('No hay bienes que coincidan con los filtros actuales para imprimir.');
      return;
    }

    const reportWindow = window.open('', '_blank');
    if (!reportWindow) {
      window.alert('Permite las ventanas emergentes para imprimir el reporte.');
      return;
    }
    const reportDocument = reportWindow.document;
    reportDocument.open();
    reportDocument.write('<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Reporte de bienes</title><style>body{font:12px Arial,sans-serif;color:#161A1D;margin:20px}h1{font-size:20px;margin:0 0 4px}p{margin:0 0 16px;color:#555}table{width:100%;border-collapse:collapse}th,td{border:1px solid #bbb;padding:6px;text-align:left;vertical-align:top}th{background:#eee} @page{size:landscape;margin:12mm} @media print{body{margin:0}}</style></head><body></body></html>');
    reportDocument.close();

    const title = reportDocument.createElement('h1');
    title.textContent = 'Reporte de bienes';
    reportDocument.body.append(title);
    const description = reportDocument.createElement('p');
    description.textContent = `${matchingRows.length} ${matchingRows.length === 1 ? 'bien' : 'bienes'} según los filtros actuales.`;
    reportDocument.body.append(description);

    const table = sourceTable.cloneNode(false);
    const head = sourceTable.tHead?.cloneNode(true);
    head?.rows[0]?.deleteCell(-1);
    if (head) table.append(head);
    const body = reportDocument.createElement('tbody');
    matchingRows.forEach(row => {
      const copy = row.cloneNode(true);
      copy.deleteCell(-1);
      body.append(copy);
    });
    table.append(body);
    reportDocument.body.append(table);
    reportWindow.addEventListener('afterprint', () => reportWindow.close(), { once: true });
    reportWindow.focus();
    reportWindow.print();
  });

  const form = document.querySelector('#bienForm');
  if (!form) return;

  const codigoUnidadManual = document.querySelector('#codigoUnidadManual');
  const unidadSeleccionadaId = document.querySelector('#unidadSeleccionadaId');
  const unidadCoincidencias = document.querySelector('#unidadCoincidencias');
  const estadoBusquedaUnidad = document.querySelector('#estadoBusquedaUnidad');
  let temporizadorBusquedaUnidad;
  let solicitudUnidades;
  let codigoSeleccionado = codigoUnidadManual?.value.trim() || '';
  const camposAdministracion = {
    secretaria: '#uaSecretaria', subsecretaria: '#uaSubsecretaria', direccion_secretaria: '#uaDireccion',
    direccion_area: '#uaDireccionArea', delegacion_administrativa: '#uaDelegacionAdministrativa',
    subdireccion: '#uaSubdireccion', departamento: '#uaDepartamento', oficina: '#uaOficina'
  };
  const mostrarAdministracion = administracion => {
    Object.entries(camposAdministracion).forEach(([campo, selector]) => {
      const input = document.querySelector(selector);
      if (input) input.value = administracion?.[campo] || '—';
    });
  };
  const informarBusquedaUnidad = (mensaje = '') => {
    if (!estadoBusquedaUnidad) return;
    estadoBusquedaUnidad.textContent = mensaje;
  };
  const ocultarCoincidencias = () => {
    if (!unidadCoincidencias) return;
    unidadCoincidencias.replaceChildren();
    unidadCoincidencias.hidden = true;
    codigoUnidadManual?.setAttribute('aria-expanded', 'false');
  };
  const seleccionarUnidad = unidad => {
    const codigo = String(unidad?.codigo_ua || '').trim();
    const id = Number(unidad?.id_unidad || 0);
    if (!codigo || !id || Number(unidad.activo) !== 1) return;
    codigoUnidadManual.value = codigo;
    unidadSeleccionadaId.value = String(id);
    codigoSeleccionado = codigo;
    mostrarAdministracion(unidad.administracion || {});
    ocultarCoincidencias();
    informarBusquedaUnidad('Unidad Administrativa seleccionada.');
  };
  const buscarCoincidenciasUnidad = async termino => {
    solicitudUnidades?.abort();
    solicitudUnidades = new AbortController();
    const url = new URL(codigoUnidadManual.dataset.searchUrl, window.location.href);
    url.searchParams.set('q', termino);
    try {
      const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, signal: solicitudUnidades.signal });
      if (!response.ok) throw new Error('No fue posible consultar las Unidades Administrativas.');
      const data = await response.json();
      if (codigoUnidadManual.value.trim() !== termino) return;
      const resultados = Array.isArray(data.results) ? data.results.slice(0, 3) : [];
      unidadCoincidencias.replaceChildren();
      resultados.forEach(unidad => {
        const opcion = document.createElement('button');
        opcion.type = 'button';
        opcion.className = 'list-group-item list-group-item-action';
        opcion.setAttribute('role', 'option');
        opcion.textContent = `${unidad.codigo_ua} - ${unidad.nombre}`;
        opcion.addEventListener('click', () => seleccionarUnidad(unidad));
        unidadCoincidencias.append(opcion);
      });
      if (resultados.length) {
        unidadCoincidencias.hidden = false;
        codigoUnidadManual.setAttribute('aria-expanded', 'true');
        informarBusquedaUnidad('');
      } else {
        ocultarCoincidencias();
        informarBusquedaUnidad('No se encontraron coincidencias.');
      }
    } catch (error) {
      if (error.name !== 'AbortError') informarBusquedaUnidad('No fue posible consultar las Unidades Administrativas.');
    }
  };
  codigoUnidadManual?.addEventListener('input', () => {
    const termino = codigoUnidadManual.value.trim();
    if (termino !== codigoSeleccionado) {
      unidadSeleccionadaId.value = '';
      codigoSeleccionado = '';
      mostrarAdministracion(null);
    }
    ocultarCoincidencias();
    informarBusquedaUnidad('');
    clearTimeout(temporizadorBusquedaUnidad);
    solicitudUnidades?.abort();
    if (termino.length < 3) return;
    temporizadorBusquedaUnidad = setTimeout(() => buscarCoincidenciasUnidad(termino), 250);
  });
  codigoUnidadManual?.addEventListener('keydown', event => {
    if (event.key === 'Escape') ocultarCoincidencias();
    if (event.key === 'Enter' && !unidadCoincidencias.hidden) {
      const primeraCoincidencia = unidadCoincidencias.querySelector('[role="option"]');
      if (primeraCoincidencia) { event.preventDefault(); primeraCoincidencia.click(); }
    }
  });
  document.addEventListener('click', event => {
    if (!unidadCoincidencias?.contains(event.target) && event.target !== codigoUnidadManual) ocultarCoincidencias();
  });
  form.addEventListener('submit', event => {
    if (!unidadSeleccionadaId?.value || codigoUnidadManual?.value.trim() !== codigoSeleccionado) {
      event.preventDefault();
      informarBusquedaUnidad('Selecciona una coincidencia válida de la lista antes de guardar.');
      codigoUnidadManual?.focus();
    }
  });
  if (codigoUnidadManual?.value.trim() && unidadSeleccionadaId?.value) {
    codigoSeleccionado = codigoUnidadManual.value.trim();
  }

  const municipioUbicacion = document.querySelector('#municipioUbicacion');
  const localidadUbicacion = document.querySelector('#localidadUbicacion');
  const localidades = localidadUbicacion ? [...localidadUbicacion.options].map(option => option.cloneNode(true)) : [];
  document.addEventListener('bien:ubicacion-agregada', event => {
    const option = event.detail?.option;
    if (option && !localidades.some(item => item.value === option.value)) localidades.push(option.cloneNode(true));
  });
  const cargarLocalidades = () => {
    if (!localidadUbicacion) return;
    const seleccionActual = localidadUbicacion.value;
    const municipio = municipioUbicacion?.value || '';
    localidadUbicacion.replaceChildren(...localidades.filter(option => !option.value || !municipio || option.dataset.municipio === municipio).map(option => option.cloneNode(true)));
    if ([...localidadUbicacion.options].some(option => option.value === seleccionActual)) localidadUbicacion.value = seleccionActual;
  };
  municipioUbicacion?.addEventListener('change', () => {
    cargarLocalidades();
    localidadUbicacion.value = '';
    const addLocalidad = document.querySelector('#agregarLocalidadBtn');
    if (addLocalidad) addLocalidad.disabled = !municipioUbicacion.value;
  });
  cargarLocalidades();
  const addLocalidad = document.querySelector('#agregarLocalidadBtn');
  if (addLocalidad) addLocalidad.disabled = !municipioUbicacion?.value;

  const hidden = document.querySelector('#componentesInput');
  const list = document.querySelector('#componentesLista');
  const config = document.querySelector('#componentesConfig');
  const tiposIniciales = ['CPU', 'CARGADOR', 'MONITOR', 'MOUSE', 'TECLADO', 'UPS'];
  let componentesExistentes = [];
  try { componentesExistentes = JSON.parse(config?.dataset.componentes || '[]'); } catch (_) { componentesExistentes = []; }
  const usados = new Set();
  const items = tiposIniciales.map(tipo => {
    const existente = componentesExistentes.find(item => !usados.has(item.id_componente) && String(item.tipo_componente || '').trim().toUpperCase() === tipo);
    if (existente) usados.add(existente.id_componente);
    return existente || ({ tipo_componente: tipo, marca: '', modelo: '', numero_serie: '', numero_inventario: '' });
  });

  const input = (value, field, index, listName = '') => {
    const element = document.createElement('input');
    element.className = 'form-control form-control-sm';
    element.value = value || '';
    if (listName) element.setAttribute('list', listName);
    element.addEventListener('input', () => { items[index][field] = element.value; });
    return element;
  };
  const renderComponentes = () => {
    list.replaceChildren();
    items.forEach((item, index) => {
      const row = document.createElement('tr');
      const tipo = document.createElement('td');
      tipo.textContent = item.tipo_componente;
      row.append(tipo);
      [['marca', ''], ['modelo', ''], ['numero_serie', ''], ['numero_inventario', '']].forEach(([field, listName]) => {
        const cell = document.createElement('td');
        cell.append(input(item[field], field, index, listName));
        row.append(cell);
      });
      list.append(row);
    });
  };
  form.addEventListener('submit', () => {
    hidden.value = JSON.stringify(items.filter(item => String(item.tipo_componente || '').trim() !== '' && (item.id_componente || item.marca || item.modelo || item.numero_serie || item.numero_inventario)));
  });
  renderComponentes();
})();
