<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once ROOT . 'app/vendor/autoload.php';
require_once ROOT . 'app/classes/TraspasoDocumentService.php';

function bajaExpect(bool $condition, string $message): void
{
  if (!$condition) throw new RuntimeException($message);
}

$events = [
  ['tipo_evento'=>'EDICION_CCP_BAJA','metadatos'=>['bien'=>7,'ccp'=>'ANTERIOR','observaciones'=>'Anterior']],
  ['tipo_evento'=>'EDICION_CCP_BAJA','metadatos'=>['bien'=>7,'ccp'=>"MARÍA ELENA GARCÍA — TITULAR\nJUAN PÉREZ LÓPEZ — JEFE",'observaciones'=>"Equipo funcional.\nAccesorios completos."]],
];
$saved = TraspasoDocumentService::camposBajaDesdeEventos($events, 7, 'Observación general');
bajaExpect($saved['ccp'] === $events[1]['metadatos']['ccp'] && $saved['observaciones'] === $events[1]['metadatos']['observaciones'], 'La última edición de la baja no se recuperó correctamente.');

$bien = [
  'fecha_documento'=>'2026-10-13','resguardante_origen_nombre'=>'C. PERSONA DE PRUEBA','traspaso_observaciones'=>$saved['observaciones'],
  'perfil'=>'DESARROLLADOR DE SISTEMAS','marca_nombre'=>'DELL','numero_inventario'=>'10C027611G','clave_interna'=>'CI-000002','ccp_baja'=>$saved['ccp'],
  'componentes'=>[
    ['tipo_componente'=>'MONITOR','numero_inventario'=>'MON-000002'],['tipo_componente'=>'CPU','numero_serie'=>'CPU-ASDF1234'],
    ['tipo_componente'=>'TECLADO','numero_serie'=>'TEC-QWER5678'],['tipo_componente'=>'MOUSE','numero_serie'=>'MOU-ZXCV9012'],
    ['tipo_componente'=>'UPS','numero_serie'=>'UPS-112233'],['tipo_componente'=>'CARGADOR','numero_serie'=>'ASDFGHJNKM'],
  ],
];
$htmlMethod = new ReflectionMethod(TraspasoDocumentService::class, 'bajaResguardante');
$htmlMethod->setAccessible(true);
$html = (string)$htmlMethod->invoke(null, $bien);
foreach (['background:#F2E4E4;color:#000;font-weight:normal','font-size:10pt','font-size:9.7pt;font-weight:normal','height:9mm','font-size:6.5pt;line-height:1.05','width:8mm','padding-left:.5mm','margin:5mm 0 6mm','width:auto;margin-left:0;margin-right:0'] as $expected) bajaExpect(str_contains($html, $expected), 'Falta el estilo de baja: '.$expected);
bajaExpect(substr_count($html, 'class="document-emphasis"') === 2 && str_contains($html, '.document-emphasis{color:#000;font-weight:bold}'), 'Los dos textos destacados no conservan negrita negra específica.');
bajaExpect((bool)preg_match('/<table class="signatures">(.*?)<\/table>/s', $html, $signatures), 'No se encontró el bloque de firmas.');
bajaExpect(!str_contains($signatures[1], 'PERSONA DE PRUEBA'), 'El nombre dinámico todavía aparece debajo de ENTREGA.');

$view = (string)file_get_contents(ROOT . 'templates/views/traspasos/detalleView.twig');
bajaExpect(substr_count($view, 'name="campos_baja[') === 2, 'La baja debe exponer exactamente dos campos editables.');
foreach (['name="campos_baja[ccp]"','name="campos_baja[observaciones]"'] as $field) bajaExpect(str_contains($view, $field), 'Falta el campo editable '.$field.'.');

$model = (string)file_get_contents(ROOT . 'app/models/TraspasoModel.php');
$controller = (string)file_get_contents(ROOT . 'app/controllers/traspasosController.php');
bajaExpect(str_contains($model, 'actualizarCamposBaja') && str_contains($model, "'observaciones'=>\$observaciones"), 'La persistencia de Observaciones no quedó registrada en el evento.');
bajaExpect(str_contains($controller, "TraspasoDocumentService::generar((int)\$id,\$uid,['BAJA_RESGUARDANTE'])"), 'La edición no regenera exclusivamente la Baja de Resguardante.');

$pdfMethod = new ReflectionMethod(TraspasoDocumentService::class, 'pdf');
$pdfMethod->setAccessible(true);
$pdf = (string)$pdfMethod->invoke(null, $html);
preg_match_all('/\/Type\s*\/Page\b/', $pdf, $pages);
bajaExpect(count($pages[0]) === 1, 'La Baja de Resguardante debe conservar una página.');
$output = ROOT . 'output' . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR . 'baja_resguardante_prueba.pdf';
if (!is_dir(dirname($output))) mkdir(dirname($output), 0775, true);
file_put_contents($output, $pdf);

echo "OK BajaResguardanteLayoutTest\n{$output}\n";
