<?php

class identificarController extends InventoryController implements ControllerInterface
{
  public function index()
  {
    $this->requirePermission('bienes-consultar');
    $consulta = trim((string) ($_GET['q'] ?? ''));
    $errorQr = '';
    $qrLegado = false;

    // El lector entrega su contenido por separado: nunca se usa una URL QR como búsqueda libre.
    $contenidoQr = trim((string) ($_GET['qr'] ?? ''));
    if ($contenidoQr !== '') {
      if (preg_match('~^[a-z][a-z0-9+.-]*://~i', $contenidoQr)) {
        try { $idQr = BienModel::idDesdeUrlQr($contenidoQr); }
        catch (Throwable $e) { $idQr = null; }
        if ($idQr !== null) {
          if (BienModel::existe($idQr)) {
            Redirect::to('bienes/detalle/' . $idQr);
            return;
          }
          $errorQr = 'El código escaneado no corresponde a un bien registrado.';
        } else {
          $errorQr = 'Este código QR no pertenece al sistema de Bienes Informáticos.';
        }
      } elseif (preg_match('/^CI-[A-Za-z0-9-]+$/i', $contenidoQr)) {
        $qrLegado = true;
        $consulta = $contenidoQr;
        $idQr = BienModel::idPorIdentificadorExactoSeguro($contenidoQr);
        if ($idQr !== null) {
          Redirect::to('bienes/detalle/' . $idQr);
          return;
        }
        $errorQr = 'El código escaneado no corresponde a un bien registrado.';
      } else {
        $errorQr = 'El código escaneado no corresponde a un bien registrado.';
      }
    }

    $coincidencias = $consulta !== '' ? BienModel::identificarCoincidencias($consulta) : [];
    if ($qrLegado && $coincidencias) {
      $errorQr = 'El código QR requiere confirmación. Selecciona el bien correcto en los resultados.';
    }
    if ($consulta !== '' && mb_strlen($consulta, 'UTF-8') >= 3) {
      $idExacto = BienModel::idPorIdentificadorExactoSeguro($consulta);
      if ($idExacto !== null) {
        Redirect::to('bienes/detalle/' . $idExacto);
        return;
      }
    }

    $this->setTitle('Identificar bien');
    $this->renderInventory('index', [
      'consulta' => $consulta, 'coincidencias' => $coincidencias, 'error_qr' => $errorQr,
      'busqueda_corta' => $consulta !== '' && mb_strlen($consulta, 'UTF-8') < 3,
    ]);
  }
}
