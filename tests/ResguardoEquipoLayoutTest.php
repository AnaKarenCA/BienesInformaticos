<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once ROOT . 'app/vendor/autoload.php';
require_once ROOT . 'app/classes/TraspasoDocumentService.php';

function resguardoEquipoAssert(bool $condition, string $message): void
{
  if (!$condition) throw new RuntimeException($message);
}

$components = [
  ['id_componente'=>1,'tipo_componente'=>'CPU','marca'=>'DELL','modelo'=>'PRO MAX 1','numero_serie'=>'3PSJGFVBNJHGF','numero_inventario'=>'INV-CPU-001'],
  ['id_componente'=>2,'tipo_componente'=>'CARGADOR','marca'=>'DELL','modelo'=>'65W','numero_serie'=>'CARG-001','numero_inventario'=>null],
];
foreach(['MONITOR','MOUSE','TECLADO','UPS'] as $type)$components[]=['tipo_componente'=>$type,'marca'=>null,'modelo'=>null,'numero_serie'=>null,'numero_inventario'=>null];
$bien=[
  'clave_interna'=>'000321','resguardante_nombre'=>'PERSONA DE PRUEBA','resguardante_csp'=>'123456789',
  'resguardante_notas'=>'Dirección original','componentes'=>$components,
];
$resguardo=['tipo_equipo'=>'Escritorio','estado_equipo'=>'Completo','observaciones'=>'Observación original','candado'=>'NO'];
$captura=['notas'=>"Edificio principal\nPiso 2",'tipo_equipo'=>'Estación de trabajo','estado_equipo'=>'Incompleto','observaciones'=>"Primera línea\nSegunda línea",'candado'=>'SI'];

$campos=TraspasoDocumentService::camposResguardoEquipo($bien,$resguardo,$captura);
resguardoEquipoAssert($campos===$captura,'Caso 4: los cinco valores capturados no se recuperaron sin cambios.');

$method=new ReflectionMethod(TraspasoDocumentService::class,'resguardoEquipo');$method->setAccessible(true);
$html=$method->invoke(null,$bien,$resguardo,$captura);
resguardoEquipoAssert(str_contains($html,'DELL')&&str_contains($html,'PRO MAX 1')&&str_contains($html,'3PSJGFVBNJHGF')&&str_contains($html,'INV-CPU-001'),'Caso 1: el componente completo no muestra sus valores reales.');
resguardoEquipoAssert(str_contains($html,'CARG-001')&&str_contains($html,'XXXXXXXXXX'),'Caso 2: el componente existente sin inventario no usa XXXXXXXXXX.');
resguardoEquipoAssert(substr_count($html,'N/A')>=16,'Caso 3: los componentes inexistentes no muestran N/A en sus cuatro datos.');
resguardoEquipoAssert(str_contains($html,'Persona de Prueba'),'El nombre del servidor público no alterna mayúscula inicial y minúsculas.');
resguardoEquipoAssert(str_contains($html,'.data-grid thead th,.components thead th{background:#800000;color:#fff;font-weight:bold}')&&str_contains($html,'.data-grid tbody tr:nth-child(odd)>*,.components tbody tr:nth-child(odd)>*{background:#ededed;color:#111}'),'Caso 6: encabezados o filas alternadas incorrectos.');
resguardoEquipoAssert(str_contains($html,'.intro{margin:0 0 1.5mm;text-align:justify;font-size:8.5pt;')&&!str_contains($html,'<strong>CI-000321</strong>'),'Caso 5: el acuerdo no quedó en 8.5pt o el control interno sigue en negritas.');
resguardoEquipoAssert(str_contains($html,'.components{table-layout:auto;word-wrap:break-word;word-break:break-word}')&&str_contains($html,'max-width:100%'),'La tabla de componentes no usa ajuste automático dentro del ancho disponible.');
resguardoEquipoAssert(str_contains($html,'.section-heading{text-align:center;font-weight:bold;margin:3mm 0 2.5mm;font-size:9.8pt;')&&str_contains($html,'.alpha li{margin:0 0 1.5mm;text-align:justify;font-size:8.7pt;line-height:1.45}'),'El bloque de Disposiciones no aumentó exactamente 0.3pt.');
resguardoEquipoAssert(str_contains($html,'.page--one .roman li:last-child{page-break-inside:avoid;page-break-after:avoid}'),'El inciso VIII no tiene protección específica contra división de página.');
resguardoEquipoAssert(str_contains($html,"Primera línea<br>\nSegunda línea"),'Caso 4: las observaciones no conservan saltos de línea.');

$view=(string)file_get_contents(ROOT.'templates/views/traspasos/detalleView.twig');
foreach(['notas','tipo_equipo','estado_equipo','observaciones','candado'] as $field)resguardoEquipoAssert(str_contains($view,'name="campos_resguardo_equipo['.$field.']"'),'Caso 4: falta el control editable '.$field.'.');
resguardoEquipoAssert(substr_count($view,'name="campos_resguardo_equipo[')===5,'Caso 4: la interfaz expone más de cinco campos editables.');
resguardoEquipoAssert(str_contains($view,'<select class="form-select form-select-sm"')&&str_contains($view,'name="campos_resguardo_equipo[candado]"'),'Caso 4: Candado no es un dropdown.');
resguardoEquipoAssert(str_contains($view,'data-equipment-state-select')&&str_contains($view,'value="Completo"')&&str_contains($view,'value="Incompleto"')&&str_contains($view,'value="__custom__"'),'Estado del equipo no ofrece Completo, Incompleto y Escribir.');

$pdfMethod=new ReflectionMethod(TraspasoDocumentService::class,'pdf');$pdfMethod->setAccessible(true);
$outputDir=ROOT.'output'.DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR;if(!is_dir($outputDir))mkdir($outputDir,0775,true);
$cases=[];
$cases['componentes_cortos']=$components;
$cases['componentes_largos']=[
  ['id_componente'=>100,'tipo_componente'=>'CPU','marca'=>'DELL','modelo'=>'MODELO PROFESIONAL DE ALTO RENDIMIENTO','numero_serie'=>'CPU-000001','numero_inventario'=>'INV-CPU-001'],
  ['id_componente'=>101,'tipo_componente'=>'CARGADOR','marca'=>'DELL','modelo'=>'65W USB-C','numero_serie'=>'CARG-000001','numero_inventario'=>'INV-CARG-001'],
  ['id_componente'=>102,'tipo_componente'=>'MONITOR','marca'=>'DELL','modelo'=>'P2422H','numero_serie'=>'MON-000001','numero_inventario'=>'INV-MON-001'],
  ['id_componente'=>103,'tipo_componente'=>'MOUSE','marca'=>'LOGITECH','modelo'=>'M100','numero_serie'=>'MOU-000001','numero_inventario'=>'INV-MOU-001'],
  ['id_componente'=>104,'tipo_componente'=>'TECLADO','marca'=>'DELL','modelo'=>'KB216','numero_serie'=>'TEC-000001','numero_inventario'=>'INV-TEC-001'],
  ['id_componente'=>105,'tipo_componente'=>'UPS','marca'=>'APC','modelo'=>'BACK-UPS','numero_serie'=>'UPS-000001','numero_inventario'=>'INV-UPS-001'],
];
$cases['componentes_maximo_real']=[
  ['id_componente'=>7,'tipo_componente'=>'CPU','modelo'=>'MODELO PRO MAX','marca'=>'DELL','numero_serie'=>'SERIESADSSD','numero_inventario'=>'CI-00000147852458'],
  ['id_componente'=>14,'tipo_componente'=>'CARGADOR','modelo'=>'MODELOCARGADOR','marca'=>'MARCACARGADOR','numero_serie'=>'SERIECARGADOR123456','numero_inventario'=>'CI-20261007CARGADOR'],
  ['id_componente'=>15,'tipo_componente'=>'MONITOR','modelo'=>'MODELOMONITOR','marca'=>'MARCAMONITOR','numero_serie'=>'SERIEMONITOR123456','numero_inventario'=>'CI-2025841MONITOR'],
  ['id_componente'=>16,'tipo_componente'=>'MOUSE','modelo'=>'MODELOMOUSE','marca'=>'MARCAMOUSE','numero_serie'=>'SERIEMOUSE123456','numero_inventario'=>'CI-20261007MOUSE'],
  ['id_componente'=>17,'tipo_componente'=>'TECLADO','modelo'=>'MODELOTECLADO','marca'=>'MARCATECLADO','numero_serie'=>'SERIETECLADO123456','numero_inventario'=>'CI-20261007TECLADO'],
  ['id_componente'=>18,'tipo_componente'=>'UPS','modelo'=>'MODELOUPS','marca'=>'MARCAUPS','numero_serie'=>'SERIEUPS123456','numero_inventario'=>'CI-20261007UPS'],
];
$outputs=[];foreach($cases as $case=>$caseComponents){$caseBien=$bien;$caseBien['componentes']=$caseComponents;$caseHtml=$method->invoke(null,$caseBien,$resguardo,$captura);$pdf=$pdfMethod->invoke(null,$caseHtml);$output=$outputDir.'resguardo_equipo_'.$case.'.pdf';file_put_contents($output,$pdf);resguardoEquipoAssert(is_file($output)&&filesize($output)>1000,'No se generó el PDF '.$case.'.');preg_match_all('/\/Type\s*\/Page\b/',$pdf,$pageMatches);resguardoEquipoAssert(count($pageMatches[0])===2,'El PDF '.$case.' no contiene exactamente dos páginas.');$outputs[]=$output;}
echo "OK ResguardoEquipoLayoutTest\n".implode("\n",$outputs)."\n";
