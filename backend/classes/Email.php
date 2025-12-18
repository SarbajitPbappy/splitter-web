<?php
/**
 * Email Class
 * Handles sending emails for invitations and notifications
 */

class Email {
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        // Get email settings from config or environment
        $this->fromEmail = getenv('SMTP_FROM_EMAIL') ?: 'noreply@splitter.local';
        $this->fromName = getenv('SMTP_FROM_NAME') ?: 'Splitter App';
    }
    
    /**
     * Send invitation email
     */
    public function sendInvitation($toEmail, $groupName, $inviterName, $token, $isRegistered = false) {
        $baseUrl = $this->getBaseUrl();
        
        if ($isRegistered) {
            // User is already registered - send notification to join
            $subject = "Invitation to join group: {$groupName}";
            $acceptUrl = $baseUrl . "/frontend/groups/invitation.html?token=" . urlencode($token) . "&action=accept";
            $rejectUrl = $baseUrl . "/frontend/groups/invitation.html?token=" . urlencode($token) . "&action=reject";
            
            $message = $this->getRegisteredUserEmailTemplate($groupName, $inviterName, $acceptUrl, $rejectUrl);
        } else {
            // User is not registered - send invitation to register first
            $subject = "Invitation to join Splitter and group: {$groupName}";
            $registerUrl = $baseUrl . "/frontend/register.html?invite_token=" . urlencode($token);
            
            $message = $this->getUnregisteredUserEmailTemplate($groupName, $inviterName, $registerUrl);
        }
        
        return $this->sendEmail($toEmail, $subject, $message);
    }
    
    /**
     * Get base URL for the application
     */
    private function getBaseUrl() {
        // Try to get from environment or config
        $baseUrl = getenv('APP_BASE_URL');
        if ($baseUrl) {
            return $baseUrl;
        }
        
        // Fallback to detecting from server
        if (php_sapi_name() !== 'cli' && isset($_SERVER['HTTP_HOST'])) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            return $protocol . '://' . $host;
        }
        
        // Default for CLI/development
        return 'http://localhost:8000';
    }
    
    /**
     * Email template for registered users
     */
    private function getRegisteredUserEmailTemplate($groupName, $inviterName, $acceptUrl, $rejectUrl) {
        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
        .button { display: inline-block; padding: 12px 30px; margin: 10px 5px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .button-accept { background-color: #4CAF50; color: white; }
        .button-reject { background-color: #f44336; color: white; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Group Invitation</h1>
        </div>
        <div class='content'>
            <p>Hello,</p>
            <p><strong>{$inviterName}</strong> has invited you to join the group <strong>{$groupName}</strong> on Splitter.</p>
            <p>Would you like to accept this invitation?</p>
            <p style='text-align: center; margin-top: 30px;'>
                <a href='{$acceptUrl}' class='button button-accept'>Accept Invitation</a>
                <a href='{$rejectUrl}' class='button button-reject'>Reject</a>
            </p>
            <p style='margin-top: 30px; font-size: 14px; color: #666;'>
                If the buttons don't work, you can copy and paste these links into your browser:<br>
                Accept: {$acceptUrl}<br>
                Reject: {$rejectUrl}
            </p>
        </div>
        <div class='footer'>
            <p>This invitation will expire in 7 days.</p>
            <p>© " . date('Y') . " Splitter. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
        ";
    }
    
    /**
     * Email template for unregistered users
     */
    private function getUnregisteredUserEmailTemplate($groupName, $inviterName, $registerUrl) {
        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
        .button { display: inline-block; padding: 12px 30px; margin: 10px 5px; text-decoration: none; background-color: #4CAF50; color: white; border-radius: 5px; font-weight: bold; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Welcome to Splitter!</h1>
        </div>
        <div class='content'>
            <p>Hello,</p>
            <p><strong>{$inviterName}</strong> has invited you to join the group <strong>{$groupName}</strong> on Splitter.</p>
            <p>To accept this invitation, please create an account first:</p>
            <p style='text-align: center; margin-top: 30px;'>
                <a href='{$registerUrl}' class='button'>Create Account & Join Group</a>
            </p>
            <p style='margin-top: 30px; font-size: 14px; color: #666;'>
                If the button doesn't work, you can copy and paste this link into your browser:<br>
                {$registerUrl}
            </p>
        </div>
        <div class='footer'>
            <p>This invitation will expire in 7 days.</p>
            <p>© " . date('Y') . " Splitter. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
        ";
    }
    
    /**
     * Send email
     */
    private function sendEmail($to, $subject, $message) {
        $headers = [
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $headersString = implode("\r\n", $headers);
        
        // For development, log instead of actually sending
        if (getenv('ENVIRONMENT') === 'development' || php_sapi_name() === 'cli') {
            error_log("Email would be sent to: {$to}");
            error_log("Subject: {$subject}");
            return true; // Return true for development
        }
        
        return mail($to, $subject, $message, $headersString);
    }
}

