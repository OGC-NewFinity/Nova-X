# Provider Integration Guide

## Overview

Nova-X supports multiple AI providers, allowing you to choose the best AI model for your needs. This guide explains how to integrate and manage different AI providers.

---

## Supported Providers

Nova-X currently supports the following AI providers:

| Provider | Models | Status |
|----------|--------|--------|
| **OpenAI** | GPT-4, GPT-4 Turbo, GPT-3.5 | ✅ Supported |
| **Google Gemini** | Gemini Pro, Gemini Ultra | 🚧 Coming Soon |
| **Anthropic Claude** | Claude 3 Opus, Claude 3 Sonnet, Claude 3 Haiku | 🚧 Coming Soon |
| **Mistral AI** | Mistral Large, Mistral Medium | 🚧 Coming Soon |
| **Cohere** | Command, Command Light | 🚧 Coming Soon |

---

## Provider Features

- **Multiple Provider Support**: Switch between providers based on your needs
- **User-Managed API Keys**: Store and manage API keys securely in WordPress options
- **Provider Fallback**: Automatic failover to backup providers (coming soon)
- **Usage Tracking**: Monitor API usage per provider (coming soon)

---

## Setting Up Providers

### Via WordPress Admin

1. Navigate to **Nova-X** → **Settings** in the WordPress admin menu
2. Select the provider you want to configure
3. Enter your API key in the provided field
4. Click **Save** to store the key securely

### Programmatic Setup

#### Save API Key

```php
use Nova_X_Provider_Manager;

$success = Nova_X_Provider_Manager::save_api_key( 'openai', 'sk-...' );
```

#### Get API Key

```php
use Nova_X_Provider_Manager;

$api_key = Nova_X_Provider_Manager::get_api_key( 'openai' );
```

#### Check Provider Status

```php
use Nova_X_Provider_Manager;

$status = Nova_X_Provider_Manager::get_providers_status();
// Returns array with provider name and has_key status
```

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
- Mistral: `nova_x_mistral_api_key`
- Cohere: `nova_x_cohere_api_key`

---

## Using Providers

### Switching Providers

To switch between AI providers programmatically:

```php
$ai_engine = new Nova_X_AI_Engine();
$ai_engine->set_provider( 'gemini' ); // or 'claude', 'mistral', etc.
$result = $ai_engine->generate( $prompt );
```

### Default Provider

The default provider is **OpenAI**. Users can change the default provider in settings.

---

## Security Best Practices

1. **Never commit API keys**: Keep API keys out of version control
2. **Use environment variables**: For production, consider using environment variables
3. **Rotate keys regularly**: Periodically update your API keys
4. **Monitor usage**: Track API usage to detect unauthorized access
5. **Use least privilege**: Only grant necessary permissions to API keys

---

## Provider-Specific Configuration

### OpenAI

- **Models**: GPT-4, GPT-4 Turbo, GPT-3.5 Turbo
- **API Key Format**: Starts with `sk-`
- **Rate Limits**: Varies by model and tier

### Google Gemini (Coming Soon)

- **Models**: Gemini Pro, Gemini Ultra
- **API Key Format**: Varies
- **Rate Limits**: To be documented

### Anthropic Claude (Coming Soon)

- **Models**: Claude 3 Opus, Claude 3 Sonnet, Claude 3 Haiku
- **API Key Format**: Starts with `sk-ant-`
- **Rate Limits**: To be documented

### Mistral AI (Coming Soon)

- **Models**: Mistral Large, Mistral Medium
- **API Key Format**: To be documented
- **Rate Limits**: To be documented

### Cohere (Coming Soon)

- **Models**: Command, Command Light
- **API Key Format**: To be documented
- **Rate Limits**: To be documented

---

## Error Handling

When working with providers, always handle potential errors:

```php
try {
    $ai_engine = new Nova_X_AI_Engine();
    $result = $ai_engine->generate( $prompt );
} catch ( Exception $e ) {
    // Handle error (missing API key, rate limit, etc.)
    error_log( 'Nova-X Error: ' . $e->getMessage() );
}
```

Common error scenarios:
- Missing API key
- Invalid API key format
- Rate limit exceeded
- Network timeout
- Provider service unavailable

---

## Future Enhancements

- [ ] Bulk import/export of API keys
- [ ] API key validation on save
- [ ] Usage tracking per provider
- [ ] Provider fallback chain configuration
- [ ] API key encryption at rest
- [ ] Provider health monitoring
- [ ] Automatic failover between providers

---

## Troubleshooting

### API Key Not Working

1. Verify the API key format is correct for the provider
2. Check that the API key has not expired
3. Ensure the API key has the necessary permissions
4. Verify network connectivity to the provider's API

### Provider Not Available

1. Check the provider status in settings
2. Verify your API key is correctly configured
3. Check provider service status
4. Review error logs for detailed messages

---

