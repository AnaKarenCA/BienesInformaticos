<?php

class historico_movimientosController extends InventoryController implements ControllerInterface
{
  public function index()
  {
    $this->setTitle('Histórico de movimientos');
    $filtros = [
      'q' => trim((string) ($_GET['q'] ?? '')),
      'tipo' => trim((string) ($_GET['tipo'] ?? '')),
      'desde' => trim((string) ($_GET['desde'] ?? '')),
      'hasta' => trim((string) ($_GET['hasta'] ?? '')),
    ];
    $movimientos = ResguardoModel::historico($filtros);
    foreach ($movimientos as &$movimiento) {
      $movimiento['resumen_cambio'] = $this->resumenCambio($movimiento);
      $movimiento['tipo_etiqueta'] = $this->etiquetaTipo((string) $movimiento['tipo_movimiento']);
    }
    unset($movimiento);
    $tipos = ResguardoModel::tiposMovimiento();
    foreach ($tipos as &$tipo) $tipo['etiqueta'] = $this->etiquetaTipo((string) $tipo['tipo_movimiento']);
    unset($tipo);
    $this->renderInventory('index', [
      'movimientos' => $movimientos, 'total_movimientos' => count($movimientos), 'filtros' => $filtros,
      'tipos' => $tipos
    ]);
  }

  public function detalle($id = null)
  {
    $movimiento = ResguardoModel::movimientoPorId((int) $id);
    if (!$movimiento) { Flasher::error('El movimiento solicitado no existe.'); Redirect::to('historico-movimientos'); }
    $movimiento['resumen_cambio'] = $this->resumenCambio($movimiento);
    $movimiento['tipo_etiqueta'] = $this->etiquetaTipo((string) $movimiento['tipo_movimiento']);
    $this->setTitle('Detalle del movimiento');
    $this->renderInventory('detalle', ['movimiento' => $movimiento]);
  }

  private function etiquetaTipo(string $tipo): string
  {
    return mb_strtoupper(trim($tipo), 'UTF-8') === 'ASIGNACION' ? 'Asignación' : $tipo;
  }

  private function resumenCambio(array $m): string
  {
    $tipo = mb_strtolower((string) $m['tipo_movimiento'], 'UTF-8');
    $resguardanteAnterior = trim(implode(' · ', array_filter([$m['csp_anterior_vista'] ?? null, $m['nombre_resguardante_anterior_vista'] ?? null]))) ?: 'Sin resguardante';
    $resguardanteNuevo = trim(implode(' · ', array_filter([$m['csp_nuevo_vista'] ?? null, $m['nombre_resguardante_nuevo_vista'] ?? null]))) ?: 'Sin resguardante';
    if (str_contains($tipo, 'resguardante') || str_contains($tipo, 'asignación') || str_contains($tipo, 'asignacion') || str_contains($tipo, 'liberación') || str_contains($tipo, 'liberacion') || str_contains($tipo, 'devolución') || str_contains($tipo, 'devolucion')) {
      if (str_contains($tipo, 'unidad del resguardante')) {
        $antes = trim(implode(' · ', array_filter([$m['codigo_ua_resguardante_anterior'] ?? null, $m['unidad_resguardante_anterior'] ?? null]))) ?: 'Sin dato anterior';
        $despues = trim(implode(' · ', array_filter([$m['codigo_ua_resguardante_nueva'] ?? null, $m['unidad_resguardante_nueva'] ?? null]))) ?: 'Sin dato nuevo';
        return 'Unidad del resguardante: ' . $antes . ' → ' . $despues;
      }
      return 'Resguardante: ' . $resguardanteAnterior . ' → ' . $resguardanteNuevo;
    }
    if (str_contains($tipo, 'unidad')) {
      $antes = trim(implode(' · ', array_filter([$m['codigo_ua_anterior'] ?? null, $m['unidad_anterior'] ?? null]))) ?: 'Sin dato anterior';
      $despues = trim(implode(' · ', array_filter([$m['codigo_ua_nueva'] ?? null, $m['unidad_nueva'] ?? null]))) ?: 'Sin dato nuevo';
      return 'Unidad: ' . $antes . ' → ' . $despues;
    }
    if (str_contains($tipo, 'ubicación') || str_contains($tipo, 'ubicacion')) {
      return 'Ubicación: ' . (($m['ubicacion_anterior_detalle'] ?? '') ?: 'Sin ubicación') . ' → ' . (($m['ubicacion_nueva_detalle'] ?? '') ?: 'Sin ubicación');
    }
    if (str_contains($tipo, 'baja') || str_contains($tipo, 'reactivación') || str_contains($tipo, 'reactivacion')) {
      return 'Estado: ' . (($m['estado_anterior'] ?? '') ?: 'Sin dato') . ' → ' . (($m['estado_nuevo'] ?? '') ?: 'Sin dato');
    }
    if (str_contains($tipo, 'alta')) return 'Bien incorporado al inventario.';
    return trim((string) ($m['motivo'] ?? $m['observaciones'] ?? '')) ?: 'Movimiento registrado.';
  }
}
