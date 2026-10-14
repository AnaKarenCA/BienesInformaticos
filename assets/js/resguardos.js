/**
 * Bienes Informáticos - Resguardos
 * Búsqueda dinámica con debounce, filtro de estado y actualización asíncrona.
 */
(() => {
  const initResguardos = () => {
    const filterForm = document.querySelector('#resguardosFiltros');
    const table = document.querySelector('#tablaResguardos');
    const results = document.querySelector('#resguardosResultados');
    const totalBadge = document.querySelector('#resguardosTotalBadge');

    const initTooltips = (context = document) => {
      if (!window.bootstrap?.Tooltip) return;
      context.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        bootstrap.Tooltip.getOrCreateInstance(el, { trigger: 'hover focus' });
      });
    };

    // Inicializar tooltips existentes
    initTooltips();

    if (!filterForm || !results) return;

    const queryInput = filterForm.querySelector('#buscarResguardos');
    const statusSelect = filterForm.querySelector('#filtroEstadoResguardos');
    let debounceTimer;
    let currentRequest;

    const updateTotalCount = () => {
      if (!totalBadge) return;
      const rows = results.querySelectorAll('.resguardo-row');
      const count = rows.length;
      totalBadge.textContent = `${count} ${count === 1 ? 'resguardante' : 'resguardantes'}`;
    };

    const updateResults = async (updateHistory = true) => {
      if (currentRequest) {
        currentRequest.abort();
      }
      currentRequest = new AbortController();

      const params = new URLSearchParams(new FormData(filterForm));
      const visibleParams = new URLSearchParams(params);

      // Limpiar parámetros vacíos para URL limpia
      for (const [key, value] of [...visibleParams.entries()]) {
        if (!value && value !== '0') visibleParams.delete(key);
      }

      params.set('ajax', '1');

      if (table) table.classList.add('is-loading');

      try {
        const response = await fetch(`${filterForm.action}?${params.toString()}`, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html'
          },
          signal: currentRequest.signal
        });

        if (!response.ok) {
          throw new Error('Error al consultar los resguardos.');
        }

        const markup = await response.text();
        results.innerHTML = markup;

        updateTotalCount();
        initTooltips(results);

        if (updateHistory) {
          const queryString = visibleParams.toString();
          history.replaceState(null, '', `${filterForm.action}${queryString ? `?${queryString}` : ''}`);
        }
      } catch (error) {
        if (error.name !== 'AbortError') {
          console.error('Error al actualizar resultados de resguardos:', error);
          results.innerHTML = `
            <tr>
              <td colspan="6" class="text-center py-5">
                <div class="bi-empty-state">
                  <i class="fa-solid fa-triangle-exclamation text-danger fs-2 mb-2" aria-hidden="true"></i>
                  <h3 class="h6 mb-1 text-dark">Error al consultar los datos</h3>
                  <p class="text-muted small mb-0">No fue posible obtener los resguardantes. Por favor, intenta de nuevo.</p>
                </div>
              </td>
            </tr>
          `;
          if (totalBadge) totalBadge.textContent = '0 resguardantes';
        }
      } finally {
        if (table) table.classList.remove('is-loading');
      }
    };

    // Búsqueda en tiempo real con debounce
    queryInput?.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => updateResults(), 250);
    });

    // Filtro de estado inmediato
    statusSelect?.addEventListener('change', () => {
      clearTimeout(debounceTimer);
      updateResults();
    });

    // Evitar recarga tradicional del formulario
    filterForm.addEventListener('submit', event => {
      event.preventDefault();
      clearTimeout(debounceTimer);
      updateResults();
    });

    // Delegación para botón de restablecer filtros dentro de los estados vacíos
    document.addEventListener('click', event => {
      const resetBtn = event.target.closest('[data-reset-filters]');
      if (!resetBtn) return;
      event.preventDefault();
      if (queryInput) queryInput.value = '';
      if (statusSelect) statusSelect.value = '1';
      updateResults();
      queryInput?.focus();
    });

    // Soporte para navegación con historial
    window.addEventListener('popstate', () => {
      const currentParams = new URLSearchParams(location.search);
      if (queryInput) queryInput.value = currentParams.get('q') || '';
      if (statusSelect) statusSelect.value = currentParams.get('estado') !== null ? currentParams.get('estado') : '1';
      updateResults(false);
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initResguardos);
  } else {
    initResguardos();
  }
})();
