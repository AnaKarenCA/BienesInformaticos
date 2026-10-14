<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('IS_LOCAL', false);
define('DB_ENGINE', 'mysql');
define('DB_HOST', '127.0.0.1;port=3307');
define('DB_NAME', 'resguardo_equipo_test');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
function get_user(string $key){return $key==='rol'?'usuario':null;}

require_once ROOT.'app/vendor/autoload.php';
require_once ROOT.'app/classes/Db.php';
require_once ROOT.'app/classes/Model.php';
require_once ROOT.'app/models/TraspasoModel.php';
require_once ROOT.'app/classes/TraspasoDocumentService.php';

$captura=['notas'=>"Dirección capturada\nSegundo nivel",'tipo_equipo'=>'Mini PC','estado_equipo'=>'Completo','observaciones'=>"Línea uno\nLínea dos",'candado'=>'NO'];
TraspasoModel::actualizarCamposResguardoEquipo(1,10,$captura,7);
$pdo=Db::connect(true);
$json=$pdo->query("SELECT metadatos FROM traspaso_evento WHERE tipo_evento='EDICION_RESGUARDO_EQUIPO' ORDER BY id_traspaso_evento DESC LIMIT 1")->fetchColumn();
$meta=json_decode((string)$json,true,512,JSON_THROW_ON_ERROR);
foreach($captura as $key=>$value)if(($meta['campos'][$key]??null)!==$value)throw new RuntimeException('El campo '.$key.' no se persistió con su valor exacto.');
$recargados=TraspasoDocumentService::camposResguardoEquipo(['resguardante_notas'=>'Dirección anterior'],['tipo_equipo'=>'Laptop','estado_equipo'=>'Incompleto','observaciones'=>'Anterior','candado'=>'SI'],$meta['campos']);
if($recargados!==$captura)throw new RuntimeException('La recarga no recuperó los cinco valores guardados.');
$bien=['clave_interna'=>'900','resguardante_nombre'=>'PERSONA','resguardante_csp'=>'999999999','resguardante_notas'=>'Dirección anterior','componentes'=>[]];
$method=new ReflectionMethod(TraspasoDocumentService::class,'resguardoEquipo');$method->setAccessible(true);$html=$method->invoke(null,$bien,[],$recargados);
foreach(['Dirección capturada','Segundo nivel','Mini PC','Completo','Línea uno','Línea dos','No'] as $value)if(!str_contains($html,$value))throw new RuntimeException('El PDF regenerado no recibió el valor guardado: '.$value);
echo "OK ResguardoEquipoPersistenceTest: guardar, recargar y regenerar\n";
