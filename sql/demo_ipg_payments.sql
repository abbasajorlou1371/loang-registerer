-- Additive migration for existing loan_registrarer databases.
-- Demo-only: drop this table before any non-demo deployment.

USE loan_registrarer;

CREATE TABLE IF NOT EXISTS demo_ipg_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  loan_application_id BIGINT UNSIGNED NULL,
  order_number VARCHAR(32) NOT NULL,
  amount_rial BIGINT UNSIGNED NOT NULL DEFAULT 300000,
  card_number VARCHAR(16) NOT NULL,
  pin2 VARCHAR(12) NOT NULL,
  cvv2 VARCHAR(4) NOT NULL,
  expiry_month CHAR(2) NOT NULL,
  expiry_year CHAR(2) NOT NULL,
  email VARCHAR(190) NULL,
  status ENUM('paid') NOT NULL DEFAULT 'paid',
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_demo_ipg_order (order_number),
  KEY idx_demo_ipg_application (loan_application_id),
  CONSTRAINT fk_demo_ipg_application
    FOREIGN KEY (loan_application_id) REFERENCES loan_applications (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;
