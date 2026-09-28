-- Customer Manager - database structure
-- Import this file once (phpMyAdmin > Import). It creates the database and all tables.

CREATE DATABASE IF NOT EXISTS customer_manager
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE customer_manager;

-- Login users: the admin (you) and your staff
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120) NOT NULL,
  `year`        SMALLINT UNSIGNED NULL,
  join_date     DATE NULL,
  exit_date     DATE NULL,
  role          ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  username      VARCHAR(60) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB;

-- Customers with their website and domain login details
CREATE TABLE IF NOT EXISTS customers (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name              VARCHAR(160) NOT NULL,
  website           VARCHAR(255) NULL,
  domain_name       VARCHAR(255) NULL,
  domain_login_url  VARCHAR(255) NULL,
  domain_username   VARCHAR(255) NULL,
  domain_password   TEXT NULL,
  domain_auth_id    TEXT NULL,
  maintained_by_me  TINYINT(1) NOT NULL DEFAULT 1,
  notes             TEXT NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Hosting accounts
CREATE TABLE IF NOT EXISTS hosting (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  customer_id       INT UNSIGNED NOT NULL,
  provider          VARCHAR(160) NULL,
  login_url         VARCHAR(255) NULL,
  username          VARCHAR(255) NULL,
  password          TEXT NULL,
  maintained_by_me  TINYINT(1) NOT NULL DEFAULT 1,
  notes             TEXT NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_hosting_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Web plans: join date, amount, duration
CREATE TABLE IF NOT EXISTS web_plans (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  customer_id      INT UNSIGNED NOT NULL,
  join_date        DATE NOT NULL,
  amount           DECIMAL(12,2) NOT NULL DEFAULT 0,
  duration_months  SMALLINT UNSIGNED NOT NULL DEFAULT 12,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_plans_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Backups: join date, amount
CREATE TABLE IF NOT EXISTS backups (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  customer_id  INT UNSIGNED NOT NULL,
  join_date    DATE NOT NULL,
  amount       DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_backups_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Payments, year by year
CREATE TABLE IF NOT EXISTS payments (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  customer_id  INT UNSIGNED NOT NULL,
  category     VARCHAR(30) NOT NULL DEFAULT 'Web plan',
  amount       DECIMAL(12,2) NOT NULL DEFAULT 0,
  `year`       SMALLINT UNSIGNED NOT NULL,
  due_date     DATE NULL,
  paid_date    DATE NULL,
  status       VARCHAR(10) NOT NULL DEFAULT 'Pending',
  notes        TEXT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payments_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;
