-- Bank Melli Loan Platform — MySQL schema
CREATE DATABASE IF NOT EXISTS loan_registrarer
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE loan_registrarer;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS transaction_messages;
DROP TABLE IF EXISTS demo_ipg_payments;
DROP TABLE IF EXISTS application_status_history;
DROP TABLE IF EXISTS loan_applications;
DROP TABLE IF EXISTS application_documents;
DROP TABLE IF EXISTS rate_limits;
DROP TABLE IF EXISTS loan_eligibility_rules;
DROP TABLE IF EXISTS loan_product_versions;
DROP TABLE IF EXISTS loan_products;
DROP TABLE IF EXISTS applicants;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  username VARCHAR(80) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  mobile VARCHAR(20) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB;

CREATE TABLE loan_products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL,
  title_fa VARCHAR(160) NOT NULL,
  description_fa TEXT NULL,
  product_type ENUM('qard_hasanah', 'financing', 'external_government') NOT NULL,
  application_type ENUM('direct', 'external') NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_loan_products_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE loan_product_versions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  loan_product_id BIGINT UNSIGNED NOT NULL,
  version INT UNSIGNED NOT NULL DEFAULT 1,
  min_amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
  max_amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
  fee_options JSON NULL,
  interest_rates JSON NULL,
  term_months_min INT UNSIGNED NOT NULL DEFAULT 1,
  term_months_max INT UNSIGNED NOT NULL DEFAULT 12,
  term_options JSON NULL,
  eligibility_notes_fa TEXT NULL,
  conditions_json JSON NULL,
  source_url VARCHAR(500) NULL,
  source_label_fa VARCHAR(190) NULL,
  last_updated_at DATE NOT NULL,
  effective_from DATE NOT NULL,
  is_current TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_loan_product_versions (loan_product_id, version),
  KEY idx_loan_product_versions_current (loan_product_id, is_current),
  CONSTRAINT fk_loan_product_versions_product
    FOREIGN KEY (loan_product_id) REFERENCES loan_products (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE loan_eligibility_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  loan_product_id BIGINT UNSIGNED NOT NULL,
  rule_key VARCHAR(80) NOT NULL,
  rule_value JSON NOT NULL,
  label_fa VARCHAR(190) NOT NULL,
  description_fa TEXT NULL,
  KEY idx_loan_eligibility_rules_product (loan_product_id),
  CONSTRAINT fk_loan_eligibility_rules_product
    FOREIGN KEY (loan_product_id) REFERENCES loan_products (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE applicants (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  national_id VARCHAR(20) NOT NULL,
  mobile VARCHAR(20) NOT NULL,
  birth_date DATE NULL,
  employment_type VARCHAR(60) NULL,
  monthly_income BIGINT UNSIGNED NULL,
  bank_melli_account VARCHAR(40) NULL,
  has_bank_melli_account TINYINT(1) NOT NULL DEFAULT 0,
  account_activity_months INT UNSIGNED NULL,
  average_balance BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_applicants_national_id (national_id),
  KEY idx_applicants_mobile (mobile)
) ENGINE=InnoDB;

CREATE TABLE loan_applications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tracking_number VARCHAR(20) NOT NULL,
  applicant_id BIGINT UNSIGNED NOT NULL,
  loan_product_id BIGINT UNSIGNED NOT NULL,
  loan_product_version_id BIGINT UNSIGNED NOT NULL,
  requested_amount BIGINT UNSIGNED NOT NULL,
  repayment_months INT UNSIGNED NOT NULL,
  eligibility_result ENUM('eligible', 'potentially_eligible', 'not_eligible', 'external')
    NOT NULL DEFAULT 'potentially_eligible',
  status ENUM('submitted', 'under_review', 'needs_info', 'approved', 'rejected', 'external')
    NOT NULL DEFAULT 'submitted',
  extra_data JSON NULL,
  notes TEXT NULL,
  ip_address VARCHAR(45) NULL,
  submitted_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_loan_applications_tracking (tracking_number),
  KEY idx_loan_applications_status (status),
  KEY idx_loan_applications_applicant (applicant_id),
  CONSTRAINT fk_loan_applications_applicant
    FOREIGN KEY (applicant_id) REFERENCES applicants (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_loan_applications_product
    FOREIGN KEY (loan_product_id) REFERENCES loan_products (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_loan_applications_version
    FOREIGN KEY (loan_product_version_id) REFERENCES loan_product_versions (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE application_status_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  loan_application_id BIGINT UNSIGNED NOT NULL,
  from_status VARCHAR(40) NULL,
  to_status VARCHAR(40) NOT NULL,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_application_status_history_app (loan_application_id),
  CONSTRAINT fk_application_status_history_application
    FOREIGN KEY (loan_application_id) REFERENCES loan_applications (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE rate_limits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  action VARCHAR(40) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_rate_limits_lookup (ip_address, action, created_at)
) ENGINE=InnoDB;

CREATE TABLE demo_ipg_payments (
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

CREATE TABLE transaction_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  data TEXT NOT NULL
) ENGINE=InnoDB;
