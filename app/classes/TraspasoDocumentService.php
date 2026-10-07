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
    $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
    if ($relative === '' || str_contains($relative, '..') || str_starts_with($relative, DIRECTORY_SEPARATOR)) throw new RuntimeException('Ruta documental inválida.');
    $root = realpath(self::storageRoot());
    if ($root === false) throw new RuntimeException('No existe el almacenamiento privado de documentos.');
    $path = realpath($root . DIRECTORY_SEPARATOR . $relative);
    if ($path === false || !str_starts_with(strtolower($path), strtolower($root . DIRECTORY_SEPARATOR))) throw new RuntimeException('El documento no está disponible.');
    return $path;
  }

  /** Genera un PDF por bien para cada tipo solicitado y lo asocia al expediente. */
  public static function generar(int $traspasoId, int $usuarioId, array $tipos = ['TARJETA_RESGUARDO','RESGUARDO_EQUIPO']): array
  {
    $exp = TraspasoModel::detalle($traspasoId);
    if (!$exp || !in_array($exp['estado'], ['DOCUMENTOS','FIRMA','COMPLETADO'], true)) throw new RuntimeException('Solo se generan documentos después de la autorización y antes de completar el traspaso.');
    if (!$exp['bienes']) throw new RuntimeException('El expediente no contiene bienes.');
    $creados = [];
    foreach ($exp['bienes'] as $line) {
      $bien = BienModel::porId((int) $line['id_bien']);
      if (!$bien) throw new RuntimeException('No se encontró un bien del expediente.');
      $resguardo = Model::query('SELECT * FROM resguardo WHERE id_bien=:id AND activo=1 LIMIT 1', ['id' => $bien['id_bien']]);
      $resguardo = $resguardo[0] ?? [];
      // Los formatos deben reflejar el destino propuesto, aunque la custodia
      // real solo cambia al completar el expediente.
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
      if (empty($line['ubicacion_sin_cambio']) && !empty($line['id_ubicacion_destino'])) {
        $bien['id_ubicacion'] = (int) $line['id_ubicacion_destino'];
        $bien['municipio'] = $line['municipio_destino'] ?? null;
        $bien['localidad'] = $line['localidad_destino'] ?? null;
      }
      $resguardo['fecha_asignacion'] = date('Y-m-d');
      $documents = [];
      if (in_array('TARJETA_RESGUARDO',$tipos,true)) $documents['TARJETA_RESGUARDO'] = self::tarjeta($bien, $resguardo);
      if (in_array('RESGUARDO_EQUIPO',$tipos,true)) $documents['RESGUARDO_EQUIPO'] = self::resguardoEquipo($bien, $resguardo);
      foreach ($documents as $tipo => $html) {
        $bytes = self::pdf($html);
        $file = 'doc-' . bin2hex(random_bytes(18)) . '.pdf';
        $relative = $exp['folio'] . DIRECTORY_SEPARATOR . $file;
        $full = self::writePrivate($relative, $bytes);
        $id = null;
        try {
          $id = TraspasoModel::generarDocumento($traspasoId, $tipo, str_replace(DIRECTORY_SEPARATOR, '/', $relative), $tipo . '-' . ($bien['clave_interna'] ?: $bien['id_bien']) . '.pdf', hash('sha256',$bytes), strlen($bytes), $usuarioId, (int) $bien['id_bien']);
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
    $pdf = new Dompdf($options); $pdf->loadHtml('<!doctype html><html lang="es"><meta charset="UTF-8"><body>' . $html . '</body></html>','UTF-8');
    $pdf->setPaper('A4','portrait'); $pdf->render(); return $pdf->output();
  }

  private static function e($v): string { return htmlspecialchars(trim((string)($v ?? '')) ?: '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

  private static function tarjeta(array $b,array $r): string
  {
    $jerarquia = $b['jerarquia_administrativa'] ?? [];
    $ruta = $jerarquia['ruta'] ?? [];
    $rows = '';
    $labels=['SECRETARIA'=>'Secretaría','SUBSECRETARIA'=>'Subsecretaría','STAFF'=>'STAFF','DIRECCION'=>'Dirección','DIRECCION_AREA'=>'Dirección de área','SUBDIRECCION'=>'Subdirección','DEPARTAMENTO'=>'Departamento','OFICINA'=>'Oficina'];
    foreach ($ruta as $n) $rows .= '<tr><th>' . self::e($labels[$n['tipo'] ?? ''] ?? ($n['tipo_nombre'] ?? 'Unidad')) . '</th><td>' . self::e($n['nombre'] ?? '') . (!empty($n['codigo_ua']) ? ' <small>(' . self::e($n['codigo_ua']) . ')</small>' : '') . '</td></tr>';
    $fields = [
      'Número de inventario' => $b['numero_inventario'] ?? null, 'NIC/CEA' => $b['nic_cea'] ?? null,
      'Código UA' => $b['codigo_ua'] ?? null, 'Unidad administrativa' => $b['unidad_nombre'] ?? null,
      'Activo genérico' => $b['activo_generico_nombre'] ?? null, 'Grupo' => $b['grupo_nombre'] ?? null,
      'Activo específico' => $b['activo_especifico_nombre'] ?? null, 'Bien' => $b['nombre_bien'] ?? null,
      'Material' => $b['material_nombre'] ?? null, 'Marca' => $b['marca_nombre'] ?? null, 'Modelo' => $b['modelo_nombre'] ?? null,
      'Color' => $b['color_nombre'] ?? null, 'Estado de uso' => $b['estado_nombre'] ?? null, 'Serie' => $b['numero_serie'] ?? null,
      'Características' => $b['caracteristicas'] ?? null, 'Observaciones' => $b['observaciones'] ?? null,
      'Fecha de alta' => $b['fecha_alta'] ?? null, 'Fecha de adquisición' => $b['fecha_adquisicion'] ?? null,
      'Fecha de elaboración' => $b['fecha_elaboracion'] ?? null, 'Fecha de asignación' => $r['fecha_asignacion'] ?? ($b['fecha_asignacion_actual'] ?? null),
      'Valor MXN' => isset($b['valor']) ? '$' . number_format((float)$b['valor'],2) : null,
      'Ubicación física' => $b['ubicacion_fisica'] ?? null, 'Municipio' => $b['municipio'] ?? null, 'Localidad' => $b['localidad'] ?? null,
      'Piso' => $b['piso'] ?? null, 'Sección/Ala' => $b['seccion_ala'] ?? null, 'Cubículo' => $b['cubiculo'] ?? null,
      'Resguardante' => $b['resguardante_nombre'] ?? null, 'C.S.P.' => $b['resguardante_csp'] ?? null,
      'Motivo del traspaso' => $b['traspaso_motivo'] ?? null, 'Observaciones del traspaso' => $b['traspaso_observaciones'] ?? null,
    ];
    foreach ($fields as $label=>$value) $rows .= '<tr><th>' . self::e($label) . '</th><td>' . self::e($value) . '</td></tr>';
    return self::base('Tarjeta de Resguardo', '<table>' . $rows . '</table><div class="signature">Firma del resguardante</div>');
  }

  private static function resguardoEquipo(array $b,array $r): string
  {
    $componentes = [];
    foreach (($b['componentes'] ?? []) as $c) $componentes[strtoupper((string)$c['tipo_componente'])] = $c;
    $notes = Model::query('SELECT notas FROM resguardante WHERE id_resguardante=:id LIMIT 1', ['id' => $b['id_resguardante'] ?? 0]);
    $body = '<table><tr><th>Dato</th><th>Valor</th></tr>';
    foreach (['Clave interna'=>'clave_interna','Nombre'=>'resguardante_nombre','C.S.P.'=>'resguardante_csp','Notas'=>'notas_resguardante','Tipo de equipo'=>'tipo_equipo','Estado'=>'estado_equipo','Observaciones'=>'observaciones_resguardo'] as $label=>$field) {
      $value = match($field){'resguardante_nombre'=>$b['resguardante_nombre']??null,'resguardante_csp'=>$b['resguardante_csp']??null,'notas_resguardante'=>$notes[0]['notas']??null,'observaciones_resguardo'=>$r['observaciones']??null,default=>$r[$field]??$b[$field]??null};
      if($field==='clave_interna'&&$value&&!str_starts_with(strtoupper((string)$value),'CI-'))$value='CI-' . $value;
      $body .= '<tr><th>' . self::e($label) . '</th><td>' . self::e($value) . '</td></tr>';
    }
    $body .= '<tr><th>Candado</th><td>' . self::e(in_array(strtoupper((string)($r['candado']??'')),['SI','SÍ','1','YES'],true)?'Sí':(!empty($r['candado'])?'No':'N/A')) . '</td></tr></table><h2>Componentes</h2><table><tr><th>Componente</th><th>Modelo</th><th>Marca</th><th>Serie</th><th>Inventario</th></tr>';
    foreach(['CPU','CARGADOR','MONITOR','MOUSE','TECLADO','UPS'] as $type){$c=$componentes[$type]??[];$vals=[$type,$c['modelo']??'N/A',$c['marca']??'N/A',$c['numero_serie']??'N/A',$c['numero_inventario']??'N/A'];$body.='<tr>'.implode('',array_map(static fn($v)=>'<td>'.self::e($v).'</td>',$vals)).'</tr>';}
    $body .= '</table><p><strong>Motivo del traspaso:</strong> '.self::e($b['traspaso_motivo']??null).'</p><p><strong>Observaciones:</strong> '.self::e($b['traspaso_observaciones']??null).'</p><div class="signature">Entrega: firma ____________________ &nbsp;&nbsp; Recibe: firma ____________________</div>';
    return self::base('Resguardo del Equipo',$body);
  }

  private static function base(string $title,string $body):string
  { return '<style>body{font:10px Arial,sans-serif;color:#202124}h1{color:#680000;border-bottom:3px solid #D4AF37;padding:0 0 8px}h2{font-size:13px;color:#680000;margin-top:18px}table{width:100%;border-collapse:collapse;margin:10px 0}th,td{border:1px solid #bbb;text-align:left;padding:5px;vertical-align:top}th{background:#f1f1f1;width:28%}.signature{margin-top:55px;padding-top:10px;border-top:1px solid #555;text-align:center}</style><h1>'.self::e($title).'</h1>'.$body; }
}
