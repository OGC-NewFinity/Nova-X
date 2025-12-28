<?php
/**
 * Theme Exporter
 * Handles exporting generated theme code as a ZIP archive
 *
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_Theme_Exporter {

    /**
     * Export theme code as ZIP archive
     *
     * @param string $site_title Theme title/name.
     * @param string $code       Generated theme code from AI.
     * @return array Response array with success status, download URL, or error message.
     */
    public static function export_theme( $site_title, $code ) {
        // Sanitize inputs
        $site_title = sanitize_text_field( $site_title );
        $code       = wp_kses_post( $code ); // Allow HTML/PHP/CSS but sanitize dangerous content
        
        if ( empty( $site_title ) || empty( $code ) ) {
            return [
                'success' => false,
                'message' => 'Site title and code are required.',
            ];
        }

        // Create safe slug from site title
        $slug = sanitize_title( $site_title );
        if ( empty( $slug ) ) {
            $slug = 'nova-x-theme';
        }
        
        // Add timestamp to make unique
        $slug = $slug . '-' . time();

        // Get uploads directory
        $upload_dir = wp_upload_dir();
        if ( $upload_dir['error'] ) {
            return [
                'success' => false,
                'message' => 'Failed to access uploads directory.',
            ];
        }

        // Create export directory
        $export_base = trailingslashit( $upload_dir['basedir'] ) . 'nova-x-exports';
        wp_mkdir_p( $export_base );

        // Create theme-specific directory
        $theme_dir = trailingslashit( $export_base ) . $slug;
        
        // Remove existing directory if it exists
        if ( file_exists( $theme_dir ) ) {
            self::delete_directory( $theme_dir );
        }
        
        wp_mkdir_p( $theme_dir );

        // Parse and create theme files
        $result = self::create_theme_files( $theme_dir, $site_title, $code );
        
        if ( ! $result['success'] ) {
            // Clean up on failure
            self::delete_directory( $theme_dir );
            return $result;
        }

        // Create ZIP archive
        $zip_result = self::create_zip_archive( $theme_dir, $slug, $export_base );
        
        // Clean up temporary directory
        self::delete_directory( $theme_dir );

        if ( ! $zip_result['success'] ) {
            return $zip_result;
        }

        // Return download URL
        $download_url = trailingslashit( $upload_dir['baseurl'] ) . 'nova-x-exports/' . $zip_result['filename'];

        return [
            'success'      => true,
            'download_url' => $download_url,
            'filename'     => $zip_result['filename'],
            'message'      => 'Theme exported successfully.',
        ];
    }

    /**
     * Create theme files from generated code
     *
     * @param string $theme_dir  Theme directory path.
     * @param string $site_title Theme title.
     * @param string $code       Generated code.
     * @return array Success status and message.
     */
    private static function create_theme_files( $theme_dir, $site_title, $code ) {
        // Parse code to extract different file types
        $files = self::parse_code_into_files( $code, $site_title );

        // Write each file
        foreach ( $files as $filename => $content ) {
            $file_path = trailingslashit( $theme_dir ) . $filename;
            $written   = file_put_contents( $file_path, $content );

            if ( false === $written ) {
                return [
                    'success' => false,
                    'message' => "Failed to write file: {$filename}",
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'Theme files created successfully.',
        ];
    }

    /**
     * Parse generated code into separate theme files
     *
     * @param string $code       Generated code.
     * @param string $site_title Theme title.
     * @return array Array of filename => content.
     */
    private static function parse_code_into_files( $code, $site_title ) {
        $files = [];

        // Extract CSS (between <style> tags or in style blocks)
        $css_content = '';
        if ( preg_match( '/<style[^>]*>(.*?)<\/style>/is', $code, $css_matches ) ) {
            $css_content = trim( $css_matches[1] );
        } elseif ( preg_match( '/```css\s*(.*?)\s*```/is', $code, $css_matches ) ) {
            $css_content = trim( $css_matches[1] );
        }

        // Extract PHP/HTML (everything else or between <?php tags)
        $php_content = $code;
        if ( preg_match( '/```php\s*(.*?)\s*```/is', $code, $php_matches ) ) {
            $php_content = trim( $php_matches[1] );
        } elseif ( preg_match( '/<\?php\s*(.*?)\s*\?>/is', $code, $php_matches ) ) {
            $php_content = '<?php' . "\n" . trim( $php_matches[1] ) . "\n" . '?>';
        }

        // Create style.css with theme header
        $style_header = "/*
Theme Name: {$site_title}
Author: Nova-X
Version: 0.1.0
Description: AI-generated WordPress theme
*/";
        
        $files['style.css'] = $style_header . "\n\n" . ( ! empty( $css_content ) ? $css_content : "body {\n\tfont-family: system-ui, Arial, sans-serif;\n\tmargin: 0;\n\tpadding: 0;\n}\n" );

        // Create functions.php
        $functions_content = "<?php\n";
        $functions_content .= "if ( ! defined( 'ABSPATH' ) ) {\n";
        $functions_content .= "\texit;\n";
        $functions_content .= "}\n\n";
        $functions_content .= "// Nova-X Theme Functions\n";
        
        // Extract functions if present in code
        if ( preg_match( '/function\s+\w+\s*\([^)]*\)\s*\{[^}]*\}/is', $php_content, $func_matches ) ) {
            $functions_content .= "\n" . $func_matches[0] . "\n";
        }
        
        $files['functions.php'] = $functions_content;

        // Create index.php
        $index_content = "<?php\n";
        $index_content .= "get_header();\n";
        $index_content .= "?>\n\n";
        
        // Extract HTML content
        $html_content = '';
        if ( preg_match( '/<body[^>]*>(.*?)<\/body>/is', $code, $html_matches ) ) {
            $html_content = trim( $html_matches[1] );
        } elseif ( preg_match( '/```html\s*(.*?)\s*```/is', $code, $html_matches ) ) {
            $html_content = trim( $html_matches[1] );
        } else {
            // Use PHP content as HTML if it contains HTML-like content
            $html_content = strip_tags( $php_content, '<div><p><h1><h2><h3><h4><h5><h6><a><img><ul><ol><li><span><strong><em><br>' );
        }
        
        if ( empty( $html_content ) ) {
            $html_content = "<h1>Welcome to {$site_title}</h1>\n<p>This theme was generated by Nova-X.</p>";
        }
        
        $index_content .= $html_content . "\n\n";
        $index_content .= "<?php\n";
        $index_content .= "get_footer();\n";
        $index_content .= "?>\n";
        
        $files['index.php'] = $index_content;

        return $files;
    }

    /**
     * Create ZIP archive from theme directory
     *
     * @param string $theme_dir   Theme directory path.
     * @param string $slug        Theme slug.
     * @param string $export_base Export base directory.
     * @return array Success status, filename, or error message.
     */
    private static function create_zip_archive( $theme_dir, $slug, $export_base ) {
        // Check if ZipArchive is available
        if ( ! class_exists( 'ZipArchive' ) ) {
            return [
                'success' => false,
                'message' => 'ZipArchive class is not available on this server.',
            ];
        }

        $zip_filename = $slug . '.zip';
        $zip_path     = trailingslashit( $export_base ) . $zip_filename;

        // Remove existing ZIP if it exists
        if ( file_exists( $zip_path ) ) {
            unlink( $zip_path );
        }

        $zip = new ZipArchive();
        
        if ( $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
            return [
                'success' => false,
                'message' => 'Failed to create ZIP archive.',
            ];
        }

        // Add all files from theme directory
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $theme_dir ),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ( $files as $file ) {
            if ( ! $file->isDir() ) {
                $file_path     = $file->getRealPath();
                $relative_path = substr( $file_path, strlen( $theme_dir ) + 1 );
                
                $zip->addFile( $file_path, $relative_path );
            }
        }

        $zip->close();

        if ( ! file_exists( $zip_path ) ) {
            return [
                'success' => false,
                'message' => 'ZIP archive was not created successfully.',
            ];
        }

        return [
            'success'  => true,
            'filename' => $zip_filename,
            'message'  => 'ZIP archive created successfully.',
        ];
    }

    /**
     * Recursively delete directory
     *
     * @param string $dir Directory path.
     * @return bool Success status.
     */
    private static function delete_directory( $dir ) {
        if ( ! file_exists( $dir ) ) {
            return true;
        }

        if ( ! is_dir( $dir ) ) {
            return unlink( $dir );
        }

        $files = array_diff( scandir( $dir ), [ '.', '..' ] );
        
        foreach ( $files as $file ) {
            $file_path = trailingslashit( $dir ) . $file;
            if ( is_dir( $file_path ) ) {
                self::delete_directory( $file_path );
            } else {
                unlink( $file_path );
            }
        }

        return rmdir( $dir );
    }
}

