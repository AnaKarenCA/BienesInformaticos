<?php
/**
 * Plantilla general de modelos
 * @version 1.1.8
 *
 * Modelo de user
 */
class userModel extends Model {
  /**
  * Nombre de la tabla
  */
  public static $t1 = 'bee_users';
  
  // Nombre de tablas secundarias
  //public static $t2 = '__tabla 2__'; 
  //public static $t3 = '__tabla 3__'; 

  // Esquema del Modelo
  

  function __construct()
  {
    // Constructor general
  }
  
  static function all()
  {
    // Todos los registros
    $sql = sprintf('SELECT * FROM %s ORDER BY id DESC', self::$t1);
    return ($rows = parent::query($sql)) ? $rows : [];
  }

  static function all_paginated(string $search = '', string $status = '')
  {
    $where = [];
    $params = [];
    if ($search !== '') {
      $where[] = '(u.username LIKE :search_user OR u.nombre LIKE :search_name OR u.email LIKE :search_email)';
      $params['search_user'] = $params['search_name'] = $params['search_email'] = '%' . $search . '%';
    }
    if (in_array($status, ['activo', 'inactivo'], true)) {
      $where[] = 'u.activo = :activo';
      $params['activo'] = $status === 'activo' ? 1 : 0;
    }
    $sql = sprintf("SELECT u.*, CASE WHEN u.apellido_paterno <> '' THEN TRIM(LEFT(u.nombre, CHAR_LENGTH(u.nombre) - CHAR_LENGTH(CONCAT_WS(' ', NULLIF(u.apellido_paterno, ''), NULLIF(u.apellido_materno, ''))))) ELSE '' END AS nombre_pila, (SELECT r.nombre FROM bee_roles r WHERE r.slug = u.rol ORDER BY r.id LIMIT 1) AS rol_nombre FROM %s u%s ORDER BY u.id DESC", self::$t1, $where ? ' WHERE ' . implode(' AND ', $where) : '');
    return PaginationHandler::paginate($sql, $params, 20, null, false, true, 7);
  }

  static function permission_matrix(): array
  {
    $roles = (new BeeRoleManager())->getRoles() ?: [];
    // Se conservan las claves heredadas en la base de datos por compatibilidad,
    // pero las acciones actuales de la matriz usan sus reemplazos granulares.
    $legacySlugs = ['inventario-consultar', 'bienes-guardar', 'bienes-inactivar', 'catalogos-guardar', 'catalogos-inactivar', 'catalogos-eliminar'];
    $permissions = parent::query('SELECT id, nombre, slug, descripcion FROM bee_permisos ORDER BY nombre, id') ?: [];
    $permissions = array_values(array_filter($permissions, static fn($permission) => !in_array((string) ($permission['slug'] ?? ''), $legacySlugs, true)));
    foreach ($permissions as &$permission) $permission['id'] = (int) $permission['id'];
    unset($permission);
    foreach ($roles as &$role) {
      $role['permisos_ids'] = array_map('intval', array_column(parent::query('SELECT id_permiso FROM bee_roles_permisos WHERE id_role = :id', ['id' => $role['id']]) ?: [], 'id_permiso'));
      $permissionSlugs = [];
      foreach ($permissions as $permission) {
        if (in_array((int) $permission['id'], $role['permisos_ids'], true)) $permissionSlugs[] = (string) $permission['slug'];
      }
      $roleHasGlobalAccess = ($role['slug'] ?? '') === 'developer' || in_array('admin-access', $permissionSlugs, true);
      $role['permisos_efectivos_ids'] = $roleHasGlobalAccess
        ? array_map('intval', array_column($permissions, 'id'))
        : $role['permisos_ids'];
    }
    unset($role);
    return ['roles' => $roles, 'permissions' => $permissions];
  }

  static function users_for_matrix(): array
  {
    return parent::query("SELECT u.id, u.username, u.nombre, u.apellido_paterno, u.apellido_materno, u.email, u.rol, u.activo, (SELECT r.nombre FROM bee_roles r WHERE r.slug = u.rol ORDER BY r.id LIMIT 1) AS rol_nombre FROM " . self::$t1 . " u ORDER BY u.nombre, u.username") ?: [];
  }

  static function by_id($id)
  {
    // Un registro con $id
    $sql = sprintf('SELECT * FROM %s WHERE id = :id LIMIT 1', self::$t1);
    return ($rows = parent::query($sql, ['id' => $id])) ? $rows[0] : [];
  }

  /** Datos de cuenta para el perfil propio, con el nombre propio derivado del nombre completo existente. */
  static function profile_by_id(int $id): array
  {
    $sql = "SELECT u.*, CASE WHEN u.apellido_paterno <> '' THEN TRIM(LEFT(u.nombre, CHAR_LENGTH(u.nombre) - CHAR_LENGTH(CONCAT_WS(' ', NULLIF(u.apellido_paterno, ''), NULLIF(u.apellido_materno, ''))))) ELSE u.nombre END AS nombre_pila, (SELECT r.nombre FROM bee_roles r WHERE r.slug = u.rol ORDER BY r.id LIMIT 1) AS rol_nombre FROM " . self::$t1 . " u WHERE u.id = :id LIMIT 1";
    return ($rows = parent::query($sql, ['id' => $id])) ? $rows[0] : [];
  }

  static function assign_role(int $id, string $role): bool
  {
    if (!in_array($role, ['capturista', 'consultor'], true)) return false;
    return parent::query('UPDATE ' . self::$t1 . ' SET rol = :rol WHERE id = :id AND activo = 1', ['id' => $id, 'rol' => $role]) !== false;
  }

  static function update_by_id($id, $params)
  {
    return parent::update(self::$t1, ['id' => $id], $params);
  }

  static function delete_by_id($id)
  {
    return parent::remove(self::$t1, ['id' => $id]);
  }
}

