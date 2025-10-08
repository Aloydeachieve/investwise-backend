<?php

require_once 'vendor/autoload.php';

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Helpers\ApiResponse;
use App\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

echo "Testing API Implementation Components\n";
echo "===================================\n\n";

// Test 1: Test ApiResponse helper
echo "1. Testing ApiResponse Helper:\n";
try {
    // Test success response
    $successResponse = ApiResponse::success(['user' => ['id' => 1, 'name' => 'Test']], 'Success message', 200);
    $successData = json_decode($successResponse->getContent(), true);

    if (isset($successData['success']) && $successData['success'] === true) {
        echo "✓ ApiResponse::success() working correctly\n";
        echo "  Success: " . ($successData['success'] ? 'true' : 'false') . "\n";
        echo "  Message: " . $successData['message'] . "\n";
        echo "  Has data: " . (isset($successData['data']) ? 'Yes' : 'No') . "\n";
    } else {
        echo "✗ ApiResponse::success() not working\n";
    }

    // Test error response
    $errorResponse = ApiResponse::error('Error message', 400, ['field' => 'error']);
    $errorData = json_decode($errorResponse->getContent(), true);

    if (isset($errorData['success']) && $errorData['success'] === false) {
        echo "✓ ApiResponse::error() working correctly\n";
        echo "  Success: " . ($errorData['success'] ? 'true' : 'false') . "\n";
        echo "  Message: " . $errorData['message'] . "\n";
        echo "  Has errors: " . (isset($errorData['errors']) ? 'Yes' : 'No') . "\n";
    } else {
        echo "✗ ApiResponse::error() not working\n";
    }

    // Test validation error response
    $validationResponse = ApiResponse::validationError(['email' => 'Invalid email']);
    $validationData = json_decode($validationResponse->getContent(), true);

    if (isset($validationData['success']) && $validationData['success'] === false && $validationResponse->getStatusCode() === 422) {
        echo "✓ ApiResponse::validationError() working correctly\n";
        echo "  Status Code: " . $validationResponse->getStatusCode() . "\n";
        echo "  Has validation errors: " . (isset($validationData['errors']) ? 'Yes' : 'No') . "\n";
    } else {
        echo "✗ ApiResponse::validationError() not working\n";
    }

} catch (Exception $e) {
    echo "✗ ApiResponse test failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Test Exception Handler
echo "2. Testing Exception Handler:\n";
try {
    $handler = new Handler(app());

    // Test validation exception
    $validationException = new ValidationException(
        validator([], ['email' => 'required']),
        response()
    );

    $request = Request::create('/api/test', 'GET');
    $request->headers->set('Accept', 'application/json');

    $response = $handler->render($request, $validationException);
    $responseData = json_decode($response->getContent(), true);

    if (isset($responseData['success']) && $responseData['success'] === false && $response->getStatusCode() === 422) {
        echo "✓ ValidationException handled correctly\n";
