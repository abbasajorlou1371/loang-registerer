-- Transaction / disposable bank codes
USE loan_registrarer;

CREATE TABLE IF NOT EXISTS transaction_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bank VARCHAR(120) NOT NULL,
  destination VARCHAR(190) NOT NULL,
  amount BIGINT NOT NULL,
  currency VARCHAR(16) NOT NULL,
  disposable_code VARCHAR(120) NOT NULL,
  received_at BIGINT NOT NULL COMMENT 'Unix timestamp (seconds or milliseconds)',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_transaction_messages_received_at (received_at),
  KEY idx_transaction_messages_created_at (created_at),
  KEY idx_transaction_messages_code (disposable_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
