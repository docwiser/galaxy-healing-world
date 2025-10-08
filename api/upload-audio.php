<?php
header('Content-Type: application/json');

try {
    if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No audio file uploaded or upload error occurred');
    }

    $uploadDir = '../uploads/voice_recordings/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $extension = 'webm'; // Default for browser recordings
    $filename = uniqid('recording_') . '_' . time() . '.' . $extension;
    $uploadPath = $uploadDir . $filename;

    // Move uploaded file
    if (move_uploaded_file($_FILES['audio']['tmp_name'], $uploadPath)) {
        $relativePath = '/uploads/voice_recordings/' . $filename;

        echo json_encode([
            'success' => true,
            'path' => $relativePath,
            'message' => 'Audio file uploaded successfully'
        ]);
    } else {
        throw new Exception('Failed to save audio file');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
