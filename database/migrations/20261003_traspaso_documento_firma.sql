-- Adds explicit workflow stages used by the document and signature process.
-- Existing transfer states and records remain unchanged.
ALTER TABLE traspaso
  MODIFY COLUMN estado ENUM(
    'CREADO',
    'PENDIENTE_AUTORIZACION',
    'AUTORIZADO',
    'PENDIENTE_ENTREGA',
    'ENTREGA_CONFIRMADA',
    'PENDIENTE_RECEPCION',
    'DOCUMENTOS',
    'FIRMA',
    'COMPLETADO',
    'RECHAZADO',
    'CANCELADO'
  ) NOT NULL DEFAULT 'CREADO';

-- Generated mandatory documents belong to one transfer item. Existing
-- authorization documents and historical generated documents remain NULL.
ALTER TABLE traspaso_documento
  ADD COLUMN id_bien INT NULL AFTER id_traspaso,
  ADD KEY idx_traspaso_doc_bien (id_bien, id_traspaso, tipo_documento),
  ADD CONSTRAINT fk_traspaso_doc_bien
    FOREIGN KEY (id_bien) REFERENCES bien(id_bien) ON DELETE SET NULL;
