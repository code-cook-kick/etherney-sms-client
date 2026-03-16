#!/usr/bin/env node

/**
 * Simple SMS Sender using fetch (Node.js version)
 * Based on cli-sender.php
 * 
 * Usage: node cli-sender.js --mobile=<number> --message=<text> [--api-key=<key>] [--api-url=<url>]
 * 
 * Example: node cli-sender.js --mobile=639176738989 --message="Hello World"
 */

// Default configuration
const API_KEY = 'sign-up-and-create-api-key';
const API_BASE_URL = 'https://www.etherney.org';
const API_SEND_URL = API_BASE_URL + '/api/send-sms.php';

/**
 * Parse command line arguments
 */
function parseArgs(argv) {
    const options = {
        mobile: null,
        message: null,
        apiKey: null,
        apiUrl: null,
        help: false
    };

    for (let i = 2; i < argv.length; i++) {
        const arg = argv[i];
        if (arg === '--help' || arg === '-h') {
            options.help = true;
        } else if (arg.startsWith('--mobile=')) {
            options.mobile = arg.substring(9);
        } else if (arg.startsWith('--message=')) {
            options.message = arg.substring(10);
        } else if (arg.startsWith('--api-key=')) {
            options.apiKey = arg.substring(10);
        } else if (arg.startsWith('--api-url=')) {
            options.apiUrl = arg.substring(10);
        }
    }

    return options;
}

/**
 * Display help message
 */
function displayHelp() {
    console.log("Simple SMS Sender using fetch (Node.js)");
    console.log("=======================================\n");
    console.log("Usage: node cli-sender.js [options]\n");
    console.log("Options:");
    console.log("  --mobile=<number>      Mobile number (without +)");
    console.log("  --message=<text>       Message text to send");
    console.log("  --api-key=<key>        API key (optional, uses default if not provided)");
    console.log("  --api-url=<url>        API base URL (optional, default: https://www.etherney.org)");
    console.log("  --help                 Show this help message\n");
    console.log("Examples:");
    console.log("  node cli-sender.js --mobile=639176738989 --message=\"Hello\"");
    console.log("  node cli-sender.js --mobile=639176738989 --message=\"Test\" --api-key=your_key");
    console.log("  node cli-sender.js --mobile=639176738989 --message=\"Test\" --api-url=http://your-server.com");
}

/**
 * Validate mobile number format
 */
function validateMobileNumber(mobileNo) {
    const regex = /^[0-9]{10,15}$/;
    return regex.test(mobileNo);
}

/**
 * Send SMS using fetch
 */
async function sendSms(mobileNo, message, apiKey, apiUrl) {
    // Validate inputs
    if (!message || !message.trim()) {
        return { success: false, error: 'Message cannot be empty' };
    }

    if (!validateMobileNumber(mobileNo)) {
        return { success: false, error: 'Invalid mobile number format. Use 10-15 digits without +' };
    }

    // Prepare data
    const data = new URLSearchParams();
    data.append('api_key', apiKey);
    data.append('mobile_no', mobileNo);
    data.append('message', message);

    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: data.toString(),
            // Disable SSL verification like PHP does
            agent: new (require('https').Agent)({ rejectUnauthorized: false })
        });

        const responseText = await response.text();
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            return { 
                success: false, 
                error: `Invalid JSON response: ${e.message}`,
                rawResponse: responseText
            };
        }

        return result;
    } catch (error) {
        return { 
            success: false, 
            error: `HTTP error: ${error.message}` 
        };
    }
}

/**
 * Format currency amount
 */
function formatCurrency(amount) {
    try {
        const num = parseFloat(amount);
        return `$${num.toFixed(4)}`;
    } catch (e) {
        return `$${amount}`;
    }
}

/**
 * Main execution
 */
async function main() {
    const options = parseArgs(process.argv);

    // Show help if requested or no arguments
    if (options.help || process.argv.length === 2) {
        displayHelp();
        return;
    }

    // Check required arguments
    if (!options.mobile || !options.message) {
        console.log("Error: Both --mobile and --message are required.\n");
        displayHelp();
        return;
    }

    // Set API key and URL
    const apiKey = options.apiKey || API_KEY;
    const apiUrl = options.apiUrl ? options.apiUrl + '/api/send-sms.php' : API_SEND_URL;

    // Display info
    console.log("Simple SMS Sender");
    console.log("================\n");
    console.log(`Mobile: ${options.mobile}`);
    const messagePreview = options.message.length > 50 
        ? options.message.substring(0, 50) + '...' 
        : options.message;
    console.log(`Message: ${messagePreview}`);
    console.log(`Message length: ${options.message.length} characters`);
    console.log(`API Key: ${apiKey.substring(0, 8)}...`);
    console.log(`API URL: ${apiUrl}\n`);

    // Send SMS
    console.log("Sending SMS...");
    const result = await sendSms(options.mobile, options.message, apiKey, apiUrl);

    // Display result
    if (result.success) {
        console.log("✓ SMS sent successfully!");
        const data = result.data || {};
        console.log(`  SMS ID: ${data.sms_id || 'N/A'}`);
        console.log(`  Status: ${data.status || 'N/A'}`);
        console.log(`  Provider: ${data.provider || 'N/A'}`);
        console.log(`  To: ${data.to || 'N/A'}`);

        if (data.balance_before !== undefined && data.balance_after !== undefined) {
            console.log(`  Credits before: ${formatCurrency(data.balance_before)}`);
            console.log(`  Credits after:  ${formatCurrency(data.balance_after)}`);
        }
    } else {
        console.log(`✗ Failed to send SMS: ${result.error || 'Unknown error'}`);

        const data = result.data || {};
        if (data.balance_before !== undefined && data.balance_after !== undefined) {
            console.log(`  Credits before: ${formatCurrency(data.balance_before)}`);
            console.log(`  Credits after:  ${formatCurrency(data.balance_after)}`);
        }
    }
}

// Run main function
main().catch(error => {
    console.error(`Unexpected error: ${error.message}`);
    process.exit(1);
});