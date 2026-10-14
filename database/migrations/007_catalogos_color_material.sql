-- Normaliza color y material sin eliminar los valores de texto heredados.
-- Ejecutar una sola vez sobre bienes_informaticos.

CREATE TABLE color (
  id_color INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  nombre_normalizado VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_color),
  UNIQUE KEY uq_color_nombre_normalizado (nombre_normalizado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE material (
  id_material INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  nombre_normalizado VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_material),
  UNIQUE KEY uq_material_nombre_normalizado (nombre_normalizado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO color (nombre, nombre_normalizado)
SELECT CONCAT(UPPER(LEFT(valores.nombre_normalizado, 1)), SUBSTRING(valores.nombre_normalizado, 2)), valores.nombre_normalizado
FROM (
  SELECT LOWER(REGEXP_REPLACE(TRIM(color), '[[:space:]]+', ' ')) AS nombre_normalizado
  FROM bien
  WHERE color IS NOT NULL AND TRIM(color) <> ''
    AND LOWER(TRIM(color)) NOT IN ('s/c', 'sin especificar', 'no aplica')
  GROUP BY LOWER(REGEXP_REPLACE(TRIM(color), '[[:space:]]+', ' '))
) AS valores;

INSERT INTO material (nombre, nombre_normalizado)
SELECT CONCAT(UPPER(LEFT(valores.nombre_normalizado, 1)), SUBSTRING(valores.nombre_normalizado, 2)), valores.nombre_normalizado
FROM (
  SELECT LOWER(REGEXP_REPLACE(TRIM(material), '[[:space:]]+', ' ')) AS nombre_normalizado
  FROM bien
  WHERE material IS NOT NULL AND TRIM(material) <> ''
    AND LOWER(TRIM(material)) NOT IN ('s/c', 'sin especificar', 'no aplica')
  GROUP BY LOWER(REGEXP_REPLACE(TRIM(material), '[[:space:]]+', ' '))
) AS valores;

ALTER TABLE bien
  ADD COLUMN id_material INT NULL AFTER material,
  ADD COLUMN id_color INT NULL AFTER color;

UPDATE bien b
INNER JOIN material m ON m.nombre_normalizado = LOWER(REGEXP_REPLACE(TRIM(b.material), '[[:space:]]+', ' '))
SET b.id_material = m.id_material
WHERE b.material IS NOT NULL AND TRIM(b.material) <> ''
  AND LOWER(TRIM(b.material)) NOT IN ('s/c', 'sin especificar', 'no aplica');

UPDATE bien b
INNER JOIN color c ON c.nombre_normalizado = LOWER(REGEXP_REPLACE(TRIM(b.color), '[[:space:]]+', ' '))
SET b.id_color = c.id_color
WHERE b.color IS NOT NULL AND TRIM(b.color) <> ''
  AND LOWER(TRIM(b.color)) NOT IN ('s/c', 'sin especificar', 'no aplica');

ALTER TABLE bien
  ADD KEY FK_bien_material (id_material),
  ADD KEY FK_bien_color (id_color),
  ADD CONSTRAINT FK_bien_material FOREIGN KEY (id_material) REFERENCES material (id_material) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT FK_bien_color FOREIGN KEY (id_color) REFERENCES color (id_color) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Comprobación posterior: el texto heredado permanece; los valores normales deben tener ID.
SELECT
  COUNT(*) AS bienes,
  SUM(material IS NOT NULL AND TRIM(material) <> '' AND LOWER(TRIM(material)) NOT IN ('s/c', 'sin especificar', 'no aplica') AND id_material IS NOT NULL) AS materiales_asociados,
  SUM(color IS NOT NULL AND TRIM(color) <> '' AND LOWER(TRIM(color)) NOT IN ('s/c', 'sin especificar', 'no aplica') AND id_color IS NOT NULL) AS colores_asociados,
  SUM(id_material IS NULL) AS sin_material_normalizado,
  SUM(id_color IS NULL) AS sin_color_normalizado
FROM bien;
