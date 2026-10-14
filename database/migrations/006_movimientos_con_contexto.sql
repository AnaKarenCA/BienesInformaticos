-- Completa la trazabilidad de movimientos sin alterar ni eliminar eventos existentes.
-- Los campos de contexto son instantáneas nuevas; los movimientos anteriores quedan sin valores inventados.
ALTER TABLE movimiento_bien
  ADD COLUMN id_usuario INT NULL AFTER id_bien,
  ADD COLUMN usuario_nombre VARCHAR(255) NULL AFTER id_usuario,
  ADD COLUMN unidad_anterior VARCHAR(250) NULL AFTER usuario_nombre,
  ADD COLUMN codigo_ua_anterior VARCHAR(20) NULL AFTER unidad_anterior,
  ADD COLUMN unidad_nueva VARCHAR(250) NULL AFTER codigo_ua_anterior,
  ADD COLUMN codigo_ua_nueva VARCHAR(20) NULL AFTER unidad_nueva,
  ADD COLUMN ubicacion_anterior_detalle VARCHAR(500) NULL AFTER codigo_ua_nueva,
  ADD COLUMN ubicacion_nueva_detalle VARCHAR(500) NULL AFTER ubicacion_anterior_detalle,
  ADD COLUMN csp_anterior VARCHAR(9) NULL AFTER ubicacion_nueva_detalle,
  ADD COLUMN nombre_resguardante_anterior VARCHAR(305) NULL AFTER csp_anterior,
  ADD COLUMN csp_nuevo VARCHAR(9) NULL AFTER nombre_resguardante_anterior,
  ADD COLUMN nombre_resguardante_nuevo VARCHAR(305) NULL AFTER csp_nuevo,
  ADD COLUMN unidad_resguardante_anterior VARCHAR(250) NULL AFTER nombre_resguardante_nuevo,
  ADD COLUMN codigo_ua_resguardante_anterior VARCHAR(20) NULL AFTER unidad_resguardante_anterior,
  ADD COLUMN unidad_resguardante_nueva VARCHAR(250) NULL AFTER codigo_ua_resguardante_anterior,
  ADD COLUMN codigo_ua_resguardante_nueva VARCHAR(20) NULL AFTER unidad_resguardante_nueva,
  ADD COLUMN estado_anterior VARCHAR(30) NULL AFTER codigo_ua_resguardante_nueva,
  ADD COLUMN estado_nuevo VARCHAR(30) NULL AFTER estado_anterior,
  ADD KEY idx_movimiento_usuario (id_usuario),
  ADD CONSTRAINT FK_movimiento_usuario
    FOREIGN KEY (id_usuario)
    REFERENCES bee_users(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL;
