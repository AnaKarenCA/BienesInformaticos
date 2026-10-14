-- Permisos granulares para las áreas y operaciones que existen en la aplicación.
-- Migra las asignaciones heredadas a las nuevas acciones sin cambiar el esquema.

INSERT INTO bee_permisos (nombre, slug, descripcion, creado)
SELECT p.nombre, p.slug, p.descripcion, NOW()
FROM (
  SELECT 'Consultar inicio' AS nombre, 'inicio-consultar' AS slug, 'Acceso al panel principal.' AS descripcion
  UNION ALL SELECT 'Consultar bienes', 'bienes-consultar', 'Consultar inventario, detalle e identificación de bienes.'
  UNION ALL SELECT 'Crear bienes', 'bienes-crear', 'Registrar bienes nuevos.'
  UNION ALL SELECT 'Actualizar bienes', 'bienes-actualizar', 'Editar bienes existentes.'
  UNION ALL SELECT 'Activar bienes', 'bienes-activar', 'Reactivar bienes inactivos.'
  UNION ALL SELECT 'Desactivar bienes', 'bienes-desactivar', 'Inactivar bienes activos.'
  UNION ALL SELECT 'Imprimir bienes', 'bienes-imprimir', 'Imprimir la etiqueta/código QR de un bien.'
  UNION ALL SELECT 'Consultar catálogos', 'catalogos-consultar', 'Consultar los catálogos del sistema.'
  UNION ALL SELECT 'Crear catálogos', 'catalogos-crear', 'Crear registros de catálogo.'
  UNION ALL SELECT 'Actualizar catálogos', 'catalogos-actualizar', 'Editar registros y asignaciones de catálogo.'
  UNION ALL SELECT 'Activar catálogos', 'catalogos-activar', 'Reactivar registros de catálogo.'
  UNION ALL SELECT 'Desactivar catálogos', 'catalogos-desactivar', 'Inactivar registros de catálogo.'
  UNION ALL SELECT 'Consultar resguardos', 'resguardos-consultar', 'Consultar resguardos y sus detalles.'
  UNION ALL SELECT 'Consultar movimientos', 'movimientos-consultar', 'Consultar el histórico de movimientos.'
  UNION ALL SELECT 'Consultar documentos', 'documentos-consultar', 'Acceder a formularios y datos previos de documentos.'
  UNION ALL SELECT 'Consultar usuarios', 'usuarios-consultar', 'Consultar la lista y los datos administrativos de usuarios.'
  UNION ALL SELECT 'Crear usuarios', 'usuarios-crear', 'Registrar cuentas de usuario.'
  UNION ALL SELECT 'Actualizar usuarios', 'usuarios-actualizar', 'Modificar datos y asignar roles a usuarios.'
  UNION ALL SELECT 'Eliminar usuarios', 'usuarios-eliminar', 'Eliminar físicamente cuentas de usuario.'
  UNION ALL SELECT 'Activar usuarios', 'usuarios-activar', 'Activar cuentas de usuario.'
  UNION ALL SELECT 'Desactivar usuarios', 'usuarios-desactivar', 'Desactivar cuentas de usuario.'
  UNION ALL SELECT 'Cerrar sesiones de usuarios', 'usuarios-cerrar-sesion', 'Invalidar la sesión activa de otro usuario.'
  UNION ALL SELECT 'Consultar roles y permisos', 'roles-consultar', 'Consultar la matriz y el acceso efectivo de usuarios.'
  UNION ALL SELECT 'Modificar permisos de roles', 'permisos-actualizar', 'Cambiar los permisos asignados a roles.'
) p
WHERE NOT EXISTS (SELECT 1 FROM bee_permisos existing WHERE existing.slug = p.slug);

-- Los perfiles que ya consultaban el sistema conservan consulta en cada módulo.
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT rp.id_role, p.id
FROM bee_roles_permisos rp
JOIN bee_permisos oldp ON oldp.id = rp.id_permiso AND oldp.slug = 'inventario-consultar'
JOIN bee_permisos p ON p.slug IN ('inicio-consultar', 'bienes-consultar', 'catalogos-consultar', 'resguardos-consultar', 'movimientos-consultar')
WHERE NOT EXISTS (SELECT 1 FROM bee_roles_permisos existing WHERE existing.id_role = rp.id_role AND existing.id_permiso = p.id);

-- Consulta de documentos corresponde a las pantallas/formularios existentes de resguardos.
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT rp.id_role, p.id
FROM bee_roles_permisos rp
JOIN bee_permisos oldp ON oldp.id = rp.id_permiso AND oldp.slug = 'inventario-consultar'
JOIN bee_permisos p ON p.slug = 'documentos-consultar'
WHERE NOT EXISTS (SELECT 1 FROM bee_roles_permisos existing WHERE existing.id_role = rp.id_role AND existing.id_permiso = p.id);

-- bienes-guardar significaba crear y actualizar; ambas capacidades se preservan.
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT rp.id_role, p.id
FROM bee_roles_permisos rp
JOIN bee_permisos oldp ON oldp.id = rp.id_permiso AND oldp.slug = 'bienes-guardar'
JOIN bee_permisos p ON p.slug IN ('bienes-crear', 'bienes-actualizar')
WHERE NOT EXISTS (SELECT 1 FROM bee_roles_permisos existing WHERE existing.id_role = rp.id_role AND existing.id_permiso = p.id);

-- catalogos-guardar también agrupaba las dos operaciones.
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT rp.id_role, p.id
FROM bee_roles_permisos rp
JOIN bee_permisos oldp ON oldp.id = rp.id_permiso AND oldp.slug = 'catalogos-guardar'
JOIN bee_permisos p ON p.slug IN ('catalogos-crear', 'catalogos-actualizar')
WHERE NOT EXISTS (SELECT 1 FROM bee_roles_permisos existing WHERE existing.id_role = rp.id_role AND existing.id_permiso = p.id);

-- Una asignación antigua para cambiar estado se divide en activar/desactivar.
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT rp.id_role, p.id
FROM bee_roles_permisos rp
JOIN bee_permisos oldp ON oldp.id = rp.id_permiso AND oldp.slug = 'bienes-inactivar'
JOIN bee_permisos p ON p.slug IN ('bienes-activar', 'bienes-desactivar')
WHERE NOT EXISTS (SELECT 1 FROM bee_roles_permisos existing WHERE existing.id_role = rp.id_role AND existing.id_permiso = p.id);
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT rp.id_role, p.id
FROM bee_roles_permisos rp
JOIN bee_permisos oldp ON oldp.id = rp.id_permiso AND oldp.slug = 'catalogos-inactivar'
JOIN bee_permisos p ON p.slug IN ('catalogos-activar', 'catalogos-desactivar')
WHERE NOT EXISTS (SELECT 1 FROM bee_roles_permisos existing WHERE existing.id_role = rp.id_role AND existing.id_permiso = p.id);

-- Capturista conserva las bajas/inactivaciones operativas de bienes; esto no
-- habilita borrado físico, que no existe para bienes en la aplicación.
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT r.id, p.id FROM bee_roles r JOIN bee_permisos p
  ON p.slug IN ('bienes-activar', 'bienes-desactivar')
WHERE r.slug = 'capturista'
  AND NOT EXISTS (SELECT 1 FROM bee_roles_permisos rp WHERE rp.id_role = r.id AND rp.id_permiso = p.id);

-- Los permisos granulares administrativos son explícitos para el rol que ya
-- posee admin-access; el backend conserva esa autorización maestra.
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT r.id, p.id FROM bee_roles r JOIN bee_permisos p
  ON p.slug IN ('usuarios-consultar', 'usuarios-crear', 'usuarios-actualizar', 'usuarios-eliminar',
                'usuarios-activar', 'usuarios-desactivar', 'usuarios-cerrar-sesion', 'roles-consultar', 'permisos-actualizar')
WHERE r.slug = 'admin'
  AND NOT EXISTS (SELECT 1 FROM bee_roles_permisos rp WHERE rp.id_role = r.id AND rp.id_permiso = p.id);

-- Generar un PDF no modifica el inventario; Capturista y Consulta conservan
-- esta salida de consulta/descarga. El baja real sigue protegido aparte.
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT r.id, p.id FROM bee_roles r JOIN bee_permisos p ON p.slug = 'documentos-generar'
WHERE r.slug IN ('capturista', 'consultor')
  AND NOT EXISTS (SELECT 1 FROM bee_roles_permisos rp WHERE rp.id_role = r.id AND rp.id_permiso = p.id);

-- Asigna impresión del QR y exportación del reporte a perfiles operativos.
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT r.id, p.id FROM bee_roles r JOIN bee_permisos p ON p.slug = 'bienes-imprimir'
WHERE r.slug IN ('capturista', 'consultor')
  AND NOT EXISTS (SELECT 1 FROM bee_roles_permisos rp WHERE rp.id_role = r.id AND rp.id_permiso = p.id);
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT r.id, p.id FROM bee_roles r JOIN bee_permisos p ON p.slug = 'reportes-exportar'
WHERE r.slug IN ('capturista', 'consultor')
  AND NOT EXISTS (SELECT 1 FROM bee_roles_permisos rp WHERE rp.id_role = r.id AND rp.id_permiso = p.id);
-- No se eliminan permisos ni asociaciones heredadas. La aplicación oculta los
-- slugs sustituidos/no ejecutables en la matriz, pero conserva sus registros.
