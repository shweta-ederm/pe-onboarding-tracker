-- Turns People into user accounts.
--
-- Each person gets a username and a 6-digit PIN so they can sign in.
-- They see everything and may change only the tasks assigned to them.
-- The global admin still signs in with the PIN in config/config.php.
--
-- `name` is kept as a denormalised display name, rebuilt from first and
-- last whenever a person is saved. Dozens of queries order and label by
-- it, and keeping it avoids CONCAT in every one of them.
--
-- Paste into cPanel > phpMyAdmin > SQL if you cannot run migrate.php.

ALTER TABLE assignees
  ADD COLUMN first_name VARCHAR(80)  NOT NULL DEFAULT '' AFTER id,
  ADD COLUMN last_name  VARCHAR(80)  NOT NULL DEFAULT '' AFTER first_name,
  ADD COLUMN username   VARCHAR(60)  DEFAULT NULL AFTER name,
  ADD COLUMN pin_hash   VARCHAR(255) DEFAULT NULL AFTER username,
  ADD COLUMN last_login DATETIME     DEFAULT NULL AFTER pin_hash;

-- Split whatever is in `name` into first and last as a starting point.
UPDATE assignees
   SET first_name = TRIM(SUBSTRING_INDEX(name, ' ', 1)),
       last_name  = TRIM(CASE WHEN LOCATE(' ', name) > 0
                              THEN SUBSTRING(name, LOCATE(' ', name) + 1)
                              ELSE '' END)
 WHERE first_name = '';

ALTER TABLE assignees
  ADD UNIQUE KEY uq_assignees_username (username);

-- Email is no longer collected.
ALTER TABLE assignees DROP COLUMN email;

-- A light colour per category, used to tint its group heading.
ALTER TABLE categories
  ADD COLUMN color CHAR(7) DEFAULT NULL AFTER name;

UPDATE categories SET color = '#0284C7' WHERE name = 'Administrative'    AND color IS NULL;
UPDATE categories SET color = '#6E62A8' WHERE name = 'Technical'         AND color IS NULL;
UPDATE categories SET color = '#0F8B8D' WHERE name = 'Configuration'     AND color IS NULL;
UPDATE categories SET color = '#B07AA1' WHERE name = 'Cosmetic / Design' AND color IS NULL;
UPDATE categories SET color = '#D9A253' WHERE name = 'Training'          AND color IS NULL;
UPDATE categories SET color = '#5B8FA8' WHERE name = 'Testing'           AND color IS NULL;
UPDATE categories SET color = '#3E8E5C' WHERE name = 'Go-Live'           AND color IS NULL;
UPDATE categories SET color = '#94A3B8' WHERE color IS NULL;
