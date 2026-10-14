-- Diagnóstico de solo lectura. Debe ejecutarse antes de la migración
-- 20261012_integridad_critica_resguardos.sql sobre una copia aislada.

-- Debe devolver cero filas: más de una custodia activa para el mismo bien.
SELECT id_bien, COUNT(*) AS resguardos_activos
FROM resguardo
WHERE activo = 1
GROUP BY id_bien
HAVING COUNT(*) > 1;

-- Debe devolver cero filas: más de una baja para el mismo resguardo.
SELECT id_resguardo, COUNT(*) AS bajas_registradas
FROM baja_resguardo
GROUP BY id_resguardo
HAVING COUNT(*) > 1;

-- Debe devolver cero filas: movimientos cuyo bien ya no existe.
SELECT m.id_movimiento, m.id_bien
FROM movimiento_bien m
LEFT JOIN bien b ON b.id_bien = m.id_bien
WHERE b.id_bien IS NULL;

-- Debe devolver cero filas: bienes inactivos que todavía tienen custodia activa.
SELECT b.id_bien, r.id_resguardo
FROM bien b
JOIN resguardo r ON r.id_bien = b.id_bien AND r.activo = 1
WHERE b.activo = 0;

-- Debe devolver cero filas: más de un traspaso activo para el mismo bien.
SELECT tb.id_bien, COUNT(DISTINCT tb.id_traspaso) AS traspasos_activos
FROM traspaso_bien tb
JOIN traspaso t ON t.id_traspaso = tb.id_traspaso
WHERE t.estado IN ('PENDIENTE_VALIDACION', 'VALIDADO')
  AND t.fecha_cancelacion IS NULL
GROUP BY tb.id_bien
HAVING COUNT(DISTINCT tb.id_traspaso) > 1;

