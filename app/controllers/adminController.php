<?php

use Cocur\Slugify\Slugify;

/**
 * Plantilla general de controladores
 * @version 1.0.5
 *
 * Controlador de admin
 */
class adminController extends Controller implements ControllerInterface 
{
  function __construct()
  {
    parent::__construct();
    $user = $this->requireActiveAccount();
    $this->addToData('csrf', (new Csrf())->get_token());
    $this->addToData('current_user', $user);
    $this->addToData('flash_html', Flasher::flash());
    $this->addToData('can_create_goods', $this->hasPermission('bienes-crear', $user));
    $this->addToData('can_update_goods', $this->hasPermission('bienes-actualizar', $user));
    $this->addToData('can_manage_goods', $this->hasPermission('bienes-crear', $user) || $this->hasPermission('bienes-actualizar', $user));
    $this->addToData('can_activate_goods', $this->hasPermission('bienes-activar', $user));
    $this->addToData('can_deactivate_goods', $this->hasPermission('bienes-desactivar', $user));
    $this->addToData('can_inactivate_goods', $this->hasPermission('bienes-activar', $user) && $this->hasPermission('bienes-desactivar', $user));
    $this->addToData('can_print_goods', $this->hasPermission('bienes-imprimir', $user));
    $this->addToData('can_view_catalogs', $this->hasPermission('catalogos-consultar', $user));
    $this->addToData('can_create_catalogs', $this->hasPermission('catalogos-crear', $user));
    $this->addToData('can_update_catalogs', $this->hasPermission('catalogos-actualizar', $user));
    $this->addToData('can_manage_catalogs', $this->hasPermission('catalogos-crear', $user) || $this->hasPermission('catalogos-actualizar', $user));
    $this->addToData('can_activate_catalogs', $this->hasPermission('catalogos-activar', $user));
    $this->addToData('can_deactivate_catalogs', $this->hasPermission('catalogos-desactivar', $user));
    $this->addToData('can_inactivate_catalogs', $this->hasPermission('catalogos-activar', $user) && $this->hasPermission('catalogos-desactivar', $user));
    $this->addToData('can_change_custody', $this->hasPermission('catalogos-actualizar', $user) && $this->hasPermission('catalogos-desactivar', $user));
    $this->addToData('can_view_resguardos', $this->hasPermission('resguardos-consultar', $user));
    $this->addToData('can_view_movements', $this->hasPermission('movimientos-consultar', $user));
    $this->addToData('can_view_transfers', $this->hasPermission('traspasos-consultar', $user));
    $this->addToData('can_access_documents', $this->hasPermission('documentos-consultar', $user));
    $this->addToData('can_admin_users', $this->hasPermission('admin-access', $user));
    $this->addToData('can_manage_role_permissions', ($user['rol'] ?? '') === 'admin' && $this->hasPermission('admin-access', $user));
    $this->addToData('can_generate_documents', $this->hasPermission('documentos-generar', $user));
    $this->addToData('can_export_reports', $this->hasPermission('reportes-exportar', $user));
    foreach (['consultar' => 'usuarios-consultar', 'crear' => 'usuarios-crear', 'actualizar' => 'usuarios-actualizar', 'eliminar' => 'usuarios-eliminar', 'activar' => 'usuarios-activar', 'desactivar' => 'usuarios-desactivar', 'cerrar_sesion' => 'usuarios-cerrar-sesion'] as $action => $permission) $this->addToData('can_users_' . $action, $this->hasPermission($permission, $user));
    $this->addToData('can_view_roles', $this->hasPermission('roles-consultar', $user));
    $this->addToData('can_update_permissions', $this->hasPermission('permisos-actualizar', $user));
  }
  
  function index()
  {
    $this->requirePermission('inicio-consultar');
    $this->setEngine('twig');
    $this->setTitle('Inicio');
    $canViewGoods = $this->hasPermission('bienes-consultar');
    $this->addToData('can_view_goods', $canViewGoods);
    $this->addToData('resumen', $canViewGoods ? BienModel::resumen() : ['total' => 0, 'con_resguardante' => 0, 'pendientes' => 0]);
    $this->addToData('recientes', $canViewGoods ? BienModel::recientes() : []);
    $this->addToData('csrf', (new Csrf())->get_token());
    $this->addToData('current_user', get_user());
    $this->addToData('flash_html', Flasher::flash());
    $this->setView('dashboard/index');
    $this->render();
  }

  function perfil()
  {
    $userId = (int) get_user('id');
    $profile = $userId > 0 ? userModel::profile_by_id($userId) : [];
    if (!$profile || (int) ($profile['activo'] ?? 0) !== 1) {
      Flasher::error('No fue posible cargar la información de tu cuenta.');
      Redirect::to('admin');
    }

    $this->setTitle('Mi perfil');
    $this->setEngine('twig');
    $this->addToData('profile_user', $profile);
    $this->addToData('csrf', (new Csrf())->get_token());
    $this->addToData('flash_html', Flasher::flash());
    $this->setView('perfil');
    $this->render();
  }

  /** Guarda exclusivamente teléfono, correo y contraseña de la sesión actual. */
  function guardar_perfil()
  {
    $passwordRequest = ($_POST['section'] ?? '') === 'password';
    $passwordResponseCode = 'system_error';
    try {
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $passwordResponseCode = 'validation_error';
        throw new Exception('La solicitud no es válida.');
      }
      if (!Csrf::validate((string) ($_POST['csrf'] ?? ''))) {
        $passwordResponseCode = 'validation_error';
        throw new Exception('La solicitud no es válida o expiró. Recarga la página e inténtalo de nuevo.');
      }

      // Nunca se toma un ID de GET/POST: la cuenta objetivo sale de la sesión validada.
      $user = $this->requireActiveAccount();
      $userId = (int) $user['id'];

      $section = (string) ($_POST['section'] ?? '');
      if ($section === 'contact') {
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['telefono'] ?? ''));
        $length = function ($value) { return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value); };
        if ($email === '' && $phone === '') throw new Exception('Proporciona al menos un medio de contacto: correo electrónico o teléfono.');
        if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || $length($email) > 100)) throw new Exception('Ingresa un correo electrónico válido de hasta 100 caracteres.');
        if ($phone !== '' && !preg_match('/^[0-9]{7,15}$/', $phone)) throw new Exception('El teléfono debe contener únicamente entre 7 y 15 dígitos.');
        if ($email !== '' && userModel::query('SELECT id FROM bee_users WHERE email = :email AND id <> :id LIMIT 1', ['email' => $email, 'id' => $userId])) throw new Exception('Ese correo electrónico ya está registrado en otra cuenta.');
        $changes = ['email' => $email, 'telefono' => $phone];
        if (!userModel::update_by_id($userId, $changes)) throw new Exception('No se pudieron guardar los datos de contacto.');
        Flasher::success('¿Desea guardar los cambios?');
      } elseif ($section === 'password') {
        $passwordResponseCode = 'validation_error';
        $currentPassword = (string) ($_POST['password_actual'] ?? '');
        $newPassword = (string) ($_POST['password_nueva'] ?? '');
        $confirmPassword = (string) ($_POST['password_confirmacion'] ?? '');
        $storedHash = (string) ($user['password'] ?? '');

        // Reconsultamos la cuenta autenticada en cada petición y verificamos la contraseña antes
        // de considerar cualquier cambio. No se acepta un ID de usuario enviado por el cliente.
        if ($currentPassword === '' || $storedHash === '' || !password_verify($currentPassword . AUTH_SALT, $storedHash)) {
          $passwordResponseCode = 'invalid_current_password';
          throw new Exception('No se pudo cambiar la contraseña porque la contraseña actual es incorrecta.');
        }

        if ($newPassword === '') throw new Exception('Captura una nueva contraseña.');
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*_-])[A-Za-z\d!@#$%^&*_-]{5,20}$/', $newPassword)) {
          throw new Exception('La nueva contraseña debe tener de 5 a 20 caracteres, incluir minúscula, mayúscula, número y un símbolo permitido (!@#$%^&*_-), y no contener otros caracteres.');
        }
        if ($confirmPassword === '' || !hash_equals($newPassword, $confirmPassword)) {
          throw new Exception('La confirmación de la nueva contraseña no coincide.');
        }

        // El hash se genera después de todas las verificaciones y usa el mismo esquema del login.
        $passwordResponseCode = 'system_error';
        $newHash = password_hash($newPassword . AUTH_SALT, PASSWORD_BCRYPT);
        if (!is_string($newHash) || $newHash === '') throw new Exception('No se pudo proteger la nueva contraseña.');
        if (!userModel::update_by_id($userId, ['password' => $newHash])) throw new Exception('No se pudo actualizar la contraseña.');
        $this->profilePasswordJsonResponse('success', 'La contraseña se ha cambiado exitosamente.', 200);
      } else {
        throw new Exception('La sección de perfil solicitada no es válida.');
      }
    } catch (Throwable $e) {
      if ($passwordRequest) {
        if ($passwordResponseCode === 'invalid_current_password') {
          $this->profilePasswordJsonResponse('invalid_current_password', 'No se pudo cambiar la contraseña porque la contraseña actual es incorrecta.', 422);
        }
        if ($passwordResponseCode === 'system_error') {
          $this->profilePasswordJsonResponse('system_error', 'No se pudo cambiar la contraseña debido a un error del sistema.', 500);
        }
        $this->profilePasswordJsonResponse('validation_error', $e->getMessage(), 422);
      }
      Flasher::error($e->getMessage());
    }
    Redirect::to('admin/perfil');
  }

  /** Respuesta acotada para el cambio de contraseña; nunca incluye errores técnicos ni secretos. */
  private function profilePasswordJsonResponse(string $code, string $message, int $status): void
  {
    http_response_code($status);
    header('Cache-Control: no-store, max-age=0');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $code === 'success', 'code' => $code, 'message' => $message]);
    exit;
  }

  function botones()
  {
    $this->adminOnly();
    $this->setTitle('Botones');
    $this->setView('botones');
    $this->render();
  }

  function cartas()
  {
    $this->adminOnly();
    $this->setTitle('Cartas');
    $this->setView('cartas');
    $this->render();
  }

  ////////////////////////////////////////////////////
  ////////////////////////////////////////////////////
  ////////////////////////////////////////////////////
  //////// USUARIOS
  ////////////////////////////////////////////////////
  ////////////////////////////////////////////////////
  ////////////////////////////////////////////////////
  function usuarios()
  {
    $this->requirePermission('usuarios-consultar');
    $search = trim((string) ($_GET['q'] ?? ''));
    $status = trim((string) ($_GET['activo'] ?? ''));
    $this->setTitle('Usuarios');
    $this->setEngine('twig');
    $this->addToData('users', userModel::all_paginated($search, $status));
    $this->addToData('user_roles', (new BeeRoleManager())->getRoles() ?: []);
    $this->addToData('filters', ['q' => $search, 'activo' => $status]);
    $this->setView('usuarios/usuarios');
    $this->render();
  }

  /** Lista parcial para los filtros dinámicos de la vista de usuarios. */
  function usuarios_ajax()
  {
    $this->requirePermission('usuarios-consultar');
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'xmlhttprequest') {
      http_response_code(400);
      exit;
    }
    $search = trim((string) ($_GET['q'] ?? ''));
    $status = trim((string) ($_GET['activo'] ?? ''));
    $this->setEngine('twig');
    $this->addToData('users', userModel::all_paginated($search, $status));
    $this->addToData('current_user', get_user());
    $this->addToData('user_roles', (new BeeRoleManager())->getRoles() ?: []);
    $this->addToData('csrf', (new Csrf())->get_token());
    ob_start();
    View::render('usuarios/usuarios-rows', $this->data, 'twig');
    $html = ob_get_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['html' => $html]);
    exit;
  }

  /** Comprueba duplicados de correo y teléfono para validación anticipada del formulario. */
  function validar_duplicados_usuario()
  {
    try {
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf'] ?? '')) throw new Exception('La solicitud de validación no es válida.');
      $targetId = (int) ($_POST['user_id'] ?? 0);
      $this->requirePermission($targetId > 0 ? 'usuarios-actualizar' : 'usuarios-crear');
      if ($targetId > 0 && !userModel::by_id($targetId)) throw new Exception('El usuario ya no existe.');

      $email = trim((string) sanitize_input($_POST['email'] ?? ''));
      $phone = trim((string) ($_POST['telefono'] ?? ''));
      $duplicates = self::duplicateContactMessages($email, $phone, $targetId ?: null);
      if ($duplicates && $targetId === 0) {
        $message = self::safeUserCreationError(implode(' ', $duplicates));
        Flasher::error($message);
        $this->jsonResponse(true, $message, 200, ['duplicates' => $duplicates, 'notification_html' => Flasher::flash()]);
      }
      $this->jsonResponse(true, '', 200, ['duplicates' => $duplicates]);
    } catch (Throwable $e) {
      if ((int) ($_POST['user_id'] ?? 0) === 0) {
        $message = 'Usuario no agregado, verificar datos';
        Flasher::error($message);
        $this->jsonResponse(false, $message, 422, ['notification_html' => Flasher::flash()]);
      }
      $this->jsonResponse(false, $e->getMessage(), 422);
    }
  }

  /** Limita los mensajes AJAX de alta a textos seguros y entendibles para administración. */
  private static function safeUserCreationError(string $error): string
  {
    $reasons = [];
    if (strpos($error, 'El correo electrónico ya está registrado') !== false) $reasons[] = 'El correo electrónico ya está registrado.';
    if (strpos($error, 'El número de celular ya está registrado') !== false) $reasons[] = 'El número de celular ya está registrado.';
    if (strpos($error, 'El usuario generado ya está registrado') !== false) $reasons[] = 'El usuario generado ya está registrado. Intenta nuevamente.';
    return $reasons ? 'Usuario no agregado, verificar datos. ' . implode(' ', $reasons) : 'Usuario no agregado, verificar datos';
  }

  /** Devuelve mensajes para los datos de contacto que ya pertenecen a otra cuenta. */
  private static function duplicateContactMessages(string $email, string $phone, ?int $excludeId = null): array
  {
    $messages = [];
    $emailSql = 'SELECT id FROM bee_users WHERE email = :email';
    $emailParams = ['email' => $email];
    $phoneSql = 'SELECT id FROM bee_users WHERE telefono = :telefono';
    $phoneParams = ['telefono' => $phone];
    if ($excludeId !== null) {
      $emailSql .= ' AND id <> :exclude_id';
      $phoneSql .= ' AND id <> :exclude_id';
      $emailParams['exclude_id'] = $phoneParams['exclude_id'] = $excludeId;
    }
    $owner = $excludeId !== null ? ' por otro usuario' : '';
    if ($email !== '' && userModel::query($emailSql . ' LIMIT 1', $emailParams)) $messages[] = 'El correo electrónico ya está registrado' . $owner . '.';
    if ($phone !== '' && userModel::query($phoneSql . ' LIMIT 1', $phoneParams)) $messages[] = 'El número de celular ya está registrado' . $owner . '.';
    return $messages;
  }

  function matriz_permisos()
  {
    $this->requireAdministratorRole('roles-consultar');
    $matrix = userModel::permission_matrix();
    $roleOrder = ['admin' => 0, 'capturista' => 1, 'consultor' => 2];
    usort($matrix['roles'], static function ($left, $right) use ($roleOrder) {
      $leftOrder = $roleOrder[$left['slug'] ?? ''] ?? 99;
      $rightOrder = $roleOrder[$right['slug'] ?? ''] ?? 99;
      return $leftOrder === $rightOrder
        ? strcasecmp((string) ($left['nombre'] ?? ''), (string) ($right['nombre'] ?? ''))
        : $leftOrder <=> $rightOrder;
    });
    $selectedUserId = (int) ($_GET['usuario'] ?? 0);
    $selectedUser = null;
    $matrixUsers = userModel::users_for_matrix();
    foreach ($matrixUsers as $item) if ((int) $item['id'] === $selectedUserId) { $selectedUser = $item; break; }
    $effectivePermissions = [];
    if ($selectedUser) {
      try { $effectivePermissions = array_column((new BeeRoleManager((string) $selectedUser['rol']))->getPermissions(), 'slug'); }
      catch (Throwable $e) { $effectivePermissions = []; }
      if (in_array((string) $selectedUser['rol'], ['admin', 'developer'], true) || in_array('admin-access', $effectivePermissions, true)) $effectivePermissions = array_column($matrix['permissions'], 'slug');
    }
    $operationLabels = [
      'inicio-consultar' => 'Consultar inicio', 'bienes-consultar' => 'Consultar bienes',
      'bienes-crear' => 'Crear bienes', 'bienes-actualizar' => 'Modificar bienes', 'bienes-activar' => 'Activar bienes', 'bienes-desactivar' => 'Desactivar bienes', 'bienes-imprimir' => 'Imprimir bienes (código QR)',
      'catalogos-consultar' => 'Consultar catálogos', 'catalogos-crear' => 'Crear catálogos',
      'catalogos-actualizar' => 'Modificar catálogos', 'catalogos-activar' => 'Activar catálogos', 'catalogos-desactivar' => 'Desactivar catálogos',
      'resguardos-consultar' => 'Consultar resguardos', 'movimientos-consultar' => 'Consultar movimientos',
      'traspasos-consultar' => 'Consultar traspasos', 'traspasos-crear' => 'Crear traspasos', 'traspasos-modificar' => 'Modificar traspasos', 'traspasos-cancelar' => 'Cancelar traspasos', 'traspasos-subir-documento' => 'Cargar documentos de traspaso', 'traspasos-autorizar' => 'Autorizar traspasos', 'traspasos-rechazar' => 'Rechazar traspasos', 'traspasos-ejecutar' => 'Ejecutar traspasos', 'traspasos-confirmar-entrega' => 'Confirmar entrega', 'traspasos-confirmar-recepcion' => 'Confirmar recepción', 'traspasos-generar-tarjeta' => 'Generar tarjeta de resguardo', 'traspasos-generar-resguardo' => 'Generar resguardo del equipo', 'traspasos-descargar-documentos' => 'Descargar documentos de traspaso',
      'documentos-consultar' => 'Consultar documentos', 'documentos-generar' => 'Generar / descargar documentos PDF',
      'reportes-exportar' => 'Exportar / descargar reporte de inventario (PDF)',
      'usuarios-consultar' => 'Consultar usuarios', 'usuarios-crear' => 'Crear usuarios', 'usuarios-actualizar' => 'Modificar usuarios', 'usuarios-eliminar' => 'Eliminar usuarios', 'usuarios-activar' => 'Activar usuarios', 'usuarios-desactivar' => 'Desactivar usuarios', 'usuarios-cerrar-sesion' => 'Cerrar sesión de usuarios',
      'roles-consultar' => 'Consultar roles y permisos', 'permisos-actualizar' => 'Modificar permisos de roles', 'admin-access' => 'Acceso total a Administración',
    ];
    foreach ($matrix['permissions'] as &$permission) $permission['operacion'] = $operationLabels[(string) $permission['slug']] ?? 'Permiso';
    unset($permission);
    $this->setEngine('twig');
    $this->setTitle('Matriz de permisos');
    $this->addToData('matrix', $matrix);
    $this->addToData('matrix_users', $matrixUsers);
    $this->addToData('selected_user', $selectedUser);
    $this->addToData('selected_user_id', $selectedUserId);
    $this->addToData('effective_permissions', $effectivePermissions);
    $menuSections = [
      'Inicio' => ['access' => ['inicio-consultar'], 'context' => 'Panel principal', 'permissions' => ['inicio-consultar']],
      'Bienes' => ['access' => ['bienes-consultar'], 'context' => 'Consulta y mantenimiento del inventario de bienes', 'permissions' => ['bienes-consultar', 'bienes-crear', 'bienes-actualizar', 'bienes-activar', 'bienes-desactivar', 'bienes-imprimir']],
      'Catálogos' => ['access' => ['catalogos-consultar'], 'context' => 'Consulta y mantenimiento de catálogos', 'permissions' => ['catalogos-consultar', 'catalogos-crear', 'catalogos-actualizar', 'catalogos-activar', 'catalogos-desactivar']],
      'Resguardos' => ['access' => ['resguardos-consultar'], 'context' => 'Consulta de resguardos', 'permissions' => ['resguardos-consultar']],
      'Movimientos' => ['access' => ['movimientos-consultar'], 'context' => 'Consulta del histórico de movimientos', 'permissions' => ['movimientos-consultar']],
      'Documentos' => ['access' => ['documentos-consultar'], 'context' => 'Consulta y generación/descarga de documentos PDF', 'permissions' => ['documentos-consultar', 'documentos-generar']],
      'Reportes' => ['access' => ['reportes-exportar'], 'context' => 'Exportación del reporte de inventario a PDF', 'permissions' => ['reportes-exportar']],
      'Administración' => ['access' => ['usuarios-consultar', 'roles-consultar'], 'context' => 'Administración de usuarios, roles y permisos', 'permissions' => ['usuarios-consultar', 'usuarios-crear', 'usuarios-actualizar', 'usuarios-eliminar', 'usuarios-activar', 'usuarios-desactivar', 'usuarios-cerrar-sesion', 'roles-consultar', 'permisos-actualizar', 'admin-access']],
    ];
    $permissionsBySlug = [];
    foreach ($matrix['permissions'] as $permission) $permissionsBySlug[(string) $permission['slug']] = $permission;
    $mappedSlugs = [];
    foreach ($menuSections as &$section) {
      $section['permission_rows'] = [];
      foreach ($section['permissions'] as $slug) {
        if (isset($permissionsBySlug[$slug])) {
          $section['permission_rows'][] = $permissionsBySlug[$slug];
          $mappedSlugs[] = $slug;
        }
      }
      $section['access_by_role'] = [];
      foreach ($matrix['roles'] as $role) {
        $roleSlugs = [];
        foreach ($matrix['permissions'] as $permission) {
          if (in_array((int) $permission['id'], $role['permisos_efectivos_ids'], true)) $roleSlugs[] = (string) $permission['slug'];
        }
        $section['access_by_role'][$role['slug']] = count(array_diff($section['access'], $roleSlugs)) === 0;
      }
    }
    unset($section);
    $unmappedPermissions = array_values(array_filter($matrix['permissions'], static fn($permission) => !in_array((string) $permission['slug'], $mappedSlugs, true)));
    if ($unmappedPermissions) {
      $menuSections['Permisos sin sección asociada'] = [
        'access' => [], 'context' => 'Registros reales de bee_permisos que no corresponden a una sección identificada del menú.',
        'permission_rows' => $unmappedPermissions, 'access_by_role' => []
      ];
      foreach ($matrix['roles'] as $role) $menuSections['Permisos sin sección asociada']['access_by_role'][$role['slug']] = true;
    }
    $this->addToData('permission_groups', $menuSections);
    $this->setView('usuarios/matriz-permisos');
    $this->render();
  }

  function guardar_permisos_rol()
  {
    $this->requireAdministratorRole('permisos-actualizar');
    try {
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf'] ?? '')) throw new Exception('La solicitud de permisos no es válida.');
      $roleSlug = trim((string) ($_POST['rol'] ?? ''));
      $permissionSlug = trim((string) ($_POST['permiso'] ?? ''));
      $enabled = $_POST['habilitado'] ?? null;
      if (!in_array($enabled, ['0', '1', 0, 1], true)) throw new Exception('El estado del permiso no es válido.');
      $roles = (new BeeRoleManager())->getRoles() ?: [];
      $roleExists = false;
      foreach ($roles as $role) if (($role['slug'] ?? '') === $roleSlug) { $roleExists = true; break; }
      if (!$roleExists) throw new Exception('El rol seleccionado no existe.');
      if (in_array($roleSlug, ['admin', 'developer'], true)) throw new Exception('Los permisos efectivos de este rol están protegidos y no pueden modificarse.');
      $permissions = userModel::permission_matrix()['permissions'];
      $permissionExists = false;
      foreach ($permissions as $permission) if (($permission['slug'] ?? '') === $permissionSlug) { $permissionExists = true; break; }
      if (!$permissionExists) throw new Exception('El permiso seleccionado no existe.');
      if (in_array($roleSlug, ['capturista', 'consultor'], true) && in_array($permissionSlug, ['admin-access', 'usuarios-consultar', 'usuarios-crear', 'usuarios-actualizar', 'usuarios-eliminar', 'usuarios-activar', 'usuarios-desactivar', 'usuarios-cerrar-sesion', 'roles-consultar', 'permisos-actualizar'], true) && (string) $enabled === '1') throw new Exception('Los perfiles Capturista y Consulta no pueden recibir acceso de administración.');
      $manager = new BeeRoleManager($roleSlug);
      $saved = (string) $enabled === '1' ? $manager->allow($permissionSlug) : $manager->deny($permissionSlug);
      if (!$saved) throw new Exception('No se pudo actualizar la relación entre el rol y el permiso.');
      $this->jsonResponse(true, 'Permiso actualizado.');
    } catch (Throwable $e) {
      $this->jsonResponse(false, $e->getMessage(), 422);
    }
  }

  function consultar_acceso_efectivo()
  {
    $this->requireAdministratorRole('roles-consultar');
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !$this->isAjaxRequest()) $this->jsonResponse(false, 'La solicitud de consulta no es válida.', 400);
    $userId = filter_var($_GET['usuario'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$userId) $this->jsonResponse(false, 'Selecciona un usuario válido.', 422);

    $selectedUser = null;
    foreach (userModel::users_for_matrix() as $item) {
      if ((int) $item['id'] === (int) $userId) { $selectedUser = $item; break; }
    }
    if (!$selectedUser) $this->jsonResponse(false, 'No se encontró el usuario seleccionado.', 404);

    $matrix = userModel::permission_matrix();
    $effectivePermissions = [];
    try { $effectivePermissions = array_column((new BeeRoleManager((string) $selectedUser['rol']))->getPermissions(), 'slug'); }
    catch (Throwable $e) { $effectivePermissions = []; }
    if (in_array((string) $selectedUser['rol'], ['admin', 'developer'], true) || in_array('admin-access', $effectivePermissions, true)) {
      $effectivePermissions = array_column($matrix['permissions'], 'slug');
    }

    $operationLabels = [
      'inicio-consultar' => 'Consultar inicio', 'bienes-consultar' => 'Consultar bienes',
      'bienes-crear' => 'Crear bienes', 'bienes-actualizar' => 'Modificar bienes', 'bienes-activar' => 'Activar bienes', 'bienes-desactivar' => 'Desactivar bienes', 'bienes-imprimir' => 'Imprimir bienes (código QR)',
      'catalogos-consultar' => 'Consultar catálogos', 'catalogos-crear' => 'Crear catálogos',
      'catalogos-actualizar' => 'Modificar catálogos', 'catalogos-activar' => 'Activar catálogos', 'catalogos-desactivar' => 'Desactivar catálogos',
      'resguardos-consultar' => 'Consultar resguardos', 'movimientos-consultar' => 'Consultar movimientos',
      'documentos-consultar' => 'Consultar documentos', 'documentos-generar' => 'Generar / descargar documentos PDF',
      'reportes-exportar' => 'Exportar / descargar reporte de inventario (PDF)',
      'usuarios-consultar' => 'Consultar usuarios', 'usuarios-crear' => 'Crear usuarios', 'usuarios-actualizar' => 'Modificar usuarios', 'usuarios-eliminar' => 'Eliminar usuarios', 'usuarios-activar' => 'Activar usuarios', 'usuarios-desactivar' => 'Desactivar usuarios', 'usuarios-cerrar-sesion' => 'Cerrar sesión de usuarios',
      'roles-consultar' => 'Consultar roles y permisos', 'permisos-actualizar' => 'Modificar permisos de roles', 'admin-access' => 'Acceso total a Administración',
    ];
    foreach ($matrix['permissions'] as &$permission) $permission['operacion'] = $operationLabels[(string) $permission['slug']] ?? 'Permiso';
    unset($permission);
    $menuSections = [
      'Inicio' => ['permissions' => ['inicio-consultar']],
      'Bienes' => ['permissions' => ['bienes-consultar', 'bienes-crear', 'bienes-actualizar', 'bienes-activar', 'bienes-desactivar', 'bienes-imprimir']],
      'Catálogos' => ['permissions' => ['catalogos-consultar', 'catalogos-crear', 'catalogos-actualizar', 'catalogos-activar', 'catalogos-desactivar']],
      'Resguardos' => ['permissions' => ['resguardos-consultar']],
      'Traspasos' => ['permissions' => ['traspasos-consultar','traspasos-crear','traspasos-modificar','traspasos-cancelar','traspasos-subir-documento','traspasos-autorizar','traspasos-rechazar','traspasos-ejecutar','traspasos-confirmar-entrega','traspasos-confirmar-recepcion','traspasos-generar-tarjeta','traspasos-generar-resguardo','traspasos-descargar-documentos']],
      'Movimientos' => ['permissions' => ['movimientos-consultar']],
      'Documentos' => ['permissions' => ['documentos-consultar', 'documentos-generar']],
      'Reportes' => ['permissions' => ['reportes-exportar']],
      'Administración' => ['permissions' => ['usuarios-consultar', 'usuarios-crear', 'usuarios-actualizar', 'usuarios-eliminar', 'usuarios-activar', 'usuarios-desactivar', 'usuarios-cerrar-sesion', 'roles-consultar', 'permisos-actualizar', 'admin-access']],
    ];
    $permissionsBySlug = [];
    foreach ($matrix['permissions'] as $permission) $permissionsBySlug[(string) $permission['slug']] = $permission;
    $mappedSlugs = [];
    foreach ($menuSections as &$section) {
      $section['permission_rows'] = [];
      foreach ($section['permissions'] as $slug) if (isset($permissionsBySlug[$slug])) {
        $section['permission_rows'][] = $permissionsBySlug[$slug];
        $mappedSlugs[] = $slug;
      }
    }
    unset($section);
    $unmapped = array_values(array_filter($matrix['permissions'], static fn($permission) => !in_array((string) $permission['slug'], $mappedSlugs, true)));
    if ($unmapped) $menuSections['Permisos sin sección asociada'] = ['permission_rows' => $unmapped];

    $operationColumns = [];
    foreach ($matrix['permissions'] as $permission) {
      $operation = (string) ($permission['operacion'] ?? 'Permiso');
      if (!in_array($operation, $operationColumns, true)) $operationColumns[] = $operation;
    }
    foreach ($menuSections as &$section) {
      $section['effective_cells'] = [];
      foreach ($operationColumns as $operation) {
        $section['effective_cells'][$operation] = array_values(array_filter(
          $section['permission_rows'],
          static fn($permission) => (string) ($permission['operacion'] ?? 'Permiso') === $operation
        ));
      }
    }
    unset($section);

    ob_start();
    View::render('usuarios/acceso-efectivo', [
      'selected_user' => $selectedUser,
      'permission_groups' => $menuSections,
      'operation_columns' => $operationColumns,
      'effective_permissions' => $effectivePermissions,
    ], 'twig');
    $html = ob_get_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'html' => $html]);
    exit;
  }

  function cambiar_estado_usuario($id = null)
  {
    try {
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf'] ?? '')) throw new Exception('La solicitud de estado no es válida.');
      $targetId = (int) $id;
      $target = userModel::by_id($targetId);
      if (!$target || $targetId === (int) get_user('id')) throw new Exception('No es posible cambiar el estado de esta cuenta.');
      $this->requirePermission((int) $target['activo'] === 1 ? 'usuarios-desactivar' : 'usuarios-activar');
      $newState = (int) $target['activo'] === 1 ? 0 : 1;
      if (!userModel::update_by_id($targetId, ['activo' => $newState, 'auth_token' => $newState ? ($target['auth_token'] ?? null) : null])) throw new Exception('No se pudo actualizar el estado.');
      Flasher::success($newState ? 'Usuario activado.' : 'Usuario desactivado.');
    } catch (Throwable $e) { Flasher::error($e->getMessage()); }
    Redirect::to('admin/usuarios');
  }

  function actualizar_usuario($id = null)
  {
    $this->requirePermission('usuarios-actualizar');
    try {
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf'] ?? '')) throw new Exception('La solicitud de edición no es válida.');
      $targetId = (int) $id;
      $target = userModel::by_id($targetId);
      if (!$target) throw new Exception('El usuario ya no existe.');
      // El nombre de acceso se conserva y no se incluye en los cambios de edición.
      $name = trim((string) ($_POST['nombre'] ?? ''));
      $email = trim((string) ($_POST['email'] ?? ''));
      $role = trim((string) ($_POST['rol'] ?? ''));
      $paternal = trim((string) ($_POST['apellido_paterno'] ?? ''));
      $maternal = trim((string) ($_POST['apellido_materno'] ?? ''));
      $phone = trim((string) ($_POST['telefono'] ?? ''));
      $length = function ($value) { return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value); };
      $fullName = trim($name . ' ' . $paternal . ' ' . $maternal);
      if ($name === '' || $paternal === '' || $maternal === '' || $length($name) > 80 || $length($paternal) > 80 || $length($maternal) > 80 || $length($fullName) > 150) throw new Exception('Nombre y apellidos son obligatorios y deben respetar sus límites de longitud.');
      if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $length($email) > 100) throw new Exception('El correo electrónico no es válido o supera 100 caracteres.');
      if ($phone !== '' && !preg_match('/^[0-9]{7,15}$/', $phone)) throw new Exception('El teléfono debe contener únicamente entre 7 y 15 dígitos.');
      $validRoles = array_column((new BeeRoleManager())->getRoles() ?: [], 'slug');
      if ($role === '' || !in_array($role, $validRoles, true)) throw new Exception('El rol seleccionado no existe.');
      if ($targetId === (int) get_user('id') && $role !== (string) ($target['rol'] ?? '')) throw new Exception('No puedes cambiar tu propio rol.');
      $duplicates = self::duplicateContactMessages($email, $phone, $targetId);
      if ($duplicates) throw new Exception(implode(' ', $duplicates));
      if (!userModel::update_by_id($targetId, ['nombre' => $fullName, 'apellido_paterno' => $paternal, 'apellido_materno' => $maternal, 'email' => $email, 'telefono' => $phone, 'rol' => $role])) throw new Exception('No se pudieron guardar los cambios.');
      if ($this->isAjaxRequest()) $this->jsonResponse(true, 'La información del usuario se actualizó.');
      Flasher::success('La información del usuario se actualizó.');
    } catch (Throwable $e) {
      if ($this->isAjaxRequest()) $this->jsonResponse(false, $e->getMessage(), 422);
      Flasher::error($e->getMessage());
    }
    Redirect::to('admin/usuarios');
  }

  private static function generateRandomPassword(): string
  {
    $sets = [
      'abcdefghijklmnopqrstuvwxyz',
      'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
      '0123456789',
      '!@#$%^&*_',
    ];
    $allCharacters = implode('', $sets);

    do {
      $characters = [];
      foreach ($sets as $set) {
        $characters[] = $set[random_int(0, strlen($set) - 1)];
      }
      while (count($characters) < 12) {
        $characters[] = $allCharacters[random_int(0, strlen($allCharacters) - 1)];
      }
      for ($index = count($characters) - 1; $index > 0; $index--) {
        $swapIndex = random_int(0, $index);
        [$characters[$index], $characters[$swapIndex]] = [$characters[$swapIndex], $characters[$index]];
      }
      $password = implode('', $characters);
    } while (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*_])[A-Za-z\d!@#$%^&*_]{8,20}$/', $password));

    return $password;
  }

  /** Crea un identificador con nombre, iniciales de apellidos y dos dígitos aleatorios. */
  private static function generateUniqueUsername(string $name, string $paternal, string $maternal): string
  {
    $ascii = static function (string $value): string {
      $converted = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : $value;
      return preg_replace('/[^a-zA-Z0-9]/', '', (string) $converted);
    };
    $firstName = strtolower($ascii($name));
    $initials = strtoupper(substr($ascii($paternal), 0, 1) . substr($ascii($maternal), 0, 1));
    if ($firstName === '' || strlen($initials) !== 2) throw new Exception('No se pudo generar un usuario con el nombre y apellidos capturados.');
    $firstName = substr($firstName, 0, 16);

    for ($attempt = 0; $attempt < 1000; $attempt++) {
      $candidate = $firstName . $initials . str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);
      if (!userModel::query('SELECT id FROM bee_users WHERE username = :username LIMIT 1', ['username' => $candidate])) return $candidate;
    }
    throw new Exception('El usuario generado ya está registrado. Intenta nuevamente.');
  }

  function post_usuarios()
  {
    try {
      $this->requirePermission('usuarios-crear');
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_posted_data(['email', 'nombre', 'apellido_paterno', 'apellido_materno'], $_POST)) {
        throw new Exception('Por favor completa el formulario.');
      }

      if (!Csrf::validate($_POST['csrf'] ?? '')) {
        throw new Exception(get_bee_message(0));
      }

      $email = trim((string) sanitize_input($_POST['email']));
      $name = trim((string) sanitize_input($_POST['nombre']));
      $paternal = trim((string) sanitize_input($_POST['apellido_paterno']));
      $maternal = trim((string) sanitize_input($_POST['apellido_materno']));
      $phone = (string) ($_POST['telefono'] ?? '');
      $fullName = trim($name . ' ' . $paternal . ' ' . $maternal);
      $role = trim((string) sanitize_input($_POST['rol'] ?? '')) ?: 'consultor';
      $errorMessage = '';
      $errors = 0;

      $length = function ($value) { return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value); };
      if ($name === '' || $paternal === '' || $maternal === '' || $length($name) > 80 || $length($paternal) > 80 || $length($maternal) > 80 || $length($fullName) > 150) {
        $errorMessage .= '- Nombre, apellido paterno y apellido materno son obligatorios; cada campo admite hasta 80 caracteres y el nombre completo hasta 150.<br>';
        $errors++;
      }

      if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $length($email) > 100) {
        $errorMessage .= '- El correo electrónico no es válido o supera 100 caracteres.<br>';
        $errors++;
      }

      if (is_temporary_email($email)) {
        $errorMessage .= '- El dominio del correo electrónico no está autorizado.<br>';
        $errors++;
      }

      if ($phone !== '' && !preg_match('/^[0-9]{7,15}$/', $phone)) {
        $errorMessage .= '- El teléfono debe contener únicamente entre 7 y 15 dígitos.<br>';
        $errors++;
      }

      $validRoles = array_column((new BeeRoleManager())->getRoles() ?: [], 'slug');
      if ($role === '' || !in_array($role, $validRoles, true)) {
        $errorMessage .= '- Selecciona un rol existente.<br>';
        $errors++;
      }

      if ($errors > 0) {
        throw new Exception($errorMessage);
      }

      $duplicates = self::duplicateContactMessages($email, $phone);
      if ($duplicates) throw new Exception(implode(' ', $duplicates));

      $username = self::generateUniqueUsername($name, $paternal, $maternal);
      $password = self::generateRandomPassword();
      // La fecha y el estado activo inicial se asignan exclusivamente en servidor.
      $user = [
        'username' => $username,
        'password' => password_hash($password . AUTH_SALT, PASSWORD_BCRYPT),
        'email' => $email,
        'nombre' => $fullName,
        'apellido_paterno' => $paternal,
        'apellido_materno' => $maternal,
        'telefono' => $phone,
        'rol' => $role,
        'activo' => 1,
        'created_at' => now()
      ];

      // Insertando el registro en la base de datos
      if (!$id = userModel::add(userModel::$t1, $user)) {
        unset($password);
        throw new Exception('Hubo un problema al agregar el usuario.');
      }

      $safeName = htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $safeUsername = htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $safePassword = htmlspecialchars($password, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $safeLoginUrl = htmlspecialchars(get_base_url() . 'login', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $emailBody = '<p>Hola, ' . $safeName . ':</p>'
        . '<p>Tu cuenta de acceso al sistema Bienes Informáticos ha sido creada correctamente.</p>'
        . '<p><strong>Usuario:</strong> ' . $safeUsername . '<br>'
        . '<strong>Contraseña temporal:</strong> ' . $safePassword . '</p>'
        . '<p>Te recomendamos mantener estas credenciales seguras y no compartirlas con otras personas.</p>'
        . '<p>Ya puedes <a href="' . $safeLoginUrl . '">iniciar sesión en el sistema</a>.</p>'
        . '<p>Saludos.</p>';
      $emailAlt = "Hola, {$fullName}:\n\nTu cuenta de acceso al sistema Bienes Informáticos ha sido creada correctamente.\n\n"
        . "Usuario: {$username}\nContraseña temporal: {$password}\n\n"
        . "Te recomendamos mantener estas credenciales seguras y no compartirlas con otras personas.\n"
        . 'Ya puedes iniciar sesión en el sistema: ' . get_base_url() . "login\n\nSaludos.";
      $emailSent = false;
      try {
        $emailSent = send_email(get_siteemail(), $email, 'Credenciales de acceso - Bienes Informáticos', $emailBody, $emailAlt) === true;
      } catch (Throwable $mailError) {
        // No incluir datos SMTP ni la contraseña temporal en alertas o logs.
      }
      unset($password, $safePassword, $emailBody, $emailAlt);

      $message = $emailSent
        ? 'Usuario Agregado'
        : 'Usuario Agregado, pero no se pudieron enviar las credenciales por correo.';
      if ($this->isAjaxRequest()) {
        if ($emailSent) Flasher::success($message);
        else Flasher::warn($message);
        $this->jsonResponse(true, $message, 200, ['email_sent' => $emailSent, 'notification_html' => Flasher::flash()]);
      }
      if ($emailSent) Flasher::success($message);
      else Flasher::warn($message);
      Redirect::back();

    } catch (Exception $e) {
      unset($password, $safePassword, $emailBody, $emailAlt);
      $message = self::safeUserCreationError($e->getMessage());
      if ($this->isAjaxRequest()) {
        Flasher::error($message);
        $this->jsonResponse(false, $message, 422, ['notification_html' => Flasher::flash()]);
      }
      Flasher::error($message);
      Redirect::back();
    }
  }

  function borrar_usuario($id = null)
  {
    try {
      $this->requirePermission('usuarios-eliminar');
      if (!Csrf::validate($_GET['_t'])) {
        throw new Exception(get_bee_message(0));
      }

      // Verificar que exista el usuario
      if (!$user = userModel::by_id($id)) {
        throw new Exception('No existe el usuario en la base de datos.');
      }

      // Validar que no sea el propio usuario que está solicitando la petición
      if ($id == get_user('id')) {
        throw new Exception('No puedes realizar esta acción sobre ti mismo.');
      }

      // Borrando el registro de la base de datos
      if (!userModel::remove(userModel::$t1, ['id' => $id], 1)) {
        throw new Exception('Hubo un problema al borrar el usuario.');
      }

      Flasher::success(sprintf('Usuario <b>%s</b> borrado con éxito.', $user['username']));
      Redirect::back();

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  function destruir_sesion($id = null)
  {
    try {
      $this->requirePermission('usuarios-cerrar-sesion');
      if (!Csrf::validate($_GET['_t'])) {
        throw new Exception(get_bee_message(0));
      }

      // Verificar que exista el usuario
      if (!$user = userModel::by_id($id)) {
        throw new Exception('No existe el usuario en la base de datos.');
      }

      // Validar que no sea el propio usuario que está solicitando la petición
      if ($id == get_user('id')) {
        throw new Exception('No puedes realizar esta acción sobre ti mismo.');
      }

      // Verificar que el usuario tenga una sesión activa
      if (empty($user['auth_token']) || $user['auth_token'] == null) {
        throw new Exception('El usuario no tiene una sesión activa.');
      }

      // Cerrando su sesión
      if (!userModel::update(userModel::$t1, ['id' => $id], ['auth_token' => null])) {
        throw new Exception('Hubo un problema al actualizar el usuario.');
      }

      Flasher::success(sprintf('La sesión de <b>%s</b> ha sido cerrada con éxito.', $user['username']));
      Redirect::back();

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  function asignar_rol_usuario($id = null)
  {
    $this->requirePermission('usuarios-actualizar');
    try {
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf'] ?? '')) throw new Exception('La solicitud de cambio de perfil no es válida.');
      $targetId = (int) $id;
      $target = userModel::by_id($targetId);
      $role = trim((string) ($_POST['rol'] ?? ''));
      if (!$target || $targetId === (int) get_user('id')) throw new Exception('No es posible cambiar el perfil de esta cuenta.');
      $assignable = ['capturista', 'consultor'];
      if (!in_array($role, $assignable, true)) throw new Exception('El perfil seleccionado no se puede asignar.');
      if ((int) $target['activo'] !== 1 || !userModel::assign_role($targetId, $role)) throw new Exception('Solo se puede cambiar el perfil de una cuenta activa.');
      if ($this->isAjaxRequest()) $this->jsonResponse(true, 'Rol del usuario actualizado.');
      Flasher::success('El perfil del usuario se actualizó.');
    } catch (Throwable $e) {
      if ($this->isAjaxRequest()) $this->jsonResponse(false, $e->getMessage(), 422);
      Flasher::error($e->getMessage());
    }
    Redirect::to('admin/usuarios');
  }

  private function isAjaxRequest(): bool
  {
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
  }

  /** La matriz exige el rol administrador y su permiso administrativo vigente. */
  private function requireAdministratorRole(string $permission): void
  {
    $user = $this->requireActiveAccount();
    if (($user['rol'] ?? '') === 'admin' && $this->hasPermission('admin-access', $user) && $this->hasPermission($permission, $user)) return;
    if ($this->isAjaxRequest()) $this->jsonResponse(false, 'No tienes autorización para administrar roles y permisos.', 403);
    Flasher::error('No tienes autorización para administrar roles y permisos.');
    Redirect::to('admin');
  }

  private function jsonResponse(bool $success, string $message, int $status = 200, array $extra = []): void
  {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
  }

  ////////////////////////////////////////////////////
  ////////////////////////////////////////////////////
  ////////////////////////////////////////////////////
  //////// PRODUCTOS
  ////////////////////////////////////////////////////
  ////////////////////////////////////////////////////
  ////////////////////////////////////////////////////
  function productos()
  {
    $this->adminOnly();
    // Formulario para agregar nuevo registro
    $form = new BeeFormBuilder('agregar-producto', 'agregar-producto', ['needs-validation'], 'admin/post_productos', true, true);
    
    // Inputs
    $form->addCustomFields(insert_inputs());
    $form->addTextField('nombre', 'Nombre del producto', ['form-control'], 'product-name', true);
    $form->addTextField('sku', 'SKU o número de rastreo', ['form-control'], 'product-sku');
    $form->addTextareaField('descripcion', 'Descripción del producto', 4, 5, ['form-control'], 'product-description');
    $form->addNumberField('precio', 'Precio principal', 1, 999999999, 'any', null, ['form-control'], 'product-price', true);
    $form->addNumberField('precio_comparacion', 'Precio de comparación', 1, 999999999, 'any', null, ['form-control'], 'product-compare-price');

    $form->addFileField('imagen', 'Imagen principal del producto', ['form-control'], 'product-imagen', true);

    $form->addCustomFields('<hr>');

    $form->addCheckboxField('rastrear_stock', 'Seguimiento de stock', 'true', ['form-check-input'], 'trackStock', false);
    $form->addNumberField('stock', 'Unidades disponibles', 1, 999999999, 1, null, ['form-control'], 'stock', false);

    $form->addButton('submit', 'submit', 'Agregar producto', ['btn btn-success'], 'submit-button');

    $this->setTitle('Productos');
    $this->addToData('form'     , $form->getFormHtml());
    $this->addToData('productos', productoModel::all_paginated());
    $this->addToData('slug'     , 'productos');
    $this->setView('productos/productos');
    $this->render();
  }

  function post_productos()
  {
    try {
      $this->adminOnly();
      if (!check_posted_data(['nombre','sku','descripcion','precio','precio_comparacion','stock'], $_POST)) {
        throw new Exception('Por favor completa el formulario.');
      }

      if (!Csrf::validate($_POST['csrf'])) {
        throw new Exception(get_bee_message(0));
      }

      // Definición de variables
      array_map('sanitize_input', $_POST);
      $nombre             = $_POST['nombre'];
      $sku                = $_POST["sku"];
      $descripcion        = $_POST["descripcion"];
      $precio             = (float) $_POST["precio"];
      $precio_comparacion = (float) $_POST["precio_comparacion"];
      $rastrear_stock     = isset($_POST["rastrear_stock"]) ? 1 : 0;
      $stock              = (int) $_POST["stock"];
      $imagen             = $_FILES["imagen"];
      $errorMessage       = '';
      $errors             = 0;

      // Crear slug con base al nombre del producto
      $slugify = new Slugify();
      $slug    = $slugify->slugify($nombre);

      // Verificar que no exista ya un producto con el sku si es que no está vacío
      $sql = 'SELECT * FROM productos WHERE sku = :sku OR nombre = :nombre OR slug = :slug';
      if (productoModel::query($sql, ['sku' => $sku, 'nombre' => $nombre, 'slug' => $slug])) {
        throw new Exception('Ya existe un producto registrado con el mismo SKU o nombre.');
      }

      // Validar longitud del nombre, no mayor a 150 caracteres
      if (strlen($nombre) > 150) {
        $errorMessage .= '- El nombre del producto debe ser menor a 150 caracteres.' . PHP_EOL;
        $errors++;
      }

      // Validar el precio regular del producto
      if ($precio == 0) {
        $errorMessage .= '- Ingresa un precio mayor a 0.' . PHP_EOL;
        $errors++;
      }

      // Validar el precio de comparación si no es igual a 0
      if ($precio_comparacion != 0 && $precio_comparacion < $precio) {
        $errorMessage .= '- El precio de comparación debe ser mayor al precio principal del producto.' . PHP_EOL;
        $errors++;
      }

      // Validación de la imagen
      if ($imagen['error'] !== 0) {
        $errorMessage .= '- Selecciona una imagen de producto válida por favor.' . PHP_EOL;
        $errors++;
      }

      // Procesar imagen
      $tmp_name = $imagen['tmp_name'];
      $filename = $imagen['name'];
      $type     = $imagen['type'];
      $ext      = pathinfo($filename, PATHINFO_EXTENSION);
      $new_name = generate_filename() . '.' . $ext;

      if (!move_uploaded_file($tmp_name, UPLOADS . $new_name)) {
        $errorMessage .= '- Hubo un problema al subir el archivo de imagen.' . PHP_EOL;
        $errors++;
      }

      if ($errors > 0) {
        if (is_file(UPLOADS . $new_name)) {
          unlink(UPLOADS . $new_name);
        }
        throw new Exception($errorMessage);
      }

      // Array de información del producto
      $data =
      [
        'nombre'             => $nombre,
        'slug'               => $slug,
        'sku'                => empty($sku) ? random_password(8, 'numeric') : $sku,
        'descripcion'        => $descripcion,
        'precio'             => $precio,
        'precio_comparacion' => $precio_comparacion,
        'rastrear_stock'     => $rastrear_stock,
        'stock'              => empty($stock) ? 0 : $stock,
        'imagen'             => $new_name,
        'creado'             => now()
      ];

      // Agregar producto a la base de datos
      if (!$id = productoModel::insertOne($data)) {
        throw new Exception('Hubo un error, intenta de nuevo.');
      }

      $producto = productoModel::by_id($id);

      Flasher::success(sprintf('Nuevo producto <b>%s</b> agregado con éxito.', $producto['nombre']));
      Redirect::back();

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }
}
