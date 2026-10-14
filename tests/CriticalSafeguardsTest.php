<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

$assert = static function (bool $condition, string $message) use (&$failures): void {
  if (!$condition) $failures[] = $message;
};

require_once $root . '/app/classes/Csrf.php';

// Pruebas B y C: ausencia, valor incorrecto, sesión distinta y expiración.
$_SESSION = [];
$csrf = new Csrf();
$token = $csrf->get_token();
$assert(is_string($token) && strlen($token) === 64, 'CSRF: el token generado no tiene 256 bits representados en hexadecimal.');
$assert(Csrf::validate($token), 'CSRF: un token vigente de la misma sesión debe aceptarse.');
$assert(!Csrf::validate(''), 'CSRF: un token ausente debe rechazarse.');
$assert(!Csrf::validate(str_repeat('0', 64)), 'CSRF: un token incorrecto debe rechazarse.');

$firstSessionToken = $token;
$_SESSION = [];
$secondSessionToken = (new Csrf())->get_token();
$assert($firstSessionToken !== $secondSessionToken, 'CSRF: dos sesiones simuladas no deben compartir token.');
$assert(!Csrf::validate($firstSessionToken), 'CSRF: un token de otra sesión debe rechazarse.');

$_SESSION['csrf_token'] = ['token' => str_repeat('a', 64), 'expiration' => time() - 1];
$assert(!Csrf::validate(str_repeat('a', 64)), 'CSRF: un token vencido debe rechazarse.');
$assert($_SESSION['csrf_token']['token'] !== str_repeat('a', 64), 'CSRF: al detectar expiración debe rotarse el token de sesión.');

// Prueba D a nivel de la política central, sin conexión a base de datos.
// Reflection evita cargar un rol real y prueba exclusivamente can().
if (!class_exists('Model', false)) {
  class Model {}
}
require_once $root . '/app/classes/BeeRoleManager.php';
$manager = (new ReflectionClass(BeeRoleManager::class))->newInstanceWithoutConstructor();
$roleSlug = new ReflectionProperty(BeeRoleManager::class, 'roleSlug');
$permissions = new ReflectionProperty(BeeRoleManager::class, 'permissions');
$roleSlug->setValue($manager, 'developer');
$permissions->setValue($manager, []);
$assert(!$manager->can('usuarios-eliminar'), 'Roles: developer no debe recibir permisos sin una asignación en la matriz.');
$permissions->setValue($manager, [['slug' => 'bienes-consultar']]);
$assert($manager->can('bienes-consultar'), 'Roles: un permiso asignado debe autorizar la operación correspondiente.');
$assert(!$manager->can('usuarios-eliminar'), 'Roles: un permiso asignado no debe autorizar otra operación.');
$permissions->setValue($manager, [['slug' => 'admin-access']]);
$assert($manager->can('usuarios-eliminar'), 'Roles: admin-access debe conservar su comportamiento maestro documentado.');

// Regresiones estructurales de la coordinación SQL/archivos de la Prueba A.
$controller = file_get_contents($root . '/app/controllers/traspasosController.php');
$model = file_get_contents($root . '/app/models/TraspasoModel.php');
$documentService = file_get_contents($root . '/app/classes/TraspasoDocumentService.php');
$userModel = file_get_contents($root . '/app/models/userModel.php');
$assert(strpos($controller, '$pdo->beginTransaction()') < strpos($controller, 'TraspasoModel::crear('), 'Atomicidad: la transacción debe comenzar antes de crear el expediente.');
$generationPosition = strpos($controller, 'TraspasoDocumentService::generar(');
$commitAfterGeneration = $generationPosition === false ? false : strpos($controller, '$pdo->commit();', $generationPosition);
$assert($generationPosition !== false && $commitAfterGeneration !== false && $generationPosition < $commitAfterGeneration, 'Atomicidad: los documentos deben generarse antes del commit.');
$assert(str_contains($controller, '$pdo->rollBack()') && str_contains($controller, 'array_reverse($archivosCreados)'), 'Atomicidad: el fallo debe revertir SQL y limpiar archivos creados.');
$assert(str_contains($model, '?PDO $pdo = null') && str_contains($model, 'requiere una transacción activa'), 'Atomicidad: el modelo debe poder participar en una transacción coordinada activa.');
$assert(str_contains($controller, 'TraspasoModel::detalle($id,$pdo)') && str_contains($documentService, 'TraspasoModel::detalle($traspasoId, $pdo)'), 'Atomicidad: las lecturas del expediente nuevo deben usar la misma transacción.');
$assert(!str_contains($userModel, "=== 'developer' || in_array('admin-access'"), 'Roles: la matriz no debe conceder acceso global automático a developer.');

if ($failures) {
  fwrite(STDERR, "FALLÓ CriticalSafeguardsTest\n- " . implode("\n- ", $failures) . "\n");
  exit(1);
}

echo "OK CriticalSafeguardsTest: CSRF, matriz de permisos y coordinación atómica verificadas.\n";

