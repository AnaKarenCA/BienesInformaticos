-- Finaliza los perfiles operativos Bee: admin, capturista y consultor.
-- Las cuentas legadas inventario pasan a consultor para conservar solo lectura.
-- Revisar el listado previo del requerimiento antes de ejecutar en otros entornos.

INSERT INTO bee_permisos (nombre, slug, descripcion, creado)
SELECT 'Capturar catálogos', 'catalogos-guardar', 'Crear y editar información de catálogos.', NOW()
WHERE NOT EXISTS (SELECT 1 FROM bee_permisos WHERE slug = 'catalogos-guardar');

INSERT INTO bee_permisos (nombre, slug, descripcion, creado)
SELECT 'Activar o desactivar catálogos', 'catalogos-inactivar', 'Cambiar el estado de catálogos.', NOW()
WHERE NOT EXISTS (SELECT 1 FROM bee_permisos WHERE slug = 'catalogos-inactivar');

INSERT INTO bee_permisos (nombre, slug, descripcion, creado)
SELECT 'Eliminar unidades administrativas', 'catalogos-eliminar', 'Eliminar unidades administrativas sin dependencias.', NOW()
WHERE NOT EXISTS (SELECT 1 FROM bee_permisos WHERE slug = 'catalogos-eliminar');

INSERT INTO bee_roles (nombre, slug, creado)
SELECT 'Administrador', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM bee_roles WHERE slug = 'admin');
INSERT INTO bee_roles (nombre, slug, creado)
SELECT 'Capturista', 'capturista', NOW()
WHERE NOT EXISTS (SELECT 1 FROM bee_roles WHERE slug = 'capturista');
INSERT INTO bee_roles (nombre, slug, creado)
SELECT 'Consultor', 'consultor', NOW()
WHERE NOT EXISTS (SELECT 1 FROM bee_roles WHERE slug = 'consultor');

UPDATE bee_roles SET nombre = 'Administrador' WHERE slug = 'admin';
UPDATE bee_roles SET nombre = 'Capturista' WHERE slug = 'capturista';
UPDATE bee_roles SET nombre = 'Consultor' WHERE slug = 'consultor';

-- Mapeo explícito para la cuenta identificada como capturista; las demás
-- cuentas inventario, incluidas solicitudes antiguas, pasan al perfil seguro.
UPDATE bee_users SET rol = 'capturista'
WHERE username = 'Capturista1' AND rol = 'inventario';
UPDATE bee_users SET rol = 'consultor' WHERE rol = 'inventario';
ALTER TABLE bee_users MODIFY rol VARCHAR(30) NOT NULL DEFAULT 'consultor';

-- Reemplaza solo las asociaciones de los tres roles funcionales.
DELETE rp FROM bee_roles_permisos rp
JOIN bee_roles r ON r.id = rp.id_role
WHERE r.slug IN ('admin', 'capturista', 'consultor');

-- Retira la autorización de catálogo demasiado amplia, reemplazada abajo por
-- permisos separados para guardar, inactivar y eliminar.
DELETE rp FROM bee_roles_permisos rp
JOIN bee_permisos p ON p.id = rp.id_permiso
WHERE p.slug = 'catalogos-gestionar';
DELETE FROM bee_permisos WHERE slug = 'catalogos-gestionar';

INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT r.id, p.id FROM bee_roles r JOIN bee_permisos p ON p.slug = 'admin-access'
WHERE r.slug = 'admin';
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT r.id, p.id FROM bee_roles r JOIN bee_permisos p ON p.slug = 'inventario-consultar'
WHERE r.slug IN ('admin', 'capturista', 'consultor');
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT r.id, p.id FROM bee_roles r JOIN bee_permisos p ON p.slug IN ('bienes-guardar', 'catalogos-guardar', 'documentos-generar')
WHERE r.slug = 'capturista';

-- Retira todas las asociaciones del perfil obsoleto y después el rol,
-- una vez migradas sus cuentas y eliminado su uso funcional.
DELETE rp FROM bee_roles_permisos rp
JOIN bee_roles r ON r.id = rp.id_role
WHERE r.slug = 'inventario';
DELETE FROM bee_roles WHERE slug = 'inventario';
