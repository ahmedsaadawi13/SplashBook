<?php
// FILE: /app/controllers/UploadController.php

class UploadController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }

    public function image()
    {
        $this->requireCsrf();

        if (!$this->request->hasFile('file')) {
            $this->json(['success' => false, 'message' => 'No file uploaded'], 400);
        }

        $config = require __DIR__ . '/../../config/app.php';
        $uploader = new FileUpload($config['allowed_image_types'], $config['max_upload_size']);

        $result = $uploader->upload($this->request->file('file'), 'images');

        $this->json($result);
    }

    public function document()
    {
        $this->requireCsrf();

        if (!$this->request->hasFile('file')) {
            $this->json(['success' => false, 'message' => 'No file uploaded'], 400);
        }

        $config = require __DIR__ . '/../../config/app.php';
        $uploader = new FileUpload($config['allowed_document_types'], $config['max_upload_size']);

        $result = $uploader->upload($this->request->file('file'), 'documents');

        $this->json($result);
    }
}
