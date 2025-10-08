<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Mail\CustomNotificationMail;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Test email route
Route::get('/test-email', function () {
    try {
        // Test basic email functionality
        Mail::raw('Test email from InvestWise', function($message) {
            $message->to('test@example.com')->subject('Test Email');
        });

        // Test custom notification mail
        $mail = new CustomNotificationMail('Test Subject', 'Test Message');
        Mail::send($mail);

        return response()->json(['status' => 'success', 'message' => 'Emails sent successfully']);

    } catch (Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
    }
});
