<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Mail;
use App\Mail\CustomNotificationMail;

try {
    // Test basic email functionality
    Mail::raw('Test email from InvestWise', function($message) {
        $message->to('test@example.com')->subject('Test Email');
    });

    echo "Email sent successfully!\n";

    // Test custom notification mail
    $mail = new CustomNotificationMail('Test Subject', 'Test Message');
    Mail::send($mail);

    echo "Custom notification email sent successfully!\n";

} catch (Exception $e) {
    echo "Error sending email: " . $e->getMessage() . "\n";
}
