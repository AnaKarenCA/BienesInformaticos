<?php

class catalogosController extends InventoryController implements ControllerInterface
{
  public function __construct()
  {
    parent::__construct();
  }

  public function index() { $this->clasificacion(); }
  public function clasificacion()
  {
    $this->can('catalogos-consultar');
    $this->setTitle('Clasificación de activos');
    $genericos = CatalogoModel::administrar('generico');
    $grupos = CatalogoModel::administrar('grupo');
    $especificos = CatalogoModel::administrar('especifico');
    $especificosPorGrupo = [];
    foreach ($especificos as $especifico) $especificosPorGrupo[(int) $especifico['id_grupo_activo']][] = $especifico;
    $gruposPorGenerico = [];
    foreach ($grupos as $grupo) {
      $grupo['activos_especificos'] = $especificosPorGrupo[(int) $grupo['id_grupo_activo']] ?? [];
      $gruposPorGenerico[(int) $grupo['id_activo_generico']][] = $grupo;
    }
    $clasificacionTree = [];
    foreach ($genericos as $generico) {
      $generico['grupos'] = $gruposPorGenerico[(int) $generico['id_activo_generico']] ?? [];
      $clasificacionTree[] = $generico;
    }
    $this->renderInventory('clasificacion', [
      'genericos' => $genericos, 'grupos' => $grupos,
      'especificos' => $especificos, 'clasificacion_tree' => $clasificacionTree,
      'genericos_para_modal' => $genericos, 'grupos_para_modal' => $grupos,
      'busqueda' => (string) ($_GET['q'] ?? '')
    ]);
  }
  public function marcas_modelos() { $this->can('catalogos-consultar'); $this->setTitle('Marcas y modelos'); $this->renderInventory('marcas-modelos', ['marcas' => CatalogoModel::administrar('marca'), 'modelos' => CatalogoModel::administrar('modelo'), 'busqueda' => (string) ($_GET['q'] ?? '')]); }
  public function marca_modelo() { $this->marcas_modelos(); }
  public function colores() { $this->catalogoNombre('color', 'Colores', 'id_color'); }
  public function materiales() { $this->catalogoNombre('material', 'Materiales', 'id_material'); }
  private function catalogoNombre(string $tipo, string $titulo, string $idField): void
  {
    $this->can('catalogos-consultar');
    $this->setTitle($titulo);
    $this->renderInventory('catalogo-simple', [
      'tipo' => $tipo, 'titulo' => $titulo, 'id_field' => $idField,
      'registros' => CatalogoModel::administrar($tipo)
    ]);
  }
  public function especificos()
  {
    $this->clasificacion();
  }
  public function estados_uso() { $this->can('catalogos-consultar'); $this->setTitle('Estados de uso'); $this->renderInventory('estados-uso', ['estados' => CatalogoModel::administrar('estado')]); }
  public function ubicaciones() { $this->can('catalogos-consultar'); $this->setTitle('Ubicaciones'); $this->renderInventory('ubicaciones', ['ubicaciones' => CatalogoModel::administrar('ubicacion')]); }
  public function unidades_admin() { $this->can('catalogos-consultar'); $this->setTitle('Unidades administrativas'); $this->renderInventory('unidades-admin', ['unidades' => CatalogoModel::unidadesAdministrativas(), 'unidades_para_padre' => CatalogoModel::unidadesConJerarquia(null, true), 'tipos_unidad' => CatalogoModel::tiposUnidad()]); }
  public function unidades() { $this->unidades_admin(); }
  public function resguardantes()
  {
    $this->can('catalogos-consultar');
    $this->setTitle('Catálogo de resguardantes');
    $resguardantes = ResguardanteModel::listar('');
    $this->renderInventory('resguardantes', [
      'resguardantes' => $resguardantes,
      'unidades' => CatalogoModel::unidadesConJerarquia(null, true), 'busqueda' => (string) ($_GET['q'] ?? '')
    ]);
  }

  public function cambiar_resguardante($id = null)
  {
    $this->can('catalogos-actualizar');
    $this->can('catalogos-desactivar');
    $this->can('traspasos-crear');
    $anteriorId = (int) $id;
    $actual = ResguardanteModel::porId($anteriorId);
    if (!$actual) { Flasher::error('El resguardante solicitado no existe.'); Redirect::to('catalogos/resguardantes'); }
    $bienes = ResguardanteModel::bienesVigentes($anteriorId);
    if (!$bienes) { Flasher::error('Este resguardante ya no tiene bienes vigentes.'); Redirect::to('catalogos/resguardantes'); }
    Flasher::info('La reasignación debe registrarse mediante uno o varios expedientes de traspaso. El resguardante podrá desactivarse cuando ya no tenga bienes vigentes.');
    Redirect::to('traspasos/crear?resguardante_id=' . (int) $actual['id_resguardante']);
  }

  public function guardar($tipo = null, $id = null)
  {
    $esJson = ($_POST['respuesta'] ?? '') === 'json';
    try {
      $isUpdate = !empty($id) || ($tipo === 'resguardante' && !empty($_POST['id_resguardante']));
      $this->can($isUpdate ? 'catalogos-actualizar' : 'catalogos-crear');
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
        $catalogoId = CatalogoModel::guardar((string) $tipo, $_POST, $id ? (int) $id : null);
        if ($esJson) {
          header('Content-Type: application/json; charset=utf-8');
          echo json_encode(['ok' => true, 'registro' => CatalogoModel::porId((string) $tipo, $catalogoId)], JSON_UNESCAPED_UNICODE);
          return;
        }
      }
      Flasher::success('Catálogo actualizado correctamente.');
    } catch (Exception $e) {
      if ($esJson) {
        try { $duplicado = CatalogoModel::encontrarDuplicado((string) $tipo, $_POST); }
        catch (Throwable $lookupError) { $duplicado = []; }
        if ($duplicado && (str_contains($e->getMessage(), 'Ya existe') || str_contains($e->getMessage(), 'ya existe'))) {
          $nombre = (string) ($duplicado['nombre'] ?? '');
          if (array_key_exists('activo', $duplicado) && !(int) $duplicado['activo']) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => "El registro \"{$nombre}\" ya existe, pero está inactivo. Actívalo desde Catálogos para poder seleccionarlo."], JSON_UNESCAPED_UNICODE);
            return;
          }
          $mensaje = match ($tipo) {
            'generico' => "El Activo Genérico \"{$nombre}\" ya existe. Se seleccionó el registro existente.",
            'grupo' => "El Grupo del Activo \"{$nombre}\" ya existe para ese Activo Genérico. Se seleccionó el registro existente.",
            'especifico' => "El Activo Específico \"{$nombre}\" ya existe en este Grupo del Activo. Se seleccionó el registro existente.",
            'color' => "El color \"{$nombre}\" ya existe. Se seleccionó el registro existente.",
            'material' => "El material \"{$nombre}\" ya existe. Se seleccionó el registro existente.",
            default => 'Ese valor ya existía; se seleccionó el registro existente.'
          };
          header('Content-Type: application/json; charset=utf-8');
          echo json_encode(['ok' => true, 'duplicado' => true, 'registro' => $duplicado, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
          return;
        }
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
      if (!Csrf::validate($_GET['_t'] ?? '')) throw new Exception(get_bee_message(0));
      $registro = $tipo === 'resguardante' ? ResguardanteModel::porId((int) $id) : CatalogoModel::porId((string) $tipo, (int) $id);
      if (!$registro) throw new Exception('El registro solicitado no existe.');
      $this->can((int) ($registro['activo'] ?? 0) === 1 ? 'catalogos-desactivar' : 'catalogos-activar');
      if ($tipo === 'resguardante') ResguardanteModel::cambiarEstado((int) $id);
      else CatalogoModel::cambiarEstado((string) $tipo, (int) $id);
      Flasher::success('Estado del catálogo actualizado.');
    } catch (Exception $e) { Flasher::error($e->getMessage()); }
    Redirect::back();
  }

}
