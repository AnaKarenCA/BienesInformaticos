-- Ejecutar una sola vez sobre el esquema de inventario existente.
-- Conserva los resguardantes y asignaciones; no inventa ni elimina identificadores.
ALTER TABLE resguardante
  MODIFY COLUMN clave_interna VARCHAR(18) NULL,
  MODIFY COLUMN csp VARCHAR(9) NOT NULL,
  ADD COLUMN id_unidad INT NULL AFTER csp,
  ADD UNIQUE KEY uq_resguardante_csp (csp),
  ADD KEY idx_resguardante_unidad (id_unidad),
  ADD CONSTRAINT FK_resguardante_unidad
    FOREIGN KEY (id_unidad)
    REFERENCES unidad_administrativa(id_unidad)
    ON UPDATE CASCADE
    ON DELETE SET NULL;

-- Retira el identificador artificial anterior; conserva las filas y sus CSP.
UPDATE resguardante
SET clave_interna = NULL
WHERE clave_interna LIKE 'RES-%' OR clave_interna LIKE 'RES000%';

ALTER TABLE resguardo
  ADD COLUMN csp VARCHAR(9) NULL AFTER id_resguardante;

UPDATE resguardo r
INNER JOIN resguardante p ON p.id_resguardante = r.id_resguardante
SET r.csp = p.csp;

-- Verificar que esta actualización no dejó asignaciones sin CSP antes de continuar.
ALTER TABLE resguardo
  MODIFY COLUMN csp VARCHAR(9) NOT NULL,
  ADD KEY idx_resguardo_csp (csp),
  ADD CONSTRAINT FK_resguardo_csp
    FOREIGN KEY (csp)
    REFERENCES resguardante(csp)
    ON UPDATE CASCADE
    ON DELETE RESTRICT;
