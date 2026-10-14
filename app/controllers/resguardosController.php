<?php

class resguardosController extends InventoryController implements ControllerInterface
{
  public function index()
  {
    $this->requirePermission('resguardos-consultar');
    $this->setTitle('Resguardos');
    $filtros = [
      'q' => trim((string) ($_GET['q'] ?? '')),
      'estado' => (string) ($_GET['estado'] ?? '1'),
    ];
    if (!in_array($filtros['estado'], ['0', '1', ''], true)) $filtros['estado'] = '1';
    $resguardantes = ResguardoModel::resumenResguardantes($filtros);
    if (($_GET['ajax'] ?? '') === '1') {
      $this->renderInventory('resguardo-rows', ['resguardantes' => $resguardantes, 'filtros' => $filtros]);
      return;
    }
    $this->renderInventory('index', ['resguardantes' => $resguardantes, 'filtros' => $filtros]);
  }

  public function detalle($id = null)
  {
    $this->requirePermission('resguardos-consultar');
    $detalle = ResguardoModel::detalleResguardante((int) $id);
    if (!$detalle) { Flasher::error('El resguardante solicitado no existe.'); Redirect::to('resguardos'); }
    $detalle['bienes_asignados'] = count($detalle['asignaciones']);
    $this->setTitle('Detalle de resguardo');
    $this->renderInventory('detalle', ['resguardo' => $detalle]);
  }

  /** Compatibilidad con enlaces anteriores: toda reasignación ahora inicia un expediente. */
  public function cambiar_resguardante($id = null)
  {
    $this->can('traspasos-crear');
    $bien = BienModel::porId((int) $id, false);
    if (!$bien) { Flasher::error('El bien solicitado no existe.'); Redirect::to('bienes'); }
    Flasher::info('El cambio de responsable se registra mediante un expediente de traspaso.');
    Redirect::to('traspasos/crear?bien_id=' . (int) $id);
  }

  public function tarjeta($id = null) { $this->documento('tarjeta', $id); }
  public function resguardo_equipo($id = null) { $this->documento('resguardo', $id); }
  public function baja_resguardante($id = null) { $this->documento('baja', $id); }

  private function documento(string $tipo, $id): void
  {
    $this->requirePermission('documentos-consultar');
    $this->setTitle(ucfirst($tipo) . ' de resguardo');
    $bienId = $id ?: ($_GET['id'] ?? null);
    $this->renderInventory($tipo, ['tipo' => $tipo, 'bien' => $bienId ? BienModel::porId((int) $bienId) : [], 'bienes' => BienModel::buscar(['activo' => 1])]);
  }
}
