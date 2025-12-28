<?php
/**
 * Helper Functions
 * General utility functions for Nova-X
 *
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Generate safe theme slug from title
 *
 * @param string $title Theme title.
 * @return string Safe slug for theme directory.
 */
function nova_x_generate_slug( $title ) {
    $slug = sanitize_title( $title );
    return $slug ?: 'nova-x-theme';
}

/**
 * Sanitize text input
 *
 * @param string $text Text to sanitize.
 * @return string Sanitized text.
 */
function nova_x_sanitize_text( $text ) {
    return sanitize_text_field( $text );
}

/**
 * Sanitize prompt/textarea input
 *
 * @param string $prompt Prompt text to sanitize.
 * @return string Sanitized prompt text.
 */
function nova_x_sanitize_prompt( $prompt ) {
    return sanitize_textarea_field( $prompt );
}

/**
 * Encrypt data using AES-256-CBC
 *
 * @param string $data Data to encrypt.
 * @param string $key  Encryption key (must be 32 bytes for AES-256).
 * @return string|false Base64-encoded encrypted string with IV, or false on failure.
 */
function nova_x_encrypt( $data, $key ) {
    if ( ! function_exists( 'openssl_encrypt' ) ) {
        return false;
    }

    $method = 'AES-256-CBC';
    
    // Ensure key is 32 bytes (AES-256 requires 256-bit key).
    $key = substr( hash( 'sha256', $key, true ), 0, 32 );

    // Generate initialization vector.
    $iv_length = openssl_cipher_iv_length( $method );
    if ( false === $iv_length ) {
        return false;
    }

    $iv = openssl_random_pseudo_bytes( $iv_length );
    if ( false === $iv ) {
        return false;
    }

    // Encrypt the data.
    $cipher = openssl_encrypt( $data, $method, $key, 0, $iv );
    if ( false === $cipher ) {
        return false;
    }

    // Combine IV and cipher, then base64 encode.
    return base64_encode( $iv . '::' . $cipher );
}

/**
 * Decrypt data using AES-256-CBC
 *
 * @param string $encrypted Base64-encoded encrypted string with IV.
 * @param string $key       Encryption key (must be 32 bytes for AES-256).
 * @return string|false Decrypted data, or false on failure.
 */
function nova_x_decrypt( $encrypted, $key ) {
    if ( ! function_exists( 'openssl_decrypt' ) ) {
        return false;
    }

    $method = 'AES-256-CBC';
    
    // Ensure key is 32 bytes (AES-256 requires 256-bit key).
    $key = substr( hash( 'sha256', $key, true ), 0, 32 );

    // Decode from base64.
    $decoded = base64_decode( $encrypted, true );
    if ( false === $decoded ) {
        return false;
    }

    // Split IV and cipher.
    $parts = explode( '::', $decoded, 2 );
    if ( count( $parts ) !== 2 ) {
        return false;
    }

    list( $iv, $cipher ) = $parts;

    // Decrypt the data.
    $decrypted = openssl_decrypt( $cipher, $method, $key, 0, $iv );
    
    return $decrypted;
}

/**
 * Get encryption key for Nova-X
 *
 * Attempts to use NOVA_X_SECRET_KEY constant if defined,
 * otherwise falls back to a combination of WordPress salts.
 *
 * @return string Encryption key.
 */
function nova_x_get_encryption_key() {
    // Use constant if defined.
    if ( defined( 'NOVA_X_SECRET_KEY' ) && ! empty( NOVA_X_SECRET_KEY ) ) {
        return NOVA_X_SECRET_KEY;
    }

    // Fall back to WordPress salts (for development/local environments).
    // In production, NOVA_X_SECRET_KEY should be defined in wp-config.php.
    $key = '';
    if ( defined( 'AUTH_KEY' ) ) {
        $key .= AUTH_KEY;
    }
    if ( defined( 'SECURE_AUTH_KEY' ) ) {
        $key .= SECURE_AUTH_KEY;
    }
    if ( defined( 'LOGGED_IN_KEY' ) ) {
        $key .= LOGGED_IN_KEY;
    }
    if ( defined( 'NONCE_KEY' ) ) {
        $key .= NONCE_KEY;
    }

    // If no keys are available, use a site-specific hash as fallback.
    if ( empty( $key ) ) {
        $key = md5( get_site_url() . get_option( 'admin_email', '' ) );
    }

    return $key;
}

