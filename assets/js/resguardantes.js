(() => {
  const mostrarNombreSeleccionado = selector => {
    const nombre = selector?.selectedOptions?.[0]?.dataset.nombre || '';
    if (selector?.id === 'bienResguardante') {
      const output = document.querySelector('#nombreBienResguardante');
      if (output) output.textContent = nombre;
    }
    const output = selector?.parentElement?.querySelector('[data-resguardante-name]');
    if (output) output.textContent = nombre;
  };
  document.querySelectorAll('[data-resguardante-selector]').forEach(selector => {
    selector.addEventListener('change', () => mostrarNombreSeleccionado(selector));
    mostrarNombreSeleccionado(selector);
  });

  document.querySelector('[data-confirm-transfer]')?.addEventListener('submit', event => {
    if (!window.confirm('¿Confirmas reasignar cada bien al resguardante seleccionado y desactivar al resguardante actual?')) event.preventDefault();
  });

  const modalElement = document.querySelector('#resguardanteModal');
  const form = document.querySelector('[data-resguardante-form]');
  if (!modalElement || !form) return;

  const title = modalElement.querySelector('#resguardanteModalLabel');
  const alertBox = document.createElement('div');
  alertBox.className = 'alert alert-danger d-none mx-3 mt-3';
  alertBox.setAttribute('role', 'alert');
  form.querySelector('.modal-body').before(alertBox);

  modalElement.addEventListener('show.bs.modal', event => {
    const trigger = event.relatedTarget;
    alertBox.classList.add('d-none');
    alertBox.textContent = '';
    form.reset();
    form.querySelector('#resguardanteId').value = '';
    if (trigger?.hasAttribute('data-edit-resguardante')) {
      title.textContent = 'Editar resguardante';
      form.querySelector('#resguardanteId').value = trigger.dataset.id || '';
      form.querySelector('#resguardanteCsp').value = trigger.dataset.csp || '';
      form.querySelector('#resguardanteNombre').value = trigger.dataset.nombre || '';
      form.querySelector('#resguardantePaterno').value = trigger.dataset.paterno || '';
      form.querySelector('#resguardanteMaterno').value = trigger.dataset.materno || '';
      form.querySelector('#resguardanteUnidad').value = trigger.dataset.unidad || '';
    } else {
      title.textContent = 'Agregar resguardante';
      form.querySelector('#resguardanteUnidad').value = trigger?.dataset.unidadInicial || '';
    }
  });

  form.addEventListener('submit', async event => {
    if (form.dataset.ajax !== '1') return;
    event.preventDefault();
    alertBox.classList.add('d-none');
    const submit = form.querySelector('[type="submit"]');
    submit.disabled = true;
    try {
      const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.error || 'No fue posible guardar el resguardante.');
      const record = result.resguardante;
      const selector = document.querySelector('select[name="csp"]');
      if (selector && record?.csp) {
        let option = [...selector.options].find(item => item.value === record.csp);
        if (!option) {
          option = new Option(record.csp, record.csp);
          selector.add(option);
        }
        option.textContent = record.csp;
        option.dataset.nombre = record.nombre_completo || '';
        selector.value = record.csp;
        mostrarNombreSeleccionado(selector);
      }
      bootstrap.Modal.getOrCreateInstance(modalElement).hide();
    } catch (error) {
      alertBox.textContent = error.message || 'No fue posible guardar el resguardante.';
      alertBox.classList.remove('d-none');
    } finally {
      submit.disabled = false;
    }
  });

  const editId = new URLSearchParams(location.search).get('editar');
  if (editId) {
    const editButton = [...document.querySelectorAll('[data-edit-resguardante]')].find(button => button.dataset.id === editId);
    if (editButton) bootstrap.Modal.getOrCreateInstance(modalElement).show(editButton);
  }
})();
