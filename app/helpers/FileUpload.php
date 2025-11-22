<?php
// FILE: /app/helpers/FileUpload.php

/**
 * FileUpload - Handles secure file uploads
 *
 * Validates file type and size, generates unique filenames,
 * and prevents directory traversal attacks.
 */
class FileUpload
{
    private $allowedTypes;
    private $maxSize;
    private $uploadPath;

    /**
     * Constructor
     *
     * @param array $allowedTypes Allowed file extensions
     * @param int $maxSize Maximum file size in bytes
     * @param string $uploadPath Upload directory path
     */
    public function __construct($allowedTypes = [], $maxSize = 5242880, $uploadPath = null)
    {
        $this->allowedTypes = $allowedTypes;
        $this->maxSize = $maxSize;
        $this->uploadPath = $uploadPath ?: __DIR__ . '/../../storage/uploads/';
    }

    /**
     * Upload a file
     *
     * @param array $file File from $_FILES
     * @param string $subdirectory Optional subdirectory
     * @return array Result with success status and message or filename
     */
    public function upload($file, $subdirectory = '')
    {
        // Check if file was uploaded
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'message' => 'Invalid file upload'];
        }

        // Check for upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'message' => 'File size exceeds limit'];
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'message' => 'No file uploaded'];
            default:
                return ['success' => false, 'message' => 'Unknown upload error'];
        }

        // Validate file size
        if ($file['size'] > $this->maxSize) {
            $maxSizeMb = round($this->maxSize / 1024 / 1024, 2);
            return ['success' => false, 'message' => "File size must not exceed {$maxSizeMb}MB"];
        }

        // Get file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Validate file type
        if (!empty($this->allowedTypes) && !in_array($extension, $this->allowedTypes)) {
            return ['success' => false, 'message' => 'File type not allowed. Allowed: ' . implode(', ', $this->allowedTypes)];
        }

        // Additional security: check MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        // Map of allowed MIME types
        $allowedMimes = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ];

        if (isset($allowedMimes[$extension]) && !in_array($mimeType, $allowedMimes[$extension])) {
            return ['success' => false, 'message' => 'File content does not match extension'];
        }

        // Generate unique filename
        $filename = $this->generateFilename($extension);

        // Prepare upload directory
        $targetDir = $this->uploadPath . ltrim($subdirectory, '/');
        if ($subdirectory) {
            $targetDir = rtrim($targetDir, '/') . '/';
        }

        // Create directory if not exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'message' => 'Failed to move uploaded file'];
        }

        // Return success with relative path
        $relativePath = ltrim($subdirectory, '/');
        $relativePath = $relativePath ? $relativePath . '/' : '';

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $relativePath . $filename
        ];
    }

    /**
     * Generate a unique filename
     *
     * @param string $extension File extension
     * @return string Unique filename
     */
    private function generateFilename($extension)
    {
        return uniqid('upload_', true) . '_' . time() . '.' . $extension;
    }

    /**
     * Delete a file
     *
     * @param string $filename Filename to delete
     * @param string $subdirectory Optional subdirectory
     * @return bool Success status
     */
    public function delete($filename, $subdirectory = '')
    {
        // Prevent directory traversal
        $filename = basename($filename);

        $targetDir = $this->uploadPath . ltrim($subdirectory, '/');
        if ($subdirectory) {
            $targetDir = rtrim($targetDir, '/') . '/';
        }

        $filePath = $targetDir . $filename;

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * Get upload path for a file
     *
     * @param string $filename Filename
     * @param string $subdirectory Optional subdirectory
     * @return string Full file path
     */
    public function getPath($filename, $subdirectory = '')
    {
        $filename = basename($filename);

        $targetDir = $this->uploadPath . ltrim($subdirectory, '/');
        if ($subdirectory) {
            $targetDir = rtrim($targetDir, '/') . '/';
        }

        return $targetDir . $filename;
    }
}
