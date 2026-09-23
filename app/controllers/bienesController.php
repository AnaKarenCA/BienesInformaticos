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
    $unidades = CatalogoModel::unidadesConJerarquia();
    $this->setTitle(empty($bien) ? 'Registrar bien' : 'Editar bien');
    $this->renderInventory('registrar', [
      'bien' => $bien, 'siguiente_ci' => $bien['clave_interna'] ?? BienModel::siguienteCI(),
      'activos_genericos' => CatalogoModel::activosGenericos(), 'grupos' => CatalogoModel::grupos($bien['id_activo_generico'] ?? null),
      'activos_especificos' => CatalogoModel::activosEspecificos($bien['id_grupo_activo'] ?? null),
      'todos_grupos' => CatalogoModel::grupos(), 'todos_especificos' => CatalogoModel::activosEspecificos(),
      'marcas' => CatalogoModel::marcas(), 'modelos' => CatalogoModel::modelos(),
      'estados' => CatalogoModel::estados(), 'unidades' => $unidades, 'ubicaciones' => CatalogoModel::ubicaciones(), 'municipios' => CatalogoModel::municipios(),
      'resguardantes' => ResguardanteModel::activos(),
      'unidades_json' => json_encode($unidades, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
      'componentes_json' => json_encode($bien['componentes'] ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
    ]);
  }

  public function detalle($id = null)
  {
    $bien = BienModel::porId((int) $id);
    if (!$bien) { Flasher::error('El bien solicitado no existe.'); Redirect::to('bienes'); }
    $this->setTitle('Detalle del bien');
    $this->renderInventory('detalle', ['bien' => $bien]);
  }

  public function codigo_barra($id = null)
  {
    $bien = BienModel::porId((int) $id);
    if (!$bien) { Flasher::error('El bien solicitado no existe.'); Redirect::to('bienes'); }
    $codigo = BienModel::codigoBarra((int) $id);
    $this->setTitle('Código de barras del bien');
    $this->renderInventory('codigo-barra', ['bien' => $bien, 'codigo_barra' => $codigo]);
  }

  public function guardar($id = null)
  {
    try {
      $this->can('bienes-guardar');
      if (!Csrf::validate($_POST['csrf'] ?? '') || empty($_POST['numero_inventario']) || empty($_POST['nombre_bien']) || empty($_POST['id_grupo_activo']) || empty($_POST['activo_especifico_nombre']) || empty($_POST['codigo_ua'])) {
        throw new Exception('Completa los campos obligatorios del bien.');
      }
      $unidad = CatalogoModel::unidadPorCodigo((string) $_POST['codigo_ua']);
      if (!$unidad) throw new Exception('Selecciona un Código de Unidad Administrativa válido.');
      $_POST['id_unidad'] = $unidad['id_unidad'];
      if (!empty($_POST['modelo_nombre']) && empty($_POST['id_marca'])) throw new Exception('Selecciona una marca para el modelo capturado.');
      $nullable = ['nic_cea', 'material', 'id_marca', 'modelo_nombre', 'color', 'id_estado_uso', 'numero_serie', 'caracteristicas', 'observaciones', 'fecha_alta', 'fecha_adquisicion', 'fecha_elaboracion', 'fecha_asignacion', 'valor', 'id_ubicacion', 'piso', 'seccion_ala', 'cubiculo'];
      $datos = [];
      foreach (array_merge(['numero_inventario', 'id_unidad', 'id_grupo_activo', 'activo_especifico_nombre'], $nullable) as $campo) {
        $valor = isset($_POST[$campo]) ? trim((string) $_POST[$campo]) : null;
        $datos[$campo] = $valor === '' ? null : $valor;
      }
      $datos['nombre_bien'] = trim($_POST['nombre_bien']);
      $datos['caracteristicas'] = $datos['caracteristicas'] ?: 'S/C';
      $datos['activo'] = isset($_POST['activo']) ? 1 : 0;
      if (BienModel::inventarioEnUso((string) $datos['numero_inventario'], $id ? (int) $id : null)) throw new Exception('El Inventario SICOPA ya está asignado a otro bien.');
      $componentes = json_decode($_POST['componentes'] ?? '[]', true) ?: [];
      if (!is_array($componentes)) throw new Exception('Los componentes recibidos no son válidos.');
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
