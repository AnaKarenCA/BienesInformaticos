<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('IS_LOCAL', false);
define('DB_ENGINE', 'mysql');
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'bienes_informaticos_test_20261012_flow');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

require_once ROOT . 'app/classes/Db.php';
require_once ROOT . 'app/classes/Model.php';
require_once ROOT . 'app/models/CatalogoModel.php';
require_once ROOT . 'app/models/ResguardoModel.php';
require_once ROOT . 'app/models/BienModel.php';
require_once ROOT . 'app/models/TraspasoModel.php';

function detailAssert(bool $condition, string $message): void
{
  if (!$condition) throw new RuntimeException($message);
}

$pdo = Db::connect(true);
detailAssert($pdo->query('SELECT DATABASE()')->fetchColumn() === DB_NAME, 'La prueba no está conectada a la base aislada.');
$snapshot = static function (PDO $pdo): array {
  $tables = ['bien','resguardo','baja_resguardo','movimiento_bien','traspaso','traspaso_bien','traspaso_documento'];
  $result = [];
  foreach ($tables as $table) $result[$table] = (int)$pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
  return $result;
};
$before = $snapshot($pdo);

$ids = [];
$ids['pendiente'] = (int)$pdo->query("SELECT id_traspaso FROM traspaso WHERE estado IN ('PENDIENTE_VALIDACION','VALIDADO') ORDER BY id_traspaso DESC LIMIT 1")->fetchColumn();
$ids['completado'] = (int)$pdo->query("SELECT id_traspaso FROM traspaso WHERE estado='COMPLETADO' ORDER BY id_traspaso DESC LIMIT 1")->fetchColumn();

foreach ($ids as $case => $id) {
  detailAssert($id > 0, "No existe fixture $case.");
  $detail = TraspasoModel::detalle($id);
  detailAssert($detail !== [], "El detalle $case no cargó.");
  foreach (['estado','unidad_origen_nombre','unidad_destino_nombre','resguardante_destino_nombre','bienes','documentos'] as $field) detailAssert(array_key_exists($field, $detail), "Falta $field en $case.");
  detailAssert(count($detail['bienes']) > 0, "El detalle $case no contiene bienes.");
  foreach ($detail['bienes'] as $line) {
    detailAssert(!empty($line['resguardante_origen_nombre']), "Falta resguardante origen en $case.");
    $good = BienModel::porId((int)$line['id_bien']);
    detailAssert($good !== [] && array_key_exists('historial_cronologico', $good), "No cargó el historial del bien en $case.");
  }
  $office = array_values(array_filter($detail['documentos'], static fn(array $doc): bool => $doc['tipo_documento'] === 'OFICIO_AUTORIZACION'));
  echo strtoupper($case).": {$detail['folio']} | {$detail['estado']} | bienes=".count($detail['bienes'])." | documentos=".count($detail['documentos'])." | oficios=".count($office).PHP_EOL;
}

$after = $snapshot($pdo);
detailAssert($before === $after, 'La lectura del detalle modificó filas patrimoniales o documentales.');
echo "OK TransferDetailSchemaCompatibilityTest: ambos detalles cargan y la consulta es de solo lectura.\n";
