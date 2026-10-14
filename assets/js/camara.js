(() => {
  const button = document.querySelector('#usarCamara');
  if (!button) return;

  const panel = document.querySelector('#cameraPanel');
  const video = document.querySelector('#cameraVideo');
  const frame = document.querySelector('#cameraFrame');
  const status = document.querySelector('#cameraStatus');
  const form = document.querySelector('#identificarForm');
  const stopButton = document.querySelector('#cerrarCamara');
  let stream = null;
  let active = false;
  let framePending = false;
  let attempt = 0;

  const setStatus = message => {
    if (status) status.textContent = message;
  };

  const stopCamera = (message, hideViewfinder = true) => {
    attempt++;
    active = false;
    framePending = false;
    if (stream) stream.getTracks().forEach(track => track.stop());
    stream = null;
    if (video) video.srcObject = null;
    if (hideViewfinder) frame?.classList.add('d-none');
    stopButton?.classList.add('d-none');
    if (message) setStatus(message);
  };

  stopButton?.addEventListener('click', () => {
    stopCamera('Escaneo detenido. Puedes volver a iniciarlo o buscar el bien manualmente.');
  });
  window.addEventListener('pagehide', () => stopCamera());

  button.addEventListener('click', async () => {
    if (active) return;
    const currentAttempt = ++attempt;
    panel?.classList.remove('d-none');
    frame?.classList.add('d-none');
    stopButton?.classList.remove('d-none');
    setStatus('Preparando el escáner…');
    button.disabled = true;
    try {
      const getUserMedia = navigator.mediaDevices?.getUserMedia;
      if (typeof getUserMedia !== 'function') {
        const message = window.isSecureContext
          ? 'Este navegador no permite acceder a la cámara. Puedes buscar el bien manualmente con su Clave Interna, número de inventario, NIC/CEA o serie.'
          : 'No se puede acceder a la cámara desde esta conexión. Puedes identificar el bien manualmente con su Clave Interna, número de inventario, NIC/CEA o número de serie.';
        stopCamera(message);
        return;
      }

      setStatus('Solicitando permiso para utilizar la cámara…');
      if (currentAttempt !== attempt) return;
      const newStream = await getUserMedia.call(navigator.mediaDevices, {
        video: { facingMode: { ideal: 'environment' } },
        audio: false,
      });
      if (currentAttempt !== attempt) {
        newStream.getTracks().forEach(track => track.stop());
        return;
      }
      stream = newStream;

      let detector;
      try {
        if (!('BarcodeDetector' in window) || typeof BarcodeDetector !== 'function') {
          stopCamera('La cámara está disponible, pero este navegador no incluye el lector QR compatible. Puedes buscar el bien manualmente con su Clave Interna, número de inventario, NIC/CEA o serie.');
          return;
        }
        const formats = typeof BarcodeDetector.getSupportedFormats === 'function'
          ? await BarcodeDetector.getSupportedFormats()
          : ['qr_code'];
        if (currentAttempt !== attempt) return;
        if (!formats.includes('qr_code')) {
          stopCamera('La cámara está disponible, pero este navegador no puede leer códigos QR. Puedes buscar el bien manualmente con su Clave Interna, número de inventario, NIC/CEA o serie.');
          return;
        }
        detector = new BarcodeDetector({ formats: ['qr_code'] });
      } catch (error) {
        if (currentAttempt !== attempt) return;
        stopCamera('La cámara está disponible, pero no se pudo preparar el lector QR en este navegador. Puedes buscar el bien manualmente con su Clave Interna, número de inventario, NIC/CEA o serie.');
        return;
      }
      if (currentAttempt !== attempt) return;

      video.srcObject = newStream;
      await video.play();
      if (currentAttempt !== attempt) return;
      active = true;
      frame?.classList.remove('d-none');
      setStatus('Coloca el código QR dentro del recuadro y mantenlo enfocado.');

      const scan = async () => {
        if (!active || !stream || framePending) return;
        framePending = true;
        try {
          const codes = await detector.detect(video);
          const value = codes.find(code => typeof code.rawValue === 'string' && code.rawValue.trim())?.rawValue.trim();
          if (value) {
            stopCamera('Código leído. Buscando el bien…');
            const destination = new URL(form.action);
            destination.searchParams.set('qr', value);
            window.location.assign(destination.href);
            return;
          }
        } catch (error) {
          stopCamera('No pudimos leer el código. Vuelve a iniciar el escaneo o busca el bien manualmente.');
          return;
        } finally {
          framePending = false;
        }
        if (active) requestAnimationFrame(scan);
      };
      requestAnimationFrame(scan);
    } catch (error) {
      if (currentAttempt !== attempt) return;
      if (!window.isSecureContext && (error?.name === 'SecurityError' || error?.name === 'NotAllowedError')) {
        setStatus('No se puede acceder a la cámara desde esta conexión. Puedes identificar el bien manualmente con su Clave Interna, número de inventario, NIC/CEA o número de serie.');
      } else if (error?.name === 'NotAllowedError') {
        setStatus('No se permitió el acceso a la cámara. Puedes buscar el bien manualmente utilizando su Clave Interna, número de inventario, NIC/CEA o serie.');
      } else if (error?.name === 'NotFoundError' || error?.name === 'DevicesNotFoundError') {
        setStatus('No hay una cámara disponible en este dispositivo. Puedes buscar el bien manualmente utilizando su Clave Interna, número de inventario, NIC/CEA o serie.');
      } else {
        setStatus('No fue posible iniciar la cámara. Revisa sus permisos o busca el bien manualmente utilizando su Clave Interna, número de inventario, NIC/CEA o serie.');
      }
      stopCamera();
    } finally {
      button.disabled = false;
    }
  });
})();
