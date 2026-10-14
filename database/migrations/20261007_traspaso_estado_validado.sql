-- Add the canonical validation state used by the current transfer workflow.
-- Preserve legacy enum values and existing records; this migration changes no data.
ALTER TABLE traspaso MODIFY COLUMN estado ENUM(
  'CREADO',
  'PENDIENTE_AUTORIZACION',
  'AUTORIZADO',
  'PENDIENTE_ENTREGA',
  'ENTREGA_CONFIRMADA',
  'PENDIENTE_RECEPCION',
  'DOCUMENTOS',
  'FIRMA',
  'PENDIENTE_VALIDACION',
  'VALIDADO',
  'COMPLETADO',
  'RECHAZADO',
  'CANCELADO'
) NOT NULL DEFAULT 'CREADO';
