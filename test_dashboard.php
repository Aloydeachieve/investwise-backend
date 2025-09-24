<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "Testing Dashboard Endpoints...\n";

// Test 1: Check if DashboardController exists
if (class_exists('App\Http\Controllers\DashboardController')) {
    echo "✅ DashboardController exists\n";
} else {
    echo "❌ DashboardController not found\n";
}

// Test 2: Check if routes are registered
$routes = [
    'dashboard.overview',
    'dashboard.recent-transactions',
    'dashboard.investments-summary',
    'dashboard.referrals-summary',
    'dashboard.activity-log'
];

foreach ($routes as $route) {
    $routeInfo = \Illuminate\Support\Facades\Route::getRoutes()->getByName($route);
    if ($routeInfo) {
        echo "✅ Route {$route} is registered\n";
    } else {
        echo "❌ Route {$route} not found\n";
    }
}

// Test 3: Check if models exist
$models = [
    'App\Models\Investment',
    'App\Models\Transaction',
    'App\Models\Referral',
    'App\Models\Payout',
    'App\Models\AuditLog'
];

foreach ($models as $model) {
    if (class_exists($model)) {
        echo "✅ Model {$model} exists\n";
    } else {
        echo "❌ Model {$model} not found\n";
    }
}

echo "\nDashboard implementation test completed!\n";
