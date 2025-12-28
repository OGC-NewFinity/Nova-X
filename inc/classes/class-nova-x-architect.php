<?php
/**
 * Theme Building Logic
 * Handles theme architecture and generation
 *
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_Architect {

    /**
     * WordPress themes directory path
     *
     * @var string
     */
    private $themes_dir;

    /**
     * Constructor
     */
    public function __construct() {
        $this->themes_dir = WP_CONTENT_DIR . '/themes/';
    }

    /**
     * Build theme from user input
     *
     * @param string $site_title Theme name/title.
     * @param string $prompt     User prompt/description.
     * @return array Response array with success status and message.
     */
    public function build_theme( $site_title, $prompt ) {
        // Sanitize user inputs.
        $site_title = sanitize_text_field( $site_title );
        $prompt     = sanitize_textarea_field( $prompt );

        // Create safe slug from site title.
        $slug = sanitize_title( $site_title );
        if ( empty( $slug ) ) {
            $slug = 'nova-x-theme';
        }

        // Build target directory path.
        $target_dir = trailingslashit( $this->themes_dir ) . $slug;

        // Check if theme folder already exists.
        if ( file_exists( $target_dir ) ) {
            return [
                'success' => false,
                'message' => 'Theme folder already exists: ' . $slug,
            ];
        }

        // Create theme directory.
        wp_mkdir_p( $target_dir );

        // Generate theme files.
        $result = $this->generate_files( $target_dir, $site_title, $prompt );

        return $result;
    }

    /**
     * Generate theme files (style.css, functions.php, index.php)
     *
     * @param string $target_dir Target directory path.
     * @param string $site_title Theme name/title.
     * @param string $prompt     User prompt/description.
     * @return array Response array with success status and message.
     */
    private function generate_files( $target_dir, $site_title, $prompt ) {
        // Generate style.css content.
        $style_css = "/*
Theme Name: {$site_title}
Author: Nova-X
Version: 0.1.0
*/\n\nbody{font-family:system-ui,Arial,sans-serif;margin:0;padding:0;}\n";

        // Generate functions.php content.
        $functions_php = "<?php\nif(!defined('ABSPATH')){exit;}\n// Nova-X Theme Functions\n";

        // Generate index.php content.
        $index_php = "<?php\nget_header();\n?>\n<h1>Welcome to {$site_title}</h1>\n<?php get_footer(); ?>";

        // Define files to create.
        $files = [
            'style.css'     => $style_css,
            'functions.php' => $functions_php,
            'index.php'     => $index_php,
        ];

        // Write each file and validate success.
        foreach ( $files as $filename => $content ) {
            $file_path = trailingslashit( $target_dir ) . $filename;
            $written   = file_put_contents( $file_path, $content );

            if ( false === $written ) {
                return [
                    'success' => false,
                    'message' => "Failed to write: {$filename}",
                ];
            }
        }

        // Return success response.
        return [
            'success' => true,
            'slug'    => basename( $target_dir ),
            'message' => 'Theme successfully generated.',
        ];
    }
}

