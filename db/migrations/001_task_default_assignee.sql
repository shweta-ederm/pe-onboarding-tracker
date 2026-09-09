-- Adds a default assignee to each global task definition.
--
-- A practice task with nobody explicitly set falls back to this, so
-- changing the default here updates every practice that has not
-- overridden it. Nothing is copied or duplicated.
--
-- Safe to run once. If you cannot run tools/migrate.php on your host,
-- paste this into cPanel > phpMyAdmin > SQL and press Go.

ALTER TABLE tasks
  ADD COLUMN default_assignee_id INT UNSIGNED DEFAULT NULL AFTER category_id;

ALTER TABLE tasks
  ADD CONSTRAINT fk_tasks_default_assignee
  FOREIGN KEY (default_assignee_id) REFERENCES assignees (id) ON DELETE SET NULL;

ALTER TABLE tasks
  ADD INDEX ix_tasks_default_assignee (default_assignee_id);
