<?php
header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(0);

try {
    if (!isset($_FILES['audio_data']) || $_FILES['audio_data']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No audio file uploaded or an error occurred during upload.');
    }

    $uploadDir = __DIR__ . '/../uploads/voice_recordings/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory.');
        }
    }

    // Validate file type and extension
    $allowedMimeTypes = ['audio/webm', 'audio/mp3', 'audio/wav', 'audio/ogg'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($_FILES['audio_data']['tmp_name']);

    if (!in_array($mimeType, $allowedMimeTypes)) {
        throw new Exception('Invalid file type. Only WEBM, MP3, WAV, or OGG are allowed.');
    }

    // Sanitize filename and generate a unique name
    $originalName = pathinfo($_FILES['audio_data']['name'], PATHINFO_FILENAME);
    $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '', $originalName); // Remove special characters
    $extension = pathinfo($_FILES['audio_data']['name'], PATHINFO_EXTENSION);

    // Double-check extension against a safe list
    $allowedExtensions = ['webm', 'mp3', 'wav', 'ogg'];
    if (!in_array(strtolower($extension), $allowedExtensions)) {
        $extension = 'webm'; // Default to a safe extension if something is fishy
    }

    $filename = uniqid($sanitizedName . '_', true) . '.' . $extension;
    $uploadPath = $uploadDir . $filename;

    // Move the uploaded file
    if (move_uploaded_file($_FILES['audio_data']['tmp_name'], $uploadPath)) {
        // Return a path relative to the web root, not the file system root
        $relativePath = 'uploads/voice_recordings/' . $filename;

        echo json_encode([
            'success' => true,
            'filepath' => $relativePath,
            'message' => 'Audio file uploaded successfully'
        ]);
    } else {
        throw new Exception('Failed to move uploaded file. Check permissions.');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
