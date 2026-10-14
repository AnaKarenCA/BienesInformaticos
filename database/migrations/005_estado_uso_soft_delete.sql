-- Permite desactivar estados de uso sin eliminar filas referenciadas por bienes.
ALTER TABLE estado_uso
  ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;
