# Settings Page API Key Manager

## Overview

Nova-X supports multiple AI providers, allowing users and administrators to manage API keys for different services. This document outlines how the API key management system works.

---

## Supported Providers

Nova-X supports the following AI providers:

- **OpenAI** - GPT-4, GPT-4 Turbo, GPT-3.5
- **Google Gemini** - Gemini Pro, Gemini Ultra
- **Anthropic Claude** - Claude 3 Opus, Claude 3 Sonnet, Claude 3 Haiku
- **Mistral AI** - Mistral Large, Mistral Medium
- **Cohere** - Command, Command Light

---

## API Key Storage

Each provider's API key is stored separately in WordPress Options API using the following naming convention:

```
nova_x_{provider}_api_key
```

For example:
- OpenAI: `nova_x_openai_api_key`
- Gemini: `nova_x_gemini_api_key`
- Claude: `nova_x_claude_api_key`

---

## Managing API Keys

### For Administrators

Administrators can add and manage API keys through the Nova-X settings page in WordPress Admin:

1. Navigate to **Nova-X** in the WordPress admin menu
2. Go to **Settings** (or API Keys section)
3. Select the provider you want to configure
4. Enter your API key in the provided field
5. Click **Save** to store the key securely

### Programmatic Access

#### Get API Key

```php
use Nova_X_Provider_Manager;

$api_key = Nova_X_Provider_Manager::get_api_key( 'openai' );
```

#### Save API Key

```php
use Nova_X_Provider_Manager;

$success = Nova_X_Provider_Manager::save_api_key( 'openai', 'sk-...' );
```

#### Check Provider Status

```php
use Nova_X_Provider_Manager;

$status = Nova_X_Provider_Manager::get_providers_status();
// Returns array with provider name and has_key status
```

---

## Security Considerations

1. **Permissions**: Only users with `manage_options` capability can save API keys
2. **Sanitization**: All API keys are sanitized using `sanitize_text_field()` before storage
3. **No Exposure**: API keys are never exposed to the frontend or in REST API responses
4. **Encryption**: Consider implementing encryption for production environments (WordPress Secrets API)

---

## REST API Endpoints

### Save API Key

**Endpoint:** `/wp-json/nova-x/v1/save-key`

**Method:** POST

**Parameters:**
- `provider` (string, required) - Provider slug (openai, gemini, claude, etc.)
- `api_key` (string, required) - The API key to save

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
});
```

**Example Response:**

```json
{
    "success": true,
    "message": "API key saved successfully."
}
```

---

## Switching Providers

To switch between AI providers programmatically:

```php
$ai_engine = new Nova_X_AI_Engine();
$ai_engine->set_provider( 'gemini' ); // or 'claude', 'mistral', etc.
$result = $ai_engine->generate( $prompt );
```

---

## Default Provider

The default provider is **OpenAI**. Users can change the default provider in settings.

---

## Future Enhancements

- [ ] Bulk import/export of API keys
- [ ] API key validation on save
- [ ] Usage tracking per provider
- [ ] Provider fallback chain configuration
- [ ] API key encryption at rest

---

