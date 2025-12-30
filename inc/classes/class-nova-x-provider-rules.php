<?php
/**
 * Provider Rules for API Key Validation
 *
 * Centralized validation rules for all supported AI providers.
 * Each provider has unique format requirements (prefix, regex, length).
 *
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_Provider_Rules {

    /**
     * Get validation rules for all providers
     * Normalized structure for easy expansion and consistency.
     *
     * @return array Array of provider rules with prefix, regex, min, max, label, and enabled status
     */
    public static function get_rules() {
        /**
         * Filter provider rules to allow external customization.
         * 
         * @param array $rules Default provider rules.
         * @return array Modified provider rules.
         */
        $rules = apply_filters( 'nova_x_provider_rules', [
            'openai' => [
                'prefix'  => 'sk-',
                'regex'   => '/^sk-[A-Za-z0-9]{32,64}$/',
                'min'     => 32,
                'max'     => 64,
                'label'   => 'OpenAI',
                'enabled' => true,
            ],
            'anthropic' => [
                'prefix'  => 'cla-',
                'regex'   => '/^cla-[A-Za-z0-9]{20,64}$/',
                'min'     => 20,
                'max'     => 64,
                'label'   => 'Anthropic Claude',
                'enabled' => true,
            ],
            'claude' => [
                'prefix'  => 'cla-',
                'regex'   => '/^cla-[A-Za-z0-9]{20,64}$/',
                'min'     => 20,
                'max'     => 64,
                'label'   => 'Anthropic Claude',
                'enabled' => true,
            ],
            'gemini' => [
                'prefix'  => 'AIza',
                'regex'   => '/^AIza[0-9A-Za-z-_]{30,60}$/',
                'min'     => 30,
                'max'     => 60,
                'label'   => 'Google Gemini',
                'enabled' => true,
            ],
            'mistral' => [
                'prefix'  => '',
                'regex'   => '/^[A-Za-z0-9]{32,128}$/',
                'min'     => 32,
                'max'     => 128,
                'label'   => 'Mistral AI',
                'enabled' => true,
            ],
            'cohere' => [
                'prefix'  => '',
                'regex'   => '/^[A-Za-z0-9]{40,128}$/',
                'min'     => 40,
                'max'     => 128,
                'label'   => 'Cohere',
                'enabled' => true,
            ],
            'groq' => [
                'prefix'  => '',
                'regex'   => '/^[A-Za-z0-9]{32,128}$/',
                'min'     => 32,
                'max'     => 128,
                'label'   => 'Groq',
                'enabled' => true,
            ],
            'custom' => [
                'prefix'  => '',
                'regex'   => '/^[A-Za-z0-9-_]{20,128}$/',
                'min'     => 20,
                'max'     => 128,
                'label'   => 'Custom Provider',
                'enabled' => true,
            ],
        ] );

        // Normalize rules structure (ensure all required keys exist)
        foreach ( $rules as $provider => &$rule ) {
            $rule = wp_parse_args( $rule, [
                'prefix'  => '',
                'regex'   => '/^[A-Za-z0-9-_]+$/',
                'min'     => 20,
                'max'     => 128,
                'label'   => ucfirst( $provider ),
                'enabled' => true,
            ] );
        }

        return $rules;
    }

    /**
     * Get normalized rule object for a provider.
     * Returns a consistent structure regardless of provider.
     *
     * @param string $provider Provider slug.
     * @return array|false Normalized rule array or false if not found.
     */
    public static function get_normalized_rule( $provider ) {
        $rule = self::get_provider_rule( $provider );
        
        if ( ! $rule ) {
            return false;
        }

        // Ensure all required keys are present
        return wp_parse_args( $rule, [
            'prefix'  => '',
            'regex'   => '/^[A-Za-z0-9-_]+$/',
            'min'     => 20,
            'max'     => 128,
            'label'   => ucfirst( $provider ),
            'enabled' => true,
        ] );
    }

    /**
     * Get validation rules for a specific provider
     *
     * @param string $provider Provider slug (e.g., 'openai', 'anthropic').
     * @return array|false Provider rules array or false if not found.
     */
    public static function get_provider_rule( $provider ) {
        $provider = strtolower( sanitize_key( $provider ) );
        $rules    = self::get_rules();

        // Direct match
        if ( isset( $rules[ $provider ] ) ) {
            return $rules[ $provider ];
        }

        // Map common provider name variations
        $provider_map = [
            'openai'     => 'openai',
            'gpt'        => 'openai',
            'anthropic'  => 'anthropic',
            'claude'     => 'anthropic',
            'gemini'     => 'gemini',
            'google'     => 'gemini',
        ];

        $mapped_provider = isset( $provider_map[ $provider ] ) ? $provider_map[ $provider ] : $provider;

        if ( isset( $rules[ $mapped_provider ] ) ) {
            return $rules[ $mapped_provider ];
        }

        // Fallback to custom rules
        return $rules['custom'];
    }

    /**
     * Validate API key format for a provider
     *
     * @param string $api_key  API key to validate.
     * @param string $provider Provider slug.
     * @return array Validation result with 'valid' (bool) and 'message' (string).
     */
    public static function validate_key( $api_key, $provider ) {
        $api_key = trim( (string) $api_key );

        // Empty key check
        if ( empty( $api_key ) ) {
            return [
                'valid'   => false,
                'message' => 'API key cannot be empty.',
            ];
        }

        // Get provider rules
        $rule = self::get_provider_rule( $provider );

        if ( ! $rule ) {
            return [
                'valid'   => false,
                'message' => 'Invalid provider specified.',
            ];
        }

        // Check length
        $key_length = strlen( $api_key );
        if ( $key_length < $rule['min'] || $key_length > $rule['max'] ) {
            return [
                'valid'   => false,
                'message' => sprintf(
                    'API key length must be between %d and %d characters. Current length: %d.',
                    $rule['min'],
                    $rule['max'],
                    $key_length
                ),
            ];
        }

        // Check prefix (if required)
        if ( ! empty( $rule['prefix'] ) && strpos( $api_key, $rule['prefix'] ) !== 0 ) {
            return [
                'valid'   => false,
                'message' => sprintf(
                    'API key must start with "%s".',
                    $rule['prefix']
                ),
            ];
        }

        // Check regex pattern
        if ( ! empty( $rule['regex'] ) && ! preg_match( $rule['regex'], $api_key ) ) {
            return [
                'valid'   => false,
                'message' => sprintf(
                    'API key format is invalid for %s. Expected format: %s',
                    $rule['label'],
                    $rule['prefix'] . '...'
                ),
            ];
        }

        return [
            'valid'   => true,
            'message' => 'API key format is valid.',
        ];
    }

    /**
     * Check if a key appears to be encrypted (detects base64 encoded encrypted data)
     *
     * @param string $key Key to check.
     * @return bool True if key appears encrypted.
     */
    public static function is_encrypted( $key ) {
        $key = trim( (string) $key );

        // Encrypted keys are base64 encoded and typically longer
        // They won't match any provider's expected format
        if ( empty( $key ) || strlen( $key ) < 50 ) {
            return false;
        }

        // Check if it's valid base64 (encrypted keys are base64 encoded)
        if ( ! preg_match( '/^[A-Za-z0-9+\/]+=*$/', $key ) ) {
            return false;
        }

        // If it doesn't match any provider's expected format, it's likely encrypted
        $rules = self::get_rules();
        foreach ( $rules as $provider => $rule ) {
            if ( preg_match( $rule['regex'], $key ) ) {
                return false; // Matches a provider format, likely plaintext
            }
        }

        // Additional check: encrypted keys are typically much longer than max provider key length
        $max_provider_length = max( array_column( $rules, 'max' ) );
        if ( strlen( $key ) > $max_provider_length * 2 ) {
            return true; // Likely encrypted (base64 expands data)
        }

        return false;
    }

    /**
     * Detect provider from API key format (for migration purposes)
     *
     * @param string $api_key API key to analyze.
     * @return string|false Provider slug or false if cannot detect.
     */
    public static function detect_provider_from_key( $api_key ) {
        $api_key = trim( (string) $api_key );

        if ( empty( $api_key ) ) {
            return false;
        }

        $rules = self::get_rules();

        foreach ( $rules as $provider => $rule ) {
            if ( preg_match( $rule['regex'], $api_key ) ) {
                return $provider;
            }
        }

        return false;
    }
}

