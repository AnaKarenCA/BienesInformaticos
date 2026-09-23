<?php

class BienModel extends Model
{
  private const SELECT = "SELECT b.*, ua.codigo_ua, ua.nombre AS unidad_nombre, m.nombre AS marca_nombre,
    mo.nombre AS modelo_nombre, eu.nombre AS estado_nombre, ae.nombre AS activo_especifico_nombre,
    ga.nombre AS grupo_nombre, ag.nombre AS activo_generico_nombre,
    CONCAT(r.nombre, ' ', r.apellido_paterno, ' ', COALESCE(r.apellido_materno, '')) AS resguardante_nombre,
    ub.municipio, ub.localidad, ub.ubicacion_fisica, COALESCE(rg.fecha_asignacion, b.fecha_asignacion) AS fecha_asignacion_actual,
    cb.codigo AS codigo_barra, rg.id_resguardante
    FROM bien b
    INNER JOIN unidad_administrativa ua ON ua.id_unidad = b.id_unidad
    LEFT JOIN marca m ON m.id_marca = b.id_marca
    LEFT JOIN modelo mo ON mo.id_modelo = b.id_modelo AND mo.id_marca = b.id_marca
    LEFT JOIN estado_uso eu ON eu.id_estado_uso = b.id_estado_uso
    LEFT JOIN ubicacion ub ON ub.id_ubicacion = b.id_ubicacion
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
      $sql .= ' AND (b.numero_inventario LIKE :q OR b.clave_interna LIKE :q OR b.nombre_bien LIKE :q OR b.numero_serie LIKE :q OR b.nic_cea LIKE :q OR ua.nombre LIKE :q OR ua.codigo_ua LIKE :q OR m.nombre LIKE :q OR r.nombre LIKE :q OR r.apellido_paterno LIKE :q)';
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
    $bien['jerarquia_administrativa'] = CatalogoModel::jerarquiaUnidad((int) $bien['id_unidad']);
    $bien['componentes'] = parent::query('SELECT * FROM componente WHERE id_bien = :id AND activo = 1 ORDER BY tipo_componente', ['id' => $id]) ?: [];
    $bien['movimientos'] = parent::query('SELECT * FROM movimiento_bien WHERE id_bien = :id ORDER BY fecha_movimiento DESC', ['id' => $id]) ?: [];
    return $bien;
  }

  public static function identificar(string $valor): array
  {
    $busqueda = '%' . trim($valor) . '%';
    $sql = self::SELECT . ' WHERE b.numero_inventario LIKE :inventario OR b.clave_interna LIKE :clave_interna OR b.numero_serie LIKE :serie OR b.nic_cea LIKE :nic_cea OR cb.codigo LIKE :codigo_barra ORDER BY b.id_bien DESC LIMIT 1';
    $params = [
      'inventario' => $busqueda, 'clave_interna' => $busqueda, 'serie' => $busqueda,
      'nic_cea' => $busqueda, 'codigo_barra' => $busqueda
    ];
    return ($rows = parent::query($sql, $params)) ? self::porId((int) $rows[0]['id_bien']) : [];
  }

  /** Recupera el código activo o lo crea usando exclusivamente la Clave Interna. */
  public static function codigoBarra(int $bienId): array
  {
    $bien = self::porId($bienId);
    if (!$bien) return [];
    if (empty($bien['clave_interna'])) throw new Exception('El bien no cuenta con una Clave Interna para generar su código de barras.');

    $existente = parent::query('SELECT id_codigo_barra, codigo, activo FROM codigo_barra WHERE id_bien = :bien ORDER BY activo DESC, fecha_generacion DESC LIMIT 1', ['bien' => $bienId]);
    if ($existente) {
      $registro = $existente[0];
      if ($registro['codigo'] !== $bien['clave_interna'] || !(int) $registro['activo']) {
        parent::update('codigo_barra', ['id_codigo_barra' => $registro['id_codigo_barra']], [
          'codigo' => $bien['clave_interna'], 'tipo_codigo' => 'CODE128', 'activo' => 1
        ]);
      }
      return ['codigo' => $bien['clave_interna'], 'tipo_codigo' => 'CODE128'];
    }

    parent::add('codigo_barra', [
      'id_bien' => $bienId, 'codigo' => $bien['clave_interna'], 'tipo_codigo' => 'CODE128', 'activo' => 1
    ]);
    return ['codigo' => $bien['clave_interna'], 'tipo_codigo' => 'CODE128'];
  }

  public static function siguienteCI(): string
  {
    $marcaTiempo = date('YmdHis');
    for ($sufijo = 1; $sufijo <= 9; $sufijo++) {
      $clave = 'CI-' . $marcaTiempo . $sufijo;
      if (!parent::query('SELECT id_bien FROM bien WHERE clave_interna = :clave LIMIT 1', ['clave' => $clave])) return $clave;
    }
    throw new Exception('No fue posible generar una Clave Interna única; intenta nuevamente.');
  }

  public static function inventarioEnUso(string $inventario, ?int $exceptoId = null): bool
  {
    $sql = 'SELECT id_bien FROM bien WHERE numero_inventario = :inventario';
    $params = ['inventario' => $inventario];
    if ($exceptoId) { $sql .= ' AND id_bien != :id'; $params['id'] = $exceptoId; }
    return (bool) parent::query($sql . ' LIMIT 1', $params);
  }

  public static function guardar(array $datos, array $componentes = [], ?int $id = null): int
  {
    $datos['id_activo_especifico'] = CatalogoModel::obtenerOCrearActivoEspecifico($datos['activo_especifico_nombre'], (int) $datos['id_grupo_activo']);
    $datos['id_modelo'] = CatalogoModel::obtenerOCrearModelo($datos['modelo_nombre'] ?? '', (int) ($datos['id_marca'] ?? 0));
    unset($datos['activo_especifico_nombre'], $datos['id_grupo_activo'], $datos['modelo_nombre']);
    if ($id) {
      parent::update('bien', ['id_bien' => $id], $datos);
    } else {
      $datos['clave_interna'] = self::siguienteCI();
      $id = (int) parent::add('bien', $datos);
      parent::add('codigo_barra', ['id_bien' => $id, 'codigo' => $datos['clave_interna'], 'tipo_codigo' => 'CODE128', 'activo' => 1]);
    }
    self::sincronizarComponentes($id, $componentes);
    return $id;
  }

  private static function sincronizarComponentes(int $bienId, array $componentes): void
  {
    $existentes = parent::query('SELECT id_componente FROM componente WHERE id_bien = :bien AND activo = 1', ['bien' => $bienId]) ?: [];
    $pendientes = array_flip(array_map(fn($fila) => (int) $fila['id_componente'], $existentes));
    foreach ($componentes as $componente) {
      $tipo = trim((string) ($componente['tipo_componente'] ?? ''));
      if ($tipo === '') continue;
      $datos = ['tipo_componente' => $tipo, 'marca' => trim((string) ($componente['marca'] ?? '')) ?: null,
        'modelo' => trim((string) ($componente['modelo'] ?? '')) ?: null,
        'numero_serie' => trim((string) ($componente['numero_serie'] ?? '')) ?: null,
        'numero_inventario' => trim((string) ($componente['numero_inventario'] ?? '')) ?: null, 'activo' => 1];
      $idComponente = (int) ($componente['id_componente'] ?? 0);
      if ($idComponente && isset($pendientes[$idComponente])) {
        parent::update('componente', ['id_componente' => $idComponente], $datos);
        unset($pendientes[$idComponente]);
      } else {
        $datos['id_bien'] = $bienId;
        parent::add('componente', $datos);
      }
    }
    foreach (array_keys($pendientes) as $idComponente) parent::update('componente', ['id_componente' => $idComponente], ['activo' => 0]);
  }

  public static function cambiarEstado(int $id): bool
  {
    $bien = self::porId($id);
    return !empty($bien) && parent::update('bien', ['id_bien' => $id], ['activo' => $bien['activo'] ? 0 : 1]);
  }

  public static function asignarResguardante(int $bienId, ?int $resguardanteId, ?string $fecha): void
  {
    $actual = parent::query('SELECT * FROM resguardo WHERE id_bien = :bien AND activo = 1 LIMIT 1', ['bien' => $bienId]);
    if (!$resguardanteId) {
      if (!$actual) return;
      $resguardo = $actual[0];
      parent::update('resguardo', ['id_resguardo' => $resguardo['id_resguardo']], ['activo' => 0, 'fecha_devolucion' => date('Y-m-d')]);
      parent::add('movimiento_bien', ['id_bien' => $bienId, 'tipo_movimiento' => 'Liberación de resguardo', 'resguardante_anterior' => $resguardo['id_resguardante'], 'fecha_movimiento' => now()]);
      return;
    }
    if ($actual && (int) $actual[0]['id_resguardante'] === $resguardanteId) {
      if ($fecha) parent::update('resguardo', ['id_resguardo' => $actual[0]['id_resguardo']], ['fecha_asignacion' => $fecha]);
      return;
    }
    if ($actual) parent::update('resguardo', ['id_resguardo' => $actual[0]['id_resguardo']], ['activo' => 0, 'fecha_devolucion' => date('Y-m-d')]);
    parent::add('resguardo', ['id_bien' => $bienId, 'id_resguardante' => $resguardanteId, 'fecha_asignacion' => $fecha ?: date('Y-m-d'), 'activo' => 1]);
    parent::add('movimiento_bien', ['id_bien' => $bienId, 'tipo_movimiento' => 'Asignación de resguardo', 'resguardante_nuevo' => $resguardanteId, 'fecha_movimiento' => now()]);
  }
}
