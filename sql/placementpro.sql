-- ============================================================
--  PlacementPro  |  Database Schema
--  Database : placementpro
--  Engine   : MySQL  (via phpMyAdmin / XAMPP)
-- ============================================================

CREATE DATABASE IF NOT EXISTS placementpro
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE placementpro;

-- ─────────────────────────────────────────
--  1. USERS  (students, admins, holder)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120)  NOT NULL,
  email         VARCHAR(180)  NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,          -- bcrypt hash
  role          ENUM('student','admin','holder') NOT NULL DEFAULT 'student',
  roll_no       VARCHAR(40)   DEFAULT NULL,
  branch        VARCHAR(80)   DEFAULT NULL,
  cgpa          DECIMAL(4,2)  DEFAULT NULL,
  phone         VARCHAR(20)   DEFAULT NULL,
  dept          VARCHAR(100)  DEFAULT NULL,       -- admin only
  college       VARCHAR(150)  DEFAULT NULL,       -- admin only
  created_at    DATETIME      DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
--  2. EXAM RESULTS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS exam_results (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED  NOT NULL,
  company       VARCHAR(60)   NOT NULL,
  round         VARCHAR(40)   NOT NULL,   -- Aptitude | Technical | Coding | HR
  score         TINYINT UNSIGNED NOT NULL DEFAULT 0,   -- percentage 0-100
  total_q       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  correct_q     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  passed        TINYINT(1)    NOT NULL DEFAULT 0,
  taken_at      DATETIME      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
--  3. COMPLETED ROUNDS  (unlock tracking)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS completed_rounds (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED  NOT NULL,
  company       VARCHAR(60)   NOT NULL,
  round         VARCHAR(40)   NOT NULL,
  completed_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_co_round (user_id, company, round),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
--  4. SESSIONS  (server-side tokens)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sessions (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED  NOT NULL,
  token         VARCHAR(128)  NOT NULL UNIQUE,
  expires_at    DATETIME      NOT NULL,
  created_at    DATETIME      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
--  SEED DATA  —  default accounts
-- ─────────────────────────────────────────
-- Passwords are bcrypt of: admin123 / student123 / holder123
-- Generated with password_hash() at cost 10

INSERT IGNORE INTO users
  (name, email, password_hash, role, roll_no, branch, dept, college)
VALUES
  ('Admin User',
   'admin@placementpro.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'admin', 'ADMIN001', 'Administration',
   'Training & Placement', 'PlacementPro HQ'),

  ('Arjun Sharma',
   'arjun@student.com',
   '$2y$10$TKh8H1.PFbuSpgzjssC9ouvtKe5VbZpL3e0lVALcJUleV/HQgbUwW',
   'student', 'CS2021001', 'Computer Science',
   NULL, NULL),

  ('Super Admin',
   'holder@placementpro.com',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'holder', 'HOLDER001', 'Management',
   NULL, NULL);

-- NOTE: The hash '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
--       is the Laravel / PHP default test hash for "password".
--       Run generate_hashes.php (included) to regenerate proper hashes
--       for admin123 / student123 / holder123 before production use.
