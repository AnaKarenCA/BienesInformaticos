-- Ejecutar una sola vez después de importar el esquema existente.
-- Es aditiva: no elimina tablas ni datos del inventario.
ALTER TABLE bee_users
  ADD COLUMN nombre VARCHAR(150) NULL AFTER email,
  ADD COLUMN telefono VARCHAR(30) NULL AFTER nombre,
  ADD COLUMN rol VARCHAR(30) NOT NULL DEFAULT 'inventario' AFTER telefono,
  ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER rol;

CREATE INDEX idx_bien_inventario ON bien (numero_inventario);
CREATE INDEX idx_bien_serie ON bien (numero_serie);
CREATE INDEX idx_bien_activo ON bien (activo);
CREATE INDEX idx_resguardo_vigente ON resguardo (id_bien, activo);
CREATE INDEX idx_movimiento_bien_fecha ON movimiento_bien (id_bien, fecha_movimiento);

-- Roles de aplicación. El administrador recibe el acceso total de Bee;
-- inventario queda limitado a las operaciones cotidianas definidas abajo.
UPDATE bee_users SET rol = 'admin' WHERE username = 'bee' AND (rol IS NULL OR rol = 'inventario');
INSERT INTO bee_roles (nombre, slug, creado)
SELECT 'Administrador de inventario', 'admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM bee_roles WHERE slug = 'admin');
INSERT INTO bee_roles (nombre, slug, creado)
SELECT 'Operador de inventario', 'inventario', NOW()
WHERE NOT EXISTS (SELECT 1 FROM bee_roles WHERE slug = 'inventario');

INSERT INTO bee_permisos (nombre, slug, descripcion, creado)
SELECT 'Acceso de administrador', 'admin-access', 'Acceso total a la administración.', NOW() WHERE NOT EXISTS (SELECT 1 FROM bee_permisos WHERE slug = 'admin-access');
INSERT INTO bee_permisos (nombre, slug, descripcion, creado)
SELECT 'Guardar bienes', 'bienes-guardar', 'Registrar y editar bienes.', NOW() WHERE NOT EXISTS (SELECT 1 FROM bee_permisos WHERE slug = 'bienes-guardar');
INSERT INTO bee_permisos (nombre, slug, descripcion, creado)
SELECT 'Inactivar bienes', 'bienes-inactivar', 'Cambiar el estado activo de bienes.', NOW() WHERE NOT EXISTS (SELECT 1 FROM bee_permisos WHERE slug = 'bienes-inactivar');
INSERT INTO bee_permisos (nombre, slug, descripcion, creado)
SELECT 'Gestionar catálogos', 'catalogos-gestionar', 'Administrar catálogos del inventario.', NOW() WHERE NOT EXISTS (SELECT 1 FROM bee_permisos WHERE slug = 'catalogos-gestionar');
INSERT INTO bee_permisos (nombre, slug, descripcion, creado)
SELECT 'Exportar reportes', 'reportes-exportar', 'Exportar el inventario a PDF.', NOW() WHERE NOT EXISTS (SELECT 1 FROM bee_permisos WHERE slug = 'reportes-exportar');
INSERT INTO bee_permisos (nombre, slug, descripcion, creado)
SELECT 'Generar documentos', 'documentos-generar', 'Generar tarjetas, resguardos y bajas.', NOW() WHERE NOT EXISTS (SELECT 1 FROM bee_permisos WHERE slug = 'documentos-generar');

INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT r.id, p.id FROM bee_roles r CROSS JOIN bee_permisos p
WHERE r.slug = 'inventario' AND p.slug IN ('bienes-guardar', 'bienes-inactivar', 'catalogos-gestionar', 'reportes-exportar', 'documentos-generar')
  AND NOT EXISTS (SELECT 1 FROM bee_roles_permisos rp WHERE rp.id_role = r.id AND rp.id_permiso = p.id);
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT r.id, p.id FROM bee_roles r CROSS JOIN bee_permisos p
WHERE r.slug = 'admin' AND p.slug = 'admin-access'
  AND NOT EXISTS (SELECT 1 FROM bee_roles_permisos rp WHERE rp.id_role = r.id AND rp.id_permiso = p.id);
