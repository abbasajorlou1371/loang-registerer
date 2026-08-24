-- Transaction messages (upgrade: keep id, replace all other columns with data)
USE loan_registrarer;

DROP TABLE IF EXISTS transaction_messages;

CREATE TABLE transaction_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  data TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
