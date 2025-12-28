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
    ];

    /**
     * Log usage for a provider call
     *
     * @param string $provider    Provider name (openai, anthropic, groq, mistral).
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

        // Get current totals
        $total_tokens = absint( get_option( 'nova_x_total_tokens_used', 0 ) );
        $total_cost = floatval( get_option( 'nova_x_total_cost_usd', 0 ) );

        // Calculate cost for this usage
        $cost = self::calculate_cost( $provider, $tokens_used );

        // Update totals
        $total_tokens += $tokens_used;
        $total_cost += $cost;

        // Save updated totals
        update_option( 'nova_x_total_tokens_used', $total_tokens, false );
        update_option( 'nova_x_total_cost_usd', $total_cost, false );

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
     * Reset tracker (clear all totals)
     *
     * @return bool Success status.
     */
    public static function reset_tracker() {
        delete_option( 'nova_x_total_tokens_used' );
        delete_option( 'nova_x_total_cost_usd' );
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

