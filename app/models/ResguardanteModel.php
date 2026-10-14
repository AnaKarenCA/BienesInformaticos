<?php

class ResguardanteModel extends Model
{
  private const SELECT = "SELECT r.*, ua.codigo_ua AS unidad_codigo_ua, ua.nombre AS unidad_nombre,
    CONCAT_WS(' ', r.nombre, r.apellido_paterno, NULLIF(r.apellido_materno, '')) AS nombre_completo
    FROM resguardante r LEFT JOIN unidad_administrativa ua ON ua.id_unidad = r.id_unidad";

  public static function activos(?int $incluirId = null): array
  {
    $sql = self::SELECT . ' WHERE r.activo = 1'; $params = [];
    if ($incluirId) { $sql .= ' OR r.id_resguardante = :incluir'; $params['incluir'] = $incluirId; }
    return parent::query($sql . ' ORDER BY r.apellido_paterno, r.nombre', $params) ?: [];
  }

  public static function listar(string $busqueda = ''): array
  {
    $sql = "SELECT r.*, ua.codigo_ua AS unidad_codigo_ua, ua.nombre AS unidad_nombre,
      CONCAT_WS(' ', r.nombre, r.apellido_paterno, NULLIF(r.apellido_materno, '')) AS nombre_completo,
      (SELECT COUNT(*) FROM resguardo rg WHERE rg.id_resguardante = r.id_resguardante AND rg.activo = 1) AS bienes_vigentes
      FROM resguardante r LEFT JOIN unidad_administrativa ua ON ua.id_unidad = r.id_unidad WHERE 1=1";
    $params = [];
    if ($busqueda !== '') {
      $like = '%' . trim($busqueda) . '%';
      $sql .= ' AND (r.csp LIKE :csp OR r.nombre LIKE :nombre OR r.apellido_paterno LIKE :paterno OR r.apellido_materno LIKE :materno OR ua.nombre LIKE :unidad OR ua.codigo_ua LIKE :codigo_ua)';
      $params = ['csp' => $like, 'nombre' => $like, 'paterno' => $like, 'materno' => $like, 'unidad' => $like, 'codigo_ua' => $like];
    }
    return parent::query($sql . ' ORDER BY r.apellido_paterno, r.nombre', $params) ?: [];
  }

  /** Catálogo activo limitado a una UA para selectores contextuales, como traspasos. */
  public static function buscarActivosPorUnidad(int $unidadId, string $busqueda = '', int $limite = 8): array
  {
    if ($unidadId < 1) return [];
    $sql = "SELECT r.id_resguardante, r.id_unidad, r.csp, r.nombre, r.apellido_paterno, r.apellido_materno,
      CONCAT_WS(' ', r.nombre, r.apellido_paterno, NULLIF(r.apellido_materno, '')) AS nombre_completo,
      ua.nombre AS unidad_nombre, ua.codigo_ua AS unidad_codigo
      FROM resguardante r INNER JOIN unidad_administrativa ua ON ua.id_unidad = r.id_unidad
      WHERE r.activo = 1 AND r.id_unidad = :unidad";
    $params = ['unidad' => $unidadId];
    $busqueda = trim($busqueda);
    if ($busqueda !== '') {
      $sql .= " AND (r.csp LIKE :q_csp OR r.nombre LIKE :q_nombre OR r.apellido_paterno LIKE :q_paterno OR r.apellido_materno LIKE :q_materno
        OR CONCAT_WS(' ', r.nombre, r.apellido_paterno, NULLIF(r.apellido_materno, '')) LIKE :q_completo)";
      $like = '%' . $busqueda . '%';
      $params += ['q_csp' => $like, 'q_nombre' => $like, 'q_paterno' => $like, 'q_materno' => $like, 'q_completo' => $like];
    }
    $limite = max(1, min($limite, 20));
    return parent::query($sql . " ORDER BY r.apellido_paterno, r.nombre LIMIT {$limite}", $params) ?: [];
  }

  public static function porId(int $id): array
  {
    $rows = parent::query(self::SELECT . ' WHERE r.id_resguardante = :id LIMIT 1', ['id' => $id]);
    return $rows ? $rows[0] : [];
  }

  public static function guardar(array $entrada, ?int $id = null, ?array $usuario = null): int
  {
    $csp = trim((string) ($entrada['csp'] ?? ''));
    $nombre = trim((string) ($entrada['nombre'] ?? ''));
    $paterno = trim((string) ($entrada['apellido_paterno'] ?? ''));
    $materno = trim((string) ($entrada['apellido_materno'] ?? ''));
    $unidadId = (int) ($entrada['id_unidad'] ?? 0);

    if (!preg_match('/^[0-9]{1,9}$/D', $csp)) throw new Exception('Captura un CSP válido de hasta 9 dígitos.');
    if ($nombre === '' || $paterno === '') throw new Exception('Nombre y apellido paterno son obligatorios.');
    if (!$unidadId || !CatalogoModel::unidadExiste($unidadId)) throw new Exception('Selecciona una unidad administrativa válida.');

    $params = ['csp' => $csp]; $sql = 'SELECT id_resguardante FROM resguardante WHERE LOWER(csp) = LOWER(:csp)';
    if ($id) { $sql .= ' AND id_resguardante <> :id'; $params['id'] = $id; }
    if (parent::query($sql . ' LIMIT 1', $params)) throw new Exception('El CSP ya se encuentra registrado.');

    $datos = [
      'csp' => $csp, 'nombre' => $nombre, 'apellido_paterno' => $paterno,
      'apellido_materno' => $materno !== '' ? $materno : null, 'id_unidad' => $unidadId
    ];
    if ($id) {
      $actual = self::porId($id);
      if (!$actual) throw new Exception('El resguardante solicitado no existe.');
      if ((int) ($actual['id_unidad'] ?? 0) !== $unidadId) {
        if (self::cantidadVigentes($id) > 0) throw new Exception('No se puede cambiar la unidad de un resguardante con bienes vigentes. Registra primero los traspasos de sus bienes; después podrás actualizar su unidad.');
      }
      try { parent::update('resguardante', ['id_resguardante' => $id], $datos); }
      catch (PDOException $e) {
        if (str_contains($e->getMessage(), '23000') || str_contains($e->getMessage(), '1062')) throw new Exception('El CSP ya se encuentra registrado.');
        throw $e;
      }
      return $id;
    }
    $datos['activo'] = 1;
    try { return (int) parent::add('resguardante', $datos); }
    catch (PDOException $e) {
      if (str_contains($e->getMessage(), '23000') || str_contains($e->getMessage(), '1062')) throw new Exception('El CSP ya se encuentra registrado.');
      throw $e;
    }
  }

  public static function cambiarEstado(int $id): bool
  {
    $resguardante = self::porId($id);
    if (!$resguardante) throw new Exception('El resguardante solicitado no existe.');
    if ((int) $resguardante['activo'] === 1 && self::cantidadVigentes($id) > 0) {
      throw new Exception('El resguardante tiene bienes resguardados. Debes cambiar sus asignaciones antes de desactivarlo.');
    }
    return parent::update('resguardante', ['id_resguardante' => $id], ['activo' => $resguardante['activo'] ? 0 : 1]);
  }

  public static function cantidadVigentes(int $id): int
  {
    $rows = parent::query('SELECT COUNT(*) AS total FROM resguardo WHERE id_resguardante = :id AND activo = 1', ['id' => $id]);
    return (int) ($rows[0]['total'] ?? 0);
  }

  public static function bienesVigentes(int $id): array
  {
    $sql = "SELECT rg.id_resguardo, rg.fecha_asignacion, b.id_bien, b.clave_interna, b.numero_inventario, b.nombre_bien, b.numero_serie,
      ag.nombre AS generico_nombre, ga.nombre AS grupo_nombre, ae.nombre AS especifico_nombre,
      m.nombre AS marca_nombre, mo.nombre AS modelo_nombre, ua.codigo_ua, ua.nombre AS unidad_nombre,
      ub.municipio, ub.localidad, ub.ubicacion_fisica
      FROM resguardo rg INNER JOIN bien b ON b.id_bien = rg.id_bien
      LEFT JOIN activo_especifico ae ON ae.id_activo_especifico = b.id_activo_especifico
      LEFT JOIN grupo_activo ga ON ga.id_grupo_activo = ae.id_grupo_activo
      LEFT JOIN activo_generico ag ON ag.id_activo_generico = ga.id_activo_generico
      LEFT JOIN marca m ON m.id_marca = b.id_marca
      LEFT JOIN modelo mo ON mo.id_modelo = b.id_modelo
      INNER JOIN unidad_administrativa ua ON ua.id_unidad = b.id_unidad
      LEFT JOIN ubicacion ub ON ub.id_ubicacion = b.id_ubicacion
      WHERE rg.id_resguardante = :id AND rg.activo = 1 ORDER BY b.nombre_bien, b.id_bien";
    return parent::query($sql, ['id' => $id]) ?: [];
  }

  /** Traslada toda la custodia vigente y desactiva al titular anterior en una sola transacción. */
  public static function transferirVigentesYDesactivar(int $anteriorId, array $reasignaciones, ?array $usuario = null, ?PDO $pdo = null): int
  {
    if (!$reasignaciones) throw new Exception('No hay bienes seleccionados para reasignar.');
    $reasignacionesNormalizadas = [];
    foreach ($reasignaciones as $asignacionId => $destinoId) {
      if (!ctype_digit((string) $asignacionId) || !is_scalar($destinoId) || !ctype_digit((string) $destinoId) || (int) $destinoId < 1) {
        throw new Exception('Selecciona un nuevo resguardante válido para cada bien.');
      }
      $reasignacionesNormalizadas[(int) $asignacionId] = (int) $destinoId;
    }
    $reasignaciones = $reasignacionesNormalizadas;
    $conexionPropia = $pdo === null;
    $pdo ??= parent::connect(true);
    $savepoint = null;
    if ($conexionPropia) {
      if ($pdo->inTransaction()) $pdo->commit();
      $pdo->beginTransaction();
    } elseif (!$pdo->inTransaction()) {
      throw new Exception('La reasignación masiva requiere una transacción contenedora activa.');
    } else {
      $savepoint = 'resguardante_bulk_custody';
      $pdo->exec('SAVEPOINT ' . $savepoint);
    }
    try {
      $stmt = $pdo->prepare('SELECT id_resguardante, csp, activo FROM resguardante WHERE id_resguardante = ? OR id_resguardante IN (' . implode(',', array_fill(0, count(array_unique(array_map('intval', $reasignaciones))), '?')) . ') FOR UPDATE');
      $destinos = array_values(array_unique(array_map('intval', $reasignaciones)));
      $stmt->execute(array_merge([$anteriorId], $destinos));
      $resguardantes = [];
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) $resguardantes[(int) $fila['id_resguardante']] = $fila;
      if (empty($resguardantes[$anteriorId])) throw new Exception('El resguardante que se intenta desactivar ya no existe.');
      if (!(int) $resguardantes[$anteriorId]['activo']) throw new Exception('El resguardante que intentas desactivar ya está inactivo.');
      foreach ($destinos as $destinoId) {
        if ($destinoId === $anteriorId) throw new Exception('No puedes reasignar bienes al mismo resguardante que se va a desactivar.');
        if (empty($resguardantes[$destinoId]) || !(int) $resguardantes[$destinoId]['activo']) throw new Exception('Cada bien debe asignarse a un resguardante existente y activo.');
      }

      $stmt = $pdo->prepare('SELECT * FROM resguardo WHERE id_resguardante = ? AND activo = 1 FOR UPDATE');
      $stmt->execute([$anteriorId]);
      $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $asignacionesPorId = [];
      foreach ($asignaciones as $asignacion) $asignacionesPorId[(int) $asignacion['id_resguardo']] = $asignacion;
      if (count($asignacionesPorId) !== count($reasignaciones)) throw new Exception('La lista de reasignaciones no coincide con todos los bienes actualmente asignados. Actualiza la pantalla e inténtalo nuevamente.');
      foreach ($asignacionesPorId as $idResguardo => $_) {
        if (!isset($reasignaciones[$idResguardo]) || (int) $reasignaciones[$idResguardo] < 1) throw new Exception('Debes seleccionar un nuevo resguardante para cada bien.');
      }
      foreach ($asignaciones as $asignacion) {
        $nuevoId = (int) $reasignaciones[(int) $asignacion['id_resguardo']];
        $resultado = ResguardoModel::cambiarCustodia(
          (int) $asignacion['id_bien'], $nuevoId,
          ['fecha_asignacion' => date('Y-m-d')], 'Cambio de resguardante', null,
          'Transferencia individual por desactivación de resguardante.', $usuario, $pdo
        );
        if (empty($resultado['changed']) || (int) ($resultado['resguardo_anterior'] ?? 0) !== (int) $asignacion['id_resguardo']) {
          throw new Exception('La custodia de uno de los bienes cambió durante el proceso. La operación completa se canceló.');
        }
      }
      $stmt = $pdo->prepare('SELECT COUNT(*) FROM resguardo WHERE id_resguardante = ? AND activo = 1');
      $stmt->execute([$anteriorId]);
      if ((int) $stmt->fetchColumn() !== 0) throw new Exception('Aún existen asignaciones vigentes para este resguardante.');
      $stmt = $pdo->prepare('UPDATE resguardante SET activo = 0 WHERE id_resguardante = ? AND activo = 1');
      $stmt->execute([$anteriorId]);
      if ($stmt->rowCount() !== 1) throw new Exception('No se pudo desactivar el resguardante anterior.');
      if ($conexionPropia) $pdo->commit();
      else $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
      return count($asignaciones);
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
