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
  ];

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
    if ($tipo === 'unidad' && (empty($datos['codigo_ua']) || empty($datos['nombre']))) throw new Exception('Código y nombre son requeridos.');
    if ($id) { parent::update($config['tabla'], [$config['id'] => $id], $datos); return $id; }
    if ($tipo !== 'unidad') $datos['activo'] = 1;
    return (int) parent::add($config['tabla'], $datos);
  }

  public static function cambiarEstado(string $tipo, int $id): bool
  {
    if (!isset(self::TIPOS[$tipo]) || $tipo === 'unidad') throw new Exception('Esta acción no está disponible para este catálogo.');
    $config = self::TIPOS[$tipo];
    $fila = parent::list($config['tabla'], [$config['id'] => $id], 1);
    if (!$fila) throw new Exception('El registro solicitado no existe.');
    return parent::update($config['tabla'], [$config['id'] => $id], ['activo' => $fila['activo'] ? 0 : 1]);
  }

  public static function activosGenericos(): array
  {
    return parent::query('SELECT * FROM activo_generico WHERE activo = 1 ORDER BY nombre') ?: [];
  }

  public static function grupos(?int $activoGenericoId = null): array
  {
    $sql = 'SELECT * FROM grupo_activo WHERE activo = 1';
    $params = [];
    if ($activoGenericoId) {
      $sql .= ' AND id_activo_generico = :id';
      $params['id'] = $activoGenericoId;
    }
    return parent::query($sql . ' ORDER BY nombre', $params) ?: [];
  }

  public static function activosEspecificos(?int $grupoId = null): array
  {
    $sql = 'SELECT * FROM activo_especifico WHERE activo = 1';
    $params = [];
    if ($grupoId) {
      $sql .= ' AND id_grupo_activo = :id';
      $params['id'] = $grupoId;
    }
    return parent::query($sql . ' ORDER BY nombre', $params) ?: [];
  }

  /** Devuelve el id existente o crea el valor escrito por el usuario. */
  public static function obtenerOCrearActivoEspecifico(string $nombre, int $grupoId): int
  {
    $nombre = trim($nombre);
    $sql = 'SELECT id_activo_especifico FROM activo_especifico WHERE id_grupo_activo = :grupo AND nombre = :nombre LIMIT 1';
    if ($row = parent::query($sql, ['grupo' => $grupoId, 'nombre' => $nombre])) {
      return (int) $row[0]['id_activo_especifico'];
    }
    return (int) parent::add('activo_especifico', [
      'id_grupo_activo' => $grupoId, 'nombre' => $nombre, 'activo' => 1
    ]);
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
    $nombre = trim($nombre);
    if ($nombre === '') return null;
    if (!$marcaId) throw new Exception('Selecciona una marca antes de capturar el modelo.');
    $sql = 'SELECT id_modelo FROM modelo WHERE id_marca = :marca AND nombre = :nombre LIMIT 1';
    if ($row = parent::query($sql, ['marca' => $marcaId, 'nombre' => $nombre])) return (int) $row[0]['id_modelo'];
    return (int) parent::add('modelo', ['id_marca' => $marcaId, 'nombre' => $nombre, 'activo' => 1]);
  }

  public static function estados(): array { return parent::query('SELECT * FROM estado_uso ORDER BY nombre') ?: []; }
  public static function ubicaciones(): array
  {
    return parent::query('SELECT MIN(id_ubicacion) AS id_ubicacion, municipio, localidad FROM ubicacion WHERE activo = 1 GROUP BY municipio, localidad ORDER BY municipio, localidad') ?: [];
  }
  public static function municipios(): array
  {
    return parent::query('SELECT DISTINCT municipio FROM ubicacion WHERE activo = 1 ORDER BY municipio') ?: [];
  }
  public static function unidades(): array { return parent::query('SELECT * FROM unidad_administrativa ORDER BY codigo_ua') ?: []; }

  public static function unidadPorCodigo(string $codigo): array
  {
    $rows = parent::query('SELECT * FROM unidad_administrativa WHERE codigo_ua = :codigo LIMIT 1', ['codigo' => trim($codigo)]);
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
      $etiqueta = $nivel['codigo_ua'] . ' - ' . $nombre;
      if (empty($nivel['id_padre'])) $niveles['secretaria'] = $etiqueta;
      elseif (str_starts_with($nombre, 'Subsecretaría')) $niveles['subsecretaria'] = $etiqueta;
      elseif (str_starts_with($nombre, 'Dirección General')) $niveles['direccion_secretaria'] = $etiqueta;
      elseif (str_starts_with($nombre, 'Dirección')) $niveles['direccion_area'] = $etiqueta;
      elseif (str_starts_with($nombre, 'Delegación Administrativa')) $niveles['delegacion_administrativa'] = $etiqueta;
      elseif (str_starts_with($nombre, 'Subdirección')) $niveles['subdireccion'] = $etiqueta;
      elseif (str_starts_with($nombre, 'Departamento')) $niveles['departamento'] = $etiqueta;
      elseif (str_starts_with($nombre, 'Oficina')) $niveles['oficina'] = $etiqueta;
    }
    return $niveles;
  }
}
