<?php

class traspasosController extends InventoryController implements ControllerInterface
{
  public function index()
  {
    $this->can('traspasos-consultar');
    $filters=[];foreach(['q','folio','tipo','estado','desde','hasta','unidad_origen','unidad_destino','resguardante_destino'] as $key)$filters[$key]=trim((string)($_GET[$key]??''));
    $traspasos=TraspasoModel::listar($filters);$documentos=TraspasoModel::documentosParaTraspasos(array_column($traspasos,'id_traspaso'));
    foreach($traspasos as &$traspaso)$traspaso['resumen_documental']=TraspasoDocumentService::resumirDocumentos($documentos[(int)$traspaso['id_traspaso']]??[]);unset($traspaso);
    $this->setTitle('Traspasos');$this->renderInventory('index',['traspasos'=>$traspasos,'filtros'=>$filters,'unidades'=>CatalogoModel::unidadesConJerarquia(),'can_create_transfer'=>$this->hasPermission('traspasos-crear')]);
  }

  public function crear()
  {
    $this->can('traspasos-crear');
    if($_SERVER['REQUEST_METHOD']==='POST'){
      $id=null;$archivosCreados=[];$pdo=null;
      try{$this->validarPost();$user=$this->requireActiveAccount();$tipo=(string)($_POST['tipo']??'');$oficio=$tipo==='ENTRE_UNIDADES'?$this->validarArchivoOficio($_FILES['oficio']??null):null;
        $pdo=Db::connect(true);if($pdo->inTransaction())$pdo->commit();$pdo->beginTransaction();
        $id=TraspasoModel::crear($_POST,$this->idsBienPost(),(int)$user['id'],$pdo);
        if($oficio){$exp=TraspasoModel::detalle($id,$pdo);$relative=$exp['folio'].DIRECTORY_SEPARATOR.'oficio-'.bin2hex(random_bytes(16)).'.'.$oficio['ext'];$stored=$this->writePrivate($relative,$oficio['tmp_name'],(int)$oficio['size']);$archivosCreados[]=$stored['path'];
          TraspasoModel::registrarDocumento($id,'OFICIO_AUTORIZACION',str_replace(DIRECTORY_SEPARATOR,'/',$relative),$oficio['name'],$oficio['mime'],$stored['size'],$stored['hash'],(int)$user['id'],null,null,['numero_oficio'=>$oficio['numero'],'fecha_oficio'=>$oficio['fecha']],$pdo);}
        TraspasoDocumentService::generar($id,(int)$user['id'],['TARJETA_RESGUARDO','RESGUARDO_EQUIPO'],[],$pdo,static function(string $path)use(&$archivosCreados):void{$archivosCreados[]=$path;});
        $pdo->commit();
        Flasher::success($tipo==='MISMA_UNIDAD'?'Se creó el expediente validado; puedes preparar sus documentos.':'Se creó el expediente pendiente de validación documental.');Redirect::to('traspasos/detalle/'.$id);
      }catch(Throwable $e){if($pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();foreach(array_reverse($archivosCreados) as $path)if(is_file($path))@unlink($path);Flasher::error($e->getMessage());Redirect::to('traspasos/crear');}
    }
    $data=TraspasoModel::catalogosFormulario();
    $data['unidades']=CatalogoModel::unidadesConJerarquia();
    $data['bienes_preseleccionados']=[];$data['unidad_origen_preseleccionada']='';$data['resguardante_actual_preseleccionado']='';$data['preseleccion_mensaje']='';
    $bienId=filter_var($_GET['bien_id']??null,FILTER_VALIDATE_INT);
    $resguardanteId=filter_var($_GET['resguardante_id']??null,FILTER_VALIDATE_INT);
    if($bienId&&$resguardanteId){$resguardanteId=null;}
    if($bienId||$resguardanteId){$available=TraspasoModel::bienesDisponibles($resguardanteId?:null);if($bienId){foreach($available as $item)if((int)$item['id_bien']===(int)$bienId){$data['bienes_preseleccionados']=[(int)$bienId];$resguardanteId=(int)$item['id_resguardante'];break;}}
      if($resguardanteId){$keeperRows=array_values(array_filter($data['resguardantes'],static fn($r)=>(int)$r['id_resguardante']===(int)$resguardanteId));if($keeperRows)$data['resguardante_actual_preseleccionado']=(string)$resguardanteId;}}
    if(($bienId||$resguardanteId)&&empty($data['resguardante_actual_preseleccionado']))$data['preseleccion_mensaje']='El resguardante indicado no tiene bienes disponibles para iniciar un traspaso.';
    $this->setTitle('Crear traspaso');$this->renderInventory('crear',$data);
  }

  public function bienes_origen($id=null)
  {
    header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store, private');
    try{$this->can('traspasos-crear');$keeperId=filter_var($id,FILTER_VALIDATE_INT);if(!$keeperId)throw new InvalidArgumentException('Selecciona un resguardante de origen.');echo json_encode(['success'=>true,'results'=>TraspasoModel::bienesDisponibles((int)$keeperId)],JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);}
    catch(Throwable $e){http_response_code(422);echo json_encode(['success'=>false,'message'=>'No fue posible cargar los bienes del resguardante.'],JSON_UNESCAPED_UNICODE);}
  }

  public function resguardantes_destino($unidadId=null)
  {
    header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store, private');
    try{$this->can('traspasos-crear');$id=filter_var($unidadId,FILTER_VALIDATE_INT);if(!$id)throw new InvalidArgumentException('Selecciona una Unidad Administrativa destino válida.');$query=trim((string)($_GET['q']??''));echo json_encode(['success'=>true,'results'=>ResguardanteModel::buscarActivosPorUnidad((int)$id,$query)],JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);}
    catch(Throwable $e){http_response_code(422);echo json_encode(['success'=>false,'message'=>'No fue posible buscar resguardantes para la Unidad Administrativa seleccionada.'],JSON_UNESCAPED_UNICODE);}
  }

  public function detalle($id=null)
  {
    $this->can('traspasos-consultar');$exp=TraspasoModel::detalle((int)$id);
    if(!$exp){Flasher::error('No se encontró el expediente.');Redirect::to('traspasos');}
    $permisos=[];foreach(['modificar','cancelar','subir-documento','autorizar','rechazar','ejecutar','confirmar-entrega','confirmar-recepcion','generar-tarjeta','generar-resguardo','descargar-documentos','regularizar-documentos'] as $name)$permisos[$name]=$this->hasPermission('traspasos-'.$name);
    $evidencias=[];$evidenceHistory=[];$evidenceFiles=[];$evidenceValidations=[];$evidenceRejections=[];$metadata=[];$origins=[];
    foreach($exp['eventos'] as $event){
      $meta=$event['metadatos']??[];
      if($event['tipo_evento']==='CARGA_DOCUMENTO')$metadata[(int)($meta['documento']??0)]=$meta;
      if(in_array($event['tipo_evento'],['GENERACION_DOCUMENTO','CARGA_DOCUMENTO'],true))$origins[(int)($meta['documento']??0)]=$event['tipo_evento']==='GENERACION_DOCUMENTO'?'GENERADO':'CARGADO';
      if($event['tipo_evento']==='CARGA_DOCUMENTO_FIRMADO'){$originId=(int)($meta['documento_origen']??0);$fileId=(int)($meta['documento']??0);$evidencias[$originId]=$event;$evidenceHistory[$originId][]=$fileId;$evidenceFiles[$fileId]=true;}
      if(in_array($event['tipo_evento'],['VALIDACION_EVIDENCIA','RECHAZO_EVIDENCIA'],true)){$originId=(int)($meta['documento_origen']??0);$fileId=(int)($meta['documento_evidencia']??0);if($originId&&$fileId){if($event['tipo_evento']==='VALIDACION_EVIDENCIA')$evidenceValidations[$originId][$fileId]=$event;else $evidenceRejections[$originId][$fileId]=$event;}}
    }
    $tarjetaPorBien=[];$resguardoEquipoPorBien=[];foreach($exp['eventos'] as $event){$meta=$event['metadatos']??[];$bienId=(int)($meta['bien']??0);if($event['tipo_evento']==='EDICION_TARJETA_RESGUARDO'&&$bienId)$tarjetaPorBien[$bienId]=$meta['campos']??[];if($event['tipo_evento']==='EDICION_RESGUARDO_EQUIPO'&&$bienId)$resguardoEquipoPorBien[$bienId]=$meta['campos']??[];}
    foreach($exp['documentos'] as &$documento){$verification=TraspasoDocumentService::verificarDocumento((string)$documento['ruta_interna'],(string)($documento['sha256']??''),isset($documento['tamano_bytes'])?(int)$documento['tamano_bytes']:null);$documento['archivo_estado']=$verification['estado'];$documento['archivo_mensaje']=$verification['mensaje'];$documento['archivo_disponible']=$verification['disponible'];}unset($documento);$exp['resumen_documental']=TraspasoDocumentService::resumirDocumentos($exp['documentos']);$exp['regularizacion_habilitada']=getenv('BIENES_ENABLE_DOCUMENT_REGULARIZATION')==='1';
    $exp['evidencias_documento']=$evidencias;$exp['tiene_evidencia_documental']=(bool)$evidenceFiles;$labels=[];foreach($exp['bienes'] as $item){$labels[(int)$item['id_bien']]=trim((string)$item['clave_interna'].' · '.(string)$item['nombre_bien'],' ·');}$documentoDesde=1;foreach($exp['eventos'] as $event)if($event['tipo_evento']==='EDICION_DATOS_DOCUMENTO')$documentoDesde=max($documentoDesde,(int)($event['metadatos']['documento_desde']??1));$latest=[];foreach($exp['documentos'] as &$doc){$docId=(int)$doc['id_traspaso_documento'];$doc['metadata']=$metadata[$docId]??[];$doc['bien_label']=$labels[(int)($doc['id_bien']??0)]??'';$doc['obsoleto']=$docId<$documentoDesde;$doc['es_evidencia']=isset($evidenceFiles[$docId]);$doc['evidencia_firmada']=isset($evidencias[$docId]);if(!$doc['es_evidencia']&&!$doc['obsoleto']&&!empty($doc['id_bien'])){$key=$doc['id_bien'].'|'.$doc['tipo_documento'];if(!isset($latest[$key])||(int)$docId>(int)$latest[$key]['id_traspaso_documento'])$latest[$key]=$doc;} }unset($doc);
    $latest=[];$docsById=[];foreach($exp['documentos'] as &$doc){$doc['origen_archivo']=$origins[(int)$doc['id_traspaso_documento']]??'DESCONOCIDO';$doc['tamano_legible']=$this->formatoTamano((int)$doc['tamano_bytes']);$doc['tipo_archivo']=$this->tipoArchivo((string)$doc['mime_type']);$docsById[(int)$doc['id_traspaso_documento']]=$doc;if(!$doc['es_evidencia']&&!$doc['obsoleto']&&!empty($doc['id_bien'])){$key=$doc['id_bien'].'|'.$doc['tipo_documento'];if(!isset($latest[$key])||(int)$doc['id_traspaso_documento']>(int)$latest[$key]['id_traspaso_documento'])$latest[$key]=$doc;}}unset($doc);
    foreach($evidenceHistory as $originId=>$historyIds)foreach($historyIds as $evidenceId)if(isset($docsById[$evidenceId])){$validation=$evidenceValidations[$originId][$evidenceId]??null;$rejection=$evidenceRejections[$originId][$evidenceId]??null;$docsById[$evidenceId]['estado_evidencia']=$validation?'VALIDADO':($rejection?'RECHAZADO':'PENDIENTE');$docsById[$evidenceId]['validacion_evidencia']=$validation;$docsById[$evidenceId]['rechazo_evidencia']=$rejection;}
    $currentDocumentIds=[];foreach($latest as &$currentDoc){$currentId=(int)$currentDoc['id_traspaso_documento'];$currentDocumentIds[$currentId]=true;$historyIds=$evidenceHistory[$currentId]??[];$currentDoc['evidencia_archivo']=isset($evidencias[$currentId])?($docsById[(int)($evidencias[$currentId]['metadatos']['documento']??0)]??null):null;$currentDoc['evidencias_anteriores']=[];foreach($historyIds as $evidenceId)if(!$currentDoc['evidencia_archivo']||(int)$currentDoc['evidencia_archivo']['id_traspaso_documento']!==$evidenceId)if(isset($docsById[$evidenceId]))$currentDoc['evidencias_anteriores'][]=$docsById[$evidenceId];$currentEvidenceId=(int)($currentDoc['evidencia_archivo']['id_traspaso_documento']??0);$currentDoc['estado_evidencia']=$currentEvidenceId?($docsById[$currentEvidenceId]['estado_evidencia']??'PENDIENTE'):'SIN_EVIDENCIA';$currentDoc['evidencia_validada']=$currentDoc['estado_evidencia']==='VALIDADO';$currentDoc['validacion_evidencia']=$currentEvidenceId?($evidenceValidations[$currentId][$currentEvidenceId]??null):null;$currentDoc['rechazo_evidencia']=$currentEvidenceId?($evidenceRejections[$currentId][$currentEvidenceId]??null):null;if($currentDoc['tipo_documento']==='TARJETA_RESGUARDO'){foreach($exp['bienes'] as $line)if((int)$line['id_bien']===(int)$currentDoc['id_bien']){$fields=TraspasoDocumentService::camposTarjetaDesdeExpediente($exp,$line,$currentDoc);$currentDoc['tarjeta_campos']=array_replace($fields,$tarjetaPorBien[(int)$currentDoc['id_bien']]??[]);break;}}elseif($currentDoc['tipo_documento']==='RESGUARDO_EQUIPO'){$bien=BienModel::porId((int)$currentDoc['id_bien']);$resguardos=Model::query('SELECT * FROM resguardo WHERE id_bien=:id AND activo=1 LIMIT 1',['id'=>$currentDoc['id_bien']]);$bien['id_resguardante']=(int)$exp['id_resguardante_destino'];$currentDoc['resguardo_equipo_campos']=TraspasoDocumentService::camposResguardoEquipo($bien,$resguardos[0]??[],$resguardoEquipoPorBien[(int)$currentDoc['id_bien']]??[]);}elseif($currentDoc['tipo_documento']==='BAJA_RESGUARDANTE'){$currentDoc['baja_campos']=TraspasoDocumentService::camposBajaDesdeEventos($exp['eventos'],(int)$currentDoc['id_bien'],(string)($exp['observaciones']??''));$currentDoc['ccp_baja']=$currentDoc['baja_campos']['ccp'];}}unset($currentDoc);
    $historicalEvidence=[];foreach($evidenceHistory as $sourceId=>$historyIds)if(!isset($currentDocumentIds[(int)$sourceId]))foreach($historyIds as $evidenceId)if(isset($docsById[$evidenceId]))$historicalEvidence[]=$docsById[$evidenceId];$exp['evidencias_historicas']=$historicalEvidence;
    $ready=(bool)$exp['bienes'];foreach($exp['bienes'] as $item){$bienId=(int)$item['id_bien'];foreach(['TARJETA_RESGUARDO','RESGUARDO_EQUIPO'] as $type)if(!isset($latest[$bienId.'|'.$type]))$ready=false;if(!empty($item['id_resguardante_origen'])&&!isset($latest[$bienId.'|BAJA_RESGUARDANTE']))$ready=false;}
    foreach($exp['bienes'] as &$item)$item['requiere_baja_resguardante']=!empty($item['id_resguardante_origen']);unset($item);
    $complete=$ready;foreach($latest as $doc)if(empty($doc['evidencia_archivo'])||empty($doc['evidencia_validada']))$complete=false;
    $exp['documentos_obligatorios_generados']=$ready;$exp['documentos_vigentes']=array_values($latest);$exp['listo_para_completar']=$complete;$exp['puede_editar_datos_documento']=$exp['estado']==='VALIDADO'&&!$evidencias;$exp['hay_versiones_anteriores']=false;foreach($exp['documentos'] as $oldDoc)if(!$oldDoc['es_evidencia']&&((!empty($oldDoc['obsoleto'])&&!empty($oldDoc['id_bien']))||(in_array($oldDoc['tipo_documento'],['TARJETA_RESGUARDO','RESGUARDO_EQUIPO'],true)&&empty($oldDoc['id_bien'])))){$exp['hay_versiones_anteriores']=true;break;}
    $exp['tiene_oficio_externo']=false;$exp['oficio_actual']=null;foreach($exp['documentos'] as $doc)if($doc['tipo_documento']==='OFICIO_AUTORIZACION'){$exp['tiene_oficio_externo']=true;if(!$exp['oficio_actual']||(int)$doc['id_traspaso_documento']>(int)$exp['oficio_actual']['id_traspaso_documento'])$exp['oficio_actual']=$doc;}
    $user=$this->requireActiveAccount();$isAdmin=($user['rol']??'')==='admin'&&$this->hasPermission('admin-access',$user);$isTransferAuthorizer=$isAdmin&&$this->hasPermission('traspasos-autorizar',$user);foreach($exp['documentos_vigentes'] as &$currentDoc){$canEdit=$exp['estado']==='VALIDADO'&&!isset($evidencias[(int)$currentDoc['id_traspaso_documento']])&&$permisos['modificar']&&((int)$exp['id_usuario_creador']===(int)$user['id']||($user['rol']??'')==='admin');$currentDoc['puede_editar_tarjeta']=$currentDoc['tipo_documento']==='TARJETA_RESGUARDO'&&$canEdit;$currentDoc['puede_editar_resguardo_equipo']=$currentDoc['tipo_documento']==='RESGUARDO_EQUIPO'&&$canEdit;$currentDoc['puede_editar_baja']=$currentDoc['tipo_documento']==='BAJA_RESGUARDANTE'&&$canEdit;}unset($currentDoc);
    $nextAction=['key'=>'none','label'=>'Sin acciones pendientes','anchor'=>'','button'=>false];$firstMissingEvidence=null;$firstPendingEvidence=null;foreach($exp['documentos_vigentes'] as $currentDoc){$anchor='documento-'.(int)$currentDoc['id_bien'].'-'.(string)$currentDoc['tipo_documento'];if(!$firstMissingEvidence&&empty($currentDoc['evidencia_archivo']))$firstMissingEvidence=$anchor;if(!$firstPendingEvidence&&($currentDoc['estado_evidencia']??'')==='PENDIENTE')$firstPendingEvidence=$anchor;}
    if($exp['estado']==='COMPLETADO'){$nextAction=['key'=>'complete','label'=>'Sin acciones pendientes','anchor'=>'','button'=>false];}elseif((int)$exp['id_unidad_origen']!==(int)$exp['id_unidad_destino']&&!$exp['tiene_oficio_externo']){$nextAction=['key'=>'office-upload','label'=>'Cargar oficio','anchor'=>'autorizacion-externa','button'=>$permisos['subir-documento']];}elseif((int)$exp['id_unidad_origen']!==(int)$exp['id_unidad_destino']&&!$exp['fecha_autorizacion']){$nextAction=['key'=>'office-validate','label'=>$isTransferAuthorizer?'Validar oficio':'Pendiente de validación del oficio','anchor'=>'autorizacion-externa','button'=>$isTransferAuthorizer];}elseif($exp['estado']==='VALIDADO'&&!$exp['documentos_obligatorios_generados']){$canGenerate=$permisos['generar-tarjeta']&&$permisos['generar-resguardo'];$nextAction=['key'=>'generate','label'=>$canGenerate?'Generar / actualizar documentos':'Documentos obligatorios pendientes','anchor'=>'documentos-expediente','button'=>$canGenerate];}elseif($exp['estado']==='VALIDADO'&&$firstMissingEvidence){$nextAction=['key'=>'evidence-upload','label'=>$permisos['subir-documento']?'Subir evidencia':'Evidencia documental pendiente','anchor'=>$firstMissingEvidence,'button'=>$permisos['subir-documento']];}elseif($exp['estado']==='VALIDADO'&&$firstPendingEvidence){$canValidate=$isAdmin&&$permisos['autorizar'];$nextAction=['key'=>'evidence-validate','label'=>$canValidate?'Validar evidencia':'Pendiente de validación de evidencia','anchor'=>$firstPendingEvidence,'button'=>$canValidate];}elseif($exp['estado']==='VALIDADO'&&$exp['listo_para_completar']){$nextAction=['key'=>'complete','label'=>$permisos['ejecutar']?'Completar traspaso':'Documentación completa','anchor'=>'documentos-expediente','button'=>$permisos['ejecutar']];}
    $exp['evidencias_validadas']=0;foreach($exp['documentos_vigentes'] as $currentDoc)if(!empty($currentDoc['evidencia_validada']))$exp['evidencias_validadas']++;
    foreach($exp['eventos'] as $event){$meta=$event['metadatos']??[];if($event['tipo_evento']==='ENTREGA_REGISTRADA')$exp['responsable_entrega_fisico']=$meta['responsable_fisico']??'';if($event['tipo_evento']==='RECEPCION_REGISTRADA')$exp['responsable_recepcion_fisico']=$meta['responsable_fisico']??'';}
    $exp['entre_unidades']=(int)$exp['id_unidad_origen']!==(int)$exp['id_unidad_destino'];$exp['estado_etiqueta']=match($exp['estado']){'PENDIENTE_VALIDACION','CREADO','PENDIENTE_AUTORIZACION'=>'Pendiente de validar','VALIDADO','AUTORIZADO','PENDIENTE_ENTREGA','ENTREGA_CONFIRMADA','PENDIENTE_RECEPCION','DOCUMENTOS','FIRMA'=>'Validado','COMPLETADO'=>'Completado','CANCELADO'=>'Cancelado','RECHAZADO'=>'Rechazado',default=>(string)$exp['estado']};$exp['estado_tooltip']=match($exp['estado']){'PENDIENTE_VALIDACION','CREADO','PENDIENTE_AUTORIZACION'=>'Estado del expediente: pendiente de validar el oficio externo.','VALIDADO','AUTORIZADO','PENDIENTE_ENTREGA','ENTREGA_CONFIRMADA','PENDIENTE_RECEPCION','DOCUMENTOS','FIRMA'=>'Estado del expediente: Validado. Continúa con documentos y evidencias; el expediente se completa cuando las evidencias vigentes hayan sido validadas.','COMPLETADO'=>'Estado del expediente: Completado.','CANCELADO'=>'Estado del expediente: Cancelado.','RECHAZADO'=>'Estado del expediente: Rechazado.',default=>'Estado del expediente: '.(string)$exp['estado'].'.'};
    $this->setTitle('Detalle del traspaso');$this->renderInventory('detalle',['traspaso'=>$exp,'transfer_permissions'=>$permisos,'is_transfer_admin'=>$isAdmin,'is_transfer_authorizer'=>$isTransferAuthorizer,'current_user_id'=>(int)$user['id'],'next_action'=>$nextAction,'customBreadcrumb'=>true]);
  }

  private function formatoTamano(int $bytes):string
  { if($bytes>=1048576)return number_format($bytes/1048576,2).' MB';if($bytes>=1024)return number_format($bytes/1024,0).' KB';return $bytes.' bytes'; }

  private function tipoArchivo(string $mime):string
  { return match(strtolower($mime)){'application/pdf'=>'PDF','image/jpeg'=>'JPEG','image/png'=>'PNG',default=>'Archivo digitalizado'}; }

  public function editar($id=null)
  {
    $this->can('traspasos-modificar');$user=$this->requireActiveAccount();
    try{$this->validarPost();$current=TraspasoModel::detalle((int)$id);if(!$current)throw new RuntimeException('No existe el expediente.');$payload=['tipo'=>$current['tipo'],'id_unidad_origen'=>$current['id_unidad_origen'],'id_unidad_destino'=>$current['id_unidad_destino'],'id_resguardante_destino'=>$current['id_resguardante_destino'],'motivo'=>trim((string)($_POST['motivo']??'')),'observaciones'=>trim((string)($_POST['observaciones']??''))];$lines=array_column($current['bienes'],'id_bien');TraspasoModel::actualizarPendienteValidacion((int)$id,$payload,$lines,(int)$user['id']);Flasher::success('Se actualizaron los datos del expediente.');}
    catch(Throwable $e){Flasher::error($e->getMessage());}
    $this->volverAlDetalle((int)$id);
  }

  public function editar_datos_documentos($id=null)
  {
    if(array_key_exists('campos_tarjeta',$_POST)){$documento=filter_var($_POST['documento_tarjeta']??null,FILTER_VALIDATE_INT);$campos=is_array($_POST['campos_tarjeta'])?$_POST['campos_tarjeta']:[];$this->accionPost('traspasos-modificar',static function($uid)use($id,$documento,$campos){if(!$documento)throw new InvalidArgumentException('Selecciona una Tarjeta de Resguardo válida.');TraspasoModel::actualizarCamposTarjeta((int)$id,(int)$documento,$campos,$uid);TraspasoDocumentService::generar((int)$id,$uid,['TARJETA_RESGUARDO']);},'Se guardaron los cambios y se generó una nueva versión de la tarjeta.',(int)$id);return;}
    if(array_key_exists('campos_resguardo_equipo',$_POST)){$documento=filter_var($_POST['documento_resguardo_equipo']??null,FILTER_VALIDATE_INT);$campos=is_array($_POST['campos_resguardo_equipo'])?$_POST['campos_resguardo_equipo']:[];$this->accionPost('traspasos-modificar',static function($uid)use($id,$documento,$campos){if(!$documento)throw new InvalidArgumentException('Selecciona un Resguardo del equipo válido.');TraspasoModel::actualizarCamposResguardoEquipo((int)$id,(int)$documento,$campos,$uid);TraspasoDocumentService::generar((int)$id,$uid,['RESGUARDO_EQUIPO']);},'Se guardaron los cambios y se generó una nueva versión del Resguardo del equipo.',(int)$id);return;}
    if(array_key_exists('campos_baja',$_POST)){$documento=filter_var($_POST['documento_baja']??null,FILTER_VALIDATE_INT);$campos=is_array($_POST['campos_baja'])?$_POST['campos_baja']:[];$this->accionPost('traspasos-modificar',static function($uid)use($id,$documento,$campos){if(!$documento)throw new InvalidArgumentException('Selecciona una baja de resguardante válida.');TraspasoModel::actualizarCamposBaja((int)$id,(int)$documento,$campos,$uid);TraspasoDocumentService::generar((int)$id,$uid,['BAJA_RESGUARDANTE']);},'Se guardaron C. c. p. y Observaciones y se actualizó el PDF de la baja.',(int)$id);return;}
    $this->accionPost('traspasos-modificar',function($uid)use($id){TraspasoModel::actualizarDatosDocumento((int)$id,(string)($_POST['motivo']??''),(string)($_POST['observaciones']??''),$uid);},'Se actualizaron los datos. Regenera los documentos para que reflejen la versión actualizada.',(int)$id);
  }

  public function enviar($id=null)
  { Flasher::error('El expediente no utiliza etapas de envío adicionales.');Redirect::to('traspasos/detalle/'.(int)$id); }

  public function autorizar($id=null)
  { $this->transferAuthorizerOnly();$this->accionPost('traspasos-autorizar',function($uid)use($id){TraspasoModel::validarOficio((int)$id,$uid);},'El oficio externo quedó validado documentalmente.',(int)$id); }

  public function validar_evidencia($id=null)
  { $this->adminOnly();$this->accionPost('traspasos-autorizar',function($uid)use($id){$source=filter_var($_POST['documento_origen']??null,FILTER_VALIDATE_INT);$evidence=filter_var($_POST['documento_evidencia']??null,FILTER_VALIDATE_INT);if(!$source||!$evidence)throw new RuntimeException('No se identificó la evidencia vigente que debe validarse.');TraspasoModel::validarEvidencia((int)$id,(int)$source,(int)$evidence,$uid);},'La evidencia firmada y digitalizada quedó validada.',(int)$id); }

  public function rechazar_evidencia($id=null)
  { $this->adminOnly();$this->accionPost('traspasos-rechazar',function($uid)use($id){$source=filter_var($_POST['documento_origen']??null,FILTER_VALIDATE_INT);$evidence=filter_var($_POST['documento_evidencia']??null,FILTER_VALIDATE_INT);$reason=trim((string)($_POST['motivo_rechazo']??''));if(!$source||!$evidence)throw new RuntimeException('No se identificó la evidencia vigente que debe rechazarse.');TraspasoModel::rechazarEvidencia((int)$id,(int)$source,(int)$evidence,$reason,$uid);},'La evidencia fue rechazada y se registró el motivo.',(int)$id); }

  public function registrar_constancia_administrativa($id=null)
  { $this->accionPost('traspasos-regularizar-documentos',function($uid)use($id){return TraspasoModel::registrarConstanciaAdministrativa((int)$id,(int)$uid,['tipo_incidencia'=>(string)($_POST['tipo_incidencia']??''),'documentos'=>$_POST['documentos']??[],'fecha_deteccion'=>(string)($_POST['fecha_deteccion']??''),'referencia_resolucion'=>(string)($_POST['referencia_resolucion']??'')]);},'La constancia administrativa quedó registrada con la fecha actual del sistema.',(int)$id); }

  public function rechazar($id=null)
  { $this->adminOnly();$this->accionPost('traspasos-rechazar',function($uid)use($id){TraspasoModel::rechazar((int)$id,$uid,trim((string)($_POST['motivo']??'')));},'Traspaso rechazado.',(int)$id); }

  public function cancelar($id=null)
  { $this->accionPost('traspasos-cancelar',function($uid)use($id){TraspasoModel::cancelar((int)$id,$uid,trim((string)($_POST['motivo']??'')));},'Traspaso cancelado.',(int)$id); }

  public function confirmar_entrega($id=null)
  { $this->accionPost('traspasos-confirmar-entrega',function($uid)use($id){TraspasoModel::confirmarEntrega((int)$id,$uid,(string)($_POST['tipo_entrega']??''),trim((string)($_POST['responsable_fisico']??'')));},'La entrega quedó registrada.',(int)$id); }

  public function confirmar_recepcion($id=null)
  { $this->accionPost('traspasos-confirmar-recepcion',function($uid)use($id){TraspasoModel::confirmarRecepcion((int)$id,$uid,trim((string)($_POST['responsable_fisico']??'')));},'La recepción quedó registrada. Completa el expediente cuando toda la evidencia esté lista.',(int)$id); }

  public function firmar_documento($id=null)
  { $transferId=(int)($_POST['id_traspaso']??0);$this->accionPost('traspasos-ejecutar',static function(){throw new RuntimeException('La firma no se registra con una confirmación en pantalla. Carga la evidencia documental firmada.');},'', $transferId); }

  public function subir_documento_firmado($id=null)
  {
    $this->can('traspasos-subir-documento');$user=$this->requireActiveAccount();$saved=null;
    try{$this->validarPost();$documentoOrigen=filter_var($_POST['documento_origen']??null,FILTER_VALIDATE_INT);if(!$documentoOrigen)throw new RuntimeException('Selecciona el documento generado cuya evidencia firmada se cargará.');$file=$this->validarArchivoFirmado($_FILES['documento_firmado']??null);
      $exp=TraspasoModel::detalle((int)$id);if(!$exp)throw new RuntimeException('No existe el expediente.');
      $relative=$exp['folio'].DIRECTORY_SEPARATOR.'firmado-'.$documentoOrigen.'-'.bin2hex(random_bytes(16)).'.'.$file['ext'];$stored=$this->writePrivate($relative,$file['tmp_name'],(int)$file['size']);$saved=$stored['path'];
      TraspasoModel::registrarDocumentoFirmado((int)$id,$documentoOrigen,str_replace(DIRECTORY_SEPARATOR,'/',$relative),$file['name'],$file['mime'],$stored['size'],$stored['hash'],(int)$user['id']);$saved=null;
      Flasher::success('La evidencia documental firmada se agregó al expediente sin sobrescribir versiones anteriores.');
    }catch(Throwable $e){if($saved&&is_file($saved))@unlink($saved);Flasher::error($e->getMessage());}
    $this->volverAlDetalle((int)$id);
  }

  public function completar($id=null)
  { $this->accionPost('traspasos-ejecutar',function($uid)use($id){TraspasoModel::completarDocumentado((int)$id,$uid);},'El traspaso se completó y se registró en el histórico de movimientos.',(int)$id); }

  public function subir_documento($id=null)
  {
    $this->can('traspasos-subir-documento');$user=$this->requireActiveAccount();$file=$_FILES['oficio']??null;$saved=null;
    try{$this->validarPost();$file=$this->validarArchivoOficio($file);
      $exp=TraspasoModel::detalle((int)$id);if(!$exp)throw new RuntimeException('No existe el expediente.');
      $relative=$exp['folio'].DIRECTORY_SEPARATOR.'oficio-'.bin2hex(random_bytes(16)).'.'.$file['ext'];$stored=$this->writePrivate($relative,$file['tmp_name'],(int)$file['size']);$saved=$stored['path'];
      $prior=null;foreach($exp['documentos'] as $d)if($d['tipo_documento']==='OFICIO_AUTORIZACION'){$prior=(int)$d['id_traspaso_documento'];break;}
      TraspasoModel::registrarDocumento((int)$id,'OFICIO_AUTORIZACION',str_replace(DIRECTORY_SEPARATOR,'/',$relative),$file['name'],$file['mime'],$stored['size'],$stored['hash'],(int)$user['id'],$prior,null,['numero_oficio'=>$file['numero'],'fecha_oficio'=>$file['fecha']]);$saved=null;
      Flasher::success('El oficio se agregó al expediente sin sobrescribir versiones anteriores.');
    }catch(Throwable $e){if($saved&&is_file($saved))@unlink($saved);Flasher::error($e->getMessage());}
    $this->volverAlDetalle((int)$id);
  }

  public function subir_archivo_documento($id=null)
  {
    $this->can('traspasos-subir-documento');$user=$this->requireActiveAccount();$saved=null;
    try{$this->validarPost();$documentoId=filter_var($_POST['documento_actual']??null,FILTER_VALIDATE_INT);if(!$documentoId)throw new RuntimeException('Selecciona el documento que deseas sustituir.');$file=$this->validarPdfDocumento($_FILES['archivo_documento']??null);$doc=TraspasoModel::documentoPorId((int)$documentoId);$types=['TARJETA_RESGUARDO','RESGUARDO_EQUIPO','BAJA_RESGUARDANTE'];if(!$doc||(int)$doc['id_traspaso']!==(int)$id||!in_array($doc['tipo_documento'],$types,true)||empty($doc['id_bien']))throw new RuntimeException('El archivo seleccionado no es un documento del expediente que pueda sustituirse.');$exp=TraspasoModel::detalle((int)$id);if(!$exp)throw new RuntimeException('No existe el expediente.');$relative=$exp['folio'].DIRECTORY_SEPARATOR.'reemplazo-'.bin2hex(random_bytes(16)).'.pdf';$stored=$this->writePrivate($relative,$file['tmp_name'],(int)$file['size']);$saved=$stored['path'];TraspasoModel::registrarDocumento((int)$id,(string)$doc['tipo_documento'],str_replace(DIRECTORY_SEPARATOR,'/',$relative),$file['name'],'application/pdf',$stored['size'],$stored['hash'],(int)$user['id'],(int)$documentoId,(int)$doc['id_bien']);$saved=null;Flasher::success('Se agregó el PDF como versión vigente; la versión anterior permanece en el historial.');}
    catch(Throwable $e){if($saved&&is_file($saved))@unlink($saved);Flasher::error($e->getMessage());}$this->volverAlDetalle((int)$id);
  }

  private function validarPdfDocumento($file):array
  {
    if(!$file||($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('Selecciona un archivo PDF.');$size=(int)($file['size']??0);if($size<1||$size>15*1024*1024)throw new RuntimeException('El archivo debe pesar como máximo 15 MB.');$name=basename((string)$file['name']);if(strtolower(pathinfo($name,PATHINFO_EXTENSION))!=='pdf')throw new RuntimeException('Solo se permiten archivos PDF.');$head=file_get_contents($file['tmp_name'],false,null,0,8);$handle=@fopen($file['tmp_name'],'rb');$tail='';if($handle){fseek($handle,max(0,$size-2048));$tail=(string)stream_get_contents($handle);fclose($handle);}if(!str_starts_with((string)$head,'%PDF-')||!str_contains($tail,'%%EOF'))throw new RuntimeException('El contenido del archivo no corresponde a un PDF válido.');$mime=class_exists(finfo::class)?(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']):null;if(is_string($mime)&&!in_array($mime,['application/pdf','application/x-pdf'],true))throw new RuntimeException('El tipo MIME del archivo no corresponde a PDF.');return ['tmp_name'=>$file['tmp_name'],'name'=>$name,'size'=>$size];
  }

  public function generar_documentos($id=null)
  {
    $this->can('traspasos-consultar');$user=$this->requireActiveAccount();
    try{$this->validarPost();if(!$this->hasPermission('traspasos-generar-tarjeta')||!$this->hasPermission('traspasos-generar-resguardo'))throw new RuntimeException('Se requieren permisos para actualizar ambos documentos obligatorios.');$ids=TraspasoDocumentService::generar((int)$id,(int)$user['id'],['TARJETA_RESGUARDO','RESGUARDO_EQUIPO']);Flasher::success(count($ids)?'Se actualizaron '.count($ids).' documentos. Las versiones anteriores y los documentos cargados manualmente se conservaron.':'Los documentos generados ya estaban actualizados; los documentos cargados manualmente se conservaron.');}
    catch(Throwable $e){Flasher::error($e->getMessage());}$this->volverAlDetalle((int)$id);
  }

  public function descargar_documento($documento=null)
  { $this->servirDocumento($documento,true); }

  public function ver_documento($documento=null)
  { $this->servirDocumento($documento,false); }

  private function servirDocumento($documento,bool $attachment):void
  {
    $this->can('traspasos-descargar-documentos');$doc=TraspasoModel::documentoPorId((int)$documento);
    if(!$doc){http_response_code(404);exit('Documento no encontrado.');}
    $verification=TraspasoDocumentService::verificarDocumento((string)$doc['ruta_interna'],(string)($doc['sha256']??''),isset($doc['tamano_bytes'])?(int)$doc['tamano_bytes']:null);
    if(!$verification['disponible']){$status=match($verification['estado']){'RUTA_INVALIDA'=>400,'AUSENTE'=>404,'ERROR_LECTURA'=>503,'HASH_INVALIDO','TAMANO_INVALIDO','NO_VERIFICABLE'=>409,default=>404};http_response_code($status);exit('Documento no disponible: '.$verification['mensaje']);}
    try{$path=$verification['ruta'];
      $name=str_replace(["\r","\n",'"'],'_',basename((string)$doc['nombre_original']));header('Content-Type: '.$doc['mime_type']);header('Content-Length: '.filesize($path));header("Content-Disposition: ".($attachment?'attachment':'inline').'; filename="'.$name.'"; filename*=UTF-8\'\''.rawurlencode($name));header('X-Content-Type-Options: nosniff');readfile($path);exit;
    }catch(Throwable $e){http_response_code(503);exit('Documento no disponible: no fue posible leer el archivo de forma segura.');}
  }

  public function revertir($id=null)
  {
    $this->adminOnly();$this->accionPost('traspasos-crear',function($uid)use($id){$new=TraspasoModel::crearReversion((int)$id,$uid,trim((string)($_POST['motivo']??'')));Flasher::success('Se creó el expediente compensatorio '.$new.'. Debe completar el flujo normal.');return 'traspasos/detalle/'.$new;},'',(int)$id);
  }

  protected function adminOnly():void
  { $user=$this->requireActiveAccount();if(($user['rol']??'')!=='admin'||!$this->hasPermission('admin-access',$user)){Flasher::error('Esta operación requiere el rol Administrador.');Redirect::to('traspasos');} }

  private function transferAuthorizerOnly():void
  { $user=$this->requireActiveAccount();if(($user['rol']??'')!=='admin'||!$this->hasPermission('admin-access',$user)||!$this->hasPermission('traspasos-autorizar',$user)){Flasher::error('La validación del oficio requiere el rol Administrador y el permiso correspondiente.');Redirect::to('traspasos');} }

  private function accionPost(string $permission,callable $action,string $success,int $routeId=0):void
  { $this->can($permission);$id=$routeId?:((int)($_POST['id_traspaso']??0));if(!$id)$id=(int)($_GET['id']??0);try{$this->validarPost();$user=$this->requireActiveAccount();$result=$action((int)$user['id']);if($success!=='')Flasher::success($success);if(is_string($result)&&$result!==''){Redirect::to($result);return;}}catch(Throwable $e){Flasher::error($e->getMessage());}$this->volverAlDetalle($id); }

  private function volverAlDetalle(int $id):void
  { $anchor=trim((string)($_POST['return_anchor']??''));if(!preg_match('/^[a-zA-Z0-9_-]{1,100}$/',$anchor))$anchor='';Redirect::to('traspasos/detalle/'.$id.($anchor!==''?'#'.rawurlencode($anchor):'')); }

  private function validarPost():void
  { if($_SERVER['REQUEST_METHOD']!=='POST')throw new RuntimeException('Método no permitido.');if(!Csrf::validate($_POST['csrf']??''))throw new RuntimeException('La solicitud no es válida. Actualiza la página.'); }

  private function idsBienPost():array
  { $raw=$_POST['bienes']??[];if(!is_array($raw))return[];return array_values(array_unique(array_map('intval',$raw))); }

  private function validarArchivoOficio($file):array
  {
    if(!$file||($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('Adjunta el oficio requerido.');
    $size=(int)($file['size']??0);if($size<1||$size>15*1024*1024)throw new RuntimeException('El archivo debe pesar como máximo 15 MB.');
    $name=basename((string)$file['name']);$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));if($ext!=='pdf')throw new RuntimeException('El oficio de autorización debe ser un archivo PDF.');
    $head=file_get_contents($file['tmp_name'],false,null,0,8);$handle=@fopen($file['tmp_name'],'rb');$tail='';if($handle){fseek($handle,max(0,$size-2048));$tail=(string)stream_get_contents($handle);fclose($handle);}if(!str_starts_with((string)$head,'%PDF-')||!str_contains($tail,'%%EOF'))throw new RuntimeException('El contenido del archivo no coincide con un PDF válido.');
    $detected=class_exists(finfo::class)?(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']):null;if(is_string($detected)&&!in_array($detected,['application/pdf','application/x-pdf'],true))throw new RuntimeException('El tipo MIME del archivo no corresponde a un PDF.');
    $mime='application/pdf';
    $numero=trim((string)($_POST['numero_oficio']??''));$fecha=trim((string)($_POST['fecha_oficio']??''));if($numero===''||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$fecha)||!checkdate((int)substr($fecha,5,2),(int)substr($fecha,8,2),(int)substr($fecha,0,4)))throw new RuntimeException('Captura el número y la fecha del oficio.');
    return ['tmp_name'=>$file['tmp_name'],'name'=>$name,'ext'=>$ext,'mime'=>$mime,'size'=>$size,'numero'=>$numero,'fecha'=>$fecha];
  }

  private function validarArchivoFirmado($file):array
  {
    if(!$file||($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('Adjunta el documento firmado digitalizado.');
    $size=(int)($file['size']??0);if($size<1||$size>15*1024*1024)throw new RuntimeException('El archivo debe pesar como máximo 15 MB.');
    $name=basename((string)$file['name']);$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));$allowed=['pdf','jpg','jpeg','png'];if(!in_array($ext,$allowed,true))throw new RuntimeException('La evidencia firmada debe ser PDF, JPG, JPEG o PNG.');
    $head=file_get_contents($file['tmp_name'],false,null,0,8);$valid=$ext==='pdf'?str_starts_with((string)$head,'%PDF-'):(in_array($ext,['jpg','jpeg'],true)?str_starts_with((string)$head,"\xFF\xD8\xFF"):$head==="\x89PNG\r\n\x1a\n");
    if(!$valid)throw new RuntimeException('El contenido del archivo no coincide con una evidencia documental permitida.');
    $mime=['pdf'=>'application/pdf','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png'][$ext];return ['tmp_name'=>$file['tmp_name'],'name'=>$name,'ext'=>$ext,'mime'=>$mime,'size'=>$size];
  }

  private function writePrivate(string $relative,string $source,int $size):array
  { return TraspasoDocumentService::guardarArchivoSubido($relative,$source,$size); }
}
