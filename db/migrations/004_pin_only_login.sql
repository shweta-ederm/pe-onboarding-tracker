-- People sign in with a PIN alone, so usernames are no longer used.
--
-- Because the PIN is now the only credential, failed attempts have to be
-- counted somewhere an attacker cannot clear. Session storage is not
-- that place: discarding a cookie resets it. This table is.
--
-- Paste into cPanel > phpMyAdmin > SQL if you cannot run migrate.php.

CREATE TABLE IF NOT EXISTS login_attempts (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip          VARBINARY(16) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_attempts_ip_time (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The username column stays, unused, rather than being dropped. Nothing
-- reads it any more, and keeping it means this change is reversible.
ALTER TABLE assignees DROP INDEX uq_assignees_username;
