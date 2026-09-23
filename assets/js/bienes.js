(() => {
  const inventorySearch = document.querySelector('#inventarioBusqueda');
  const inventoryFilters = document.querySelector('#inventarioFiltros');
  if (inventorySearch && inventoryFilters) {
    const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const applyInventoryFilters = () => {
      const query = normalize(inventorySearch.value);
      const { estado, marca, activo } = inventoryFilters.elements;
      document.querySelectorAll('[data-bien-row]').forEach(row => {
        const text = normalize(row.dataset.busqueda || '');
        row.hidden = !text.includes(query) || (estado.value && row.dataset.estado !== estado.value) || (marca.value && row.dataset.marca !== marca.value) || (activo.value !== '' && row.dataset.activo !== activo.value);
      });
    };
    inventorySearch.addEventListener('input', applyInventoryFilters);
    inventoryFilters.querySelectorAll('select').forEach(select => select.addEventListener('change', applyInventoryFilters));
  }

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
  let unidades = [];
  try { unidades = JSON.parse(unidadesConfig?.dataset.unidades || '[]'); } catch (_) { unidades = []; }
  const camposAdministracion = {
    secretaria: '#uaSecretaria', subsecretaria: '#uaSubsecretaria', direccion_secretaria: '#uaDireccion',
    direccion_area: '#uaDireccionArea', delegacion_administrativa: '#uaDelegacionAdministrativa',
    subdireccion: '#uaSubdireccion', departamento: '#uaDepartamento', oficina: '#uaOficina'
  };
  const mostrarUnidad = unidad => {
    Object.entries(camposAdministracion).forEach(([campo, selector]) => {
      const input = document.querySelector(selector);
      if (input) input.value = unidad?.administracion?.[campo] || '';
    });
    if (idUnidad) idUnidad.value = unidad?.id_unidad || '';
    if (nombreUnidad) nombreUnidad.textContent = unidad ? `${unidad.codigo_ua} · ${unidad.nombre}` : 'Selecciona una clave válida de la lista.';
  };
  const resolverUnidad = () => {
    const valor = (codigoUnidad?.value || '').trim().toLocaleLowerCase();
    const unidad = unidades.find(item => item.codigo_ua.toLocaleLowerCase() === valor || item.nombre.toLocaleLowerCase() === valor);
    mostrarUnidad(unidad);
  };
  codigoUnidad?.addEventListener('input', resolverUnidad);
  codigoUnidad?.addEventListener('change', resolverUnidad);
  if (idUnidad?.value) mostrarUnidad(unidades.find(item => String(item.id_unidad) === idUnidad.value));

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
    const existente = componentesExistentes.find(item => !usados.has(item.id_componente) && String(item.tipo_componente || '').toUpperCase() === tipo);
    if (existente) usados.add(existente.id_componente);
    return existente || ({ tipo_componente: tipo, marca: '', modelo: '', numero_serie: '', numero_inventario: '' });
  });
  componentesExistentes.filter(item => !usados.has(item.id_componente)).forEach(item => items.push(item));

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
      [['tipo_componente', 'tiposComponente'], ['marca', ''], ['modelo', ''], ['numero_serie', ''], ['numero_inventario', '']].forEach(([field, listName]) => {
        const cell = document.createElement('td');
        cell.append(input(item[field], field, index, listName));
        row.append(cell);
      });
      const actions = document.createElement('td');
      const remove = document.createElement('button');
      remove.type = 'button'; remove.className = 'btn btn-sm btn-outline-danger'; remove.title = 'Quitar componente'; remove.innerHTML = '<i class="fa-solid fa-trash"></i>';
      remove.addEventListener('click', () => { items.splice(index, 1); renderComponentes(); });
      actions.append(remove); row.append(actions); list.append(row);
    });
  };
  document.querySelector('#agregarComponente')?.addEventListener('click', () => {
    items.push({ tipo_componente: '', marca: '', modelo: '', numero_serie: '', numero_inventario: '' });
    renderComponentes();
  });
  form.addEventListener('submit', () => {
    hidden.value = JSON.stringify(items.filter(item => String(item.tipo_componente || '').trim() !== '' && (item.id_componente || item.marca || item.modelo || item.numero_serie || item.numero_inventario)));
  });
  renderComponentes();
})();
