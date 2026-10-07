-- Ejecutar una sola vez sobre la base de datos existente.
-- Migración aditiva: conserva las claves internas históricas y no elimina datos.

ALTER TABLE bien
  MODIFY COLUMN numero_inventario VARCHAR(20) NULL,
  ADD COLUMN clave_interna VARCHAR(18) NULL AFTER numero_inventario,
  ADD COLUMN piso VARCHAR(50) NULL AFTER id_ubicacion,
  ADD COLUMN seccion_ala VARCHAR(100) NULL AFTER piso,
  ADD COLUMN cubiculo VARCHAR(100) NULL AFTER seccion_ala;

-- Los registros existentes usaban numero_inventario para la CI. Se conserva
-- exactamente ese valor como clave histórica y se libera el campo SICOPA.
UPDATE bien
SET clave_interna = numero_inventario
WHERE numero_inventario REGEXP '^CI-[0-9]+$'
  AND (clave_interna IS NULL OR clave_interna = '');

UPDATE bien
SET numero_inventario = NULL
WHERE numero_inventario REGEXP '^CI-[0-9]+$'
  AND clave_interna = numero_inventario;

ALTER TABLE bien
  ADD UNIQUE KEY uq_bien_clave_interna (clave_interna),
  ADD KEY idx_bien_clave_interna (clave_interna);
