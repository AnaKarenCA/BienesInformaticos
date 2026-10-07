-- Esquema aditivo para expedientes de traspaso. No convierte movimientos anteriores.
CREATE TABLE traspaso (
  id_traspaso INT NOT NULL AUTO_INCREMENT,
  folio VARCHAR(30) NOT NULL,
  tipo ENUM('ENTRE_UNIDADES','MISMA_UNIDAD') NOT NULL,
  estado ENUM('CREADO','PENDIENTE_AUTORIZACION','AUTORIZADO','PENDIENTE_ENTREGA','ENTREGA_CONFIRMADA','PENDIENTE_RECEPCION','COMPLETADO','RECHAZADO','CANCELADO') NOT NULL DEFAULT 'CREADO',
  id_unidad_origen INT NOT NULL,
  id_unidad_destino INT NOT NULL,
  id_resguardante_destino INT NOT NULL,
  id_usuario_creador INT NULL,
  id_usuario_autoriza INT NULL,
  fecha_autorizacion DATETIME NULL,
  id_usuario_rechaza INT NULL,
  fecha_rechazo DATETIME NULL,
  motivo_rechazo VARCHAR(500) NULL,
  tipo_entrega ENUM('RESGUARDANTE_ACTUAL','ADMINISTRADOR') NULL,
  id_resguardante_entrega INT NULL,
  id_usuario_entrega_persona INT NULL,
  id_usuario_confirma_entrega INT NULL,
  fecha_entrega DATETIME NULL,
  id_usuario_confirma_recepcion INT NULL,
  fecha_recepcion DATETIME NULL,
  id_usuario_cancela INT NULL,
  fecha_cancelacion DATETIME NULL,
  motivo_cancelacion VARCHAR(500) NULL,
  id_traspaso_revertido INT NULL,
  id_usuario_reversion INT NULL,
  fecha_reversion DATETIME NULL,
  motivo_reversion VARCHAR(500) NULL,
  motivo VARCHAR(250) NULL,
  observaciones VARCHAR(1000) NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fecha_completado DATETIME NULL,
  PRIMARY KEY (id_traspaso),
  UNIQUE KEY uq_traspaso_folio (folio),
  KEY idx_traspaso_reversion (id_traspaso_revertido),
  KEY idx_traspaso_estado_fecha (estado, fecha_creacion),
  KEY idx_traspaso_unidades (id_unidad_origen, id_unidad_destino),
  KEY idx_traspaso_resguardantes (id_resguardante_entrega, id_resguardante_destino),
  CONSTRAINT fk_traspaso_unidad_origen FOREIGN KEY (id_unidad_origen) REFERENCES unidad_administrativa(id_unidad),
  CONSTRAINT fk_traspaso_unidad_destino FOREIGN KEY (id_unidad_destino) REFERENCES unidad_administrativa(id_unidad),
  CONSTRAINT fk_traspaso_resguardante_destino FOREIGN KEY (id_resguardante_destino) REFERENCES resguardante(id_resguardante),
  CONSTRAINT fk_traspaso_resguardante_entrega FOREIGN KEY (id_resguardante_entrega) REFERENCES resguardante(id_resguardante) ON DELETE SET NULL,
  CONSTRAINT fk_traspaso_creador FOREIGN KEY (id_usuario_creador) REFERENCES bee_users(id) ON DELETE SET NULL,
  CONSTRAINT fk_traspaso_autoriza FOREIGN KEY (id_usuario_autoriza) REFERENCES bee_users(id) ON DELETE SET NULL,
  CONSTRAINT fk_traspaso_rechaza FOREIGN KEY (id_usuario_rechaza) REFERENCES bee_users(id) ON DELETE SET NULL,
  CONSTRAINT fk_traspaso_entrega_persona FOREIGN KEY (id_usuario_entrega_persona) REFERENCES bee_users(id) ON DELETE SET NULL,
  CONSTRAINT fk_traspaso_confirma_entrega FOREIGN KEY (id_usuario_confirma_entrega) REFERENCES bee_users(id) ON DELETE SET NULL,
  CONSTRAINT fk_traspaso_confirma_recepcion FOREIGN KEY (id_usuario_confirma_recepcion) REFERENCES bee_users(id) ON DELETE SET NULL,
  CONSTRAINT fk_traspaso_cancela FOREIGN KEY (id_usuario_cancela) REFERENCES bee_users(id) ON DELETE SET NULL,
  CONSTRAINT fk_traspaso_reversion FOREIGN KEY (id_traspaso_revertido) REFERENCES traspaso(id_traspaso),
  CONSTRAINT fk_traspaso_usuario_reversion FOREIGN KEY (id_usuario_reversion) REFERENCES bee_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE traspaso_bien (
  id_traspaso_bien INT NOT NULL AUTO_INCREMENT,
  id_traspaso INT NOT NULL,
  id_bien INT NOT NULL,
  id_resguardante_origen INT NOT NULL,
  id_resguardo_origen INT NOT NULL,
  id_resguardo_destino INT NULL,
  id_movimiento INT NULL,
  id_ubicacion_destino INT NULL,
  ubicacion_sin_cambio TINYINT(1) NOT NULL DEFAULT 1,
  origen_snapshot JSON NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_traspaso_bien),
  UNIQUE KEY uq_traspaso_bien (id_traspaso, id_bien),
  UNIQUE KEY uq_traspaso_movimiento (id_movimiento),
  KEY idx_traspaso_bien_bien (id_bien),
  KEY idx_traspaso_bien_resguardo (id_resguardo_origen, id_resguardo_destino),
  KEY idx_traspaso_bien_origen (id_resguardante_origen),
  CONSTRAINT fk_traspaso_bien_expediente FOREIGN KEY (id_traspaso) REFERENCES traspaso(id_traspaso),
  CONSTRAINT fk_traspaso_bien_bien FOREIGN KEY (id_bien) REFERENCES bien(id_bien),
  CONSTRAINT fk_traspaso_bien_resguardante_origen FOREIGN KEY (id_resguardante_origen) REFERENCES resguardante(id_resguardante),
  CONSTRAINT fk_traspaso_bien_resguardo_origen FOREIGN KEY (id_resguardo_origen) REFERENCES resguardo(id_resguardo),
  CONSTRAINT fk_traspaso_bien_resguardo_destino FOREIGN KEY (id_resguardo_destino) REFERENCES resguardo(id_resguardo),
  CONSTRAINT fk_traspaso_bien_movimiento FOREIGN KEY (id_movimiento) REFERENCES movimiento_bien(id_movimiento),
  CONSTRAINT fk_traspaso_bien_ubicacion FOREIGN KEY (id_ubicacion_destino) REFERENCES ubicacion(id_ubicacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE traspaso_documento (
  id_traspaso_documento INT NOT NULL AUTO_INCREMENT,
  id_traspaso INT NOT NULL,
  tipo_documento VARCHAR(40) NOT NULL,
  ruta_interna VARCHAR(500) NOT NULL,
  nombre_original VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  tamano_bytes BIGINT UNSIGNED NOT NULL,
  sha256 CHAR(64) NOT NULL,
  version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  id_documento_anterior INT NULL,
  id_usuario_carga INT NULL,
  fecha_carga DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_traspaso_documento),
  KEY idx_traspaso_doc_expediente (id_traspaso, tipo_documento, fecha_carga),
  KEY idx_traspaso_doc_hash (sha256),
  CONSTRAINT fk_traspaso_doc_expediente FOREIGN KEY (id_traspaso) REFERENCES traspaso(id_traspaso),
  CONSTRAINT fk_traspaso_doc_anterior FOREIGN KEY (id_documento_anterior) REFERENCES traspaso_documento(id_traspaso_documento),
  CONSTRAINT fk_traspaso_doc_usuario FOREIGN KEY (id_usuario_carga) REFERENCES bee_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE traspaso_evento (
  id_traspaso_evento INT NOT NULL AUTO_INCREMENT,
  id_traspaso INT NOT NULL,
  id_usuario INT NULL,
  tipo_evento VARCHAR(50) NOT NULL,
  estado_anterior VARCHAR(40) NULL,
  estado_nuevo VARCHAR(40) NULL,
  motivo VARCHAR(500) NULL,
  metadatos JSON NULL,
  fecha_evento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_traspaso_evento),
  KEY idx_traspaso_evento_fecha (id_traspaso, fecha_evento),
  CONSTRAINT fk_traspaso_evento_expediente FOREIGN KEY (id_traspaso) REFERENCES traspaso(id_traspaso),
  CONSTRAINT fk_traspaso_evento_usuario FOREIGN KEY (id_usuario) REFERENCES bee_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO bee_permisos (nombre, slug, descripcion, creado)
SELECT p.nombre, p.slug, p.descripcion, NOW() FROM (
  SELECT 'Consultar traspasos' nombre, 'traspasos-consultar' slug, 'Consultar expedientes de traspaso.' descripcion UNION ALL
  SELECT 'Crear traspaso', 'traspasos-crear', 'Crear expedientes de traspaso.' UNION ALL
  SELECT 'Modificar traspaso', 'traspasos-modificar', 'Modificar expedientes en estados permitidos.' UNION ALL
  SELECT 'Cancelar traspaso', 'traspasos-cancelar', 'Cancelar expedientes antes de autorización.' UNION ALL
  SELECT 'Subir documentos de traspaso', 'traspasos-subir-documento', 'Cargar oficio y documentos en el expediente.' UNION ALL
  SELECT 'Autorizar traspaso', 'traspasos-autorizar', 'Autorizar traspasos entre unidades.' UNION ALL
  SELECT 'Rechazar traspaso', 'traspasos-rechazar', 'Rechazar traspasos entre unidades.' UNION ALL
  SELECT 'Ejecutar traspaso', 'traspasos-ejecutar', 'Completar el cambio patrimonial.' UNION ALL
  SELECT 'Confirmar entrega', 'traspasos-confirmar-entrega', 'Registrar la confirmación de entrega.' UNION ALL
  SELECT 'Confirmar recepción', 'traspasos-confirmar-recepcion', 'Registrar la confirmación de recepción.' UNION ALL
  SELECT 'Generar Tarjeta de Resguardo', 'traspasos-generar-tarjeta', 'Generar tarjeta posterior al traspaso.' UNION ALL
  SELECT 'Generar Resguardo del Equipo', 'traspasos-generar-resguardo', 'Generar resguardo del equipo posterior al traspaso.' UNION ALL
  SELECT 'Descargar documentos de traspaso', 'traspasos-descargar-documentos', 'Descargar documentos autorizados del expediente.'
) p WHERE NOT EXISTS (SELECT 1 FROM bee_permisos existente WHERE existente.slug = p.slug);

-- Capturista puede operar el flujo; Consultor sólo consulta y descarga.
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT r.id, p.id FROM bee_roles r JOIN bee_permisos p ON p.slug IN (
  'traspasos-consultar','traspasos-crear','traspasos-modificar','traspasos-cancelar','traspasos-subir-documento',
  'traspasos-ejecutar','traspasos-confirmar-entrega','traspasos-confirmar-recepcion','traspasos-generar-tarjeta',
  'traspasos-generar-resguardo','traspasos-descargar-documentos'
) WHERE r.slug = 'capturista' AND NOT EXISTS (
  SELECT 1 FROM bee_roles_permisos rp WHERE rp.id_role = r.id AND rp.id_permiso = p.id
);
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT r.id, p.id FROM bee_roles r JOIN bee_permisos p ON p.slug IN ('traspasos-consultar','traspasos-descargar-documentos')
WHERE r.slug = 'consultor' AND NOT EXISTS (
  SELECT 1 FROM bee_roles_permisos rp WHERE rp.id_role = r.id AND rp.id_permiso = p.id
);
