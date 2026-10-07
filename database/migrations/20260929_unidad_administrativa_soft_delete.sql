-- Añade estado lógico sin eliminar ni reconstruir unidades administrativas existentes.
ALTER TABLE unidad_administrativa
  ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER id_padre;
