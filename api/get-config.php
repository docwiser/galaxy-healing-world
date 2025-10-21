<?php
header('Content-Type: application/json');

$configFile = __DIR__ . '/../includes/rzp.json';

if (!file_exists($configFile)) {
    echo json_encode(['success' => false, 'message' => 'Razorpay configuration file is missing.']);
    exit;
}

$razorpay_config = json_decode(file_get_contents($configFile), true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($razorpay_config['key_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid Razorpay configuration.']);
    exit;
}

echo json_encode(['success' => true, 'key_id' => $razorpay_config['key_id']]);
