<?php
/**
 * Theme Generator Class
 * Handles the creation of the theme folder and files.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_Generator {

    private $themes_dir;

    public function __construct() {
        $this->themes_dir = WP_CONTENT_DIR . '/themes/';
    }

    /**
     * Main function to build the theme
     * @param string $slug The folder name (e.g., 'nova-genesis')
     * @param array $data The theme structure (json, html, css)
     */
    public function build_theme( $slug, $data ) {
        $slug = sanitize_title( $slug );
        $target_dir = $this->themes_dir . $slug;

        // 1. Create Directory Structure
        if ( ! file_exists( $target_dir ) ) {
            wp_mkdir_p( $target_dir );
            wp_mkdir_p( $target_dir . '/templates' );
            wp_mkdir_p( $target_dir . '/parts' );
        }

        // 2. Create style.css (Required for WP to see the theme)
        $style_content = "/*\nTheme Name: Nova Generated ($slug)\nAuthor: Nova-X AI\nVersion: 1.0\n*/";
        $this->write_file( $target_dir . '/style.css', $style_content );

        // 3. Create theme.json (The Brain of Block Themes)
        if ( ! empty( $data['theme_json'] ) ) {
            $this->write_file( $target_dir . '/theme.json', json_encode( $data['theme_json'], JSON_PRETTY_PRINT ) );
        }

        // 4. Create index.html (The Homepage)
        if ( ! empty( $data['index_html'] ) ) {
            $this->write_file( $target_dir . '/templates/index.html', $data['index_html'] );
        }

        return [
            'success' => true,
            'message' => 'Theme built successfully!',
            'path'    => $target_dir
        ];
    }

    private function write_file( $path, $content ) {
        $handle = fopen( $path, 'w' );
        if ( $handle ) {
            fwrite( $handle, $content );
            fclose( $handle );
        }
    }
}