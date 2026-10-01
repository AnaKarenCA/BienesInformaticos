<?php

class bienesController extends InventoryController implements ControllerInterface
{
  public function index()
  {
    $this->can('bienes-consultar');
    $this->setTitle('Inventario de bienes');
    $bienes = BienModel::buscar($_GET);
    if (($_GET['ajax'] ?? '') === '1') {
      $this->renderInventory('inventory-rows', ['bienes' => $bienes]);
      return;
    }
    $this->renderInventory('index', [
      'bienes' => $bienes,
      'filtros' => $_GET
    ]);
  }

  public function registrar()
  {
    $this->can('bienes-crear');
    $this->formulario();
  }

  public function editar($id = null)
  {
    $this->can('bienes-actualizar');
    $bien = BienModel::porId((int) $id, false);
    if (!$bien) { Flasher::error('El bien solicitado no existe.'); Redirect::to('bienes'); }
    $this->formulario($bien);
  }

  private function formulario(array $bien = []): void
  {
    $unidadesConAncestros = CatalogoModel::unidadesConJerarquia($bien['id_unidad'] ?? null);
    $unidades = array_values(array_filter($unidadesConAncestros, static fn($unidad) => (int) $unidad['activo'] === 1 || (int) $unidad['id_unidad'] === (int) ($bien['id_unidad'] ?? 0)));
    $this->setTitle(empty($bien) ? 'Registrar bien' : 'Editar bien');
    $this->renderInventory('registrar', [
      'bien' => $bien, 'siguiente_ci' => $bien['clave_interna'] ?? BienModel::siguienteCI(),
      'activos_genericos' => CatalogoModel::activosGenericos($bien['id_activo_generico'] ?? null),
      'grupos' => !empty($bien['id_activo_generico']) ? CatalogoModel::grupos((int) $bien['id_activo_generico'], $bien['id_grupo_activo'] ?? null) : [],
      'activos_especificos' => !empty($bien['id_grupo_activo']) ? CatalogoModel::activosEspecificos((int) $bien['id_grupo_activo'], $bien['id_activo_especifico'] ?? null) : [],
      'todos_grupos' => CatalogoModel::grupos(), 'todos_especificos' => CatalogoModel::activosEspecificos(),
      'marcas' => CatalogoModel::marcas($bien['id_marca'] ?? null), 'modelos' => CatalogoModel::modelos(null, $bien['id_modelo'] ?? null),
      'materiales' => CatalogoModel::materiales($bien['id_material'] ?? null), 'colores' => CatalogoModel::colores($bien['id_color'] ?? null),
      'estados' => CatalogoModel::estados($bien['id_estado_uso'] ?? null), 'unidades' => $unidades, 'ubicaciones' => CatalogoModel::ubicaciones($bien['id_ubicacion'] ?? null), 'municipios' => CatalogoModel::municipios($bien['id_ubicacion'] ?? null),
      'resguardantes' => ResguardanteModel::activos($bien['id_resguardante'] ?? null),
      'unidades_resguardante' => $unidades,
      'unidades_json' => json_encode($unidadesConAncestros, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
      'componentes_json' => json_encode($bien['componentes'] ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
    ]);
  }

  public function detalle($id = null)
  {
    $this->can('bienes-consultar');
    $bien = BienModel::porId((int) $id);
    if (!$bien) { Flasher::error('El bien solicitado no existe.'); Redirect::to('bienes'); }
    $this->setTitle('Detalle del bien');
    $codigoQr = !empty($bien['clave_interna']) ? BienModel::codigoQr((int) $id) : [];
    $this->renderInventory('detalle', ['bien' => $bien, 'codigo_qr' => $codigoQr]);
  }

  public function codigo_barra($id = null)
  {
    $this->can('bienes-consultar');
    $this->can('bienes-imprimir');
    $bien = BienModel::porId((int) $id);
    if (!$bien) { Flasher::error('El bien solicitado no existe.'); Redirect::to('bienes'); }
    $codigoQr = BienModel::codigoQr((int) $id);
    $this->setTitle('Código QR del bien');
    $this->renderInventory('codigo-qr', ['bien' => $bien, 'codigo_qr' => $codigoQr]);
  }

  /** Devuelve el QR del identificador existente para el modal del inventario. */
  public function codigo_qr($id = null)
  {
    try {
      $this->can('bienes-consultar');
      $bien = BienModel::porId((int) $id, false);
      if (!$bien) { http_response_code(404); exit; }
      $codigoQr = BienModel::codigoQr((int) $id);
      header('Content-Type: image/svg+xml; charset=utf-8');
      header('Cache-Control: private, max-age=300');
      echo $codigoQr['svg'];
      exit;
    } catch (Throwable $e) {
      http_response_code(404);
      exit;
    }
  }

  public function guardar($id = null)
  {
    try {
      $this->can($id ? 'bienes-actualizar' : 'bienes-crear');
      if (!Csrf::validate($_POST['csrf'] ?? '') || empty($_POST['numero_inventario']) || empty($_POST['nombre_bien']) || empty($_POST['id_activo_generico']) || empty($_POST['id_grupo_activo']) || empty($_POST['id_activo_especifico']) || empty($_POST['id_unidad'])) {
        throw new Exception('Completa los campos obligatorios del bien.');
      }
      $unidad = CatalogoModel::unidadPorId((int) $_POST['id_unidad']);
      if (!$unidad) throw new Exception('Selecciona un Código de Unidad Administrativa válido.');
      $codigoCapturado = trim((string) ($_POST['codigo_ua'] ?? ''));
      if ($unidad['codigo_ua'] !== null && $codigoCapturado !== (string) $unidad['codigo_ua']) {
        throw new Exception('El Código de Unidad Administrativa no corresponde a la unidad seleccionada.');
      }
      $_POST['id_unidad'] = $unidad['id_unidad'];
      $nullable = ['nic_cea', 'id_marca', 'id_modelo', 'id_material', 'id_color', 'id_estado_uso', 'numero_serie', 'caracteristicas', 'observaciones', 'fecha_alta', 'fecha_adquisicion', 'fecha_elaboracion', 'fecha_asignacion', 'valor', 'id_ubicacion', 'piso', 'seccion_ala', 'cubiculo'];
      $datos = [];
      foreach (array_merge(['numero_inventario', 'id_unidad', 'id_activo_generico', 'id_grupo_activo', 'id_activo_especifico'], $nullable) as $campo) {
        $valor = isset($_POST[$campo]) ? trim((string) $_POST[$campo]) : null;
        $datos[$campo] = $valor === '' ? null : $valor;
      }
      $datos['nombre_bien'] = trim($_POST['nombre_bien']);
      $datos['caracteristicas'] = $datos['caracteristicas'] ?: 'S/C';
      $observaciones = (string) ($_POST['observaciones'] ?? '');
      $datos['observaciones'] = $observaciones === '' ? 'S/O' : $observaciones;
      if (!$id) $datos['activo'] = 1;
      if (BienModel::inventarioEnUso((string) $datos['numero_inventario'], $id ? (int) $id : null)) throw new Exception('El Inventario SICOPA ya está asignado a otro bien.');
      $componentes = json_decode($_POST['componentes'] ?? '[]', true) ?: [];
      if (!is_array($componentes)) throw new Exception('Los componentes recibidos no son válidos.');
      $bienId = BienModel::guardar($datos, $componentes, $id ? (int) $id : null, get_user() ?: null);
      $cspSeleccionado = trim((string) ($_POST['csp'] ?? ''));
      BienModel::asignarResguardante($bienId, $cspSeleccionado !== '' ? $cspSeleccionado : null, $datos['fecha_asignacion'], get_user() ?: null);
      Flasher::success('El bien fue guardado correctamente.');
      Redirect::to($id ? 'bienes' : 'bienes/detalle/' . $bienId);
    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  public function cambiar_estado($id = null)
  {
    try {
      if (!Csrf::validate($_GET['_t'] ?? '')) throw new Exception(get_bee_message(0));
      $bien = BienModel::porId((int) $id, false);
      if (!$bien) throw new Exception('El bien solicitado no existe.');
      $this->can((int) ($bien['activo'] ?? 0) === 1 ? 'bienes-desactivar' : 'bienes-activar');
      if (!BienModel::cambiarEstado((int) $id, get_user() ?: null)) throw new Exception('No fue posible actualizar el estado del bien.');
      Flasher::success('Estado del bien actualizado.');
    } catch (Exception $e) { Flasher::error($e->getMessage()); }
    Redirect::back();
  }
}
