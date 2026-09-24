(() => {
  const patterns = ['212222','222122','222221','121223','121322','131222','122213','122312','132212','221213','221312','231212','112232','122132','122231','113222','123122','123221','223211','221132','221231','213212','223112','312131','311222','321122','321221','312212','322112','322211','212123','212321','232121','111323','131123','131321','112313','132113','132311','211313','231113','231311','112133','112331','132131','113123','113321','133121','313121','211331','231131','213113','213311','213131','311123','311321','331121','312113','312311','332111','314111','221411','431111','111224','111422','121124','121421','141122','141221','112214','112412','122114','122411','142112','142211','241211','221114','413111','241112','134111','111242','121142','121241','114212','124112','124211','411212','421112','421211','212141','214121','412121','111143','111341','131141','114113','114311','411113','411311','113141','114131','311141','411131','211412','211214','211232','2331112'];

  const renderBarcode = (svg, value) => {
    if (!svg) return;
    const codes = [...(value || '')].map(char => char.charCodeAt(0) - 32);
    if (codes.length === 0 || codes.some(code => code < 0 || code > 94)) { svg.innerHTML = ''; return; }
    const checksum = (104 + codes.reduce((sum, code, index) => sum + code * (index + 1), 0)) % 103;
    const sequence = [104, ...codes, checksum, 106].map(code => patterns[code]).join('');
    let x = 20, bars = '', black = true;
    for (const width of sequence) {
      const barWidth = Number(width) * 2;
      if (black) bars += `<rect x="${x}" y="5" width="${barWidth}" height="110"/>`;
      x += barWidth;
      black = !black;
    }
    svg.innerHTML = `<g fill="#000" transform="scale(${600 / (x - 20)},1)">${bars}</g>`;
  };

  document.querySelectorAll('[data-code128]').forEach(svg => renderBarcode(svg, svg.dataset.code128));

  document.addEventListener('show.bs.modal', event => {
    if (event.target.id !== 'codigoBarrasListadoModal') return;
    const trigger = event.relatedTarget;
    const value = trigger?.dataset.barcodeCode || '';
    const inventory = trigger?.dataset.barcodeInventory || '';
    const svg = event.target.querySelector('[data-list-code128]');
    svg?.setAttribute('aria-label', `Código de barras ${value}`);
    renderBarcode(svg, value);
    const output = event.target.querySelector('[data-list-barcode-value]');
    const inventoryOutput = event.target.querySelector('[data-list-inventory]');
    if (output) output.textContent = value;
    if (inventoryOutput) inventoryOutput.textContent = inventory ? `Inventario SICOPA: ${inventory}` : '';
  });

  document.addEventListener('click', event => {
    if (event.target.closest('[data-print-barcode]')) window.print();
  });
})();
