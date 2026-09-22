<?php

class BienModel extends Model
{
  private const SELECT = "SELECT b.*, ua.codigo_ua, ua.nombre AS unidad_nombre, m.nombre AS marca_nombre,
    mo.nombre AS modelo_nombre, eu.nombre AS estado_nombre, ae.nombre AS activo_especifico_nombre,
    ga.nombre AS grupo_nombre, ag.nombre AS activo_generico_nombre,
    CONCAT(r.nombre, ' ', r.apellido_paterno, ' ', COALESCE(r.apellido_materno, '')) AS resguardante_nombre,
    cb.codigo AS codigo_barra, rg.id_resguardante
    FROM bien b
    INNER JOIN unidad_administrativa ua ON ua.id_unidad = b.id_unidad
    LEFT JOIN marca m ON m.id_marca = b.id_marca
    LEFT JOIN modelo mo ON mo.id_modelo = b.id_modelo
    LEFT JOIN estado_uso eu ON eu.id_estado_uso = b.id_estado_uso
    LEFT JOIN activo_especifico ae ON ae.id_activo_especifico = b.id_activo_especifico
    LEFT JOIN grupo_activo ga ON ga.id_grupo_activo = ae.id_grupo_activo
    LEFT JOIN activo_generico ag ON ag.id_activo_generico = ga.id_activo_generico
    LEFT JOIN codigo_barra cb ON cb.id_bien = b.id_bien AND cb.activo = 1
    LEFT JOIN resguardo rg ON rg.id_bien = b.id_bien AND rg.activo = 1
    LEFT JOIN resguardante r ON r.id_resguardante = rg.id_resguardante";

  public static function recientes(int $limite = 5): array
  {
    return parent::query(self::SELECT . ' ORDER BY b.fecha_registro DESC LIMIT ' . (int) $limite) ?: [];
  }

  public static function resumen(): array
  {
    $sql = "SELECT COUNT(*) total, COALESCE(SUM(activo = 1), 0) activos,
      COALESCE(SUM(activo = 1 AND NOT EXISTS (SELECT 1 FROM resguardo r WHERE r.id_bien = bien.id_bien AND r.activo = 1)), 0) pendientes,
      COALESCE(SUM(EXISTS (SELECT 1 FROM resguardo r WHERE r.id_bien = bien.id_bien AND r.activo = 1)), 0) con_resguardante FROM bien";
    return (parent::query($sql) ?: [['total' => 0, 'activos' => 0, 'pendientes' => 0, 'con_resguardante' => 0]])[0];
  }

  public static function buscar(array $filtros = []): array
  {
    $sql = self::SELECT . ' WHERE 1=1'; $params = [];
    if (!empty($filtros['q'])) {
      $sql .= ' AND (b.numero_inventario LIKE :q OR b.nombre_bien LIKE :q OR b.numero_serie LIKE :q OR b.nic_cea LIKE :q OR ua.nombre LIKE :q OR ua.codigo_ua LIKE :q OR m.nombre LIKE :q OR r.nombre LIKE :q OR r.apellido_paterno LIKE :q)';
      $params['q'] = '%' . trim($filtros['q']) . '%';
    }
    foreach (['estado' => 'b.id_estado_uso', 'marca' => 'b.id_marca', 'activo' => 'b.activo'] as $key => $column) {
      if (isset($filtros[$key]) && $filtros[$key] !== '') { $sql .= " AND {$column} = :{$key}"; $params[$key] = $filtros[$key]; }
    }
    return parent::query($sql . ' ORDER BY b.fecha_registro DESC', $params) ?: [];
  }

  public static function porId(int $id): array
  {
    $rows = parent::query(self::SELECT . ' WHERE b.id_bien = :id', ['id' => $id]);
    if (!$rows) return [];
    $bien = $rows[0];
    $bien['componentes'] = parent::query('SELECT * FROM componente WHERE id_bien = :id AND activo = 1 ORDER BY tipo_componente', ['id' => $id]) ?: [];
    $bien['movimientos'] = parent::query('SELECT * FROM movimiento_bien WHERE id_bien = :id ORDER BY fecha_movimiento DESC', ['id' => $id]) ?: [];
    return $bien;
  }

  public static function identificar(string $valor): array
  {
    $sql = self::SELECT . ' WHERE b.numero_inventario = :valor OR b.numero_serie = :valor OR b.nic_cea = :valor OR cb.codigo = :valor LIMIT 1';
    return ($rows = parent::query($sql, ['valor' => trim($valor)])) ? self::porId((int) $rows[0]['id_bien']) : [];
  }

  public static function siguienteCI(): string
  {
    $row = (parent::query("SELECT MAX(CAST(SUBSTRING(numero_inventario, 4) AS UNSIGNED)) AS consecutivo FROM bien WHERE numero_inventario REGEXP '^CI-[0-9]+$'") ?: [['consecutivo' => 0]])[0];
    return 'CI-' . str_pad((string) (((int) $row['consecutivo']) + 1), 6, '0', STR_PAD_LEFT);
  }

  public static function guardar(array $datos, array $componentes = [], ?int $id = null): int
  {
    $datos['id_activo_especifico'] = CatalogoModel::obtenerOCrearActivoEspecifico($datos['activo_especifico_nombre'], (int) $datos['id_grupo_activo']);
    unset($datos['activo_especifico_nombre'], $datos['id_grupo_activo']);
    if ($id) {
      parent::update('bien', ['id_bien' => $id], $datos);
    } else {
      $id = (int) parent::add('bien', $datos);
      parent::add('codigo_barra', ['id_bien' => $id, 'codigo' => $datos['numero_inventario'], 'tipo_codigo' => 'CODE128', 'activo' => 1]);
    }
    if ($componentes) {
      foreach ($componentes as $componente) {
        $componente['id_bien'] = $id;
        parent::add('componente', $componente);
      }
    }
    return $id;
  }

  public static function cambiarEstado(int $id): bool
  {
    $bien = self::porId($id);
    return !empty($bien) && parent::update('bien', ['id_bien' => $id], ['activo' => $bien['activo'] ? 0 : 1]);
  }

  public static function asignarResguardante(int $bienId, ?int $resguardanteId, ?string $fecha): void
  {
    if (!$resguardanteId) return;
    $actual = parent::query('SELECT * FROM resguardo WHERE id_bien = :bien AND activo = 1 LIMIT 1', ['bien' => $bienId]);
    if ($actual && (int) $actual[0]['id_resguardante'] === $resguardanteId) return;
    if ($actual) parent::update('resguardo', ['id_resguardo' => $actual[0]['id_resguardo']], ['activo' => 0, 'fecha_devolucion' => date('Y-m-d')]);
    parent::add('resguardo', ['id_bien' => $bienId, 'id_resguardante' => $resguardanteId, 'fecha_asignacion' => $fecha ?: date('Y-m-d'), 'activo' => 1]);
    parent::add('movimiento_bien', ['id_bien' => $bienId, 'tipo_movimiento' => 'Asignación de resguardo', 'resguardante_nuevo' => $resguardanteId, 'fecha_movimiento' => now()]);
  }
}
