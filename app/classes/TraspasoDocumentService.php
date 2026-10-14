<?php

use Dompdf\Dompdf;
use Dompdf\Options;

/** Genera y almacena documentos de expediente en directorio privado. */
class TraspasoDocumentService
{
  public static function storageRoot(): string
  {
    $configured = getenv('BIENES_PRIVATE_STORAGE');
    return ($configured ?: dirname(dirname(ROOT)) . DIRECTORY_SEPARATOR . 'BienesInformaticosPrivate') . DIRECTORY_SEPARATOR . 'traspasos';
  }

  public static function absolutePath(string $relative): string
  {
    $verification = self::verificarDocumento($relative, null, null);
    if ($verification['estado'] !== 'NO_VERIFICABLE') throw new RuntimeException($verification['mensaje']);
    return $verification['ruta'];
  }

  /**
   * Comprueba un archivo sin modificar el expediente ni el almacenamiento.
   * La ruta siempre se resuelve dentro del directorio privado de traspasos.
   */
  public static function verificarDocumento(string $relative, ?string $hashEsperado, ?int $tamanoEsperado): array
  {
    $result = static function (string $estado, string $mensaje, ?string $ruta = null): array {
      return ['estado' => $estado, 'mensaje' => $mensaje, 'ruta' => $ruta, 'disponible' => $estado === 'DISPONIBLE'];
    };
    $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($relative));
    if ($relative === '' || str_contains($relative, '..') || str_starts_with($relative, DIRECTORY_SEPARATOR) || preg_match('/^[a-z]:/i', $relative)) {
      return $result('RUTA_INVALIDA', 'La ruta registrada no es válida dentro del almacenamiento privado.');
    }

    $root = realpath(self::storageRoot());
    if ($root === false || !is_dir($root) || !is_readable($root)) {
      return $result('ERROR_LECTURA', 'No fue posible acceder al almacenamiento privado de documentos.');
    }
    $candidate = $root . DIRECTORY_SEPARATOR . $relative;
    $parent = dirname($candidate);
    if (!is_dir($parent) || !file_exists($candidate)) {
      return $result('AUSENTE', 'El archivo físico no se encuentra en el almacenamiento privado.');
    }
    if (!is_readable($candidate) || !is_file($candidate)) {
      return $result('ERROR_LECTURA', 'El archivo existe, pero no se puede leer de forma segura.');
    }
    $path = realpath($candidate);
    if ($path === false || !str_starts_with(strtolower($path), strtolower($root . DIRECTORY_SEPARATOR))) {
      return $result('RUTA_INVALIDA', 'La ruta resuelta queda fuera del almacenamiento privado permitido.');
    }

    $size = filesize($path);
    if ($size === false) return $result('ERROR_LECTURA', 'No fue posible leer el tamaño del archivo.');
    if ($tamanoEsperado === null || $tamanoEsperado < 0 || !is_string($hashEsperado) || !preg_match('/^[a-f0-9]{64}$/i', $hashEsperado)) {
      return $result('NO_VERIFICABLE', 'El archivo existe, pero no cuenta con metadatos íntegros para verificarlo.', $path);
    }
    if ((int)$size !== $tamanoEsperado) {
      return $result('TAMANO_INVALIDO', 'El tamaño del archivo no coincide con el registro del expediente.', $path);
    }
    $hashActual = hash_file('sha256', $path);
    if ($hashActual === false) return $result('ERROR_LECTURA', 'No fue posible calcular la integridad del archivo.', $path);
    if (!hash_equals(strtolower($hashEsperado), strtolower($hashActual))) {
      return $result('HASH_INVALIDO', 'El hash del archivo no coincide con el registrado en el expediente.', $path);
    }
    return $result('DISPONIBLE', 'Archivo disponible e integridad verificada.', $path);
  }

  /** Resume la disponibilidad real sin exponer rutas privadas ni alterar registros. */
  public static function resumirDocumentos(array $documentos): array
  {
    $summary = ['total'=>0,'disponibles'=>0,'ausentes'=>0,'ruta_invalida'=>0,'integridad_inconsistente'=>0,'integridad_no_verificada'=>0,'error_lectura'=>0];
    foreach ($documentos as $documento) {
      $summary['total']++;
      $verification = self::verificarDocumento((string)($documento['ruta_interna'] ?? ''), (string)($documento['sha256'] ?? ''), isset($documento['tamano_bytes']) ? (int)$documento['tamano_bytes'] : null);
      match ($verification['estado']) {
        'DISPONIBLE' => $summary['disponibles']++,
        'AUSENTE' => $summary['ausentes']++,
        'RUTA_INVALIDA' => $summary['ruta_invalida']++,
        'HASH_INVALIDO', 'TAMANO_INVALIDO' => $summary['integridad_inconsistente']++,
        'NO_VERIFICABLE' => $summary['integridad_no_verificada']++,
        default => $summary['error_lectura']++,
      };
    }
    $summary['anomalias'] = $summary['total'] - $summary['disponibles'];
    return $summary;
  }

  /**
   * Persiste una carga HTTP en el almacenamiento privado y comprueba el archivo
   * resultante antes de que el controlador lo relacione con la base de datos.
   */
  public static function guardarArchivoSubido(string $relative, string $source, int $tamanoEsperado, ?callable $mover = null): array
  {
    $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($relative));
    if ($relative === '' || str_contains($relative, '..') || str_starts_with($relative, DIRECTORY_SEPARATOR) || preg_match('/^[a-z]:/i', $relative)) {
      throw new RuntimeException('Ruta documental inválida.');
    }
    if ($tamanoEsperado < 1) throw new RuntimeException('El archivo cargado no tiene un tamaño válido.');

    $root = self::storageRoot();
    if (!is_dir($root) && !mkdir($root, 0700, true) && !is_dir($root)) throw new RuntimeException('No se pudo preparar el almacenamiento privado.');
    if (!is_writable($root)) throw new RuntimeException('El almacenamiento privado no está disponible para recibir archivos.');
    $destination = $root . DIRECTORY_SEPARATOR . $relative;
    $directory = dirname($destination);
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new RuntimeException('No se pudo preparar el almacenamiento privado.');
    if (!is_writable($directory)) throw new RuntimeException('El almacenamiento privado no está disponible para recibir archivos.');
    if (file_exists($destination)) throw new RuntimeException('Ya existe un archivo con el identificador documental generado.');

    $move = $mover ?? static fn(string $from, string $to): bool => move_uploaded_file($from, $to);
    if (!$move($source, $destination)) throw new RuntimeException('No fue posible almacenar el archivo cargado.');
    try {
      $size = filesize($destination);
      if ($size === false || (int)$size !== $tamanoEsperado) throw new RuntimeException('El archivo almacenado no coincide con el tamaño recibido.');
      $hash = hash_file('sha256', $destination);
      if ($hash === false) throw new RuntimeException('No fue posible comprobar la integridad del archivo almacenado.');
      $verification = self::verificarDocumento($relative, $hash, $tamanoEsperado);
      if (!$verification['disponible']) throw new RuntimeException('No fue posible comprobar la persistencia del archivo almacenado.');
      return ['path' => $verification['ruta'], 'hash' => $hash, 'size' => (int)$size];
    } catch (Throwable $e) {
      if (is_file($destination)) @unlink($destination);
      throw $e;
    }
  }

  /** Genera un PDF por bien para cada tipo solicitado y lo asocia al expediente. */
  public static function generar(int $traspasoId, int $usuarioId, array $tipos = ['TARJETA_RESGUARDO','RESGUARDO_EQUIPO'], array $bajas = [], ?PDO $pdo = null, ?callable $archivoCreado = null): array
  {
    $exp = TraspasoModel::detalle($traspasoId, $pdo);
    if (!$exp || !in_array($exp['estado'], ['PENDIENTE_VALIDACION','VALIDADO'], true)) throw new RuntimeException('Solo se generan o actualizan documentos en un expediente activo y no completado.');
    if (!$exp['bienes']) throw new RuntimeException('El expediente no contiene bienes.');
    $creados=[];$formatHashes=[];$generatedDocs=[];$tarjetaPorBien=[];$resguardoEquipoPorBien=[];
    foreach($exp['eventos'] as $event){$meta=$event['metadatos']??[];if($event['tipo_evento']==='GENERACION_DOCUMENTO'){$documentId=(int)($meta['documento']??0);if($documentId){$generatedDocs[$documentId]=true;if(isset($meta['formato_hash']))$formatHashes[$documentId]=(string)$meta['formato_hash'];}}elseif($event['tipo_evento']==='EDICION_TARJETA_RESGUARDO'){$bienId=(int)($meta['bien']??0);if($bienId)$tarjetaPorBien[$bienId]=$meta['campos']??[];}elseif($event['tipo_evento']==='EDICION_RESGUARDO_EQUIPO'){$bienId=(int)($meta['bien']??0);if($bienId)$resguardoEquipoPorBien[$bienId]=$meta['campos']??[];}}
    foreach ($exp['bienes'] as $line) {
      $bien = BienModel::porId((int) $line['id_bien']);
      if (!$bien) throw new RuntimeException('No se encontró un bien del expediente.');
      $documentoBajaAnterior = !empty($line['id_resguardante_origen']) ? TraspasoModel::documentoGeneradoVigente($traspasoId,(int)$bien['id_bien'],'BAJA_RESGUARDANTE',$pdo) : null;
      $fechaEmision = null;
      if ($documentoBajaAnterior) foreach ($exp['documentos'] as $documento) {
        if ((int)$documento['id_traspaso_documento'] === $documentoBajaAnterior) { $fechaEmision = $documento['fecha_carga'] ?? null; break; }
      }
      $fechaBaja = $fechaEmision ?: ($exp['fecha_entrega'] ?: ($exp['fecha_completado'] ?: date('Y-m-d')));
      if (self::fechaCorta($fechaBaja) === '') $fechaBaja = date('Y-m-d');
      $resguardo = Model::query('SELECT * FROM resguardo WHERE id_bien=:id AND activo=1 LIMIT 1', ['id' => $bien['id_bien']]);
      $resguardo = $resguardo[0] ?? [];
      // Los formatos deben reflejar el destino propuesto, aunque la custodia
      // real solo cambia al completar el expediente.
      $bien['resguardante_origen_nombre'] = $line['resguardante_origen_nombre'] ?? null;
      $bien['resguardante_destino_nombre'] = $exp['resguardante_destino_nombre'];
      $bien['resguardante_nombre'] = $exp['resguardante_destino_nombre'];
      $bien['resguardante_csp'] = $exp['resguardante_destino_csp'];
      $bien['id_resguardante'] = (int) $exp['id_resguardante_destino'];
      $bien['resguardante_unidad_nombre'] = $exp['unidad_destino_nombre'];
      $bien['resguardante_codigo_ua'] = $exp['unidad_destino_codigo'];
      $bien['unidad_nombre'] = $exp['unidad_destino_nombre'];
      $bien['codigo_ua'] = $exp['unidad_destino_codigo'];
      $bien['traspaso_motivo'] = $exp['motivo'] ?? null;
      $bien['traspaso_observaciones'] = $exp['observaciones'] ?? null;
      $bien['jerarquia_administrativa'] = CatalogoModel::jerarquiaUnidad((int) $exp['id_unidad_destino']);
      $origenId=(int)($line['id_resguardante_origen']??0);$origen=$origenId?ResguardanteModel::porId($origenId):[];
      if($origen){$bien['resguardante_origen_nombre']=$origen['nombre_completo']??$bien['resguardante_origen_nombre'];$bien['resguardante_origen_csp']=$origen['csp']??($line['resguardante_origen_csp']??null);}
      else{$bien['resguardante_origen_csp']=$line['resguardante_origen_csp']??null;}
      $bien['fecha_documento']=$fechaBaja;
      $bien['fecha_retiro']=$fechaBaja;
      $camposBaja=self::camposBajaDesdeEventos($exp['eventos'],(int)$bien['id_bien'],(string)($exp['observaciones']??''));
      $bien['traspaso_observaciones']=$camposBaja['observaciones'];
      $bien['ccp_baja']=$camposBaja['ccp'];
      if (empty($line['ubicacion_sin_cambio']) && !empty($line['id_ubicacion_destino'])) {
        $bien['id_ubicacion'] = (int) $line['id_ubicacion_destino'];
        $bien['municipio'] = $line['municipio_destino'] ?? null;
        $bien['localidad'] = $line['localidad_destino'] ?? null;
        $bien['ubicacion_fisica'] = $line['ubicacion_fisica_destino'] ?? null;
      }
      $resguardo['fecha_asignacion'] = date('Y-m-d');
      $documents = [];
      if (in_array('TARJETA_RESGUARDO',$tipos,true)) {
        $previous=TraspasoModel::documentoGeneradoVigente($traspasoId,(int)$bien['id_bien'],'TARJETA_RESGUARDO',$pdo);
        if(!$previous||isset($generatedDocs[$previous])){$cardHtml=self::tarjeta($bien,$resguardo,$tarjetaPorBien[(int)$bien['id_bien']]??[]);$cardHash=hash('sha256',$cardHtml);if(!$previous||($formatHashes[$previous]??null)!==$cardHash)$documents['TARJETA_RESGUARDO']=['html'=>$cardHtml,'anterior'=>$previous,'metadata'=>['formato'=>'TARJETA_RESGUARDO_V1','formato_hash'=>$cardHash]];}
      }
      if (in_array('RESGUARDO_EQUIPO',$tipos,true)) {
        $previous=TraspasoModel::documentoGeneradoVigente($traspasoId,(int)$bien['id_bien'],'RESGUARDO_EQUIPO',$pdo);
        if(!$previous||isset($generatedDocs[$previous])){$equipmentHtml=self::resguardoEquipo($bien,$resguardo,$resguardoEquipoPorBien[(int)$bien['id_bien']]??[]);$equipmentHash=hash('sha256',$equipmentHtml);if(!$previous||($formatHashes[$previous]??null)!==$equipmentHash)$documents['RESGUARDO_EQUIPO']=['html'=>$equipmentHtml,'anterior'=>$previous,'metadata'=>['formato'=>'RESGUARDO_EQUIPO_INSTITUCIONAL_V1','formato_hash'=>$equipmentHash]];}
      }
      // Every bien in the expediente conserva el resguardante previo en
      // traspaso_bien. When that relationship exists, its baja documental is
      // mandatory and must not depend on a client checkbox.
      if (!empty($line['id_resguardante_origen'])) {
        if(!$documentoBajaAnterior||isset($generatedDocs[$documentoBajaAnterior])){$bajaHtml=self::bajaResguardante($bien);$formatHash=hash('sha256',$bajaHtml);if(!$documentoBajaAnterior||($formatHashes[$documentoBajaAnterior]??null)!==$formatHash)$documents['BAJA_RESGUARDANTE']=['html'=>$bajaHtml,'anterior'=>$documentoBajaAnterior,'metadata'=>['formato'=>'BAJA_INSTITUCIONAL_V1','formato_hash'=>$formatHash]];}
      }
      foreach ($documents as $tipo => $document) {
        $bytes = self::pdf($document['html']);
        $file = 'doc-' . bin2hex(random_bytes(18)) . '.pdf';
        $relative = $exp['folio'] . DIRECTORY_SEPARATOR . $file;
        $full = self::writePrivate($relative, $bytes);
        if ($archivoCreado !== null) $archivoCreado($full);
        $id = null;
        try {
          $id = TraspasoModel::generarDocumento($traspasoId, $tipo, str_replace(DIRECTORY_SEPARATOR, '/', $relative), $tipo . '-' . ($bien['clave_interna'] ?: $bien['id_bien']) . '.pdf', hash('sha256',$bytes), strlen($bytes), $usuarioId, (int) $bien['id_bien'], $document['anterior']??null, $document['metadata']??[], $pdo);
        } catch (Throwable $e) { @unlink($full); throw $e; }
        $creados[] = $id;
      }
    }
    return $creados;
  }

  private static function writePrivate(string $relative, string $bytes): string
  {
    $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
    $full = self::storageRoot() . DIRECTORY_SEPARATOR . $relative;
    $dir = dirname($full);
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) throw new RuntimeException('No se pudo preparar el almacenamiento privado.');
    $handle = fopen($full, 'xb');
    if (!$handle) throw new RuntimeException('No se pudo crear el documento sin sobrescribir un archivo existente.');
    try { if (fwrite($handle, $bytes) !== strlen($bytes)) throw new RuntimeException('No se pudo guardar el PDF completo.'); }
    finally { fclose($handle); }
    return $full;
  }

  private static function pdf(string $html): string
  {
    $options = new Options(); $options->set('defaultFont','Arial'); $options->set('isRemoteEnabled',false);
    $styles='';$html=preg_replace_callback('/<style\b[^>]*>.*?<\/style>/is',static function($match)use(&$styles){$styles.=$match[0];return '';},$html)??$html;
    $pdf = new Dompdf($options); $pdf->loadHtml('<!doctype html><html lang="es"><head><meta charset="UTF-8">'.$styles.'</head><body>' . $html . '</body></html>','UTF-8');
    $pdf->setPaper('letter','portrait'); $pdf->render(); return $pdf->output();
  }

  private static function e($v): string { return htmlspecialchars(trim((string)($v ?? '')) ?: '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

  /** Formatea un nivel oficial sin desplazar ni completar niveles ausentes. */
  private static function nivelAdministrativo(array $jerarquia, string $campo): string
  {
    $indices=['secretaria'=>0,'subsecretaria'=>1,'direccion_secretaria'=>2,'direccion_area'=>3,'subdireccion'=>5,'departamento'=>6,'oficina'=>7];
    $detalle=$jerarquia['niveles_detalle'][$indices[$campo]??-1]??[];
    $codigo=trim((string)($detalle['codigo_ua']??''));$nombre=trim((string)($detalle['nombre']??''));
    return $codigo!==''&&$nombre!==''?$codigo.' - '.$nombre:'';
  }

  /** Mapea los datos del bien, la UA destino y el resguardo a los numerales oficiales. */
  public static function camposTarjeta(array $b,array $r,array $overrides=[]):array
  {
    $u=$b['jerarquia_administrativa']??[];$values=[
      'inventario'=>$b['numero_inventario']??null,'nic_cea'=>$b['nic_cea']??null,
      'secretaria'=>self::nivelAdministrativo($u,'secretaria'),'subsecretaria'=>self::nivelAdministrativo($u,'subsecretaria'),'direccion'=>self::nivelAdministrativo($u,'direccion_secretaria'),
      'direccion_area'=>self::nivelAdministrativo($u,'direccion_area'),'subdireccion'=>self::nivelAdministrativo($u,'subdireccion'),'departamento'=>self::nivelAdministrativo($u,'departamento'),
      'oficina'=>self::nivelAdministrativo($u,'oficina'),'codigo_ua'=>$b['codigo_ua']??null,'activo_generico'=>$b['activo_generico_nombre']??null,
      'grupo_activo'=>$b['grupo_nombre']??null,'activo_especifico'=>$b['activo_especifico_nombre']??null,'nombre_bien'=>$b['nombre_bien']??null,
      'material'=>$b['material_nombre']??$b['material']??null,'marca'=>$b['marca_nombre']??null,'modelo'=>$b['modelo_nombre']??null,
      'color'=>$b['color_nombre']??$b['color']??null,'estado_uso'=>$b['estado_nombre']??null,'serie'=>$b['numero_serie']??null,
      'caracteristicas'=>$b['caracteristicas']??null,'observaciones'=>$b['traspaso_observaciones']??null,
      'fecha_alta'=>$b['fecha_alta']??null,'fecha_adquisicion'=>$b['fecha_adquisicion']??null,'fecha_elaboracion'=>$b['fecha_elaboracion']??null,
      'fecha_asignacion'=>$r['fecha_asignacion']??$b['fecha_asignacion_actual']??null,
      'valor'=>isset($b['valor'])&&$b['valor']!==''?'$'.number_format((float)$b['valor'],2):null,
      'ubicacion_fisica'=>'PISO: '.trim((string)($b['piso']??'')).'   SECCIÓN/ALA: '.trim((string)($b['seccion_ala']??'')).'   CUBÍCULO: '.trim((string)($b['cubiculo']??'')),'municipio'=>$b['municipio']??null,'localidad'=>$b['localidad']??null,
    ];
    foreach(['fecha_alta','fecha_adquisicion','fecha_elaboracion','fecha_asignacion'] as $key)if(!empty($values[$key])&&strtotime((string)$values[$key])!==false)$values[$key]=date('d/m/Y',strtotime((string)$values[$key]));
    if(array_key_exists('observaciones',$overrides))$values['observaciones']=trim((string)$overrides['observaciones']);
    return $values;
  }

  /** Valores prellenados para el editor sin alterar los registros del inventario. */
  public static function camposTarjetaDesdeExpediente(array $exp,array $line,array $doc):array
  {
    $b=BienModel::porId((int)$line['id_bien']);if(!$b)return [];
    $b['jerarquia_administrativa']=CatalogoModel::jerarquiaUnidad((int)$exp['id_unidad_destino']);$b['codigo_ua']=$exp['unidad_destino_codigo']??null;$b['traspaso_observaciones']=$exp['observaciones']??null;
    if(empty($line['ubicacion_sin_cambio'])&&!empty($line['id_ubicacion_destino'])){$b['municipio']=$line['municipio_destino']??null;$b['localidad']=$line['localidad_destino']??null;$b['ubicacion_fisica']=$line['ubicacion_fisica_destino']??null;}
    return self::camposTarjeta($b,['fecha_asignacion'=>substr((string)($doc['fecha_carga']??''),0,10)]);
  }

  private static function tarjeta(array $b,array $r,array $overrides=[]):string
  {
    $f=self::camposTarjeta($b,$r,$overrides);$e=static function($v):string{$value=trim((string)($v??''));return $value===''?'':htmlspecialchars(mb_strtoupper($value,'UTF-8'),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');};
    $cell=static fn(string $key,string $label,int $span=4):string=>'<td colspan="'.$span.'"><span class="field-label">'.$label.'</span><div class="field-value">'.$e($f[$key]??null).'</div></td>';
    $rows=[];$rows[]='<tr><th scope="row" colspan="3">INVENTARIO</th><td colspan="3">'.$e($f['inventario']).'</td><th scope="row" colspan="3">NIC / CEA</th><td colspan="3">'.$e($f['nic_cea']).'</td></tr>';
    foreach([['secretaria','1) SECRETARÍA'],['subsecretaria','2) SUBSECRETARÍA'],['direccion','3) DIRECCIÓN'],['direccion_area','4) DIRECCIÓN DE ÁREA'],['subdireccion','5) SUBDIRECCIÓN'],['departamento','6) DEPARTAMENTO']] as [$key,$label])$rows[]='<tr>'.$cell($key,$label,12).'</tr>';
    $rows[]='<tr>'.$cell('oficina','7) OFICINA',6).$cell('codigo_ua','8) CÓDIGO DE UNIDAD ADMINISTRATIVA',6).'</tr>';
    $rows[]='<tr>'.$cell('activo_generico','9) ACTIVO GENÉRICO',12).'</tr>';
    $rows[]='<tr>'.$cell('grupo_activo','10) GRUPO DEL ACTIVO',6).$cell('activo_especifico','11) ACTIVO ESPECÍFICO',6).'</tr>';
    $rows[]='<tr>'.$cell('nombre_bien','12) NOMBRE DEL BIEN',12).'</tr>';
    $rows[]='<tr>'.$cell('material','13) MATERIAL').$cell('marca','14) MARCA').$cell('modelo','15) MODELO').'</tr>';
    $rows[]='<tr>'.$cell('color','16) COLOR').$cell('estado_uso','17) ESTADO DE USO').$cell('serie','18) SERIE').'</tr>';
    $rows[]='<tr>'.$cell('caracteristicas','19) CARACTERÍSTICAS (MATRÍCULA O REGISTRO)',12).'</tr>';
    $rows[]='<tr>'.$cell('observaciones','20) OBSERVACIONES',12).'</tr>';
    $rows[]='<tr>'.$cell('fecha_alta','21) FECHA DE ALTA',6).$cell('fecha_adquisicion','22) FECHA DE ADQUISICIÓN',6).'</tr>';
    $rows[]='<tr>'.$cell('fecha_elaboracion','23) FECHA DE ELABORACIÓN',6).$cell('fecha_asignacion','24) FECHA DE ASIGNACIÓN',6).'</tr>';
    $rows[]='<tr>'.$cell('valor','25) VALOR',12).'</tr>';
    $rows[]='<tr>'.$cell('ubicacion_fisica','26) UBICACIÓN FÍSICA DEL BIEN',6).$cell('municipio','27) MUNICIPIO',3).$cell('localidad','28) LOCALIDAD',3).'</tr>';
    $body='<h1 class="card-title">TARJETA DE RESGUARDO</h1><table class="card-grid" aria-label="Tarjeta de resguardo del bien"><colgroup>'.str_repeat('<col>',12).'</colgroup><tbody>'.implode('',$rows).'</tbody></table>';
    $body.='<p class="legal-note"> NOTA: La firma y la fecha de quien suscribe es el responsable de la custodia y buen uso del bien que ampara.</p><table class="signature-grid" aria-label="Fecha y firma del resguardatario"><tbody><tr><td><div class="signature-top">&nbsp;</div><div class="signature-line"></div><div class="signature-label">FECHA<br><span>DD/MM/AAAA</span></div></td><td><div class="signature-top"><span class="signature-name">'.$e($b['resguardante_destino_nombre']??$b['resguardante_nombre']??null).'</span></div><div class="signature-line"></div><div class="signature-label">NOMBRE Y FIRMA DEL RESGUARDATARIO</div></td></tr></tbody></table>';
    $membrete=self::imageData('MembreteOficialiaMayor.jpg');
    return '<style>*{box-sizing:border-box}@page{size:Letter portrait;margin:25mm 25mm}body{font:7.5pt Arial,Helvetica,sans-serif;color:#111;margin:0;line-height:1.08}.page-letterhead{position:fixed;top:-25mm;left:-25mm;width:215.9mm;height:279.4mm;z-index:-1}.card-title{text-align:center;font-size:14pt;margin:10mm 0 3mm;font-weight:normal}.card-grid{width:100%;border-collapse:collapse;table-layout:fixed;border:1px solid #000}.card-grid td,.card-grid th{border:1px solid #000;padding:1mm 1.5mm;vertical-align:top;overflow-wrap:anywhere}.card-grid th{text-align:left;font-weight:normal}.field-label{display:block;font-size:6.5pt;font-weight:normal;margin-bottom:.4mm}.field-value{min-height:3mm;white-space:pre-wrap;font-size:7pt}.legal-note{font-size:7.5pt;margin:3mm 0 1.5mm}.signature-grid{width:100%;border-collapse:collapse;table-layout:fixed;margin-top:2mm}.signature-grid td{width:50%;border:0;text-align:center;vertical-align:top;padding:0 5mm;font-size:7.5pt}.signature-top{height:12mm;text-align:center;padding-top:7mm}.signature-name{display:block;position:relative; top:8mm;padding-bottom:0mm}.signature-line{border-top:1px solid #000;height:0}.signature-label{margin-top:.5mm;line-height:1.15;text-align:center}.signature-label span{font-size:6.5pt}</style><img class="page-letterhead" src="'.$membrete.'" alt="">'.$body;
  }

  public static function camposResguardoEquipo(array $b,array $r,array $overrides=[]): array
  {
    if(array_key_exists('resguardante_notas',$b))$notas=(string)$b['resguardante_notas'];
    else{$notes=Model::query('SELECT notas FROM resguardante WHERE id_resguardante=:id LIMIT 1',['id'=>$b['id_resguardante']??0]);$notas=(string)($notes[0]['notas']??'');}
    $defaults=[
      'notas'=>$notas,
      'tipo_equipo'=>(string)($r['tipo_equipo']??$b['tipo_equipo']??''),
      'estado_equipo'=>(string)($r['estado_equipo']??$b['estado_equipo']??''),
      'observaciones'=>(string)($r['observaciones']??$b['traspaso_observaciones']??''),
      'candado'=>(string)($r['candado']??''),
    ];
    foreach(['notas','tipo_equipo','estado_equipo','observaciones','candado'] as $key)if(array_key_exists($key,$overrides))$defaults[$key]=(string)$overrides[$key];
    foreach(['notas','observaciones'] as $key)$defaults[$key]=str_replace(["\r\n","\r"],"\n",$defaults[$key]);
    foreach(['tipo_equipo','estado_equipo'] as $key)$defaults[$key]=trim($defaults[$key]);
    $candado=strtoupper(trim($defaults['candado']));$defaults['candado']=$candado===''?'':(in_array($candado,['SI','SÍ','1','YES'],true)?'SI':'NO');
    return $defaults;
  }

  private static function resguardoEquipo(array $b,array $r,array $overrides=[]): string
  {
    $componentes=[];
    foreach(($b['componentes']??[]) as $c)$componentes[strtoupper((string)($c['tipo_componente']??''))]=$c;
    $clave=trim((string)($b['clave_interna']??''));if($clave!==''&&!str_starts_with(strtoupper($clave),'CI-'))$clave='CI-'.$clave;
    $editable=self::camposResguardoEquipo($b,$r,$overrides);
    $nombreResguardante=trim((string)($b['resguardante_nombre']??''));
    if($nombreResguardante!==''){
      $nombreResguardante=mb_convert_case(mb_strtolower($nombreResguardante,'UTF-8'),MB_CASE_TITLE,'UTF-8');
      $nombreResguardante=preg_replace_callback('/\b(?:De|Del|La|Las|Los|Y)\b/u',static fn($match)=>mb_strtolower($match[0],'UTF-8'),$nombreResguardante)??$nombreResguardante;
    }
    $fields=[
      'Clave interna'=>$clave,'Nombre del S.P.'=>$nombreResguardante,'C.S.P.'=>$b['resguardante_csp']??null,
      'Notas'=>$editable['notas'],'Tipo de equipo'=>$editable['tipo_equipo'],
      'Estado del equipo'=>$editable['estado_equipo'],'Observaciones'=>$editable['observaciones'],
      'Candado'=>$editable['candado']===''?null:($editable['candado']==='SI'?'Sí':'No'),
    ];
    $componentRows=[];
    foreach(['CPU','CARGADOR','MONITOR','MOUSE','TECLADO','UPS'] as $type){$c=$componentes[$type]??[];$exists=!empty($c['id_componente']);$value=static fn($key)=>trim((string)($c[$key]??''))?:'N/A';$componentRows[]=['componente'=>$type,'marca'=>$exists?$value('marca'):'N/A','modelo'=>$exists?$value('modelo'):'N/A','serie'=>$exists?$value('numero_serie'):'N/A','inventario'=>$exists?(trim((string)($c['numero_inventario']??''))?:'XXXXXXXXXX'):'N/A'];}
    $e=static fn($value)=>htmlspecialchars(trim((string)($value??'')),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    $membrete=self::imageData('MembreteResguardo.jpg');
    $template=dirname(__DIR__,2).DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR.'resguardo-equipo.php';
    if(!is_file($template))throw new RuntimeException('No se encontró la plantilla HTML del Resguardo de equipo.');
    ob_start();require $template;return (string)ob_get_clean();
  }

  /** Recupera la última edición documental de la baja para un bien. */
  public static function camposBajaDesdeEventos(array $eventos,int $bienId,string $observacionesPredeterminadas=''):array
  {
    $campos=['ccp'=>'','observaciones'=>str_replace(["\r\n","\r"],"\n",$observacionesPredeterminadas)];
    foreach($eventos as $evento){if(($evento['tipo_evento']??'')!=='EDICION_CCP_BAJA')continue;$meta=$evento['metadatos']??[];if((int)($meta['bien']??0)!==$bienId)continue;$campos['ccp']=str_replace(["\r\n","\r"],"\n",(string)($meta['ccp']??''));if(array_key_exists('observaciones',$meta))$campos['observaciones']=str_replace(["\r\n","\r"],"\n",(string)$meta['observaciones']);}
    return $campos;
  }

  private static function bajaResguardante(array $b): string
  {
    $componentes=[];foreach(($b['componentes']??[]) as $component)$componentes[strtoupper((string)($component['tipo_componente']??''))]=$component;
    $esc=static fn($value)=>self::e($value);
    $date=self::fechaCorta($b['fecha_documento']??null);
    if($date==='')throw new RuntimeException('No se pudo determinar una fecha válida para emitir la baja de resguardante.');
    $dateLong=self::fechaInstitucional($b['fecha_documento']??null);
    $personName=trim((string)($b['resguardante_origen_nombre']??''));
    if($personName!==''){$personName=preg_replace('/^C\.\s*/iu','',$personName)??$personName;$person='<strong class="document-emphasis">C. '.$esc($personName).'</strong>';}
    else{$person='<span class="editable-orange">[Nombre del servidor público pendiente de captura]</span>';}
    $observations=trim((string)($b['traspaso_observaciones']??''));
    $observationsHtml=$observations!==''?'<div class="observations-box">'.nl2br($esc($observations)).'</div>':'<div class="observations-box observations-space">&nbsp;</div>';
    $monitor=$componentes['MONITOR']??[];$cpu=$componentes['CPU']??[];$keyboard=$componentes['TECLADO']??[];$mouse=$componentes['MOUSE']??[];$ups=$componentes['UPS']??[];$charger=$componentes['CARGADOR']??[];
    $profile=trim((string)($b['perfil']??''));
    $lastColumn=[];if(!empty($ups['numero_serie']))$lastColumn[]=$esc($ups['numero_serie']);if(!empty($charger['numero_serie']))$lastColumn[]=$esc($charger['numero_serie']);
    $internalKey=trim((string)($b['clave_interna']??''));
    $table='<table class="equipment"><thead><tr><th>Perfil</th><th>Marca</th><th>Número de Inventario</th><th>Número de Monitor</th><th>Número de Serie CPU</th><th>Número de Serie Teclado</th><th>Número de Serie Mouse</th><th>Número de Serie UPS / Cargador</th></tr></thead><tbody><tr>';
    $cells=[$profile?:'—',$b['marca_nombre']??null,$internalKey,$monitor['numero_inventario']??null,$cpu['numero_serie']??null,$keyboard['numero_serie']??null,$mouse['numero_serie']??null,implode('<br>',$lastColumn)];
    foreach($cells as $index=>$value){$table.='<td>'.($index===7?($value!==''?$value:'—'):$esc($value?:'—')).'</td>';}$table.='</tr></tbody></table>';
    $ccp=trim((string)($b['ccp_baja']??''));
    $ccpEntries=[];
    foreach(preg_split('/\R/u',$ccp)?:[] as $ccpLine){$ccpLine=trim($ccpLine);if($ccpLine==='')continue;if(preg_match('/^(.+?)(\s+(?:—|–|-)\s+.+)$/u',$ccpLine,$parts))$ccpEntries[]='<div class="ccp-entry"><strong>'.$esc($parts[1]).'</strong>'.$esc($parts[2]).'</div>';else $ccpEntries[]='<div class="ccp-entry"><strong>'.$esc($ccpLine).'</strong></div>';}
    $ccpHtml=$ccpEntries?'<div class="ccp"><strong class="ccp-label">C. c. p.</strong><div class="ccp-value">'.implode('',$ccpEntries).'</div></div>':'';
    $content='<p class="year-legend">&quot;2026. Año del Humanismo Mexicano en el Estado de México&quot;</p><div class="institutional-address"><div><strong>Secretaría de Cultura y Turismo</strong></div><div><strong>Unidad de Información, Planeación, Programación y Evaluación</strong></div><div><strong>Departamento de Tecnologías de la Información</strong></div><p class="document-date">Toluca, Estado de México a '.$esc($dateLong).'.</p></div>
      <div class="document-title"><div>FORMATO</div><div>&quot;BAJA DE RESGUARDANTE DE EQUIPOS DE CÓMPUTO&quot;</div></div>
      <p class="intro">Anteponiendo un cordial saludo, me es grato dirigirme a usted, con la finalidad de informarle que con fecha de <strong class="document-date-inline">'.$esc($date).'</strong>, el personal del Departamento de Tecnologías de la Información adscrito a la Unidad de Información, Planeación, Programación y Evaluación de esta Secretaría, llevó a cabo el retiro del siguiente equipo de cómputo:</p>
      '.$table.'
      <p class="body-copy">Lo anterior, con el objeto de dar por <strong class="document-emphasis">concluido el periodo de resguardante</strong> del equipo de cómputo en mención con el servidor público '.$person.', toda vez que a partir de esta fecha el equipo de cómputo estará resguardado en el Departamento de Tecnologías de la Información hasta que se determine su reasignación. <br> <br>No omito mencionar que, el equipo de cómputo en referencia presenta las siguientes observaciones:</p>
      <p class="observations-intro"><strong> </strong></p>'.$observationsHtml.'
      <p class="closing">Sin otro particular por el momento, me reitero a sus apreciables órdenes.</p>
      <table class="signatures"><tbody><tr><td><strong>RECIBE</strong><span class="signature-space">&nbsp;</span><strong>FIRMA DEL PERSONAL ADSCRITO AL<br>DEPARTAMENTO DE TECNOLOGÍAS DE LA INFORMACIÓN</strong></td><td><strong>ENTREGA</strong><span class="signature-space">&nbsp;</span><strong>FIRMA DE CONFORMIDAD<br>DEL SERVIDOR PÚBLICO<br>QUE ENTREGA EL EQUIPO</strong></td></tr></tbody></table>
      '.$ccpHtml.'
      <div class="institutional-footer"><div class="institutional-footer-address">Bulevar Jesús Reyes Heroles número 302, delegación San Buenaventura, C. P. 50110, Toluca, Estado de México.</div><div class="institutional-footer-phones">Teléfonos: 722 274 13 15 y 722 274 11 77.</div></div>';
    return self::baseInstitucional($content);
  }

  private static function fechaCorta($value):string
  { $date=trim((string)$value);if($date===''||strtotime($date)===false)return '';return date('d/m/Y',strtotime($date)); }

  private static function fechaInstitucional($value):string
  { $date=trim((string)$value);if($date===''||strtotime($date)===false)return '[Fecha pendiente de captura]';$months=[1=>'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];$timestamp=strtotime($date);return date('j',$timestamp).' de '.$months[(int)date('n',$timestamp)].' de '.date('Y',$timestamp); }

  private static function baseInstitucional(string $body):string
  {
    $background=self::imageData('FondoMembretado.jpeg');
    $style='<style>
      *{box-sizing:border-box}
      @page{size:Letter;margin:0}
      html,body{margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;color:#2d3748;font-size:9.5pt;line-height:1.28}
      .page-background{position:fixed;top:0;left:7.95mm;width:200mm;height:259.5mm;z-index:0}
      .institutional-page{position:relative;z-index:1;margin:25mm 25mm;padding:10mm 0 0}
      .year-legend{text-align:center;margin:0 0 4mm;font-size:10pt;font-weight:normal}
      .institutional-address{width:72%;margin:0 0 6mm auto;text-align:right;font-size:9pt;overflow-wrap:break-word;word-wrap:break-word}
      .document-date{margin:2mm 0 0;font-weight:normal}
      .document-title{text-align:center;color:#1e293b;font-size:9pt;font-weight:bold;line-height:1.25;margin:3mm 0 6mm}
      .document-title div{margin:0}
      .intro,.body-copy,.observations-intro,.closing{margin:0 0 3mm;text-align:justify}
      .document-date-inline{color:#000;font-weight:bold}
      .document-emphasis{color:#000;font-weight:bold}
      .equipment{width:100%;table-layout:fixed;border-collapse:collapse;margin:4mm 0 5mm;font-size:6.4pt;line-height:1.13;overflow-wrap:break-word;background:transparent}
      .equipment th{background:#F2E4E4;color:#000;font-weight:normal;text-align:center;font-size:10pt;line-height:1.05;padding:1.3mm .65mm;border:.35pt solid #64748b;vertical-align:middle;overflow-wrap:break-word;white-space:normal;word-wrap:break-word}
      .equipment td{background:transparent;color:#2d3748;font-size:9.7pt;font-weight:normal;text-align:center;padding:1.4mm .65mm;border:.35pt solid #94a3b8;vertical-align:middle;overflow-wrap:break-word;white-space:normal;word-wrap:break-word}
      .equipment tr{page-break-inside:avoid}
      .observations-intro{margin:2mm 0 1.5mm}
      .observations-box{width:auto;margin-left:0;margin-right:0;min-height:11.5mm;border:.8pt solid #64748b;background:transparent;padding:3mm;text-align:left;white-space:pre-wrap;overflow-wrap:break-word}
      .observations-space{min-height:11.5mm}
      .editable-orange{color:#c2410c;font-weight:bold}
      .editable-purple{color:#6b21a8;font-weight:bold}
      .signatures{width:100%;table-layout:fixed;border-collapse:collapse;border:none;margin:5mm 0 6mm}
      .signatures td{width:50%;border:none;text-align:center;vertical-align:top;padding:0 4mm;font-size:7.8pt}
      .signature-space{display:block;height:9mm}
      .ccp{display:table;width:100%;font-size:6.5pt;line-height:1.05;margin:4mm 0 2mm;text-align:left}.ccp-label,.ccp-value{display:table-cell;vertical-align:top}.ccp-label{width:8mm;white-space:nowrap}.ccp-value{padding-left:.5mm}.ccp-entry{margin:0 0 .3mm}
      .institutional-footer{position:fixed;left:25mm;right:25mm;bottom:25mm;font-size:8.6pt;line-height:1.3;margin:0}
      .institutional-footer-address{text-align:justify;text-align-last:justify}
      .institutional-footer-phones{text-align:center;margin-top:.8mm;font-size:7.8pt}
      .institutional-page table{page-break-inside:avoid}
    </style>';
    return $style.'<img class="page-background" src="'.$background.'" alt=""><main class="institutional-page">'.$body.'</main>';
  }

  private static function imageData(string $filename):string
  {
    $path=dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.$filename;
    $bytes=is_file($path)?file_get_contents($path):false;if($bytes===false)throw new RuntimeException('No se encontró o no se pudo leer el membrete assets/images/'.$filename.'.');
    return 'data:image/jpeg;base64,'.base64_encode($bytes);
  }
}
