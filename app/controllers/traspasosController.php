<?php

class traspasosController extends InventoryController implements ControllerInterface
{
  public function index()
  {
    $this->can('traspasos-consultar');
    $filters=[];foreach(['q','folio','tipo','estado','desde','hasta','unidad_origen','unidad_destino','resguardante_destino'] as $key)$filters[$key]=trim((string)($_GET[$key]??''));
    $this->setTitle('Traspasos');$this->renderInventory('index',['traspasos'=>TraspasoModel::listar($filters),'filtros'=>$filters,'unidades'=>CatalogoModel::unidadesConJerarquia(),'can_create_transfer'=>$this->hasPermission('traspasos-crear')]);
  }

  public function crear()
  {
    $this->can('traspasos-crear');
    if($_SERVER['REQUEST_METHOD']==='POST'){
      $id=null;$saved=null;
      try{$this->validarPost();$user=$this->requireActiveAccount();$tipo=(string)($_POST['tipo']??'');$oficio=$tipo==='ENTRE_UNIDADES'?$this->validarArchivoOficio($_FILES['oficio']??null):null;
        $id=TraspasoModel::crear($_POST,$this->idsBienPost(),(int)$user['id']);
        if($oficio){$exp=TraspasoModel::detalle($id);$relative=$exp['folio'].DIRECTORY_SEPARATOR.'oficio-'.bin2hex(random_bytes(16)).'.'.$oficio['ext'];$full=$this->writePrivate($relative,$oficio['tmp_name']);$saved=$full;
          TraspasoModel::registrarDocumento($id,'OFICIO_AUTORIZACION',str_replace(DIRECTORY_SEPARATOR,'/',$relative),$oficio['name'],$oficio['mime'],$oficio['size'],hash_file('sha256',$full),(int)$user['id'],null,null,['numero_oficio'=>$oficio['numero'],'fecha_oficio'=>$oficio['fecha']]);$saved=null;}
        Flasher::success($tipo==='MISMA_UNIDAD'?'Se creó el expediente en etapa de documentos.':'Se creó el expediente pendiente de autorización.');Redirect::to('traspasos/detalle/'.$id);
      }catch(Throwable $e){if($saved&&is_file($saved))@unlink($saved);if($id){try{TraspasoModel::cancelar((int)$id,(int)(get_user('id')?:0),'No se pudo asociar el oficio obligatorio.');}catch(Throwable $ignored){}}Flasher::error($e->getMessage());Redirect::to('traspasos/crear');}
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
    $permisos=[];foreach(['modificar','cancelar','subir-documento','autorizar','rechazar','ejecutar','confirmar-entrega','confirmar-recepcion','generar-tarjeta','generar-resguardo','descargar-documentos'] as $name)$permisos[$name]=$this->hasPermission('traspasos-'.$name);
    $evidencias=[];$metadata=[];foreach($exp['eventos'] as $event){$meta=$event['metadatos']??[];if(in_array($event['tipo_evento'],['CARGA_DOCUMENTO','CARGA_DOCUMENTO_FIRMADO'],true))$metadata[(int)($meta['documento']??0)]=$meta;if($event['tipo_evento']==='CARGA_DOCUMENTO_FIRMADO')$evidencias[(int)($meta['documento']??0)]=$event;}
    $exp['evidencias_documento']=$evidencias;$labels=[];foreach($exp['bienes'] as $item){$labels[(int)$item['id_bien']]=trim((string)$item['clave_interna'].' · '.(string)$item['nombre_bien'],' ·');}$documentoDesde=1;foreach($exp['eventos'] as $event)if($event['tipo_evento']==='EDICION_DATOS_DOCUMENTO')$documentoDesde=max($documentoDesde,(int)($event['metadatos']['documento_desde']??1));$latest=[];foreach($exp['documentos'] as &$doc){$doc['metadata']=$metadata[(int)$doc['id_traspaso_documento']]??[];$doc['bien_label']=$labels[(int)($doc['id_bien']??0)]??'';$doc['obsoleto']=(int)$doc['id_traspaso_documento']<$documentoDesde;$doc['evidencia_firmada']=isset($evidencias[(int)$doc['id_traspaso_documento']]);if(!$doc['obsoleto']&&!empty($doc['id_bien'])){$key=$doc['id_bien'].'|'.$doc['tipo_documento'];if(!isset($latest[$key])||(int)$doc['id_traspaso_documento']>(int)$latest[$key]['id_traspaso_documento'])$latest[$key]=$doc;} }unset($doc);
    $ready=count($latest)===(count($exp['bienes'])*2);$complete=$ready;foreach($latest as $doc)if(empty($doc['evidencia_firmada']))$complete=false;
    $exp['documentos_obligatorios_generados']=$ready;$exp['documentos_vigentes']=array_values($latest);$exp['listo_para_completar']=$complete;$exp['puede_editar_datos_documento']=in_array($exp['estado'],['DOCUMENTOS','FIRMA'],true)&&!$evidencias;
    $user=$this->requireActiveAccount();$isAdmin=in_array(($user['rol']??''),['admin','developer'],true)&&$this->hasPermission('admin-access',$user);
    $this->setTitle('Detalle de traspaso');$this->renderInventory('detalle',['traspaso'=>$exp,'transfer_permissions'=>$permisos,'is_transfer_admin'=>$isAdmin]);
  }

  public function editar($id=null)
  {
    $this->can('traspasos-modificar');$user=$this->requireActiveAccount();
    try{$this->validarPost();$current=TraspasoModel::detalle((int)$id);if(!$current)throw new RuntimeException('No existe el expediente.');$payload=['tipo'=>$current['tipo'],'id_unidad_origen'=>$current['id_unidad_origen'],'id_unidad_destino'=>$current['id_unidad_destino'],'id_resguardante_destino'=>$current['id_resguardante_destino'],'motivo'=>trim((string)($_POST['motivo']??'')),'observaciones'=>trim((string)($_POST['observaciones']??''))];$lines=array_column($current['bienes'],'id_bien');TraspasoModel::actualizarBorrador((int)$id,$payload,$lines,(int)$user['id']);Flasher::success('Se actualizaron los datos editables del borrador.');}
    catch(Throwable $e){Flasher::error($e->getMessage());}
    Redirect::to('traspasos/detalle/'.(int)$id);
  }

  public function editar_datos_documentos($id=null)
  { $this->accionPost('traspasos-modificar',function($uid)use($id){TraspasoModel::actualizarDatosDocumento((int)$id,(string)($_POST['motivo']??''),(string)($_POST['observaciones']??''),$uid);},'Se actualizaron los datos. Regenera los documentos para que reflejen la versión actualizada.',(int)$id); }

  public function enviar($id=null)
  { $this->accionPost('traspasos-modificar',function($uid)use($id){TraspasoModel::enviar((int)$id,$uid);},'El expediente pasó a la siguiente etapa.',(int)$id); }

  public function autorizar($id=null)
  { $this->adminOnly();$this->accionPost('traspasos-autorizar',function($uid)use($id){TraspasoModel::autorizar((int)$id,$uid);},'Traspaso autorizado.',(int)$id); }

  public function rechazar($id=null)
  { $this->adminOnly();$this->accionPost('traspasos-rechazar',function($uid)use($id){TraspasoModel::rechazar((int)$id,$uid,trim((string)($_POST['motivo']??'')));},'Traspaso rechazado.',(int)$id); }

  public function cancelar($id=null)
  { $this->accionPost('traspasos-cancelar',function($uid)use($id){TraspasoModel::cancelar((int)$id,$uid,trim((string)($_POST['motivo']??'')));},'Traspaso cancelado.',(int)$id); }

  public function confirmar_entrega($id=null)
  { $this->accionPost('traspasos-confirmar-entrega',function($uid)use($id){TraspasoModel::confirmarEntrega((int)$id,$uid,(string)($_POST['tipo_entrega']??''));},'La entrega quedó registrada.',(int)$id); }

  public function confirmar_recepcion($id=null)
  { $this->accionPost('traspasos-confirmar-recepcion',function($uid)use($id){TraspasoModel::confirmarRecepcion((int)$id,$uid);},'La recepción se registró y el traspaso se completó.',(int)$id); }

  public function firmar_documento($id=null)
  { $transferId=(int)($_POST['id_traspaso']??0);$this->accionPost('traspasos-ejecutar',static function(){throw new RuntimeException('La firma no se registra con una confirmación en pantalla. Carga la evidencia documental firmada.');},'', $transferId); }

  public function subir_documento_firmado($id=null)
  {
    $this->can('traspasos-subir-documento');$user=$this->requireActiveAccount();$saved=null;
    try{$this->validarPost();$documentoOrigen=filter_var($_POST['documento_origen']??null,FILTER_VALIDATE_INT);if(!$documentoOrigen)throw new RuntimeException('Selecciona el documento generado cuya evidencia firmada se cargará.');$file=$this->validarArchivoFirmado($_FILES['documento_firmado']??null);
      $exp=TraspasoModel::detalle((int)$id);if(!$exp)throw new RuntimeException('No existe el expediente.');
      $relative=$exp['folio'].DIRECTORY_SEPARATOR.'firmado-'.$documentoOrigen.'-'.bin2hex(random_bytes(16)).'.'.$file['ext'];$full=$this->writePrivate($relative,$file['tmp_name']);$saved=$full;
      TraspasoModel::registrarDocumentoFirmado((int)$id,$documentoOrigen,str_replace(DIRECTORY_SEPARATOR,'/',$relative),$file['name'],$file['mime'],$file['size'],hash_file('sha256',$full),(int)$user['id']);$saved=null;
      Flasher::success('La evidencia documental firmada se agregó al expediente sin sobrescribir versiones anteriores.');
    }catch(Throwable $e){if($saved&&is_file($saved))@unlink($saved);Flasher::error($e->getMessage());}
    Redirect::to('traspasos/detalle/'.(int)$id);
  }

  public function completar($id=null)
  { $this->accionPost('traspasos-ejecutar',function($uid)use($id){TraspasoModel::completarDocumentado((int)$id,$uid);},'El traspaso se completó y se registró en el histórico de movimientos.',(int)$id); }

  public function subir_documento($id=null)
  {
    $this->can('traspasos-subir-documento');$user=$this->requireActiveAccount();$file=$_FILES['oficio']??null;$saved=null;
    try{$this->validarPost();$file=$this->validarArchivoOficio($file);
      $exp=TraspasoModel::detalle((int)$id);if(!$exp)throw new RuntimeException('No existe el expediente.');
      $relative=$exp['folio'].DIRECTORY_SEPARATOR.'oficio-'.bin2hex(random_bytes(16)).'.'.$file['ext'];$full=$this->writePrivate($relative,$file['tmp_name']);$saved=$full;
      $prior=null;foreach($exp['documentos'] as $d)if($d['tipo_documento']==='OFICIO_AUTORIZACION'){$prior=(int)$d['id_traspaso_documento'];break;}
      TraspasoModel::registrarDocumento((int)$id,'OFICIO_AUTORIZACION',str_replace(DIRECTORY_SEPARATOR,'/',$relative),$file['name'],$file['mime'],$file['size'],hash_file('sha256',$full),(int)$user['id'],$prior,null,['numero_oficio'=>$file['numero'],'fecha_oficio'=>$file['fecha']]);$saved=null;
      Flasher::success('El oficio se agregó al expediente sin sobrescribir versiones anteriores.');
    }catch(Throwable $e){if($saved&&is_file($saved))@unlink($saved);Flasher::error($e->getMessage());}
    Redirect::to('traspasos/detalle/'.(int)$id);
  }

  public function generar_documentos($id=null)
  {
    $this->can('traspasos-consultar');$user=$this->requireActiveAccount();
    try{$this->validarPost();if(!$this->hasPermission('traspasos-generar-tarjeta')||!$this->hasPermission('traspasos-generar-resguardo'))throw new RuntimeException('Se requieren permisos para generar ambos documentos obligatorios.');$ids=TraspasoDocumentService::generar((int)$id,(int)$user['id']);Flasher::success('Se generaron '.count($ids).' documentos y se asociaron a los bienes del expediente.');}
    catch(Throwable $e){Flasher::error($e->getMessage());}Redirect::to('traspasos/detalle/'.(int)$id);
  }

  public function descargar_documento($documento=null)
  { $this->servirDocumento($documento,true); }

  public function ver_documento($documento=null)
  { $this->servirDocumento($documento,false); }

  private function servirDocumento($documento,bool $attachment):void
  {
    $this->can('traspasos-descargar-documentos');$doc=TraspasoModel::documentoPorId((int)$documento);
    if(!$doc){http_response_code(404);exit('Documento no encontrado.');}
    try{$path=TraspasoDocumentService::absolutePath((string)$doc['ruta_interna']);if(!hash_equals((string)$doc['sha256'],hash_file('sha256',$path)))throw new RuntimeException('La integridad del documento no pudo verificarse.');
      $name=str_replace(["\r","\n",'"'],'_',basename((string)$doc['nombre_original']));header('Content-Type: '.$doc['mime_type']);header('Content-Length: '.filesize($path));header("Content-Disposition: ".($attachment?'attachment':'inline').'; filename="'.$name.'"; filename*=UTF-8\'\''.rawurlencode($name));header('X-Content-Type-Options: nosniff');readfile($path);exit;
    }catch(Throwable $e){http_response_code(404);exit('Documento no disponible.');}
  }

  public function revertir($id=null)
  {
    $this->adminOnly();$this->accionPost('traspasos-crear',function($uid)use($id){$new=TraspasoModel::crearReversion((int)$id,$uid,trim((string)($_POST['motivo']??'')));Flasher::success('Se creó el expediente compensatorio '.$new.'. Debe completar el flujo normal.');return 'traspasos/detalle/'.$new;},'',(int)$id);
  }

  protected function adminOnly():void
  { $user=$this->requireActiveAccount();if(!in_array((string)($user['rol']??''),['admin','developer'],true)||!$this->hasPermission('admin-access',$user)){Flasher::error('Esta operación requiere el rol Administrador.');Redirect::to('traspasos');} }

  private function accionPost(string $permission,callable $action,string $success,int $routeId=0):void
  { $this->can($permission);$id=$routeId?:((int)($_POST['id_traspaso']??0));if(!$id)$id=(int)($_GET['id']??0);try{$this->validarPost();$user=$this->requireActiveAccount();$result=$action((int)$user['id']);if($success!=='')Flasher::success($success);if(is_string($result)&&$result!==''){Redirect::to($result);return;}}catch(Throwable $e){Flasher::error($e->getMessage());}Redirect::to('traspasos/detalle/'.$id); }

  private function validarPost():void
  { if($_SERVER['REQUEST_METHOD']!=='POST')throw new RuntimeException('Método no permitido.');if(!Csrf::validate($_POST['csrf']??''))throw new RuntimeException('La solicitud no es válida. Actualiza la página.'); }

  private function idsBienPost():array
  { $raw=$_POST['bienes']??[];if(!is_array($raw))return[];return array_values(array_unique(array_map('intval',$raw))); }

  private function validarArchivoOficio($file):array
  {
    if(!$file||($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('Adjunta el oficio requerido.');
    $size=(int)($file['size']??0);if($size<1||$size>15*1024*1024)throw new RuntimeException('El archivo debe pesar como máximo 15 MB.');
    $name=basename((string)$file['name']);$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));if($ext!=='pdf')throw new RuntimeException('El oficio de autorización debe ser un archivo PDF.');
    $head=file_get_contents($file['tmp_name'],false,null,0,8);if(!str_starts_with((string)$head,'%PDF-'))throw new RuntimeException('El contenido del archivo no coincide con un PDF válido.');
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

  private function writePrivate(string $relative,string $source):string
  { $relative=str_replace(['/', '\\'],DIRECTORY_SEPARATOR,$relative);if(str_contains($relative,'..')||str_starts_with($relative,DIRECTORY_SEPARATOR))throw new RuntimeException('Ruta documental inválida.');$root=TraspasoDocumentService::storageRoot();$dir=dirname($root.DIRECTORY_SEPARATOR.$relative);if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('No se pudo crear el almacenamiento privado.');$dest=$root.DIRECTORY_SEPARATOR.$relative;if(!move_uploaded_file($source,$dest))throw new RuntimeException('No se pudo almacenar el oficio.');return $dest; }
}
