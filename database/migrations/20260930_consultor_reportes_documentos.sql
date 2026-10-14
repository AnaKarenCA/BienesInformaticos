-- El rol Consultor conserva consulta y además puede exportar reportes
-- y generar los documentos disponibles en la aplicación.
-- Usa permisos ya registrados; no agrega tablas, columnas ni permisos nuevos.
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT r.id, p.id
FROM bee_roles r
JOIN bee_permisos p ON p.slug IN ('reportes-exportar', 'documentos-generar')
WHERE r.slug = 'consultor'
  AND NOT EXISTS (
    SELECT 1
    FROM bee_roles_permisos rp
    WHERE rp.id_role = r.id AND rp.id_permiso = p.id
  );
