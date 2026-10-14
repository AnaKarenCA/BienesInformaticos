<?php

declare(strict_types=1);

/** Prueba autónoma: usa únicamente un directorio temporal, sin BD ni expedientes reales. */
define('ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once ROOT . 'app/classes/TraspasoDocumentService.php';

function expect(bool $condition, string $message): void
{
  if (!$condition) throw new RuntimeException($message);
}

function removeFixture(string $path): void
{
  if (!is_dir($path)) return;
  foreach (scandir($path) ?: [] as $entry) {
    if ($entry === '.' || $entry === '..') continue;
    $target = $path . DIRECTORY_SEPARATOR . $entry;
    is_dir($target) ? removeFixture($target) : unlink($target);
  }
  rmdir($path);
}

$fixture = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bienes-document-check-' . bin2hex(random_bytes(8));
$previousStorage = getenv('BIENES_PRIVATE_STORAGE');
try {
  mkdir($fixture . DIRECTORY_SEPARATOR . 'traspasos' . DIRECTORY_SEPARATOR . 'TR-TEST', 0700, true);
  putenv('BIENES_PRIVATE_STORAGE=' . $fixture);
  $contents = "archivo de prueba\n";
  $file = $fixture . DIRECTORY_SEPARATOR . 'traspasos' . DIRECTORY_SEPARATOR . 'TR-TEST' . DIRECTORY_SEPARATOR . 'valido.pdf';
  file_put_contents($file, $contents);
  $hash = hash('sha256', $contents);

  expect(TraspasoDocumentService::verificarDocumento('TR-TEST/valido.pdf', $hash, strlen($contents))['estado'] === 'DISPONIBLE', 'Debe aceptar un archivo con tamaño y hash válidos.');
  expect(TraspasoDocumentService::verificarDocumento('TR-TEST/faltante.pdf', $hash, strlen($contents))['estado'] === 'AUSENTE', 'Debe distinguir un archivo ausente.');
  expect(TraspasoDocumentService::verificarDocumento('../fuera.pdf', $hash, strlen($contents))['estado'] === 'RUTA_INVALIDA', 'Debe rechazar rutas fuera del almacenamiento privado.');
  expect(TraspasoDocumentService::verificarDocumento('TR-TEST/valido.pdf', str_repeat('0', 64), strlen($contents))['estado'] === 'HASH_INVALIDO', 'Debe detectar un hash distinto.');
  expect(TraspasoDocumentService::verificarDocumento('TR-TEST/valido.pdf', $hash, strlen($contents) + 1)['estado'] === 'TAMANO_INVALIDO', 'Debe detectar un tamaño distinto.');
  expect(TraspasoDocumentService::verificarDocumento('TR-TEST/valido.pdf', null, null)['estado'] === 'NO_VERIFICABLE', 'Debe señalar metadatos insuficientes sin afirmar integridad.');

  $uploadSource = $fixture . DIRECTORY_SEPARATOR . 'origen.pdf';
  file_put_contents($uploadSource, $contents);
  $stored = TraspasoDocumentService::guardarArchivoSubido('TR-TEST/carga.pdf', $uploadSource, strlen($contents), static fn(string $from, string $to): bool => copy($from, $to));
  expect(is_file($stored['path']), 'La carga válida debe persistir fuera del temporal.');
  expect($stored['size'] === strlen($contents) && $stored['hash'] === $hash, 'La persistencia debe devolver el tamaño y hash reales.');
  expect(TraspasoDocumentService::verificarDocumento('TR-TEST/carga.pdf', $stored['hash'], $stored['size'])['estado'] === 'DISPONIBLE', 'El archivo debe recuperarse en una verificación posterior.');

  $duplicateRejected = false;
  try { TraspasoDocumentService::guardarArchivoSubido('TR-TEST/carga.pdf', $uploadSource, strlen($contents), static fn(string $from, string $to): bool => copy($from, $to)); }
  catch (RuntimeException $e) { $duplicateRejected = true; }
  expect($duplicateRejected && hash_file('sha256', $stored['path']) === $hash, 'Un intento de reemplazo no debe alterar la versión anterior.');

  $writeFailed = false;
  try { TraspasoDocumentService::guardarArchivoSubido('TR-TEST/fallida.pdf', $uploadSource, strlen($contents), static fn(): bool => false); }
  catch (RuntimeException $e) { $writeFailed = true; }
  expect($writeFailed && !file_exists($fixture . DIRECTORY_SEPARATOR . 'traspasos' . DIRECTORY_SEPARATOR . 'TR-TEST' . DIRECTORY_SEPARATOR . 'fallida.pdf'), 'Una falla de escritura no debe dejar un archivo persistido.');

  $corruptRejected = false;
  try { TraspasoDocumentService::guardarArchivoSubido('TR-TEST/corrupta.pdf', $uploadSource, strlen($contents), static function (string $from, string $to): bool { file_put_contents($to, 'contenido incompleto'); return true; }); }
  catch (RuntimeException $e) { $corruptRejected = true; }
  expect($corruptRejected && !file_exists($fixture . DIRECTORY_SEPARATOR . 'traspasos' . DIRECTORY_SEPARATOR . 'TR-TEST' . DIRECTORY_SEPARATOR . 'corrupta.pdf'), 'Una persistencia con tamaño o hash distinto debe limpiarse antes de registrar datos.');

  putenv('BIENES_PRIVATE_STORAGE=' . $fixture . DIRECTORY_SEPARATOR . 'inaccesible');
  expect(TraspasoDocumentService::verificarDocumento('TR-TEST/valido.pdf', $hash, strlen($contents))['estado'] === 'ERROR_LECTURA', 'Debe informar un almacenamiento no accesible.');
  putenv('BIENES_PRIVATE_STORAGE=' . $fixture);

  $controller = file_get_contents(ROOT . 'app/controllers/traspasosController.php');
  expect(strpos((string)$controller, "can('traspasos-descargar-documentos')") !== false, 'La entrega de archivos debe conservar su control de permisos.');
  echo "OK: verificación de disponibilidad documental\n";
} finally {
  $previousStorage === false ? putenv('BIENES_PRIVATE_STORAGE') : putenv('BIENES_PRIVATE_STORAGE=' . $previousStorage);
  removeFixture($fixture);
}
