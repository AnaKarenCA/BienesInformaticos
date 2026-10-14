-- Solicitudes temporales de recuperación. Nunca se almacena el código en claro.
CREATE TABLE IF NOT EXISTS password_reset_requests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NULL,
  method ENUM('email', 'whatsapp') NOT NULL,
  lookup_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  request_ip_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  code_hash VARCHAR(255) NULL,
  expires_at DATETIME NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  verified_at DATETIME NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_password_reset_lookup_created (lookup_hash, created_at),
  KEY idx_password_reset_ip_created (request_ip_hash, created_at),
  KEY idx_password_reset_user_state (user_id, used_at, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
