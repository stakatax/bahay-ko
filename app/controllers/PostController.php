<?php

    require_once 'BaseController.php';
    require_once __DIR__ . '/../services/PostService.php';

    class PostController extends BaseController {

    private $service;

    public function __construct() {
        $this->service = new PostService();
    }

    public function news() {

        return $this->service->getNewsFeed();
        
    }

    public function store() {
        try {
            $this->service->create(
                $_POST,
                $_FILES,
                $_SESSION['user_id']
            );
            $this->redirect('index.php?page=postings&success=1');
        } catch (Exception $e) {
            $this->redirect( 'index.php?page=postings&error=' . urlencode($e->getMessage()) );
        }
    }
}