<?php

/** Catálogos que alimentan el inventario patrimonial. */
class CatalogoModel extends Model
{
  private static function unidadTieneColumnaActivo(): bool
  {
    static $tieneActivo = null;
    if ($tieneActivo === null) {
      $columnas = parent::query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'unidad_administrativa' AND COLUMN_NAME = 'activo' LIMIT 1");
      $tieneActivo = (bool) $columnas;
    }
    return $tieneActivo;
  }

  private const TIPOS = [
    'generico' => ['tabla' => 'activo_generico', 'id' => 'id_activo_generico', 'campos' => ['nombre', 'descripcion']],
    'grupo' => ['tabla' => 'grupo_activo', 'id' => 'id_grupo_activo', 'campos' => ['id_activo_generico', 'nombre', 'descripcion']],
    'especifico' => ['tabla' => 'activo_especifico', 'id' => 'id_activo_especifico', 'campos' => ['id_grupo_activo', 'nombre', 'descripcion']],
    'marca' => ['tabla' => 'marca', 'id' => 'id_marca', 'campos' => ['nombre']],
    'modelo' => ['tabla' => 'modelo', 'id' => 'id_modelo', 'campos' => ['id_marca', 'nombre']],
    'color' => ['tabla' => 'color', 'id' => 'id_color', 'campos' => ['nombre']],
    'material' => ['tabla' => 'material', 'id' => 'id_material', 'campos' => ['nombre']],
    'ubicacion' => ['tabla' => 'ubicacion', 'id' => 'id_ubicacion', 'campos' => ['municipio', 'localidad']],
    'unidad' => ['tabla' => 'unidad_administrativa', 'id' => 'id_unidad', 'campos' => ['codigo_ua', 'nombre', 'tipo', 'id_padre']],
    'estado' => ['tabla' => 'estado_uso', 'id' => 'id_estado_uso', 'campos' => ['nombre']],
  ];

  private static function normalizarUnico($valor): string
  {
    $valor = preg_replace('/\s+/u', ' ', trim((string) $valor));
    return mb_strtolower($valor, 'UTF-8');
  }

  /** Comprueba combinaciones naturales del catálogo, conservando el ID editado. */
  private static function validarDuplicado(string $tipo, array $datos, ?int $id): void
  {
    $config = self::TIPOS[$tipo];
    if (in_array($tipo, ['generico', 'grupo', 'especifico', 'marca', 'modelo', 'estado', 'color', 'material'], true)) {
      if (self::encontrarDuplicado($tipo, $datos, $id)) throw new Exception('Ya existe un registro con esa información en este catálogo.');
      return;
    }
    $filas = parent::query('SELECT * FROM ' . $config['tabla']) ?: [];
    if ($id) {
      foreach ($filas as $fila) {
        if ((int) $fila[$config['id']] !== $id) continue;
        $mismosDatosUnicos = match ($tipo) {
          'generico', 'marca', 'estado', 'color', 'material' => self::normalizarUnico($fila['nombre']) === self::normalizarUnico($datos['nombre'] ?? ''),
          'grupo' => (int) $fila['id_activo_generico'] === (int) ($datos['id_activo_generico'] ?? 0) && self::normalizarUnico($fila['nombre']) === self::normalizarUnico($datos['nombre'] ?? ''),
          'especifico' => (int) $fila['id_grupo_activo'] === (int) ($datos['id_grupo_activo'] ?? 0) && self::normalizarUnico($fila['nombre']) === self::normalizarUnico($datos['nombre'] ?? ''),
          'modelo' => (int) $fila['id_marca'] === (int) ($datos['id_marca'] ?? 0) && self::normalizarUnico($fila['nombre']) === self::normalizarUnico($datos['nombre'] ?? ''),
          'ubicacion' => self::normalizarUnico($fila['municipio']) === self::normalizarUnico($datos['municipio'] ?? '') && self::normalizarUnico($fila['localidad']) === self::normalizarUnico($datos['localidad'] ?? ''),
          'unidad' => (($datos['codigo_ua'] ?? null) !== null && $fila['codigo_ua'] !== null && self::normalizarUnico($fila['codigo_ua']) === self::normalizarUnico($datos['codigo_ua'])) && (int) ($fila['id_padre'] ?? 0) === (int) ($datos['id_padre'] ?? 0) && self::normalizarUnico($fila['nombre']) === self::normalizarUnico($datos['nombre'] ?? ''),
          default => false,
        };
        if ($mismosDatosUnicos) return;
        break;
      }
    }
    foreach ($filas as $fila) {
      if ($id && (int) $fila[$config['id']] === $id) continue;
      $igual = static fn($a, $b) => self::normalizarUnico($a) === self::normalizarUnico($b);
      $duplicado = false;
      switch ($tipo) {
        case 'generico': case 'marca': case 'estado': case 'color': case 'material':
          $duplicado = $igual($fila['nombre'] ?? '', $datos['nombre'] ?? ''); break;
        case 'grupo':
          $duplicado = (int) $fila['id_activo_generico'] === (int) $datos['id_activo_generico'] && $igual($fila['nombre'], $datos['nombre']); break;
        case 'especifico':
          $duplicado = (int) $fila['id_grupo_activo'] === (int) $datos['id_grupo_activo'] && $igual($fila['nombre'], $datos['nombre']); break;
        case 'modelo':
          $duplicado = (int) $fila['id_marca'] === (int) $datos['id_marca'] && $igual($fila['nombre'], $datos['nombre']); break;
        case 'ubicacion':
          $duplicado = $igual($fila['municipio'], $datos['municipio']) && $igual($fila['localidad'], $datos['localidad']); break;
        case 'unidad':
          $codigoUA = $datos['codigo_ua'] ?? null;
          $duplicado = ($codigoUA !== null && $fila['codigo_ua'] !== null && $igual($fila['codigo_ua'], $codigoUA))
            || ((int) ($fila['id_padre'] ?? 0) === (int) ($datos['id_padre'] ?? 0) && $igual($fila['nombre'], $datos['nombre'])); break;
      }
      if ($duplicado) throw new Exception('Ya existe un registro con esa información en este catálogo.');
    }
  }

  public static function guardar(string $tipo, array $entrada, ?int $id = null): int
  {
    if (!isset(self::TIPOS[$tipo])) throw new Exception('Catálogo no válido.');
    $config = self::TIPOS[$tipo];
    $datos = [];
    foreach ($config['campos'] as $campo) {
      $valor = isset($entrada[$campo]) ? trim((string) $entrada[$campo]) : null;
      if ($valor !== null && in_array($campo, ['nombre', 'municipio', 'localidad'], true)) $valor = preg_replace('/\s+/u', ' ', $valor);
      $datos[$campo] = $valor === '' ? null : $valor;
    }
    if (in_array($tipo, ['color', 'material'], true)) {
      $limite = $tipo === 'color' ? 100 : 150;
      if (mb_strlen((string) ($datos['nombre'] ?? ''), 'UTF-8') > $limite) throw new Exception('El nombre excede la longitud permitida para este catálogo.');
      $datos['nombre_normalizado'] = self::normalizarUnico($datos['nombre'] ?? '');
    }
    if (empty($datos['nombre']) && !in_array($tipo, ['ubicacion', 'unidad'], true)) throw new Exception('El nombre es requerido.');
    if ($tipo === 'ubicacion' && (empty($datos['municipio']) || empty($datos['localidad']))) throw new Exception('Municipio y localidad son requeridos.');
    if ($tipo === 'unidad' && empty($datos['nombre'])) throw new Exception('El nombre de la unidad es requerido.');
    if ($tipo === 'unidad' && empty($datos['tipo'])) {
      if (!$id) throw new Exception('Selecciona el tipo administrativo de la unidad.');
      $actual = self::unidadPorId($id);
      if (($actual['tipo'] ?? null) !== null) throw new Exception('Selecciona un tipo administrativo para la unidad.');
    }
    if ($tipo === 'unidad' && !empty($datos['tipo']) && !parent::query('SELECT clave_tipo FROM tipo_unidad_administrativa WHERE clave_tipo = :tipo AND activo = 1 LIMIT 1', ['tipo' => $datos['tipo']])) {
      throw new Exception('Selecciona un tipo administrativo válido.');
    }
    if ($tipo === 'unidad' && empty($datos['id_padre'])) $datos['id_padre'] = null;
    if ($tipo === 'unidad' && $datos['id_padre'] !== null) {
      $padreId = (int) $datos['id_padre'];
      if ($padreId < 1 || !self::unidadExiste($padreId)) throw new Exception('Selecciona una unidad padre existente.');
      if ($id && $padreId === $id) throw new Exception('Una unidad no puede ser su propia unidad padre.');
      if ($id && self::unidadEsDescendiente($padreId, $id)) throw new Exception('No puedes asignar como padre una unidad que depende de esta unidad.');
      $datos['id_padre'] = $padreId;
    }
    if ($tipo === 'grupo' && !self::relacionValidaOActual('activo_generico', 'id_activo_generico', (int) ($datos['id_activo_generico'] ?? 0), 'grupo_activo', 'id_grupo_activo', 'id_activo_generico', $id)) throw new Exception('Selecciona un activo genérico existente y activo.');
    if ($tipo === 'especifico' && !self::relacionValidaOActual('grupo_activo', 'id_grupo_activo', (int) ($datos['id_grupo_activo'] ?? 0), 'activo_especifico', 'id_activo_especifico', 'id_grupo_activo', $id)) throw new Exception('Selecciona un grupo de activo existente y activo.');
    if ($tipo === 'especifico' && !empty($entrada['id_activo_generico'])) {
      $grupo = parent::query('SELECT id_activo_generico FROM grupo_activo WHERE id_grupo_activo = :id LIMIT 1', ['id' => (int) $datos['id_grupo_activo']]);
      if (!$grupo || (int) $grupo[0]['id_activo_generico'] !== (int) $entrada['id_activo_generico']) throw new Exception('El grupo no corresponde al activo genérico seleccionado.');
    }
    if ($tipo === 'modelo' && !self::relacionValidaOActual('marca', 'id_marca', (int) ($datos['id_marca'] ?? 0), 'modelo', 'id_modelo', 'id_marca', $id)) throw new Exception('Selecciona una marca existente y activa.');
    if ($id && !parent::query('SELECT ' . $config['id'] . ' FROM ' . $config['tabla'] . ' WHERE ' . $config['id'] . ' = :id LIMIT 1', ['id' => $id])) throw new Exception('El registro solicitado no existe.');
    self::validarDuplicado($tipo, $datos, $id);
    try {
      if ($id) { parent::update($config['tabla'], [$config['id'] => $id], $datos); return $id; }
      if (!$id && ($tipo !== 'unidad' || self::unidadTieneColumnaActivo())) $datos['activo'] = 1;
      return (int) parent::add($config['tabla'], $datos);
    } catch (PDOException $e) {
      if (str_contains($e->getMessage(), '23000') || str_contains($e->getMessage(), '1062')) throw new Exception('El registro ya existe. Verifica la información capturada.');
      throw $e;
    }
  }

  public static function cambiarEstado(string $tipo, int $id): bool
  {
    if (!isset(self::TIPOS[$tipo])) throw new Exception('Esta acción no está disponible para este catálogo.');
    if ($tipo === 'unidad' && !self::unidadTieneColumnaActivo()) throw new Exception('La base de datos actual no incluye estado para las unidades administrativas.');
    if ($tipo === 'unidad' && parent::query('SELECT id_unidad FROM unidad_administrativa WHERE id_padre = :id LIMIT 1', ['id' => $id])) {
      throw new Exception('No se puede cambiar el estado de esta unidad mientras tenga unidades dependientes.');
    }
    $config = self::TIPOS[$tipo];
    $fila = parent::list($config['tabla'], [$config['id'] => $id], 1);
    if (!$fila) throw new Exception('El registro solicitado no existe.');
    if ((int) $fila['activo'] === 1) {
      $dependencias = [
        'generico' => ['grupo_activo', 'id_activo_generico'],
        'grupo' => ['activo_especifico', 'id_grupo_activo'],
        'marca' => ['modelo', 'id_marca'],
      ];
      if (isset($dependencias[$tipo])) {
        [$tabla, $campo] = $dependencias[$tipo];
        if (parent::query("SELECT {$campo} FROM {$tabla} WHERE {$campo} = :id AND activo = 1 LIMIT 1", ['id' => $id])) {
          throw new Exception('No se puede desactivar este registro mientras tenga elementos activos relacionados.');
        }
      }
    } else {
      $padres = [
        'grupo' => ['activo_generico', 'id_activo_generico'],
        'especifico' => ['grupo_activo', 'id_grupo_activo'],
        'modelo' => ['marca', 'id_marca'],
      ];
      if (isset($padres[$tipo])) {
        [$tabla, $campo] = $padres[$tipo];
        $padreId = (int) $fila[$campo];
        if (!self::relacionActiva($tabla, $campo, $padreId)) throw new Exception('Activa primero el elemento superior relacionado antes de reactivar este registro.');
      }
    }
    return parent::update($config['tabla'], [$config['id'] => $id], ['activo' => $fila['activo'] ? 0 : 1]);
  }

  private static function relacionActiva(string $tabla, string $campoId, int $id): bool
  {
    return $id > 0 && (bool) parent::query("SELECT {$campoId} FROM {$tabla} WHERE {$campoId} = :id AND activo = 1 LIMIT 1", ['id' => $id]);
  }

  private static function relacionValidaOActual(string $tablaPadre, string $campoPadre, int $padreId, string $tablaHija, string $campoHijoId, string $campoRelacion, ?int $registroId): bool
  {
    if (self::relacionActiva($tablaPadre, $campoPadre, $padreId)) return true;
    if (!$registroId || $padreId < 1) return false;
    return (bool) parent::query("SELECT {$campoHijoId} FROM {$tablaHija} WHERE {$campoHijoId} = :registro AND {$campoRelacion} = :padre LIMIT 1", ['registro' => $registroId, 'padre' => $padreId]);
  }

  /** Registros activos e inactivos para administrar catálogos sin perder historial. */
  public static function administrar(string $tipo): array
  {
    $consultas = [
      'generico' => 'SELECT * FROM activo_generico ORDER BY nombre',
      'grupo' => 'SELECT g.*, a.nombre AS padre_nombre FROM grupo_activo g INNER JOIN activo_generico a ON a.id_activo_generico = g.id_activo_generico ORDER BY a.nombre, g.nombre',
      'especifico' => 'SELECT e.*, g.nombre AS padre_nombre, a.nombre AS generico_nombre FROM activo_especifico e INNER JOIN grupo_activo g ON g.id_grupo_activo = e.id_grupo_activo INNER JOIN activo_generico a ON a.id_activo_generico = g.id_activo_generico ORDER BY a.nombre, g.nombre, e.nombre',
      'marca' => 'SELECT * FROM marca ORDER BY nombre',
      'modelo' => 'SELECT mo.*, m.nombre AS marca_nombre FROM modelo mo INNER JOIN marca m ON m.id_marca = mo.id_marca ORDER BY m.nombre, mo.nombre',
      'estado' => 'SELECT * FROM estado_uso ORDER BY nombre',
      'color' => 'SELECT * FROM color ORDER BY nombre',
      'material' => 'SELECT * FROM material ORDER BY nombre',
      'ubicacion' => 'SELECT id_ubicacion, municipio, localidad, activo FROM ubicacion ORDER BY municipio, localidad, id_ubicacion',
    ];
    if (!isset($consultas[$tipo])) throw new Exception('Catálogo no válido.');
    return parent::query($consultas[$tipo]) ?: [];
  }

  public static function activosGenericos(?int $incluirId = null): array
  {
    $sql = 'SELECT * FROM activo_generico WHERE activo = 1'; $params = [];
    if ($incluirId) { $sql .= ' OR id_activo_generico = :incluir'; $params['incluir'] = $incluirId; }
    return parent::query($sql . ' ORDER BY nombre', $params) ?: [];
  }

  public static function grupos(?int $activoGenericoId = null, ?int $incluirId = null): array
  {
    $params = [];
    if ($activoGenericoId) {
      $where = '((activo = 1 AND id_activo_generico = :generico)';
      $params['generico'] = $activoGenericoId;
      if ($incluirId) { $where .= ' OR (id_grupo_activo = :incluir AND id_activo_generico = :generico_inc)'; $params['incluir'] = $incluirId; $params['generico_inc'] = $activoGenericoId; }
      $sql = 'SELECT * FROM grupo_activo WHERE ' . $where . ')';
    } else {
      $sql = 'SELECT * FROM grupo_activo WHERE activo = 1';
      if ($incluirId) { $sql .= ' OR id_grupo_activo = :incluir'; $params['incluir'] = $incluirId; }
    }
    return parent::query($sql . ' ORDER BY nombre', $params) ?: [];
  }

  public static function activosEspecificos(?int $grupoId = null, ?int $incluirId = null): array
  {
    $params = [];
    if ($grupoId) {
      $where = '((activo = 1 AND id_grupo_activo = :grupo)';
      $params['grupo'] = $grupoId;
      if ($incluirId) { $where .= ' OR (id_activo_especifico = :incluir AND id_grupo_activo = :grupo_inc)'; $params['incluir'] = $incluirId; $params['grupo_inc'] = $grupoId; }
      $sql = 'SELECT * FROM activo_especifico WHERE ' . $where . ')';
    } else {
      $sql = 'SELECT * FROM activo_especifico WHERE activo = 1';
      if ($incluirId) { $sql .= ' OR id_activo_especifico = :incluir'; $params['incluir'] = $incluirId; }
    }
    return parent::query($sql . ' ORDER BY nombre', $params) ?: [];
  }

  /** Devuelve el id existente o crea el valor escrito por el usuario. */
  public static function obtenerOCrearActivoEspecifico(string $nombre, int $grupoId): int
  {
    $nombre = trim(preg_replace('/\s+/u', ' ', $nombre));
    foreach (parent::query('SELECT id_activo_especifico, nombre FROM activo_especifico WHERE id_grupo_activo = :grupo', ['grupo' => $grupoId]) ?: [] as $row) {
      if (self::normalizarUnico($row['nombre']) === self::normalizarUnico($nombre)) return (int) $row['id_activo_especifico'];
    }
    try {
      return (int) parent::add('activo_especifico', [
        'id_grupo_activo' => $grupoId, 'nombre' => $nombre, 'activo' => 1
      ]);
    } catch (PDOException $e) {
      if (str_contains($e->getMessage(), '23000') || str_contains($e->getMessage(), '1062')) throw new Exception('El activo específico ya existe para este grupo.');
      throw $e;
    }
  }

  public static function marcas(?int $incluirId = null): array { return self::catalogoActivo('marca', 'id_marca', $incluirId); }
  public static function colores(?int $incluirId = null): array { return self::catalogoActivo('color', 'id_color', $incluirId); }
  public static function materiales(?int $incluirId = null): array { return self::catalogoActivo('material', 'id_material', $incluirId); }
  private static function catalogoActivo(string $tabla, string $id, ?int $incluirId): array
  {
    $sql = "SELECT * FROM {$tabla} WHERE activo = 1"; $params = [];
    if ($incluirId) { $sql .= " OR {$id} = :incluir"; $params['incluir'] = $incluirId; }
    return parent::query($sql . ' ORDER BY nombre', $params) ?: [];
  }
  public static function porId(string $tipo, int $id): array
  {
    if (!isset(self::TIPOS[$tipo])) return [];
    $config = self::TIPOS[$tipo];
    $rows = parent::query('SELECT * FROM ' . $config['tabla'] . ' WHERE ' . $config['id'] . ' = :id LIMIT 1', ['id' => $id]);
    return $rows[0] ?? [];
  }
  public static function encontrarDuplicado(string $tipo, array $entrada, ?int $excluirId = null): array
  {
    if (!in_array($tipo, ['generico', 'grupo', 'especifico', 'marca', 'modelo', 'estado', 'color', 'material'], true)) return [];
    $config = self::TIPOS[$tipo];
    $columnaNombre = in_array($tipo, ['color', 'material'], true) ? 'nombre_normalizado' : 'nombre';
    $sql = 'SELECT * FROM ' . $config['tabla'] . ' WHERE ' . $columnaNombre . ' = :nombre';
    $nombre = trim(preg_replace('/\s+/u', ' ', (string) ($entrada['nombre'] ?? '')));
    $params = ['nombre' => in_array($tipo, ['color', 'material'], true) ? self::normalizarUnico($nombre) : $nombre];
    if ($tipo === 'grupo') { $sql .= ' AND id_activo_generico = :padre'; $params['padre'] = (int) ($entrada['id_activo_generico'] ?? 0); }
    if ($tipo === 'especifico') { $sql .= ' AND id_grupo_activo = :padre'; $params['padre'] = (int) ($entrada['id_grupo_activo'] ?? 0); }
    if ($tipo === 'modelo') { $sql .= ' AND id_marca = :padre'; $params['padre'] = (int) ($entrada['id_marca'] ?? 0); }
    if ($excluirId) { $sql .= ' AND ' . $config['id'] . ' <> :excluir'; $params['excluir'] = $excluirId; }
    $rows = parent::query($sql . ' ORDER BY activo DESC, ' . $config['id'] . ' ASC LIMIT 1', $params);
    return $rows[0] ?? [];
  }
  public static function modelos(?int $marcaId = null, ?int $incluirId = null): array
  {
    $params = [];
    if ($marcaId) {
      $where = '((activo = 1 AND id_marca = :marca)'; $params['marca'] = $marcaId;
      if ($incluirId) { $where .= ' OR (id_modelo = :incluir AND id_marca = :marca_inc)'; $params['incluir'] = $incluirId; $params['marca_inc'] = $marcaId; }
      $sql = 'SELECT * FROM modelo WHERE ' . $where . ')';
    } else {
      $sql = 'SELECT * FROM modelo WHERE activo = 1';
      if ($incluirId) { $sql .= ' OR id_modelo = :incluir'; $params['incluir'] = $incluirId; }
    }
    return parent::query($sql . ' ORDER BY nombre', $params) ?: [];
  }

  /** Devuelve el modelo existente o crea el texto capturado para la marca indicada. */
  public static function obtenerOCrearModelo(string $nombre, int $marcaId): ?int
  {
    $nombre = trim(preg_replace('/\s+/u', ' ', $nombre));
    if ($nombre === '') return null;
    if (!$marcaId) throw new Exception('Selecciona una marca antes de capturar el modelo.');
    foreach (parent::query('SELECT id_modelo, nombre FROM modelo WHERE id_marca = :marca', ['marca' => $marcaId]) ?: [] as $row) {
      if (self::normalizarUnico($row['nombre']) === self::normalizarUnico($nombre)) return (int) $row['id_modelo'];
    }
    try { return (int) parent::add('modelo', ['id_marca' => $marcaId, 'nombre' => $nombre, 'activo' => 1]); }
    catch (PDOException $e) {
      if (str_contains($e->getMessage(), '23000') || str_contains($e->getMessage(), '1062')) throw new Exception('El modelo ya existe para esta marca.');
      throw $e;
    }
  }

  public static function estados(?int $incluirId = null): array
  {
    $sql = 'SELECT * FROM estado_uso WHERE activo = 1'; $params = [];
    if ($incluirId) { $sql .= ' OR id_estado_uso = :incluir'; $params['incluir'] = $incluirId; }
    return parent::query($sql . ' ORDER BY nombre', $params) ?: [];
  }
  public static function ubicaciones(?int $incluirId = null): array
  {
    $sql = 'SELECT MIN(id_ubicacion) AS id_ubicacion, municipio, localidad FROM ubicacion WHERE activo = 1 GROUP BY municipio, localidad';
    $params = [];
    if ($incluirId) {
      $sql = 'SELECT ubicaciones.id_ubicacion, ubicaciones.municipio, ubicaciones.localidad FROM (' . $sql . ' UNION SELECT id_ubicacion, municipio, localidad FROM ubicacion WHERE id_ubicacion = :id) ubicaciones';
      $params['id'] = $incluirId;
    }
    return parent::query($sql . ' ORDER BY municipio, localidad', $params) ?: [];
  }
  public static function municipios(?int $incluirUbicacionId = null): array
  {
    $sql = 'SELECT DISTINCT municipio FROM ubicacion WHERE activo = 1'; $params = [];
    if ($incluirUbicacionId) {
      $sql = 'SELECT DISTINCT municipio FROM ubicacion WHERE activo = 1 UNION SELECT municipio FROM ubicacion WHERE id_ubicacion = :id';
      $params['id'] = $incluirUbicacionId;
    }
    return parent::query($sql . ' ORDER BY municipio', $params) ?: [];
  }
  public static function unidades(): array
  {
    $consulta = self::unidadTieneColumnaActivo()
      ? 'SELECT * FROM unidad_administrativa ORDER BY codigo_ua IS NULL, codigo_ua, nombre'
      : 'SELECT unidad_administrativa.*, 1 AS activo FROM unidad_administrativa ORDER BY codigo_ua IS NULL, codigo_ua, nombre';
    return parent::query($consulta) ?: [];
  }

  public static function unidadesAdministrativas(): array
  {
    $tieneActivo = self::unidadTieneColumnaActivo();
    $camposUnidad = $tieneActivo ? 'u.*' : 'u.*, 1 AS activo';
    $unidades = parent::query("SELECT {$camposUnidad}, p.nombre AS padre_nombre, t.nombre AS tipo_nombre, t.campo_visual, EXISTS(SELECT 1 FROM unidad_administrativa dependiente WHERE dependiente.id_padre = u.id_unidad) AS tiene_dependientes FROM unidad_administrativa u LEFT JOIN unidad_administrativa p ON p.id_unidad = u.id_padre LEFT JOIN tipo_unidad_administrativa t ON t.clave_tipo = u.tipo ORDER BY u.codigo_ua IS NULL, u.codigo_ua, u.nombre") ?: [];
    foreach ($unidades as &$unidad) {
      $unidad['estado_administrable'] = $tieneActivo;
      $unidad['tiene_dependientes'] = (bool) $unidad['tiene_dependientes'];
    }
    unset($unidad);
    return $unidades;
  }

  public static function unidadPorCodigo(string $codigo): array
  {
    $rows = parent::query('SELECT * FROM unidad_administrativa WHERE codigo_ua = :codigo LIMIT 1', ['codigo' => trim($codigo)]);
    return $rows ? $rows[0] : [];
  }

  /** Busca únicamente por código oficial y devuelve un máximo de tres coincidencias activas. */
  public static function buscarUnidadesPorCodigo(string $termino, int $limite = 3): array
  {
    $termino = trim($termino);
    if (strlen($termino) < 3 || strlen($termino) > 20) return [];

    $limite = max(1, min(3, $limite));
    $literal = strtr($termino, ['!' => '!!', '%' => '!%', '_' => '!_']);
    $unidades = parent::query(
      "SELECT u.id_unidad, u.codigo_ua, u.nombre, u.tipo, u.id_padre, u.activo
       FROM unidad_administrativa u
       WHERE u.activo = 1 AND u.codigo_ua IS NOT NULL AND u.codigo_ua <> ''
         AND u.codigo_ua LIKE :coincidencia ESCAPE '!'
       ORDER BY CASE WHEN u.codigo_ua = :exacto THEN 0
                     WHEN u.codigo_ua LIKE :prefijo ESCAPE '!' THEN 1 ELSE 2 END,
                ABS(CHAR_LENGTH(u.codigo_ua) - CHAR_LENGTH(:longitud)), u.codigo_ua
       LIMIT {$limite}",
      ['coincidencia' => '%' . $literal . '%', 'exacto' => $termino, 'prefijo' => $literal . '%', 'longitud' => $termino]
    ) ?: [];

    foreach ($unidades as &$unidad) {
      $administracion = self::jerarquiaUnidad((int) $unidad['id_unidad']);
      unset($administracion['ruta']);
      $unidad['administracion'] = $administracion;
    }
    unset($unidad);
    return $unidades;
  }
  private static function unidadEsDescendiente(int $posibleDescendiente, int $ancestro): bool
  {
    $unidades = self::unidades();
    $padres = [];
    foreach ($unidades as $unidad) $padres[(int) $unidad['id_unidad']] = (int) ($unidad['id_padre'] ?? 0);
    $cursor = $posibleDescendiente;
    $visitados = [];
    while ($cursor && !isset($visitados[$cursor])) {
      if ($cursor === $ancestro) return true;
      $visitados[$cursor] = true;
      $cursor = $padres[$cursor] ?? 0;
    }
    return false;
  }

  public static function unidadPorId(int $id): array
  {
    $rows = parent::query('SELECT * FROM unidad_administrativa WHERE id_unidad = :id LIMIT 1', ['id' => $id]);
    return $rows ? $rows[0] : [];
  }

  public static function tiposUnidad(): array
  {
    static $tipos = null;
    if ($tipos === null) $tipos = parent::query('SELECT clave_tipo, nombre, campo_visual FROM tipo_unidad_administrativa WHERE activo = 1 ORDER BY orden, nombre') ?: [];
    return $tipos;
  }

  private static function tiposUnidadPorClave(): array
  {
    static $porClave = null;
    if ($porClave === null) {
      $porClave = [];
      foreach (self::tiposUnidad() as $tipo) $porClave[$tipo['clave_tipo']] = $tipo;
    }
    return $porClave;
  }

  public static function unidadExiste(int $id): bool
  {
    return (bool) parent::query('SELECT id_unidad FROM unidad_administrativa WHERE id_unidad = :id LIMIT 1', ['id' => $id]);
  }

  public static function unidadActivaOActual(int $id, ?int $actualId = null): bool
  {
    if ($id < 1) return false;
    if (!self::unidadTieneColumnaActivo()) return self::unidadExiste($id);
    return (bool) parent::query('SELECT id_unidad FROM unidad_administrativa WHERE id_unidad = :id AND (activo = 1 OR id_unidad = :actual) LIMIT 1', ['id' => $id, 'actual' => (int) $actualId]);
  }

  /**
   * La estructura administrativa ya existe como árbol; estos campos se derivan
   * de sus ancestros y no se duplican como columnas nuevas.
   */
  public static function unidadesConJerarquia(?int $incluirId = null, bool $incluirInactivas = false): array
  {
    $todas = self::unidades();
    $porId = [];
    foreach ($todas as $unidad) $porId[(int) $unidad['id_unidad']] = $unidad;
    $incluidas = [];
    $agregarAncestros = static function (int $id) use (&$incluidas, $porId): void {
      $cursor = $id; $visitados = [];
      while ($cursor && !isset($visitados[$cursor]) && isset($porId[$cursor])) {
        $visitados[$cursor] = true; $incluidas[$cursor] = true;
        $cursor = (int) ($porId[$cursor]['id_padre'] ?? 0);
      }
    };
    foreach ($todas as $unidad) if ((int) $unidad['activo'] === 1) $agregarAncestros((int) $unidad['id_unidad']);
    if ($incluirInactivas) foreach ($todas as $unidad) $agregarAncestros((int) $unidad['id_unidad']);
    if ($incluirId) $agregarAncestros($incluirId);
    $unidades = array_values(array_filter($todas, static fn($unidad) => isset($incluidas[(int) $unidad['id_unidad']])));
    foreach ($unidades as &$unidad) {
      $unidad['administracion'] = self::jerarquiaUnidad((int) $unidad['id_unidad'], $unidades);
      $unidad['ruta_nombres'] = implode(' › ', array_column($unidad['administracion']['ruta'], 'nombre'));
    }
    unset($unidad);
    return $unidades;
  }

  /** Deriva los niveles administrativos desde id_padre; no depende de columnas inexistentes. */
  public static function jerarquiaUnidad(int $unidadId, ?array $unidades = null): array
  {
    $cadena = [];
    if ($unidades === null) {
      $cursor = self::unidadPorId($unidadId);
      $visitados = [];
      while ($cursor && !isset($visitados[(int) $cursor['id_unidad']])) {
        $visitados[(int) $cursor['id_unidad']] = true;
        array_unshift($cadena, $cursor);
        $cursor = !empty($cursor['id_padre']) ? self::unidadPorId((int) $cursor['id_padre']) : null;
      }
    } else {
      $porId = [];
      foreach ($unidades as $unidad) $porId[(int) $unidad['id_unidad']] = $unidad;
      $visitados = [];
      $cursor = $porId[$unidadId] ?? null;
      while ($cursor && !isset($visitados[(int) $cursor['id_unidad']])) {
        $visitados[(int) $cursor['id_unidad']] = true;
        array_unshift($cadena, $cursor);
        $padre = $cursor['id_padre'] ?? null;
        $cursor = $padre ? ($porId[(int) $padre] ?? null) : null;
      }
    }

    $niveles = [
      'secretaria' => '', 'subsecretaria' => '', 'direccion_secretaria' => '', 'direccion_area' => '',
      'delegacion_administrativa' => '', 'subdireccion' => '', 'departamento' => '', 'oficina' => ''
    ];
    $camposDetalle = [
      'SECRETARIA' => 'secretaria',
      'SUBSECRETARIA' => 'subsecretaria',
      'STAFF' => 'subsecretaria',
      'DIRECCION' => 'direccion_secretaria',
      'DIRECCION_AREA' => 'direccion_area',
      'DELEGACION_ADMINISTRATIVA' => 'delegacion_administrativa',
      'SUBDIRECCION' => 'subdireccion',
      'DEPARTAMENTO' => 'departamento',
      'OFICINA' => 'oficina'
    ];
    $etiquetasDetalle = [
      'secretaria' => 'Secretaría',
      'subsecretaria' => 'Subsecretaría',
      'direccion_secretaria' => 'Dirección',
      'direccion_area' => 'Dirección de Área',
      'delegacion_administrativa' => 'Delegación Administrativa',
      'subdireccion' => 'Subdirección',
      'departamento' => 'Departamento',
      'oficina' => 'Oficina'
    ];
    $nivelesDetalle = [];
    foreach ($etiquetasDetalle as $campo => $etiqueta) {
      $nivelesDetalle[$campo] = ['etiqueta' => $etiqueta, 'codigo_ua' => null, 'nombre' => null];
    }
    $ruta = [];
    foreach ($cadena as $nivel) {
      $nombre = (string) $nivel['nombre'];
      $tipo = $nivel['tipo'] ?? null;
      $tipoInfo = $tipo ? (self::tiposUnidadPorClave()[$tipo] ?? null) : null;
      $ruta[] = ['id_unidad' => (int) $nivel['id_unidad'], 'codigo_ua' => $nivel['codigo_ua'] ?? null, 'nombre' => $nombre, 'tipo' => $tipo, 'tipo_nombre' => $tipoInfo['nombre'] ?? 'Pendiente de asignar'];
      $campoVisual = $tipoInfo['campo_visual'] ?? null;
      if ($campoVisual !== null && array_key_exists($campoVisual, $niveles)) $niveles[$campoVisual] = $nombre;
      $campoDetalle = $camposDetalle[strtoupper((string) $tipo)] ?? null;
      if ($campoDetalle !== null) {
        $nivelesDetalle[$campoDetalle]['codigo_ua'] = $nivel['codigo_ua'] ?? null;
        $nivelesDetalle[$campoDetalle]['nombre'] = $nombre;
      }
    }
    $niveles['ruta'] = $ruta;
    $niveles['niveles_detalle'] = array_values($nivelesDetalle);
    return $niveles;
  }
}
