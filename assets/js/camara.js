(() => {
  const button = document.querySelector('#usarCamara');
  if (!button) return;

  const panel = document.querySelector('#cameraPanel');
  const video = document.querySelector('#cameraVideo');
  const status = document.querySelector('#cameraStatus');
  const input = document.querySelector('#identInput');
  const form = document.querySelector('#identificarForm');
  const close = document.querySelector('#cerrarCamara');
  let stream = null;
  let active = false;

  const stopCamera = (hidePanel = true) => {
    active = false;
    if (stream) stream.getTracks().forEach(track => track.stop());
    stream = null;
    if (video) video.srcObject = null;
    if (hidePanel) panel?.classList.add('d-none');
  };

  close?.addEventListener('click', stopCamera);
  window.addEventListener('pagehide', stopCamera);

  button.addEventListener('click', async () => {
    panel?.classList.remove('d-none');
    if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
      status.textContent = 'La cámara no está disponible en este contexto. Puedes buscar el código manualmente.';
      return;
    }
    if (!('BarcodeDetector' in window)) {
      status.textContent = 'Este navegador no admite lectura de códigos de barras por cámara. La búsqueda manual sigue disponible.';
      return;
    }

    try {
      const disponibles = typeof BarcodeDetector.getSupportedFormats === 'function'
        ? await BarcodeDetector.getSupportedFormats()
        : ['code_128'];
      if (!disponibles.includes('code_128')) {
        status.textContent = 'Este navegador no admite el formato del código de barras del sistema (Code 128). Usa la búsqueda manual.';
        return;
      }

      status.textContent = 'Solicitando permiso para usar la cámara…';
      stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false });
      video.srcObject = stream;
      await video.play();
      const detector = new BarcodeDetector({ formats: ['code_128'] });
      active = true;
      status.textContent = 'Apunta la cámara al código de barras.';

      const scan = async () => {
        if (!active || !stream) return;
        try {
          const codes = await detector.detect(video);
          const value = codes.find(code => code.rawValue?.trim())?.rawValue?.trim();
          if (value) {
            input.value = value;
            status.textContent = 'Código leído. Buscando el bien…';
            stopCamera();
            form.requestSubmit();
            return;
          }
        } catch (error) {
          status.textContent = 'No se pudo leer la imagen. Mantén el código enfocado o usa la búsqueda manual.';
          stopCamera(false);
          return;
        }
        requestAnimationFrame(scan);
      };
      requestAnimationFrame(scan);
    } catch (error) {
      status.textContent = 'No fue posible acceder a la cámara. Revisa los permisos del navegador o usa la búsqueda manual.';
      if (stream) stopCamera(false);
    }
  });
})();
