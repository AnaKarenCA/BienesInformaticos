<?php

class resguardosController extends InventoryController implements ControllerInterface
{
  public function index()
  {
    $this->setTitle('Resguardos');
    $this->renderInventory('index', ['vigentes' => ResguardoModel::vigentes(), 'historico' => ResguardoModel::historico()]);
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
