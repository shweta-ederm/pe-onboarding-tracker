-- Practice Onboarding Tracker - schema
-- Target: MySQL 5.7+ / MariaDB 10.2+ (no CTEs, no window functions,
-- so it runs on typical GoDaddy shared hosting).
-- Contains no patient data. Do not store PHI in this database.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Reference data
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS products (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(120) NOT NULL,
  slug         VARCHAR(140) NOT NULL,
  description  VARCHAR(500) DEFAULT NULL,
  sort_order   INT NOT NULL DEFAULT 0,
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_products_slug (slug),
  KEY ix_products_order (sort_order, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(120) NOT NULL,
  sort_order   INT NOT NULL DEFAULT 0,
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_name (name),
  KEY ix_categories_order (sort_order, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assignees (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(120) NOT NULL,
  email        VARCHAR(190) DEFAULT NULL,
  role_title   VARCHAR(120) DEFAULT NULL,
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_assignees_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Global task definitions.
-- One row here is one task that applies to EVERY practice using the
-- product. Per-practice state lives in practice_tasks.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS tasks (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id   INT UNSIGNED NOT NULL,
  category_id  INT UNSIGNED NOT NULL,
  name         VARCHAR(200) NOT NULL,
  description  TEXT DEFAULT NULL,
  sort_order   INT NOT NULL DEFAULT 0,
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_tasks_product (product_id, sort_order, id),
  KEY ix_tasks_category (category_id),
  CONSTRAINT fk_tasks_product  FOREIGN KEY (product_id)  REFERENCES products (id)   ON DELETE CASCADE,
  CONSTRAINT fk_tasks_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Practices and which products each is onboarding
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS practices (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name                VARCHAR(180) NOT NULL,
  slug                VARCHAR(200) NOT NULL,
  location            VARCHAR(180) DEFAULT NULL,
  onboarding_state    ENUM('active','on_hold','completed') NOT NULL DEFAULT 'active',
  target_go_live_date DATE DEFAULT NULL,
  notes               TEXT DEFAULT NULL,
  is_archived         TINYINT(1) NOT NULL DEFAULT 0,
  created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_practices_slug (slug),
  KEY ix_practices_state (onboarding_state, is_archived),
  KEY ix_practices_golive (target_go_live_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS practice_products (
  practice_id  INT UNSIGNED NOT NULL,
  product_id   INT UNSIGNED NOT NULL,
  added_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (practice_id, product_id),
  KEY ix_pp_product (product_id),
  CONSTRAINT fk_pp_practice FOREIGN KEY (practice_id) REFERENCES practices (id) ON DELETE CASCADE,
  CONSTRAINT fk_pp_product  FOREIGN KEY (product_id)  REFERENCES products (id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Per-practice state for a global task.
-- A row exists only once someone has touched the task. Absence of a row
-- means Not Started, which is why a brand new global task appears for
-- every practice immediately with no fan-out write.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS practice_tasks (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  practice_id  INT UNSIGNED NOT NULL,
  task_id      INT UNSIGNED NOT NULL,
  status       ENUM('not_started','in_progress','waiting','blocked','completed','not_applicable')
               NOT NULL DEFAULT 'not_started',
  assignee_id  INT UNSIGNED DEFAULT NULL,
  due_date     DATE DEFAULT NULL,
  notes        TEXT DEFAULT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_practice_task (practice_id, task_id),
  KEY ix_pt_status (status),
  KEY ix_pt_assignee (assignee_id),
  KEY ix_pt_due (due_date),
  CONSTRAINT fk_pt_practice FOREIGN KEY (practice_id) REFERENCES practices (id) ON DELETE CASCADE,
  CONSTRAINT fk_pt_task     FOREIGN KEY (task_id)     REFERENCES tasks (id)     ON DELETE CASCADE,
  CONSTRAINT fk_pt_assignee FOREIGN KEY (assignee_id) REFERENCES assignees (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Audit trail. Also the source of truth for "Last updated".
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS activity_log (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  practice_id  INT UNSIGNED DEFAULT NULL,
  task_id      INT UNSIGNED DEFAULT NULL,
  actor        VARCHAR(120) NOT NULL DEFAULT 'admin',
  entity       VARCHAR(60) NOT NULL,
  entity_id    INT UNSIGNED DEFAULT NULL,
  action       VARCHAR(40) NOT NULL,
  field        VARCHAR(60) DEFAULT NULL,
  old_value    TEXT DEFAULT NULL,
  new_value    TEXT DEFAULT NULL,
  summary      VARCHAR(400) DEFAULT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_log_practice (practice_id, created_at),
  KEY ix_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_settings (
  setting_key   VARCHAR(60) NOT NULL,
  setting_value TEXT DEFAULT NULL,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schema_migrations (
  filename   VARCHAR(190) NOT NULL,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
