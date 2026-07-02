<?php
/**
 * Contact Form Backend - PHP + SendGrid
 * Handles contact form submissions and sends emails via SendGrid API
 * 
 * Place this file on your PHP-enabled hosting
 */

// Enable error logging (disable in production)
error_reporting(E_ALL);
ini_set('log_errors', true);
ini_set('error_log', __DIR__ . '/contact-form.log');

// CORS Headers - Allow requests from your portfolio domain
header('Access-Control-Allow-Origin: *'); // Change to your GitHub Pages URL in production
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit();
}

// Load configuration from .env file or environment variables
function getConfig($key, $default = '') {
    // Try environment variable first
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }
    
    // Try .env file
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue; // Skip comments
            if (strpos($line, '=') !== false) {
                list($envKey, $envValue) = explode('=', $line, 2);
                if (trim($envKey) === $key) {
                    return trim($envValue);
                }
            }
        }
    }
    
    return $default;
}

// Configuration
$sendgridApiKey = getConfig('SENDGRID_API_KEY');
$fromEmail = getConfig('FROM_EMAIL', 'noreply@yourdomain.com');
$toEmail = getConfig('TO_EMAIL', 'your-email@example.com');

// Validate configuration
if (empty($sendgridApiKey)) {
    error_log('ERROR: SENDGRID_API_KEY not configured');
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error. Please contact the administrator.']);
    exit();
}

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// If not JSON, try to parse as form data
if ($data === null && !empty($_POST)) {
    $data = $_POST;
}

// Validate required fields
$requiredFields = ['name', 'email', 'subject'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing required fields: ' . implode(', ', $missingFields)
    ]);
    exit();
}

// Sanitize input
$name = htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8');
$email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
$company = !empty($data['company']) ? htmlspecialchars(trim($data['company']), ENT_QUOTES, 'UTF-8') : '';
$phone = !empty($data['phone']) ? htmlspecialchars(trim($data['phone']), ENT_QUOTES, 'UTF-8') : '';
$subject = htmlspecialchars(trim($data['subject']), ENT_QUOTES, 'UTF-8');
$message = !empty($data['message']) ? htmlspecialchars(trim($data['message']), ENT_QUOTES, 'UTF-8') : '';

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit();
}

// Build email content
$emailSubject = 'Contact Form: ' . $subject;

// HTML email template
$htmlContent = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table tr { border-bottom: 1px solid #eeeeee; }
        .info-table tr:last-child { border-bottom: none; }
        .info-table td { padding: 12px 0; }
        .info-table td:first-child { font-weight: bold; color: #667eea; width: 120px; }
        .message-box { background: #f9f9f9; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; }
        .footer { background: #f4f4f4; text-align: center; padding: 20px; color: #999999; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📬 New Contact Form Submission</h1>
        </div>
        <div class="content">
            <table class="info-table">
                <tr>
                    <td><strong>Name:</strong></td>
                    <td>' . $name . '</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>' . $email . '</td>
                </tr>
                ' . (!empty($company) ? '<tr><td><strong>Company:</strong></td><td>' . $company . '</td></tr>' : '') . '
                ' . (!empty($phone) ? '<tr><td><strong>Phone:</strong></td><td>' . $phone . '</td></tr>' : '') . '
                <tr>
                    <td><strong>Subject:</strong></td>
                    <td>' . $subject . '</td>
                </tr>
            </table>
            <div class="message-box">
                <strong>Message:</strong><br><br>
                ' . nl2br($message) . '
            </div>
        </div>
        <div class="footer">
            <p>This message was sent from your portfolio contact form.</p>
            <p>Reply directly to this email to respond to ' . $email . '</p>
        </div>
    </div>
</body>
</html>
';

// Plain text version
$textContent = "
New Contact Form Submission
===========================

Name: $name
Email: $email
" . (!empty($company) ? "Company: $company\n" : '') . "
" . (!empty($phone) ? "Phone: $phone\n" : '') . "
Subject: $subject

Message:
---------
$message

---
This message was sent from your portfolio contact form.
Reply to this email to respond to $email
";

// SendGrid API endpoint
$sendgridUrl = 'https://api.sendgrid.com/v3/mail/send';

// Build the request payload
$payload = [
    'personalizations' => [
        [
            'to' => [
                ['email' => $toEmail]
            ],
            'subject' => $emailSubject,
            'headers' => [
                'Reply-To' => $email
            ]
        ]
    ],
    'from' => [
        'email' => $fromEmail,
        'name' => 'Portfolio Contact Form'
    ],
    'content' => [
        [
            'type' => 'text/plain',
            'value' => $textContent
        ],
        [
            'type' => 'text/html',
            'value' => $htmlContent
        ]
    ]
];

// Initialize cURL
$ch = curl_init($sendgridUrl);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $sendgridApiKey,
    'Content-Type: application/json'
]);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

// Check response
if ($httpCode === 202) {
    // Success!
    error_log('SUCCESS: Email sent to ' . $toEmail . ' from ' . $email);
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your message has been sent successfully.'
    ]);
} else {
    // Failed
    error_log('ERROR: SendGrid API returned HTTP ' . $httpCode);
    error_log('Response: ' . $response);
    if ($curlError) {
        error_log('cURL Error: ' . $curlError);
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to send email. Please try again later or contact directly.'
    ]);
}
?>
