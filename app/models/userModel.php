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
    if (in_array($status, ['activo', 'inactivo', 'pendiente', 'rechazada'], true)) {
      if ($status === 'activo') $where[] = "u.activo = 1 AND u.estado = 'aprobada'";
      elseif ($status === 'inactivo') $where[] = "(u.activo = 0 OR u.estado = 'rechazada')";
      else { $where[] = 'u.estado = :estado'; $params['estado'] = $status; }
    }
    $sql = sprintf('SELECT u.*, (SELECT r.nombre FROM bee_roles r WHERE r.slug = u.rol ORDER BY r.id LIMIT 1) AS rol_nombre FROM %s u%s ORDER BY u.id DESC', self::$t1, $where ? ' WHERE ' . implode(' AND ', $where) : '');
    return PaginationHandler::paginate($sql, $params, 20, null, false, true, 7);
  }

  static function permission_matrix(): array
  {
    $roles = (new BeeRoleManager())->getRoles() ?: [];
    $permissions = parent::query('SELECT id, nombre, slug, descripcion FROM bee_permisos ORDER BY nombre, id') ?: [];
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
    return parent::query('SELECT id, username, nombre, email, rol, activo, estado FROM ' . self::$t1 . ' ORDER BY nombre, username') ?: [];
  }

  static function by_id($id)
  {
    // Un registro con $id
    $sql = sprintf('SELECT * FROM %s WHERE id = :id LIMIT 1', self::$t1);
    return ($rows = parent::query($sql, ['id' => $id])) ? $rows[0] : [];
  }

  static function pending_approval(): array
  {
    $sql = 'SELECT id, username, email, nombre, telefono, rol, estado, created_at
            FROM ' . self::$t1 . ' WHERE estado = :estado ORDER BY created_at ASC, id ASC';
    return parent::query($sql, ['estado' => 'pendiente']) ?: [];
  }

  static function count_pending(): int
  {
    $rows = parent::query('SELECT COUNT(*) AS total FROM ' . self::$t1 . ' WHERE estado = :estado', ['estado' => 'pendiente']);
    return (int) ($rows[0]['total'] ?? 0);
  }

  static function assignable_roles(): array
  {
    $sql = "SELECT id, nombre, slug FROM bee_roles WHERE slug IN ('capturista', 'consultor') ORDER BY nombre";
    return parent::query($sql) ?: [];
  }

  static function approve(int $id): bool
  {
    return parent::query('UPDATE ' . self::$t1 . " SET estado = 'aprobada', activo = 1, rol = 'consultor' WHERE id = :id AND estado = 'pendiente'", ['id' => $id]) !== false;
  }

  static function reject(int $id): bool
  {
    return parent::query('UPDATE ' . self::$t1 . " SET estado = 'rechazada', activo = 0, auth_token = NULL WHERE id = :id AND estado = 'pendiente'", ['id' => $id]) !== false;
  }

  static function assign_role(int $id, string $role): bool
  {
    if (!in_array($role, ['capturista', 'consultor'], true)) return false;
    return parent::query('UPDATE ' . self::$t1 . " SET rol = :rol WHERE id = :id AND estado = 'aprobada' AND activo = 1", ['id' => $id, 'rol' => $role]) !== false;
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

