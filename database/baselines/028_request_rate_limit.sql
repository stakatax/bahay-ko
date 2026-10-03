-- Approved M4 additive schema baseline.
CREATE TABLE `request_rate_limit` (
  `key_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `scope` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `expires_at` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`key_hash`),
  KEY `idx_request_rate_limit_expiry` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
