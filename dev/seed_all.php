<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require 'reset_database.php';

require 'seed_users.php';
require 'seed_announcements.php';
require 'seed_events.php';
require 'seed_documents.php';
require 'seed_surveys.php';
require 'seed_engagement.php';
require 'seed_notifications.php';
require 'AcademicStructureSeeder.php';

echo "<h2>Development Database Ready!</h2>";
