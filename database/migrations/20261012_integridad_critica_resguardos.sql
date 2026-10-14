-- Requisito previo obligatorio:
-- ejecutar database/diagnostics/20261012_integridad_critica_precheck.sql en
-- una copia aislada y corregir cualquier fila devuelta antes de aplicar.
-- Esta migración no modifica registros históricos.

-- Los disparadores preservan todos los registros históricos inactivos e
-- impiden abrir dos custodias vigentes. Se evita reconstruir la tabla heredada,
-- cuyas claves foráneas antiguas no son recreables de forma portable en 8.4.
DELIMITER $$
CREATE TRIGGER trg_resguardo_unico_activo_insert
BEFORE INSERT ON resguardo FOR EACH ROW
BEGIN
  IF NEW.activo = 1 AND EXISTS (SELECT 1 FROM resguardo WHERE id_bien=NEW.id_bien AND activo=1) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El bien ya tiene un resguardo activo';
  END IF;
END$$
CREATE TRIGGER trg_resguardo_unico_activo_update
BEFORE UPDATE ON resguardo FOR EACH ROW
BEGIN
  IF NEW.activo = 1 AND EXISTS (SELECT 1 FROM resguardo WHERE id_bien=NEW.id_bien AND activo=1 AND id_resguardo<>OLD.id_resguardo) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='El bien ya tiene un resguardo activo';
  END IF;
END$$
DELIMITER ;

-- Un mismo resguardo sólo puede tener una baja administrativa.
ALTER TABLE baja_resguardo
  ADD UNIQUE KEY uq_baja_resguardo_unica (id_resguardo);

