-- Fase 4: mecanismo separado para actuaciones administrativas actuales.
-- No altera estados, movimientos, resguardos, documentos ni eventos históricos.
-- Aplicar únicamente en un entorno aprobado; durante Fase 4 se aplica sólo a `prueba`.

CREATE TABLE IF NOT EXISTS traspaso_constancia_administrativa (
  id_constancia_administrativa INT NOT NULL AUTO_INCREMENT,
  id_traspaso INT NOT NULL,
  tipo_incidencia ENUM('DOCUMENTOS_AUSENTES') NOT NULL,
  estado ENUM('REGISTRADA') NOT NULL DEFAULT 'REGISTRADA',
  descripcion_hallazgos TEXT NOT NULL,
  evidencia_tecnica TEXT NOT NULL,
  fecha_hecho_historico DATETIME NULL,
  fecha_deteccion DATE NULL,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_usuario_registra INT NOT NULL,
  referencia_resolucion VARCHAR(150) NULL,
  huella_documentos CHAR(64) NOT NULL,
  PRIMARY KEY (id_constancia_administrativa),
  UNIQUE KEY uq_constancia_documental (id_traspaso, tipo_incidencia, huella_documentos),
  KEY idx_constancia_traspaso (id_traspaso, fecha_registro),
  KEY idx_constancia_registrador (id_usuario_registra),
  CONSTRAINT fk_constancia_traspaso FOREIGN KEY (id_traspaso) REFERENCES traspaso (id_traspaso),
  CONSTRAINT fk_constancia_registrador FOREIGN KEY (id_usuario_registra) REFERENCES bee_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS traspaso_constancia_documento (
  id_constancia_administrativa INT NOT NULL,
  id_traspaso_documento INT NOT NULL,
  PRIMARY KEY (id_constancia_administrativa, id_traspaso_documento),
  CONSTRAINT fk_constancia_documento_constancia FOREIGN KEY (id_constancia_administrativa)
    REFERENCES traspaso_constancia_administrativa (id_constancia_administrativa) ON DELETE RESTRICT,
  CONSTRAINT fk_constancia_documento_documento FOREIGN KEY (id_traspaso_documento)
    REFERENCES traspaso_documento (id_traspaso_documento) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO bee_permisos (nombre, slug, descripcion, creado)
SELECT 'Registrar regularización documental', 'traspasos-regularizar-documentos',
       'Registrar una constancia administrativa actual sobre incidencias documentales verificadas.', NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM bee_permisos WHERE slug = 'traspasos-regularizar-documentos'
);

-- La asignación queda limitada al rol administrativo existente. No concede permisos de consulta ni de modificación patrimonial.
INSERT INTO bee_roles_permisos (id_role, id_permiso)
SELECT r.id, p.id
FROM bee_roles r
JOIN bee_permisos p ON p.slug = 'traspasos-regularizar-documentos'
WHERE r.slug = 'admin'
  AND NOT EXISTS (
    SELECT 1 FROM bee_roles_permisos rp WHERE rp.id_role = r.id AND rp.id_permiso = p.id
  );
