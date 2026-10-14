<?php

declare(strict_types=1);

/** Prueba de escritura exclusivamente para la base aislada `prueba`. */
define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('IS_LOCAL', false);
define('DB_ENGINE', 'mysql');
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'prueba');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

require_once ROOT . 'app/classes/Db.php';
require_once ROOT . 'app/classes/Model.php';
require_once ROOT . 'app/classes/TraspasoDocumentService.php';
require_once ROOT . 'app/models/TraspasoModel.php';

function expectConstancy(bool $condition, string $message): void
{
  if (!$condition) throw new RuntimeException($message);
}

$pdo = new PDO('mysql:host=127.0.0.1;dbname=prueba;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
expectConstancy((string)$pdo->query('SELECT DATABASE()')->fetchColumn() === 'prueba', 'La prueba sólo puede ejecutarse en la base aislada prueba.');
expectConstancy((bool)$pdo->query("SHOW TABLES LIKE 'traspaso_constancia_administrativa'")->fetchColumn(), 'Instala primero la migración de constancias en prueba.');

$transferId = (int)$pdo->query("SELECT id_traspaso FROM traspaso WHERE folio='TR-000007'")->fetchColumn();
expectConstancy($transferId > 0, 'No se encontró el fixture TR-000007 en prueba.');
$userId = (int)$pdo->query("SELECT id FROM bee_users WHERE activo=1 ORDER BY id LIMIT 1")->fetchColumn();
expectConstancy($userId > 0, 'No existe usuario activo de prueba.');
$missingIds = array_map('intval', $pdo->query("SELECT id_traspaso_documento FROM traspaso_documento WHERE id_traspaso=$transferId ORDER BY id_traspaso_documento")->fetchAll(PDO::FETCH_COLUMN));
expectConstancy(count($missingIds) === 8, 'El fixture debe conservar los ocho documentos históricos de TR-000007.');
$before = $pdo->query("SELECT estado,fecha_completado FROM traspaso WHERE id_traspaso=$transferId")->fetch(PDO::FETCH_ASSOC);
$movementBefore = $pdo->query("SELECT id_movimiento,id_resguardo_destino FROM traspaso_bien WHERE id_traspaso=$transferId")->fetch(PDO::FETCH_ASSOC);
$eventsBefore = (int)$pdo->query("SELECT COUNT(*) FROM traspaso_evento WHERE id_traspaso=$transferId")->fetchColumn();

putenv('BIENES_ENABLE_DOCUMENT_REGULARIZATION=1');
$constancyId = 0;
try {
  $tamperedRejected = false;
  try { TraspasoModel::registrarConstanciaAdministrativa($transferId, $userId, ['tipo_incidencia'=>'DOCUMENTOS_AUSENTES','documentos'=>[$missingIds[0]]]); }
  catch (RuntimeException $e) { $tamperedRejected = true; }
  expectConstancy($tamperedRejected, 'Una selección manipulada o incompleta de documentos debe rechazarse.');

  $constancyId = TraspasoModel::registrarConstanciaAdministrativa($transferId, $userId, [
    'tipo_incidencia' => 'DOCUMENTOS_AUSENTES',
    'documentos' => $missingIds,
    'fecha_deteccion' => '2026-10-09',
    'referencia_resolucion' => 'PRUEBA-AISLADA-F4',
  ]);
  expectConstancy($constancyId > 0, 'La constancia aislada no fue creada.');
  $stored = $pdo->query("SELECT id_traspaso,estado,fecha_deteccion,id_usuario_registra FROM traspaso_constancia_administrativa WHERE id_constancia_administrativa=$constancyId")->fetch(PDO::FETCH_ASSOC);
  expectConstancy((int)$stored['id_traspaso'] === $transferId && $stored['estado'] === 'REGISTRADA' && $stored['fecha_deteccion'] === '2026-10-09' && (int)$stored['id_usuario_registra'] === $userId, 'La constancia no conserva sus datos de actuación actuales.');
  expectConstancy((int)$pdo->query("SELECT COUNT(*) FROM traspaso_constancia_documento WHERE id_constancia_administrativa=$constancyId")->fetchColumn() === 8, 'La constancia debe enlazar los ocho documentos ausentes.');

  $duplicateRejected = false;
  try { TraspasoModel::registrarConstanciaAdministrativa($transferId, $userId, ['tipo_incidencia'=>'DOCUMENTOS_AUSENTES','documentos'=>$missingIds]); }
  catch (Throwable $e) { $duplicateRejected = true; }
  expectConstancy($duplicateRejected, 'Una constancia con el mismo expediente, tipo y documentos debe rechazarse como duplicada.');

  $after = $pdo->query("SELECT estado,fecha_completado FROM traspaso WHERE id_traspaso=$transferId")->fetch(PDO::FETCH_ASSOC);
  $movementAfter = $pdo->query("SELECT id_movimiento,id_resguardo_destino FROM traspaso_bien WHERE id_traspaso=$transferId")->fetch(PDO::FETCH_ASSOC);
  $eventsAfter = (int)$pdo->query("SELECT COUNT(*) FROM traspaso_evento WHERE id_traspaso=$transferId")->fetchColumn();
  expectConstancy($after === $before && $movementAfter === $movementBefore && $eventsAfter === $eventsBefore, 'La constancia no debe modificar estado, movimiento, resguardo ni eventos históricos.');

  putenv('BIENES_ENABLE_DOCUMENT_REGULARIZATION');
  $disabledRejected = false;
  try { TraspasoModel::registrarConstanciaAdministrativa($transferId, $userId, ['tipo_incidencia'=>'DOCUMENTOS_AUSENTES','documentos'=>$missingIds]); }
  catch (RuntimeException $e) { $disabledRejected = true; }
  expectConstancy($disabledRejected, 'La escritura debe permanecer bloqueada si no se habilita el entorno aislado.');
  $controller = file_get_contents(ROOT . 'app/controllers/traspasosController.php');
  expectConstancy(strpos((string)$controller, "accionPost('traspasos-regularizar-documentos'") !== false, 'La ruta debe exigir el permiso específico y la protección CSRF de accionPost.');
  echo "OK: constancia administrativa aislada\n";
} finally {
  putenv('BIENES_ENABLE_DOCUMENT_REGULARIZATION');
  if ($constancyId > 0) {
    $pdo->prepare('DELETE FROM traspaso_constancia_documento WHERE id_constancia_administrativa=?')->execute([$constancyId]);
    $pdo->prepare('DELETE FROM traspaso_constancia_administrativa WHERE id_constancia_administrativa=?')->execute([$constancyId]);
  }
}
