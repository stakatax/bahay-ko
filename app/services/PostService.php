<?php
    require_once __DIR__ . '/../models/Event.php';
    require_once __DIR__ . '/../models/Announcement.php';
    require_once __DIR__ . '/../models/Document.php';
    
    class PostService {
    
        private $event;
        private $announcement;
        private $document;
    
        public function __construct() {
            $this->event = new Event();
            $this->announcement = new Announcement();
            $this->document = new Document();
        }
    
        public function getNewsFeed() {
    
            return [
                'events' => $this->event->getRecent(5),
                'announcements' => $this->announcement->getRecent(5),
                'documents' => $this->document->getRecent(5)
            ];
        }

        public function create($data, $files, $user_id) {
        switch ($data['post_type']) {
            case 'announcement':
                $result = $this->announcement->create(
                    $data['title'],
                    $data['content'],
                    $user_id
                );

                $this->document->log(
                    "UPLOAD_ANNOUNCEMENT",
                    "{$user_name} uploaded a document"
                );

                return $result;

            case 'event':
                $result = $this->event->create(
                    $data['title'],
                    $data['event_date'],
                    $user_id
                );

                $this->event->log(
                    "POST_EVENT",
                    "{$user_name} created an event"
                );

                return $result;

            case 'document':
                $result = $this->handleUpload($files, $user_id);

                $this->document->log(
                    "UPLOAD_DOCUMENT",
                    "{$user_name} uploaded a document"
                );

                return $result;
        }
        throw new Exception("Invalid post type");
    }

    private function handleUpload($files, $user_id) {
        if (!isset($files['attachment']) || $files['attachment']['error'] !== 0) {
            throw new Exception("File required");
        }

        $uploadDir = __DIR__ . "/../../Assets/uploads/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . "_" . basename($files["attachment"]["name"]);
        $target = $uploadDir . $fileName;

        $type = pathinfo($fileName, PATHINFO_EXTENSION);

        if (!move_uploaded_file($files["attachment"]["tmp_name"], $target)) {
            throw new Exception("Upload failed");
        }

        return $this->document->create($fileName, $type, $user_id);
    }
    }