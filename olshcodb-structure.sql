-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: olshcodb
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `academic_program`
--

DROP TABLE IF EXISTS `academic_program`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_program` (
  `academic_program_id` int(11) NOT NULL AUTO_INCREMENT,
  `education_level_id` int(11) NOT NULL,
  `program_name` varchar(150) NOT NULL,
  `program_code` varchar(30) NOT NULL,
  `program_type` enum('Program','Strand') NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`academic_program_id`),
  UNIQUE KEY `uq_academic_program_code` (`education_level_id`,`program_code`),
  UNIQUE KEY `uq_academic_program_name` (`education_level_id`,`program_name`),
  KEY `idx_academic_program_level` (`education_level_id`),
  KEY `idx_academic_program_status` (`status`),
  CONSTRAINT `fk_academic_program_level` FOREIGN KEY (`education_level_id`) REFERENCES `education_level` (`education_level_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `academic_structure_history`
--

DROP TABLE IF EXISTS `academic_structure_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_structure_history` (
  `academic_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` enum('department','education_level','academic_program','grade_level','section') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `change_type` enum('create','update','activate','deactivate') NOT NULL,
  `previous_data` longtext DEFAULT NULL,
  `new_data` longtext DEFAULT NULL,
  `reason` varchar(1000) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`academic_history_id`),
  KEY `idx_academic_history_entity` (`entity_type`,`entity_id`,`created_at`),
  KEY `idx_academic_history_actor` (`changed_by`,`created_at`),
  CONSTRAINT `fk_academic_history_actor` FOREIGN KEY (`changed_by`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `actions`
--

DROP TABLE IF EXISTS `actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actions` (
  `action_id` int(11) NOT NULL AUTO_INCREMENT,
  `action_name` varchar(100) NOT NULL,
  PRIMARY KEY (`action_id`),
  UNIQUE KEY `uq_actions_action_name` (`action_name`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `browser` varchar(150) DEFAULT NULL,
  `device` varchar(150) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `action_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `target_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `action_id` (`action_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_activity_log_target_user` (`target_user_id`,`timestamp`),
  CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`action_id`) REFERENCES `actions` (`action_id`),
  CONSTRAINT `activity_log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
  CONSTRAINT `fk_activity_log_target_user` FOREIGN KEY (`target_user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=476 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `announcement_acknowledgment`
--

DROP TABLE IF EXISTS `announcement_acknowledgment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_acknowledgment` (
  `acknowledgment_id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `acknowledged_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`acknowledgment_id`),
  UNIQUE KEY `uq_announcement_acknowledgment` (`announcement_id`,`user_id`),
  KEY `idx_acknowledgment_announcement` (`announcement_id`),
  KEY `idx_acknowledgment_user` (`user_id`),
  CONSTRAINT `fk_acknowledgment_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_acknowledgment_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `announcement_comment`
--

DROP TABLE IF EXISTS `announcement_comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_comment` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`comment_id`),
  KEY `announcement_id` (`announcement_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `announcement_comment_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_comment_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `announcement_reaction`
--

DROP TABLE IF EXISTS `announcement_reaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_reaction` (
  `reaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reaction` enum('Like','Love','Care','Wow') DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`reaction_id`),
  KEY `announcement_id` (`announcement_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `announcement_reaction_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_reaction_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `announcement_target`
--

DROP TABLE IF EXISTS `announcement_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_target` (
  `target_id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `education_level_id` int(11) DEFAULT NULL,
  `academic_program_id` int(11) DEFAULT NULL,
  `grade_level_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`target_id`),
  KEY `announcement_id` (`announcement_id`),
  KEY `role_id` (`role_id`),
  KEY `department_id` (`department_id`),
  KEY `education_level_id` (`education_level_id`),
  KEY `grade_level_id` (`grade_level_id`),
  KEY `section_id` (`section_id`),
  KEY `idx_announcement_target_program` (`academic_program_id`),
  CONSTRAINT `announcement_target_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_target_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_target_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_target_ibfk_4` FOREIGN KEY (`education_level_id`) REFERENCES `education_level` (`education_level_id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_target_ibfk_5` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_level` (`grade_level_id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_target_ibfk_6` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_announcement_target_program` FOREIGN KEY (`academic_program_id`) REFERENCES `academic_program` (`academic_program_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=195 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `announcement_view`
--

DROP TABLE IF EXISTS `announcement_view`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_view` (
  `view_id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `viewed_at` datetime DEFAULT current_timestamp(),
  `duration_seconds` int(11) DEFAULT NULL,
  `source` enum('Dashboard','Notification','Search') DEFAULT 'Dashboard',
  PRIMARY KEY (`view_id`),
  KEY `announcement_id` (`announcement_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `announcement_view_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`announcement_id`) ON DELETE CASCADE,
  CONSTRAINT `announcement_view_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `audio_path` varchar(500) DEFAULT NULL,
  `audio_file_name` varchar(255) DEFAULT NULL,
  `audio_mime_type` varchar(100) DEFAULT NULL,
  `audio_file_size` bigint(20) unsigned DEFAULT NULL,
  `audio_transcript` text DEFAULT NULL,
  `reference_link` varchar(500) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `priority` enum('Low','Normal','Medium','High','Important','Urgent','Emergency') DEFAULT 'Medium',
  `created_at` datetime DEFAULT current_timestamp(),
  `published_at` datetime DEFAULT NULL,
  `allow_reactions` tinyint(1) NOT NULL DEFAULT 1,
  `allow_comments` tinyint(1) NOT NULL DEFAULT 1,
  `require_acknowledgment` tinyint(1) NOT NULL DEFAULT 0,
  `send_notification` tinyint(1) NOT NULL DEFAULT 1,
  `status` varchar(50) DEFAULT NULL,
  `workflow_status` enum('draft','pending_review','approved','rejected','scheduled','published','archived') NOT NULL DEFAULT 'draft',
  `release_mode` enum('immediate','scheduled','calendar') NOT NULL DEFAULT 'immediate',
  `scheduled_publish_at` datetime DEFAULT NULL,
  `calendar_event_id` int(11) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`announcement_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_announcement_workflow_status` (`workflow_status`),
  KEY `idx_announcement_scheduled_publish` (`scheduled_publish_at`),
  KEY `idx_announcement_calendar_event` (`calendar_event_id`),
  KEY `fk_announcement_reviewer` (`reviewed_by`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_announcement_calendar_event` FOREIGN KEY (`calendar_event_id`) REFERENCES `events` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_announcement_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `calendar_holiday`
--

DROP TABLE IF EXISTS `calendar_holiday`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calendar_holiday` (
  `calendar_holiday_id` int(11) NOT NULL AUTO_INCREMENT,
  `holiday_date` date NOT NULL,
  `title` varchar(150) NOT NULL,
  `holiday_type` enum('Regular','SpecialNonWorking','SpecialWorking','Local','School') NOT NULL,
  `scope` enum('Nationwide','Local','School') NOT NULL DEFAULT 'Nationwide',
  `description` varchar(500) DEFAULT NULL,
  `proclamation_reference` varchar(150) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`calendar_holiday_id`),
  UNIQUE KEY `uq_calendar_holiday` (`holiday_date`,`title`,`scope`),
  KEY `idx_calendar_holiday_directory` (`holiday_date`,`status`,`scope`),
  KEY `fk_calendar_holiday_creator` (`created_by`),
  CONSTRAINT `fk_calendar_holiday_creator` FOREIGN KEY (`created_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `content_acknowledgment`
--

DROP TABLE IF EXISTS `content_acknowledgment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `content_acknowledgment` (
  `acknowledgment_id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type` enum('announcement','event','document','survey') NOT NULL,
  `content_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `acknowledged_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`acknowledgment_id`),
  UNIQUE KEY `uq_content_acknowledgment` (`content_type`,`content_id`,`user_id`),
  KEY `idx_content_acknowledgment_lookup` (`content_type`,`content_id`),
  KEY `idx_content_acknowledgment_user` (`user_id`),
  CONSTRAINT `fk_content_acknowledgment_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `content_comment`
--

DROP TABLE IF EXISTS `content_comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `content_comment` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type` enum('announcement','event','document','survey') NOT NULL,
  `content_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `status` enum('Active','Hidden','Deleted') NOT NULL DEFAULT 'Active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`comment_id`),
  KEY `idx_content_comment_lookup` (`content_type`,`content_id`,`status`,`created_at`),
  KEY `idx_content_comment_user` (`user_id`),
  KEY `idx_content_comment_parent` (`parent_comment_id`),
  CONSTRAINT `fk_content_comment_parent` FOREIGN KEY (`parent_comment_id`) REFERENCES `content_comment` (`comment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_content_comment_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `content_interest`
--

DROP TABLE IF EXISTS `content_interest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `content_interest` (
  `interest_id` int(11) NOT NULL AUTO_INCREMENT,
  `interest_name` varchar(100) NOT NULL,
  `interest_slug` varchar(100) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`interest_id`),
  UNIQUE KEY `uq_content_interest_name` (`interest_name`),
  UNIQUE KEY `uq_content_interest_slug` (`interest_slug`),
  KEY `idx_content_interest_directory` (`status`,`sort_order`,`interest_name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `content_interest_assignment`
--

DROP TABLE IF EXISTS `content_interest_assignment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `content_interest_assignment` (
  `content_interest_assignment_id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type` enum('announcement','event','document','survey') NOT NULL,
  `content_id` int(11) NOT NULL,
  `interest_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`content_interest_assignment_id`),
  UNIQUE KEY `uq_content_interest_assignment` (`content_type`,`content_id`,`interest_id`),
  KEY `idx_content_interest_content` (`content_type`,`content_id`),
  KEY `idx_content_interest_topic` (`interest_id`,`content_type`),
  KEY `idx_content_interest_assigned_by` (`assigned_by`),
  CONSTRAINT `fk_content_interest_assignment_interest` FOREIGN KEY (`interest_id`) REFERENCES `content_interest` (`interest_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_content_interest_assignment_user` FOREIGN KEY (`assigned_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `content_reaction`
--

DROP TABLE IF EXISTS `content_reaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `content_reaction` (
  `reaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type` enum('announcement','event','document','survey') NOT NULL,
  `content_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reaction_type` enum('Like','Love','Care','Wow') NOT NULL,
  `reacted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`reaction_id`),
  UNIQUE KEY `uq_content_reaction` (`content_type`,`content_id`,`user_id`),
  KEY `idx_content_reaction_lookup` (`content_type`,`content_id`),
  KEY `idx_content_reaction_user` (`user_id`),
  CONSTRAINT `fk_content_reaction_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `content_view`
--

DROP TABLE IF EXISTS `content_view`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `content_view` (
  `view_id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type` enum('announcement','event','document','survey') NOT NULL,
  `content_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `viewed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`view_id`),
  UNIQUE KEY `uq_content_view` (`content_type`,`content_id`,`user_id`),
  KEY `idx_content_view_lookup` (`content_type`,`content_id`),
  KEY `idx_content_view_user` (`user_id`),
  CONSTRAINT `fk_content_view_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=257 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `department`
--

DROP TABLE IF EXISTS `department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department` (
  `department_id` int(11) NOT NULL AUTO_INCREMENT,
  `department_name` varchar(100) DEFAULT NULL,
  `department_code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `department_name` (`department_name`),
  UNIQUE KEY `uq_department_code` (`department_code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `document_download`
--

DROP TABLE IF EXISTS `document_download`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_download` (
  `download_id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `downloaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`download_id`),
  KEY `idx_document_download_document` (`document_id`),
  KEY `idx_document_download_user` (`user_id`),
  CONSTRAINT `fk_document_download_document` FOREIGN KEY (`document_id`) REFERENCES `documents` (`document_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_document_download_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `document_target`
--

DROP TABLE IF EXISTS `document_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_target` (
  `target_id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `education_level_id` int(11) DEFAULT NULL,
  `academic_program_id` int(11) DEFAULT NULL,
  `grade_level_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`target_id`),
  KEY `document_id` (`document_id`),
  KEY `idx_document_target_program` (`academic_program_id`),
  CONSTRAINT `document_target_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`document_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_document_target_program` FOREIGN KEY (`academic_program_id`) REFERENCES `academic_program` (`academic_program_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents` (
  `document_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `cover_image_path` varchar(500) DEFAULT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'active',
  `workflow_status` enum('draft','pending_review','approved','scheduled','published','rejected','archived') NOT NULL DEFAULT 'draft',
  `release_mode` enum('immediate','scheduled','calendar') NOT NULL DEFAULT 'immediate',
  `scheduled_publish_at` datetime DEFAULT NULL,
  `calendar_event_id` int(11) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `send_notification` tinyint(1) NOT NULL DEFAULT 1,
  `user_id` int(11) DEFAULT NULL,
  `allow_reactions` tinyint(1) NOT NULL DEFAULT 1,
  `allow_comments` tinyint(1) NOT NULL DEFAULT 1,
  `require_acknowledgment` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`document_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_document_workflow_status` (`workflow_status`),
  KEY `fk_document_reviewer` (`reviewed_by`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_document_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `education_level`
--

DROP TABLE IF EXISTS `education_level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `education_level` (
  `education_level_id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) NOT NULL,
  `education_level_name` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  PRIMARY KEY (`education_level_id`),
  UNIQUE KEY `education_level_name` (`education_level_name`),
  KEY `idx_education_level_department` (`department_id`),
  CONSTRAINT `fk_education_level_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `email_delivery`
--

DROP TABLE IF EXISTS `email_delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_delivery` (
  `delivery_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `recipient_email` varchar(254) NOT NULL,
  `delivery_status` enum('Pending','Sent','Failed','Skipped') NOT NULL DEFAULT 'Pending',
  `attempt_count` int(11) NOT NULL DEFAULT 0,
  `last_error` varchar(1000) DEFAULT NULL,
  `queued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_attempt_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`delivery_id`),
  UNIQUE KEY `uq_email_delivery_notification` (`notification_id`),
  KEY `idx_email_delivery_status_queue` (`delivery_status`,`queued_at`),
  CONSTRAINT `fk_email_delivery_notification` FOREIGN KEY (`notification_id`) REFERENCES `notification` (`notification_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=200 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `event_target`
--

DROP TABLE IF EXISTS `event_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_target` (
  `target_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `education_level_id` int(11) DEFAULT NULL,
  `academic_program_id` int(11) DEFAULT NULL,
  `grade_level_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`target_id`),
  KEY `event_id` (`event_id`),
  KEY `idx_event_target_program` (`academic_program_id`),
  CONSTRAINT `event_target_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_target_program` FOREIGN KEY (`academic_program_id`) REFERENCES `academic_program` (`academic_program_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT NULL,
  `workflow_status` enum('draft','pending_review','approved','scheduled','published','rejected','archived') NOT NULL DEFAULT 'draft',
  `release_mode` enum('immediate','scheduled','calendar') NOT NULL DEFAULT 'immediate',
  `scheduled_publish_at` datetime DEFAULT NULL,
  `calendar_event_id` int(11) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `send_notification` tinyint(1) NOT NULL DEFAULT 1,
  `user_id` int(11) DEFAULT NULL,
  `allow_reactions` tinyint(1) NOT NULL DEFAULT 1,
  `allow_comments` tinyint(1) NOT NULL DEFAULT 1,
  `require_acknowledgment` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`event_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_event_workflow_status` (`workflow_status`),
  KEY `fk_event_reviewer` (`reviewed_by`),
  CONSTRAINT `events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_event_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `government_advisory`
--

DROP TABLE IF EXISTS `government_advisory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `government_advisory` (
  `government_advisory_id` int(11) NOT NULL AUTO_INCREMENT,
  `government_source_id` int(11) NOT NULL,
  `external_reference` varchar(150) DEFAULT NULL,
  `source_url` varchar(1000) NOT NULL,
  `source_url_hash` char(64) NOT NULL,
  `source_page_mode` enum('Specific','Reusable') NOT NULL DEFAULT 'Specific',
  `title` varchar(255) NOT NULL,
  `summary` text DEFAULT NULL,
  `extracted_text` mediumtext DEFAULT NULL,
  `content_hash` char(64) DEFAULT NULL,
  `advisory_type` enum('Holiday','EducationPolicy','ClassSuspension','Emergency','Weather','HealthSafety','Scholarship','Compliance','Other') NOT NULL DEFAULT 'Other',
  `geographic_scope` enum('Nationwide','Region','Province','Municipality','School') NOT NULL DEFAULT 'Nationwide',
  `scope_value` varchar(150) DEFAULT NULL,
  `issued_at` datetime DEFAULT NULL,
  `effective_from` datetime DEFAULT NULL,
  `effective_until` datetime DEFAULT NULL,
  `relevance_score` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `relevance_reasons` text DEFAULT NULL,
  `fetch_status` enum('Pending','Fetched','Failed','Manual') NOT NULL DEFAULT 'Pending',
  `retrieval_error` varchar(500) DEFAULT NULL,
  `review_status` enum('Pending','Relevant','Irrelevant','Converted','Archived') NOT NULL DEFAULT 'Pending',
  `review_notes` varchar(1000) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `linked_content_type` enum('announcement','event','document','survey','holiday') DEFAULT NULL,
  `linked_content_id` int(11) DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `fetched_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`government_advisory_id`),
  UNIQUE KEY `uq_government_advisory_url_reference` (`source_url_hash`,`external_reference`),
  KEY `idx_government_advisory_review` (`review_status`,`relevance_score`,`created_at`),
  KEY `idx_government_advisory_type` (`advisory_type`,`geographic_scope`,`effective_from`),
  KEY `idx_government_advisory_reference` (`government_source_id`,`external_reference`),
  KEY `idx_government_advisory_content_hash` (`content_hash`),
  KEY `idx_government_advisory_link` (`linked_content_type`,`linked_content_id`),
  KEY `fk_government_advisory_reviewer` (`reviewed_by`),
  KEY `fk_government_advisory_submitter` (`submitted_by`),
  CONSTRAINT `fk_government_advisory_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_government_advisory_source` FOREIGN KEY (`government_source_id`) REFERENCES `government_source` (`government_source_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_government_advisory_submitter` FOREIGN KEY (`submitted_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `government_advisory_rule`
--

DROP TABLE IF EXISTS `government_advisory_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `government_advisory_rule` (
  `government_advisory_rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(150) NOT NULL,
  `match_field` enum('Title','Content','CombinedText','AgencyCategory','SourceHost') NOT NULL,
  `match_value` varchar(150) NOT NULL,
  `advisory_type` varchar(40) DEFAULT NULL,
  `geographic_scope` varchar(40) DEFAULT NULL,
  `score_adjustment` smallint(6) NOT NULL DEFAULT 0,
  `priority_order` int(11) NOT NULL DEFAULT 100,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`government_advisory_rule_id`),
  UNIQUE KEY `uq_government_advisory_rule_name` (`rule_name`),
  KEY `idx_government_advisory_rule_engine` (`status`,`priority_order`,`match_field`),
  KEY `fk_government_advisory_rule_creator` (`created_by`),
  CONSTRAINT `fk_government_advisory_rule_creator` FOREIGN KEY (`created_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `government_source`
--

DROP TABLE IF EXISTS `government_source`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `government_source` (
  `government_source_id` int(11) NOT NULL AUTO_INCREMENT,
  `source_name` varchar(150) NOT NULL,
  `agency_code` varchar(30) DEFAULT NULL,
  `agency_category` enum('NationalGovernment','Education','WeatherEmergency','LocalGovernment','HealthSafety','Other') NOT NULL DEFAULT 'Other',
  `base_url` varchar(500) NOT NULL,
  `allowed_host` varchar(255) NOT NULL,
  `connector_type` enum('ManualUrl','Html','Rss','JsonApi') NOT NULL DEFAULT 'ManualUrl',
  `authority_weight` tinyint(3) unsigned NOT NULL DEFAULT 50,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`government_source_id`),
  UNIQUE KEY `uq_government_source_host` (`allowed_host`),
  KEY `idx_government_source_directory` (`status`,`agency_category`,`source_name`),
  KEY `fk_government_source_creator` (`created_by`),
  CONSTRAINT `fk_government_source_creator` FOREIGN KEY (`created_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `grade_level`
--

DROP TABLE IF EXISTS `grade_level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grade_level` (
  `grade_level_id` int(11) NOT NULL AUTO_INCREMENT,
  `education_level_id` int(11) DEFAULT NULL,
  `grade_level_name` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  PRIMARY KEY (`grade_level_id`),
  UNIQUE KEY `uq_grade_level_name` (`education_level_id`,`grade_level_name`),
  CONSTRAINT `grade_level_ibfk_1` FOREIGN KEY (`education_level_id`) REFERENCES `education_level` (`education_level_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `legal_document_version`
--

DROP TABLE IF EXISTS `legal_document_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legal_document_version` (
  `legal_document_version_id` int(11) NOT NULL AUTO_INCREMENT,
  `document_type` enum('Terms','Privacy') NOT NULL,
  `version` varchar(30) NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` longtext NOT NULL,
  `effective_at` datetime NOT NULL,
  `status` enum('Draft','Active','Retired') NOT NULL DEFAULT 'Draft',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`legal_document_version_id`),
  UNIQUE KEY `uq_legal_document_version` (`document_type`,`version`),
  KEY `idx_legal_document_active` (`document_type`,`status`,`effective_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notification`
--

DROP TABLE IF EXISTS `notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `notification_type` enum('system','content','reminder','workflow') NOT NULL DEFAULT 'system',
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `content_type` enum('announcement','event','document','survey') DEFAULT NULL,
  `content_id` int(11) DEFAULT NULL,
  `in_system_visible` tinyint(1) NOT NULL DEFAULT 1,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `deduplication_key` varchar(191) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  UNIQUE KEY `uq_notification_user_deduplication` (`user_id`,`deduplication_key`),
  KEY `idx_notification_user_unread_created` (`user_id`,`is_read`,`created_at`),
  KEY `idx_notification_content` (`content_type`,`content_id`),
  KEY `idx_notification_user_visible_unread` (`user_id`,`in_system_visible`,`is_read`,`created_at`),
  CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18356 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notification_category_preference`
--

DROP TABLE IF EXISTS `notification_category_preference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_category_preference` (
  `category_preference_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notification_category` enum('content_updates','discussion','engagement','reminders','workflow','account_system') NOT NULL,
  `system_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `email_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `browser_push_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`category_preference_id`),
  UNIQUE KEY `uq_notification_category_user` (`user_id`,`notification_category`),
  KEY `idx_notification_category_delivery` (`notification_category`,`system_enabled`,`email_enabled`,`browser_push_enabled`),
  CONSTRAINT `fk_notification_category_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=280 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notification_preference`
--

DROP TABLE IF EXISTS `notification_preference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_preference` (
  `preference_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `email_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `email_enabled_at` datetime DEFAULT NULL,
  `system_enabled` tinyint(1) DEFAULT 1,
  `browser_push_enabled` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `notification_preference_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `parent_student`
--

DROP TABLE IF EXISTS `parent_student`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent_student` (
  `parent_student_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_user_id` int(11) NOT NULL,
  `student_user_id` int(11) NOT NULL,
  `relationship` varchar(50) NOT NULL,
  `status` enum('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`parent_student_id`),
  UNIQUE KEY `uq_parent_student` (`parent_user_id`,`student_user_id`),
  KEY `idx_parent_student_parent` (`parent_user_id`),
  KEY `idx_parent_student_student` (`student_user_id`),
  KEY `idx_parent_student_status` (`status`),
  KEY `fk_parent_student_verifier` (`verified_by`),
  CONSTRAINT `fk_parent_student_parent` FOREIGN KEY (`parent_user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_parent_student_student` FOREIGN KEY (`student_user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_parent_student_verifier` FOREIGN KEY (`verified_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset_request`
--

DROP TABLE IF EXISTS `password_reset_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_request` (
  `password_reset_request_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `identifier_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `request_ip_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`password_reset_request_id`),
  KEY `idx_password_reset_identifier` (`identifier_hash`,`requested_at`),
  KEY `idx_password_reset_ip` (`request_ip_hash`,`requested_at`),
  KEY `idx_password_reset_request_user` (`user_id`,`requested_at`),
  CONSTRAINT `fk_password_reset_request_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset_token`
--

DROP TABLE IF EXISTS `password_reset_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_token` (
  `password_reset_token_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `request_ip_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `user_agent_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `invalidated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`password_reset_token_id`),
  UNIQUE KEY `uq_password_reset_token_hash` (`token_hash`),
  KEY `idx_password_reset_token_user` (`user_id`,`created_at`),
  KEY `idx_password_reset_token_state` (`user_id`,`used_at`,`invalidated_at`,`expires_at`),
  CONSTRAINT `fk_password_reset_token_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `push_delivery`
--

DROP TABLE IF EXISTS `push_delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `push_delivery` (
  `delivery_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `subscription_id` bigint(20) NOT NULL,
  `delivery_status` enum('Pending','Sent','Failed','Expired','Skipped') NOT NULL DEFAULT 'Pending',
  `attempt_count` int(11) NOT NULL DEFAULT 0,
  `last_http_status` smallint(5) unsigned DEFAULT NULL,
  `last_error` varchar(1000) DEFAULT NULL,
  `queued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_attempt_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`delivery_id`),
  UNIQUE KEY `uq_push_delivery_notification_subscription` (`notification_id`,`subscription_id`),
  KEY `idx_push_delivery_status_queue` (`delivery_status`,`queued_at`),
  KEY `idx_push_delivery_subscription` (`subscription_id`),
  CONSTRAINT `fk_push_delivery_notification` FOREIGN KEY (`notification_id`) REFERENCES `notification` (`notification_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_push_delivery_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `push_subscription` (`subscription_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9277 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `push_subscription`
--

DROP TABLE IF EXISTS `push_subscription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `push_subscription` (
  `subscription_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `endpoint_hash` char(64) NOT NULL,
  `endpoint` text NOT NULL,
  `public_key` varchar(255) NOT NULL,
  `auth_token` varchar(255) NOT NULL,
  `content_encoding` varchar(50) NOT NULL DEFAULT 'aes128gcm',
  `user_agent` varchar(500) DEFAULT NULL,
  `device_label` varchar(100) DEFAULT NULL,
  `subscription_status` enum('Active','Expired','Revoked') NOT NULL DEFAULT 'Active',
  `failure_count` int(11) NOT NULL DEFAULT 0,
  `last_used_at` datetime DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`subscription_id`),
  UNIQUE KEY `uq_push_subscription_endpoint` (`endpoint_hash`),
  KEY `idx_push_subscription_user_status` (`user_id`,`subscription_status`),
  CONSTRAINT `fk_push_subscription_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_prefix` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_prefix` (`role_prefix`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `section`
--

DROP TABLE IF EXISTS `section`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `section` (
  `section_id` int(11) NOT NULL AUTO_INCREMENT,
  `grade_level_id` int(11) DEFAULT NULL,
  `academic_program_id` int(11) DEFAULT NULL,
  `academic_program_scope_id` int(11) GENERATED ALWAYS AS (coalesce(`academic_program_id`,0)) STORED,
  `section_name` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  PRIMARY KEY (`section_id`),
  UNIQUE KEY `uq_section_assignment` (`grade_level_id`,`academic_program_scope_id`,`section_name`),
  KEY `idx_section_academic_program` (`academic_program_id`),
  CONSTRAINT `fk_section_academic_program` FOREIGN KEY (`academic_program_id`) REFERENCES `academic_program` (`academic_program_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `section_ibfk_1` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_level` (`grade_level_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_profile`
--

DROP TABLE IF EXISTS `student_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_profile` (
  `student_profile_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `completion_status` enum('NotStarted','InProgress','Completed') NOT NULL DEFAULT 'NotStarted',
  `survey_completion_status` enum('NotStarted','InProgress','Completed') NOT NULL DEFAULT 'NotStarted',
  `survey_version` int(11) NOT NULL DEFAULT 1,
  `survey_current_step` varchar(50) DEFAULT NULL,
  `survey_completed_at` datetime DEFAULT NULL,
  `survey_last_saved_at` datetime DEFAULT NULL,
  `personalization_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `profile_version` int(11) NOT NULL DEFAULT 1,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`student_profile_id`),
  UNIQUE KEY `uq_student_profile_user` (`user_id`),
  KEY `idx_student_profile_completion` (`completion_status`,`updated_at`),
  KEY `idx_student_profile_survey_completion` (`survey_completion_status`,`survey_version`,`survey_last_saved_at`),
  CONSTRAINT `fk_student_profile_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_profile_consent`
--

DROP TABLE IF EXISTS `student_profile_consent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_profile_consent` (
  `student_profile_consent_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `student_profile_id` int(11) NOT NULL,
  `consent_key` varchar(100) NOT NULL,
  `consent_version` varchar(30) NOT NULL,
  `consent_granted` tinyint(1) NOT NULL DEFAULT 0,
  `granted_at` datetime DEFAULT NULL,
  `withdrawn_at` datetime DEFAULT NULL,
  `acceptance_source` enum('Survey','ProfileUpdate') NOT NULL DEFAULT 'Survey',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`student_profile_consent_id`),
  UNIQUE KEY `uq_student_profile_consent` (`student_profile_id`,`consent_key`,`consent_version`),
  KEY `idx_student_profile_consent_lookup` (`consent_key`,`consent_granted`,`granted_at`),
  CONSTRAINT `fk_student_profile_consent_profile` FOREIGN KEY (`student_profile_id`) REFERENCES `student_profile` (`student_profile_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_profile_consent_definition`
--

DROP TABLE IF EXISTS `student_profile_consent_definition`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_profile_consent_definition` (
  `student_profile_consent_definition_id` int(11) NOT NULL AUTO_INCREMENT,
  `consent_key` varchar(100) NOT NULL,
  `consent_version` varchar(30) NOT NULL,
  `title` varchar(150) NOT NULL,
  `consent_statement` text NOT NULL,
  `status` enum('Draft','Active','Retired') NOT NULL DEFAULT 'Draft',
  `effective_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`student_profile_consent_definition_id`),
  UNIQUE KEY `uq_student_profile_consent_definition` (`consent_key`,`consent_version`),
  KEY `idx_student_profile_consent_active` (`status`,`effective_at`,`consent_key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_profile_cycle`
--

DROP TABLE IF EXISTS `student_profile_cycle`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_profile_cycle` (
  `student_profile_cycle_id` int(11) NOT NULL AUTO_INCREMENT,
  `cycle_name` varchar(150) NOT NULL,
  `academic_year` varchar(30) DEFAULT NULL,
  `cycle_type` enum('Initial','SchoolYear','Semester','Custom') NOT NULL DEFAULT 'Custom',
  `academic_term` enum('NotApplicable','FirstSemester','SecondSemester','Summer','Custom') NOT NULL DEFAULT 'NotApplicable',
  `survey_version` int(11) NOT NULL,
  `status` enum('Draft','Scheduled','Active','Closed','Cancelled') NOT NULL DEFAULT 'Draft',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `opens_at` datetime DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `activated_by` int(11) DEFAULT NULL,
  `closed_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `activated_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`student_profile_cycle_id`),
  KEY `idx_student_profile_cycle_status` (`status`,`opens_at`,`due_at`),
  KEY `idx_student_profile_cycle_version` (`survey_version`,`status`),
  KEY `idx_student_profile_cycle_default` (`is_default`,`status`),
  KEY `idx_student_profile_cycle_created_by` (`created_by`),
  KEY `idx_student_profile_cycle_activated_by` (`activated_by`),
  KEY `idx_student_profile_cycle_closed_by` (`closed_by`),
  CONSTRAINT `fk_student_profile_cycle_activated_by` FOREIGN KEY (`activated_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_student_profile_cycle_closed_by` FOREIGN KEY (`closed_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_student_profile_cycle_created_by` FOREIGN KEY (`created_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_student_profile_cycle_version` FOREIGN KEY (`survey_version`) REFERENCES `student_profile_survey_version` (`survey_version`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_profile_cycle_assignment`
--

DROP TABLE IF EXISTS `student_profile_cycle_assignment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_profile_cycle_assignment` (
  `student_profile_cycle_assignment_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `student_profile_cycle_id` int(11) NOT NULL,
  `student_profile_id` int(11) NOT NULL,
  `assignment_status` enum('Assigned','InProgress','Completed','Exempt') NOT NULL DEFAULT 'Assigned',
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `last_saved_at` datetime DEFAULT NULL,
  `exempted_at` datetime DEFAULT NULL,
  `exemption_reason` varchar(1000) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`student_profile_cycle_assignment_id`),
  UNIQUE KEY `uq_student_profile_cycle_assignment` (`student_profile_cycle_id`,`student_profile_id`),
  KEY `idx_student_profile_cycle_assignment_status` (`assignment_status`,`assigned_at`,`completed_at`),
  KEY `idx_student_profile_cycle_assignment_profile` (`student_profile_id`,`assignment_status`),
  KEY `idx_student_profile_cycle_assignment_assigned_by` (`assigned_by`),
  CONSTRAINT `fk_student_profile_cycle_assignment_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_student_profile_cycle_assignment_cycle` FOREIGN KEY (`student_profile_cycle_id`) REFERENCES `student_profile_cycle` (`student_profile_cycle_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_student_profile_cycle_assignment_profile` FOREIGN KEY (`student_profile_id`) REFERENCES `student_profile` (`student_profile_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_profile_cycle_scope`
--

DROP TABLE IF EXISTS `student_profile_cycle_scope`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_profile_cycle_scope` (
  `student_profile_cycle_scope_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `student_profile_cycle_id` int(11) NOT NULL,
  `scope_type` enum('AllStudents','Department','EducationLevel','AcademicProgram','GradeLevel','Section') NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `education_level_id` int(11) DEFAULT NULL,
  `academic_program_id` int(11) DEFAULT NULL,
  `grade_level_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `scope_signature` varchar(180) GENERATED ALWAYS AS (concat(`scope_type`,':',coalesce(`department_id`,0),':',coalesce(`education_level_id`,0),':',coalesce(`academic_program_id`,0),':',coalesce(`grade_level_id`,0),':',coalesce(`section_id`,0))) STORED,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`student_profile_cycle_scope_id`),
  UNIQUE KEY `uq_student_profile_cycle_scope` (`student_profile_cycle_id`,`scope_signature`),
  KEY `idx_student_profile_cycle_scope_department` (`department_id`),
  KEY `idx_student_profile_cycle_scope_level` (`education_level_id`),
  KEY `idx_student_profile_cycle_scope_program` (`academic_program_id`),
  KEY `idx_student_profile_cycle_scope_grade` (`grade_level_id`),
  KEY `idx_student_profile_cycle_scope_section` (`section_id`),
  CONSTRAINT `fk_student_profile_cycle_scope_cycle` FOREIGN KEY (`student_profile_cycle_id`) REFERENCES `student_profile_cycle` (`student_profile_cycle_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_student_profile_cycle_scope_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_student_profile_cycle_scope_grade` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_level` (`grade_level_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_student_profile_cycle_scope_level` FOREIGN KEY (`education_level_id`) REFERENCES `education_level` (`education_level_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_student_profile_cycle_scope_program` FOREIGN KEY (`academic_program_id`) REFERENCES `academic_program` (`academic_program_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_student_profile_cycle_scope_section` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_profile_interest`
--

DROP TABLE IF EXISTS `student_profile_interest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_profile_interest` (
  `student_profile_interest_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_profile_id` int(11) NOT NULL,
  `interest_id` int(11) NOT NULL,
  `preference_weight` tinyint(4) NOT NULL DEFAULT 3,
  `selected_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`student_profile_interest_id`),
  UNIQUE KEY `uq_student_profile_interest` (`student_profile_id`,`interest_id`),
  KEY `idx_student_interest_lookup` (`interest_id`,`preference_weight`),
  CONSTRAINT `fk_student_interest_catalog` FOREIGN KEY (`interest_id`) REFERENCES `content_interest` (`interest_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_student_interest_profile` FOREIGN KEY (`student_profile_id`) REFERENCES `student_profile` (`student_profile_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_profile_question`
--

DROP TABLE IF EXISTS `student_profile_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_profile_question` (
  `student_profile_question_id` int(11) NOT NULL AUTO_INCREMENT,
  `question_key` varchar(100) NOT NULL,
  `section_key` varchar(50) NOT NULL,
  `section_label` varchar(100) NOT NULL,
  `section_sort_order` int(11) NOT NULL DEFAULT 0,
  `question_text` varchar(500) NOT NULL,
  `help_text` varchar(500) DEFAULT NULL,
  `response_type` enum('SingleChoice','MultipleChoice','Boolean','ShortText','LongText','Number') NOT NULL,
  `options_json` longtext DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `is_sensitive` tinyint(1) NOT NULL DEFAULT 0,
  `consent_key` varchar(100) DEFAULT NULL,
  `analytics_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `survey_version` int(11) NOT NULL DEFAULT 1,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`student_profile_question_id`),
  UNIQUE KEY `uq_student_profile_question_key` (`question_key`,`survey_version`),
  KEY `idx_student_profile_question_directory` (`survey_version`,`status`,`section_key`,`sort_order`),
  KEY `idx_student_profile_question_sensitive` (`is_sensitive`,`consent_key`),
  KEY `idx_student_profile_question_form` (`survey_version`,`status`,`section_sort_order`,`sort_order`),
  CONSTRAINT `fk_student_profile_question_survey_version` FOREIGN KEY (`survey_version`) REFERENCES `student_profile_survey_version` (`survey_version`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_profile_response`
--

DROP TABLE IF EXISTS `student_profile_response`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_profile_response` (
  `student_profile_response_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `student_profile_id` int(11) NOT NULL,
  `student_profile_question_id` int(11) NOT NULL,
  `response_json` longtext NOT NULL,
  `responded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`student_profile_response_id`),
  UNIQUE KEY `uq_student_profile_response` (`student_profile_id`,`student_profile_question_id`),
  KEY `idx_student_profile_response_question` (`student_profile_question_id`,`updated_at`),
  CONSTRAINT `fk_student_profile_response_profile` FOREIGN KEY (`student_profile_id`) REFERENCES `student_profile` (`student_profile_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_student_profile_response_question` FOREIGN KEY (`student_profile_question_id`) REFERENCES `student_profile_question` (`student_profile_question_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_profile_survey_version`
--

DROP TABLE IF EXISTS `student_profile_survey_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_profile_survey_version` (
  `survey_version` int(11) NOT NULL,
  `version_name` varchar(150) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `status` enum('Draft','Active','Retired') NOT NULL DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `activated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `activated_at` datetime DEFAULT NULL,
  `retired_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`survey_version`),
  KEY `idx_student_profile_survey_version_status` (`status`,`survey_version`),
  KEY `idx_student_profile_survey_version_created_by` (`created_by`),
  KEY `idx_student_profile_survey_version_activated_by` (`activated_by`),
  CONSTRAINT `fk_student_profile_survey_version_activated_by` FOREIGN KEY (`activated_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_student_profile_survey_version_created_by` FOREIGN KEY (`created_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey`
--

DROP TABLE IF EXISTS `survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey` (
  `survey_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Draft','Published','Archived') DEFAULT NULL,
  `workflow_status` enum('draft','pending_review','approved','rejected','scheduled','published','archived') NOT NULL DEFAULT 'draft',
  `release_mode` enum('immediate','scheduled','calendar') NOT NULL DEFAULT 'immediate',
  `scheduled_publish_at` datetime DEFAULT NULL,
  `calendar_event_id` int(11) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `opens_at` datetime DEFAULT NULL,
  `closes_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `allow_comments` tinyint(1) NOT NULL DEFAULT 0,
  `send_notification` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `allow_reactions` tinyint(1) NOT NULL DEFAULT 1,
  `require_acknowledgment` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`survey_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_survey_workflow_status` (`workflow_status`),
  KEY `fk_survey_reviewer` (`reviewed_by`),
  KEY `idx_survey_calendar_event` (`calendar_event_id`),
  CONSTRAINT `fk_survey_calendar_event` FOREIGN KEY (`calendar_event_id`) REFERENCES `events` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_survey_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `survey_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey_answer`
--

DROP TABLE IF EXISTS `survey_answer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_answer` (
  `answer_id` int(11) NOT NULL AUTO_INCREMENT,
  `response_id` int(11) DEFAULT NULL,
  `question_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `answer` text DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`answer_id`),
  KEY `question_id` (`question_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_survey_answer_response` (`response_id`),
  CONSTRAINT `fk_survey_answer_response` FOREIGN KEY (`response_id`) REFERENCES `survey_response` (`response_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `survey_answer_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `survey_question` (`question_id`),
  CONSTRAINT `survey_answer_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey_answer_choice`
--

DROP TABLE IF EXISTS `survey_answer_choice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_answer_choice` (
  `answer_id` int(11) NOT NULL,
  `choice_id` int(11) NOT NULL,
  PRIMARY KEY (`answer_id`,`choice_id`),
  KEY `idx_survey_answer_choice_choice` (`choice_id`),
  CONSTRAINT `fk_survey_answer_choice_answer` FOREIGN KEY (`answer_id`) REFERENCES `survey_answer` (`answer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_survey_answer_choice_choice` FOREIGN KEY (`choice_id`) REFERENCES `survey_choice` (`choice_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey_choice`
--

DROP TABLE IF EXISTS `survey_choice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_choice` (
  `choice_id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) DEFAULT NULL,
  `choice_text` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`choice_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `survey_choice_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `survey_question` (`question_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey_question`
--

DROP TABLE IF EXISTS `survey_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_question` (
  `question_id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) DEFAULT NULL,
  `question` text DEFAULT NULL,
  `question_type` enum('Text','Multiple Choice','Checkbox','Rating','Short Text','Long Text','Yes/No') DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 1,
  `rating_min` tinyint(4) DEFAULT NULL,
  `rating_max` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`question_id`),
  KEY `survey_id` (`survey_id`),
  CONSTRAINT `survey_question_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `survey` (`survey_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey_response`
--

DROP TABLE IF EXISTS `survey_response`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_response` (
  `response_id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `started_at` datetime DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`response_id`),
  UNIQUE KEY `uq_survey_user_response` (`survey_id`,`user_id`),
  KEY `idx_survey_response_survey` (`survey_id`),
  KEY `idx_survey_response_user` (`user_id`),
  CONSTRAINT `fk_survey_response_survey` FOREIGN KEY (`survey_id`) REFERENCES `survey` (`survey_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_survey_response_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey_target`
--

DROP TABLE IF EXISTS `survey_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_target` (
  `target_id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `education_level_id` int(11) DEFAULT NULL,
  `academic_program_id` int(11) DEFAULT NULL,
  `grade_level_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`target_id`),
  KEY `survey_id` (`survey_id`),
  KEY `idx_survey_target_program` (`academic_program_id`),
  KEY `fk_survey_target_role` (`role_id`),
  KEY `fk_survey_target_department` (`department_id`),
  KEY `fk_survey_target_education_level` (`education_level_id`),
  KEY `fk_survey_target_grade_level` (`grade_level_id`),
  KEY `fk_survey_target_section` (`section_id`),
  CONSTRAINT `fk_survey_target_department` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_survey_target_education_level` FOREIGN KEY (`education_level_id`) REFERENCES `education_level` (`education_level_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_survey_target_grade_level` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_level` (`grade_level_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_survey_target_program` FOREIGN KEY (`academic_program_id`) REFERENCES `academic_program` (`academic_program_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_survey_target_role` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_survey_target_section` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `survey_target_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `survey` (`survey_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `studID` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `name_suffix` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `password_changed_at` datetime DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `age` int(11) NOT NULL,
  `birthdate` date DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Active','Inactive','Rejected') NOT NULL DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `account_review_notes` varchar(1000) DEFAULT NULL,
  `account_reviewed_at` datetime DEFAULT NULL,
  `account_reviewed_by` int(11) DEFAULT NULL,
  `provisioned_by` int(11) DEFAULT NULL,
  `provisioned_at` datetime DEFAULT NULL,
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `lock_until` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `education_level_id` int(11) DEFAULT NULL,
  `academic_program_id` int(11) DEFAULT NULL,
  `grade_level_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `studID` (`studID`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  KEY `department_id` (`department_id`),
  KEY `fk_user_education` (`education_level_id`),
  KEY `fk_user_grade` (`grade_level_id`),
  KEY `fk_user_section` (`section_id`),
  KEY `fk_user_approved_by` (`approved_by`),
  KEY `idx_user_academic_program` (`academic_program_id`),
  KEY `idx_user_status_created` (`status`,`created_at`),
  KEY `fk_user_account_reviewed_by` (`account_reviewed_by`),
  KEY `idx_user_provisioned_by` (`provisioned_by`),
  CONSTRAINT `fk_user_academic_program` FOREIGN KEY (`academic_program_id`) REFERENCES `academic_program` (`academic_program_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_user_account_reviewed_by` FOREIGN KEY (`account_reviewed_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_user_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_user_education` FOREIGN KEY (`education_level_id`) REFERENCES `education_level` (`education_level_id`),
  CONSTRAINT `fk_user_grade` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_level` (`grade_level_id`),
  CONSTRAINT `fk_user_provisioned_by` FOREIGN KEY (`provisioned_by`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_user_section` FOREIGN KEY (`section_id`) REFERENCES `section` (`section_id`),
  CONSTRAINT `user_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `user_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_legal_acceptance`
--

DROP TABLE IF EXISTS `user_legal_acceptance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_legal_acceptance` (
  `user_legal_acceptance_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `legal_document_version_id` int(11) NOT NULL,
  `accepted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `acceptance_source` enum('Registration','Reconsent') NOT NULL DEFAULT 'Registration',
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_legal_acceptance_id`),
  UNIQUE KEY `uq_user_legal_acceptance` (`user_id`,`legal_document_version_id`),
  KEY `idx_user_legal_acceptance_user` (`user_id`,`accepted_at`),
  KEY `idx_user_legal_acceptance_document` (`legal_document_version_id`,`accepted_at`),
  CONSTRAINT `fk_user_legal_acceptance_document` FOREIGN KEY (`legal_document_version_id`) REFERENCES `legal_document_version` (`legal_document_version_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_user_legal_acceptance_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_optional_consent`
--

DROP TABLE IF EXISTS `user_optional_consent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_optional_consent` (
  `user_optional_consent_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `consent_type` enum('SensitiveSurveyData') NOT NULL,
  `consent_version` varchar(30) NOT NULL,
  `granted` tinyint(1) NOT NULL DEFAULT 0,
  `responded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `withdrawn_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_optional_consent_id`),
  UNIQUE KEY `uq_user_optional_consent` (`user_id`,`consent_type`,`consent_version`),
  KEY `idx_optional_consent_lookup` (`consent_type`,`consent_version`,`granted`),
  CONSTRAINT `fk_user_optional_consent_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_role_history`
--

DROP TABLE IF EXISTS `user_role_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_role_history` (
  `user_role_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `previous_role_id` int(11) NOT NULL,
  `new_role_id` int(11) NOT NULL,
  `reason` varchar(1000) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_role_history_id`),
  KEY `idx_user_role_history_user` (`user_id`,`created_at`),
  KEY `idx_user_role_history_admin` (`changed_by`,`created_at`),
  KEY `fk_user_role_history_previous` (`previous_role_id`),
  KEY `fk_user_role_history_new` (`new_role_id`),
  CONSTRAINT `fk_user_role_history_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_user_role_history_new` FOREIGN KEY (`new_role_id`) REFERENCES `role` (`role_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_user_role_history_previous` FOREIGN KEY (`previous_role_id`) REFERENCES `role` (`role_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_user_role_history_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_status_history`
--

DROP TABLE IF EXISTS `user_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_status_history` (
  `user_status_history_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `previous_status` enum('Pending','Active','Inactive','Rejected') NOT NULL,
  `new_status` enum('Pending','Active','Inactive','Rejected') NOT NULL,
  `reason` varchar(1000) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_status_history_id`),
  KEY `idx_user_status_history_user` (`user_id`,`created_at`),
  KEY `idx_user_status_history_admin` (`changed_by`,`created_at`),
  CONSTRAINT `fk_user_status_history_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_user_status_history_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping events for database 'olshcodb'
--

--
-- Dumping routines for database 'olshcodb'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-02 20:32:07
