(() => {
  const form = document.querySelector('[data-transfer-create]');
  if (!form) return;
  const json = selector => { try { return JSON.parse(form.querySelector(selector)?.textContent || '[]'); } catch (_) { return []; } };
  const units = json('[data-transfer-units]').filter(item => Number(item.activo ?? 1) === 1);
  const keepers = json('[data-transfer-keepers]');
  const steps = [...form.querySelectorAll('[data-transfer-step]')];
  const progress = [...form.querySelectorAll('[data-transfer-progress] .transfer-step')];
  const kindInputs = [...form.querySelectorAll('input[name="tipo"]')];
  const originInput = form.querySelector('#transferSourceKeeperSearch');
  const originId = form.querySelector('[data-origin-keeper]');
  const originUnit = form.querySelector('[data-origin-unit]');
  const targetUnit = form.querySelector('[data-target-unit]');
  const targetInput = form.querySelector('#targetUnitSearch');
  const targetKeeperInput = form.querySelector('#transferNewKeeperSearch');
  const targetKeeperId = form.querySelector('[data-target-keeper]');
  const addTargetKeeper = form.querySelector('[data-add-target-keeper]');
  const unitWrap = form.querySelector('[data-unit-destination-wrap]');
  const officeBlocks = [...form.querySelectorAll('[data-office-section]')];
  const officeFile = form.querySelector('#transferOffice');
  const officeNumber = form.querySelector('#transferOfficeNumber');
  const officeDate = form.querySelector('#transferOfficeDate');
  const goodsList = form.querySelector('[data-goods-list]');
  const goodsSearch = form.querySelector('#goodsSearch');
  const count = form.querySelector('[data-transfer-selected-count]');
  const allCurrent = form.querySelector('[data-select-all-current]');
  const locationsTemplate = form.querySelector('[data-location-options]');
  const feedback = form.querySelector('[data-transfer-feedback]');
  const feedbackMessage = form.querySelector('[data-transfer-feedback-message]');
  let activeStep = 1, goods = [], selected = new Set((form.dataset.preselectedGoods || '').split(',').filter(Boolean)), locations = new Map(), officeUrl = null, goodsRequest = 0, targetKeeperRequest = 0, targetKeeperTimer = null;

  const chosenKind = () => kindInputs.find(input => input.checked)?.value || '';
  const betweenUnits = () => chosenKind() === 'ENTRE_UNIDADES';
  const clearError = () => { feedback.hidden = true; feedbackMessage.textContent = ''; };
  const error = message => { feedbackMessage.textContent = message; feedback.hidden = false; feedback.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); };
  const showStep = step => {
    activeStep = step; clearError();
    steps.forEach(section => { section.hidden = Number(section.dataset.transferStep) !== step; });
    progress.forEach((item, index) => { const number = index + 1; item.classList.toggle('is-current', number === step); item.classList.toggle('is-complete', number < step); item.removeAttribute('aria-current'); if (number === step) item.setAttribute('aria-current', 'step'); item.querySelector('.transfer-step__number').textContent = number < step ? '✓' : number; });
  };
  const matches = (items, term, fields) => {
    const normalized = term.trim().toLocaleLowerCase(); if (!normalized) return items.slice(0, 8);
    return items.filter(item => fields.map(field => String(item[field] || '')).join(' ').toLocaleLowerCase().includes(normalized)).slice(0, 8);
  };
  const closeResults = input => { const box = input.closest('.transfer-autocomplete')?.querySelector('.transfer-results'); if (box) box.hidden = true; input.setAttribute('aria-expanded', 'false'); };
  const configureAutocomplete = (input, getItems, fields, draw, choose) => {
    const wrap = input.closest('.transfer-autocomplete'); const results = wrap.querySelector('.transfer-results');
    let debounceTimer;
    const render = () => {
      const found = matches(getItems(), input.value, fields); results.replaceChildren();
      if (!found.length || input.disabled) { closeResults(input); return; }
      found.forEach(item => { const button = document.createElement('button'); button.type = 'button'; button.className = 'transfer-result'; button.setAttribute('role', 'option'); draw(button, item); let handled = false; const select = event => { if (handled) return; handled = true; event?.preventDefault(); choose(item); closeResults(input); }; button.addEventListener('pointerdown', select); button.addEventListener('click', select); results.append(button); });
      results.hidden = false; input.setAttribute('aria-expanded', 'true');
    };
    input.addEventListener('focus', render); input.addEventListener('input', () => { window.clearTimeout(debounceTimer); debounceTimer = window.setTimeout(render, 180); }); input.addEventListener('keydown', event => { if (event.key === 'Escape') closeResults(input); }); input.addEventListener('blur', () => window.setTimeout(() => closeResults(input), 160));
  };
  const keeperResult = (button, item) => {
    const name = document.createElement('strong'); name.textContent = item.nombre_completo || 'Resguardante';
    const csp = document.createElement('small'); csp.textContent = `CSP: ${item.csp || 'Sin CSP'}`;
    const ua = document.createElement('small'); ua.textContent = `Unidad: ${item.unidad_nombre || 'Sin Unidad Administrativa'} · Código UA: ${item.unidad_codigo || 'Sin código oficial'}`;
    button.append(name, csp, ua);
  };
  const unitResult = (button, item) => { const name = document.createElement('strong'); name.textContent = item.nombre || 'Unidad administrativa'; const code = document.createElement('small'); code.textContent = `Código: ${item.codigo_ua || 'Sin código oficial'}`; button.append(name, code); };
  const setSelection = (selector, name, meta) => { const card = form.querySelector(selector); card.querySelector('[data-origin-selection-name],[data-target-unit-name],[data-target-keeper-name]')?.replaceChildren(document.createTextNode(name)); card.querySelector('[data-origin-selection-meta],[data-target-unit-meta],[data-target-keeper-meta]')?.replaceChildren(document.createTextNode(meta)); card.hidden = false; };
  const selectedOrigin = () => { const id = String(originId.value || ''); return id !== '' && id === String(originInput.dataset.selectedKeeperId || '') && keepers.some(item => String(item.id_resguardante) === id); };
  const clearOrigin = (keepText = false) => { goodsRequest += 1; if (!keepText) originInput.value = ''; delete originInput.dataset.selectedKeeperId; originId.value = ''; originUnit.value = ''; goods = []; selected.clear(); locations.clear(); renderGoods(); if (!betweenUnits()) clearTargetUnit(); form.querySelector('[data-origin-selection]').hidden = true; };
  const upsertKeeper = item => { const normalized = { ...item, unidad_codigo: item.unidad_codigo || item.unidad_codigo_ua || '' }; const index = keepers.findIndex(current => String(current.id_resguardante) === String(normalized.id_resguardante)); if (index === -1) keepers.push(normalized); else keepers[index] = normalized; return normalized; };
  const selectedTargetKeeper = () => { const id = String(targetKeeperId.value || ''); return id !== '' && id === String(targetKeeperInput.dataset.selectedKeeperId || '') && keepers.some(item => String(item.id_resguardante) === id && String(item.id_unidad) === String(targetUnit.value)); };
  const clearTargetKeeper = (keepText = false) => { targetKeeperRequest += 1; if (!keepText) targetKeeperInput.value = ''; delete targetKeeperInput.dataset.selectedKeeperId; targetKeeperId.value = ''; targetKeeperInput.disabled = !targetUnit.value; form.querySelector('[data-target-keeper-selection]').hidden = true; };
  const clearTargetUnit = () => { targetUnit.value = ''; targetInput.value = ''; form.querySelector('[data-target-unit-selection]').hidden = true; clearTargetKeeper(); if (addTargetKeeper) addTargetKeeper.disabled = true; };
  const chooseOrigin = item => { const id = String(item.id_resguardante || ''); if (!id) return; originInput.value = item.nombre_completo || ''; originInput.dataset.selectedKeeperId = id; originId.value = id; originUnit.value = item.id_unidad; setSelection('[data-origin-selection]', item.nombre_completo || '', `CSP: ${item.csp || 'Sin CSP'} · Unidad: ${item.unidad_nombre || 'Sin UA'} · Código UA: ${item.unidad_codigo || 'Sin código oficial'}`); if (!betweenUnits()) setTargetUnit(units.find(unit => String(unit.id_unidad) === String(item.id_unidad))); loadGoods(); };
  const setTargetUnit = unit => { if (!unit) { clearTargetUnit(); return; } targetUnit.value = unit.id_unidad; targetInput.value = unit.nombre || ''; setSelection('[data-target-unit-selection]', unit.nombre || '', `Código: ${unit.codigo_ua || 'Sin código oficial'}`); clearTargetKeeper(); targetKeeperInput.disabled = false; if (addTargetKeeper) addTargetKeeper.disabled = false; };
  const chooseTargetKeeper = raw => { const item = upsertKeeper(raw); const id = String(item.id_resguardante || ''); if (!id || String(item.id_unidad) !== String(targetUnit.value)) return; targetKeeperInput.value = item.nombre_completo || ''; targetKeeperInput.dataset.selectedKeeperId = id; targetKeeperId.value = id; setSelection('[data-target-keeper-selection]', item.nombre_completo || '', `CSP: ${item.csp || 'Sin CSP'} · Unidad: ${item.unidad_nombre || 'Sin UA'} · Código UA: ${item.unidad_codigo || 'Sin código oficial'}`); };
  configureAutocomplete(originInput, () => keepers, ['nombre_completo','csp','unidad_nombre','unidad_codigo'], keeperResult, chooseOrigin);
  configureAutocomplete(targetInput, () => units.filter(unit => String(unit.id_unidad) !== String(originUnit.value)), ['nombre','codigo_ua'], unitResult, setTargetUnit);
  originInput.addEventListener('input', () => { if (originId.value || originInput.dataset.selectedKeeperId) clearOrigin(true); });
  targetInput.addEventListener('input', () => { const current = units.find(item => String(item.id_unidad) === String(targetUnit.value)); if (current && targetInput.value !== current.nombre) { targetUnit.value = ''; form.querySelector('[data-target-unit-selection]').hidden = true; clearTargetKeeper(); } });
  const renderTargetKeeperResults = async () => {
    const wrap = targetKeeperInput.closest('.transfer-autocomplete'); const results = wrap.querySelector('.transfer-results'); const unitId = String(targetUnit.value || ''); const request = ++targetKeeperRequest;
    results.replaceChildren();
    if (!unitId || targetKeeperInput.disabled) { closeResults(targetKeeperInput); return; }
    try {
      const url = new URL(`${form.dataset.targetKeepersUrl}${encodeURIComponent(unitId)}`, window.location.href); url.searchParams.set('q', targetKeeperInput.value.trim());
      const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } }); const data = await response.json();
      if (request !== targetKeeperRequest || unitId !== String(targetUnit.value)) return;
      if (!response.ok || !data.success) throw new Error(data.message || 'No fue posible buscar resguardantes.');
      const found = (Array.isArray(data.results) ? data.results : []).map(upsertKeeper).filter(item => String(item.id_resguardante) !== String(originId.value));
      if (!found.length) { const empty = document.createElement('p'); empty.className = 'small text-muted mb-0 p-3'; empty.textContent = 'No hay resguardantes activos que coincidan en la UA destino.'; results.append(empty); results.hidden = false; targetKeeperInput.setAttribute('aria-expanded', 'true'); return; }
      found.forEach(item => { const button = document.createElement('button'); button.type = 'button'; button.className = 'transfer-result'; button.setAttribute('role', 'option'); keeperResult(button, item); let handled = false; const select = event => { if (handled) return; handled = true; event?.preventDefault(); chooseTargetKeeper(item); closeResults(targetKeeperInput); }; button.addEventListener('pointerdown', select); button.addEventListener('click', select); results.append(button); });
      results.hidden = false; targetKeeperInput.setAttribute('aria-expanded', 'true');
    } catch (_) { if (request !== targetKeeperRequest) return; closeResults(targetKeeperInput); }
  };
  targetKeeperInput.addEventListener('focus', renderTargetKeeperResults);
  targetKeeperInput.addEventListener('input', () => { if (targetKeeperId.value || targetKeeperInput.dataset.selectedKeeperId) clearTargetKeeper(true); window.clearTimeout(targetKeeperTimer); targetKeeperTimer = window.setTimeout(renderTargetKeeperResults, 180); });
  targetKeeperInput.addEventListener('keydown', event => { if (event.key === 'Escape') closeResults(targetKeeperInput); });
  targetKeeperInput.addEventListener('blur', () => window.setTimeout(() => closeResults(targetKeeperInput), 160));
  form.querySelector('[data-clear-origin]').addEventListener('click', () => clearOrigin()); form.querySelector('[data-clear-target-unit]').addEventListener('click', clearTargetUnit); form.querySelector('[data-clear-target-keeper]').addEventListener('click', () => clearTargetKeeper()); form.querySelector('[data-transfer-feedback-close]').addEventListener('click', clearError);

  const updateKind = () => {
    const same = chosenKind() === 'MISMA_UNIDAD'; unitWrap.hidden = same; officeBlocks.forEach(block => block.hidden = !betweenUnits()); [officeFile, officeNumber, officeDate].forEach(field => { field.required = betweenUnits(); field.disabled = !betweenUnits(); });
    if (same && originUnit.value) { setTargetUnit(units.find(unit => String(unit.id_unidad) === String(originUnit.value))); targetInput.disabled = true; } else { targetInput.disabled = false; if (betweenUnits() && String(targetUnit.value) === String(originUnit.value)) clearTargetUnit(); }
  };
  kindInputs.forEach(input => input.addEventListener('change', updateKind));

  const selectionCount = () => selected.size;
  const visibleBoxes = () => [...goodsList.querySelectorAll('input[name="bienes[]"]')].filter(box => !box.closest('tr').hidden);
  const updateSelectionUi = () => { const total = selectionCount(); count.textContent = `${total} bien${total === 1 ? '' : 'es'} seleccionado${total === 1 ? '' : 's'}`; const boxes = visibleBoxes(); const checked = boxes.filter(box => box.checked).length; allCurrent.checked = !!boxes.length && checked === boxes.length; allCurrent.indeterminate = checked > 0 && checked < boxes.length; goodsList.querySelectorAll('[data-transfer-row]').forEach(row => row.classList.toggle('is-selected', row.querySelector('input')?.checked)); };
  const filterGoods = () => { const term = (goodsSearch.value || '').trim().toLocaleLowerCase(); goodsList.querySelectorAll('[data-transfer-row]').forEach(row => { row.hidden = !!term && !row.textContent.toLocaleLowerCase().includes(term); }); updateSelectionUi(); };
  const renderGoods = () => {
    goodsList.replaceChildren(); if (!goods.length) { const row = document.createElement('tr'); const cell = document.createElement('td'); cell.colSpan = 4; cell.className = 'text-center py-4 text-muted'; cell.textContent = originId.value ? 'Este resguardante no tiene bienes disponibles para traspaso.' : 'Selecciona un resguardante para cargar sus bienes.'; row.append(cell); goodsList.append(row); updateSelectionUi(); return; }
    goods.forEach(item => {
      const row = document.createElement('tr'); row.dataset.transferRow = ''; const selection = document.createElement('td'); const checkbox = document.createElement('input'); checkbox.type = 'checkbox'; checkbox.className = 'form-check-input'; checkbox.name = 'bienes[]'; checkbox.value = item.id_bien; checkbox.checked = selected.has(String(item.id_bien)); checkbox.setAttribute('aria-label', `Seleccionar ${item.nombre_bien || 'bien'}`); selection.append(checkbox);
      const description = document.createElement('td'); const title = document.createElement('strong'); title.textContent = item.nombre_bien || 'Bien sin nombre'; const info = document.createElement('small'); info.className = 'd-block text-muted'; info.textContent = `Clave interna: ${item.clave_interna || 'Sin clave'} · Inventario CI: ${item.numero_inventario || 'Sin número'}${item.numero_serie ? ` · Serie: ${item.numero_serie}` : ''}`; description.append(title, info);
      const model = document.createElement('td'); model.textContent = [item.marca_nombre, item.modelo_nombre].filter(Boolean).join(' / ') || 'Sin marca o modelo';
      const locationCell = document.createElement('td'); const label = document.createElement('span'); label.className = 'transfer-location-label'; label.textContent = 'Ubicación actual'; const location = document.createElement('select'); location.className = 'form-select form-select-sm'; location.name = `ubicaciones[${item.id_bien}]`; const current = [item.municipio, item.localidad].filter(Boolean).join(' — ') || item.ubicacion_fisica || 'Sin ubicación registrada'; const preserve = document.createElement('option'); preserve.value = ''; preserve.textContent = `Conservar ubicación actual · ${current}`; location.append(preserve); if (locationsTemplate) location.append(...[...locationsTemplate.content.children].map(option => option.cloneNode(true))); location.value = locations.get(String(item.id_bien)) || ''; location.addEventListener('change', () => locations.set(String(item.id_bien), location.value)); locationCell.append(label, location);
      checkbox.addEventListener('change', () => { checkbox.checked ? selected.add(String(item.id_bien)) : selected.delete(String(item.id_bien)); updateSelectionUi(); }); row.addEventListener('click', event => { if (event.target.closest('input,select,option,label')) return; checkbox.checked = !checkbox.checked; checkbox.dispatchEvent(new Event('change', { bubbles: true })); }); row.append(selection, description, model, locationCell); goodsList.append(row);
    }); filterGoods();
  };
  const loadGoods = async () => { if (!selectedOrigin()) { renderGoods(); return; } const request = ++goodsRequest; const keeperId = originId.value; goodsList.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Cargando bienes…</td></tr>'; try { const response = await fetch(`${form.dataset.goodsUrl}${encodeURIComponent(keeperId)}`, { credentials: 'same-origin', headers: { Accept: 'application/json' } }); const data = await response.json(); if (request !== goodsRequest || !selectedOrigin() || String(originId.value) !== String(keeperId)) return; if (!response.ok || !data.success) throw new Error(data.message || 'No fue posible cargar los bienes.'); goods = Array.isArray(data.results) ? data.results : []; selected = new Set([...selected].filter(id => goods.some(item => String(item.id_bien) === id))); renderGoods(); } catch (exception) { if (request !== goodsRequest || !selectedOrigin()) return; goods = []; error(exception.message || 'No fue posible cargar los bienes.'); renderGoods(); } };
  goodsSearch.addEventListener('input', filterGoods); allCurrent.addEventListener('change', () => { visibleBoxes().forEach(box => { box.checked = allCurrent.checked; box.checked ? selected.add(box.value) : selected.delete(box.value); }); updateSelectionUi(); });

  const removeOffice = (clearInput = true) => { if (clearInput) officeFile.value = ''; form.querySelector('[data-office-preview]').hidden = true; form.querySelector('[data-office-media]').replaceChildren(); form.querySelector('[data-office-error]').textContent = ''; officeFile.classList.remove('is-invalid'); if (officeUrl) URL.revokeObjectURL(officeUrl); officeUrl = null; };
  officeFile.addEventListener('change', () => { removeOffice(false); const file = officeFile.files?.[0]; if (!file) return; const extension = file.name.split('.').pop()?.toLocaleLowerCase(); if (file.size > 15 * 1024 * 1024 || extension !== 'pdf' || (file.type && file.type !== 'application/pdf')) { officeFile.classList.add('is-invalid'); form.querySelector('[data-office-error]').textContent = 'Selecciona un archivo PDF de máximo 15 MB.'; return; } officeUrl = URL.createObjectURL(file); const media = form.querySelector('[data-office-media]'); const frame = document.createElement('embed'); frame.className = 'transfer-file-preview__media'; frame.src = officeUrl; frame.type = 'application/pdf'; media.append(frame); form.querySelector('[data-office-name]').textContent = file.name; form.querySelector('[data-office-details]').textContent = `PDF · ${(file.size / 1024 / 1024).toFixed(2)} MB`; form.querySelector('[data-office-preview]').hidden = false; });
  form.querySelector('[data-remove-office]').addEventListener('click', removeOffice);

  const validate = step => { clearError(); if (step === 1) { if (!chosenKind()) return error('Selecciona el tipo de traspaso que deseas realizar.'), false; if (!selectedOrigin()) return error('Busca y selecciona al resguardante actual de la lista.'), false; } if (step === 2 && !selectionCount()) return error('Selecciona al menos un bien para continuar.'), false; if (step === 3) { if (!targetUnit.value) return error('Selecciona una Unidad Administrativa de destino de la lista.'), false; if (!selectedTargetKeeper()) return error('Busca y selecciona al resguardante destino de la lista.'), false; if (betweenUnits()) { const file = officeFile.files?.[0]; const extension = file?.name.split('.').pop()?.toLocaleLowerCase(); if (!officeNumber.value.trim() || !officeDate.value || !file) return error('Captura número, fecha y archivo PDF del oficio de autorización.'), false; if (officeFile.classList.contains('is-invalid') || extension !== 'pdf' || (file.type && file.type !== 'application/pdf')) return error('El oficio debe ser un archivo PDF de máximo 15 MB.'), false; } } return true; };
  const fillReview = () => { const origin = keepers.find(item => String(item.id_resguardante) === String(originId.value)); const target = keepers.find(item => String(item.id_resguardante) === String(targetKeeperId.value)); const unit = units.find(item => String(item.id_unidad) === String(targetUnit.value)); form.querySelector('[data-review-type]').textContent = betweenUnits() ? 'Traspaso entre Unidades Administrativas' : 'Cambio de resguardante dentro de la misma UA'; form.querySelector('[data-review-source]').textContent = origin ? `${origin.nombre_completo} · CSP: ${origin.csp}` : '—'; form.querySelector('[data-review-origin-unit]').textContent = origin ? `${origin.unidad_nombre} · Código UA: ${origin.unidad_codigo || 'Sin código'}` : '—'; form.querySelector('[data-review-keeper]').textContent = target ? `${target.nombre_completo} · CSP: ${target.csp}` : '—'; form.querySelector('[data-review-target-unit]').textContent = unit ? `${unit.nombre} · Código UA: ${unit.codigo_ua || 'Sin código'}` : '—'; const officeWrap = form.querySelector('[data-review-office-wrap]'); officeWrap.hidden = !betweenUnits(); if (betweenUnits()) { const file = officeFile.files[0]; form.querySelector('[data-review-office]').textContent = `${officeNumber.value} · ${officeDate.value} · ${file?.name || ''}`; const link = form.querySelector('[data-review-office-link]'); link.hidden = !officeUrl; link.href = officeUrl || '#'; } const list = form.querySelector('[data-review-goods]'); list.replaceChildren(); [...selected].forEach(id => { const item = goods.find(good => String(good.id_bien) === id); const line = document.createElement('li'); line.className = 'list-group-item'; const location = locations.get(id); const selectedLocation = item && location ? locationsTemplate.content.querySelector(`option[value="${CSS.escape(location)}"]`)?.textContent : ''; line.textContent = `${item?.nombre_bien || 'Bien'} · ${item?.clave_interna || ''} · ${item?.numero_inventario || ''}${selectedLocation ? ` · Ubicación destino: ${selectedLocation}` : ''}`; list.append(line); }); form.querySelector('[data-review-goods-count]').textContent = `${selectionCount()} bien${selectionCount() === 1 ? '' : 'es'}`; form.querySelector('[data-create-transfer]').disabled = false; };
  const newKeeperModal = document.querySelector('#resguardanteModal');
  const newKeeperForm = newKeeperModal?.querySelector('[data-resguardante-form]');
  if (newKeeperModal && newKeeperForm && addTargetKeeper) {
    const unitField = newKeeperForm.querySelector('[name="id_unidad"]');
    const modalBody = newKeeperForm.querySelector('.modal-body');
    const modalError = document.createElement('div'); modalError.className = 'alert alert-danger d-none'; modalError.setAttribute('role', 'alert'); modalBody.prepend(modalError);
    newKeeperModal.addEventListener('show.bs.modal', event => {
      if (event.relatedTarget !== addTargetKeeper || !targetUnit.value) { event.preventDefault(); return; }
      newKeeperForm.reset(); modalError.textContent = ''; modalError.classList.add('d-none');
      newKeeperForm.querySelector('[name="id_resguardante"]').value = ''; unitField.value = targetUnit.value; unitField.disabled = true;
    });
    newKeeperModal.addEventListener('hidden.bs.modal', () => { unitField.disabled = false; });
    newKeeperForm.addEventListener('submit', async event => {
      event.preventDefault(); if (!targetUnit.value) return;
      modalError.textContent = ''; modalError.classList.add('d-none'); const submit = newKeeperForm.querySelector('[type="submit"]'); submit.disabled = true;
      try {
        const payload = new FormData(newKeeperForm); payload.set('id_unidad', targetUnit.value); payload.set('respuesta', 'json');
        const response = await fetch(newKeeperForm.action, { method: 'POST', body: payload, headers: { Accept: 'application/json' }, credentials: 'same-origin' }); const result = await response.json();
        if (!response.ok || !result.ok || !result.resguardante) throw new Error(result.error || 'No fue posible guardar el resguardante.');
        chooseTargetKeeper(result.resguardante); bootstrap.Modal.getOrCreateInstance(newKeeperModal).hide();
      } catch (exception) { modalError.textContent = exception.message || 'No fue posible guardar el resguardante.'; modalError.classList.remove('d-none'); }
      finally { submit.disabled = false; }
    });
  }
  form.querySelectorAll('[data-next-step]').forEach(button => button.addEventListener('click', () => { if (!validate(activeStep)) return; if (activeStep === 1 && !goods.length) loadGoods(); if (activeStep === 3) fillReview(); showStep(Math.min(4, activeStep + 1)); }));
  form.querySelectorAll('[data-prev-step]').forEach(button => button.addEventListener('click', () => showStep(Math.max(1, activeStep - 1))));
  form.addEventListener('submit', event => { if (!validate(1) || !validate(2) || !validate(3)) { event.preventDefault(); return; } });
  const preselected = keepers.find(item => String(item.id_resguardante) === String(form.dataset.preselectedKeeper)); if (preselected) chooseOrigin(preselected); updateKind(); showStep(1);
})();
