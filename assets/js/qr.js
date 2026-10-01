(() => {
  document.addEventListener('show.bs.modal', event => {
    if (event.target.id !== 'codigoQrListadoModal') return;
    const trigger = event.relatedTarget;
    const image = event.target.querySelector('[data-list-qr]');
    const output = event.target.querySelector('[data-list-qr-value]');
    const inventoryOutput = event.target.querySelector('[data-list-inventory]');
    const url = trigger?.dataset.qrUrl || '';
    if (image) {
      image.src = url;
      image.alt = 'Código QR de la clave interna del bien';
    }
    if (output) output.textContent = trigger?.dataset.qrValue || '';
    if (inventoryOutput) inventoryOutput.textContent = trigger?.dataset.inventory ? `Inventario SICOPA: ${trigger.dataset.inventory}` : '';
    if (image && url) {
      image.onload = () => {
        if (!image.naturalWidth) image.alt = 'No fue posible cargar el código QR';
      };
    }
  });
  document.addEventListener('click', async event => {
    const printButton = event.target.closest('[data-print-qr]');
    if (!printButton) return;
    const image = printButton.closest('.modal')?.querySelector('.bi-qr-image');
    if (image && !image.complete) {
      try { await image.decode(); } catch (error) { /* El navegador imprimirá el estado disponible. */ }
    }
    window.print();
  });
})();
