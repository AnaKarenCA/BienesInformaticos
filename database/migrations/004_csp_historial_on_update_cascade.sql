-- Permite corregir el CSP de una persona sin perder ni reemplazar sus asignaciones.
-- MySQL actualiza la referencia csp de cada fila histórica y conserva sus IDs/fechas.
ALTER TABLE resguardo DROP FOREIGN KEY FK_resguardo_csp;
ALTER TABLE resguardo
  ADD CONSTRAINT FK_resguardo_csp
    FOREIGN KEY (csp)
    REFERENCES resguardante(csp)
    ON UPDATE CASCADE
    ON DELETE RESTRICT;
