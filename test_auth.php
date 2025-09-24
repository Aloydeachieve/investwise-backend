<?php

// Test Authentication API Endpoints
$baseUrl = 'http://localhost:8000/api';

echo "=== Testing Authentication & User Management APIs ===\n\n";

// Test 1: Register new user
echo "1. Testing User Registration:\n";
$data = json_encode([
    'name' => 'Jane Smith',
    'email' => 'jane@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $data
    ]
]);

$result = file_get_contents($baseUrl . '/auth/register', false, $context);
echo $result . "\n\n";

// Test 2: Login
echo "2. Testing User Login:\n";
$data = json_encode([
    'email' => 'user@investwise.com',
    'password' => 'password123'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $data
    ]
]);

$result = file_get_contents($baseUrl . '/auth/login', false, $context);
$response = json_decode($result, true);

if (isset($response['token'])) {
    $token = $response['token'];
    echo "Login successful! Token: " . substr($token, 0, 50) . "...\n\n";

    // Test 3: Get Profile (with token)
    echo "3. Testing Get Profile:\n";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Authorization: Bearer ' . $token
        ]
    ]);

    $result = file_get_contents($baseUrl . '/auth/profile', false, $context);
    echo $result . "\n\n";

    // Test 4: Admin Login
    echo "4. Testing Admin Login:\n";
    $data = json_encode([
        'email' => 'admin@investwise.com',
        'password' => 'password123'
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $data
        ]
    ]);

    $result = file_get_contents($baseUrl . '/auth/login', false, $context);
    $adminResponse = json_decode($result, true);

    if (isset($adminResponse['token'])) {
        $adminToken = $adminResponse['token'];
        echo "Admin login successful! Token: " . substr($adminToken, 0, 50) . "...\n\n";
    } else {
        echo "Admin login failed: " . $result . "\n\n";
    }

} else {
    echo "Login failed: " . $result . "\n\n";
}

// Test 5: Forgot Password
echo "5. Testing Forgot Password:\n";
$data = json_encode([
    'email' => 'user@investwise.com'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $data
    ]
]);

$result = file_get_contents($baseUrl . '/auth/forgot-password', false, $context);
echo $result . "\n\n";

echo "=== API Testing Complete ===\n";
