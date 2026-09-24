<?php

class resguardosController extends InventoryController implements ControllerInterface
{
  public function index()
  {
    $this->setTitle('Resguardos');
    $resguardantes = ResguardoModel::resumenResguardantes();
    $this->renderInventory('index', ['resguardantes' => $resguardantes]);
  }

  public function detalle($id = null)
  {
    $detalle = ResguardoModel::detalleResguardante((int) $id);
    if (!$detalle) { Flasher::error('El resguardante solicitado no existe.'); Redirect::to('resguardos'); }
    $detalle['bienes_asignados'] = count(array_filter($detalle['asignaciones'], static fn($a) => (int) $a['activo'] === 1));
    $detalle['bienes_activos'] = count(array_filter($detalle['asignaciones'], static fn($a) => (int) $a['activo'] === 1 && (int) $a['bien_activo'] === 1));
    $this->setTitle('Detalle de resguardo');
    $this->renderInventory('detalle', ['resguardo' => $detalle]);
  }

  public function tarjeta($id = null) { $this->documento('tarjeta', $id); }
  public function resguardo_equipo($id = null) { $this->documento('resguardo', $id); }
  public function baja_resguardante($id = null) { $this->documento('baja', $id); }

  private function documento(string $tipo, $id): void
  {
    $this->setTitle(ucfirst($tipo) . ' de resguardo');
    $bienId = $id ?: ($_GET['id'] ?? null);
    $this->renderInventory($tipo, ['tipo' => $tipo, 'bien' => $bienId ? BienModel::porId((int) $bienId) : [], 'bienes' => BienModel::buscar(['activo' => 1])]);
  }
}
