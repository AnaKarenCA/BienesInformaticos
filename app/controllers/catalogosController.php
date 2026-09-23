<?php

class catalogosController extends InventoryController implements ControllerInterface
{
  public function index() { $this->clasificacion(); }
  public function clasificacion()
  {
    $this->setTitle('Clasificación de activos');
    $this->renderInventory('clasificacion', ['genericos' => CatalogoModel::activosGenericos(), 'grupos' => CatalogoModel::grupos(), 'especificos' => CatalogoModel::activosEspecificos()]);
  }
  public function marcas_modelos() { $this->setTitle('Marcas y modelos'); $this->renderInventory('marcas-modelos', ['marcas' => CatalogoModel::marcas(), 'modelos' => CatalogoModel::modelos()]); }
  public function estados_uso() { $this->setTitle('Estados de uso'); $this->renderInventory('estados-uso', ['estados' => CatalogoModel::estados()]); }
  public function ubicaciones() { $this->setTitle('Ubicaciones'); $this->renderInventory('ubicaciones', ['ubicaciones' => CatalogoModel::ubicaciones()]); }
  public function unidades_admin() { $this->setTitle('Unidades administrativas'); $this->renderInventory('unidades-admin', ['unidades' => CatalogoModel::unidades()]); }

  public function guardar($tipo = null, $id = null)
  {
    try {
      $this->can('catalogos-gestionar');
      if (!Csrf::validate($_POST['csrf'] ?? '')) throw new Exception(get_bee_message(0));
      CatalogoModel::guardar((string) $tipo, $_POST, $id ? (int) $id : null);
      Flasher::success('Catálogo actualizado correctamente.');
    } catch (Exception $e) { Flasher::error($e->getMessage()); }
    Redirect::back();
  }

  public function cambiar_estado($tipo = null, $id = null)
  {
    try {
      $this->can('catalogos-gestionar');
      if (!Csrf::validate($_GET['_t'] ?? '')) throw new Exception(get_bee_message(0));
      CatalogoModel::cambiarEstado((string) $tipo, (int) $id);
      Flasher::success('Estado del catálogo actualizado.');
    } catch (Exception $e) { Flasher::error($e->getMessage()); }
    Redirect::back();
  }
}
