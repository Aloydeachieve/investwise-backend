<?php

require_once 'vendor/autoload.php';

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Client\Response;

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test CORS headers and standardized responses
$http = new HttpClient();

echo "Testing API Response Standardization and CORS Configuration\n";
echo "==========================================================\n\n";

// Test 1: Check CORS headers
echo "1. Testing CORS Configuration:\n";
try {
    $response = $http->withHeaders([
        'Origin' => 'http://localhost:3000',
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'Content-Type,Authorization'
    ])->options('http://localhost:8000/api/auth/login');

    echo "✓ CORS preflight request successful\n";
    echo "  Status: " . $response->status() . "\n";
    echo "  Access-Control-Allow-Origin: " . ($response->header('Access-Control-Allow-Origin') ?: 'Not set') . "\n";
    echo "  Access-Control-Allow-Methods: " . ($response->header('Access-Control-Allow-Methods') ?: 'Not set') . "\n";
    echo "  Access-Control-Allow-Headers: " . ($response->header('Access-Control-Allow-Headers') ?: 'Not set') . "\n";
    echo "  Access-Control-Allow-Credentials: " . ($response->header('Access-Control-Allow-Credentials') ?: 'Not set') . "\n";
} catch (Exception $e) {
    echo "✗ CORS test failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Test standardized error response
echo "2. Testing Standardized Error Response:\n";
try {
    $response = $http->post('http://localhost:8000/api/auth/login', [
        'email' => 'invalid@example.com',
        'password' => 'wrongpassword'
    ]);

    $data = $response->json();

    if (isset($data['success']) && $data['success'] === false) {
        echo "✓ Standardized error response format\n";
        echo "  Success: " . ($data['success'] ? 'true' : 'false') . "\n";
        echo "  Message: " . $data['message'] . "\n";
        echo "  Status Code: " . $response->status() . "\n";
    } else {
        echo "✗ Non-standardized error response\n";
        echo "  Response: " . json_encode($data) . "\n";
    }
} catch (Exception $e) {
    echo "✗ Error response test failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Test standardized success response
echo "3. Testing Standardized Success Response:\n";
try {
    // First register a test user
    $testEmail = 'test_' . time() . '@example.com';
    $registerResponse = $http->post('http://localhost:8000/api/auth/register', [
        'name' => 'Test User',
        'email' => $testEmail,
        'password' => 'password123',
        'password_confirmation' => 'password123'
    ]);

    if ($registerResponse->successful()) {
        $registerData = $registerResponse->json();

        if (isset($registerData['success']) && $registerData['success'] === true) {
            echo "✓ Standardized success response format\n";
            echo "  Success: " . ($registerData['success'] ? 'true' : 'false') . "\n";
            echo "  Message: " . $registerData['message'] . "\n";
            echo "  Status Code: " . $registerResponse->status() . "\n";
            echo "  Has data: " . (isset($registerData['data']) ? 'Yes' : 'No') . "\n";
        } else {
            echo "✗ Non-standardized success response\n";
            echo "  Response: " . json_encode($registerData) . "\n";
        }
    } else {
        echo "✗ Registration failed: " . $registerResponse->status() . "\n";
    }
} catch (Exception $e) {
    echo "✗ Success response test failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Test exception handler
echo "4. Testing Exception Handler:\n";
try {
    $response = $http->get('http://localhost:8000/api/nonexistent-endpoint');

    $data = $response->json();

    if (isset($data['success']) && $data['success'] === false) {
        echo "✓ Exception handler working correctly\n";
        echo "  Success: " . ($data['success'] ? 'true' : 'false') . "\n";
        echo "  Message: " . $data['message'] . "\n";
        echo "  Status Code: " . $response->status() . "\n";
    } else {
        echo "✗ Exception handler not working\n";
        echo "  Response: " . json_encode($data) . "\n";
    }
} catch (Exception $e) {
    echo "✗ Exception handler test failed: " . $e->getMessage() . "\n";
}

echo "\n";
echo "Test completed!\n";
