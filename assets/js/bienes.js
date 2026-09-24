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

  const form = document.querySelector('#bienForm');
  if (!form) return;

  const generico = document.querySelector('#activoGenerico');
  const grupo = document.querySelector('#grupoActivo');
  const gruposData = document.querySelector('#gruposData');
  const activosDatalist = document.querySelector('#activosEspecificos');
  const todasLasSugerencias = activosDatalist ? [...activosDatalist.querySelectorAll('option')].map(option => option.cloneNode(true)) : [];
  const cargarSugerencias = () => {
    if (!activosDatalist || !grupo) return;
    activosDatalist.replaceChildren(...todasLasSugerencias.filter(option => option.dataset.grupo === grupo.value).map(option => option.cloneNode(true)));
  };
  cargarSugerencias();
  generico?.addEventListener('change', () => {
    grupo.replaceChildren(new Option('Seleccionar...', ''));
    [...gruposData.options].filter(option => option.dataset.generico === generico.value).forEach(option => grupo.add(new Option(option.text, option.value)));
    document.querySelector('#activoEspecifico').value = '';
    cargarSugerencias();
  });
  grupo?.addEventListener('change', cargarSugerencias);

  const marca = document.querySelector('#marcaBien');
  const modelo = document.querySelector('#modeloBien');
  const modelosDatalist = document.querySelector('#modelosBien');
  const todosLosModelos = modelosDatalist ? [...modelosDatalist.querySelectorAll('option')].map(option => option.cloneNode(true)) : [];
  const cargarModelos = () => {
    if (!modelosDatalist || !marca) return;
    modelosDatalist.replaceChildren(...todosLosModelos.filter(option => !marca.value || option.dataset.marca === marca.value).map(option => option.cloneNode(true)));
  };
  marca?.addEventListener('change', cargarModelos);

  const unidadesConfig = document.querySelector('#unidadesConfig');
  const codigoUnidad = document.querySelector('#codigoUnidadBusqueda');
  const idUnidad = document.querySelector('#idUnidad');
  const nombreUnidad = document.querySelector('#nombreUnidadSeleccionada');
  const codigoUAMostrado = document.querySelector('#codigoUAMostrado');
  let unidades = [];
  try { unidades = JSON.parse(unidadesConfig?.dataset.unidades || '[]'); } catch (_) { unidades = []; }
  const camposAdministracion = {
    secretaria: '#uaSecretaria', subsecretaria: '#uaSubsecretaria', direccion_secretaria: '#uaDireccion',
    direccion_area: '#uaDireccionArea', delegacion_administrativa: '#uaDelegacionAdministrativa',
    subdireccion: '#uaSubdireccion', departamento: '#uaDepartamento', oficina: '#uaOficina'
  };
  const normalizarUnidad = valor => (valor || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim().toLocaleLowerCase();
  const mostrarUnidad = (unidad, codigoCapturado = '') => {
    Object.entries(camposAdministracion).forEach(([campo, selector]) => {
      const input = document.querySelector(selector);
      if (input) input.value = unidad?.administracion?.[campo] || '';
    });
    if (idUnidad) idUnidad.value = unidad?.id_unidad || '';
    if (nombreUnidad) nombreUnidad.textContent = unidad?.nombre || 'Selecciona una unidad válida de la lista.';
    if (codigoUAMostrado) codigoUAMostrado.textContent = unidad
      ? (unidad.codigo_ua || 'Sin código UA')
      : (codigoCapturado || 'Sin código UA');
  };
  const resolverUnidad = () => {
    const valor = (codigoUnidad?.value || '').trim();
    const buscado = normalizarUnidad(valor);
    const unidad = buscado ? unidades.find(item => {
      const codigo = normalizarUnidad(item.codigo_ua == null ? '' : String(item.codigo_ua));
      const nombre = normalizarUnidad(item.nombre == null ? '' : String(item.nombre));
      return (codigo !== '' && codigo === buscado) || nombre === buscado;
    }) : null;
    if (unidad && codigoUnidad && String(codigoUnidad.value).trim() !== String(unidad.codigo_ua || '')) {
      codigoUnidad.value = unidad.codigo_ua || '';
    }
    mostrarUnidad(unidad, valor);
  };
  codigoUnidad?.addEventListener('input', resolverUnidad);
  if (idUnidad?.value) {
    const unidadInicial = unidades.find(item => String(item.id_unidad) === idUnidad.value);
    if (unidadInicial && codigoUnidad) codigoUnidad.value = unidadInicial.codigo_ua || '';
    mostrarUnidad(unidadInicial, codigoUnidad?.value || '');
  } else if (codigoUnidad?.value) resolverUnidad();

  const municipioUbicacion = document.querySelector('#municipioUbicacion');
  const localidadUbicacion = document.querySelector('#localidadUbicacion');
  const localidades = localidadUbicacion ? [...localidadUbicacion.options].map(option => option.cloneNode(true)) : [];
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
  });
  cargarLocalidades();

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
