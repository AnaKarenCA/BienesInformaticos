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
    $user = $this->requireApprovedAccount();
    $this->addToData('csrf', (new Csrf())->get_token());
    $this->addToData('current_user', $user);
    $this->addToData('flash_html', Flasher::flash());
    $this->addToData('can_manage_goods', $this->hasPermission('bienes-guardar', $user));
    $this->addToData('can_inactivate_goods', $this->hasPermission('bienes-inactivar', $user));
    $this->addToData('can_view_catalogs', $this->hasPermission('inventario-consultar', $user));
    $this->addToData('can_manage_catalogs', $this->hasPermission('catalogos-guardar', $user));
    $this->addToData('can_inactivate_catalogs', $this->hasPermission('catalogos-inactivar', $user));
    $this->addToData('can_delete_catalogs', $this->hasPermission('catalogos-eliminar', $user));
    $this->addToData('can_change_custody', $this->hasPermission('bienes-inactivar', $user));
    $this->addToData('can_access_documents', $this->hasPermission('inventario-consultar', $user));
    $this->addToData('can_admin_users', $this->hasPermission('admin-access', $user));
    $this->addToData('can_manage_role_permissions', ($user['rol'] ?? '') === 'admin' && $this->hasPermission('admin-access', $user));
    $this->addToData('can_generate_documents', $this->hasPermission('documentos-generar', $user));
    $this->addToData('can_export_reports', $this->hasPermission('reportes-exportar', $user));
    if (($user['rol'] ?? '') === 'admin') {
      $this->addToData('pending_user_count', userModel::count_pending());
    }
  }
  
  function index()
  {
    $this->requirePermission('inventario-consultar');
    $this->setEngine('twig');
    $this->setTitle('Inicio');
    $this->addToData('resumen', BienModel::resumen());
    $this->addToData('recientes', BienModel::recientes());
    $this->addToData('csrf', (new Csrf())->get_token());
    $this->addToData('current_user', get_user());
    $this->addToData('flash_html', Flasher::flash());
    $this->setView('dashboard/index');
    $this->render();
  }

  function perfil()
  {
    $this->setTitle('Perfil de usuario');
    $this->setView('perfil');
    $this->render();
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
    $this->adminOnly();
    $search = trim((string) ($_GET['q'] ?? ''));
    $status = trim((string) ($_GET['estado'] ?? ''));
    $this->setTitle('Usuarios');
    $this->setEngine('twig');
    $this->addToData('users', userModel::all_paginated($search, $status));
    $this->addToData('pending_users', userModel::pending_approval());
    $this->addToData('user_roles', (new BeeRoleManager())->getRoles() ?: []);
    $this->addToData('filters', ['q' => $search, 'estado' => $status]);
    $this->setView('usuarios/usuarios');
    $this->render();
  }

  /** Lista parcial para los filtros dinámicos de la vista de usuarios. */
  function usuarios_ajax()
  {
    $this->adminOnly();
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'xmlhttprequest') {
      http_response_code(400);
      exit;
    }
    $search = trim((string) ($_GET['q'] ?? ''));
    $status = trim((string) ($_GET['estado'] ?? ''));
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

  function matriz_permisos()
  {
    $this->requireAdministratorRole();
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
      'inventario-consultar' => 'Acceso y consulta', 'bienes-guardar' => 'Crear y modificar',
      'bienes-inactivar' => 'Cambiar estado / baja', 'documentos-generar' => 'Generar documentos',
      'catalogos-guardar' => 'Crear y modificar', 'catalogos-inactivar' => 'Activar / desactivar',
      'catalogos-eliminar' => 'Eliminar', 'reportes-exportar' => 'Exportar', 'admin-access' => 'Administrar',
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
      'Principal' => ['access' => ['inventario-consultar'], 'context' => 'Inicio e Identificar bien', 'permissions' => ['inventario-consultar']],
      'Inventario' => ['access' => ['inventario-consultar'], 'context' => 'Consultar y administrar bienes', 'permissions' => ['bienes-guardar', 'bienes-inactivar']],
      'Documentos y movimientos' => ['access' => ['inventario-consultar'], 'context' => 'Resguardos, histórico y documentos', 'permissions' => ['documentos-generar']],
      'Catálogos' => ['access' => ['inventario-consultar'], 'context' => 'Catálogos visibles en la navegación principal', 'permissions' => ['catalogos-guardar', 'catalogos-inactivar', 'catalogos-eliminar']],
      'Reportes' => ['access' => ['inventario-consultar', 'reportes-exportar'], 'context' => 'Ruta de exportación disponible en el controlador de reportes', 'permissions' => ['reportes-exportar']],
      'Administración' => ['access' => ['admin-access'], 'context' => 'Usuarios, roles y permisos', 'permissions' => ['admin-access']],
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
    $this->requireAdministratorRole();
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
      if (in_array($roleSlug, ['capturista', 'consultor'], true) && $permissionSlug === 'admin-access' && (string) $enabled === '1') throw new Exception('Los perfiles Capturista y Consultor no pueden recibir acceso de administración.');
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
    $this->requireAdministratorRole();
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
      'inventario-consultar' => 'Acceso y consulta', 'bienes-guardar' => 'Crear y modificar',
      'bienes-inactivar' => 'Cambiar estado / baja', 'documentos-generar' => 'Generar documentos',
      'catalogos-guardar' => 'Crear y modificar', 'catalogos-inactivar' => 'Activar / desactivar',
      'catalogos-eliminar' => 'Eliminar', 'reportes-exportar' => 'Exportar', 'admin-access' => 'Administrar',
    ];
    foreach ($matrix['permissions'] as &$permission) $permission['operacion'] = $operationLabels[(string) $permission['slug']] ?? 'Permiso';
    unset($permission);
    $menuSections = [
      'Principal' => ['permissions' => ['inventario-consultar']],
      'Inventario' => ['permissions' => ['bienes-guardar', 'bienes-inactivar']],
      'Documentos y movimientos' => ['permissions' => ['documentos-generar']],
      'Catálogos' => ['permissions' => ['catalogos-guardar', 'catalogos-inactivar', 'catalogos-eliminar']],
      'Reportes' => ['permissions' => ['reportes-exportar']],
      'Administración' => ['permissions' => ['admin-access']],
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
    $this->adminOnly();
    try {
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf'] ?? '')) throw new Exception('La solicitud de estado no es válida.');
      $targetId = (int) $id;
      $target = userModel::by_id($targetId);
      if (!$target || $targetId === (int) get_user('id')) throw new Exception('No es posible cambiar el estado de esta cuenta.');
      if (($target['estado'] ?? '') !== 'aprobada') throw new Exception('Solo se puede activar o desactivar una cuenta aprobada.');
      $newState = (int) $target['activo'] === 1 ? 0 : 1;
      if (!userModel::update_by_id($targetId, ['activo' => $newState, 'auth_token' => $newState ? ($target['auth_token'] ?? null) : null])) throw new Exception('No se pudo actualizar el estado.');
      Flasher::success($newState ? 'Usuario activado.' : 'Usuario desactivado.');
    } catch (Throwable $e) { Flasher::error($e->getMessage()); }
    Redirect::to('admin/usuarios');
  }

  function actualizar_usuario($id = null)
  {
    $this->adminOnly();
    try {
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf'] ?? '')) throw new Exception('La solicitud de edición no es válida.');
      $targetId = (int) $id;
      $target = userModel::by_id($targetId);
      if (!$target) throw new Exception('El usuario ya no existe.');
      $username = trim((string) ($_POST['username'] ?? ''));
      $name = trim((string) ($_POST['nombre'] ?? ''));
      $email = trim((string) ($_POST['email'] ?? ''));
      $role = trim((string) ($_POST['rol'] ?? ''));
      $nameLength = function_exists('mb_strlen') ? mb_strlen($name, 'UTF-8') : strlen($name);
      if ($name === '' || $nameLength > 150) throw new Exception('El nombre es obligatorio y no puede superar 150 caracteres.');
      if (!preg_match('/^[a-zA-Z0-9]{5,20}$/', $username)) throw new Exception('El usuario debe contener entre 5 y 20 letras o números.');
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('El correo electrónico no es válido.');
      $validRoles = array_column((new BeeRoleManager())->getRoles() ?: [], 'slug');
      if ($role === '' || !in_array($role, $validRoles, true)) throw new Exception('El rol seleccionado no existe.');
      if ($targetId === (int) get_user('id') && $role !== (string) ($target['rol'] ?? '')) throw new Exception('No puedes cambiar tu propio rol.');
      $duplicate = userModel::query('SELECT id FROM bee_users WHERE id <> :id AND (username = :username OR email = :email)', ['id' => $targetId, 'username' => $username, 'email' => $email]);
      if ($duplicate) throw new Exception('Ese usuario o correo ya está registrado en otra cuenta.');
      if (!userModel::update_by_id($targetId, ['username' => $username, 'nombre' => $name, 'email' => $email, 'rol' => $role])) throw new Exception('No se pudieron guardar los cambios.');
      if ($this->isAjaxRequest()) $this->jsonResponse(true, 'La información del usuario se actualizó.');
      Flasher::success('La información del usuario se actualizó.');
    } catch (Throwable $e) {
      if ($this->isAjaxRequest()) $this->jsonResponse(false, $e->getMessage(), 422);
      Flasher::error($e->getMessage());
    }
    Redirect::to('admin/usuarios');
  }

  function post_usuarios()
  {
    try {
      $this->adminOnly();
      if (!check_posted_data(['username','email','password'], $_POST)) {
        throw new Exception('Por favor completa el formulario.');
      }

      if (!Csrf::validate($_POST['csrf'])) {
        throw new Exception(get_bee_message(0));
      }

      // Definición de variables
      array_map('sanitize_input', $_POST);
      $username     = $_POST['username'];
      $email        = $_POST['email'];
      $password     = $_POST['password'];
      $errorMessage = '';
      $errors       = 0;

      // Verificar que no exista ya un usuario con ese username o correo electrónico
      $sql = 'SELECT * FROM bee_users WHERE username = :username OR email = :email';
      if (userModel::query($sql, ['username' => $username, 'email' => $email])) {
        throw new Exception('Ya existe un usuario registrado con ese nombre de usuario o correo electrónico.');
      }

      // Validaciones necesarias
      if (!preg_match('/^[a-zA-Z0-9]{5,20}$/', $username)) {
        $errorMessage .= '- Tu nombre de usuario debe estar formado por mínimo 5 caracteres y máximo 20.<br>';
        $errors++;
      }

      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage .= '- El correo electrónico no es válido.<br>';
        $errors++;
      }

      if (is_temporary_email($email)) {
        $errorMessage .= '- El dominio del correo electrónico no está autorizado.<br>';
        $errors++;
      }

      if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*_-])[A-Za-z\d!@#$%^&*_-]{5,20}$/', $password)) {
        $errorMessage .= '- La contraseña debe ser de entre 5 y 20 caracteres, por lo menos debe contar con: 1 letra minúscula, 1 letra mayúscula, 1 digito y 1 caracter especial de entre <b>!@#$%^&*_-</b>';
        $errors++;
      }

      if ($errors > 0) {
        throw new Exception($errorMessage);
      }

      // Agregar el nuevo usuario a la base de datos
      $user     =
      [
        'username'   => $username,
        'email'      => $email,
        'rol'        => 'consultor',
        'activo'     => 1,
        'estado'     => 'aprobada',
        'password'   => password_hash($password . AUTH_SALT, PASSWORD_BCRYPT),
        'created_at' => now()
      ];

      // Insertando el registro en la base de datos
      if (!$id = userModel::add(userModel::$t1, $user)) {
        throw new Exception('Hubo un problema al agregar el usuario.');
      }

      if ($this->isAjaxRequest()) $this->jsonResponse(true, 'Nuevo usuario agregado con éxito.');
      Flasher::success(sprintf('Nuevo usuario agregado con éxito. Usuario: <b>%s</b>.', $user['username']));
      Redirect::back();

    } catch (Exception $e) {
      if ($this->isAjaxRequest()) $this->jsonResponse(false, strip_tags($e->getMessage()), 422);
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  function borrar_usuario($id = null)
  {
    try {
      $this->adminOnly();
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
      $this->adminOnly();
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

  function aprobar_usuario($id = null)
  {
    $this->adminOnly();
    try {
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf'] ?? '')) throw new Exception('La solicitud de aprobación no es válida.');
      $targetId = (int) $id;
      $target = userModel::by_id($targetId);
      if (!$target || $targetId === (int) get_user('id')) throw new Exception('No es posible aprobar esta cuenta.');
      if (($target['estado'] ?? '') !== 'pendiente' || !userModel::approve($targetId)) throw new Exception('La cuenta ya no está pendiente o no se pudo aprobar.');
      Flasher::success('Cuenta aprobada. Se asignó inicialmente el perfil de consulta.');
    } catch (Throwable $e) {
      Flasher::error($e->getMessage());
    }
    Redirect::to('admin/usuarios');
  }

  function rechazar_usuario($id = null)
  {
    $this->adminOnly();
    try {
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf'] ?? '')) throw new Exception('La solicitud de rechazo no es válida.');
      $targetId = (int) $id;
      $target = userModel::by_id($targetId);
      if (!$target || $targetId === (int) get_user('id')) throw new Exception('No es posible rechazar esta cuenta.');
      if (($target['estado'] ?? '') !== 'pendiente' || !userModel::reject($targetId)) throw new Exception('La cuenta ya no está pendiente o no se pudo rechazar.');
      Flasher::success('La solicitud fue rechazada.');
    } catch (Throwable $e) {
      Flasher::error($e->getMessage());
    }
    Redirect::to('admin/usuarios');
  }

  function asignar_rol_usuario($id = null)
  {
    $this->adminOnly();
    try {
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf'] ?? '')) throw new Exception('La solicitud de cambio de perfil no es válida.');
      $targetId = (int) $id;
      $target = userModel::by_id($targetId);
      $role = trim((string) ($_POST['rol'] ?? ''));
      if (!$target || $targetId === (int) get_user('id')) throw new Exception('No es posible cambiar el perfil de esta cuenta.');
      $assignable = array_column(userModel::assignable_roles(), 'slug');
      if (!in_array($role, $assignable, true)) throw new Exception('El perfil seleccionado no se puede asignar.');
      if (($target['estado'] ?? '') !== 'aprobada' || (int) $target['activo'] !== 1 || !userModel::assign_role($targetId, $role)) throw new Exception('Solo se puede cambiar el perfil de una cuenta aprobada y activa.');
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
  private function requireAdministratorRole(): void
  {
    $user = $this->requireApprovedAccount();
    if (($user['rol'] ?? '') === 'admin' && $this->hasPermission('admin-access', $user)) return;
    if ($this->isAjaxRequest()) $this->jsonResponse(false, 'No tienes autorización para administrar roles y permisos.', 403);
    Flasher::error('No tienes autorización para administrar roles y permisos.');
    Redirect::to('admin');
  }

  private function jsonResponse(bool $success, string $message, int $status = 200): void
  {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message]);
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
