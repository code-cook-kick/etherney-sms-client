#!/usr/bin/env python3
"""
Simple SMS Sender using HTTP requests
Python version of cli-sender.php

Usage: python cli-sender.py --mobile=<number> --message=<text> [--api-key=<key>] [--api-url=<url>]

Example: python cli-sender.py --mobile=639176738989 --message="Hello World"
"""

import sys
import re
import json
import argparse
from urllib.parse import urlencode

try:
    import requests
    HAS_REQUESTS = True
except ImportError:
    HAS_REQUESTS = False
    import urllib.request
    import urllib.error

# Default configuration (same as PHP)
API_KEY = 'sign-up-and-create-api-key'
API_BASE_URL = 'https://www.etherney.org'
API_SEND_URL = API_BASE_URL + '/api/send-sms.php'


def parse_args():
    """Parse command line arguments."""
    parser = argparse.ArgumentParser(
        description='Simple SMS Sender using HTTP requests',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  %(prog)s --mobile=639176738989 --message="Hello"
  %(prog)s --mobile=639176738989 --message="Test" --api-key=your_key
  %(prog)s --mobile=639176738989 --message="Test" --api-url=http://your-server.com
        """
    )
    parser.add_argument('--mobile', required=True, help='Mobile number (without +)')
    parser.add_argument('--message', required=True, help='Message text to send')
    parser.add_argument('--api-key', help='API key (optional, uses default if not provided)')
    parser.add_argument('--api-url', help='API base URL (optional, default: https://www.etherney.org)')
    
    # For compatibility with PHP script's --help behavior
    if len(sys.argv) == 1:
        parser.print_help()
        sys.exit(0)
    
    return parser.parse_args()


def send_sms_requests(mobile_no, message, api_key, api_url):
    """Send SMS using requests library."""
    data = {
        'api_key': api_key,
        'mobile_no': mobile_no,
        'message': message
    }
    
    try:
        response = requests.post(
            api_url,
            data=data,
            headers={'Content-Type': 'application/x-www-form-urlencoded'},
            timeout=30,
            verify=False  # Disable SSL verification like PHP does
        )
        response.raise_for_status()
        return response.json()
    except requests.exceptions.RequestException as e:
        return {'success': False, 'error': f'HTTP error: {str(e)}'}
    except json.JSONDecodeError as e:
        return {'success': False, 'error': f'Invalid JSON response: {str(e)}'}


def send_sms_urllib(mobile_no, message, api_key, api_url):
    """Send SMS using urllib (fallback when requests not available)."""
    data = urlencode({
        'api_key': api_key,
        'mobile_no': mobile_no,
        'message': message
    }).encode('utf-8')
    
    req = urllib.request.Request(
        api_url,
        data=data,
        headers={'Content-Type': 'application/x-www-form-urlencoded'},
        method='POST'
    )
    
    # Disable SSL verification (not straightforward with urllib)
    import ssl
    context = ssl._create_unverified_context()
    
    try:
        with urllib.request.urlopen(req, timeout=30, context=context) as response:
            response_text = response.read().decode('utf-8')
            return json.loads(response_text)
    except urllib.error.URLError as e:
        return {'success': False, 'error': f'URL error: {str(e)}'}
    except json.JSONDecodeError as e:
        return {'success': False, 'error': f'Invalid JSON response: {str(e)}'}
    except Exception as e:
        return {'success': False, 'error': f'Unexpected error: {str(e)}'}


def send_sms(mobile_no, message, api_key, api_url):
    """Send SMS with validation and appropriate HTTP client."""
    # Validate inputs
    if not message or not message.strip():
        return {'success': False, 'error': 'Message cannot be empty'}
    
    if not re.match(r'^[0-9]{10,15}$', mobile_no):
        return {'success': False, 'error': 'Invalid mobile number format. Use 10-15 digits without +'}
    
    # Send using requests if available, otherwise urllib
    if HAS_REQUESTS:
        return send_sms_requests(mobile_no, message, api_key, api_url)
    else:
        return send_sms_urllib(mobile_no, message, api_key, api_url)


def format_currency(amount):
    """Format amount as currency with 4 decimal places."""
    try:
        return f"${float(amount):.4f}"
    except (ValueError, TypeError):
        return f"${amount}"


def main():
    """Main execution function."""
    args = parse_args()
    
    # Set API key and URL
    api_key = args.api_key if args.api_key else API_KEY
    api_url = args.api_url + '/api/send-sms.php' if args.api_url else API_SEND_URL
    
    # Display info
    print("Simple SMS Sender")
    print("================\n")
    print(f"Mobile: {args.mobile}")
    message_preview = args.message[:50] + ('...' if len(args.message) > 50 else '')
    print(f"Message: {message_preview}")
    print(f"Message length: {len(args.message)} characters")
    print(f"API Key: {api_key[:8]}...")
    print(f"API URL: {api_url}\n")
    
    # Send SMS
    print("Sending SMS...")
    result = send_sms(args.mobile, args.message, api_key, api_url)
    
    # Display result
    if result.get('success'):
        print("✓ SMS sent successfully!")
        data = result.get('data', {})
        print(f"  SMS ID: {data.get('sms_id', 'N/A')}")
        print(f"  Status: {data.get('status', 'N/A')}")
        print(f"  Provider: {data.get('provider', 'N/A')}")
        print(f"  To: {data.get('to', 'N/A')}")
        
        if 'balance_before' in data and 'balance_after' in data:
            print(f"  Credits before: {format_currency(data['balance_before'])}")
            print(f"  Credits after:  {format_currency(data['balance_after'])}")
    else:
        print(f"✗ Failed to send SMS: {result.get('error', 'Unknown error')}")
        
        data = result.get('data', {})
        if 'balance_before' in data and 'balance_after' in data:
            print(f"  Credits before: {format_currency(data['balance_before'])}")
            print(f"  Credits after:  {format_currency(data['balance_after'])}")


if __name__ == '__main__':
    main()