<?php
/**
 * Provider Factory for Nova-X
 *
 * Encapsulates per-provider behavior and provides a clean interface
 * for creating provider instances with rules, encryption methods, and test hooks.
 *
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provider Factory Class
 * 
 * Scaffold for future extensibility - allows easy addition of new providers
 * without modifying core logic.
 */
class Nova_X_Provider_Factory {

    /**
     * Create a provider instance with all necessary components
     *
     * @param string $provider Provider slug.
     * @return array|false Provider instance array or false on failure.
     */
    public static function create( $provider ) {
        $provider = sanitize_key( $provider );
        
        if ( empty( $provider ) ) {
            return false;
        }

        // Get provider rules
        if ( ! class_exists( 'Nova_X_Provider_Rules' ) ) {
            $rules_path = defined( 'NOVA_X_PATH' ) 
                ? NOVA_X_PATH . 'inc/classes/class-nova-x-provider-rules.php'
                : plugin_dir_path( __FILE__ ) . 'class-nova-x-provider-rules.php';
            require_once $rules_path;
        }

        $rule = Nova_X_Provider_Rules::get_normalized_rule( $provider );
        
        if ( ! $rule ) {
            return false;
        }

        // Check if provider is enabled
        if ( isset( $rule['enabled'] ) && ! $rule['enabled'] ) {
            return false;
        }

        // Build provider instance
        $instance = [
            'slug'           => $provider,
            'label'           => $rule['label'],
            'rules'           => $rule,
            'has_key'         => Nova_X_Provider_Manager::has_valid_key( $provider ),
            'status'          => self::get_status( $provider ),
            'encryption_method' => 'AES-256-CBC', // Standard encryption method
        ];

        /**
         * Filter provider instance to allow customization.
         * 
         * @param array  $instance Provider instance data.
         * @param string $provider Provider slug.
         * @return array Modified provider instance.
         */
        return apply_filters( 'nova_x_provider_instance', $instance, $provider );
    }

    /**
     * Get provider status
     *
     * @param string $provider Provider slug.
     * @return string Status: 'valid', 'missing', or 'invalid'
     */
    private static function get_status( $provider ) {
        $status = Nova_X_Provider_Manager::get_provider_status( $provider );
        return $status['status'];
    }

    /**
     * Get all provider instances
     *
     * @return array Array of provider instances
     */
    public static function get_all_instances() {
        $providers = Nova_X_Provider_Manager::get_supported_providers();
        $instances = [];

        foreach ( $providers as $provider ) {
            $instance = self::create( $provider );
            if ( $instance ) {
                $instances[ $provider ] = $instance;
            }
        }

        return $instances;
    }

    /**
     * Test provider API key (placeholder for future implementation)
     * 
     * This method can be extended to actually test API connectivity
     * for each provider.
     *
     * @param string $provider Provider slug.
     * @return array Test result with 'success' and 'message'
     */
    public static function test_provider_key( $provider ) {
        $provider = sanitize_key( $provider );
        
        if ( empty( $provider ) ) {
            return [
                'success' => false,
                'message' => 'Provider name is required.',
            ];
        }

        // Check if key exists and is valid
        if ( ! Nova_X_Provider_Manager::has_valid_key( $provider ) ) {
            return [
                'success' => false,
                'message' => 'No valid API key found for this provider.',
            ];
        }

        /**
         * Filter provider test result.
         * 
         * Plugins can hook into this to implement actual API testing.
         * 
         * @param array  $result Test result.
         * @param string $provider Provider slug.
         * @return array Modified test result.
         */
        $result = apply_filters( 'nova_x_test_provider_key', [
            'success' => true,
            'message' => 'API key format is valid. (Connectivity test not implemented)',
        ], $provider );

        return $result;
    }

    /**
     * Get provider configuration for external use
     *
     * @param string $provider Provider slug.
     * @return array|false Provider configuration or false on failure.
     */
    public static function get_config( $provider ) {
        $instance = self::create( $provider );
        
        if ( ! $instance ) {
            return false;
        }

        // Return safe configuration (no sensitive data)
        return [
            'slug'  => $instance['slug'],
            'label' => $instance['label'],
            'status' => $instance['status'],
            'has_key' => $instance['has_key'],
            'rules' => [
                'min_length' => $instance['rules']['min'],
                'max_length' => $instance['rules']['max'],
                'prefix'     => $instance['rules']['prefix'],
            ],
        ];
    }
}

