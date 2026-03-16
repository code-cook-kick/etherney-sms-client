# Etherney SMS Clients

This directory contains ready-to-use client examples for sending SMS through the Etherney SMS API. The scripts here are simple wrappers around the HTTP endpoint used by the web app.

## API Basics

**Base URL**: `https://www.etherney.org`

**Endpoint**: `POST /api/send-sms.php`

**Request (form-encoded)**

- `api_key`: Your API key
- `mobile_no`: Recipient mobile number (10–15 digits, no `+`)
- `message`: SMS message text

**Response (JSON)**

Typical fields returned by the API:

- `success` (boolean)
- `data.sms_id`
- `data.status` (e.g., `ON_QUEUE`, `SENT`, `REJECTED`)
- `data.provider`
- `data.to`
- `data.balance_before`
- `data.balance_after`

> Keep your API key private. Treat it like a password.

## CLI Clients

All CLI clients accept the same arguments:

- `--mobile=<number>` (required)
- `--message=<text>` (required)
- `--api-key=<key>` (optional; defaults to the demo key embedded in the script)
- `--api-url=<url>` (optional; defaults to `https://www.etherney.org`)

### PHP (cURL)

File: `cli-sender.php`

```bash
php cli-sender.php --mobile=639171234567 --message="Hello from PHP"
```

### Node.js (fetch)

File: `cli-sender.js`

```bash
node cli-sender.js --mobile=639171234567 --message="Hello from Node"
```

### Python (requests/urllib)

File: `cli-sender.py`

```bash
python3 cli-sender.py --mobile=639171234567 --message="Hello from Python"
```

## Local Development

If you are running Etherney SMS locally, you can override the base URL:

```bash
php cli-sender.php --mobile=639171234567 --message="Local send" --api-url=http://127.0.0.1:8080
```

The client scripts automatically append `/api/send-sms.php` to the base URL.

## Related Docs

- Product overview: `ETHERNEY-SMS.md`
- Developer overview: `Dev-README.md`
- API examples: `examples/send_sms.php`, `examples/send_sms_api.php`
