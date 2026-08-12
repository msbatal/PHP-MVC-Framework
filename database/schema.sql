-- Blank SunMvc template schema. Column names match SunAuth's default
-- column map (System/SunAuth.php $config['columns']) and default table
-- name ("users") - init.php's `new SunAuth($authDb)` uses these defaults
-- with no overrides. If your project needs a different table name or
-- extra columns, override via `new SunAuth($authDb, ['table' => '...'])`
-- and add your own columns below (SunAuth ignores columns it doesn't
-- know about, so app-specific fields ride along for free).
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(100) DEFAULT NULL,
    `email` VARCHAR(190) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) DEFAULT 'user',
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `twofa_secret` VARCHAR(32) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Used by App/Controllers/Auth.php's register()/verifyemail() flow -
    -- drop these (and the matching form fields in App/Views/Auth.php) if
    -- your project doesn't want to collect a name or per-user language.
    `first_name` VARCHAR(50) NOT NULL DEFAULT '',
    `last_name` VARCHAR(50) NOT NULL DEFAULT '',
    `language` VARCHAR(2) NOT NULL DEFAULT 'en',
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Active sessions (database backed, multi device aware)
CREATE TABLE IF NOT EXISTS `sun_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `ip` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `twofa_pending` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    `last_activity` DATETIME NOT NULL,
    `expires_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token_hash` (`token_hash`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Failed login attempts (brute force protection / lockout)
CREATE TABLE IF NOT EXISTS `sun_login_attempts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `identifier` VARCHAR(190) NOT NULL,
    `ip` VARCHAR(45) NOT NULL,
    `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `last_attempt` DATETIME NOT NULL,
    `locked_until` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `identifier` (`identifier`, `ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Persistent remember-me tokens (selector + validator pattern)
CREATE TABLE IF NOT EXISTS `sun_remember_tokens` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `selector` CHAR(16) NOT NULL,
    `validator_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `selector` (`selector`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Password reset tokens
CREATE TABLE IF NOT EXISTS `sun_password_resets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token_hash` (`token_hash`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Signup email-verification codes (App/Controllers/Auth.php's register()/
-- verifyemail()). New accounts start with users.status = 0; SunAuth's own
-- login() already refuses inactive accounts, so activation is just:
-- verify a 6-digit code here, then flip status to 1 (App/Models/Auth.php's
-- verifyEmailCode()). Separate table from sun_password_resets on purpose -
-- account activation and password recovery are different concerns and
-- shouldn't be able to interfere with each other's tokens.
CREATE TABLE IF NOT EXISTS `sun_email_verifications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `code_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
