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

  /** Resumen de la custodia vigente, filtrado desde la base de datos. */
  public static function resumenResguardantes(array $filtros = []): array
  {
    $sql = "SELECT p.id_resguardante, p.csp, p.nombre, p.apellido_paterno, p.apellido_materno,
        p.id_unidad, p.activo, ua.codigo_ua AS unidad_codigo_ua, ua.nombre AS unidad_nombre,
        CONCAT_WS(' ', p.nombre, p.apellido_paterno, NULLIF(p.apellido_materno, '')) AS nombre_completo,
        COUNT(b.id_bien) AS bienes_asignados,
        MAX(rg.fecha_asignacion) AS ultima_asignacion
      FROM resguardante p
      LEFT JOIN unidad_administrativa ua ON ua.id_unidad = p.id_unidad
      LEFT JOIN resguardo rg ON rg.id_resguardante = p.id_resguardante AND rg.activo = 1
      LEFT JOIN bien b ON b.id_bien = rg.id_bien
      WHERE 1 = 1";
    $params = [];

    $estado = (string) ($filtros['estado'] ?? '1');
    if (in_array($estado, ['0', '1'], true)) {
      $sql .= ' AND p.activo = :estado';
      $params['estado'] = (int) $estado;
    }

    $busqueda = trim((string) ($filtros['q'] ?? ''));
    if ($busqueda !== '') {
      $terminos = preg_split('/\s+/u', $busqueda, -1, PREG_SPLIT_NO_EMPTY);
      if (!empty($terminos)) {
        $condicionesTerminos = [];
        foreach ($terminos as $idx => $termino) {
          $pCsp   = 't' . $idx . '_csp';
          $pNom   = 't' . $idx . '_nom';
          $pPat   = 't' . $idx . '_pat';
          $pMat   = 't' . $idx . '_mat';
          $pFull  = 't' . $idx . '_full';
          $pUaCod = 't' . $idx . '_uacod';
          $pUaNom = 't' . $idx . '_uanom';

          $condicionesTerminos[] = "(p.csp LIKE :$pCsp
            OR p.nombre LIKE :$pNom
            OR p.apellido_paterno LIKE :$pPat
            OR p.apellido_materno LIKE :$pMat
            OR CONCAT_WS(' ', p.nombre, p.apellido_paterno, NULLIF(p.apellido_materno, '')) LIKE :$pFull
            OR ua.codigo_ua LIKE :$pUaCod
            OR ua.nombre LIKE :$pUaNom)";

          $like = '%' . $termino . '%';
          $params[$pCsp]   = $like;
          $params[$pNom]   = $like;
          $params[$pPat]   = $like;
          $params[$pMat]   = $like;
          $params[$pFull]  = $like;
          $params[$pUaCod] = $like;
          $params[$pUaNom] = $like;
        }
        $sql .= ' AND (' . implode(' AND ', $condicionesTerminos) . ')';
      }
    }

    $sql .= " GROUP BY p.id_resguardante, p.csp, p.nombre, p.apellido_paterno, p.apellido_materno,
        p.id_unidad, p.activo, ua.codigo_ua, ua.nombre
      ORDER BY p.apellido_paterno, p.nombre";
    return parent::query($sql, $params) ?: [];
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
      , t.id_traspaso, t.folio AS folio_traspaso
      FROM movimiento_bien m INNER JOIN bien b ON b.id_bien = m.id_bien
      LEFT JOIN resguardante ra ON ra.id_resguardante = m.resguardante_anterior
      LEFT JOIN resguardante rn ON rn.id_resguardante = m.resguardante_nuevo
      LEFT JOIN ubicacion uba ON uba.id_ubicacion = m.ubicacion_anterior
      LEFT JOIN ubicacion ubn ON ubn.id_ubicacion = m.ubicacion_nueva
      LEFT JOIN bee_users u ON u.id = m.id_usuario
      LEFT JOIN traspaso_bien tb ON tb.id_movimiento = m.id_movimiento
      LEFT JOIN traspaso t ON t.id_traspaso = tb.id_traspaso";
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
    $detalle['unidad_jerarquia'] = !empty($detalle['id_unidad'])
      ? CatalogoModel::jerarquiaUnidad((int) $detalle['id_unidad'])
      : ['ruta' => []];
    $detalle['asignaciones'] = parent::query("SELECT rg.fecha_asignacion, b.id_bien, b.clave_interna, b.numero_inventario, b.nombre_bien, b.numero_serie,
      b.activo AS bien_activo, m.nombre AS marca_nombre, mo.nombre AS modelo_nombre, eu.nombre AS estado_uso_nombre,
      ua.codigo_ua, ua.nombre AS unidad_nombre, ub.municipio, ub.localidad, ub.ubicacion_fisica, b.piso, b.seccion_ala, b.cubiculo
      FROM resguardo rg INNER JOIN bien b ON b.id_bien = rg.id_bien
      LEFT JOIN unidad_administrativa ua ON ua.id_unidad = b.id_unidad
      LEFT JOIN marca m ON m.id_marca = b.id_marca
      LEFT JOIN modelo mo ON mo.id_modelo = b.id_modelo AND mo.id_marca = b.id_marca
      LEFT JOIN estado_uso eu ON eu.id_estado_uso = b.id_estado_uso
      LEFT JOIN ubicacion ub ON ub.id_ubicacion = b.id_ubicacion
      WHERE rg.id_resguardante = :id AND rg.activo = 1 ORDER BY rg.fecha_asignacion DESC, b.nombre_bien, b.id_bien", ['id' => $id]) ?: [];
    return $detalle;
  }

  /**
   * Única operación para cambiar la custodia de un bien. Puede abrir su propia
   * transacción o participar en una transacción global (por ejemplo, una
   * reasignación masiva). El bloqueo del bien serializa los cambios concurrentes.
   */
  public static function cambiarCustodia(
    int $bienId,
    ?int $resguardanteNuevoId,
    array $datosNuevo = [],
    ?string $tipoMovimiento = null,
    ?string $motivo = null,
    ?string $observaciones = null,
    ?array $usuario = null,
    ?PDO $pdo = null,
    ?array $contextoAnteriorOverride = null
  ): array {
    $conexionPropia = $pdo === null;
    $pdo ??= parent::connect(true);
    $savepoint = null;
    if ($conexionPropia) {
      // Db::query deja abierta la transacción iniciada por lecturas. Al no
      // haber escrituras pendientes antes de esta operación, se cierra para
      // iniciar aquí una transacción cuyo ciclo de vida sí controlamos.
      if ($pdo->inTransaction()) $pdo->commit();
      $pdo->beginTransaction();
    } elseif (!$pdo->inTransaction()) {
      throw new Exception('La operación de custodia requiere una transacción contenedora activa.');
    } else {
      $savepoint = 'custodia_operation';
      $pdo->exec('SAVEPOINT ' . $savepoint);
    }

    try {
      $bienStmt = $pdo->prepare('SELECT id_bien FROM bien WHERE id_bien = ? FOR UPDATE');
      $bienStmt->execute([$bienId]);
      if (!$bienStmt->fetch(PDO::FETCH_ASSOC)) throw new Exception('El bien solicitado no existe.');

      $actualStmt = $pdo->prepare('SELECT * FROM resguardo WHERE id_bien = ? AND activo = 1 FOR UPDATE');
      $actualStmt->execute([$bienId]);
      $resguardosActivos = $actualStmt->fetchAll(PDO::FETCH_ASSOC);
      if (count($resguardosActivos) > 1) {
        throw new Exception('El bien tiene más de un resguardo activo. La operación se canceló; revisa la inconsistencia antes de continuar.');
      }
      $anterior = $resguardosActivos[0] ?? null;
      $contextoAnterior = $contextoAnteriorOverride ?? self::contextoBien($bienId);

      $destino = null;
      if ($resguardanteNuevoId !== null) {
        $destinoStmt = $pdo->prepare('SELECT id_resguardante, csp, activo FROM resguardante WHERE id_resguardante = ? FOR UPDATE');
        $destinoStmt->execute([$resguardanteNuevoId]);
        $destino = $destinoStmt->fetch(PDO::FETCH_ASSOC);
        if (!$destino) throw new Exception('El resguardante seleccionado no existe.');
        if ((int) $destino['activo'] !== 1) throw new Exception('El resguardante seleccionado está inactivo.');
      }

      if ($anterior && $destino && (int) $anterior['id_resguardante'] === (int) $destino['id_resguardante']) {
        $fecha = trim((string) ($datosNuevo['fecha_asignacion'] ?? ''));
        if ($fecha !== '' && $fecha !== (string) $anterior['fecha_asignacion']) {
          $fechaStmt = $pdo->prepare('UPDATE resguardo SET fecha_asignacion = ? WHERE id_resguardo = ? AND activo = 1');
          $fechaStmt->execute([$fecha, $anterior['id_resguardo']]);
        }
        if ($conexionPropia) $pdo->commit();
        else $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
        return ['changed' => false, 'resguardo_anterior' => (int) $anterior['id_resguardo'], 'resguardo_nuevo' => (int) $anterior['id_resguardo'], 'id_movimiento' => null];
      }

      if (!$anterior && !$destino) throw new Exception('El bien no tiene una custodia vigente que pueda liberarse.');

      if ($anterior) {
        $cierre = $pdo->prepare('UPDATE resguardo SET activo = 0, fecha_devolucion = CURDATE() WHERE id_resguardo = ? AND activo = 1');
        $cierre->execute([$anterior['id_resguardo']]);
        if ($cierre->rowCount() !== 1) throw new Exception('No fue posible cerrar el resguardo vigente. La operación se canceló.');
      }

      $idResguardoNuevo = null;
      if ($destino) {
        // Los datos propios del equipo pertenecen a la asignación y se conservan
        // al cambiar de responsable, salvo que el llamador indique otros valores.
        $datos = [];
        foreach (['tipo_equipo', 'estado_equipo', 'observaciones', 'candado'] as $campo) {
          $datos[$campo] = $datosNuevo[$campo] ?? ($anterior[$campo] ?? null);
        }
        $datos['fecha_asignacion'] = trim((string) ($datosNuevo['fecha_asignacion'] ?? '')) ?: date('Y-m-d');
        $insert = $pdo->prepare('INSERT INTO resguardo (id_bien, id_resguardante, csp, tipo_equipo, estado_equipo, observaciones, candado, fecha_asignacion, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)');
        $insert->execute([$bienId, $destino['id_resguardante'], $destino['csp'], $datos['tipo_equipo'], $datos['estado_equipo'], $datos['observaciones'], $datos['candado'], $datos['fecha_asignacion']]);
        $idResguardoNuevo = (int) $pdo->lastInsertId();
      }

      $contextoNuevo = self::contextoBien($bienId);
      $tipo = $tipoMovimiento ?: ($destino ? ($anterior ? 'Cambio de resguardante' : 'Asignación de resguardo') : 'Liberación de resguardo');
      $idMovimiento = self::registrarMovimientoEn($pdo, $bienId, $tipo, $contextoAnterior, $contextoNuevo, $motivo, $observaciones, $usuario,
        $anterior ? (int) $anterior['id_resguardo'] : null, $idResguardoNuevo);

      if ($conexionPropia) $pdo->commit();
      else $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
      return ['changed' => true, 'resguardo_anterior' => $anterior ? (int) $anterior['id_resguardo'] : null, 'resguardo_nuevo' => $idResguardoNuevo, 'id_movimiento' => $idMovimiento];
    } catch (Throwable $e) {
      if ($conexionPropia && $pdo->inTransaction()) $pdo->rollBack();
      elseif ($savepoint !== null && $pdo->inTransaction()) {
        $pdo->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
        $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
      }
      throw $e;
    }
  }

  /** Registra la devolución/baja sólo cuando el usuario la confirma explícitamente. */
  public static function darDeBaja(int $bienId, string $fecha, ?string $motivo, ?string $observaciones, ?array $usuario = null, ?PDO $pdo = null): void
  {
    $conexionPropia = $pdo === null;
    $pdo ??= parent::connect(true);
    $savepoint = null;
    if ($conexionPropia) {
      // Las consultas previas del framework pueden haber dejado una transacción
      // de lectura abierta; la devolución inicia y controla su propia transacción.
      if ($pdo->inTransaction()) $pdo->commit();
      $pdo->beginTransaction();
    } elseif (!$pdo->inTransaction()) {
      throw new Exception('La devolución requiere una transacción contenedora activa.');
    } else {
      $savepoint = 'custodia_return';
      $pdo->exec('SAVEPOINT ' . $savepoint);
    }
    try {
      $stmt = $pdo->prepare('SELECT id_resguardo FROM resguardo WHERE id_bien = ? AND activo = 1 FOR UPDATE');
      $stmt->execute([$bienId]);
      $activos = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if (!$activos) throw new Exception('El bien no tiene una custodia vigente que pueda darse de baja.');
      if (count($activos) > 1) throw new Exception('El bien tiene más de un resguardo activo. La operación se canceló; revisa la inconsistencia antes de continuar.');
      $idResguardo = (int) $activos[0]['id_resguardo'];
      $baja = $pdo->prepare('INSERT INTO baja_resguardo (id_resguardo, id_bien, fecha_baja, motivo, observaciones) VALUES (?, ?, ?, ?, ?)');
      $baja->execute([$idResguardo, $bienId, $fecha, $motivo ?: null, $observaciones ?: null]);
      self::cambiarCustodia($bienId, null, [], 'Devolución de resguardo', $motivo, $observaciones, $usuario, $pdo);
      // Conserva la fecha seleccionada en el formulario como fecha de devolución.
      $actualizarFecha = $pdo->prepare('UPDATE resguardo SET fecha_devolucion = ? WHERE id_resguardo = ? AND activo = 0');
      $actualizarFecha->execute([$fecha, $idResguardo]);
      if ($conexionPropia) $pdo->commit();
      else $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
    } catch (Throwable $e) {
      if ($conexionPropia && $pdo->inTransaction()) $pdo->rollBack();
      elseif ($savepoint !== null && $pdo->inTransaction()) {
        $pdo->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
        $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
      }
      throw $e;
    }
  }
}
