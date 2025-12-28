<?php
/**
 * Usage Tracker
 * Tracks AI provider token usage and costs
 *
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_Usage_Tracker {

    /**
     * Cost per 1,000 tokens by provider (in USD)
     *
     * @var array
     */
    private static $cost_per_1k_tokens = [
        'openai'    => 0.01,    // GPT-4: $0.01 per 1K tokens
        'anthropic' => 0.008,   // Claude 2.1: $0.008 per 1K tokens
        'groq'      => 0.002,  // Mixtral: $0.002 per 1K tokens
        'mistral'   => 0.0027, // Mistral Large: $0.0027 per 1K tokens
        'gemini'    => 0.005,  // Gemini Pro: $0.005 per 1K tokens
    ];

    /**
     * Log usage for a provider call
     *
     * @param string $provider    Provider name (openai, anthropic, groq, mistral, gemini).
     * @param int    $tokens_used Number of tokens used.
     * @return bool Success status.
     */
    public static function log_usage( $provider, $tokens_used ) {
        // Sanitize provider name
        $provider = sanitize_text_field( $provider );
        $tokens_used = absint( $tokens_used );

        if ( empty( $provider ) || $tokens_used <= 0 ) {
            return false;
        }

        // Normalize provider name
        $provider = strtolower( $provider );
        if ( 'claude' === $provider ) {
            $provider = 'anthropic';
        }

        // Get current totals
        $total_tokens = absint( get_option( 'nova_x_total_tokens_used', 0 ) );
        $total_cost = floatval( get_option( 'nova_x_total_cost_usd', 0 ) );

        // Calculate cost for this usage
        $cost = self::calculate_cost( $provider, $tokens_used );

        // Update global totals
        $total_tokens += $tokens_used;
        $total_cost += $cost;

        // Get per-provider usage data
        $provider_usage = get_option( 'nova_x_provider_usage', [] );
        if ( ! is_array( $provider_usage ) ) {
            $provider_usage = [];
        }

        // Initialize provider if not exists
        if ( ! isset( $provider_usage[ $provider ] ) ) {
            $provider_usage[ $provider ] = [
                'tokens' => 0,
                'cost'   => 0.0,
            ];
        }

        // Update per-provider totals
        $provider_usage[ $provider ]['tokens'] = absint( $provider_usage[ $provider ]['tokens'] ) + $tokens_used;
        $provider_usage[ $provider ]['cost'] = floatval( $provider_usage[ $provider ]['cost'] ) + $cost;

        // Save updated data
        update_option( 'nova_x_total_tokens_used', $total_tokens, false );
        update_option( 'nova_x_total_cost_usd', $total_cost, false );
        update_option( 'nova_x_provider_usage', $provider_usage, false );

        return true;
    }

    /**
     * Calculate cost based on provider and tokens
     *
     * @param string $provider    Provider name.
     * @param int    $tokens_used Number of tokens used.
     * @return float Cost in USD.
     */
    private static function calculate_cost( $provider, $tokens_used ) {
        // Normalize provider name
        $provider = strtolower( $provider );
        
        // Handle claude -> anthropic mapping
        if ( 'claude' === $provider ) {
            $provider = 'anthropic';
        }

        // Get cost per 1K tokens for this provider
        $cost_per_1k = isset( self::$cost_per_1k_tokens[ $provider ] ) 
            ? self::$cost_per_1k_tokens[ $provider ] 
            : 0.01; // Default to OpenAI rate if unknown

        // Calculate cost: (tokens / 1000) * cost_per_1k
        $cost = ( $tokens_used / 1000 ) * $cost_per_1k;

        return round( $cost, 4 ); // Round to 4 decimal places
    }

    /**
     * Get total tokens used
     *
     * @return int Total tokens used.
     */
    public static function get_total_tokens() {
        return absint( get_option( 'nova_x_total_tokens_used', 0 ) );
    }

    /**
     * Get total cost in USD
     *
     * @return float Total cost in USD.
     */
    public static function get_total_cost() {
        return floatval( get_option( 'nova_x_total_cost_usd', 0 ) );
    }

    /**
     * Get tokens used for a specific provider
     *
     * @param string $provider Provider name.
     * @return int Tokens used for the provider.
     */
    public static function get_provider_tokens( $provider ) {
        $provider = strtolower( sanitize_text_field( $provider ) );
        if ( 'claude' === $provider ) {
            $provider = 'anthropic';
        }

        $provider_usage = get_option( 'nova_x_provider_usage', [] );
        if ( ! is_array( $provider_usage ) || ! isset( $provider_usage[ $provider ] ) ) {
            return 0;
        }

        return absint( $provider_usage[ $provider ]['tokens'] );
    }

    /**
     * Get cost for a specific provider
     *
     * @param string $provider Provider name.
     * @return float Cost in USD for the provider.
     */
    public static function get_provider_cost( $provider ) {
        $provider = strtolower( sanitize_text_field( $provider ) );
        if ( 'claude' === $provider ) {
            $provider = 'anthropic';
        }

        $provider_usage = get_option( 'nova_x_provider_usage', [] );
        if ( ! is_array( $provider_usage ) || ! isset( $provider_usage[ $provider ] ) ) {
            return 0.0;
        }

        return floatval( $provider_usage[ $provider ]['cost'] );
    }

    /**
     * Get all provider usage data (chart-ready format)
     *
     * @return array Array of provider data with tokens and cost.
     */
    public static function get_all_provider_data() {
        $provider_usage = get_option( 'nova_x_provider_usage', [] );
        if ( ! is_array( $provider_usage ) ) {
            return [];
        }

        // Ensure all providers are included with default values
        $all_providers = array_keys( self::$cost_per_1k_tokens );
        $result = [];

        foreach ( $all_providers as $provider ) {
            $result[ $provider ] = [
                'tokens' => isset( $provider_usage[ $provider ] ) 
                    ? absint( $provider_usage[ $provider ]['tokens'] ) 
                    : 0,
                'cost'   => isset( $provider_usage[ $provider ] ) 
                    ? floatval( $provider_usage[ $provider ]['cost'] ) 
                    : 0.0,
                'label'  => self::get_provider_label( $provider ),
            ];
        }

        return $result;
    }

    /**
     * Get human-readable provider label
     *
     * @param string $provider Provider name.
     * @return string Provider label.
     */
    private static function get_provider_label( $provider ) {
        $labels = [
            'openai'    => 'OpenAI (GPT-4)',
            'anthropic' => 'Anthropic (Claude)',
            'groq'      => 'Groq (Mixtral)',
            'mistral'   => 'Mistral (Large)',
            'gemini'    => 'Google Gemini',
        ];

        return isset( $labels[ $provider ] ) ? $labels[ $provider ] : ucfirst( $provider );
    }

    /**
     * Reset tracker (clear all totals and per-provider data)
     *
     * @return bool Success status.
     */
    public static function reset_tracker() {
        delete_option( 'nova_x_total_tokens_used' );
        delete_option( 'nova_x_total_cost_usd' );
        delete_option( 'nova_x_provider_usage' );
        return true;
    }

    /**
     * Get formatted total tokens (with number formatting)
     *
     * @return string Formatted token count.
     */
    public static function get_formatted_tokens() {
        return number_format( self::get_total_tokens() );
    }

    /**
     * Get formatted total cost (with currency formatting)
     *
     * @return string Formatted cost.
     */
    public static function get_formatted_cost() {
        return number_format( self::get_total_cost(), 2 );
    }
}

