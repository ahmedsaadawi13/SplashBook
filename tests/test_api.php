<?php
// FILE: /tests/test_api.php

/**
 * API Integration Tests
 *
 * Run: php tests/test_api.php
 */

// Configuration
$baseUrl = 'http://localhost'; // Change to your domain
$apiKey = 'bb_live_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6'; // Bella Beauty API key

echo "SplashBook API Integration Tests\n";
echo str_repeat('=', 50) . "\n\n";

// Test 1: Availability API
echo "Test 1: GET /api/availability\n";
echo "Request: Get available slots for service 1 on future date\n";

$futureDate = date('Y-m-d', strtotime('+7 days'));
$url = "$baseUrl/api/availability?service_id=1&date=$futureDate";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-API-KEY: $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo "Response Body:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n\n";

if ($httpCode === 200) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
}

echo str_repeat('-', 50) . "\n\n";

// Test 2: Create Booking API
echo "Test 2: POST /api/booking\n";
echo "Request: Create a new booking\n";

$bookingData = [
    'service_id' => 1,
    'staff_id' => 1,
    'date' => $futureDate,
    'start_time' => '14:00:00',
    'client_name' => 'API Test Client',
    'client_email' => 'apitest@example.com',
    'client_phone' => '+1-555-0199'
];

$ch = curl_init("$baseUrl/api/booking");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($bookingData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-API-KEY: $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
echo "Response Body:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n\n";

if ($httpCode === 201 || $httpCode === 200) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
}

echo str_repeat('-', 50) . "\n\n";

// Test 3: Invalid API Key
echo "Test 3: Authentication Test (Invalid API Key)\n";

$ch = curl_init("$baseUrl/api/availability?service_id=1&date=$futureDate");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-API-KEY: invalid_key"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";

if ($httpCode === 401) {
    echo "✓ PASS (Correctly rejected invalid key)\n";
} else {
    echo "✗ FAIL (Should return 401)\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "API Tests Complete\n";
