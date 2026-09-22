(() => {
  const inventorySearch = document.querySelector('#inventarioBusqueda');
  const inventoryFilters = document.querySelector('#inventarioFiltros');
  if (inventorySearch && inventoryFilters) {
    const applyInventoryFilters = () => {
      const query = inventorySearch.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
      const estado = inventoryFilters.elements.estado.value;
      const marca = inventoryFilters.elements.marca.value;
      const activo = inventoryFilters.elements.activo.value;
      document.querySelectorAll('[data-bien-row]').forEach(row => {
        const text = (row.dataset.busqueda || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        row.hidden = !text.includes(query) || (estado && row.dataset.estado !== estado) || (marca && row.dataset.marca !== marca) || (activo !== '' && row.dataset.activo !== activo);
      });
    };
    inventorySearch.addEventListener('input', applyInventoryFilters);
    inventoryFilters.querySelectorAll('select').forEach(select => select.addEventListener('change', applyInventoryFilters));
  }

  const form = document.querySelector('#bienForm');
  if (!form) return;

  const check = document.querySelector('#requiereComponentes');
  const panel = document.querySelector('#componentesPanel');
  const list = document.querySelector('#componentesLista');
  const hidden = document.querySelector('#componentesInput');
  const items = [];

  const generico = document.querySelector('#activoGenerico');
  const grupo = document.querySelector('#grupoActivo');
  const gruposData = document.querySelector('#gruposData');
  const datalist = document.querySelector('#activosEspecificos');
  const todasLasSugerencias = datalist ? [...datalist.querySelectorAll('option')].map(option => option.cloneNode(true)) : [];
  const cargarSugerencias = () => {
    if (!datalist || !grupo) return;
    datalist.replaceChildren(...todasLasSugerencias.filter(option => option.dataset.grupo === grupo.value).map(option => option.cloneNode(true)));
  };
  generico?.addEventListener('change', () => {
    const selected = generico.value;
    grupo.replaceChildren(new Option('Seleccionar...', ''));
    [...gruposData.options].filter(option => option.dataset.generico === selected).forEach(option => grupo.add(new Option(option.text, option.value)));
    document.querySelector('#activoEspecifico').value = '';
    cargarSugerencias();
  });
  grupo?.addEventListener('change', cargarSugerencias);

  const render = () => {
    hidden.value = JSON.stringify(items);
    list.innerHTML = items.map((item, index) => `<div class="alert alert-light border d-flex justify-content-between py-2"><span>${item.tipo_componente} · ${item.marca || 'Sin marca'} · ${item.numero_serie || 'Sin serie'}</span><button class="btn btn-sm btn-outline-danger" type="button" data-index="${index}">Quitar</button></div>`).join('');
    list.querySelectorAll('[data-index]').forEach(button => button.addEventListener('click', () => { items.splice(Number(button.dataset.index), 1); render(); }));
  };
  check?.addEventListener('change', () => panel.classList.toggle('d-none', !check.checked));
  document.querySelector('#agregarComponente')?.addEventListener('click', () => {
    const tipo = window.prompt('Tipo de componente (CPU, monitor, teclado, mouse, UPS, etc.)');
    if (!tipo?.trim()) return;
    items.push({ tipo_componente: tipo.trim(), marca: window.prompt('Marca (opcional)') || '', modelo: window.prompt('Modelo (opcional)') || '', numero_serie: window.prompt('Número de serie (opcional)') || '', activo: 1 });
    render();
  });
})();
