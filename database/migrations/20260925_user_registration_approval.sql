-- Autorización administrativa para cuentas Bee.
-- Las cuentas existentes se consideran aprobadas para preservar su acceso.
ALTER TABLE bee_users
  ADD COLUMN estado ENUM('pendiente', 'aprobada', 'rechazada') NOT NULL DEFAULT 'aprobada' AFTER activo;

INSERT INTO bee_permisos (nombre, slug, descripcion, creado)
SELECT 'Consultar inventario', 'inventario-consultar', 'Acceso de consulta a los módulos del inventario.', NOW()
WHERE NOT EXISTS (SELECT 1 FROM bee_permisos WHERE slug = 'inventario-consultar');

INSERT INTO bee_roles (nombre, slug, creado)
SELECT 'Consultor', 'consultor', NOW()
WHERE NOT EXISTS (SELECT 1 FROM bee_roles WHERE slug = 'consultor');

INSERT INTO bee_roles (nombre, slug, creado)
SELECT 'Capturista', 'capturista', NOW()
WHERE NOT EXISTS (SELECT 1 FROM bee_roles WHERE slug = 'capturista');

-- El perfil Capturista conserva las capacidades de escritura del rol inventario existente.
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT capturista.id, rp.id_permiso
FROM bee_roles inventario
JOIN bee_roles_permisos rp ON rp.id_role = inventario.id
JOIN bee_roles capturista ON capturista.slug = 'capturista'
WHERE inventario.slug = 'inventario'
  AND NOT EXISTS (
    SELECT 1 FROM bee_roles_permisos current_rp
    WHERE current_rp.id_role = capturista.id AND current_rp.id_permiso = rp.id_permiso
  );

-- Conserva los permisos actuales de inventario y agrega el permiso de lectura general.
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT r.id, p.id
FROM bee_roles r
JOIN bee_permisos p ON p.slug = 'inventario-consultar'
WHERE r.slug IN ('admin', 'inventario', 'consultor', 'capturista')
  AND NOT EXISTS (
    SELECT 1 FROM bee_roles_permisos rp
    WHERE rp.id_role = r.id AND rp.id_permiso = p.id
  );
