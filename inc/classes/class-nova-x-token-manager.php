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
     * Get option name for encrypted key storage
     *
     * @param string $provider Provider name.
     * @return string Option name.
     */
    private static function get_option_name( $provider ) {
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

        // Get encrypted data
        $encrypted_data = get_option( self::get_option_name( $provider ), '' );
        
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

        // Check if key already exists (unless forcing)
        if ( ! $force && false !== self::get_decrypted_key( $provider ) ) {
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

        $encrypted_data = get_option( self::get_option_name( $provider ), '' );
        return ! empty( $encrypted_data );
    }
}

