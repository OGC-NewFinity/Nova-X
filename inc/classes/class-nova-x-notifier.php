<?php
/**
 * Nova-X Notifier Utility
 * 
 * Provides standardized notification display for admin interface
 * 
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_Notifier {

    /**
     * Display success notice
     * 
     * @param string $message Message to display
     * @param bool   $dismissible Whether notice is dismissible
     * @return string HTML output
     */
    public static function success( $message, $dismissible = true ) {
        return self::render_notice( $message, 'success', $dismissible );
    }

    /**
     * Display error notice
     * 
     * @param string $message Message to display
     * @param bool   $dismissible Whether notice is dismissible
     * @return string HTML output
     */
    public static function error( $message, $dismissible = true ) {
        return self::render_notice( $message, 'error', $dismissible );
    }

    /**
     * Display info notice
     * 
     * @param string $message Message to display
     * @param bool   $dismissible Whether notice is dismissible
     * @return string HTML output
     */
    public static function info( $message, $dismissible = true ) {
        return self::render_notice( $message, 'info', $dismissible );
    }

    /**
     * Display warning notice
     * 
     * @param string $message Message to display
     * @param bool   $dismissible Whether notice is dismissible
     * @return string HTML output
     */
    public static function warning( $message, $dismissible = true ) {
        return self::render_notice( $message, 'warning', $dismissible );
    }

    /**
     * Render notice HTML
     * 
     * @param string $message Message to display
     * @param string $type Notice type (success, error, info, warning)
     * @param bool   $dismissible Whether notice is dismissible
     * @return string HTML output
     */
    private static function render_notice( $message, $type, $dismissible ) {
        $dismissible_class = $dismissible ? ' is-dismissible' : '';
        $icon = self::get_icon( $type );
        
        $output = sprintf(
            '<div class="nova-x-notice nova-x-notice-%s%s">%s<p>%s</p>%s</div>',
            esc_attr( $type ),
            $dismissible_class,
            $icon,
            wp_kses_post( $message ),
            $dismissible ? '<button type="button" class="nova-x-notice-dismiss"><span class="screen-reader-text">' . esc_html__( 'Dismiss this notice.', 'nova-x' ) . '</span></button>' : ''
        );

        return $output;
    }

    /**
     * Get icon for notice type
     * 
     * @param string $type Notice type
     * @return string Icon HTML
     */
    private static function get_icon( $type ) {
        $icons = [
            'success' => '<span class="nova-x-notice-icon dashicons dashicons-yes-alt"></span>',
            'error'   => '<span class="nova-x-notice-icon dashicons dashicons-dismiss"></span>',
            'info'    => '<span class="nova-x-notice-icon dashicons dashicons-info"></span>',
            'warning' => '<span class="nova-x-notice-icon dashicons dashicons-warning"></span>',
        ];

        return isset( $icons[ $type ] ) ? $icons[ $type ] : '';
    }

    /**
     * Echo notice directly
     * 
     * @param string $message Message to display
     * @param string $type Notice type
     * @param bool   $dismissible Whether notice is dismissible
     */
    public static function display( $message, $type = 'info', $dismissible = true ) {
        echo self::render_notice( $message, $type, $dismissible );
    }
}

