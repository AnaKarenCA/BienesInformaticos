-- Consolidate user activation in bee_users.activo and persist distinct surnames.
-- Before running this migration, create and verify a full database backup.
-- Existing fullname strings stay intact in nombre; ambiguous legacy surnames are
-- intentionally left blank instead of being guessed. New accounts store both
-- the full legacy-compatible name and separate surname columns.

-- Preserve legacy approval semantics before removing estado: pending/rejected
-- accounts become inactive; approved accounts retain their current activo flag.
UPDATE bee_users
SET activo = CASE WHEN estado IN ('pendiente', 'rechazada') THEN 0 ELSE activo END;

-- Required non-null fields: use honest empty values for unknown telephone and
-- the existing username as a safe display fallback for the single missing name.
UPDATE bee_users
SET nombre = COALESCE(NULLIF(TRIM(nombre), ''), username),
    telefono = COALESCE(REGEXP_REPLACE(telefono, '[^0-9]', ''), ''),
    created_at = COALESCE(created_at, CURRENT_TIMESTAMP);

ALTER TABLE bee_users
  MODIFY COLUMN auth_token VARCHAR(255) NULL DEFAULT NULL,
  MODIFY COLUMN username VARCHAR(50) NOT NULL,
  MODIFY COLUMN password VARCHAR(255) NOT NULL,
  MODIFY COLUMN email VARCHAR(100) NOT NULL,
  MODIFY COLUMN nombre VARCHAR(150) NOT NULL,
  ADD COLUMN apellido_paterno VARCHAR(80) NOT NULL DEFAULT '' AFTER nombre,
  ADD COLUMN apellido_materno VARCHAR(80) NOT NULL DEFAULT '' AFTER apellido_paterno,
  MODIFY COLUMN telefono VARCHAR(15) NOT NULL DEFAULT '',
  MODIFY COLUMN rol VARCHAR(20) NOT NULL DEFAULT 'consultor',
  MODIFY COLUMN activo TINYINT(1) NOT NULL DEFAULT 1,
  DROP COLUMN estado,
  MODIFY COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Rollback notes: restore database from the verified pre-migration dump to recover
-- the original estado distinctions and pre-normalized phone strings. A simple
-- reverse ALTER cannot reconstruct those values, so do not rollback by guessing.
