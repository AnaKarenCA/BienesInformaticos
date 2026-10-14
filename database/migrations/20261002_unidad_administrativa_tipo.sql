-- Catálogo formal para clasificar unidades y configurar su campo de presentación.
-- Esta migración NO asigna tipos ni códigos a unidades existentes.
CREATE TABLE tipo_unidad_administrativa (
  clave_tipo VARCHAR(40) NOT NULL,
  nombre VARCHAR(80) NOT NULL,
  campo_visual VARCHAR(40) NULL,
  orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (clave_tipo),
  UNIQUE KEY uq_tipo_unidad_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tipo_unidad_administrativa (clave_tipo, nombre, campo_visual, orden) VALUES
  ('SECRETARIA', 'Secretaría', 'secretaria', 10),
  ('SUBSECRETARIA', 'Subsecretaría', 'subsecretaria', 20),
  ('STAFF', 'STAFF', 'subsecretaria', 25),
  ('DIRECCION', 'Dirección', 'direccion_secretaria', 30),
  ('DIRECCION_AREA', 'Dirección de área', 'direccion_area', 40),
  ('SUBDIRECCION', 'Subdirección', 'subsecretaria', 50),
  ('DEPARTAMENTO', 'Departamento', 'departamento', 60),
  ('OFICINA', 'Oficina', 'oficina', 70),
  ('PENDIENTE', 'Pendiente de asignar', NULL, 999);

ALTER TABLE unidad_administrativa
  ADD COLUMN tipo VARCHAR(40) NULL AFTER codigo_ua,
  ADD KEY idx_unidad_tipo (tipo),
  ADD CONSTRAINT fk_unidad_tipo
    FOREIGN KEY (tipo) REFERENCES tipo_unidad_administrativa (clave_tipo)
    ON UPDATE CASCADE ON DELETE RESTRICT;

-- Las filas históricas conservan tipo = NULL hasta ser clasificadas con una fuente validada.
