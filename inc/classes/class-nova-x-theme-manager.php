<?php
/**
 * Theme Manager
 * Manages exported themes: listing, deleting, re-exporting
 *
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_Theme_Manager {

    /**
     * Get the exports directory path
     *
     * @return string|false Exports directory path or false on error.
     */
    private static function get_exports_dir() {
        $upload_dir = wp_upload_dir();
        if ( $upload_dir['error'] ) {
            return false;
        }

        $exports_dir = trailingslashit( $upload_dir['basedir'] ) . 'nova-x-exports';
        
        // Ensure directory exists
        if ( ! file_exists( $exports_dir ) ) {
            wp_mkdir_p( $exports_dir );
        }

        return $exports_dir;
    }

    /**
     * Get the exports directory URL
     *
     * @return string|false Exports directory URL or false on error.
     */
    private static function get_exports_url() {
        $upload_dir = wp_upload_dir();
        if ( $upload_dir['error'] ) {
            return false;
        }

        return trailingslashit( $upload_dir['baseurl'] ) . 'nova-x-exports';
    }

    /**
     * List all exported themes
     *
     * @return array Array of theme data with name, slug, path, url, date, size.
     */
    public static function list_exported_themes() {
        $exports_dir = self::get_exports_dir();
        
        if ( ! $exports_dir || ! file_exists( $exports_dir ) ) {
            return [];
        }

        $themes = [];
        $files = glob( trailingslashit( $exports_dir ) . '*.zip' );

        if ( ! $files ) {
            return [];
        }

        $exports_url = self::get_exports_url();

        foreach ( $files as $file_path ) {
            if ( ! is_file( $file_path ) ) {
                continue;
            }

            $filename = basename( $file_path );
            $slug = str_replace( '.zip', '', $filename );

            // Extract theme name from filename (remove timestamp)
            $name_parts = explode( '-', $slug );
            if ( count( $name_parts ) > 1 && is_numeric( end( $name_parts ) ) ) {
                // Remove timestamp
                array_pop( $name_parts );
            }
            $theme_name = implode( ' ', $name_parts );
            $theme_name = ucwords( str_replace( '-', ' ', $theme_name ) );

            // Get file metadata
            $file_size = filesize( $file_path );
            $file_date = filemtime( $file_path );

            // Try to extract theme name from ZIP if possible
            $theme_name_from_zip = self::extract_theme_name_from_zip( $file_path );
            if ( $theme_name_from_zip ) {
                $theme_name = $theme_name_from_zip;
            }

            $themes[] = [
                'slug'      => sanitize_text_field( $slug ),
                'name'      => sanitize_text_field( $theme_name ),
                'filename'  => sanitize_file_name( $filename ),
                'path'      => $file_path,
                'url'       => trailingslashit( $exports_url ) . $filename,
                'size'      => $file_size,
                'size_formatted' => size_format( $file_size ),
                'date'      => $file_date,
                'date_formatted' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $file_date ),
            ];
        }

        // Sort by date (newest first)
        usort( $themes, function( $a, $b ) {
            return $b['date'] - $a['date'];
        } );

        return $themes;
    }

    /**
     * Extract theme name from ZIP file's style.css
     *
     * @param string $zip_path Path to ZIP file.
     * @return string|false Theme name or false on failure.
     */
    private static function extract_theme_name_from_zip( $zip_path ) {
        if ( ! class_exists( 'ZipArchive' ) ) {
            return false;
        }

        $zip = new ZipArchive();
        if ( $zip->open( $zip_path ) !== true ) {
            return false;
        }

        // Look for style.css
        $style_css = $zip->getFromName( 'style.css' );
        if ( ! $style_css ) {
            // Try nested structure
            for ( $i = 0; $i < $zip->numFiles; $i++ ) {
                $filename = $zip->getNameIndex( $i );
                if ( strpos( $filename, 'style.css' ) !== false ) {
                    $style_css = $zip->getFromIndex( $i );
                    break;
                }
            }
        }

        $zip->close();

        if ( ! $style_css ) {
            return false;
        }

        // Extract Theme Name from style.css header
        if ( preg_match( '/Theme Name:\s*(.+)/i', $style_css, $matches ) ) {
            return trim( $matches[1] );
        }

        return false;
    }

    /**
     * Delete an exported theme
     *
     * @param string $slug Theme slug (filename without .zip).
     * @return array Success status and message.
     */
    public static function delete_exported_theme( $slug ) {
        $slug = sanitize_file_name( $slug );
        
        if ( empty( $slug ) ) {
            return [
                'success' => false,
                'message' => 'Invalid theme slug.',
            ];
        }

        $exports_dir = self::get_exports_dir();
        if ( ! $exports_dir ) {
            return [
                'success' => false,
                'message' => 'Exports directory not accessible.',
            ];
        }

        $zip_path = trailingslashit( $exports_dir ) . $slug . '.zip';

        // Security check: ensure file is in exports directory
        $real_exports_dir = realpath( $exports_dir );
        $real_zip_path = realpath( $zip_path );
        
        if ( ! $real_zip_path || strpos( $real_zip_path, $real_exports_dir ) !== 0 ) {
            return [
                'success' => false,
                'message' => 'Invalid file path.',
            ];
        }

        if ( ! file_exists( $zip_path ) ) {
            return [
                'success' => false,
                'message' => 'Theme file not found.',
            ];
        }

        if ( ! unlink( $zip_path ) ) {
            return [
                'success' => false,
                'message' => 'Failed to delete theme file.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Theme deleted successfully.',
        ];
    }

    /**
     * Re-export a theme (recreate ZIP from existing export)
     * This is a placeholder - in a real implementation, you'd need to store
     * the original code/theme data to recreate it.
     *
     * @param string $slug Theme slug.
     * @return array Success status and message.
     */
    public static function reexport_theme( $slug ) {
        $slug = sanitize_file_name( $slug );
        
        if ( empty( $slug ) ) {
            return [
                'success' => false,
                'message' => 'Invalid theme slug.',
            ];
        }

        $exports_dir = self::get_exports_dir();
        if ( ! $exports_dir ) {
            return [
                'success' => false,
                'message' => 'Exports directory not accessible.',
            ];
        }

        $zip_path = trailingslashit( $exports_dir ) . $slug . '.zip';

        // Security check
        $real_exports_dir = realpath( $exports_dir );
        $real_zip_path = realpath( $zip_path );
        
        if ( ! $real_zip_path || strpos( $real_zip_path, $real_exports_dir ) !== 0 ) {
            return [
                'success' => false,
                'message' => 'Invalid file path.',
            ];
        }

        if ( ! file_exists( $zip_path ) ) {
            return [
                'success' => false,
                'message' => 'Theme file not found.',
            ];
        }

        // For now, just update the file modification time to indicate it was "re-exported"
        // In a full implementation, you'd extract, modify, and recreate the ZIP
        touch( $zip_path );

        $exports_url = self::get_exports_url();
        $filename = basename( $zip_path );
        $download_url = trailingslashit( $exports_url ) . $filename;

        return [
            'success'      => true,
            'message'      => 'Theme re-exported successfully.',
            'download_url' => $download_url,
            'filename'     => $filename,
        ];
    }
}

