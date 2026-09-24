<?php

class reportesController extends InventoryController implements ControllerInterface
{
  public function index() { $this->exportar_pdf(); }
  public function exportar_pdf()
  {
    $this->can('reportes-exportar');
    $bienes = BienModel::buscar($_GET);
    $pdf = new BeePdf();
    $pdf->streamPdf(true);
    $pdf->setOrientation('landscape');
    $pdf->create('inventario-bienes', ReporteModel::inventarioHtml($bienes), true);
  }

  public function documento($tipo = 'tarjeta', $id = null)
  {
    $this->can('documentos-generar');
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Csrf::validate($_POST['csrf'] ?? '')) {
      Flasher::error(get_bee_message(0));
      Redirect::to('resguardos/' . ($tipo === 'resguardo' ? 'resguardo_equipo' : ($tipo === 'baja' ? 'baja_resguardante' : 'tarjeta')) . '/' . (int) $id);
    }
    $bien = BienModel::porId((int) $id);
    if (!$bien) { Flasher::error('El bien solicitado no existe.'); Redirect::to('bienes'); }
    $titulos = ['tarjeta' => 'Tarjeta de resguardo', 'resguardo' => 'Resguardo del equipo', 'baja' => 'Baja de resguardante'];
    $titulo = $titulos[$tipo] ?? 'Documento de resguardo';
    $observaciones = trim($_POST['observaciones'] ?? '');
    if ($tipo === 'baja' && ($_POST['confirmar_baja'] ?? '') === '1') {
      try {
        ResguardoModel::darDeBaja((int) $id, $_POST['fecha_baja'] ?? date('Y-m-d'), trim($_POST['motivo'] ?? ''), $observaciones, get_user() ?: null);
      } catch (Exception $e) {
        Flasher::error($e->getMessage());
        Redirect::to('resguardos/baja_resguardante/' . (int) $id);
      }
    }
    $codigoUA = !empty($bien['codigo_ua']) ? $bien['codigo_ua'] : 'Sin código UA';
    $nombreResguardante = !empty($bien['resguardante_nombre'])
      ? '<strong>' . htmlspecialchars($bien['resguardante_csp'] ?: 'CSP no capturado') . '</strong><br><small>' . htmlspecialchars($bien['resguardante_nombre']) . '</small>'
      : 'Sin asignar';
    $unidadResguardante = !empty($bien['resguardante_unidad_nombre'])
      ? htmlspecialchars($bien['resguardante_unidad_nombre']) . '<br><small>' . htmlspecialchars(!empty($bien['resguardante_codigo_ua']) ? $bien['resguardante_codigo_ua'] : 'Sin código UA') . '</small>'
      : 'Sin unidad registrada';
    $contenido = '<style>body{font:12px Arial;color:#333}h1{color:#680000;border-bottom:3px solid #D4AF37;padding-bottom:8px}.linea{margin:14px 0;border-bottom:1px solid #777}small{font-size:10px;color:#555}</style>' .
      '<h1>' . htmlspecialchars($titulo) . '</h1><p><strong>CI:</strong> ' . htmlspecialchars($bien['clave_interna'] ?? '—') . '</p><p><strong>Inventario SICOPA:</strong> ' . htmlspecialchars($bien['numero_inventario'] ?? '—') . '</p><p><strong>Bien:</strong> ' . htmlspecialchars($bien['nombre_bien']) . '</p><p><strong>Serie:</strong> ' . htmlspecialchars($bien['numero_serie'] ?? '—') . '</p><p><strong>Unidad del bien:</strong> ' . htmlspecialchars($bien['unidad_nombre']) . '<br><small>' . htmlspecialchars($codigoUA) . '</small></p><p><strong>Resguardante:</strong><br>' . $nombreResguardante . '</p><p><strong>Unidad administrativa del resguardante:</strong><br>' . $unidadResguardante . '</p>' . ($observaciones ? '<p><strong>Observaciones:</strong><br>' . nl2br(htmlspecialchars($observaciones)) . '</p>' : '') . '<br><div class="linea"></div><p>Firma del resguardante</p>';
    $pdf = new BeePdf(); $pdf->streamPdf(true); $pdf->create(strtolower(str_replace(' ', '-', $titulo)), $contenido, true);
  }
}
