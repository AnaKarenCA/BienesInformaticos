<?php

class ResguardoModel extends Model
{
  public static function vigentes(): array
  {
    $sql = "SELECT r.*, b.activo AS bien_activo, b.numero_inventario, b.clave_interna, b.nombre_bien, b.numero_serie, ua.codigo_ua, ua.nombre unidad_nombre,
      p.csp AS resguardante_csp, p.id_unidad AS resguardante_id_unidad,
      uar.codigo_ua AS resguardante_codigo_ua, uar.nombre AS resguardante_unidad_nombre,
      CONCAT_WS(' ', p.nombre, p.apellido_paterno, NULLIF(p.apellido_materno, '')) resguardante_nombre
      FROM resguardo r INNER JOIN bien b ON b.id_bien=r.id_bien INNER JOIN resguardante p ON p.csp=r.csp
      INNER JOIN unidad_administrativa ua ON ua.id_unidad=b.id_unidad
      LEFT JOIN unidad_administrativa uar ON uar.id_unidad=p.id_unidad
      WHERE r.activo=1 ORDER BY resguardante_nombre, b.nombre_bien";
    return parent::query($sql) ?: [];
  }

  /** Resumen por titular: una consulta para activos, asignaciones y fechas. */
  public static function resumenResguardantes(): array
  {
    $sql = "SELECT p.id_resguardante, p.csp, p.nombre, p.apellido_paterno, p.apellido_materno,
        p.id_unidad, p.activo, ua.codigo_ua AS unidad_codigo_ua, ua.nombre AS unidad_nombre,
        CONCAT_WS(' ', p.nombre, p.apellido_paterno, NULLIF(p.apellido_materno, '')) AS nombre_completo,
        COUNT(rg.id_resguardo) AS bienes_asignados,
        COALESCE(SUM(CASE WHEN b.activo = 1 THEN 1 ELSE 0 END), 0) AS bienes_activos,
        MAX(rg.fecha_asignacion) AS ultima_asignacion
      FROM resguardante p
      LEFT JOIN unidad_administrativa ua ON ua.id_unidad = p.id_unidad
      LEFT JOIN resguardo rg ON rg.id_resguardante = p.id_resguardante AND rg.activo = 1
      LEFT JOIN bien b ON b.id_bien = rg.id_bien
      GROUP BY p.id_resguardante, p.csp, p.nombre, p.apellido_paterno, p.apellido_materno,
        p.id_unidad, p.activo, ua.codigo_ua, ua.nombre
      ORDER BY p.apellido_paterno, p.nombre";
    return parent::query($sql) ?: [];
  }

  public static function contextoBien(int $bienId): array
  {
    $rows = parent::query("SELECT b.id_bien, b.clave_interna, b.numero_inventario, b.nombre_bien, b.activo AS bien_activo,
      b.id_unidad, ua.codigo_ua, ua.nombre AS unidad_nombre, b.id_ubicacion, ub.municipio, ub.localidad,
      b.piso, b.seccion_ala, b.cubiculo, rg.id_resguardo, rg.id_resguardante,
      p.csp, CONCAT_WS(' ', p.nombre, p.apellido_paterno, NULLIF(p.apellido_materno, '')) AS resguardante_nombre,
      uar.codigo_ua AS codigo_ua_resguardante, uar.nombre AS unidad_resguardante_nombre
      FROM bien b LEFT JOIN unidad_administrativa ua ON ua.id_unidad = b.id_unidad
      LEFT JOIN ubicacion ub ON ub.id_ubicacion = b.id_ubicacion
      LEFT JOIN resguardo rg ON rg.id_bien = b.id_bien AND rg.activo = 1
      LEFT JOIN resguardante p ON p.id_resguardante = rg.id_resguardante
      LEFT JOIN unidad_administrativa uar ON uar.id_unidad = p.id_unidad
      WHERE b.id_bien = :id LIMIT 1", ['id' => $bienId]);
    if (!$rows) return [];
    $contexto = $rows[0];
    $ubicacion = array_filter([$contexto['municipio'] ?? null, $contexto['localidad'] ?? null]);
    $espacio = [];
    foreach (['piso' => 'Piso', 'seccion_ala' => 'Sección/Ala', 'cubiculo' => 'Cubículo'] as $campo => $etiqueta) {
      if (trim((string) ($contexto[$campo] ?? '')) !== '') $espacio[] = $etiqueta . ': ' . trim((string) $contexto[$campo]);
    }
    $contexto['ubicacion_detalle'] = trim(implode(' · ', array_filter([implode(', ', $ubicacion), implode(' · ', $espacio)])));
    return $contexto;
  }

  /** Guarda instantáneas legibles para que el movimiento no dependa de los datos actuales de los catálogos. */
  public static function registrarMovimiento(int $bienId, string $tipo, array $anterior = [], array $nuevo = [], ?string $motivo = null, ?string $observaciones = null, ?array $usuario = null, ?int $resguardoAnterior = null, ?int $resguardoNuevo = null): int
  {
    return (int) parent::add('movimiento_bien', self::datosMovimiento($bienId, $tipo, $anterior, $nuevo, $motivo, $observaciones, $usuario, $resguardoAnterior, $resguardoNuevo));
  }

  /** Variante para procesos que ya mantienen una transacción PDO abierta. */
  public static function registrarMovimientoEn(PDO $pdo, int $bienId, string $tipo, array $anterior = [], array $nuevo = [], ?string $motivo = null, ?string $observaciones = null, ?array $usuario = null, ?int $resguardoAnterior = null, ?int $resguardoNuevo = null): int
  {
    $datos = self::datosMovimiento($bienId, $tipo, $anterior, $nuevo, $motivo, $observaciones, $usuario, $resguardoAnterior, $resguardoNuevo);
    $columnas = array_keys($datos);
    $parametros = array_map(static fn($columna) => ':' . $columna, $columnas);
    $sql = 'INSERT INTO movimiento_bien (' . implode(', ', $columnas) . ') VALUES (' . implode(', ', $parametros) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($datos);
    return (int) $pdo->lastInsertId();
  }

  private static function datosMovimiento(int $bienId, string $tipo, array $anterior, array $nuevo, ?string $motivo, ?string $observaciones, ?array $usuario, ?int $resguardoAnterior, ?int $resguardoNuevo): array
  {
    $persona = static fn(array $c, string $campo) => trim((string) ($c[$campo] ?? '')) ?: null;
    $idAnterior = (int) ($anterior['id_resguardante'] ?? 0);
    $idNuevo = (int) ($nuevo['id_resguardante'] ?? 0);
    $userId = isset($usuario['id']) && is_numeric($usuario['id']) ? (int) $usuario['id'] : null;
    $nombreUsuario = trim((string) ($usuario['nombre'] ?? $usuario['username'] ?? '')) ?: null;
    return [
      'id_bien' => $bienId, 'tipo_movimiento' => $tipo,
      'ubicacion_anterior' => $anterior['id_ubicacion'] ?? null, 'ubicacion_nueva' => $nuevo['id_ubicacion'] ?? null,
      'resguardante_anterior' => $idAnterior ?: null, 'resguardante_nuevo' => $idNuevo ?: null,
      'fecha_movimiento' => now(), 'motivo' => $motivo ?: null, 'observaciones' => $observaciones ?: null,
      'id_resguardo_anterior' => $resguardoAnterior ?: ($anterior['id_resguardo'] ?? null),
      'id_resguardo_nuevo' => $resguardoNuevo ?: ($nuevo['id_resguardo'] ?? null),
      'id_usuario' => $userId, 'usuario_nombre' => $nombreUsuario,
      'codigo_ua_anterior' => $persona($anterior, 'codigo_ua'), 'unidad_anterior' => $persona($anterior, 'unidad_nombre'),
      'codigo_ua_nueva' => $persona($nuevo, 'codigo_ua'), 'unidad_nueva' => $persona($nuevo, 'unidad_nombre'),
      'ubicacion_anterior_detalle' => $persona($anterior, 'ubicacion_detalle'), 'ubicacion_nueva_detalle' => $persona($nuevo, 'ubicacion_detalle'),
      'csp_anterior' => $persona($anterior, 'csp'), 'nombre_resguardante_anterior' => $persona($anterior, 'resguardante_nombre'),
      'csp_nuevo' => $persona($nuevo, 'csp'), 'nombre_resguardante_nuevo' => $persona($nuevo, 'resguardante_nombre'),
      'codigo_ua_resguardante_anterior' => $persona($anterior, 'codigo_ua_resguardante'), 'unidad_resguardante_anterior' => $persona($anterior, 'unidad_resguardante_nombre'),
      'codigo_ua_resguardante_nueva' => $persona($nuevo, 'codigo_ua_resguardante'), 'unidad_resguardante_nueva' => $persona($nuevo, 'unidad_resguardante_nombre'),
      'estado_anterior' => isset($anterior['bien_activo']) ? ((int) $anterior['bien_activo'] ? 'Activo' : 'Inactivo') : null,
      'estado_nuevo' => isset($nuevo['bien_activo']) ? ((int) $nuevo['bien_activo'] ? 'Activo' : 'Inactivo') : null,
    ];
  }

  private static function consultaMovimientos(): string
  {
    return "SELECT m.*, b.clave_interna, b.numero_inventario, b.nombre_bien,
      COALESCE(m.csp_anterior, ra.csp) AS csp_anterior_vista,
      COALESCE(m.nombre_resguardante_anterior, CONCAT_WS(' ', ra.nombre, ra.apellido_paterno, NULLIF(ra.apellido_materno, ''))) AS nombre_resguardante_anterior_vista,
      COALESCE(m.csp_nuevo, rn.csp) AS csp_nuevo_vista,
      COALESCE(m.nombre_resguardante_nuevo, CONCAT_WS(' ', rn.nombre, rn.apellido_paterno, NULLIF(rn.apellido_materno, ''))) AS nombre_resguardante_nuevo_vista,
      COALESCE(m.ubicacion_anterior_detalle, CONCAT_WS(' · ', uba.municipio, uba.localidad)) AS ubicacion_anterior_vista,
      COALESCE(m.ubicacion_nueva_detalle, CONCAT_WS(' · ', ubn.municipio, ubn.localidad)) AS ubicacion_nueva_vista,
      COALESCE(NULLIF(TRIM(m.usuario_nombre), ''), NULLIF(TRIM(u.nombre), ''), NULLIF(TRIM(u.username), '')) AS usuario_vista
      FROM movimiento_bien m INNER JOIN bien b ON b.id_bien = m.id_bien
      LEFT JOIN resguardante ra ON ra.id_resguardante = m.resguardante_anterior
      LEFT JOIN resguardante rn ON rn.id_resguardante = m.resguardante_nuevo
      LEFT JOIN ubicacion uba ON uba.id_ubicacion = m.ubicacion_anterior
      LEFT JOIN ubicacion ubn ON ubn.id_ubicacion = m.ubicacion_nueva
      LEFT JOIN bee_users u ON u.id = m.id_usuario";
  }

  private static function fechaValida(string $fecha): bool
  {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $partes)) return false;
    return checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]);
  }

  public static function historico(array $filtros = []): array
  {
    $sql = self::consultaMovimientos() . ' WHERE 1=1'; $params = [];
    if (!empty($filtros['q'])) {
      $sql .= ' AND (b.clave_interna LIKE :q1 OR b.numero_inventario LIKE :q2 OR b.nombre_bien LIKE :q3 OR m.tipo_movimiento LIKE :q4 OR m.motivo LIKE :q5 OR m.observaciones LIKE :q6 OR m.csp_anterior LIKE :q7 OR m.csp_nuevo LIKE :q8 OR m.nombre_resguardante_anterior LIKE :q9 OR m.nombre_resguardante_nuevo LIKE :q10 OR m.unidad_anterior LIKE :q11 OR m.unidad_nueva LIKE :q12 OR ra.csp LIKE :q13 OR ra.nombre LIKE :q14 OR ra.apellido_paterno LIKE :q15 OR rn.csp LIKE :q16 OR rn.nombre LIKE :q17 OR rn.apellido_paterno LIKE :q18 OR m.usuario_nombre LIKE :q19 OR u.username LIKE :q20 OR m.codigo_ua_anterior LIKE :q21 OR m.codigo_ua_nueva LIKE :q22 OR m.unidad_resguardante_anterior LIKE :q23 OR m.unidad_resguardante_nueva LIKE :q24 OR m.ubicacion_anterior_detalle LIKE :q25 OR m.ubicacion_nueva_detalle LIKE :q26 OR uba.municipio LIKE :q27 OR uba.localidad LIKE :q28 OR ubn.municipio LIKE :q29 OR ubn.localidad LIKE :q30)';
      $like = '%' . trim((string) $filtros['q']) . '%';
      for ($i = 1; $i <= 30; $i++) $params['q' . $i] = $like;
    }
    if (!empty($filtros['tipo'])) { $sql .= ' AND m.tipo_movimiento = :tipo'; $params['tipo'] = (string) $filtros['tipo']; }
    if (!empty($filtros['desde']) && self::fechaValida((string) $filtros['desde'])) { $sql .= ' AND m.fecha_movimiento >= :desde'; $params['desde'] = $filtros['desde'] . ' 00:00:00'; }
    if (!empty($filtros['hasta']) && self::fechaValida((string) $filtros['hasta'])) {
      $fechaSiguiente = (new DateTimeImmutable($filtros['hasta']))->modify('+1 day')->format('Y-m-d');
      $sql .= ' AND m.fecha_movimiento < :hasta_exclusivo'; $params['hasta_exclusivo'] = $fechaSiguiente . ' 00:00:00';
    }
    return parent::query($sql . ' ORDER BY m.fecha_movimiento DESC, m.id_movimiento DESC', $params) ?: [];
  }

  public static function movimientoPorId(int $id): array
  {
    $rows = parent::query(self::consultaMovimientos() . ' WHERE m.id_movimiento = :id LIMIT 1', ['id' => $id]);
    return $rows ? $rows[0] : [];
  }

  public static function tiposMovimiento(): array
  {
    return parent::query('SELECT DISTINCT tipo_movimiento FROM movimiento_bien ORDER BY tipo_movimiento') ?: [];
  }

  public static function detalleResguardante(int $id): array
  {
    $persona = parent::query("SELECT r.*, ua.codigo_ua AS unidad_codigo_ua, ua.nombre AS unidad_nombre,
      CONCAT_WS(' ', r.nombre, r.apellido_paterno, NULLIF(r.apellido_materno, '')) AS nombre_completo
      FROM resguardante r LEFT JOIN unidad_administrativa ua ON ua.id_unidad = r.id_unidad WHERE r.id_resguardante = :id LIMIT 1", ['id' => $id]);
    if (!$persona) return [];
    $detalle = $persona[0];
    $detalle['asignaciones'] = parent::query("SELECT rg.*, b.clave_interna, b.numero_inventario, b.nombre_bien, b.numero_serie,
      b.activo AS bien_activo, ua.codigo_ua, ua.nombre AS unidad_nombre, ub.municipio, ub.localidad, b.piso, b.seccion_ala, b.cubiculo
      FROM resguardo rg INNER JOIN bien b ON b.id_bien = rg.id_bien
      LEFT JOIN unidad_administrativa ua ON ua.id_unidad = b.id_unidad
      LEFT JOIN ubicacion ub ON ub.id_ubicacion = b.id_ubicacion
      WHERE rg.id_resguardante = :id ORDER BY rg.fecha_asignacion DESC, rg.id_resguardo DESC", ['id' => $id]) ?: [];
    $detalle['movimientos'] = parent::query(self::consultaMovimientos() . ' WHERE m.resguardante_anterior = :anterior OR m.resguardante_nuevo = :nuevo ORDER BY m.fecha_movimiento DESC', ['anterior' => $id, 'nuevo' => $id]) ?: [];
    return $detalle;
  }

  /** Registra la devolución/baja sólo cuando el usuario la confirma explícitamente. */
  public static function darDeBaja(int $bienId, string $fecha, ?string $motivo, ?string $observaciones, ?array $usuario = null): void
  {
    $actual = parent::query('SELECT * FROM resguardo WHERE id_bien = :bien AND activo = 1 LIMIT 1', ['bien' => $bienId]);
    if (!$actual) throw new Exception('El bien no tiene una custodia vigente que pueda darse de baja.');
    $resguardo = $actual[0];
    $anterior = self::contextoBien($bienId);
    parent::add('baja_resguardo', [
      'id_resguardo' => $resguardo['id_resguardo'], 'id_bien' => $bienId, 'fecha_baja' => $fecha,
      'motivo' => $motivo ?: null, 'observaciones' => $observaciones ?: null
    ]);
    parent::update('resguardo', ['id_resguardo' => $resguardo['id_resguardo']], ['activo' => 0, 'fecha_devolucion' => $fecha]);
    $nuevo = self::contextoBien($bienId);
    self::registrarMovimiento($bienId, 'Devolución de resguardo', $anterior, $nuevo, $motivo, $observaciones, $usuario, (int) $resguardo['id_resguardo']);
  }
}
