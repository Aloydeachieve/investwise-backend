<?php

require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing CORS Fix for Registration Endpoint\n";
echo "=========================================\n\n";

$client = new Client([
    'timeout' => 10,
    'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ]
]);

// Test 1: Test OPTIONS preflight request (simulating browser behavior)
echo "1. Testing OPTIONS Preflight Request:\n";
try {
    $response = $client->request('OPTIONS', 'http://127.0.0.1:8000/api/auth/register', [
        'headers' => [
            'Origin' => 'http://localhost:3000',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type,authorization,x-requested-with',
        ]
    ]);

    echo "✓ OPTIONS request successful\n";
    echo "  Status: " . $response->getStatusCode() . "\n";
    echo "  Access-Control-Allow-Origin: " . ($response->getHeader('Access-Control-Allow-Origin')[0] ?? 'Not set') . "\n";
    echo "  Access-Control-Allow-Methods: " . ($response->getHeader('Access-Control-Allow-Methods')[0] ?? 'Not set') . "\n";
    echo "  Access-Control-Allow-Headers: " . ($response->getHeader('Access-Control-Allow-Headers')[0] ?? 'Not set') . "\n";
    echo "  Access-Control-Allow-Credentials: " . ($response->getHeader('Access-Control-Allow-Credentials')[0] ?? 'Not set') . "\n";

    // Check if CORS headers are properly set
    $allowOrigin = $response->getHeader('Access-Control-Allow-Origin')[0] ?? '';
    $allowCredentials = $response->getHeader('Access-Control-Allow-Credentials')[0] ?? '';

    if ($allowOrigin === 'http://localhost:3000' && $allowCredentials === 'true') {
        echo "✓ CORS headers are correctly configured for credentials\n";
    } else {
        echo "✗ CORS headers may have issues\n";
    }

} catch (RequestException $e) {
    echo "✗ OPTIONS request failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Test actual registration with credentials
echo "2. Testing Registration with Credentials:\n";
try {
    $testEmail = 'cors_test_' . time() . '@example.com';
    $response = $client->request('POST', 'http://127.0.0.1:8000/api/auth/register', [
        'headers' => [
            'Origin' => 'http://localhost:3000',
            'Referer' => 'http://localhost:3000/',
        ],
        'json' => [
            'name' => 'CORS Test User',
            'email' => $testEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]
    ]);

    echo "✓ Registration request successful\n";
    echo "  Status: " . $response->getStatusCode() . "\n";
    echo "  Access-Control-Allow-Origin: " . ($response->getHeader('Access-Control-Allow-Origin')[0] ?? 'Not set') . "\n";
    echo "  Access-Control-Allow-Credentials: " . ($response->getHeader('Access-Control-Allow-Credentials')[0] ?? 'Not set') . "\n";

    $data = json_decode($response->getBody(), true);

    if (isset($data['success']) && $data['success'] === true) {
        echo "✓ Registration successful with proper response format\n";
        echo "  Message: " . $data['message'] . "\n";
    } else {
        echo "✗ Registration response format issue\n";
        echo "  Response: " . json_encode($data) . "\n";
    }

} catch (RequestException $e) {
    echo "✗ Registration request failed: " . $e->getMessage() . "\n";
    if ($e->hasResponse()) {
        $response = $e->getResponse();
        echo "  Status: " . $response->getStatusCode() . "\n";
        echo "  Response: " . $response->getBody() . "\n";
    }
}

echo "\n";
echo "CORS Fix Test completed!\n";
echo "=======================\n";
echo "If both tests passed, the CORS issue should be resolved.\n";
echo "Your Next.js frontend should now be able to register users successfully.\n";
