CREATE TABLE IF NOT EXISTS audit_logs (
    log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_id VARCHAR(100) NOT NULL,
    actor_email VARCHAR(254) NOT NULL,
    action VARCHAR(64) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id VARCHAR(100) NULL DEFAULT NULL,
    details JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (log_id),
    KEY idx_audit_logs_created_at (created_at),
    KEY idx_audit_logs_actor_id (actor_id),
    KEY idx_audit_logs_entity (entity_type, entity_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
