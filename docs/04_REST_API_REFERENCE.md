# REST API Reference

## Overview

Nova-X exposes a REST API for programmatic access to its features. All endpoints are namespaced under `/wp-json/nova-x/v1/` and require proper authentication.

**Base URL:** `/wp-json/nova-x/v1/`

---

## Authentication

All API requests require WordPress nonce authentication. Include the nonce in your request headers:

```javascript
headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpApiSettings.nonce
}
```

Additionally, all endpoints require the `manage_options` capability, meaning the user must be an administrator.

---

## Endpoints

### Save API Key

Save or update an API key for an AI provider.

**Endpoint:** `POST /nova-x/v1/save-key`

**Authentication:** Required (manage_options capability)

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `provider` | string | Yes | Provider slug (openai, gemini, claude, mistral, cohere) |
| `api_key` | string | Yes | The API key to save |

**Example Request:**

```javascript
fetch('/wp-json/nova-x/v1/save-key', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        provider: 'openai',
        api_key: 'sk-...'
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

**Example Response (Success):**

```json
{
    "success": true,
    "message": "API key saved successfully."
}
```

**Example Response (Error):**

```json
{
    "success": false,
    "message": "Invalid API key format."
}
```

**Error Codes:**

- `400` - Bad Request (invalid API key format, masked key, or missing parameters)
- `403` - Forbidden (insufficient permissions)
- `500` - Internal Server Error

**Validation Rules:**

- API keys starting with `sk-` are validated for OpenAI format
- Masked API keys (containing `*`) cannot be saved
- Empty API keys are rejected

---

## Provider Management

### Get API Key

Retrieve an API key for a provider programmatically.

**PHP Example:**

```php
use Nova_X_Provider_Manager;

$api_key = Nova_X_Provider_Manager::get_api_key( 'openai' );
```

### Save API Key

Save an API key programmatically.

**PHP Example:**

```php
use Nova_X_Provider_Manager;

$success = Nova_X_Provider_Manager::save_api_key( 'openai', 'sk-...' );
```

### Get Provider Status

Check which providers have API keys configured.

**PHP Example:**

```php
use Nova_X_Provider_Manager;

$status = Nova_X_Provider_Manager::get_providers_status();
// Returns array with provider name and has_key status
```

---

## Security Considerations

1. **Permissions**: Only users with `manage_options` capability can save API keys
2. **Sanitization**: All API keys are sanitized using `sanitize_text_field()` before storage
3. **No Exposure**: API keys are never exposed in REST API responses (GET endpoints for keys are intentionally not provided)
4. **Encryption**: Consider implementing encryption for production environments (WordPress Secrets API)

---

## Future Endpoints

The following endpoints are planned for future releases:

- `GET /nova-x/v1/generate` - Generate content using AI
- `POST /nova-x/v1/generate` - Generate content with custom parameters
- `GET /nova-x/v1/templates` - List saved templates
- `POST /nova-x/v1/templates` - Save a new template
- `GET /nova-x/v1/usage` - Get usage statistics
- `GET /nova-x/v1/providers` - List available providers and their status

---

## Error Handling

All endpoints return JSON responses with a consistent structure:

**Success Response:**
```json
{
    "success": true,
    "message": "Operation completed successfully.",
    "data": {}
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Human-readable error message",
    "code": "error_code"
}
```

Always check the `success` field before processing the response data.

---

