(() => {
  const button = document.querySelector('#usarCamara');
  if (!button || !navigator.mediaDevices?.getUserMedia) return;
  button.addEventListener('click', async () => {
    const panel = document.querySelector('#cameraPanel');
    const video = document.querySelector('#cameraVideo');
    const status = document.querySelector('#cameraStatus');
    try {
      video.srcObject = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
      panel.classList.remove('d-none');
      if (!('BarcodeDetector' in window)) { status.textContent = 'Tu navegador no admite BarcodeDetector. Escribe o escanea el código en el buscador.'; return; }
      const detector = new BarcodeDetector({ formats: ['code_128', 'code_39', 'ean_13', 'qr_code'] });
      const scan = async () => {
        if (!video.srcObject) return;
        const values = await detector.detect(video);
        if (values.length) { document.querySelector('#identInput').value = values[0].rawValue; video.srcObject.getTracks().forEach(track => track.stop()); panel.classList.add('d-none'); button.closest('form').submit(); return; }
        requestAnimationFrame(scan);
      };
      scan();
    } catch (error) { status.textContent = 'No fue posible acceder a la cámara. Verifica los permisos del navegador.'; panel.classList.remove('d-none'); }
  });
})();
