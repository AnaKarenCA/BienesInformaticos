<?php

class catalogosController extends InventoryController implements ControllerInterface
{
  public function index() { $this->clasificacion(); }
  public function clasificacion()
  {
    $this->setTitle('Clasificación de activos');
    $genericos = CatalogoModel::administrar('generico');
    $grupos = CatalogoModel::administrar('grupo');
    $this->renderInventory('clasificacion', [
      'genericos' => $genericos, 'grupos' => $grupos,
      'especificos' => CatalogoModel::administrar('especifico'), 'genericos_para_modal' => $genericos,
      'grupos_para_modal' => $grupos, 'busqueda' => (string) ($_GET['q'] ?? '')
    ]);
  }
  public function marcas_modelos() { $this->setTitle('Marcas y modelos'); $this->renderInventory('marcas-modelos', ['marcas' => CatalogoModel::administrar('marca'), 'modelos' => CatalogoModel::administrar('modelo'), 'busqueda' => (string) ($_GET['q'] ?? '')]); }
  public function marca_modelo() { $this->marcas_modelos(); }
  public function estados_uso() { $this->setTitle('Estados de uso'); $this->renderInventory('estados-uso', ['estados' => CatalogoModel::administrar('estado')]); }
  public function ubicaciones() { $this->setTitle('Ubicaciones'); $this->renderInventory('ubicaciones', ['ubicaciones' => CatalogoModel::administrar('ubicacion')]); }
  public function unidades_admin() { $this->setTitle('Unidades administrativas'); $this->renderInventory('unidades-admin', ['unidades' => CatalogoModel::unidadesAdministrativas(), 'unidades_para_padre' => CatalogoModel::unidades()]); }
  public function resguardantes()
  {
    $this->setTitle('Catálogo de resguardantes');
    $resguardantes = ResguardanteModel::listar((string) ($_GET['q'] ?? ''));
    $this->renderInventory('resguardantes', [
      'resguardantes' => $resguardantes,
      'unidades' => CatalogoModel::unidades(), 'busqueda' => (string) ($_GET['q'] ?? '')
    ]);
  }

  public function cambiar_resguardante($id = null)
  {
    $this->can('catalogos-gestionar');
    $anteriorId = (int) $id;
    $actual = ResguardanteModel::porId($anteriorId);
    if (!$actual) { Flasher::error('El resguardante solicitado no existe.'); Redirect::to('catalogos/resguardantes'); }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      try {
        if (!Csrf::validate($_POST['csrf'] ?? '')) throw new Exception(get_bee_message(0));
        $reasignaciones = is_array($_POST['reasignaciones'] ?? null) ? $_POST['reasignaciones'] : [];
        $transferidos = ResguardanteModel::transferirVigentesYDesactivar($anteriorId, $reasignaciones, get_user() ?: null);
        Flasher::success(sprintf('Se transfirieron %d bienes y se desactivó el resguardante anterior.', $transferidos));
        Redirect::to('catalogos/resguardantes');
      } catch (Throwable $e) {
        Flasher::error($e->getMessage());
        Redirect::to('catalogos/cambiar_resguardante/' . $anteriorId);
      }
    }
    $bienes = ResguardanteModel::bienesVigentes($anteriorId);
    if (!$bienes) { Flasher::error('Este resguardante ya no tiene bienes vigentes.'); Redirect::to('catalogos/resguardantes'); }
    $this->setTitle('Cambiar resguardante y desactivar');
    $this->renderInventory('resguardante-transferir', [
      'resguardante' => $actual, 'bienes' => $bienes,
      'resguardantes_disponibles' => array_values(array_filter(ResguardanteModel::activos(), fn($item) => (int) $item['id_resguardante'] !== $anteriorId))
    ]);
  }

  public function guardar($tipo = null, $id = null)
  {
    $esJson = ($_POST['respuesta'] ?? '') === 'json';
    try {
      $this->can('catalogos-gestionar');
      if (!Csrf::validate($_POST['csrf'] ?? '')) throw new Exception(get_bee_message(0));
      if ($tipo === 'resguardante') {
        $resguardanteId = $id ? (int) $id : (!empty($_POST['id_resguardante']) ? (int) $_POST['id_resguardante'] : null);
        $resguardanteId = ResguardanteModel::guardar($_POST, $resguardanteId, get_user() ?: null);
        if ($esJson) {
          $resguardante = ResguardanteModel::porId($resguardanteId);
          header('Content-Type: application/json; charset=utf-8');
          echo json_encode(['ok' => true, 'resguardante' => $resguardante], JSON_UNESCAPED_UNICODE);
          return;
        }
      } else {
        CatalogoModel::guardar((string) $tipo, $_POST, $id ? (int) $id : null);
      }
      Flasher::success('Catálogo actualizado correctamente.');
    } catch (Exception $e) {
      if ($esJson) {
        http_response_code(422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        return;
      }
      Flasher::error($e->getMessage());
    }
    Redirect::back();
  }

  public function cambiar_estado($tipo = null, $id = null)
  {
    try {
      $this->can('catalogos-gestionar');
      if (!Csrf::validate($_GET['_t'] ?? '')) throw new Exception(get_bee_message(0));
      if ($tipo === 'resguardante') ResguardanteModel::cambiarEstado((int) $id);
      else CatalogoModel::cambiarEstado((string) $tipo, (int) $id);
      Flasher::success('Estado del catálogo actualizado.');
    } catch (Exception $e) { Flasher::error($e->getMessage()); }
    Redirect::back();
  }

  public function eliminar_unidad($id = null)
  {
    try {
      $this->can('catalogos-gestionar');
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('La solicitud de eliminación no es válida.');
      if (!Csrf::validate($_POST['csrf'] ?? '')) throw new Exception(get_bee_message(0));
      CatalogoModel::eliminarUnidadSiNoTieneDependencias((int) $id);
      Flasher::success('La unidad administrativa se eliminó porque no tiene registros relacionados.');
    } catch (Exception $e) { Flasher::error($e->getMessage()); }
    Redirect::back();
  }
}
