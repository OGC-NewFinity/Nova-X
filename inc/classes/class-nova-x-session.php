<?php
/**
 * Nova-X Session Management
 * 
 * Centralized session helper for authentication state management.
 * 
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_Session {
    
    /**
     * Start session with secure cookie settings
     */
    public static function start() {
        if ( ! session_id() && is_admin() ) {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure'   => is_ssl(),
                'cookie_samesite' => 'Strict',
            ]);
        }
    }
    
    /**
     * Get current user from session
     * 
     * @return array|null User data or null if not set
     */
    public static function get_user() {
        return $_SESSION['nova_x_user'] ?? null;
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool True if user session exists and is valid
     */
    public static function is_logged_in() {
        $user = self::get_user();
        return is_array( $user ) && ! empty( $user['email'] ?? '' );
    }
    
    /**
     * Destroy session and clear all session data
     */
    public static function destroy() {
        if ( session_id() ) {
            $_SESSION = [];
            session_destroy();
        }
    }
}

