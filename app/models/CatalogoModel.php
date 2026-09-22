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
    'ubicacion' => ['tabla' => 'ubicacion', 'id' => 'id_ubicacion', 'campos' => ['municipio', 'localidad', 'ubicacion_fisica']],
    'unidad' => ['tabla' => 'unidad_administrativa', 'id' => 'id_unidad', 'campos' => ['codigo_ua', 'nombre', 'tipo', 'id_padre']],
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
  public static function estados(): array { return parent::query('SELECT * FROM estado_uso ORDER BY nombre') ?: []; }
  public static function ubicaciones(): array { return parent::query('SELECT * FROM ubicacion WHERE activo = 1 ORDER BY municipio, localidad') ?: []; }
  public static function unidades(): array { return parent::query('SELECT * FROM unidad_administrativa ORDER BY codigo_ua') ?: []; }
}
