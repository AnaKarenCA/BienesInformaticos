<?php

class identificarController extends InventoryController implements ControllerInterface
{
  public function index()
  {
    $consulta = trim($_GET['q'] ?? '');
    $this->setTitle('Identificar bien');
    $this->renderInventory('index', ['consulta' => $consulta, 'bien' => $consulta ? BienModel::identificar($consulta) : []]);
  }
}
