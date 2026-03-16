#!/usr/bin/env php
<?php
/**
 * Simple SMS Sender using cURL only
 * Based on external_sms_sender.php but simplified
 * 
 * Usage: php simple_sms_sender.php --mobile=<number> --message=<text> [--api-key=<key>] [--api-url=<url>]
 * 
 * Example: php simple_sms_sender.php --mobile=639176738989 --message="Hello World"
 */

// Default configuration
//$API_KEY = getenv('SMS_API_KEY') ?: 'your_api_key_here';
$API_KEY =  'sign-up-and-create-api-key';
//$API_BASE_URL = 'http://127.0.0.1:8080';
$API_BASE_URL = 'https://www.etherney.org';

$API_SEND_URL = $API_BASE_URL . '/api/send-sms.php';

// Parse command line arguments
function parseArgs($argv) {
    $options = [
        'mobile' => null,
        'message' => null,
        'api_key' => null,
        'api_url' => null,
        'help' => false
    ];
    
    foreach ($argv as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
        } elseif (strpos($arg, '--mobile=') === 0) {
            $options['mobile'] = substr($arg, 9);
        } elseif (strpos($arg, '--message=') === 0) {
            $options['message'] = substr($arg, 10);
        } elseif (strpos($arg, '--api-key=') === 0) {
            $options['api_key'] = substr($arg, 10);
        } elseif (strpos($arg, '--api-url=') === 0) {
            $options['api_url'] = substr($arg, 10);
        }
    }
    
    return $options;
}

// Display help message
function displayHelp() {
    echo "Simple SMS Sender using cURL\n";
    echo "=============================\n\n";
    echo "Usage: php simple_sms_sender.php [options]\n\n";
    echo "Options:\n";
    echo "  --mobile=<number>      Mobile number (without +)\n";
    echo "  --message=<text>       Message text to send\n";
    echo "  --api-key=<key>        API key (optional, uses default if not provided)\n";
    echo "  --api-url=<url>        API base URL (optional, default: http://127.0.0.1:8080)\n";
    echo "  --help                 Show this help message\n\n";
    echo "Examples:\n";
    echo "  php simple_sms_sender.php --mobile=639176738989 --message=\"Hello\"\n";
    echo "  php simple_sms_sender.php --mobile=639176738989 --message=\"Test\" --api-key=your_key\n";
    echo "  php simple_sms_sender.php --mobile=639176738989 --message=\"Test\" --api-url=http://your-server.com\n";
}

// Send SMS using cURL
function sendSms($mobileNo, $message, $apiKey, $apiUrl) {
    // Validate inputs
    if (empty(trim($message))) {
        return ['success' => false, 'error' => 'Message cannot be empty'];
    }
    
    if (!preg_match('/^[0-9]{10,15}$/', $mobileNo)) {
        return ['success' => false, 'error' => 'Invalid mobile number format. Use 10-15 digits without +'];
    }
    
    // Prepare data
    $data = [
        'api_key' => $apiKey,
        'mobile_no' => $mobileNo,
        'message' => $message
    ];
    
    // Initialize cURL
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    // Handle errors
    if ($error) {
        return ['success' => false, 'error' => 'cURL error: ' . $error];
    }
    
    // Parse response
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Invalid JSON response: ' . json_last_error_msg()];
    }
    
    return $result;
}

// Main execution
function main($argv) {
    // Parse arguments
    $options = parseArgs($argv);
    
    // Show help if requested
    if ($options['help'] || count($argv) === 1) {
        displayHelp();
        return;
    }
    
    // Check required arguments
    if (!$options['mobile'] || !$options['message']) {
        echo "Error: Both --mobile and --message are required.\n\n";
        displayHelp();
        return;
    }
    
    // Set API key and URL
    $apiKey = $options['api_key'] ?: $GLOBALS['API_KEY'];
    $apiUrl = $options['api_url'] ? $options['api_url'] . '/api/send-sms.php' : $GLOBALS['API_SEND_URL'];
    
    // Display info
    echo "Simple SMS Sender\n";
    echo "================\n\n";
    echo "Mobile: {$options['mobile']}\n";
    echo "Message: " . substr($options['message'], 0, 50) . (strlen($options['message']) > 50 ? '...' : '') . "\n";
    echo "Message length: " . strlen($options['message']) . " characters\n";
    echo "API Key: " . substr($apiKey, 0, 8) . "...\n";
    echo "API URL: $apiUrl\n\n";
    
    // Send SMS
    echo "Sending SMS...\n";
    $result = sendSms($options['mobile'], $options['message'], $apiKey, $apiUrl);
    
    // Display result
    if ($result['success']) {
        echo "✓ SMS sent successfully!\n";
        echo "  SMS ID: {$result['data']['sms_id']}\n";
        echo "  Status: {$result['data']['status']}\n";
        echo "  Provider: {$result['data']['provider']}\n";
        echo "  To: {$result['data']['to']}\n";

        if (isset($result['data']['balance_before'], $result['data']['balance_after'])) {
            echo "  Credits before: $" . number_format((float) $result['data']['balance_before'], 4) . "\n";
            echo "  Credits after:  $" . number_format((float) $result['data']['balance_after'], 4) . "\n";
        }
    } else {
        echo "✗ Failed to send SMS: {$result['error']}\n";

        if (!empty($result['data']) && isset($result['data']['balance_before'], $result['data']['balance_after'])) {
            echo "  Credits before: $" . number_format((float) $result['data']['balance_before'], 4) . "\n";
            echo "  Credits after:  $" . number_format((float) $result['data']['balance_after'], 4) . "\n";
        }
    }
}

// Run main function
main($argv);
?>