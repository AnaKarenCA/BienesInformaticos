<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('IS_LOCAL', false);
define('DB_ENGINE', 'mysql');
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'bienes_informaticos');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

require_once ROOT.'app/vendor/autoload.php';
require_once ROOT.'app/classes/Db.php';
require_once ROOT.'app/classes/Model.php';
require_once ROOT.'app/models/CatalogoModel.php';
require_once ROOT.'app/models/ResguardoModel.php';
require_once ROOT.'app/models/BienModel.php';
require_once ROOT.'app/models/ResguardanteModel.php';
require_once ROOT.'app/models/TraspasoModel.php';
require_once ROOT.'app/classes/TraspasoDocumentService.php';

$pdo=Db::connect(true);
$before=[];foreach(['bien','resguardo','traspaso','traspaso_documento','traspaso_evento'] as $table)$before[$table]=(int)$pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
$id=(int)$pdo->query("SELECT id_traspaso FROM traspaso WHERE estado IN ('PENDIENTE_VALIDACION','VALIDADO') ORDER BY id_traspaso DESC LIMIT 1")->fetchColumn();
if(!$id)throw new RuntimeException('No existe un traspaso activo para la prueba real de lectura.');
$transfer=TraspasoModel::detalle($id);$line=$transfer['bienes'][0]??null;
if(!$line)throw new RuntimeException('El traspaso de prueba no contiene bienes.');
$good=BienModel::porId((int)$line['id_bien']);
$good['jerarquia_administrativa']=CatalogoModel::jerarquiaUnidad((int)$transfer['id_unidad_destino']);
$good['codigo_ua']=$transfer['unidad_destino_codigo'];$good['unidad_nombre']=$transfer['unidad_destino_nombre'];
$good['resguardante_destino_nombre']=$transfer['resguardante_destino_nombre'];
$good['traspaso_observaciones']=$transfer['observaciones']??null;
if(empty($line['ubicacion_sin_cambio'])&&!empty($line['id_ubicacion_destino'])){$good['municipio']=$line['municipio_destino']??null;$good['localidad']=$line['localidad_destino']??null;}
$resguardo=['fecha_asignacion'=>date('Y-m-d')];
$card=new ReflectionMethod(TraspasoDocumentService::class,'tarjeta');$card->setAccessible(true);$html=$card->invoke(null,$good,$resguardo,[]);
$pdfMethod=new ReflectionMethod(TraspasoDocumentService::class,'pdf');$pdfMethod->setAccessible(true);$pdf=$pdfMethod->invoke(null,$html);
$output=ROOT.'output'.DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR.'tarjeta_resguardo_prueba.pdf';file_put_contents($output,$pdf);
$after=[];foreach(array_keys($before) as $table)$after[$table]=(int)$pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
if($before!==$after)throw new RuntimeException('La generación de prueba modificó datos del sistema.');
echo "OK TarjetaResguardoRealDataTest: {$transfer['folio']}, bien {$line['id_bien']}, solo lectura\n$output\n";
