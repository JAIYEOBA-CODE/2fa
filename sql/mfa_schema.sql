-- Schema for MFA app
CREATE DATABASE IF NOT EXISTS mfa_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE mfa_db;

DROP TABLE IF EXISTS remembered_devices;
DROP TABLE IF EXISTS backup_codes;
DROP TABLE IF EXISTS auth_logs;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(200) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  username VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  totp_secret VARCHAR(512) DEFAULT NULL,
  totp_enabled TINYINT(1) DEFAULT 1,
  is_admin TINYINT(1) DEFAULT 0,
  is_locked TINYINT(1) DEFAULT 0,
  failed_attempts INT DEFAULT 0,
  last_failed_at DATETIME DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE auth_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  event VARCHAR(50) NOT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(512) DEFAULT NULL,
  meta JSON DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE backup_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  used TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE remembered_devices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  device_token VARCHAR(255) NOT NULL,
  last_used DATETIME DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed admin and test user
INSERT INTO users (fullname, email, username, password_hash, totp_secret, is_admin)
VALUES
('Admin User','admin@example.com','admin', 
  -- password: Admin@123 (CHANGE AFTER FIRST LOGIN)
  '{$PASSWORD_HASH_PLACEHOLDER}', NULL, 1),
('Test User','test@example.com','testuser',
  -- password: Test@12345
  '{$PASSWORD_HASH_PLACEHOLDER_2}', NULL, 0);

-- We cannot put hashed passwords directly in an environment-agnostic SQL file,
-- We'll provide a simple helper below in README to create these or you can run:
-- php utils/generate_seed_hashes.php
