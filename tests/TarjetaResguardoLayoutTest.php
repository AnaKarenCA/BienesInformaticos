<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once ROOT . 'app/vendor/autoload.php';
require_once ROOT . 'app/classes/TraspasoDocumentService.php';

function tarjetaAssert(bool $condition, string $message): void
{
  if (!$condition) throw new RuntimeException($message);
}

$levels = static function (array $values): array {
  $fields=['secretaria','subsecretaria','direccion_secretaria','direccion_area','delegacion_administrativa','subdireccion','departamento','oficina'];
  return ['niveles_detalle'=>array_map(static function(string $field)use($values):array{return ['etiqueta'=>$field,'codigo_ua'=>$values[$field][0]??null,'nombre'=>$values[$field][1]??null];},$fields)];
};
$full=$levels([
  'secretaria'=>['22600000000000L','Secretaría de Cultura y Turismo'],
  'subsecretaria'=>['22600200000000L','Subsecretaría de Cultura'],
  'direccion_secretaria'=>['22600202000000L','Dirección General de Patrimonio'],
  'direccion_area'=>['22600202010000L','Dirección de Patrimonio Cultural'],
  'subdireccion'=>['22600202010100L','Subdirección de Bibliotecas'],
  'departamento'=>['22600202010103L','Departamento de Bibliotecas'],
  'oficina'=>['22600202010104L','Oficina de Control'],
]);
$base=[
  'numero_inventario'=>'INV-PRUEBA-001','nic_cea'=>'NIC-001','codigo_ua'=>'22600202010104L',
  'activo_generico_nombre'=>'EQUIPO DE CÓMPUTO','grupo_nombre'=>'COMPUTADORAS','activo_especifico_nombre'=>'COMPUTADORA PORTÁTIL',
  'nombre_bien'=>'COMPUTADORA PORTÁTIL PARA PRUEBA DE MAQUETACIÓN','material_nombre'=>'ALUMINIO','marca_nombre'=>'MARCA','modelo_nombre'=>'MODELO',
  'color_nombre'=>'NEGRO','estado_nombre'=>'BUENO','numero_serie'=>'SERIE-001','caracteristicas'=>'PRUEBA DE CONTENIDO DINÁMICO',
  'traspaso_observaciones'=>'OBSERVACIÓN EDITABLE','fecha_alta'=>'2026-01-01','fecha_adquisicion'=>'2026-01-02','fecha_elaboracion'=>'2026-01-03',
  'valor'=>'12345.67','piso'=>'2','seccion_ala'=>'NORTE','cubiculo'=>'14','municipio'=>'TOLUCA','localidad'=>'CENTRO',
  'resguardante_destino_nombre'=>'NOMBRE DEL RESGUARDATARIO','jerarquia_administrativa'=>$full,
];
$fields=TraspasoDocumentService::camposTarjeta($base,['fecha_asignacion'=>'2026-10-13'],['observaciones'=>'OBSERVACIÓN MANUAL','secretaria'=>'VALOR MANIPULADO']);
tarjetaAssert($fields['secretaria']==='22600000000000L - Secretaría de Cultura y Turismo','Caso A: Secretaría no contiene código y nombre.');
tarjetaAssert($fields['direccion']==='22600202000000L - Dirección General de Patrimonio','Caso A: Dirección no conserva su nivel.');
tarjetaAssert($fields['codigo_ua']==='22600202010104L','Caso A: Código UA debe contener exclusivamente el código.');
tarjetaAssert($fields['ubicacion_fisica']==='PISO: 2   SECCIÓN/ALA: NORTE   CUBÍCULO: 14','Caso C: ubicación completa incorrecta.');
tarjetaAssert($fields['observaciones']==='OBSERVACIÓN MANUAL'&&!str_contains($fields['secretaria'],'MANIPULADO'),'Caso F/G: sólo Observaciones debe admitir override.');

$partial=$base;$partial['jerarquia_administrativa']=$levels(['secretaria'=>['22600000000000L','Secretaría de Cultura y Turismo'],'departamento'=>['22600004000001S','Departamento de Información']]);$partial['piso']='';$partial['seccion_ala']='NORTE';$partial['cubiculo']='';
$partialFields=TraspasoDocumentService::camposTarjeta($partial,[]);
tarjetaAssert($partialFields['subsecretaria']===''&&$partialFields['direccion']===''&&$partialFields['departamento']==='22600004000001S - Departamento de Información','Caso B: los niveles faltantes se desplazaron o rellenaron.');
tarjetaAssert($partialFields['ubicacion_fisica']==='PISO:    SECCIÓN/ALA: NORTE   CUBÍCULO: ','Caso D: ubicación parcial incorrecta.');

$template=file_get_contents(ROOT.'templates/views/traspasos/detalleView.twig');
tarjetaAssert(substr_count((string)$template,'name="campos_tarjeta[')===1&&str_contains((string)$template,'name="campos_tarjeta[observaciones]"'),'Caso G: la interfaz expone campos editables adicionales.');
$model=file_get_contents(ROOT.'app/models/TraspasoModel.php');
tarjetaAssert(str_contains((string)$model,"\$permitidos=['observaciones'];"),'Caso G: el backend admite campos adicionales.');

$card=new ReflectionMethod(TraspasoDocumentService::class,'tarjeta');$card->setAccessible(true);
$html=$card->invoke(null,$base,['fecha_asignacion'=>'2026-10-13'],['observaciones'=>'OBSERVACIÓN MANUAL']);
$pdfMethod=new ReflectionMethod(TraspasoDocumentService::class,'pdf');$pdfMethod->setAccessible(true);
$pdf=$pdfMethod->invoke(null,$html);
$output=ROOT.'output'.DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR.'tarjeta_resguardo_prueba.pdf';
if(!is_dir(dirname($output)))mkdir(dirname($output),0775,true);
file_put_contents($output,$pdf);
tarjetaAssert(is_file($output)&&filesize($output)>1000,'No se generó el PDF de prueba.');
echo "OK TarjetaResguardoLayoutTest\n$output\n";
