-- Approved H3 additive schema baseline; original 62-table snapshot is unchanged.
CREATE TABLE `publication_notification_outbox` (
  `outbox_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `content_type` enum('announcement','event','document','survey') NOT NULL,
  `content_id` int(11) NOT NULL,
  `delivery_status` enum('Pending','Processing','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `attempt_count` int(10) unsigned NOT NULL DEFAULT 0,
  `available_at` datetime NOT NULL DEFAULT current_timestamp(),
  `locked_until` datetime DEFAULT NULL,
  `lock_token` char(64) DEFAULT NULL,
  `last_error` varchar(1000) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`outbox_id`),
  UNIQUE KEY `uq_publication_notification_content` (`content_type`,`content_id`),
  KEY `idx_publication_notification_due` (`delivery_status`,`available_at`,`outbox_id`),
  KEY `idx_publication_notification_lease` (`delivery_status`,`locked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
