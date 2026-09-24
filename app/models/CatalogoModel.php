<?php

/** Catálogos que alimentan el inventario patrimonial. */
class CatalogoModel extends Model
{
  private const TIPOS = [
    'generico' => ['tabla' => 'activo_generico', 'id' => 'id_activo_generico', 'campos' => ['nombre', 'descripcion']],
    'grupo' => ['tabla' => 'grupo_activo', 'id' => 'id_grupo_activo', 'campos' => ['id_activo_generico', 'nombre', 'descripcion']],
    'especifico' => ['tabla' => 'activo_especifico', 'id' => 'id_activo_especifico', 'campos' => ['id_grupo_activo', 'nombre', 'descripcion']],
    'marca' => ['tabla' => 'marca', 'id' => 'id_marca', 'campos' => ['nombre']],
    'modelo' => ['tabla' => 'modelo', 'id' => 'id_modelo', 'campos' => ['id_marca', 'nombre']],
    'ubicacion' => ['tabla' => 'ubicacion', 'id' => 'id_ubicacion', 'campos' => ['municipio', 'localidad']],
    'unidad' => ['tabla' => 'unidad_administrativa', 'id' => 'id_unidad', 'campos' => ['codigo_ua', 'nombre', 'id_padre']],
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
    $filas = parent::query('SELECT * FROM ' . $config['tabla']) ?: [];
    if ($id) {
      foreach ($filas as $fila) {
        if ((int) $fila[$config['id']] !== $id) continue;
        $mismosDatosUnicos = match ($tipo) {
          'generico', 'marca', 'estado' => self::normalizarUnico($fila['nombre']) === self::normalizarUnico($datos['nombre'] ?? ''),
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
        case 'generico': case 'marca': case 'estado':
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
      $datos[$campo] = $valor === '' ? null : $valor;
    }
    if (empty($datos['nombre']) && !in_array($tipo, ['ubicacion', 'unidad'], true)) throw new Exception('El nombre es requerido.');
    if ($tipo === 'ubicacion' && (empty($datos['municipio']) || empty($datos['localidad']))) throw new Exception('Municipio y localidad son requeridos.');
    if ($tipo === 'unidad' && empty($datos['nombre'])) throw new Exception('El nombre de la unidad es requerido.');
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
    if ($tipo === 'modelo' && !self::relacionValidaOActual('marca', 'id_marca', (int) ($datos['id_marca'] ?? 0), 'modelo', 'id_modelo', 'id_marca', $id)) throw new Exception('Selecciona una marca existente y activa.');
    if ($id && !parent::query('SELECT ' . $config['id'] . ' FROM ' . $config['tabla'] . ' WHERE ' . $config['id'] . ' = :id LIMIT 1', ['id' => $id])) throw new Exception('El registro solicitado no existe.');
    self::validarDuplicado($tipo, $datos, $id);
    try {
      if ($id) { parent::update($config['tabla'], [$config['id'] => $id], $datos); return $id; }
      if ($tipo !== 'unidad') $datos['activo'] = 1;
      return (int) parent::add($config['tabla'], $datos);
    } catch (PDOException $e) {
      if (str_contains($e->getMessage(), '23000') || str_contains($e->getMessage(), '1062')) throw new Exception('El registro ya existe. Verifica la información capturada.');
      throw $e;
    }
  }

  public static function cambiarEstado(string $tipo, int $id): bool
  {
    if (!isset(self::TIPOS[$tipo]) || $tipo === 'unidad') throw new Exception('Esta acción no está disponible para este catálogo.');
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
        'grupo' => ['activo_generico', 'id_activo_generico', (int) $fila['id_activo_generico']],
        'especifico' => ['grupo_activo', 'id_grupo_activo', (int) $fila['id_grupo_activo']],
        'modelo' => ['marca', 'id_marca', (int) $fila['id_marca']],
      ];
      if (isset($padres[$tipo])) {
        [$tabla, $campo, $padreId] = $padres[$tipo];
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
    $sql = 'SELECT * FROM grupo_activo WHERE activo = 1';
    $params = [];
    if ($activoGenericoId) {
      $sql .= ' AND id_activo_generico = :id';
      $params['id'] = $activoGenericoId;
    }
    if ($incluirId) { $sql .= ' OR id_grupo_activo = :incluir'; $params['incluir'] = $incluirId; }
    return parent::query($sql . ' ORDER BY nombre', $params) ?: [];
  }

  public static function activosEspecificos(?int $grupoId = null, ?int $incluirId = null): array
  {
    $sql = 'SELECT * FROM activo_especifico WHERE activo = 1';
    $params = [];
    if ($grupoId) {
      $sql .= ' AND id_grupo_activo = :id';
      $params['id'] = $grupoId;
    }
    if ($incluirId) { $sql .= ' OR id_activo_especifico = :incluir'; $params['incluir'] = $incluirId; }
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

  public static function marcas(): array { return parent::query('SELECT * FROM marca WHERE activo = 1 ORDER BY nombre') ?: []; }
  public static function modelos(?int $marcaId = null): array
  {
    $sql = 'SELECT * FROM modelo WHERE activo = 1'; $params = [];
    if ($marcaId) { $sql .= ' AND id_marca = :id'; $params['id'] = $marcaId; }
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
  public static function ubicaciones(): array
  {
    return parent::query('SELECT MIN(id_ubicacion) AS id_ubicacion, municipio, localidad FROM ubicacion WHERE activo = 1 GROUP BY municipio, localidad ORDER BY municipio, localidad') ?: [];
  }
  public static function municipios(): array
  {
    return parent::query('SELECT DISTINCT municipio FROM ubicacion WHERE activo = 1 ORDER BY municipio') ?: [];
  }
  public static function unidades(): array { return parent::query('SELECT * FROM unidad_administrativa ORDER BY codigo_ua IS NULL, codigo_ua, nombre') ?: []; }

  public static function unidadesAdministrativas(): array
  {
    return parent::query('SELECT u.*, p.nombre AS padre_nombre FROM unidad_administrativa u LEFT JOIN unidad_administrativa p ON p.id_unidad = u.id_padre ORDER BY u.codigo_ua IS NULL, u.codigo_ua, u.nombre') ?: [];
  }

  public static function unidadPorCodigo(string $codigo): array
  {
    $rows = parent::query('SELECT * FROM unidad_administrativa WHERE codigo_ua = :codigo LIMIT 1', ['codigo' => trim($codigo)]);
    return $rows ? $rows[0] : [];
  }
  public static function eliminarUnidadSiNoTieneDependencias(int $id): void
  {
    if (!self::unidadExiste($id)) throw new Exception('La unidad administrativa solicitada no existe.');
    $dependencias = [
      ['unidad_administrativa', 'id_padre', 'otras unidades administrativas'],
      ['bien', 'id_unidad', 'bienes'],
      ['resguardante', 'id_unidad', 'resguardantes'],
    ];
    foreach ($dependencias as [$tabla, $campo, $etiqueta]) {
      if (parent::query("SELECT {$campo} FROM {$tabla} WHERE {$campo} = :id LIMIT 1", ['id' => $id])) {
        throw new Exception("No se puede eliminar: la unidad tiene {$etiqueta} relacionados.");
      }
    }
    parent::query('DELETE FROM unidad_administrativa WHERE id_unidad = :id', ['id' => $id]);
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

  public static function unidadExiste(int $id): bool
  {
    return (bool) parent::query('SELECT id_unidad FROM unidad_administrativa WHERE id_unidad = :id LIMIT 1', ['id' => $id]);
  }

  /**
   * La estructura administrativa ya existe como árbol; estos campos se derivan
   * de sus ancestros y no se duplican como columnas nuevas.
   */
  public static function unidadesConJerarquia(): array
  {
    $unidades = self::unidades();
    foreach ($unidades as &$unidad) $unidad['administracion'] = self::jerarquiaUnidad((int) $unidad['id_unidad'], $unidades);
    unset($unidad);
    return $unidades;
  }

  /** Deriva los niveles administrativos desde id_padre; no depende de columnas inexistentes. */
  public static function jerarquiaUnidad(int $unidadId, ?array $unidades = null): array
  {
    $unidades ??= self::unidades();
    $porId = [];
    foreach ($unidades as $unidad) $porId[(int) $unidad['id_unidad']] = $unidad;

    $cadena = [];
    $cursor = $porId[$unidadId] ?? null;
    while ($cursor) {
      array_unshift($cadena, $cursor);
      $padre = $cursor['id_padre'] ?? null;
      $cursor = $padre ? ($porId[(int) $padre] ?? null) : null;
    }

    $niveles = [
      'secretaria' => '', 'subsecretaria' => '', 'direccion_secretaria' => '', 'direccion_area' => '',
      'delegacion_administrativa' => '', 'subdireccion' => '', 'departamento' => '', 'oficina' => ''
    ];
    foreach ($cadena as $nivel) {
      $nombre = (string) $nivel['nombre'];
      $nombreNormalizado = strtr(mb_strtolower($nombre, 'UTF-8'), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
      $nombrePresentacion = mb_convert_case(mb_strtolower($nombre, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
      foreach (['SICOPA', 'CEA', 'CSP', 'CI', 'UA', 'OIC', 'PPS', 'TIC'] as $siglas) {
        $nombrePresentacion = preg_replace('/\b' . $siglas . '\b/iu', $siglas, $nombrePresentacion);
      }
      // El nombre de la unidad es su dato; el nivel se muestra aparte en la vista.
      // No incorporar código UA ni nombres de unidades padre en esta etiqueta.
      $etiqueta = $nombrePresentacion;
      if (empty($nivel['id_padre'])) $niveles['secretaria'] = $etiqueta;
      elseif (str_starts_with($nombreNormalizado, 'subsecretaria')) $niveles['subsecretaria'] = $etiqueta;
      elseif (str_starts_with($nombreNormalizado, 'direccion de area') || str_starts_with($nombreNormalizado, 'direccion area')) $niveles['direccion_area'] = $etiqueta;
      elseif (str_starts_with($nombreNormalizado, 'direccion')) $niveles['direccion_secretaria'] = $etiqueta;
      elseif (str_starts_with($nombreNormalizado, 'delegacion administrativa')) $niveles['delegacion_administrativa'] = $etiqueta;
      elseif (str_starts_with($nombreNormalizado, 'subdireccion')) $niveles['subdireccion'] = $etiqueta;
      elseif (str_starts_with($nombreNormalizado, 'departamento')) $niveles['departamento'] = $etiqueta;
      elseif (str_starts_with($nombreNormalizado, 'oficina')) $niveles['oficina'] = $etiqueta;
    }
    return $niveles;
  }
}
