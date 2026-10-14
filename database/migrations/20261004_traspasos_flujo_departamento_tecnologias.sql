-- Normalize the current workflow to the three operational states. Historical
-- rejected/cancelled records are retained in the audit log and mapped to a
-- non-active pending record with their existing closure timestamps preserved.
ALTER TABLE traspaso MODIFY estado ENUM(
  'CREADO','PENDIENTE_AUTORIZACION','AUTORIZADO','PENDIENTE_ENTREGA',
  'ENTREGA_CONFIRMADA','PENDIENTE_RECEPCION','DOCUMENTOS','FIRMA',
  'PENDIENTE_VALIDACION','VALIDADO','COMPLETADO','RECHAZADO','CANCELADO'
) NOT NULL DEFAULT 'PENDIENTE_VALIDACION';

UPDATE traspaso
SET fecha_cancelacion=COALESCE(fecha_cancelacion,fecha_rechazo,fecha_actualizacion),
    motivo_cancelacion=COALESCE(motivo_cancelacion,motivo_rechazo,'Registro histórico cerrado antes del cambio de flujo')
WHERE estado IN ('CANCELADO','RECHAZADO');

UPDATE traspaso SET estado=CASE
  WHEN estado='COMPLETADO' THEN 'COMPLETADO'
  WHEN estado IN ('AUTORIZADO','PENDIENTE_ENTREGA','ENTREGA_CONFIRMADA','PENDIENTE_RECEPCION','DOCUMENTOS','FIRMA') THEN 'VALIDADO'
  ELSE 'PENDIENTE_VALIDACION'
END;

ALTER TABLE traspaso MODIFY estado ENUM(
  'PENDIENTE_VALIDACION','VALIDADO','COMPLETADO'
) NOT NULL DEFAULT 'PENDIENTE_VALIDACION';

-- Reuse the existing administrative permission; its semantics are validation,
-- not granting the external authorization.
UPDATE bee_permisos
SET nombre='Validar oficio de traspaso',
    descripcion='Validar documentalmente que el oficio externo corresponda al expediente.'
WHERE slug='traspasos-autorizar';
