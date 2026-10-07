<?php

/** Operaciones transaccionales de expedientes de traspaso. */
class TraspasoModel extends Model
{
  private const ESTADOS_ACTIVOS = "('CREADO','PENDIENTE_AUTORIZACION','AUTORIZADO','PENDIENTE_ENTREGA','ENTREGA_CONFIRMADA','PENDIENTE_RECEPCION','DOCUMENTOS','FIRMA')";

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

  public static function detalle(int $id): array
  {
    $rows = parent::query("SELECT t.*, uo.nombre AS unidad_origen_nombre, uo.codigo_ua AS unidad_origen_codigo,
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
    $t['bienes'] = parent::query("SELECT tb.*, b.clave_interna, b.numero_inventario, b.nic_cea, b.nombre_bien, b.numero_serie,
      b.id_unidad AS unidad_actual, b.id_ubicacion AS ubicacion_actual,
      CONCAT_WS(' ', ro.nombre, ro.apellido_paterno, NULLIF(ro.apellido_materno,'')) AS resguardante_origen_nombre,
      ro.csp AS resguardante_origen_csp, udest.municipio AS municipio_destino, udest.localidad AS localidad_destino
      FROM traspaso_bien tb JOIN bien b ON b.id_bien=tb.id_bien
      JOIN resguardante ro ON ro.id_resguardante=tb.id_resguardante_origen
      LEFT JOIN ubicacion udest ON udest.id_ubicacion=tb.id_ubicacion_destino
      WHERE tb.id_traspaso=:id ORDER BY tb.id_traspaso_bien", ['id' => $id]) ?: [];
    foreach (['documentos' => 'SELECT d.*, u.username AS usuario_carga FROM traspaso_documento d LEFT JOIN bee_users u ON u.id=d.id_usuario_carga WHERE d.id_traspaso=:id ORDER BY d.fecha_carga DESC, d.id_traspaso_documento DESC',
      'eventos' => 'SELECT e.*, u.username FROM traspaso_evento e LEFT JOIN bee_users u ON u.id=e.id_usuario WHERE e.id_traspaso=:id ORDER BY e.id_traspaso_evento ASC'] as $key => $sql) {
      $t[$key] = parent::query($sql, ['id' => $id]) ?: [];
      if ($key === 'eventos') foreach ($t[$key] as &$event) $event['metadatos'] = $event['metadatos'] ? json_decode($event['metadatos'], true) : [];
      unset($event);
    }
    return $t;
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
      WHERE b.activo=1 AND NOT EXISTS (SELECT 1 FROM traspaso_bien x JOIN traspaso t ON t.id_traspaso=x.id_traspaso
        WHERE x.id_bien=b.id_bien AND t.estado IN " . self::ESTADOS_ACTIVOS . ")";
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

  public static function crear(array $datos, array $idsBien, int $usuarioId): int
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
    $pdo = parent::connect(true);
    if ($pdo->inTransaction()) $pdo->commit();
    $pdo->beginTransaction();
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
      if (!$actualOrigen || (int)$actualOrigen['activo'] !== 1 || (int)$actualOrigen['id_unidad'] !== $origenId) throw new RuntimeException('El resguardante de origen debe estar activo y pertenecer a la unidad de origen.');
      if ($tipo === 'MISMA_UNIDAD' && (int)$nuevo['id_resguardante'] === $resguardanteOrigen) throw new RuntimeException('Selecciona un resguardante distinto al origen.');
      $marks = implode(',', array_fill(0, count($idsBien), '?'));
      $s = $pdo->prepare("SELECT id_bien FROM bien WHERE id_bien IN ($marks) ORDER BY id_bien FOR UPDATE"); $s->execute($idsBien);
      if (count($s->fetchAll(PDO::FETCH_COLUMN)) !== count($idsBien)) throw new RuntimeException('Uno o más bienes no existen.');
      $s = $pdo->prepare("SELECT b.*,rg.id_resguardo,rg.id_resguardante,r.csp AS csp_resguardante,
        CONCAT_WS(' ',r.nombre,r.apellido_paterno,NULLIF(r.apellido_materno,'')) AS nombre_resguardante,
        r.id_unidad AS unidad_resguardante,
        r.activo AS resguardante_activo,ua.nombre AS nombre_unidad,ua.codigo_ua,ub.municipio,ub.localidad
        FROM bien b JOIN resguardo rg ON rg.id_bien=b.id_bien AND rg.activo=1
        JOIN resguardante r ON r.id_resguardante=rg.id_resguardante JOIN unidad_administrativa ua ON ua.id_unidad=b.id_unidad
        LEFT JOIN ubicacion ub ON ub.id_ubicacion=b.id_ubicacion WHERE b.id_bien IN ($marks) ORDER BY b.id_bien FOR UPDATE");
      $s->execute($idsBien); $bienes = $s->fetchAll(PDO::FETCH_ASSOC);
      if (count($bienes) !== count($idsBien)) throw new RuntimeException('Cada bien debe tener exactamente una custodia vigente.');
      foreach ($bienes as $bien) {
        if ((int)$bien['activo'] !== 1 || (int)$bien['resguardante_activo'] !== 1 || (int)$bien['id_unidad'] !== $origenId || (int)$bien['id_resguardante'] !== $resguardanteOrigen) throw new RuntimeException('Cada bien debe pertenecer al resguardante origen seleccionado y a la unidad de origen.');
        if ((int)$bien['id_resguardante'] === $resguardanteDestino) throw new RuntimeException('El nuevo resguardante ya tiene la custodia de uno de los bienes seleccionados.');
      }
      $s = $pdo->prepare("SELECT COUNT(*) FROM traspaso_bien tb JOIN traspaso t ON t.id_traspaso=tb.id_traspaso WHERE tb.id_bien IN ($marks) AND t.estado IN " . self::ESTADOS_ACTIVOS);
      $s->execute($idsBien);
      if ((int)$s->fetchColumn() > 0) throw new RuntimeException('Uno o más bienes ya pertenecen a un traspaso activo.');

      $estadoInicial = $tipo === 'MISMA_UNIDAD' ? 'DOCUMENTOS' : 'PENDIENTE_AUTORIZACION';
      $insert = $pdo->prepare("INSERT INTO traspaso (folio,tipo,estado,id_unidad_origen,id_unidad_destino,id_resguardante_destino,id_usuario_creador,motivo,observaciones)
        VALUES ('PENDIENTE',?,?, ?,?,?,?,?,?)");
      $insert->execute([$tipo,$estadoInicial,$origenId,$destinoId,$resguardanteDestino,$usuarioId,trim((string)($datos['motivo']??'')) ?: null,trim((string)($datos['observaciones']??'')) ?: null]);
      $id = (int)$pdo->lastInsertId(); $folio = sprintf('TR-%06d',$id);
      $pdo->prepare('UPDATE traspaso SET folio=? WHERE id_traspaso=?')->execute([$folio,$id]);
      $line = $pdo->prepare('INSERT INTO traspaso_bien (id_traspaso,id_bien,id_resguardante_origen,id_resguardo_origen,id_ubicacion_destino,ubicacion_sin_cambio,origen_snapshot) VALUES (?,?,?,?,?,?,?)');
      foreach ($bienes as $bien) {
        $snapshot = [
          'unidad' => ['id' => (int)$bien['id_unidad'], 'nombre' => $bien['nombre_unidad'], 'codigo_ua' => $bien['codigo_ua']],
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
      $pdo->commit(); return $id;
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
  }

  public static function actualizarBorrador(int $id, array $datos, array $idsBien, int $usuarioId): void
  {
    $actual = self::detalle($id);
    if (!$actual || $actual['estado'] !== 'CREADO') throw new RuntimeException('Solo se puede editar un expediente en estado CREADO.');
    if (($datos['tipo'] ?? '') !== $actual['tipo'] || (int)($datos['id_unidad_origen']??0)!==(int)$actual['id_unidad_origen'] || (int)($datos['id_unidad_destino']??0)!==(int)$actual['id_unidad_destino'] || (int)($datos['id_resguardante_destino']??0)!==(int)$actual['id_resguardante_destino'] || array_map('intval',$idsBien)!==array_map(static fn($r)=>(int)$r['id_bien'],$actual['bienes'])) throw new RuntimeException('Para cambiar bienes, unidades o resguardante, cancela el borrador y crea un expediente nuevo.');
    $pdo=parent::connect(true); if($pdo->inTransaction())$pdo->commit(); $pdo->beginTransaction();
    try { $s=$pdo->prepare("SELECT estado FROM traspaso WHERE id_traspaso=? FOR UPDATE");$s->execute([$id]);if($s->fetchColumn()!=='CREADO')throw new RuntimeException('El expediente cambió de estado; vuelve a cargarlo.');
      $s=$pdo->prepare('UPDATE traspaso SET motivo=?,observaciones=? WHERE id_traspaso=?');$s->execute([trim((string)($datos['motivo']??''))?:null,trim((string)($datos['observaciones']??''))?:null,$id]);
      self::eventoEn($pdo,$id,$usuarioId,'MODIFICACION','CREADO','CREADO',null,['antes'=>['motivo'=>$actual['motivo'],'observaciones'=>$actual['observaciones']],'despues'=>['motivo'=>$datos['motivo']??null,'observaciones'=>$datos['observaciones']??null]]);$pdo->commit();
    } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  public static function actualizarDatosDocumento(int $id,string $motivo,string $observaciones,int $usuarioId):void
  {
    $motivo=trim($motivo);$observaciones=trim($observaciones);if(mb_strlen($motivo)>250||mb_strlen($observaciones)>1000)throw new InvalidArgumentException('El motivo o las observaciones exceden la longitud permitida.');
    $pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();
    try{$s=$pdo->prepare('SELECT estado,motivo,observaciones FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$current=$s->fetch(PDO::FETCH_ASSOC);if(!$current||!in_array($current['estado'],['DOCUMENTOS','FIRMA'],true))throw new RuntimeException('Los datos solo pueden editarse antes de completar el expediente.');
      $s=$pdo->prepare("SELECT COUNT(*) FROM traspaso_evento WHERE id_traspaso=? AND tipo_evento IN ('FIRMA_DOCUMENTO','CARGA_DOCUMENTO_FIRMADO')");$s->execute([$id]);if((int)$s->fetchColumn()>0)throw new RuntimeException('Ya se cargó evidencia firmada. No se pueden editar los datos firmados.');
      $s=$pdo->prepare('SELECT COALESCE(MAX(id_traspaso_documento),0)+1 FROM traspaso_documento WHERE id_traspaso=?');$s->execute([$id]);$from=(int)$s->fetchColumn();
      $pdo->prepare("UPDATE traspaso SET motivo=?,observaciones=?,estado='DOCUMENTOS' WHERE id_traspaso=?")->execute([$motivo?:null,$observaciones?:null,$id]);
      self::eventoEn($pdo,$id,$usuarioId,'EDICION_DATOS_DOCUMENTO',$current['estado'],'DOCUMENTOS',null,['documento_desde'=>$from,'antes'=>['motivo'=>$current['motivo'],'observaciones'=>$current['observaciones']],'despues'=>['motivo'=>$motivo,'observaciones'=>$observaciones]]);$pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  public static function enviar(int $id, int $usuarioId): void
  {
    self::transicionAny($id,$usuarioId,['CREADO','AUTORIZADO'],static function(PDO $pdo,array $t) use($id,$usuarioId){
      if($t['estado']==='AUTORIZADO'){$next='PENDIENTE_ENTREGA';}
      elseif($t['tipo']==='ENTRE_UNIDADES'){$s=$pdo->prepare("SELECT COUNT(*) FROM traspaso_documento WHERE id_traspaso=? AND tipo_documento='OFICIO_AUTORIZACION'");$s->execute([$id]);if((int)$s->fetchColumn()<1)throw new RuntimeException('Carga el oficio PDF antes de solicitar autorización.');$next='PENDIENTE_AUTORIZACION';}
      else $next='PENDIENTE_ENTREGA';
      $pdo->prepare('UPDATE traspaso SET estado=? WHERE id_traspaso=?')->execute([$next,$id]);self::eventoEn($pdo,$id,$usuarioId,'ENVIO',$t['estado'],$next,null);
    });
  }

  public static function autorizar(int $id,int $usuarioId):void
  { self::transicion($id,$usuarioId,'PENDIENTE_AUTORIZACION',static function(PDO $pdo,array $t)use($id,$usuarioId){$s=$pdo->prepare("SELECT COUNT(*) FROM traspaso_documento WHERE id_traspaso=? AND tipo_documento='OFICIO_AUTORIZACION'");$s->execute([$id]);if(!(int)$s->fetchColumn())throw new RuntimeException('El expediente no conserva un oficio.');$s=$pdo->prepare("SELECT metadatos FROM traspaso_evento WHERE id_traspaso=? AND tipo_evento='CREACION' ORDER BY id_traspaso_evento LIMIT 1");$s->execute([$id]);$meta=json_decode((string)$s->fetchColumn(),true)?:[];$nuevoFlujo=array_key_exists('resguardante_origen',$meta);$next=$nuevoFlujo?'DOCUMENTOS':'AUTORIZADO';$pdo->prepare("UPDATE traspaso SET estado=?,id_usuario_autoriza=?,fecha_autorizacion=NOW() WHERE id_traspaso=?")->execute([$next,$usuarioId,$id]);self::eventoEn($pdo,$id,$usuarioId,'AUTORIZACION','PENDIENTE_AUTORIZACION',$next,'Oficio revisado y traspaso autorizado.');}); }

  public static function rechazar(int $id,int $usuarioId,string $motivo):void
  { if(trim($motivo)==='')throw new InvalidArgumentException('Indica el motivo del rechazo.');self::transicion($id,$usuarioId,'PENDIENTE_AUTORIZACION',static function(PDO $pdo)use($id,$usuarioId,$motivo){$pdo->prepare("UPDATE traspaso SET estado='RECHAZADO',id_usuario_rechaza=?,fecha_rechazo=NOW(),motivo_rechazo=? WHERE id_traspaso=?")->execute([$usuarioId,trim($motivo),$id]);self::eventoEn($pdo,$id,$usuarioId,'RECHAZO','PENDIENTE_AUTORIZACION','RECHAZADO',$motivo);}); }

  public static function cancelar(int $id,int $usuarioId,string $motivo=''):void
  { self::transicionAny($id,$usuarioId,['CREADO','PENDIENTE_AUTORIZACION','AUTORIZADO','PENDIENTE_ENTREGA','ENTREGA_CONFIRMADA','PENDIENTE_RECEPCION','DOCUMENTOS','FIRMA'],static function(PDO $pdo,array $t)use($id,$usuarioId,$motivo){$pdo->prepare("UPDATE traspaso SET estado='CANCELADO',id_usuario_cancela=?,fecha_cancelacion=NOW(),motivo_cancelacion=? WHERE id_traspaso=?")->execute([$usuarioId,trim($motivo)?:null,$id]);self::eventoEn($pdo,$id,$usuarioId,'CANCELACION',$t['estado'],'CANCELADO',$motivo?:null);}); }

  public static function confirmarEntrega(int $id,int $usuarioId,string $tipoEntrega):void
  {
    if(!in_array($tipoEntrega,['RESGUARDANTE_ACTUAL','ADMINISTRADOR'],true))throw new InvalidArgumentException('Selecciona quién realiza la entrega.');
    self::transicion($id,$usuarioId,'PENDIENTE_ENTREGA',static function(PDO $pdo,array $t)use($id,$usuarioId,$tipoEntrega){
      if($t['tipo']==='ENTRE_UNIDADES'&&empty($t['fecha_autorizacion']))throw new RuntimeException('El traspaso requiere autorización previa.');
      $s=$pdo->prepare('SELECT DISTINCT id_resguardante_origen FROM traspaso_bien WHERE id_traspaso=?');$s->execute([$id]);$origins=array_map('intval',$s->fetchAll(PDO::FETCH_COLUMN));
      if($tipoEntrega==='RESGUARDANTE_ACTUAL'&&count($origins)!==1)throw new RuntimeException('Los bienes tienen resguardantes de origen distintos; registra la entrega como Administrador.');
      if($tipoEntrega==='ADMINISTRADOR'&&($t['_actor_role']??'')!=='admin')throw new RuntimeException('Solo el Administrador puede registrar la entrega en calidad de Administrador.');
      $personId=$tipoEntrega==='RESGUARDANTE_ACTUAL'?$origins[0]:null;
      $pdo->prepare("UPDATE traspaso SET tipo_entrega=?,id_resguardante_entrega=?,id_usuario_entrega_persona=?,id_usuario_confirma_entrega=?,fecha_entrega=NOW(),estado='PENDIENTE_RECEPCION' WHERE id_traspaso=?")
        ->execute([$tipoEntrega,$personId,$tipoEntrega==='ADMINISTRADOR'?$usuarioId:null,$usuarioId,$id]);
      self::eventoEn($pdo,$id,$usuarioId,'ENTREGA_CONFIRMADA','PENDIENTE_ENTREGA','ENTREGA_CONFIRMADA',null,['tipo_entrega'=>$tipoEntrega,'resguardante'=>$personId]);
      self::eventoEn($pdo,$id,$usuarioId,'AVANCE','ENTREGA_CONFIRMADA','PENDIENTE_RECEPCION');
    });
  }

  /** Confirma recepción y ejecuta todos los cambios patrimoniales en la misma transacción. */
  public static function confirmarRecepcion(int $id,int $usuarioId):void
  {
    $pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();
    try{
      $s=$pdo->prepare('SELECT * FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$t=$s->fetch(PDO::FETCH_ASSOC);
      if(!$t||$t['estado']!=='PENDIENTE_RECEPCION')throw new RuntimeException('El expediente no está pendiente de recepción.');
      $rid=(int)$t['id_resguardante_destino'];$s=$pdo->prepare('SELECT id_resguardante,id_unidad,activo FROM resguardante WHERE id_resguardante=? FOR UPDATE');$s->execute([$rid]);$receiver=$s->fetch(PDO::FETCH_ASSOC);
      if(!$receiver||(int)$receiver['activo']!==1||(int)$receiver['id_unidad']!==(int)$t['id_unidad_destino'])throw new RuntimeException('El nuevo resguardante ya no es válido para la unidad destino.');
      $unitCheck=$pdo->prepare('SELECT id_unidad,activo FROM unidad_administrativa WHERE id_unidad=? FOR UPDATE');$unitCheck->execute([$t['id_unidad_origen']]);$sourceUnit=$unitCheck->fetch(PDO::FETCH_ASSOC);if(!$sourceUnit||(int)$sourceUnit['activo']!==1)throw new RuntimeException('La unidad de origen está inactiva o ya no existe.');
      $lines=$pdo->prepare('SELECT tb.*,b.id_unidad,b.id_ubicacion,b.activo,b.clave_interna,b.numero_inventario FROM traspaso_bien tb JOIN bien b ON b.id_bien=tb.id_bien WHERE tb.id_traspaso=? ORDER BY tb.id_bien FOR UPDATE');$lines->execute([$id]);$items=$lines->fetchAll(PDO::FETCH_ASSOC);
      if(!$items)throw new RuntimeException('El expediente no contiene bienes.');
      foreach($items as $item){
        if((int)$item['activo']!==1)throw new RuntimeException('Uno de los bienes está inactivo.');
        if((int)$item['id_unidad']!==(int)$t['id_unidad_origen'])throw new RuntimeException('La unidad de uno de los bienes cambió desde la creación.');
        $s=$pdo->prepare('SELECT id_resguardo,id_resguardante FROM resguardo WHERE id_bien=? AND activo=1 FOR UPDATE');$s->execute([$item['id_bien']]);$current=$s->fetch(PDO::FETCH_ASSOC);
        if(!$current||(int)$current['id_resguardo']!==(int)$item['id_resguardo_origen']||(int)$current['id_resguardante']!==(int)$item['id_resguardante_origen'])throw new RuntimeException('La custodia actual de un bien cambió desde la creación.');
        $sourceKeeperCheck=$pdo->prepare('SELECT activo FROM resguardante WHERE id_resguardante=? FOR UPDATE');$sourceKeeperCheck->execute([$item['id_resguardante_origen']]);$sourceKeeper=$sourceKeeperCheck->fetch(PDO::FETCH_ASSOC);if(!$sourceKeeper||(int)$sourceKeeper['activo']!==1)throw new RuntimeException('El resguardante de origen está inactivo o ya no existe.');
        if(!$item['ubicacion_sin_cambio']&&!empty($item['id_ubicacion_destino'])){$locationCheck=$pdo->prepare('SELECT id_ubicacion FROM ubicacion WHERE id_ubicacion=? FOR UPDATE');$locationCheck->execute([$item['id_ubicacion_destino']]);if(!$locationCheck->fetchColumn())throw new RuntimeException('La ubicación destino ya no existe.');}
        $old=ResguardoModel::contextoBien((int)$item['id_bien']);
        $location=$item['ubicacion_sin_cambio']?($item['id_ubicacion']??null):$item['id_ubicacion_destino'];
        $pdo->prepare('UPDATE bien SET id_unidad=?,id_ubicacion=? WHERE id_bien=?')->execute([(int)$t['id_unidad_destino'],$location,$item['id_bien']]);
        $change=ResguardoModel::cambiarCustodia((int)$item['id_bien'],$rid,['fecha_asignacion'=>date('Y-m-d')],'Traspaso '.$t['folio'],trim((string)$t['motivo'])?:'Traspaso de bienes',trim((string)$t['observaciones'])?:null,['id'=>$usuarioId],$pdo,$old);
        $pdo->prepare('UPDATE traspaso_bien SET id_resguardo_destino=?,id_movimiento=? WHERE id_traspaso_bien=?')->execute([$change['resguardo_nuevo'],$change['id_movimiento'],$item['id_traspaso_bien']]);
      }
      $pdo->prepare("UPDATE traspaso SET estado='COMPLETADO',id_usuario_confirma_recepcion=?,fecha_recepcion=NOW(),fecha_completado=NOW() WHERE id_traspaso=?")->execute([$usuarioId,$id]);
      self::eventoEn($pdo,$id,$usuarioId,'RECEPCION_CONFIRMADA','PENDIENTE_RECEPCION','RECEPCION_CONFIRMADA',null,['resguardante_receptor'=>$rid]);
      self::eventoEn($pdo,$id,$usuarioId,'EJECUCION','RECEPCION_CONFIRMADA','COMPLETADO',null,['bienes'=>array_column($items,'id_bien')]);
      $pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  public static function registrarDocumento(int $id,string $tipo,string $ruta,string $nombre,string $mime,int $bytes,string $hash,int $usuarioId,?int $anterior=null,?int $bienId=null,?array $metadata=null):int
  {
    $pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();
    try{$s=$pdo->prepare('SELECT estado FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$estado=$s->fetchColumn();if(!$estado)throw new RuntimeException('No existe el expediente.');
      if($tipo==='OFICIO_AUTORIZACION'&&!in_array($estado,['CREADO','PENDIENTE_AUTORIZACION'],true))throw new RuntimeException('El oficio solo puede cargarse antes de la autorización.');
      $s=$pdo->prepare('SELECT COALESCE(MAX(version),0)+1 FROM traspaso_documento WHERE id_traspaso=? AND tipo_documento=?');$s->execute([$id,$tipo]);$version=(int)$s->fetchColumn();
      $s=$pdo->prepare('INSERT INTO traspaso_documento (id_traspaso,id_bien,tipo_documento,ruta_interna,nombre_original,mime_type,tamano_bytes,sha256,version,id_documento_anterior,id_usuario_carga) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
      $s->execute([$id,$bienId,$tipo,$ruta,$nombre,$mime,$bytes,$hash,$version,$anterior,$usuarioId]);$doc=(int)$pdo->lastInsertId();
      self::eventoEn($pdo,$id,$usuarioId,'CARGA_DOCUMENTO',$estado,$estado,null,['documento'=>$doc,'tipo'=>$tipo,'version'=>$version,'sha256'=>$hash]+($metadata??[]));$pdo->commit();return $doc;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  /** Conserva el original generado y agrega una nueva versión como evidencia firmada. */
  public static function registrarDocumentoFirmado(int $id,int $documentoOrigen,string $ruta,string $nombre,string $mime,int $bytes,string $hash,int $usuarioId):int
  {
    $pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();
    try{
      $s=$pdo->prepare('SELECT estado FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$estado=$s->fetchColumn();if($estado!=='FIRMA')throw new RuntimeException('La evidencia firmada solo puede cargarse cuando el expediente está pendiente de documentos firmados.');
      $s=$pdo->prepare('SELECT id_traspaso_documento,id_bien,tipo_documento FROM traspaso_documento WHERE id_traspaso_documento=? AND id_traspaso=? FOR UPDATE');$s->execute([$documentoOrigen,$id]);$origen=$s->fetch(PDO::FETCH_ASSOC);
      if(!$origen||empty($origen['id_bien'])||!in_array($origen['tipo_documento'],['TARJETA_RESGUARDO','RESGUARDO_EQUIPO'],true))throw new RuntimeException('El documento seleccionado no requiere evidencia firmada.');
      $s=$pdo->prepare('SELECT MAX(id_traspaso_documento) FROM traspaso_documento WHERE id_traspaso=? AND id_bien=? AND tipo_documento=? FOR UPDATE');$s->execute([$id,$origen['id_bien'],$origen['tipo_documento']]);if((int)$s->fetchColumn()!==$documentoOrigen)throw new RuntimeException('El documento seleccionado ya tiene una versión posterior. Actualiza el expediente antes de cargar la evidencia.');
      $s=$pdo->prepare('SELECT COALESCE(MAX(version),0)+1 FROM traspaso_documento WHERE id_traspaso=? AND tipo_documento=?');$s->execute([$id,$origen['tipo_documento']]);$version=(int)$s->fetchColumn();
      $s=$pdo->prepare('INSERT INTO traspaso_documento (id_traspaso,id_bien,tipo_documento,ruta_interna,nombre_original,mime_type,tamano_bytes,sha256,version,id_documento_anterior,id_usuario_carga) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
      $s->execute([$id,$origen['id_bien'],$origen['tipo_documento'],$ruta,$nombre,$mime,$bytes,$hash,$version,$documentoOrigen,$usuarioId]);$doc=(int)$pdo->lastInsertId();
      self::eventoEn($pdo,$id,$usuarioId,'CARGA_DOCUMENTO_FIRMADO',$estado,$estado,null,['documento'=>$doc,'documento_origen'=>$documentoOrigen,'bien'=>(int)$origen['id_bien'],'tipo'=>$origen['tipo_documento'],'version'=>$version,'sha256'=>$hash]);
      $pdo->commit();return $doc;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  public static function generarDocumento(int $id,string $tipo,string $ruta,string $nombre,string $hash,int $bytes,int $usuarioId,?int $bienId=null):int
  { if(!in_array($tipo,['TARJETA_RESGUARDO','RESGUARDO_EQUIPO'],true)||!$bienId)throw new InvalidArgumentException('Tipo de documento o bien no válido.');$pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();try{$s=$pdo->prepare("SELECT estado FROM traspaso WHERE id_traspaso=? FOR UPDATE");$s->execute([$id]);$estado=$s->fetchColumn();if(!in_array($estado,['DOCUMENTOS','FIRMA','COMPLETADO'],true))throw new RuntimeException('Los documentos se generan después de la autorización.');$s=$pdo->prepare('SELECT COUNT(*) FROM traspaso_bien WHERE id_traspaso=? AND id_bien=?');$s->execute([$id,$bienId]);if(!(int)$s->fetchColumn())throw new RuntimeException('El bien no pertenece al expediente.');$s=$pdo->prepare('INSERT INTO traspaso_documento (id_traspaso,id_bien,tipo_documento,ruta_interna,nombre_original,mime_type,tamano_bytes,sha256,id_usuario_carga) VALUES (?,?,?,?,?,?,?,?,?)');$s->execute([$id,$bienId,$tipo,$ruta,$nombre,'application/pdf',$bytes,$hash,$usuarioId]);$doc=(int)$pdo->lastInsertId();self::eventoEn($pdo,$id,$usuarioId,'GENERACION_DOCUMENTO',$estado,$estado,null,['documento'=>$doc,'bien'=>$bienId,'tipo'=>$tipo]);if(self::documentosObligatoriosGenerados($pdo,$id)&&$estado==='DOCUMENTOS'){$pdo->prepare("UPDATE traspaso SET estado='FIRMA' WHERE id_traspaso=?")->execute([$id]);self::eventoEn($pdo,$id,$usuarioId,'AVANCE','DOCUMENTOS','FIRMA');}$pdo->commit();return $doc;}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;} }

  public static function documentoPorId(int $id):array
  { $r=parent::query('SELECT d.*,t.estado FROM traspaso_documento d JOIN traspaso t ON t.id_traspaso=d.id_traspaso WHERE d.id_traspaso_documento=:id LIMIT 1',['id'=>$id]);return $r[0]??[]; }

  private static function documentosObligatoriosGenerados(PDO $pdo,int $id):bool
  {
    $from=self::documentoMinimoVigente($pdo,$id);
    $s=$pdo->prepare('SELECT COUNT(*) FROM traspaso_bien WHERE id_traspaso=?');$s->execute([$id]);$total=(int)$s->fetchColumn();if(!$total)return false;
    $s=$pdo->prepare("SELECT COUNT(DISTINCT CONCAT(id_bien,':',tipo_documento)) FROM traspaso_documento WHERE id_traspaso=? AND id_traspaso_documento>=? AND id_bien IS NOT NULL AND tipo_documento IN ('TARJETA_RESGUARDO','RESGUARDO_EQUIPO')");$s->execute([$id,$from]);return (int)$s->fetchColumn()>=$total*2;
  }

  private static function documentoMinimoVigente(PDO $pdo,int $id):int
  { $s=$pdo->prepare("SELECT metadatos FROM traspaso_evento WHERE id_traspaso=? AND tipo_evento='EDICION_DATOS_DOCUMENTO'");$s->execute([$id]);$from=1;foreach($s->fetchAll(PDO::FETCH_COLUMN) as $json){$meta=json_decode((string)$json,true)?:[];$from=max($from,(int)($meta['documento_desde']??1));}return $from; }

  private static function documentosObligatoriosFirmados(PDO $pdo,int $id):bool
  {
    $from=self::documentoMinimoVigente($pdo,$id);
    $items=$pdo->prepare('SELECT id_bien FROM traspaso_bien WHERE id_traspaso=?');$items->execute([$id]);$bienIds=array_map('intval',$items->fetchAll(PDO::FETCH_COLUMN));if(!$bienIds)return false;
    $docs=$pdo->prepare("SELECT d.id_traspaso_documento,d.id_bien,d.tipo_documento FROM traspaso_documento d JOIN (SELECT id_bien,tipo_documento,MAX(id_traspaso_documento) id FROM traspaso_documento WHERE id_traspaso=? AND id_traspaso_documento>=? AND id_bien IS NOT NULL AND tipo_documento IN ('TARJETA_RESGUARDO','RESGUARDO_EQUIPO') GROUP BY id_bien,tipo_documento) x ON x.id=d.id_traspaso_documento");$docs->execute([$id,$from]);$required=[];foreach($docs->fetchAll(PDO::FETCH_ASSOC) as $d)$required[(int)$d['id_bien']][$d['tipo_documento']]=(int)$d['id_traspaso_documento'];
    $events=$pdo->prepare("SELECT metadatos FROM traspaso_evento WHERE id_traspaso=? AND tipo_evento='CARGA_DOCUMENTO_FIRMADO'");$events->execute([$id]);$signed=[];foreach($events->fetchAll(PDO::FETCH_COLUMN) as $raw){$meta=json_decode((string)$raw,true)?:[];$signed[(int)($meta['documento']??0)]=true;}
    foreach($bienIds as $bienId){foreach(['TARJETA_RESGUARDO','RESGUARDO_EQUIPO'] as $type){$docId=$required[$bienId][$type]??0;if(!$docId||empty($signed[$docId]))return false;}}
    return true;
  }

  public static function completarDocumentado(int $id,int $usuarioId):void
  {
    $pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();
    try{$s=$pdo->prepare('SELECT * FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$t=$s->fetch(PDO::FETCH_ASSOC);if(!$t||$t['estado']!=='FIRMA')throw new RuntimeException('El expediente no está listo para completarse.');if(!self::documentosObligatoriosFirmados($pdo,$id))throw new RuntimeException('Faltan documentos o evidencias documentales firmadas.');
      self::aplicarCambiosPatrimoniales($pdo,$id,$t,$usuarioId);
      $pdo->prepare("UPDATE traspaso SET estado='COMPLETADO',id_usuario_confirma_recepcion=?,fecha_recepcion=NOW(),fecha_completado=NOW() WHERE id_traspaso=? AND estado='FIRMA'")->execute([$usuarioId,$id]);
      self::eventoEn($pdo,$id,$usuarioId,'EJECUCION','FIRMA','COMPLETADO',null);$pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  private static function aplicarCambiosPatrimoniales(PDO $pdo,int $id,array $t,int $usuarioId):void
  {
    $rid=(int)$t['id_resguardante_destino'];$s=$pdo->prepare('SELECT id_resguardante,id_unidad,activo FROM resguardante WHERE id_resguardante=? FOR UPDATE');$s->execute([$rid]);$receiver=$s->fetch(PDO::FETCH_ASSOC);if(!$receiver||(int)$receiver['activo']!==1||(int)$receiver['id_unidad']!==(int)$t['id_unidad_destino'])throw new RuntimeException('El nuevo resguardante ya no es válido para la unidad destino.');
    $unitCheck=$pdo->prepare('SELECT id_unidad,activo FROM unidad_administrativa WHERE id_unidad=? FOR UPDATE');$unitCheck->execute([$t['id_unidad_origen']]);$sourceUnit=$unitCheck->fetch(PDO::FETCH_ASSOC);if(!$sourceUnit||(int)$sourceUnit['activo']!==1)throw new RuntimeException('La unidad de origen está inactiva o ya no existe.');
    $unitCheck->execute([$t['id_unidad_destino']]);$destUnit=$unitCheck->fetch(PDO::FETCH_ASSOC);if(!$destUnit||(int)$destUnit['activo']!==1)throw new RuntimeException('La unidad destino está inactiva o ya no existe.');
    $lines=$pdo->prepare('SELECT tb.*,b.id_unidad,b.id_ubicacion,b.activo FROM traspaso_bien tb JOIN bien b ON b.id_bien=tb.id_bien WHERE tb.id_traspaso=? ORDER BY tb.id_bien FOR UPDATE');$lines->execute([$id]);$items=$lines->fetchAll(PDO::FETCH_ASSOC);if(!$items)throw new RuntimeException('El expediente no contiene bienes.');
    foreach($items as $item){if((int)$item['activo']!==1||(int)$item['id_unidad']!==(int)$t['id_unidad_origen'])throw new RuntimeException('Un bien cambió de estado o unidad desde que se creó el expediente.');
      $s=$pdo->prepare('SELECT id_resguardo,id_resguardante FROM resguardo WHERE id_bien=? AND activo=1 FOR UPDATE');$s->execute([$item['id_bien']]);$current=$s->fetch(PDO::FETCH_ASSOC);if(!$current||(int)$current['id_resguardo']!==(int)$item['id_resguardo_origen']||(int)$current['id_resguardante']!==(int)$item['id_resguardante_origen'])throw new RuntimeException('La custodia de un bien cambió desde la creación del expediente.');
      $sourceKeeperCheck=$pdo->prepare('SELECT activo FROM resguardante WHERE id_resguardante=? FOR UPDATE');$sourceKeeperCheck->execute([$item['id_resguardante_origen']]);$sourceKeeper=$sourceKeeperCheck->fetch(PDO::FETCH_ASSOC);if(!$sourceKeeper||(int)$sourceKeeper['activo']!==1)throw new RuntimeException('El resguardante de origen está inactivo o ya no existe.');
      $location=$item['ubicacion_sin_cambio']?($item['id_ubicacion']??null):$item['id_ubicacion_destino'];if(!$item['ubicacion_sin_cambio']&&!empty($location)){$locationCheck=$pdo->prepare('SELECT id_ubicacion FROM ubicacion WHERE id_ubicacion=? FOR UPDATE');$locationCheck->execute([$location]);if(!$locationCheck->fetchColumn())throw new RuntimeException('La ubicación destino ya no existe.');}
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
      foreach($lines as $line){if((int)$line['activo']!==1||(int)$line['id_unidad']!==(int)$original['id_unidad_destino']||(int)$line['vigente_resguardante']!==(int)$original['id_resguardante_destino'])throw new RuntimeException('No es posible revertir de forma segura: un bien cambió de estado, unidad o resguardante después del traspaso.');$s=$pdo->prepare('SELECT COUNT(*) FROM traspaso_bien tb JOIN traspaso t ON t.id_traspaso=tb.id_traspaso WHERE tb.id_bien=? AND t.estado IN '.self::ESTADOS_ACTIVOS);$s->execute([$line['id_bien']]);if((int)$s->fetchColumn()>0)throw new RuntimeException('No es posible revertir: uno de los bienes tiene otro traspaso activo.');$s=$pdo->prepare('SELECT COUNT(*) FROM movimiento_bien WHERE id_bien=? AND id_movimiento>?');$s->execute([$line['id_bien'],$line['id_movimiento']]);if((int)$s->fetchColumn()>0)throw new RuntimeException('No es posible revertir: existe un movimiento posterior para uno de los bienes.');}
      $type=(int)$original['id_unidad_origen']===(int)$original['id_unidad_destino']?'MISMA_UNIDAD':'ENTRE_UNIDADES';
      $sql="INSERT INTO traspaso (folio,tipo,estado,id_unidad_origen,id_unidad_destino,id_resguardante_destino,id_usuario_creador,id_traspaso_revertido,id_usuario_reversion,fecha_reversion,motivo,observaciones) VALUES ('PENDIENTE',?,'CREADO',?,?,?,?,?,?,NOW(),?,?)";
      $s=$pdo->prepare($sql);$s->execute([$type,$original['id_unidad_destino'],$original['id_unidad_origen'],$lines[0]['id_resguardante_origen'],$usuarioId,$id,$usuarioId,trim($motivo),'Reversión compensatoria de '.$original['folio']]);$newId=(int)$pdo->lastInsertId();$folio=sprintf('TR-%06d',$newId);$pdo->prepare('UPDATE traspaso SET folio=? WHERE id_traspaso=?')->execute([$folio,$newId]);
      $s=$pdo->prepare('INSERT INTO traspaso_bien (id_traspaso,id_bien,id_resguardante_origen,id_resguardo_origen,id_ubicacion_destino,ubicacion_sin_cambio,origen_snapshot) VALUES (?,?,?,?,?,?,?)');
      foreach($lines as $line){$originalSnapshot=json_decode((string)$line['origen_snapshot'],true)?:[];$restoreLocation=$originalSnapshot['ubicacion_id']??null;$noChange=(int)($restoreLocation??0)===(int)($line['id_ubicacion']??0);$snap=['reversion_de'=>$original['folio'],'resguardo_origen'=>(int)$line['vigente_id'],'resguardante_actual'=>(int)$line['vigente_resguardante'],'reversion_ubicacion'=>$restoreLocation];$s->execute([$newId,$line['id_bien'],$line['vigente_resguardante'],$line['vigente_id'],$noChange?null:$restoreLocation,$noChange?1:0,json_encode($snap,JSON_THROW_ON_ERROR)]);}
      self::eventoEn($pdo,$newId,$usuarioId,'CREACION_REVERSIÓN',null,'CREADO',$motivo,['traspaso_original'=>$id]);self::eventoEn($pdo,$id,$usuarioId,'REVERSIÓN_SOLICITADA','COMPLETADO','COMPLETADO',$motivo,['traspaso_compensatorio'=>$newId]);$pdo->commit();return $newId;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }

  private static function transicion(int $id,int $uid,string $estado,callable $fn):void
  { self::transicionAny($id,$uid,[$estado],$fn); }
  private static function transicionAny(int $id,int $uid,array $estados,callable $fn):void
  { $pdo=parent::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();try{$s=$pdo->prepare('SELECT * FROM traspaso WHERE id_traspaso=? FOR UPDATE');$s->execute([$id]);$t=$s->fetch(PDO::FETCH_ASSOC);if(!$t||!in_array($t['estado'],$estados,true))throw new RuntimeException('El expediente cambió o ya no permite esta acción.');$t['_actor_role']=(string)(get_user('rol')?:'');$fn($pdo,$t);$pdo->commit();}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;} }

  private static function eventoEn(PDO $pdo,int $id,int $uid,string $tipo,?string $antes,?string $despues,?string $motivo=null,?array $meta=null):void
  { $s=$pdo->prepare('INSERT INTO traspaso_evento (id_traspaso,id_usuario,tipo_evento,estado_anterior,estado_nuevo,motivo,metadatos) VALUES (?,?,?,?,?,?,?)');$s->execute([$id,$uid?:null,$tipo,$antes,$despues,$motivo,$meta?json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR):null]); }
}
