<?php

/** Punto de entrada temporal para la gestión documental del inventario. */
class documentosController extends InventoryController implements ControllerInterface
{
  public function index()
  {
    $this->requirePermission('documentos-consultar');
    $this->setTitle('Documentos');
    $this->renderInventory('index');
  }
}
