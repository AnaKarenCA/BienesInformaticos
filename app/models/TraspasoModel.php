<?php

/** Operaciones transaccionales de expedientes de traspaso. */
class TraspasoModel extends Model
{
  private const ESTADOS_ACTIVOS = "('PENDIENTE_VALIDACION','VALIDADO')";

  public static function listar(array $f = []): array
  {
    $where = [];
    $params = [];
    $search = trim((string)($f['q'] ?? ''));
    if ($search !== '') {
      $where[] = "(t.folio LIKE :q OR uo.nombre LIKE :q OR uo.codigo_ua LIKE :q OR ud.nombre LIKE :q OR ud.codigo_ua LIKE :q OR rd.csp LIKE :q OR CONCAT_WS(' ',rd.nombre,rd.apellido_paterno,NULLIF(rd.apellido_materno,'')) LIKE :q OR ro.csp LIKE :q OR CONCAT_WS(' ',ro.nombre,ro.apellido_paterno,NULLIF(ro.apellido_materno,'')) LIKE :q)";
      $params['q'] = '%'.$search.'%';
    }
    foreach (['folio' => 't.folio', 'tipo' => 't.tipo', 'estado' => 't.estado'] as $key => $col) {
      $value = trim((string) ($f[$key] ?? ''));
      if ($value !== '') { $where[] = $key === 'folio' ? "$col LIKE :$key" : "$col = :$key"; $params[$key] = $key === 'folio' ? '%' . $value . '%' : $value; }
    }
    foreach (['unidad_origen' => ['uo.nombre','uo.codigo_ua'], 'unidad_destino' => ['ud.nombre','ud.codigo_ua']] as $key => $cols) {
      $value = trim((string)($f[$key] ?? ''));
      if ($value !== '') { $where[] = "($cols[0] LIKE :$key OR $cols[1] LIKE :$key)"; $params[$key] = '%'.$value.'%'; }
    }
    $receiver = trim((string)($f['resguardante_destino'] ?? ''));
    if ($receiver !== '') { $where[] = "(rd.csp LIKE :resguardante_destino OR CONCAT_WS(' ',rd.nombre,rd.apellido_paterno,NULLIF(rd.apellido_materno,'')) LIKE :resguardante_destino)"; $params['resguardante_destino'] = '%'.$receiver.'%'; }
    foreach (['desde' => 'DATE(t.fecha_creacion) >=', 'hasta' => 'DATE(t.fecha_creacion) <='] as $key => $predicate) {
      $value = trim((string) ($f[$key] ?? ''));
      if ($value !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) { $where[] = "$predicate :$key"; $params[$key] = $value; }
    }
    $sql = "SELECT t.*, uo.nombre AS unidad_origen_nombre, uo.codigo_ua AS unidad_origen_codigo,
      ud.nombre AS unidad_destino_nombre, ud.codigo_ua AS unidad_destino_codigo,
      CONCAT_WS(' ', rd.nombre, rd.apellido_paterno, NULLIF(rd.apellido_materno,'')) AS resguardante_destino_nombre,
      rd.csp AS resguardante_destino_csp,
      GROUP_CONCAT(DISTINCT CONCAT_WS(' ', ro.nombre, ro.apellido_paterno, NULLIF(ro.apellido_materno,'')) ORDER BY ro.apellido_paterno, ro.nombre SEPARATOR ' / ') AS resguardante_origen_nombre,
      GROUP_CONCAT(DISTINCT ro.csp ORDER BY ro.csp SEPARATOR ' / ') AS resguardante_origen_csp,
      COUNT(DISTINCT tb.id_bien) AS total_bienes
      FROM traspaso t JOIN unidad_administrativa uo ON uo.id_unidad=t.id_unidad_origen
      JOIN unidad_administrativa ud ON ud.id_unidad=t.id_unidad_destino
      JOIN resguardante rd ON rd.id_resguardante=t.id_resguardante_destino
      LEFT JOIN traspaso_bien tb ON tb.id_traspaso=t.id_traspaso
      LEFT JOIN resguardante ro ON ro.id_resguardante=tb.id_resguardante_origen" .
      ($where ? ' WHERE ' . implode(' AND ', $where) : '') .
      ' GROUP BY t.id_traspaso ORDER BY t.fecha_creacion DESC, t.id_traspaso DESC';
    return parent::query($sql, $params) ?: [];
  }

  /** Metadatos documentales para resúmenes de lectura; no expone rutas a la vista. */
  public static function documentosParaTraspasos(array $traspasos): array
  {
    $ids = array_values(array_unique(array_filter(array_map('intval', $traspasos), static fn(int $id): bool => $id > 0)));
    if (!$ids) return [];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $rows = parent::connect(true)->prepare("SELECT id_traspaso,ruta_interna,sha256,tamano_bytes FROM traspaso_documento WHERE id_traspaso IN ($placeholders)");
    $rows->execute($ids);
    $grouped = [];
    foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $row) $grouped[(int)$row['id_traspaso']][] = $row;
    return $grouped;
  }

  public static function detalle(int $id, ?PDO $pdo = null): array
  {
    $query = static function (string $sql, array $params = []) use ($pdo): array {
      if ($pdo === null) return parent::query($sql, $params) ?: [];
      $statement = $pdo->prepare($sql);
      $statement->execute($params);
      return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    };
    $rows = $query("SELECT t.*, uo.nombre AS unidad_origen_nombre, uo.codigo_ua AS unidad_origen_codigo,
      ud.nombre AS unidad_destino_nombre, ud.codigo_ua AS unidad_destino_codigo,
      CONCAT_WS(' ', rd.nombre, rd.apellido_paterno, NULLIF(rd.apellido_materno,'')) AS resguardante_destino_nombre,
      rd.csp AS resguardante_destino_csp, re.nombre AS resguardante_entrega_nombre, re.apellido_paterno AS resguardante_entrega_apellido,
      uc.username AS creador_username, ua.username AS autorizador_username, ur.username AS rechazador_username,
      ue.username AS usuario_entrega_username, uec.username AS confirma_entrega_username, urc.username AS confirma_recepcion_username,
      ux.username AS cancelador_username, uv.username AS usuario_reversion_username
      FROM traspaso t JOIN unidad_administrativa uo ON uo.id_unidad=t.id_unidad_origen
      JOIN unidad_administrativa ud ON ud.id_unidad=t.id_unidad_destino
      JOIN resguardante rd ON rd.id_resguardante=t.id_resguardante_destino
      LEFT JOIN resguardante re ON re.id_resguardante=t.id_resguardante_entrega
      LEFT JOIN bee_users uc ON uc.id=t.id_usuario_creador LEFT JOIN bee_users ua ON ua.id=t.id_usuario_autoriza
      LEFT JOIN bee_users ur ON ur.id=t.id_usuario_rechaza LEFT JOIN bee_users ue ON ue.id=t.id_usuario_entrega_persona
      LEFT JOIN bee_users uec ON uec.id=t.id_usuario_confirma_entrega LEFT JOIN bee_users urc ON urc.id=t.id_usuario_confirma_recepcion
      LEFT JOIN bee_users ux ON ux.id=t.id_usuario_cancela LEFT JOIN bee_users uv ON uv.id=t.id_usuario_reversion
      WHERE t.id_traspaso=:id LIMIT 1", ['id' => $id]);
    if (!$rows) return [];
    $t = $rows[0];
    $t['bienes'] = $query("SELECT tb.*, b.clave_interna, b.numero_inventario, b.nic_cea, b.nombre_bien, b.numero_serie,
      b.id_unidad AS unidad_actual, b.id_ubicacion AS ubicacion_actual,
      CONCAT_WS(' ', ro.nombre, ro.apellido_paterno, NULLIF(ro.apellido_materno,'')) AS resguardante_origen_nombre,
      ro.csp AS resguardante_origen_csp, udest.municipio AS municipio_destino, udest.localidad AS localidad_destino, udest.ubicacion_fisica AS ubicacion_fisica_destino,
      uactual.municipio AS municipio_actual, uactual.localidad AS localidad_actual
      FROM traspaso_bien tb JOIN bien b ON b.id_bien=tb.id_bien
      JOIN resguardante ro ON ro.id_resguardante=tb.id_resguardante_origen
      LEFT JOIN ubicacion udest ON udest.id_ubicacion=tb.id_ubicacion_destino
      LEFT JOIN ubicacion uactual ON uactual.id_ubicacion=b.id_ubicacion
      WHERE tb.id_traspaso=:id ORDER BY tb.id_traspaso_bien", ['id' => $id]) ?: [];
    foreach (['documentos' => 'SELECT d.*, u.username AS usuario_carga FROM traspaso_documento d LEFT JOIN bee_users u ON u.id=d.id_usuario_carga WHERE d.id_traspaso=:id ORDER BY d.fecha_carga DESC, d.id_traspaso_documento DESC',
      'eventos' => 'SELECT e.*, u.username FROM traspaso_evento e LEFT JOIN bee_users u ON u.id=e.id_usuario WHERE e.id_traspaso=:id ORDER BY e.id_traspaso_evento ASC'] as $key => $sql) {
      $t[$key] = $query($sql, ['id' => $id]);
      if ($key === 'eventos') foreach ($t[$key] as &$event) $event['metadatos'] = $event['metadatos'] ? json_decode($event['metadatos'], true) : [];
      unset($event);
    }
    $t['constancias_administrativas'] = self::constanciasAdministrativas($id, $pdo);
    return $t;
  }

  /** Las constancias son registros nuevos y separados de la bitácora operativa. */
  public static function constanciasAdministrativas(int $traspasoId, ?PDO $pdo = null): array
  {
    if (!self::table_exists('traspaso_constancia_administrativa')) return [];
    $sql = "SELECT c.*, u.username AS registrador_username,
      GROUP_CONCAT(cd.id_traspaso_documento ORDER BY cd.id_traspaso_documento SEPARATOR ', ') AS documentos_afectados
      FROM traspaso_constancia_administrativa c
      JOIN bee_users u ON u.id=c.id_usuario_registra
      LEFT JOIN traspaso_constancia_documento cd ON cd.id_constancia_administrativa=c.id_constancia_administrativa
      WHERE c.id_traspaso=:id
      GROUP BY c.id_constancia_administrativa
      ORDER BY c.fecha_registro DESC, c.id_constancia_administrativa DESC";
    if ($pdo === null) return parent::query($sql, ['id'=>$traspasoId]) ?: [];
    $statement = $pdo->prepare($sql);
    $statement->execute(['id'=>$traspasoId]);
    return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }

  /**
   * Registra exclusivamente una actuación administrativa presente en un entorno habilitado.
   * Nunca actualiza traspaso, movimientos, resguardos, documentos ni traspaso_evento.
   */
  public static function registrarConstanciaAdministrativa(int $traspasoId, int $usuarioId, array $input): int
  {
    if (getenv('BIENES_ENABLE_DOCUMENT_REGULARIZATION') !== '1') throw new RuntimeException('El registro de constancias administrativas no está habilitado en este entorno.');
    if (!self::table_exists('traspaso_constancia_administrativa')) throw new RuntimeException('La estructura de constancias administrativas no está instalada en este entorno.');
    if (($input['tipo_incidencia'] ?? '') !== 'DOCUMENTOS_AUSENTES') throw new InvalidArgumentException('El tipo de incidencia documental no es válido.');
    $documents = is_array($input['documentos'] ?? null) ? $input['documentos'] : [];
    $documentIds = array_values(array_unique(array_filter(array_map(static fn($value): int => filter_var($value, FILTER_VALIDATE_INT) ?: 0, $documents), static fn(int $id): bool => $id > 0)));
    if (!$documentIds) throw new InvalidArgumentException('Selecciona los documentos ausentes que sustentarán la constancia.');
    $detection = trim((string)($input['fecha_deteccion'] ?? ''));
    if ($detection !== '') { $date = DateTime::createFromFormat('!Y-m-d', $detection); if (!$date || $date->format('Y-m-d') !== $detection) throw new InvalidArgumentException('La fecha de detección no es válida.'); }
    $reference = trim((string)($input['referencia_resolucion'] ?? ''));
    if (mb_strlen($reference, 'UTF-8') > 150) throw new InvalidArgumentException('La referencia de resolución no puede exceder 150 caracteres.');

    $pdo = parent::connect(true);
    if ($pdo->inTransaction()) $pdo->commit();
    $pdo->beginTransaction();
    try {
      $transfer = $pdo->prepare('SELECT folio,estado,fecha_completado FROM traspaso WHERE id_traspaso=? FOR UPDATE');
      $transfer->execute([$traspasoId]);
      $record = $transfer->fetch(PDO::FETCH_ASSOC);
      if (!$record) throw new RuntimeException('No existe el expediente indicado.');
      $docs = $pdo->prepare('SELECT id_traspaso_documento,ruta_interna,sha256,tamano_bytes FROM traspaso_documento WHERE id_traspaso=? FOR UPDATE');
      $docs->execute([$traspasoId]);
      $allDocuments = $docs->fetchAll(PDO::FETCH_ASSOC);
      $absentIds = [];
      foreach ($allDocuments as $document) {
        $verification = TraspasoDocumentService::verificarDocumento((string)$document['ruta_interna'], (string)$document['sha256'], (int)$document['tamano_bytes']);
        if ($verification['estado'] === 'AUSENTE') $absentIds[] = (int)$document['id_traspaso_documento'];
      }
      sort($absentIds); sort($documentIds);
      if (!$absentIds || $absentIds !== $documentIds) throw new RuntimeException('La constancia debe incluir exactamente los documentos actualmente verificados como ausentes.');
      $fingerprint = hash('sha256', implode(',', $absentIds));
      $description = 'Constancia administrativa actual: se verificaron '.count($absentIds).' registros documentales con archivo físico ausente. La actuación no modifica el estado patrimonial, movimientos, resguardos, versiones, hashes ni documentos históricos.';
      $technicalEvidence = 'Verificación actual mediante TraspasoDocumentService contra el almacenamiento privado y los metadatos registrados. La ausencia de una validación de evidencia en el sistema no acredita ni descarta una validación externa.';
      $insert = $pdo->prepare('INSERT INTO traspaso_constancia_administrativa (id_traspaso,tipo_incidencia,estado,descripcion_hallazgos,evidencia_tecnica,fecha_hecho_historico,fecha_deteccion,id_usuario_registra,referencia_resolucion,huella_documentos) VALUES (?,?,?,?,?,?,?,?,?,?)');
      $insert->execute([$traspasoId,'DOCUMENTOS_AUSENTES','REGISTRADA',$description,$technicalEvidence,$record['fecha_completado'] ?: null,$detection !== '' ? $detection : null,$usuarioId,$reference !== '' ? $reference : null,$fingerprint]);
      $constancyId = (int)$pdo->lastInsertId();
      $link = $pdo->prepare('INSERT INTO traspaso_constancia_documento (id_constancia_administrativa,id_traspaso_documento) VALUES (?,?)');
      foreach ($absentIds as $documentId) $link->execute([$constancyId,$documentId]);
      $pdo->commit();
      return $constancyId;
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      throw $e;
    }
  }

  public static function bienesDisponibles(?int $resguardanteId = null): array
  {
    $sql = "SELECT b.id_bien, b.id_unidad, b.id_ubicacion, b.clave_interna, b.numero_inventario, b.nombre_bien, b.numero_serie,
      m.nombre AS marca_nombre, mo.nombre AS modelo_nombre, eu.nombre AS estado_nombre,
      ua.nombre AS unidad_nombre, ua.codigo_ua, ub.municipio, ub.localidad, ub.ubicacion_fisica, rg.id_resguardo, rg.id_resguardante,
      CONCAT_WS(' ', r.nombre, r.apellido_paterno, NULLIF(r.apellido_materno,'')) AS resguardante_nombre, r.csp
      FROM bien b JOIN unidad_administrativa ua ON ua.id_unidad=b.id_unidad
      JOIN resguardo rg ON rg.id_bien=b.id_bien AND rg.activo=1
      JOIN resguardante r ON r.id_resguardante=rg.id_resguardante AND r.activo=1
      LEFT JOIN marca m ON m.id_marca=b.id_marca
      LEFT JOIN modelo mo ON mo.id_modelo=b.id_modelo AND mo.id_marca=b.id_marca
      LEFT JOIN estado_uso eu ON eu.id_estado_uso=b.id_estado_uso
      LEFT JOIN ubicacion ub ON ub.id_ubicacion=b.id_ubicacion
      WHERE b.activo=1 AND NOT EXISTS (SELECT 1 FROM resguardo rg2 WHERE rg2.id_bien=b.id_bien AND rg2.activo=1 AND rg2.id_resguardo<>rg.id_resguardo)
      AND NOT EXISTS (SELECT 1 FROM traspaso_bien x JOIN traspaso t ON t.id_traspaso=x.id_traspaso
        WHERE x.id_bien=b.id_bien AND t.estado IN " . self::ESTADOS_ACTIVOS . " AND t.fecha_cancelacion IS NULL)";
    $params = [];
    if ($resguardanteId !== null) { $sql .= ' AND r.id_resguardante=:resguardante'; $params['resguardante'] = $resguardanteId; }
    return parent::query($sql . ' ORDER BY ua.nombre, r.apellido_paterno, b.clave_interna', $params) ?: [];
  }

  public static function catalogosFormulario(): array
  {
    return [
      'unidades' => parent::query('SELECT id_unidad, codigo_ua, nombre FROM unidad_administrativa WHERE activo=1 ORDER BY nombre') ?: [],
      'resguardantes' => parent::query("SELECT r.id_resguardante, r.id_unidad, r.csp, r.nombre, r.apellido_paterno, r.apellido_materno,
        CONCAT_WS(' ',r.nombre,r.apellido_paterno,NULLIF(r.apellido_materno,'')) AS nombre_completo, u.nombre AS unidad_nombre, u.codigo_ua AS unidad_codigo
        FROM resguardante r LEFT JOIN unidad_administrativa u ON u.id_unidad=r.id_unidad WHERE r.activo=1 ORDER BY r.apellido_paterno,r.nombre") ?: [],
      'ubicaciones' => parent::query('SELECT id_ubicacion, municipio, localidad FROM ubicacion ORDER BY municipio, localidad') ?: [],
      'bienes' => [],
    ];
  }

  public static function crear(array $datos, array $idsBien, int $usuarioId, ?PDO $pdo = null): int
  {
    $tipo = $datos['tipo'] ?? '';
    $origenId = (int) ($datos['id_unidad_origen'] ?? 0);
    $destinoId = (int) ($datos['id_unidad_destino'] ?? 0);
    $resguardanteOrigen = (int) ($datos['id_resguardante_origen'] ?? 0);
    $resguardanteDestino = (int) ($datos['id_resguardante_destino'] ?? 0);
    $idsBien = array_values(array_unique(array_filter(array_map('intval', $idsBien), static fn($id) => $id > 0)));
    sort($idsBien, SORT_NUMERIC);
    if (!in_array($tipo, ['ENTRE_UNIDADES','MISMA_UNIDAD'], true) || !$origenId || !$destinoId || !$resguardanteOrigen || !$resguardanteDestino || !$idsBien) throw new InvalidArgumentException('Completa el tipo, resguardante origen, destino y al menos un bien.');
    if (($tipo === 'MISMA_UNIDAD' && $origenId !== $destinoId) || ($tipo === 'ENTRE_UNIDADES' && $origenId === $destinoId)) throw new InvalidArgumentException('El tipo de traspaso no coincide con las unidades seleccionadas.');
    $transaccionPropia = $pdo === null;
    $pdo ??= parent::connect(true);
    if ($transaccionPropia) {
      if ($pdo->inTransaction()) $pdo->commit();
      $pdo->beginTransaction();
    } elseif (!$pdo->inTransaction()) {
      throw new RuntimeException('La creación coordinada del traspaso requiere una transacción activa.');
    }
    try {
      $unidades = [];
      foreach (array_unique([$origenId, $destinoId]) as $unitId) {
        $s = $pdo->prepare('SELECT id_unidad, nombre, codigo_ua, activo FROM unidad_administrativa WHERE id_unidad=? FOR UPDATE');
        $s->execute([$unitId]); $u = $s->fetch(PDO::FETCH_ASSOC);
        if (!$u || (int)$u['activo'] !== 1) throw new RuntimeException('La unidad administrativa debe existir y estar activa.');
        $unidades[$unitId] = $u;
      }
      $s = $pdo->prepare('SELECT id_resguardante,id_unidad,csp,activo FROM resguardante WHERE id_resguardante=? FOR UPDATE');
      $s->execute([$resguardanteDestino]); $nuevo = $s->fetch(PDO::FETCH_ASSOC);
      if (!$nuevo || (int)$nuevo['activo'] !== 1 || (int)$nuevo['id_unidad'] !== $destinoId) throw new RuntimeException('El nuevo resguardante debe estar activo y pertenecer a la unidad destino.');
      $s->execute([$resguardanteOrigen]); $actualOrigen = $s->fetch(PDO::FETCH_ASSOC);
      if (!$actualOrigen || (int)$actualOrigen['activo'] !== 1) throw new RuntimeException('El resguardante de origen debe existir y estar activo.');
      if ($tipo === 'MISMA_UNIDAD' && (int)$nuevo['id_resguardante'] === $resguardanteOrigen) throw new RuntimeException('Selecciona un resguardante distinto al origen.');
      $marks = implode(',', array_fill(0, count($idsBien), '?'));
      $s = $pdo->prepare("SELECT id_bien FROM bien WHERE id_bien IN ($marks) ORDER BY id_bien FOR UPDATE"); $s->execute($idsBien);
      if (count($s->fetchAll(PDO::FETCH_COLUMN)) !== count($idsBien)) throw new RuntimeException('Uno o más bienes no existen.');
      $s = $pdo->prepare("SELECT b.*,rg.id_resguardo,rg.id_resguardante,r.csp AS csp_resguardante,
        CONCAT_WS(' ',r.nombre,r.apellido_paterno,NULLIF(r.apellido_materno,'')) AS nombre_resguardante,
        r.id_unidad AS unidad_resguardante,
        r.activo AS resguardante_activo,ua.nombre AS nombre_unidad,ua.codigo_ua,uar.nombre AS unidad_resguardante_nombre,uar.codigo_ua AS unidad_resguardante_codigo,
        ub.municipio,ub.localidad
        FROM bien b JOIN resguardo rg ON rg.id_bien=b.id_bien AND rg.activo=1
        JOIN resguardante r ON r.id_resguardante=rg.id_resguardante JOIN unidad_administrativa ua ON ua.id_unidad=b.id_unidad
        JOIN unidad_administrativa uar ON uar.id_unidad=r.id_unidad
        LEFT JOIN ubicacion ub ON ub.id_ubicacion=b.id_ubicacion WHERE b.id_bien IN ($marks) ORDER BY b.id_bien FOR UPDATE");
      $s->execute($idsBien); $bienes = $s->fetchAll(PDO::FETCH_ASSOC);
      if (count($bienes) !== count($idsBien)) throw new RuntimeException('Cada bien debe tener exactamente una custodia vigente.');
      foreach ($bienes as $bien) {
        if ((int)$bien['activo'] !== 1 || (int)$bien['resguardante_activo'] !== 1 || (int)$bien['id_resguardante'] !== $resguardanteOrigen || (int)$bien['id_unidad'] !== $origenId) throw new RuntimeException('Cada bien debe pertenecer al resguardante origen seleccionado y compartir la unidad patrimonial de origen indicada.');
        if ((int)$bien['id_resguardante'] === $resguardanteDestino) throw new RuntimeException('El nuevo resguardante ya tiene la custodia de uno de los bienes seleccionados.');
      }
      $s = $pdo->prepare("SELECT COUNT(*) FROM traspaso_bien tb JOIN traspaso t ON t.id_traspaso=tb.id_traspaso WHERE tb.id_bien IN ($marks) AND t.estado IN " . self::ESTADOS_ACTIVOS . " AND t.fecha_cancelacion IS NULL");
      $s->execute($idsBien);
      if ((int)$s->fetchColumn() > 0) throw new RuntimeException('Uno o más bienes ya pertenecen a un traspaso activo.');

      // El oficio ya viene autorizado externamente. TI únicamente valida el expediente.
      $estadoInicial = $tipo === 'MISMA_UNIDAD' ? 'VALIDADO' : 'PENDIENTE_VALIDACION';
      $insert = $pdo->prepare("INSERT INTO traspaso (folio,tipo,estado,id_unidad_origen,id_unidad_destino,id_resguardante_destino,id_usuario_creador,motivo,observaciones)
        VALUES ('PENDIENTE',?,?, ?,?,?,?,?,?)");
      $insert->execute([$tipo,$estadoInicial,$origenId,$destinoId,$resguardanteDestino,$usuarioId,trim((string)($datos['motivo']??'')) ?: null,trim((string)($datos['observaciones']??'')) ?: null]);
      $id = (int)$pdo->lastInsertId(); $folio = sprintf('TR-%06d',$id);
      $pdo->prepare('UPDATE traspaso SET folio=? WHERE id_traspaso=?')->execute([$folio,$id]);
      $line = $pdo->prepare('INSERT INTO traspaso_bien (id_traspaso,id_bien,id_resguardante_origen,id_resguardo_origen,id_ubicacion_destino,ubicacion_sin_cambio,origen_snapshot) VALUES (?,?,?,?,?,?,?)');
      foreach ($bienes as $bien) {
        $snapshot = [
          'unidad' => ['id' => (int)$bien['id_unidad'], 'nombre' => $bien['nombre_unidad'], 'codigo_ua' => $bien['codigo_ua']],
          'unidad_resguardante' => ['id' => (int)$bien['unidad_resguardante'], 'nombre' => $bien['unidad_resguardante_nombre'], 'codigo_ua' => $bien['unidad_resguardante_codigo']],
          'resguardante' => ['id' => (int)$bien['id_resguardante'], 'nombre' => $bien['nombre_resguardante'], 'csp' => $bien['csp_resguardante']],
          'resguardo_id' => (int)$bien['id_resguardo'], 'ubicacion_id' => $bien['id_ubicacion'] ? (int)$bien['id_ubicacion'] : null,
          'ubicacion' => trim(implode(', ',array_filter([$bien['municipio'],$bien['localidad']]))),
          'clave_interna' => $bien['clave_interna'], 'numero_inventario' => $bien['numero_inventario']
        ];
        $location = isset($datos['ubicaciones'][$bien['id_bien']]) && $datos['ubicaciones'][$bien['id_bien']] !== '' ? (int)$datos['ubicaciones'][$bien['id_bien']] : null;
        if ($location) {
          $valid = $pdo->prepare('SELECT id_ubicacion FROM ubicacion WHERE id_ubicacion=?'); $valid->execute([$location]);
          if (!$valid->fetchColumn()) throw new RuntimeException('La ubicación seleccionada no existe.');
        }
        $line->execute([$id,$bien['id_bien'],$bien['id_resguardante'],$bien['id_resguardo'],$location,$location ? 0 : 1,json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
      }
      self::eventoEn($pdo,$id,$usuarioId,'CREACION',null,$estadoInicial,null,['folio'=>$folio,'tipo'=>$tipo,'resguardante_origen'=>$resguardanteOrigen,'bienes'=>$idsBien]);
      if ($transaccionPropia) $pdo->commit();
      return $id;
    } catch (Throwable $e) { if ($transaccionPropia && $pdo->inTransaction()) $pdo->rollBack(); throw $e; }
  }

  public static function actualizarPendienteValidacion(int $id, array $datos, array $idsBien, int $usuarioId): void
  {
    $actual = self::detalle($id);
    if (!$actual || $actual['estado'] !== 'PENDIENTE_VALIDACION') throw new RuntimeException('Solo se puede editar un expediente pendiente de validación.');
    if ((int)$actual['id_usuario_creador'] !== $usuarioId && (string)(get_user('rol') ?: '') !== 'admin') throw new RuntimeException('Solo quien creó el expediente o un administrador puede modificarlo.');
    if (($datos['tipo'] ?? '') !== $actual['tipo'] || (int)($datos['id_unidad_origen']??0)!==(int)$actual['id_unidad_origen'] || (int)($datos['id_unidad_destino']??0)!==(int)$actual['id_unidad_destino'] || (int)($datos['id_resguardante_destino']??0)!==(int)$actual['id_resguardante_destino'] || array_map('intval',$idsBien)!==array_map(static fn($r)=>(int)$r['id_bien'],$actual['bienes'])) throw new RuntimeException('No se permiten cambios de bienes, unidades o resguardante desde esta edición.');
    $pdo=parent::connect(true); if($pdo->inTransaction())$pdo->commit(); $pdo->beginTransaction();
    try { $s=$pdo->prepare("SELECT estado,id_usuario_creador FROM traspaso WHERE id_traspaso=? FOR UPDATE");$s->execute([$id]);$locked=$s->fetch(PDO::FETCH_ASSOC);if(!$locked||$locked['estado']!=='PENDIENTE_VALIDACION')throw new RuntimeException('El expediente cambió de estado; vuelve a cargarlo.');if((int)$locked['id_usuario_creador']!==$usuarioId&&(string)(get_user('rol')?:'')!=='admin')throw new RuntimeException('Solo quien creó el expediente o un administrador puede modificarlo.');
      $s=$pdo->prepare('UPDATE traspaso SET motivo=?,observaciones=? WHERE id_traspaso=?');$s->execute([trim((string)($datos['motivo']??''))?:null,trim((string)($datos['observaciones']??''))?:null,$id]);
      self::eventoEn($pdo,$id,$usuarioId,'MODIFICACION','PENDIENTE_VALIDACION','PENDIENTE_VALIDACION',null,['antes'=>['motivo'=>$actual['motivo'],'observaciones'=>$actual['observaciones']],'despues'=>['motivo'=>$datos['motivo']??null,'observaciones'=>$datos['observaciones']??null]]);$pdo->commit();
    } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  public static function actualizarDatosDocumento(int $id,string $motivo,string $observaciones,int $usuarioId):void
  {
    $motivo=trim($motivo);$observaciones=trim($observaciones);if(mb_strlen($motivo)>250||mb_strlen($observaciones)>1000)throw new InvalidArgumentException('El motivo o las observaciones exceden la longitud permitida.');
    $pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();
    try{$s=$pdo->prepare('SELECT estado,motivo,observaciones,id_usuario_creador FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$current=$s->fetch(PDO::FETCH_ASSOC);if(!$current||$current['estado']!=='VALIDADO')throw new RuntimeException('Los datos solo pueden editarse en un expediente validado y no completado.');if((int)$current['id_usuario_creador']!==$usuarioId&&(string)(get_user('rol')?:'')!=='admin')throw new RuntimeException('Solo quien creó el expediente o un administrador puede modificarlo.');
      $s=$pdo->prepare("SELECT COUNT(*) FROM traspaso_evento WHERE id_traspaso=? AND tipo_evento IN ('FIRMA_DOCUMENTO','CARGA_DOCUMENTO_FIRMADO')");$s->execute([$id]);if((int)$s->fetchColumn()>0)throw new RuntimeException('Ya se cargó evidencia firmada. No se pueden editar los datos firmados.');
      $s=$pdo->prepare('SELECT COALESCE(MAX(id_traspaso_documento),0)+1 FROM traspaso_documento WHERE id_traspaso=?');$s->execute([$id]);$from=(int)$s->fetchColumn();
      $pdo->prepare("UPDATE traspaso SET motivo=?,observaciones=? WHERE id_traspaso=?")->execute([$motivo?:null,$observaciones?:null,$id]);
      self::eventoEn($pdo,$id,$usuarioId,'EDICION_DATOS_DOCUMENTO',$current['estado'],$current['estado'],null,['documento_desde'=>$from,'antes'=>['motivo'=>$current['motivo'],'observaciones'=>$current['observaciones']],'despues'=>['motivo'=>$motivo,'observaciones'=>$observaciones]]);$pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  /** Persiste los dos campos editables de la baja sin alterar los datos fuente. */
  public static function actualizarCamposBaja(int $id,int $documentoId,array $campos,int $usuarioId):void
  {
    $ccp=str_replace(["\r\n","\r"],"\n",trim((string)($campos['ccp']??'')));$observaciones=str_replace(["\r\n","\r"],"\n",trim((string)($campos['observaciones']??'')));
    if(mb_strlen($ccp,'UTF-8')>500)throw new InvalidArgumentException('El campo C. c. p. no puede exceder 500 caracteres.');
    if(mb_strlen($observaciones,'UTF-8')>2000)throw new InvalidArgumentException('El campo Observaciones no puede exceder 2000 caracteres.');
    $pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();
    try{
      $s=$pdo->prepare('SELECT estado,id_usuario_creador FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$transfer=$s->fetch(PDO::FETCH_ASSOC);
      if(!$transfer||$transfer['estado']!=='VALIDADO')throw new RuntimeException('El C. c. p. solo puede editarse en un expediente validado.');self::assertOwner($transfer,$usuarioId);
      $s=$pdo->prepare('SELECT id_bien,tipo_documento FROM traspaso_documento WHERE id_traspaso_documento=? AND id_traspaso=? FOR UPDATE');$s->execute([$documentoId,$id]);$document=$s->fetch(PDO::FETCH_ASSOC);
      if(!$document||empty($document['id_bien'])||$document['tipo_documento']!=='BAJA_RESGUARDANTE')throw new RuntimeException('Selecciona una baja de resguardante asociada al expediente.');
      $bienId=(int)$document['id_bien'];$s=$pdo->prepare('SELECT id_resguardante_origen FROM traspaso_bien WHERE id_traspaso=? AND id_bien=? FOR UPDATE');$s->execute([$id,$bienId]);$item=$s->fetch(PDO::FETCH_ASSOC);
      if(!$item||empty($item['id_resguardante_origen']))throw new RuntimeException('El bien no requiere una baja de resguardante.');
      if(self::documentoVigente($pdo,$id,$bienId,'BAJA_RESGUARDANTE',self::documentoMinimoVigente($pdo,$id))!==$documentoId)throw new RuntimeException('La baja seleccionada ya no es la versión vigente. Actualiza el expediente.');
      $s=$pdo->prepare("SELECT COUNT(*) FROM traspaso_evento WHERE id_traspaso=? AND tipo_evento='CARGA_DOCUMENTO_FIRMADO' AND JSON_UNQUOTE(JSON_EXTRACT(metadatos,'$.documento_origen'))=CAST(? AS CHAR)");$s->execute([$id,$documentoId]);if((int)$s->fetchColumn()>0)throw new RuntimeException('Ya se cargó evidencia firmada para esta baja. No se pueden modificar sus datos.');
      $s=$pdo->prepare("SELECT metadatos FROM traspaso_evento WHERE id_traspaso=? AND tipo_evento='EDICION_CCP_BAJA' ORDER BY id_traspaso_evento DESC");$s->execute([$id]);$anteriores=['ccp'=>'','observaciones'=>null];
      foreach($s->fetchAll(PDO::FETCH_COLUMN) as $json){$meta=json_decode((string)$json,true)?:[];if((int)($meta['bien']??0)===$bienId){$anteriores['ccp']=str_replace(["\r\n","\r"],"\n",(string)($meta['ccp']??''));if(array_key_exists('observaciones',$meta))$anteriores['observaciones']=str_replace(["\r\n","\r"],"\n",(string)$meta['observaciones']);break;}}
      if($anteriores['ccp']===$ccp&&$anteriores['observaciones']===$observaciones){$pdo->commit();return;}
      self::eventoEn($pdo,$id,$usuarioId,'EDICION_CCP_BAJA',$transfer['estado'],$transfer['estado'],null,['bien'=>$bienId,'documento_baja'=>$documentoId,'ccp'=>$ccp,'observaciones'=>$observaciones]);$pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  /** Guarda ajustes de presentación para una tarjeta sin alterar el inventario fuente. */
  public static function actualizarCamposTarjeta(int $id,int $documentoId,array $campos,int $usuarioId):void
  {
    $permitidos=['observaciones'];
    $limpios=[];foreach($permitidos as $key){$value=trim((string)($campos[$key]??''));$limit=$key==='observaciones'?1000:500;if(mb_strlen($value,'UTF-8')>$limit)throw new InvalidArgumentException('Uno de los campos de la tarjeta excede la longitud permitida.');$limpios[$key]=$value;}
    $pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();
    try{
      $s=$pdo->prepare('SELECT estado,id_usuario_creador FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$transfer=$s->fetch(PDO::FETCH_ASSOC);
      if(!$transfer||$transfer['estado']!=='VALIDADO')throw new RuntimeException('La tarjeta solo puede editarse en un expediente validado.');self::assertOwner($transfer,$usuarioId);
      $s=$pdo->prepare("SELECT id_bien,tipo_documento FROM traspaso_documento WHERE id_traspaso_documento=? AND id_traspaso=? FOR UPDATE");$s->execute([$documentoId,$id]);$document=$s->fetch(PDO::FETCH_ASSOC);
      if(!$document||empty($document['id_bien'])||$document['tipo_documento']!=='TARJETA_RESGUARDO')throw new RuntimeException('Selecciona una Tarjeta de Resguardo asociada al expediente.');
      $bienId=(int)$document['id_bien'];if(self::documentoVigente($pdo,$id,$bienId,'TARJETA_RESGUARDO',self::documentoMinimoVigente($pdo,$id))!==$documentoId)throw new RuntimeException('La tarjeta seleccionada ya no es la versión vigente. Actualiza el expediente.');
      $s=$pdo->prepare("SELECT COUNT(*) FROM traspaso_evento WHERE id_traspaso=? AND tipo_evento='CARGA_DOCUMENTO_FIRMADO' AND JSON_UNQUOTE(JSON_EXTRACT(metadatos,'$.documento_origen'))=CAST(? AS CHAR)");$s->execute([$id,$documentoId]);if((int)$s->fetchColumn()>0)throw new RuntimeException('Ya se cargó evidencia firmada. No se puede modificar esta tarjeta.');
      $s=$pdo->prepare("SELECT metadatos FROM traspaso_evento WHERE id_traspaso=? AND tipo_evento='EDICION_TARJETA_RESGUARDO' ORDER BY id_traspaso_evento DESC");$s->execute([$id]);$previous=[];foreach($s->fetchAll(PDO::FETCH_COLUMN) as $json){$meta=json_decode((string)$json,true)?:[];if((int)($meta['bien']??0)===$bienId){$previous=$meta['campos']??[];break;}}
      if($previous===$limpios){$pdo->commit();return;}
      self::eventoEn($pdo,$id,$usuarioId,'EDICION_TARJETA_RESGUARDO',$transfer['estado'],$transfer['estado'],null,['bien'=>$bienId,'documento_tarjeta'=>$documentoId,'campos'=>$limpios]);$pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  /** Guarda únicamente los campos editables del Resguardo del equipo sin alterar el resguardo patrimonial. */
  public static function actualizarCamposResguardoEquipo(int $id,int $documentoId,array $campos,int $usuarioId):void
  {
    $limites=['notas'=>1000,'tipo_equipo'=>150,'estado_equipo'=>150,'observaciones'=>2000];$limpios=[];
    foreach($limites as $key=>$limit){$value=(string)($campos[$key]??'');if(in_array($key,['notas','observaciones'],true))$value=str_replace(["\r\n","\r"],"\n",$value);else$value=trim($value);if(mb_strlen($value,'UTF-8')>$limit)throw new InvalidArgumentException('Uno de los campos del Resguardo del equipo excede la longitud permitida.');$limpios[$key]=$value;}
    $candado=strtoupper(trim((string)($campos['candado']??'')));if(!in_array($candado,['SI','NO'],true))throw new InvalidArgumentException('Selecciona Sí o No en el campo Candado.');$limpios['candado']=$candado;
    $pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();
    try{
      $s=$pdo->prepare('SELECT estado,id_usuario_creador FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$transfer=$s->fetch(PDO::FETCH_ASSOC);
      if(!$transfer||$transfer['estado']!=='VALIDADO')throw new RuntimeException('El Resguardo del equipo solo puede editarse en un expediente validado.');self::assertOwner($transfer,$usuarioId);
      $s=$pdo->prepare("SELECT id_bien,tipo_documento FROM traspaso_documento WHERE id_traspaso_documento=? AND id_traspaso=? FOR UPDATE");$s->execute([$documentoId,$id]);$document=$s->fetch(PDO::FETCH_ASSOC);
      if(!$document||empty($document['id_bien'])||$document['tipo_documento']!=='RESGUARDO_EQUIPO')throw new RuntimeException('Selecciona un Resguardo del equipo asociado al expediente.');
      $bienId=(int)$document['id_bien'];if(self::documentoVigente($pdo,$id,$bienId,'RESGUARDO_EQUIPO',self::documentoMinimoVigente($pdo,$id))!==$documentoId)throw new RuntimeException('El documento seleccionado ya no es la versión vigente. Actualiza el expediente.');
      $s=$pdo->prepare("SELECT COUNT(*) FROM traspaso_evento WHERE id_traspaso=? AND tipo_evento='CARGA_DOCUMENTO_FIRMADO' AND JSON_UNQUOTE(JSON_EXTRACT(metadatos,'$.documento_origen'))=CAST(? AS CHAR)");$s->execute([$id,$documentoId]);if((int)$s->fetchColumn()>0)throw new RuntimeException('Ya se cargó evidencia firmada. No se puede modificar este Resguardo del equipo.');
      $s=$pdo->prepare("SELECT metadatos FROM traspaso_evento WHERE id_traspaso=? AND tipo_evento='EDICION_RESGUARDO_EQUIPO' ORDER BY id_traspaso_evento DESC");$s->execute([$id]);$previous=[];foreach($s->fetchAll(PDO::FETCH_COLUMN) as $json){$meta=json_decode((string)$json,true)?:[];if((int)($meta['bien']??0)===$bienId){$previous=$meta['campos']??[];break;}}
      if($previous===$limpios){$pdo->commit();return;}
      self::eventoEn($pdo,$id,$usuarioId,'EDICION_RESGUARDO_EQUIPO',$transfer['estado'],$transfer['estado'],null,['bien'=>$bienId,'documento_resguardo_equipo'=>$documentoId,'campos'=>$limpios]);$pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  /** Validación documental de TI; la autorización es externa y está en el oficio cargado. */
  public static function validarOficio(int $id,int $usuarioId):void
  { self::transicion($id,$usuarioId,'PENDIENTE_VALIDACION',static function(PDO $pdo,array $t)use($id,$usuarioId){$s=$pdo->prepare("SELECT COUNT(*) FROM traspaso_documento WHERE id_traspaso=? AND tipo_documento='OFICIO_AUTORIZACION'");$s->execute([$id]);if(!(int)$s->fetchColumn())throw new RuntimeException('El expediente no conserva el oficio externo.');$pdo->prepare("UPDATE traspaso SET estado='VALIDADO',id_usuario_autoriza=?,fecha_autorizacion=NOW() WHERE id_traspaso=?")->execute([$usuarioId,$id]);self::eventoEn($pdo,$id,$usuarioId,'VALIDACION_DOCUMENTAL','PENDIENTE_VALIDACION','VALIDADO','El administrador validó documentalmente el oficio externo.');}); }

  /** Valida la versión exacta de evidencia vigente para un documento del expediente. */
  public static function validarEvidencia(int $id,int $documentoOrigen,int $evidenciaId,int $usuarioId):void
  {
    $pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();
    try{$s=$pdo->prepare('SELECT estado,id_usuario_creador FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$transfer=$s->fetch(PDO::FETCH_ASSOC);if(!$transfer||$transfer['estado']!=='VALIDADO')throw new RuntimeException('La evidencia solo puede validarse en un expediente validado.');
      $source=$pdo->prepare('SELECT id_bien,tipo_documento,ruta_interna,sha256,tamano_bytes FROM traspaso_documento WHERE id_traspaso_documento=? AND id_traspaso=? FOR UPDATE');$source->execute([$documentoOrigen,$id]);$origin=$source->fetch(PDO::FETCH_ASSOC);if(!$origin||empty($origin['id_bien'])||!in_array($origin['tipo_documento'],['TARJETA_RESGUARDO','RESGUARDO_EQUIPO','BAJA_RESGUARDANTE'],true)||self::documentoVigente($pdo,$id,(int)$origin['id_bien'],(string)$origin['tipo_documento'])!==$documentoOrigen)throw new RuntimeException('El documento generado ya no es la versión vigente. Actualiza el expediente.');$sourceVerification=TraspasoDocumentService::verificarDocumento((string)$origin['ruta_interna'],(string)$origin['sha256'],(int)$origin['tamano_bytes']);if(!$sourceVerification['disponible'])throw new RuntimeException('No se puede validar la evidencia: el documento origen no está disponible ('.$sourceVerification['mensaje'].').');
      $current=self::evidenciaVigente($pdo,$id,$documentoOrigen);if($current!==$evidenciaId)throw new RuntimeException('La evidencia seleccionada ya fue reemplazada. Actualiza el expediente antes de validar.');$file=$pdo->prepare('SELECT id_bien,tipo_documento,id_documento_anterior,ruta_interna,sha256,tamano_bytes FROM traspaso_documento WHERE id_traspaso_documento=? AND id_traspaso=? FOR UPDATE');$file->execute([$evidenciaId,$id]);$evidence=$file->fetch(PDO::FETCH_ASSOC);if(!$evidence||(int)$evidence['id_bien']!==(int)$origin['id_bien']||$evidence['tipo_documento']!==$origin['tipo_documento']||(int)$evidence['id_documento_anterior']!==$documentoOrigen)throw new RuntimeException('El archivo no corresponde a la evidencia firmada de este documento.');$evidenceVerification=TraspasoDocumentService::verificarDocumento((string)$evidence['ruta_interna'],(string)$evidence['sha256'],(int)$evidence['tamano_bytes']);if(!$evidenceVerification['disponible'])throw new RuntimeException('No se puede validar la evidencia: el archivo firmado no está disponible ('.$evidenceVerification['mensaje'].').');
      if(self::evidenciaTieneDecision($pdo,$id,$documentoOrigen,$evidenciaId))throw new RuntimeException('La evidencia vigente ya fue revisada. Actualiza el expediente.');
      $meta=['documento_origen'=>$documentoOrigen,'documento_evidencia'=>$evidenciaId,'tipo'=>$origin['tipo_documento'],'bien'=>(int)$origin['id_bien']];
      self::eventoEn($pdo,$id,$usuarioId,'VALIDACION_EVIDENCIA','VALIDADO','VALIDADO',null,$meta);$pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  /** Rechaza únicamente la versión activa de una evidencia y conserva todo el historial. */
  public static function rechazarEvidencia(int $id,int $documentoOrigen,int $evidenciaId,string $motivo,int $usuarioId):void
  {
    $motivo=trim($motivo);if($motivo==='')throw new InvalidArgumentException('Indica el motivo del rechazo de la evidencia.');if(mb_strlen($motivo,'UTF-8')>500)throw new InvalidArgumentException('El motivo de rechazo no puede exceder 500 caracteres.');
    $pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();
    try{$s=$pdo->prepare('SELECT estado FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$transfer=$s->fetch(PDO::FETCH_ASSOC);if(!$transfer||$transfer['estado']!=='VALIDADO')throw new RuntimeException('La evidencia solo puede rechazarse en un expediente validado.');
      $source=$pdo->prepare('SELECT id_bien,tipo_documento FROM traspaso_documento WHERE id_traspaso_documento=? AND id_traspaso=? FOR UPDATE');$source->execute([$documentoOrigen,$id]);$origin=$source->fetch(PDO::FETCH_ASSOC);if(!$origin||empty($origin['id_bien'])||!in_array($origin['tipo_documento'],['TARJETA_RESGUARDO','RESGUARDO_EQUIPO','BAJA_RESGUARDANTE'],true)||self::documentoVigente($pdo,$id,(int)$origin['id_bien'],(string)$origin['tipo_documento'])!==$documentoOrigen)throw new RuntimeException('El documento generado ya no es la versión vigente. Actualiza el expediente.');
      $current=self::evidenciaVigente($pdo,$id,$documentoOrigen);if($current!==$evidenciaId)throw new RuntimeException('La evidencia seleccionada ya fue reemplazada. Actualiza el expediente antes de rechazar.');$file=$pdo->prepare('SELECT id_bien,tipo_documento,id_documento_anterior FROM traspaso_documento WHERE id_traspaso_documento=? AND id_traspaso=? FOR UPDATE');$file->execute([$evidenciaId,$id]);$evidence=$file->fetch(PDO::FETCH_ASSOC);if(!$evidence||(int)$evidence['id_bien']!==(int)$origin['id_bien']||$evidence['tipo_documento']!==$origin['tipo_documento']||(int)$evidence['id_documento_anterior']!==$documentoOrigen)throw new RuntimeException('El archivo no corresponde a la evidencia firmada de este documento.');
      if(self::evidenciaTieneDecision($pdo,$id,$documentoOrigen,$evidenciaId))throw new RuntimeException('La evidencia vigente ya fue revisada. Actualiza el expediente.');
      $meta=['documento_origen'=>$documentoOrigen,'documento_evidencia'=>$evidenciaId,'tipo'=>$origin['tipo_documento'],'bien'=>(int)$origin['id_bien']];self::eventoEn($pdo,$id,$usuarioId,'RECHAZO_EVIDENCIA','VALIDADO','VALIDADO',$motivo,$meta);$pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  /** Compatibilidad de llamada mientras las vistas migran a la acción de validar. */
  public static function autorizar(int $id,int $usuarioId):void { self::validarOficio($id,$usuarioId); }

  public static function rechazar(int $id,int $usuarioId,string $motivo):void
  { throw new RuntimeException('El flujo interno valida documentalmente el oficio y no permite rechazar la autorización externa.'); }

  public static function cancelar(int $id,int $usuarioId,string $motivo=''):void
  { throw new RuntimeException('El proceso no contempla cancelar una autorización externa desde el expediente.'); }

  public static function confirmarEntrega(int $id,int $usuarioId,string $tipoEntrega,string $responsableFisico=''):void
  {
    if(!in_array($tipoEntrega,['RESGUARDANTE_ACTUAL','ADMINISTRADOR'],true))throw new InvalidArgumentException('Selecciona quién registra la entrega.');
    if(trim($responsableFisico)==='')throw new InvalidArgumentException('Indica el nombre de quien entrega físicamente los bienes.');
    self::transicion($id,$usuarioId,'VALIDADO',static function(PDO $pdo,array $t)use($id,$usuarioId,$tipoEntrega,$responsableFisico){
      self::assertOwner($t,$usuarioId);
      if($t['tipo']==='ENTRE_UNIDADES'&&empty($t['fecha_autorizacion']))throw new RuntimeException('El oficio externo debe validarse antes de registrar la entrega.');
      $s=$pdo->prepare('SELECT DISTINCT id_resguardante_origen FROM traspaso_bien WHERE id_traspaso=?');$s->execute([$id]);$origins=array_map('intval',$s->fetchAll(PDO::FETCH_COLUMN));
      if($tipoEntrega==='RESGUARDANTE_ACTUAL'&&count($origins)!==1)throw new RuntimeException('Los bienes tienen resguardantes de origen distintos; registra la entrega como Administrador.');
      if($tipoEntrega==='ADMINISTRADOR'&&($t['_actor_role']??'')!=='admin')throw new RuntimeException('Solo el Administrador puede registrar la entrega en calidad de Administrador.');
      if(!empty($t['fecha_entrega']))throw new RuntimeException('La entrega ya fue registrada.');
      $personId=$tipoEntrega==='RESGUARDANTE_ACTUAL'?$origins[0]:null;
      $pdo->prepare("UPDATE traspaso SET tipo_entrega=?,id_resguardante_entrega=?,id_usuario_entrega_persona=?,id_usuario_confirma_entrega=?,fecha_entrega=NOW() WHERE id_traspaso=?")
        ->execute([$tipoEntrega,$personId,$tipoEntrega==='ADMINISTRADOR'?$usuarioId:null,$usuarioId,$id]);
      self::eventoEn($pdo,$id,$usuarioId,'ENTREGA_REGISTRADA','VALIDADO','VALIDADO',null,['tipo_entrega'=>$tipoEntrega,'resguardante'=>$personId,'responsable_fisico'=>trim($responsableFisico)]);
    });
  }

  /** Confirma recepción y ejecuta todos los cambios patrimoniales en la misma transacción. */
  /** Registra la recepción física; el cierre patrimonial ocurre en completarDocumentado(). */
  public static function confirmarRecepcion(int $id,int $usuarioId,string $responsableFisico=''):void
  {
    if(trim($responsableFisico)==='')throw new InvalidArgumentException('Indica el nombre de quien recibe físicamente los bienes.');
    $pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();
    try{$s=$pdo->prepare('SELECT * FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$t=$s->fetch(PDO::FETCH_ASSOC);
      if(!$t||$t['estado']!=='VALIDADO')throw new RuntimeException('El expediente no está validado o ya fue completado.');
      self::assertOwner($t,$usuarioId);
      if(empty($t['fecha_entrega']))throw new RuntimeException('Registra la entrega antes de registrar la recepción.');
      if((int)$t['id_usuario_confirma_entrega']===$usuarioId)throw new RuntimeException('La recepción debe registrarla una persona distinta a quien registró la entrega.');
      if(!empty($t['fecha_recepcion']))throw new RuntimeException('La recepción ya fue registrada.');
      $rid=(int)$t['id_resguardante_destino'];$s=$pdo->prepare('SELECT id_resguardante,id_unidad,activo FROM resguardante WHERE id_resguardante=? FOR UPDATE');$s->execute([$rid]);$receiver=$s->fetch(PDO::FETCH_ASSOC);
      if(!$receiver||(int)$receiver['activo']!==1||(int)$receiver['id_unidad']!==(int)$t['id_unidad_destino'])throw new RuntimeException('El nuevo resguardante ya no es válido para la unidad destino.');
      $pdo->prepare('UPDATE traspaso SET id_usuario_confirma_recepcion=?,fecha_recepcion=NOW() WHERE id_traspaso=?')->execute([$usuarioId,$id]);
      self::eventoEn($pdo,$id,$usuarioId,'RECEPCION_REGISTRADA','VALIDADO','VALIDADO',null,['resguardante_receptor'=>$rid,'responsable_fisico'=>trim($responsableFisico)]);$pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }
  public static function registrarDocumento(int $id,string $tipo,string $ruta,string $nombre,string $mime,int $bytes,string $hash,int $usuarioId,?int $anterior=null,?int $bienId=null,?array $metadata=null,?PDO $pdo=null):int
  {
    $transaccionPropia=$pdo===null;$pdo??=parent::connect(true);if($transaccionPropia){if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();}elseif(!$pdo->inTransaction())throw new RuntimeException('El registro coordinado del documento requiere una transacción activa.');
    try{$s=$pdo->prepare('SELECT estado,id_usuario_creador FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$transfer=$s->fetch(PDO::FETCH_ASSOC);$estado=$transfer['estado']??null;if(!$estado)throw new RuntimeException('No existe el expediente.');self::assertOwner($transfer,$usuarioId);
      if($tipo==='OFICIO_AUTORIZACION'&&!in_array($estado,['PENDIENTE_VALIDACION','VALIDADO'],true))throw new RuntimeException('El oficio solo puede cargarse antes de completar el expediente.');
      if(in_array($tipo,['TARJETA_RESGUARDO','RESGUARDO_EQUIPO','BAJA_RESGUARDANTE'],true)){
        if($estado!=='VALIDADO'||!$bienId||!$anterior)throw new RuntimeException('Selecciona un documento vigente del expediente para reemplazarlo.');
        $current=self::documentoVigente($pdo,$id,$bienId,$tipo);
        if($current!==$anterior)throw new RuntimeException('El documento seleccionado ya no es la versión vigente. Actualiza el expediente.');
        $s=$pdo->prepare('SELECT id_traspaso_documento,id_bien,tipo_documento FROM traspaso_documento WHERE id_traspaso_documento=? AND id_traspaso=? FOR UPDATE');$s->execute([$anterior,$id]);$old=$s->fetch(PDO::FETCH_ASSOC);
        if(!$old||(int)$old['id_bien']!==$bienId||$old['tipo_documento']!==$tipo)throw new RuntimeException('El documento no corresponde al bien y tipo seleccionados.');
        if(self::tieneEvidencia($pdo,$id,$anterior))throw new RuntimeException('No se puede sustituir un documento que ya tiene evidencia firmada.');
      }
      if($tipo==='OFICIO_AUTORIZACION'&&$estado==='VALIDADO'){$pdo->prepare("UPDATE traspaso SET estado='PENDIENTE_VALIDACION',id_usuario_autoriza=NULL,fecha_autorizacion=NULL WHERE id_traspaso=?")->execute([$id]);self::eventoEn($pdo,$id,$usuarioId,'OFICIO_REEMPLAZADO','VALIDADO','PENDIENTE_VALIDACION','El reemplazo del oficio requiere nueva validación documental.');$estado='PENDIENTE_VALIDACION';}
      $s=$pdo->prepare('SELECT COALESCE(MAX(version),0)+1 FROM traspaso_documento WHERE id_traspaso=? AND tipo_documento=?');$s->execute([$id,$tipo]);$version=(int)$s->fetchColumn();
      $s=$pdo->prepare('INSERT INTO traspaso_documento (id_traspaso,id_bien,tipo_documento,ruta_interna,nombre_original,mime_type,tamano_bytes,sha256,version,id_documento_anterior,id_usuario_carga) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
      $s->execute([$id,$bienId,$tipo,$ruta,$nombre,$mime,$bytes,$hash,$version,$anterior,$usuarioId]);$doc=(int)$pdo->lastInsertId();
      self::eventoEn($pdo,$id,$usuarioId,'CARGA_DOCUMENTO',$estado,$estado,null,['documento'=>$doc,'tipo'=>$tipo,'version'=>$version,'sha256'=>$hash]+($metadata??[]));if($transaccionPropia)$pdo->commit();return $doc;
    }catch(Throwable $e){if($transaccionPropia&&$pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  /** Conserva el original generado y agrega una nueva versión como evidencia firmada. */
  public static function registrarDocumentoFirmado(int $id,int $documentoOrigen,string $ruta,string $nombre,string $mime,int $bytes,string $hash,int $usuarioId):int
  {
    $pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();
    try{
      $s=$pdo->prepare('SELECT estado,id_usuario_creador FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$transfer=$s->fetch(PDO::FETCH_ASSOC);$estado=$transfer['estado']??null;if($estado!=='VALIDADO')throw new RuntimeException('La evidencia documental solo se carga en expedientes validados.');self::assertOwner($transfer,$usuarioId);
      $s=$pdo->prepare('SELECT id_traspaso_documento,id_bien,tipo_documento FROM traspaso_documento WHERE id_traspaso_documento=? AND id_traspaso=? FOR UPDATE');$s->execute([$documentoOrigen,$id]);$origen=$s->fetch(PDO::FETCH_ASSOC);
      if(!$origen||empty($origen['id_bien'])||!in_array($origen['tipo_documento'],['TARJETA_RESGUARDO','RESGUARDO_EQUIPO','BAJA_RESGUARDANTE'],true))throw new RuntimeException('El documento seleccionado no requiere evidencia documental.');
      if(self::documentoVigente($pdo,$id,(int)$origen['id_bien'],(string)$origen['tipo_documento'])!==$documentoOrigen)throw new RuntimeException('El documento seleccionado ya tiene una versión posterior. Actualiza el expediente antes de cargar la evidencia.');
      $s=$pdo->prepare('SELECT COALESCE(MAX(version),0)+1 FROM traspaso_documento WHERE id_traspaso=? AND tipo_documento=?');$s->execute([$id,$origen['tipo_documento']]);$version=(int)$s->fetchColumn();
      $s=$pdo->prepare('INSERT INTO traspaso_documento (id_traspaso,id_bien,tipo_documento,ruta_interna,nombre_original,mime_type,tamano_bytes,sha256,version,id_documento_anterior,id_usuario_carga) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
      $s->execute([$id,$origen['id_bien'],$origen['tipo_documento'],$ruta,$nombre,$mime,$bytes,$hash,$version,$documentoOrigen,$usuarioId]);$doc=(int)$pdo->lastInsertId();
      self::eventoEn($pdo,$id,$usuarioId,'CARGA_DOCUMENTO_FIRMADO',$estado,$estado,null,['documento'=>$doc,'documento_origen'=>$documentoOrigen,'bien'=>(int)$origen['id_bien'],'tipo'=>$origen['tipo_documento'],'version'=>$version,'sha256'=>$hash]);
      $pdo->commit();return $doc;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  public static function generarDocumento(int $id,string $tipo,string $ruta,string $nombre,string $hash,int $bytes,int $usuarioId,?int $bienId=null,?int $documentoAnterior=null,array $formatoMetadata=[],?PDO $pdo=null):int
  {
    if(!in_array($tipo,['TARJETA_RESGUARDO','RESGUARDO_EQUIPO','BAJA_RESGUARDANTE'],true)||!$bienId)throw new InvalidArgumentException('Tipo de documento o bien no válido.');
    $transaccionPropia=$pdo===null;$pdo??=parent::connect(true);if($transaccionPropia){if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();}elseif(!$pdo->inTransaction())throw new RuntimeException('La generación coordinada del documento requiere una transacción activa.');
    try{$s=$pdo->prepare("SELECT estado,id_usuario_creador FROM traspaso WHERE id_traspaso=? FOR UPDATE");$s->execute([$id]);$transfer=$s->fetch(PDO::FETCH_ASSOC);$estado=$transfer['estado']??null;if($estado!=='VALIDADO'&&!($estado==='PENDIENTE_VALIDACION'&&$documentoAnterior===null))throw new RuntimeException('Los documentos iniciales se generan al crear el expediente; su actualización requiere validación administrativa.');self::assertOwner($transfer,$usuarioId);
      $s=$pdo->prepare('SELECT COUNT(*) FROM traspaso_bien WHERE id_traspaso=? AND id_bien=?');$s->execute([$id,$bienId]);if(!(int)$s->fetchColumn())throw new RuntimeException('El bien no pertenece al expediente.');
      $current=self::documentoVigente($pdo,$id,$bienId,$tipo,self::documentoMinimoVigente($pdo,$id));
      if($current!==$documentoAnterior)throw new RuntimeException($current?'El documento ya está generado; actualiza la versión vigente.':'El documento anterior indicado ya no es vigente.');
      if($documentoAnterior){$s=$pdo->prepare('SELECT id_bien,tipo_documento FROM traspaso_documento WHERE id_traspaso_documento=? AND id_traspaso=? FOR UPDATE');$s->execute([$documentoAnterior,$id]);$prior=$s->fetch(PDO::FETCH_ASSOC);if(!$prior||(int)$prior['id_bien']!==$bienId||$prior['tipo_documento']!==$tipo)throw new RuntimeException('La versión anterior no corresponde al bien y tipo de documento.');}
      $s=$pdo->prepare('SELECT COALESCE(MAX(version),0)+1 FROM traspaso_documento WHERE id_traspaso=? AND tipo_documento=?');$s->execute([$id,$tipo]);$version=(int)$s->fetchColumn();
      $s=$pdo->prepare('INSERT INTO traspaso_documento (id_traspaso,id_bien,tipo_documento,ruta_interna,nombre_original,mime_type,tamano_bytes,sha256,version,id_documento_anterior,id_usuario_carga) VALUES (?,?,?,?,?,?,?,?,?,?,?)');$s->execute([$id,$bienId,$tipo,$ruta,$nombre,'application/pdf',$bytes,$hash,$version,$documentoAnterior,$usuarioId]);$doc=(int)$pdo->lastInsertId();
      self::eventoEn($pdo,$id,$usuarioId,'GENERACION_DOCUMENTO',$estado,$estado,null,['documento'=>$doc,'bien'=>$bienId,'tipo'=>$tipo,'version'=>$version]+$formatoMetadata);if($transaccionPropia)$pdo->commit();return $doc;
    }catch(Throwable $e){if($transaccionPropia&&$pdo->inTransaction())$pdo->rollBack();throw $e;}
  }


  public static function documentoPorId(int $id):array
  { $r=parent::query('SELECT d.*,t.estado FROM traspaso_documento d JOIN traspaso t ON t.id_traspaso=d.id_traspaso WHERE d.id_traspaso_documento=:id LIMIT 1',['id'=>$id]);return $r[0]??[]; }

  public static function documentoGeneradoVigente(int $id,int $bienId,string $tipo,?PDO $pdo=null):?int
  { $pdo??=parent::connect(true);$from=self::documentoMinimoVigente($pdo,$id);return self::documentoVigente($pdo,$id,$bienId,$tipo,$from); }

  private static function documentosObligatoriosGenerados(PDO $pdo,int $id):bool
  {
    $from=self::documentoMinimoVigente($pdo,$id);
    $s=$pdo->prepare('SELECT id_bien,id_resguardante_origen FROM traspaso_bien WHERE id_traspaso=?');$s->execute([$id]);$items=$s->fetchAll(PDO::FETCH_ASSOC);if(!$items)return false;
    foreach($items as $item){$bienId=(int)$item['id_bien'];foreach(['TARJETA_RESGUARDO','RESGUARDO_EQUIPO'] as $type)if(!self::documentoVigente($pdo,$id,$bienId,$type,$from))return false;if(!empty($item['id_resguardante_origen'])&&!self::documentoVigente($pdo,$id,$bienId,'BAJA_RESGUARDANTE',$from))return false;}
    return true;
  }

  private static function documentoMinimoVigente(PDO $pdo,int $id):int
  { $s=$pdo->prepare("SELECT metadatos FROM traspaso_evento WHERE id_traspaso=? AND tipo_evento='EDICION_DATOS_DOCUMENTO'");$s->execute([$id]);$from=1;foreach($s->fetchAll(PDO::FETCH_COLUMN) as $json){$meta=json_decode((string)$json,true)?:[];$from=max($from,(int)($meta['documento_desde']??1));}return $from; }

  private static function documentosObligatoriosFirmados(PDO $pdo,int $id):bool
  {
    $from=self::documentoMinimoVigente($pdo,$id);
    $items=$pdo->prepare('SELECT id_bien,id_resguardante_origen FROM traspaso_bien WHERE id_traspaso=?');$items->execute([$id]);$bienRows=$items->fetchAll(PDO::FETCH_ASSOC);if(!$bienRows)return false;
    foreach($bienRows as $bienRow){$bienId=(int)$bienRow['id_bien'];foreach(['TARJETA_RESGUARDO','RESGUARDO_EQUIPO'] as $type){$docId=self::documentoVigente($pdo,$id,$bienId,$type,$from);if(!$docId||!self::evidenciaVigenteValidada($pdo,$id,$docId))return false;}$bajaId=self::documentoVigente($pdo,$id,$bienId,'BAJA_RESGUARDANTE',$from);if(!empty($bienRow['id_resguardante_origen'])&&!$bajaId)return false;if($bajaId&&!self::evidenciaVigenteValidada($pdo,$id,$bajaId))return false;}
    return true;
  }

  /** Devuelve la versión actual excluyendo los archivos que son evidencia firmada. */
  private static function documentoVigente(PDO $pdo,int $id,int $bienId,string $tipo,int $from=1):?int
  {
    $s=$pdo->prepare("SELECT d.id_traspaso_documento FROM traspaso_documento d WHERE d.id_traspaso=? AND d.id_bien=? AND d.tipo_documento=? AND d.id_traspaso_documento>=? AND NOT EXISTS (SELECT 1 FROM traspaso_evento e WHERE e.id_traspaso=d.id_traspaso AND e.tipo_evento='CARGA_DOCUMENTO_FIRMADO' AND JSON_UNQUOTE(JSON_EXTRACT(e.metadatos,'$.documento'))=CAST(d.id_traspaso_documento AS CHAR)) ORDER BY d.id_traspaso_documento DESC LIMIT 1");$s->execute([$id,$bienId,$tipo,$from]);$value=$s->fetchColumn();return $value===false?null:(int)$value;
  }

  private static function tieneEvidencia(PDO $pdo,int $id,int $documento):bool
  { $s=$pdo->prepare("SELECT COUNT(*) FROM traspaso_evento WHERE id_traspaso=? AND tipo_evento='CARGA_DOCUMENTO_FIRMADO' AND JSON_UNQUOTE(JSON_EXTRACT(metadatos,'$.documento_origen'))=CAST(? AS CHAR)");$s->execute([$id,$documento]);return (int)$s->fetchColumn()>0; }

  private static function evidenciaVigente(PDO $pdo,int $id,int $documento):?int
  { $s=$pdo->prepare("SELECT CAST(JSON_UNQUOTE(JSON_EXTRACT(metadatos,'$.documento')) AS UNSIGNED) FROM traspaso_evento WHERE id_traspaso=? AND tipo_evento='CARGA_DOCUMENTO_FIRMADO' AND JSON_UNQUOTE(JSON_EXTRACT(metadatos,'$.documento_origen'))=CAST(? AS CHAR) ORDER BY id_traspaso_evento DESC LIMIT 1");$s->execute([$id,$documento]);$value=$s->fetchColumn();return $value===false||!(int)$value?null:(int)$value; }

  private static function evidenciaTieneDecision(PDO $pdo,int $id,int $documentoOrigen,int $evidenciaId):bool
  { $s=$pdo->prepare("SELECT COUNT(*) FROM traspaso_evento WHERE id_traspaso=? AND tipo_evento IN ('VALIDACION_EVIDENCIA','RECHAZO_EVIDENCIA') AND JSON_UNQUOTE(JSON_EXTRACT(metadatos,'$.documento_origen'))=CAST(? AS CHAR) AND JSON_UNQUOTE(JSON_EXTRACT(metadatos,'$.documento_evidencia'))=CAST(? AS CHAR)");$s->execute([$id,$documentoOrigen,$evidenciaId]);return (int)$s->fetchColumn()>0; }

  private static function evidenciaVigenteValidada(PDO $pdo,int $id,int $documento):bool
  { $current=self::evidenciaVigente($pdo,$id,$documento);if(!$current)return false;$s=$pdo->prepare("SELECT COUNT(*) FROM traspaso_evento WHERE id_traspaso=? AND tipo_evento='VALIDACION_EVIDENCIA' AND JSON_UNQUOTE(JSON_EXTRACT(metadatos,'$.documento_origen'))=CAST(? AS CHAR) AND JSON_UNQUOTE(JSON_EXTRACT(metadatos,'$.documento_evidencia'))=CAST(? AS CHAR)");$s->execute([$id,$documento,$current]);return (int)$s->fetchColumn()>0; }

  public static function completarDocumentado(int $id,int $usuarioId):void
  {
    $pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();
    try{$s=$pdo->prepare('SELECT * FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$t=$s->fetch(PDO::FETCH_ASSOC);if(!$t||$t['estado']!=='VALIDADO')throw new RuntimeException('El expediente no está validado o ya fue completado.');if($t['tipo']==='ENTRE_UNIDADES'){if(empty($t['fecha_autorizacion']))throw new RuntimeException('El oficio externo aún no está validado.');$s=$pdo->prepare("SELECT COUNT(*) FROM traspaso_documento WHERE id_traspaso=? AND tipo_documento='OFICIO_AUTORIZACION'");$s->execute([$id]);if(!(int)$s->fetchColumn())throw new RuntimeException('Falta el oficio externo de autorización.');}if(!self::documentosObligatoriosFirmados($pdo,$id))throw new RuntimeException('Faltan evidencias documentales firmadas y validadas para los documentos obligatorios.');
      self::aplicarCambiosPatrimoniales($pdo,$id,$t,$usuarioId);
      $pdo->prepare("UPDATE traspaso SET estado='COMPLETADO',fecha_completado=NOW() WHERE id_traspaso=? AND estado='VALIDADO'")->execute([$id]);
      self::eventoEn($pdo,$id,$usuarioId,'EJECUCION','VALIDADO','COMPLETADO',null);$pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  private static function aplicarCambiosPatrimoniales(PDO $pdo,int $id,array $t,int $usuarioId):void
  {
    $rid=(int)$t['id_resguardante_destino'];$s=$pdo->prepare('SELECT id_resguardante,id_unidad,activo FROM resguardante WHERE id_resguardante=? FOR UPDATE');$s->execute([$rid]);$receiver=$s->fetch(PDO::FETCH_ASSOC);if(!$receiver||(int)$receiver['activo']!==1||(int)$receiver['id_unidad']!==(int)$t['id_unidad_destino'])throw new RuntimeException('El nuevo resguardante ya no es válido para la unidad destino.');
    $unitCheck=$pdo->prepare('SELECT id_unidad,activo FROM unidad_administrativa WHERE id_unidad=? FOR UPDATE');$unitCheck->execute([$t['id_unidad_origen']]);$sourceUnit=$unitCheck->fetch(PDO::FETCH_ASSOC);if(!$sourceUnit||(int)$sourceUnit['activo']!==1)throw new RuntimeException('La unidad de origen está inactiva o ya no existe.');
    $unitCheck->execute([$t['id_unidad_destino']]);$destUnit=$unitCheck->fetch(PDO::FETCH_ASSOC);if(!$destUnit||(int)$destUnit['activo']!==1)throw new RuntimeException('La unidad destino está inactiva o ya no existe.');
    $lines=$pdo->prepare('SELECT tb.*,b.id_unidad,b.id_ubicacion,b.activo FROM traspaso_bien tb JOIN bien b ON b.id_bien=tb.id_bien WHERE tb.id_traspaso=? ORDER BY tb.id_bien FOR UPDATE');$lines->execute([$id]);$items=$lines->fetchAll(PDO::FETCH_ASSOC);if(!$items)throw new RuntimeException('El expediente no contiene bienes.');
    foreach($items as $item){$lock=$pdo->prepare('SELECT x.id_traspaso_bien FROM traspaso_bien x JOIN traspaso other ON other.id_traspaso=x.id_traspaso WHERE x.id_bien=? AND x.id_traspaso<>? AND other.estado IN '.self::ESTADOS_ACTIVOS.' AND other.fecha_cancelacion IS NULL FOR UPDATE');$lock->execute([$item['id_bien'],$id]);if($lock->fetchColumn())throw new RuntimeException('El bien está involucrado en otro traspaso activo.');$snapshot=json_decode((string)($item['origen_snapshot']??''),true)?:[];$bienUnidadSnapshot=(int)($snapshot['unidad']['id']??0);if((int)$item['activo']!==1||($bienUnidadSnapshot>0&&(int)$item['id_unidad']!==$bienUnidadSnapshot))throw new RuntimeException('Un bien cambió de estado o unidad propietaria desde que se creó el expediente.');
      $s=$pdo->prepare('SELECT id_resguardo,id_resguardante FROM resguardo WHERE id_bien=? AND activo=1 FOR UPDATE');$s->execute([$item['id_bien']]);$current=$s->fetch(PDO::FETCH_ASSOC);if(!$current||(int)$current['id_resguardo']!==(int)$item['id_resguardo_origen']||(int)$current['id_resguardante']!==(int)$item['id_resguardante_origen'])throw new RuntimeException('La custodia de un bien cambió desde la creación del expediente.');
      $sourceKeeperCheck=$pdo->prepare('SELECT activo FROM resguardante WHERE id_resguardante=? FOR UPDATE');$sourceKeeperCheck->execute([$item['id_resguardante_origen']]);$sourceKeeper=$sourceKeeperCheck->fetch(PDO::FETCH_ASSOC);if(!$sourceKeeper||(int)$sourceKeeper['activo']!==1)throw new RuntimeException('El resguardante de origen ya no está activo.');
      $location=$item['ubicacion_sin_cambio']?($item['id_ubicacion']??null):$item['id_ubicacion_destino'];if(!$item['ubicacion_sin_cambio']&&!empty($location)){$locationCheck=$pdo->prepare('SELECT id_ubicacion FROM ubicacion WHERE id_ubicacion=? FOR UPDATE');$locationCheck->execute([$location]);if(!$locationCheck->fetchColumn())throw new RuntimeException('La ubicación destino ya no existe.');}
      $bajaDocumento=self::documentoVigente($pdo,$id,(int)$item['id_bien'],'BAJA_RESGUARDANTE',self::documentoMinimoVigente($pdo,$id));if(!$bajaDocumento)throw new RuntimeException('No existe una baja de resguardante vigente para completar el traspaso.');
      ResguardoModel::registrarBajaPorTraspasoEn($pdo,(int)$item['id_bien'],(int)$current['id_resguardo'],$id,$bajaDocumento,$usuarioId,date('Y-m-d'),trim((string)$t['motivo'])?:'Traspaso de bienes',trim((string)$t['observaciones'])?:null);
      $old=ResguardoModel::contextoBien((int)$item['id_bien']);$pdo->prepare('UPDATE bien SET id_unidad=?,id_ubicacion=? WHERE id_bien=?')->execute([(int)$t['id_unidad_destino'],$location,$item['id_bien']]);
      $change=ResguardoModel::cambiarCustodia((int)$item['id_bien'],$rid,['fecha_asignacion'=>date('Y-m-d')],'Traspaso '.$t['folio'],trim((string)$t['motivo'])?:'Traspaso de bienes',trim((string)$t['observaciones'])?:null,['id'=>$usuarioId],$pdo,$old);
      $pdo->prepare('UPDATE traspaso_bien SET id_resguardo_destino=?,id_movimiento=? WHERE id_traspaso_bien=?')->execute([$change['resguardo_nuevo'],$change['id_movimiento'],$item['id_traspaso_bien']]);
    }
  }

  public static function crearReversion(int $id,int $usuarioId,string $motivo):int
  {
    if(trim($motivo)==='')throw new InvalidArgumentException('Indica el motivo de la reversión.');
    $pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();
    try{$s=$pdo->prepare('SELECT * FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$original=$s->fetch(PDO::FETCH_ASSOC);if(!$original||$original['estado']!=='COMPLETADO')throw new RuntimeException('Solo puede solicitarse reversión de un traspaso completado.');
      $s=$pdo->prepare('SELECT tb.*,b.id_unidad,b.id_ubicacion,b.activo,rg.id_resguardo AS vigente_id,rg.id_resguardante AS vigente_resguardante FROM traspaso_bien tb JOIN bien b ON b.id_bien=tb.id_bien LEFT JOIN resguardo rg ON rg.id_bien=b.id_bien AND rg.activo=1 WHERE tb.id_traspaso=? ORDER BY tb.id_bien FOR UPDATE');$s->execute([$id]);$lines=$s->fetchAll(PDO::FETCH_ASSOC);if(!$lines)throw new RuntimeException('El traspaso original no contiene bienes.');
      $origenes=array_values(array_unique(array_map('intval',array_column($lines,'id_resguardante_origen'))));
      if(count($origenes)!==1)throw new RuntimeException('No es posible crear una reversión única: los bienes originales pertenecían a distintos resguardantes.');
      $s=$pdo->prepare('SELECT id_unidad,activo FROM resguardante WHERE id_resguardante=? FOR UPDATE');$s->execute([$origenes[0]]);$previousKeeper=$s->fetch(PDO::FETCH_ASSOC);
      if(!$previousKeeper||(int)$previousKeeper['activo']!==1||(int)$previousKeeper['id_unidad']!==(int)$original['id_unidad_origen'])throw new RuntimeException('No es posible revertir automáticamente: el resguardante original ya no está activo en la unidad origen.');
      foreach($lines as $line){if((int)$line['activo']!==1||(int)$line['id_unidad']!==(int)$original['id_unidad_destino']||(int)$line['vigente_resguardante']!==(int)$original['id_resguardante_destino'])throw new RuntimeException('No es posible revertir de forma segura: un bien cambió de estado, unidad o resguardante después del traspaso.');$s=$pdo->prepare('SELECT COUNT(*) FROM traspaso_bien tb JOIN traspaso t ON t.id_traspaso=tb.id_traspaso WHERE tb.id_bien=? AND t.estado IN '.self::ESTADOS_ACTIVOS.' AND t.fecha_cancelacion IS NULL');$s->execute([$line['id_bien']]);if((int)$s->fetchColumn()>0)throw new RuntimeException('No es posible revertir: uno de los bienes tiene otro traspaso activo.');$s=$pdo->prepare('SELECT COUNT(*) FROM movimiento_bien WHERE id_bien=? AND id_movimiento>?');$s->execute([$line['id_bien'],$line['id_movimiento']]);if((int)$s->fetchColumn()>0)throw new RuntimeException('No es posible revertir: existe un movimiento posterior para uno de los bienes.');}
      $type=(int)$original['id_unidad_origen']===(int)$original['id_unidad_destino']?'MISMA_UNIDAD':'ENTRE_UNIDADES';
      $sql="INSERT INTO traspaso (folio,tipo,estado,id_unidad_origen,id_unidad_destino,id_resguardante_destino,id_usuario_creador,id_traspaso_revertido,id_usuario_reversion,fecha_reversion,motivo,observaciones) VALUES ('PENDIENTE',?,'PENDIENTE_VALIDACION',?,?,?,?,?,?,NOW(),?,?)";
      $s=$pdo->prepare($sql);$s->execute([$type,$original['id_unidad_destino'],$original['id_unidad_origen'],$lines[0]['id_resguardante_origen'],$usuarioId,$id,$usuarioId,trim($motivo),'Reversión compensatoria de '.$original['folio']]);$newId=(int)$pdo->lastInsertId();$folio=sprintf('TR-%06d',$newId);$pdo->prepare('UPDATE traspaso SET folio=? WHERE id_traspaso=?')->execute([$folio,$newId]);
      $s=$pdo->prepare('INSERT INTO traspaso_bien (id_traspaso,id_bien,id_resguardante_origen,id_resguardo_origen,id_ubicacion_destino,ubicacion_sin_cambio,origen_snapshot) VALUES (?,?,?,?,?,?,?)');
      foreach($lines as $line){$originalSnapshot=json_decode((string)$line['origen_snapshot'],true)?:[];$restoreLocation=$originalSnapshot['ubicacion_id']??null;$noChange=(int)($restoreLocation??0)===(int)($line['id_ubicacion']??0);$snap=['reversion_de'=>$original['folio'],'resguardo_origen'=>(int)$line['vigente_id'],'resguardante_actual'=>(int)$line['vigente_resguardante'],'unidad'=>['id'=>(int)$line['id_unidad']],'reversion_ubicacion'=>$restoreLocation];$s->execute([$newId,$line['id_bien'],$line['vigente_resguardante'],$line['vigente_id'],$noChange?null:$restoreLocation,$noChange?1:0,json_encode($snap,JSON_THROW_ON_ERROR)]);}
      self::eventoEn($pdo,$newId,$usuarioId,'CREACION_REVERSIÓN',null,'PENDIENTE_VALIDACION',$motivo,['traspaso_original'=>$id]);self::eventoEn($pdo,$id,$usuarioId,'REVERSIÓN_SOLICITADA','COMPLETADO','COMPLETADO',$motivo,['traspaso_compensatorio'=>$newId]);$pdo->commit();return $newId;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  private static function transicion(int $id,int $uid,string $estado,callable $fn):void
  { self::transicionAny($id,$uid,[$estado],$fn); }
  private static function transicionAny(int $id,int $uid,array $estados,callable $fn):void
  { $pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();try{$s=$pdo->prepare('SELECT * FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$t=$s->fetch(PDO::FETCH_ASSOC);if(!$t||!in_array($t['estado'],$estados,true))throw new RuntimeException('El expediente cambió o ya no permite esta acción.');$t['_actor_role']=(string)(get_user('rol')?:'');$fn($pdo,$t);$pdo->commit();}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;} }

  private static function assertOwner(array $transfer,int $usuarioId):void
  { if((int)($transfer['id_usuario_creador']??0)!==$usuarioId&&(string)(get_user('rol')?:'')!=='admin')throw new RuntimeException('Solo quien creó el expediente o un administrador puede modificarlo.'); }

  private static function eventoEn(PDO $pdo,int $id,int $uid,string $tipo,?string $antes,?string $despues,?string $motivo=null,?array $meta=null):void
  { $s=$pdo->prepare('INSERT INTO traspaso_evento (id_traspaso,id_usuario,tipo_evento,estado_anterior,estado_nuevo,motivo,metadatos) VALUES (?,?,?,?,?,?,?)');$s->execute([$id,$uid?:null,$tipo,$antes,$despues,$motivo,$meta?json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR):null]); }
}
