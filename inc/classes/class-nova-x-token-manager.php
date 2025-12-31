<?php
/**
 * Token Manager for Encrypted API Key Storage
 *
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_Token_Manager {

    /**
     * Encryption method
     *
     * @var string
     */
    private const CIPHER_METHOD = 'AES-256-CBC';

    /**
     * Get encryption key
     *
     * @return string Encryption key
     */
    private static function get_encryption_key() {
        // Use constant if defined, otherwise generate from site-specific data
        if ( defined( 'NOVA_X_ENCRYPTION_KEY' ) && ! empty( NOVA_X_ENCRYPTION_KEY ) ) {
            return substr( hash( 'sha256', NOVA_X_ENCRYPTION_KEY ), 0, 32 );
        }
        
        // Fallback: Generate from site URL and salt (less secure but functional)
        $site_key = AUTH_SALT . get_site_url() . SECURE_AUTH_SALT;
        return substr( hash( 'sha256', $site_key ), 0, 32 );
    }

    /**
     * Get option name for encrypted key storage (standardized format)
     *
     * @param string $provider Provider name.
     * @return string Option name.
     */
    private static function get_option_name( $provider ) {
        $sanitized = sanitize_key( $provider );
        return 'nova_x_key_' . $sanitized;
    }

    /**
     * Get legacy option name for backward compatibility
     *
     * @param string $provider Provider name.
     * @return string Legacy option name.
     */
    private static function get_legacy_option_name( $provider ) {
        $sanitized = sanitize_key( $provider );
        return 'nova_x_api_key_encrypted_' . $sanitized;
    }

    /**
     * Store encrypted API key
     *
     * @param string $provider Provider name.
     * @param string $raw_key  Raw API key to encrypt.
     * @return bool Success status.
     */
    public static function store_encrypted_key( $provider, $raw_key ) {
        // Sanitize input
        $provider = sanitize_key( $provider );
        $raw_key  = trim( (string) $raw_key );
        
        if ( empty( $provider ) || empty( $raw_key ) ) {
            return false;
        }

        // Check if OpenSSL is available
        if ( ! function_exists( 'openssl_encrypt' ) ) {
            // Fallback to base64 encoding if OpenSSL is not available (less secure)
            $encrypted = base64_encode( $raw_key );
            return update_option( self::get_option_name( $provider ), $encrypted, false );
        }

        // Generate IV (Initialization Vector)
        $iv_length = openssl_cipher_iv_length( self::CIPHER_METHOD );
        $iv        = openssl_random_pseudo_bytes( $iv_length );
        
        // Encrypt the key
        $encrypted = openssl_encrypt( $raw_key, self::CIPHER_METHOD, self::get_encryption_key(), 0, $iv );
        
        if ( false === $encrypted ) {
            return false;
        }

        // Combine IV and encrypted data
        $encrypted_data = base64_encode( $iv . $encrypted );

        // Store encrypted key
        return update_option( self::get_option_name( $provider ), $encrypted_data, false );
    }

    /**
     * Get decrypted API key
     *
     * @param string $provider Provider name.
     * @return string|false Decrypted key or false on failure.
     */
    public static function get_decrypted_key( $provider ) {
        // Sanitize input
        $provider = sanitize_key( $provider );
        
        if ( empty( $provider ) ) {
            return false;
        }

        // Get encrypted data (check standardized format first)
        $encrypted_data = get_option( self::get_option_name( $provider ), '' );
        
        // Fallback to legacy format for backward compatibility
        if ( empty( $encrypted_data ) ) {
            $encrypted_data = get_option( self::get_legacy_option_name( $provider ), '' );
            
            // Migrate legacy to new format if found
            if ( ! empty( $encrypted_data ) ) {
                update_option( self::get_option_name( $provider ), $encrypted_data, false );
                // Optionally delete legacy option after migration
                // delete_option( self::get_legacy_option_name( $provider ) );
            }
        }
        
        if ( empty( $encrypted_data ) ) {
            return false;
        }

        // Check if OpenSSL is available
        if ( ! function_exists( 'openssl_decrypt' ) ) {
            // Fallback: Try base64 decode if OpenSSL is not available
            $decrypted = base64_decode( $encrypted_data, true );
            return $decrypted !== false ? $decrypted : false;
        }

        // Decode the encrypted data
        $data = base64_decode( $encrypted_data, true );
        
        if ( false === $data ) {
            return false;
        }

        // Extract IV and encrypted content
        $iv_length = openssl_cipher_iv_length( self::CIPHER_METHOD );
        $iv        = substr( $data, 0, $iv_length );
        $encrypted = substr( $data, $iv_length );

        // Decrypt the key
        $decrypted = openssl_decrypt( $encrypted, self::CIPHER_METHOD, self::get_encryption_key(), 0, $iv );
        
        if ( false === $decrypted ) {
            return false;
        }

        return trim( $decrypted );
    }

    /**
     * Rotate API key (replace existing with new one)
     *
     * @param string $provider Provider name.
     * @param string $new_key  New API key to store.
     * @param bool   $force    Force rotation even if key exists.
     * @return bool Success status.
     */
    public static function rotate_key( $provider, $new_key, $force = true ) {
        // Sanitize input
        $provider = sanitize_key( $provider );
        $new_key  = trim( (string) $new_key );
        
        if ( empty( $provider ) || empty( $new_key ) ) {
            return false;
        }

        // Reject masked keys (contain bullet points or asterisks)
        if ( strpos( $new_key, '•' ) !== false || strpos( $new_key, '*' ) !== false ) {
            return false;
        }

        // Validate provider
        if ( ! class_exists( 'Nova_X_Provider_Manager' ) ) {
            $provider_path = defined( 'NOVA_X_PATH' ) 
                ? NOVA_X_PATH . 'inc/classes/class-nova-x-provider-manager.php'
                : plugin_dir_path( __FILE__ ) . 'class-nova-x-provider-manager.php';
            require_once $provider_path;
        }

        if ( ! Nova_X_Provider_Manager::is_valid_provider( $provider ) ) {
            return false;
        }

        // Validate key format using Provider Rules
        if ( ! class_exists( 'Nova_X_Provider_Rules' ) ) {
            $rules_path = defined( 'NOVA_X_PATH' ) 
                ? NOVA_X_PATH . 'inc/classes/class-nova-x-provider-rules.php'
                : plugin_dir_path( __FILE__ ) . 'class-nova-x-provider-rules.php';
            require_once $rules_path;
        }

        $validation = Nova_X_Provider_Rules::validate_key( $new_key, $provider );
        
        if ( ! $validation['valid'] ) {
            return false;
        }

        // Check if key is unchanged (compare with existing)
        $existing_key = self::get_decrypted_key( $provider );
        if ( $existing_key === $new_key ) {
            // Key unchanged - don't rotate
            return false;
        }

        // Check if key already exists (unless forcing)
        if ( ! $force && false !== $existing_key ) {
            return false;
        }

        // Store the new encrypted key
        return self::store_encrypted_key( $provider, $new_key );
    }

    /**
     * Delete encrypted key
     *
     * @param string $provider Provider name.
     * @return bool Success status.
     */
    public static function delete_key( $provider ) {
        $provider = sanitize_key( $provider );
        
        if ( empty( $provider ) ) {
            return false;
        }

        return delete_option( self::get_option_name( $provider ) );
    }

    /**
     * Check if encrypted key exists
     *
     * @param string $provider Provider name.
     * @return bool True if key exists.
     */
    public static function key_exists( $provider ) {
        $provider = sanitize_key( $provider );
        
        if ( empty( $provider ) ) {
            return false;
        }

        // Check standardized format first
        $encrypted_data = get_option( self::get_option_name( $provider ), '' );
        
        // Fallback to legacy format
        if ( empty( $encrypted_data ) ) {
            $encrypted_data = get_option( self::get_legacy_option_name( $provider ), '' );
        }
        
        return ! empty( $encrypted_data );
    }

    /**
     * Encrypt a raw API key (wrapper for store_encrypted_key)
     *
     * @param string $provider Provider name.
     * @param string $raw_key  Raw API key to encrypt.
     * @return string|false Encrypted key string or false on failure.
     */
    public static function encrypt( $provider, $raw_key ) {
        $provider = sanitize_key( $provider );
        $raw_key  = trim( (string) $raw_key );
        
        if ( empty( $provider ) || empty( $raw_key ) ) {
            return false;
        }

        // Check if OpenSSL is available
        if ( ! function_exists( 'openssl_encrypt' ) ) {
            // Fallback to base64 encoding if OpenSSL is not available (less secure)
            return base64_encode( $raw_key );
        }

        // Generate IV (Initialization Vector)
        $iv_length = openssl_cipher_iv_length( self::CIPHER_METHOD );
        $iv        = openssl_random_pseudo_bytes( $iv_length );
        
        // Encrypt the key
        $encrypted = openssl_encrypt( $raw_key, self::CIPHER_METHOD, self::get_encryption_key(), 0, $iv );
        
        if ( false === $encrypted ) {
            return false;
        }

        // Combine IV and encrypted data
        return base64_encode( $iv . $encrypted );
    }

    /**
     * Decrypt an encrypted API key string
     *
     * @param string $encrypted_key Encrypted key string.
     * @return string|false Decrypted key or false on failure.
     */
    public static function decrypt( $encrypted_key ) {
        $encrypted_key = trim( (string) $encrypted_key );
        
        if ( empty( $encrypted_key ) ) {
            return false;
        }

        // Check if OpenSSL is available
        if ( ! function_exists( 'openssl_decrypt' ) ) {
            // Fallback: Try base64 decode if OpenSSL is not available
            $decrypted = base64_decode( $encrypted_key, true );
            return $decrypted !== false ? $decrypted : false;
        }

        // Decode the encrypted data
        $data = base64_decode( $encrypted_key, true );
        
        if ( false === $data ) {
            return false;
        }

        // Extract IV and encrypted content
        $iv_length = openssl_cipher_iv_length( self::CIPHER_METHOD );
        $iv        = substr( $data, 0, $iv_length );
        $encrypted = substr( $data, $iv_length );

        // Decrypt the key
        $decrypted = openssl_decrypt( $encrypted, self::CIPHER_METHOD, self::get_encryption_key(), 0, $iv );
        
        if ( false === $decrypted ) {
            return false;
        }

        return trim( $decrypted );
    }

    /**
     * Migrate unencrypted keys to encrypted storage
     * Checks legacy option names and migrates if found
     *
     * @param string $provider Provider name.
     * @return bool True if migration occurred, false otherwise.
     */
    public static function migrate_unencrypted_key( $provider ) {
        $provider = sanitize_key( $provider );
        
        if ( empty( $provider ) ) {
            return false;
        }

        // Skip if encrypted key already exists
        if ( self::key_exists( $provider ) ) {
            return false;
        }

        // Check legacy option names (both old encrypted format and plaintext)
        $legacy_options = [
            'nova_x_api_key', // Generic legacy option
            'nova_x_' . $provider . '_api_key', // Provider-specific legacy option
            self::get_legacy_option_name( $provider ), // Old encrypted format
        ];

        foreach ( $legacy_options as $option_name ) {
            $unencrypted_key = get_option( $option_name, '' );
            
            if ( ! empty( $unencrypted_key ) ) {
                $key = trim( (string) $unencrypted_key );
                
                // Skip if it looks like a masked placeholder
                if ( strpos( $key, '*' ) !== false ) {
                    continue;
                }

                // Check if it's already encrypted (base64 encoded encrypted data)
                // If it doesn't match provider format, it might be encrypted
                if ( ! self::is_likely_plaintext( $key, $provider ) ) {
                    continue;
                }

                // Migrate: encrypt and store
                if ( self::store_encrypted_key( $provider, $key ) ) {
                    // Optionally delete legacy option (commented out for safety)
                    // delete_option( $option_name );
                    
                    // Log migration (only in dev mode)
                    if ( defined( 'NOVA_X_DEV_MODE' ) && NOVA_X_DEV_MODE ) {
                        error_log( sprintf( '[Nova-X] Migrated unencrypted key for provider: %s', $provider ) );
                    }
                    
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a key is likely plaintext (matches provider format)
     *
     * @param string $key      Key to check.
     * @param string $provider Provider name.
     * @return bool True if likely plaintext.
     */
    private static function is_likely_plaintext( $key, $provider ) {
        // Load provider rules if available
        if ( class_exists( 'Nova_X_Provider_Rules' ) ) {
            $validation = Nova_X_Provider_Rules::validate_key( $key, $provider );
            return $validation['valid'];
        }

        // Fallback: basic checks
        $key = trim( (string) $key );
        
        // Too short to be encrypted
        if ( strlen( $key ) < 50 ) {
            return true;
        }

        // Check common provider prefixes
        $prefixes = [ 'sk-', 'sk-ant-', 'AIza' ];
        foreach ( $prefixes as $prefix ) {
            if ( strpos( $key, $prefix ) === 0 ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate API key format against provider rules (without saving)
     *
     * @param string $provider Provider name.
     * @param string $api_key  API key to validate.
     * @return array Validation result with 'valid' (bool), 'message' (string), 'provider_label' (string), and 'rule' (string) if debug mode.
     */
    public static function validate_api_key( $provider, $api_key ) {
        // Sanitize input
        $provider = sanitize_key( $provider );
        $api_key  = trim( (string) $api_key );
        
        if ( empty( $provider ) ) {
            return [
                'valid'   => false,
                'message' => 'Provider cannot be empty.',
                'status'  => 'invalid',
            ];
        }

        if ( empty( $api_key ) ) {
            return [
                'valid'   => false,
                'message' => 'API key cannot be empty.',
                'status'  => 'invalid',
            ];
        }

        // Reject masked keys (contain bullet points or asterisks)
        if ( strpos( $api_key, '•' ) !== false || strpos( $api_key, '*' ) !== false ) {
            return [
                'valid'   => false,
                'message' => 'Masked API keys cannot be validated. Please enter the full key.',
                'status'  => 'invalid',
            ];
        }

        // Load Provider Rules class
        if ( ! class_exists( 'Nova_X_Provider_Rules' ) ) {
            $rules_path = defined( 'NOVA_X_PATH' ) 
                ? NOVA_X_PATH . 'inc/classes/class-nova-x-provider-rules.php'
                : plugin_dir_path( __FILE__ ) . 'class-nova-x-provider-rules.php';
            require_once $rules_path;
        }

        // Get provider rule to access regex pattern
        $rule = Nova_X_Provider_Rules::get_provider_rule( $provider );
        
        if ( ! $rule ) {
            return [
                'valid'   => false,
                'message' => sprintf( 'Invalid provider: %s', esc_html( $provider ) ),
                'status'  => 'invalid',
            ];
        }

        // Validate using Provider Rules
        $validation = Nova_X_Provider_Rules::validate_key( $api_key, $provider );
        
        // Prepare response
        $result = [
            'valid'          => $validation['valid'],
            'message'        => $validation['message'],
            'status'         => $validation['valid'] ? 'valid' : 'invalid',
            'provider_label' => isset( $rule['label'] ) ? $rule['label'] : ucfirst( $provider ),
        ];

        // Add debug information if in debug mode
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $result['rule'] = isset( $rule['regex'] ) ? $rule['regex'] : '';
            $result['provider'] = $provider;
            
            // Log validation attempt
            error_log( sprintf(
                '[Nova-X] API Key Validation: Provider=%s, Valid=%s, Rule=%s, KeyLength=%d',
                $provider,
                $validation['valid'] ? 'yes' : 'no',
                isset( $rule['regex'] ) ? $rule['regex'] : 'none',
                strlen( $api_key )
            ) );
        }

        return $result;
    }
}

