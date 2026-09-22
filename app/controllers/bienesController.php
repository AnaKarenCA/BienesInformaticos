<?php

class bienesController extends InventoryController implements ControllerInterface
{
  public function index()
  {
    $this->setTitle('Inventario de bienes');
    $this->renderInventory('index', [
      'bienes' => BienModel::buscar($_GET),
      'estados' => CatalogoModel::estados(), 'marcas' => CatalogoModel::marcas(), 'filtros' => $_GET
    ]);
  }

  public function registrar()
  {
    $this->can('bienes-guardar');
    $this->formulario();
  }

  public function editar($id = null)
  {
    $this->can('bienes-guardar');
    $bien = BienModel::porId((int) $id);
    if (!$bien) { Flasher::error('El bien solicitado no existe.'); Redirect::to('bienes'); }
    $this->formulario($bien);
  }

  private function formulario(array $bien = []): void
  {
    $this->setTitle(empty($bien) ? 'Registrar bien' : 'Editar bien');
    $this->renderInventory('registrar', [
      'bien' => $bien, 'siguiente_ci' => $bien['numero_inventario'] ?? BienModel::siguienteCI(),
      'activos_genericos' => CatalogoModel::activosGenericos(), 'grupos' => CatalogoModel::grupos($bien['id_activo_generico'] ?? null),
      'activos_especificos' => CatalogoModel::activosEspecificos($bien['id_grupo_activo'] ?? null),
      'todos_grupos' => CatalogoModel::grupos(), 'todos_especificos' => CatalogoModel::activosEspecificos(),
      'marcas' => CatalogoModel::marcas(), 'modelos' => CatalogoModel::modelos($bien['id_marca'] ?? null),
      'estados' => CatalogoModel::estados(), 'unidades' => CatalogoModel::unidades(), 'ubicaciones' => CatalogoModel::ubicaciones(),
      'resguardantes' => ResguardanteModel::activos()
    ]);
  }

  public function detalle($id = null)
  {
    $bien = BienModel::porId((int) $id);
    if (!$bien) { Flasher::error('El bien solicitado no existe.'); Redirect::to('bienes'); }
    $this->setTitle('Detalle del bien');
    $this->renderInventory('detalle', ['bien' => $bien]);
  }

  public function guardar($id = null)
  {
    try {
      $this->can('bienes-guardar');
      if (!Csrf::validate($_POST['csrf'] ?? '') || empty($_POST['numero_inventario']) || empty($_POST['nombre_bien']) || empty($_POST['id_grupo_activo']) || empty($_POST['activo_especifico_nombre']) || empty($_POST['id_unidad'])) {
        throw new Exception('Completa los campos obligatorios del bien.');
      }
      $nullable = ['nic_cea', 'material', 'id_marca', 'id_modelo', 'color', 'id_estado_uso', 'numero_serie', 'caracteristicas', 'observaciones', 'fecha_alta', 'fecha_adquisicion', 'fecha_elaboracion', 'fecha_asignacion', 'valor', 'id_ubicacion'];
      $datos = [];
      foreach (array_merge(['numero_inventario', 'id_unidad', 'id_grupo_activo', 'activo_especifico_nombre'], $nullable) as $campo) {
        $valor = isset($_POST[$campo]) ? trim((string) $_POST[$campo]) : null;
        $datos[$campo] = $valor === '' ? null : $valor;
      }
      $datos['nombre_bien'] = trim($_POST['nombre_bien']);
      $datos['activo'] = isset($_POST['activo']) ? 1 : 0;
      $componentes = json_decode($_POST['componentes'] ?? '[]', true) ?: [];
      $bienId = BienModel::guardar($datos, $componentes, $id ? (int) $id : null);
      BienModel::asignarResguardante($bienId, !empty($_POST['id_resguardante']) ? (int) $_POST['id_resguardante'] : null, $datos['fecha_asignacion']);
      Flasher::success('El bien fue guardado correctamente.');
      Redirect::to('bienes/detalle/' . $bienId);
    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  public function cambiar_estado($id = null)
  {
    try {
      $this->can('bienes-inactivar');
      if (!Csrf::validate($_GET['_t'] ?? '')) throw new Exception(get_bee_message(0));
      if (!BienModel::cambiarEstado((int) $id)) throw new Exception('No fue posible actualizar el estado del bien.');
      Flasher::success('Estado del bien actualizado.');
    } catch (Exception $e) { Flasher::error($e->getMessage()); }
    Redirect::back();
  }
}
