-- Limita el selector a los tipos solicitados y asigna únicamente los niveles
-- indicados expresamente en la tabla institucional proporcionada.
START TRANSACTION;

UPDATE tipo_unidad_administrativa
SET activo = 0
WHERE clave_tipo NOT IN (
  'SECRETARIA', 'SUBSECRETARIA', 'STAFF', 'DIRECCION', 'DIRECCION_AREA',
  'SUBDIRECCION', 'DEPARTAMENTO', 'OFICINA', 'PENDIENTE'
);

INSERT INTO tipo_unidad_administrativa (clave_tipo, nombre, campo_visual, orden, activo)
VALUES ('PENDIENTE', 'Pendiente de asignar', NULL, 999, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), campo_visual = NULL, orden = 999, activo = 1;

UPDATE tipo_unidad_administrativa SET nombre = 'STAFF', campo_visual = 'subsecretaria', orden = 25, activo = 1 WHERE clave_tipo = 'STAFF';
UPDATE tipo_unidad_administrativa SET nombre = 'Subdirección', campo_visual = 'subsecretaria', orden = 50, activo = 1 WHERE clave_tipo = 'SUBDIRECCION';
UPDATE tipo_unidad_administrativa SET nombre = 'Dirección de área', campo_visual = 'direccion_area', orden = 40, activo = 1 WHERE clave_tipo = 'DIRECCION_AREA';
UPDATE tipo_unidad_administrativa SET orden = 60, activo = 1 WHERE clave_tipo = 'DEPARTAMENTO';
UPDATE tipo_unidad_administrativa SET orden = 70, activo = 1 WHERE clave_tipo = 'OFICINA';
UPDATE tipo_unidad_administrativa SET nombre = 'Pendiente de asignar', campo_visual = NULL, orden = 999, activo = 1 WHERE clave_tipo = 'PENDIENTE';

-- Los registros sin nivel explícito en la fuente quedan pendientes.
UPDATE unidad_administrativa SET tipo = 'PENDIENTE';

UPDATE unidad_administrativa SET tipo = 'SECRETARIA' WHERE codigo_ua IN ('22600000000000L');

UPDATE unidad_administrativa SET tipo = 'SUBSECRETARIA' WHERE codigo_ua IN (
  '22600001000000S', '22600003000000S', '22600004000000S',
  '22600007000000S', '22600200000000L'
);

-- La clasificación STAFF se presenta en el campo jerárquico Subsecretaría.
UPDATE unidad_administrativa SET tipo = 'STAFF' WHERE codigo_ua = '22600002000000S';

UPDATE unidad_administrativa SET tipo = 'DIRECCION' WHERE codigo_ua IN (
  '22600003010000S', '22600003020000S', '22600003030000S', '22600008000000L',
  '22600009000000L', '22600010000000L', '22600011000000L', '22600201000000L',
  '22600202000000L', '22600202010000L', '22600202020000L'
);

UPDATE unidad_administrativa SET tipo = 'DIRECCION_AREA' WHERE codigo_ua IN (
  '22600009010000L', '22600010010000L', '22600010020000L', '22600200010000L',
  '22600200020000L', '22600200030000L', '22600201010000L', '22600201020000L',
  '22600202010000S', '22600202030000L'
);

UPDATE unidad_administrativa SET tipo = 'SUBDIRECCION' WHERE codigo_ua IN (
  '22600007000100S', '22600007000200S', '22600007000300S', '22600008000200L',
  '22600008000300L', '22600009000200L', '22600011000100L', '22600200020200L',
  '22600200030200L', '22600200030300L', '22600200030400L', '22600200030500L',
  '22600200030600L', '22600202010100L', '22600202010200L', '22600202020100L',
  '22600202030100L', '22600202030200L'
);

UPDATE unidad_administrativa SET tipo = 'DEPARTAMENTO' WHERE codigo_ua IN (
  '22600003000001S', '22600003010001S', '22600003010002S', '22600003010003S',
  '22600003020001S', '22600003020002S', '22600003020003S', '22600003030001S',
  '22600003030004S', '22600003030005S', '22600004000001S', '22600004000002S',
  '22600004000003S', '22600004000004S', '22600008000201L', '22600009010001L',
  '22600009010002L', '22600010010001L', '22600010010002L', '22600010020001L',
  '22600011000001L', '22600201000101L', '22600201010001L', '22600201010002L',
  '22600201020001L', '22600201020002L', '22600202010101L', '22600202010102L',
  '22600202010103L', '22600202010201L', '22600202010202L', '22600202020101L',
  '22600202020102L', '22600202030101L', '22600202030102L', '22600202030201L',
  '22600202030202L'
);

COMMIT;
