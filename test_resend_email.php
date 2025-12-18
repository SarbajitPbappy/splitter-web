<?php
/**
 * Test Resend Email Configuration
 * 
 * Usage:
 * 1. Set your RESEND_API_KEY in environment or edit below
 * 2. Run: php test_resend_email.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Set environment variables for testing
// Replace with your actual values
putenv('EMAIL_SERVICE=resend');
putenv('RESEND_API_KEY=re_your_api_key_here'); // Replace with your actual key
putenv('SMTP_FROM_EMAIL=onboarding@resend.dev'); // Use Resend test domain or your verified domain
putenv('SMTP_FROM_NAME=Splitter App');
putenv('APP_BASE_URL=https://yourdomain.com');
putenv('ENVIRONMENT=production'); // Change to 'development' to log instead of send

// Load Email class
require_once __DIR__ . '/backend/classes/Email.php';

echo "Testing Resend Email Configuration...\n\n";

// Check if API key is set
$apiKey = getenv('RESEND_API_KEY');
if (empty($apiKey) || $apiKey === 're_your_api_key_here') {
    echo "❌ ERROR: RESEND_API_KEY not configured!\n";
    echo "Please set your Resend API key:\n";
    echo "  export RESEND_API_KEY=re_your_actual_key\n";
    echo "  OR edit this file and replace 're_your_api_key_here'\n\n";
    exit(1);
}

echo "✅ API Key found: " . substr($apiKey, 0, 10) . "...\n";
echo "✅ From Email: " . getenv('SMTP_FROM_EMAIL') . "\n";
echo "✅ From Name: " . getenv('SMTP_FROM_NAME') . "\n\n";

// Test sending email
$testEmail = 'test@example.com'; // Change to your email for testing
echo "Attempting to send test email to: {$testEmail}\n\n";

try {
    $email = new Email();
    $result = $email->sendInvitation(
        $testEmail,
        'Test Group',
        'Test User',
        'test_token_123456',
        false // false = unregistered user (registration email)
    );
    
    if ($result) {
        echo "✅ SUCCESS! Email sent successfully!\n";
        echo "Check your inbox (and spam folder) at: {$testEmail}\n";
    } else {
        echo "❌ FAILED! Email sending returned false.\n";
        echo "Check PHP error logs for details.\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n---\n";
echo "For production setup, see: RESEND_SETUP_GUIDE.md\n";

