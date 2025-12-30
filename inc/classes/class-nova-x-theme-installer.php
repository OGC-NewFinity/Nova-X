<?php
/**
 * Theme Installer
 * Handles theme preview and installation
 *
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_Theme_Installer {

    /**
     * Preview theme from ZIP URL
     *
     * @param string $zip_url ZIP file URL.
     * @return array Response array with success status, preview URL, or error message.
     */
    public static function preview_theme( $zip_url ) {
        // Sanitize input
        $zip_url = esc_url_raw( $zip_url );
        
        if ( empty( $zip_url ) ) {
            return [
                'success' => false,
                'message' => 'ZIP URL is required.',
            ];
        }

        // Download and extract ZIP
        $extract_result = self::extract_theme_zip( $zip_url );
        
        if ( ! $extract_result['success'] ) {
            return $extract_result;
        }

        $theme_dir = $extract_result['theme_dir'];
        $theme_slug = $extract_result['slug'];

        // Move to themes directory with preview prefix
        $preview_slug = 'nova-x-preview-' . $theme_slug;
        $themes_dir = WP_CONTENT_DIR . '/themes/';
        $preview_dir = trailingslashit( $themes_dir ) . $preview_slug;

        // Remove existing preview if it exists
        if ( file_exists( $preview_dir ) ) {
            self::delete_directory( $preview_dir );
        }

        // Copy theme to preview directory
        if ( ! self::copy_directory( $theme_dir, $preview_dir ) ) {
            // Clean up
            self::delete_directory( $theme_dir );
            error_log( '[Nova-X] Theme preview failed - Could not copy theme to preview directory for slug ' . $theme_slug . ' — User ID: ' . get_current_user_id() );
            return [
                'success' => false,
                'message' => 'Failed to copy theme to preview directory. Please check file permissions.',
            ];
        }

        // Clean up temporary directory
        self::delete_directory( $theme_dir );

        // Generate preview URL
        $preview_url = admin_url( 'customize.php?theme=' . $preview_slug );

        return [
            'success'     => true,
            'preview_url' => $preview_url,
            'theme_slug'  => $preview_slug,
            'message'     => 'Theme preview ready.',
        ];
    }

    /**
     * Install and activate theme from ZIP URL
     *
     * @param string $zip_url ZIP file URL.
     * @return array Response array with success status and message.
     */
    public static function install_theme( $zip_url ) {
        // Sanitize input
        $zip_url = esc_url_raw( $zip_url );
        
        if ( empty( $zip_url ) ) {
            return [
                'success' => false,
                'message' => 'ZIP URL is required.',
            ];
        }

        // Download and extract ZIP
        $extract_result = self::extract_theme_zip( $zip_url );
        
        if ( ! $extract_result['success'] ) {
            return $extract_result;
        }

        $theme_dir = $extract_result['theme_dir'];
        $theme_slug = $extract_result['slug'];

        // Move to themes directory
        $themes_dir = WP_CONTENT_DIR . '/themes/';
        $install_dir = trailingslashit( $themes_dir ) . $theme_slug;

        // Check if theme already exists
        if ( file_exists( $install_dir ) ) {
            // Clean up temporary directory
            self::delete_directory( $theme_dir );
            return [
                'success' => false,
                'message' => 'Theme already exists. Please delete it first or use a different name.',
            ];
        }

        // Copy theme to themes directory
        if ( ! self::copy_directory( $theme_dir, $install_dir ) ) {
            // Clean up
            self::delete_directory( $theme_dir );
            error_log( '[Nova-X] Theme installation failed - Could not copy theme to themes directory for slug ' . $theme_slug . ' — User ID: ' . get_current_user_id() );
            return [
                'success' => false,
                'message' => 'Failed to install theme. Please check file permissions.',
            ];
        }

        // Clean up temporary directory
        self::delete_directory( $theme_dir );

        // Validate theme
        $theme = wp_get_theme( $theme_slug );
        if ( ! $theme->exists() ) {
            // Clean up installed theme
            self::delete_directory( $install_dir );
            error_log( '[Nova-X] Theme installation failed - Installed theme is not valid for slug ' . $theme_slug . ' — User ID: ' . get_current_user_id() );
            return [
                'success' => false,
                'message' => 'Installed theme is not valid. The theme may be missing required files.',
            ];
        }

        // Activate theme
        switch_theme( $theme_slug );

        return [
            'success'     => true,
            'theme_slug'  => $theme_slug,
            'theme_name'  => $theme->get( 'Name' ),
            'message'     => 'Theme installed and activated successfully.',
        ];
    }

    /**
     * Extract theme ZIP file
     *
     * @param string $zip_url ZIP file URL or local path.
     * @return array Response array with success status, theme directory, and slug.
     */
    private static function extract_theme_zip( $zip_url ) {
        // Get uploads directory for temporary storage
        $upload_dir = wp_upload_dir();
        if ( $upload_dir['error'] ) {
            error_log( '[Nova-X] Theme installation failed - Cannot access uploads directory — User ID: ' . get_current_user_id() );
            return [
                'success' => false,
                'message' => 'Failed to access uploads directory. Please check file permissions.',
            ];
        }

        // Check if it's a local file path (convert URL to path if it's in uploads)
        $zip_path = '';
        $upload_dir = wp_upload_dir();
        $upload_url = $upload_dir['baseurl'];
        $upload_path = $upload_dir['basedir'];
        
        // If URL is in uploads directory, convert to local path
        if ( strpos( $zip_url, $upload_url ) === 0 ) {
            $relative_path = str_replace( $upload_url, '', $zip_url );
            $zip_path = trailingslashit( $upload_path ) . ltrim( $relative_path, '/' );
        } elseif ( file_exists( $zip_url ) && is_file( $zip_url ) ) {
            // It's already a local file path
            $zip_path = $zip_url;
        }
        
        if ( empty( $zip_path ) || ! file_exists( $zip_path ) ) {
            // It's a remote URL - download it
            // It's a URL - download it
            $zip_content = wp_remote_get( $zip_url );
            
            if ( is_wp_error( $zip_content ) ) {
                $error_msg = $zip_content->get_error_message();
                error_log( '[Nova-X] Theme installation failed - Download error: ' . $error_msg . ' — User ID: ' . get_current_user_id() );
                return [
                    'success' => false,
                    'message' => 'Failed to download theme file. Please check the URL and try again.',
                ];
            }

            $zip_body = wp_remote_retrieve_body( $zip_content );
            if ( empty( $zip_body ) ) {
                error_log( '[Nova-X] Theme installation failed - ZIP file is empty — User ID: ' . get_current_user_id() );
                return [
                    'success' => false,
                    'message' => 'ZIP file is empty.',
                ];
            }

            // Save ZIP to temporary file
            $temp_dir = trailingslashit( $upload_dir['basedir'] ) . 'nova-x-temp';
            if ( ! wp_mkdir_p( $temp_dir ) ) {
                error_log( '[Nova-X] Theme installation failed - Cannot create temp directory: ' . $temp_dir . ' — User ID: ' . get_current_user_id() );
                return [
                    'success' => false,
                    'message' => 'Failed to create temporary directory. Please check file permissions.',
                ];
            }
            
            $zip_path = trailingslashit( $temp_dir ) . 'theme-' . time() . '.zip';
            $written = file_put_contents( $zip_path, $zip_body );
            if ( false === $written ) {
                error_log( '[Nova-X] Theme installation failed - Cannot write ZIP file: ' . $zip_path . ' — User ID: ' . get_current_user_id() );
                return [
                    'success' => false,
                    'message' => 'Failed to save downloaded file. Please check file permissions.',
                ];
            }
        }

        // If we downloaded the file, we already have $zip_path set above
        // If it was a local file, $zip_path is already set
        if ( empty( $zip_path ) || ! file_exists( $zip_path ) ) {
            error_log( '[Nova-X] Theme installation failed - ZIP file not found — User ID: ' . get_current_user_id() );
            return [
                'success' => false,
                'message' => 'ZIP file not found.',
            ];
        }

        // Check if ZipArchive is available
        if ( ! class_exists( 'ZipArchive' ) ) {
            // Clean up if we created a temp file
            if ( $zip_path !== $zip_url && file_exists( $zip_path ) ) {
                unlink( $zip_path );
            }
            error_log( '[Nova-X] Theme installation failed - ZipArchive class not available — User ID: ' . get_current_user_id() );
            return [
                'success' => false,
                'message' => 'ZIP archive functionality is not available on this server. Please contact your hosting provider.',
            ];
        }

        // Get temp directory for extraction
        $temp_dir = trailingslashit( $upload_dir['basedir'] ) . 'nova-x-temp';
        if ( ! wp_mkdir_p( $temp_dir ) ) {
            error_log( '[Nova-X] Theme installation failed - Cannot create temp directory: ' . $temp_dir . ' — User ID: ' . get_current_user_id() );
            return [
                'success' => false,
                'message' => 'Failed to create temporary directory. Please check file permissions.',
            ];
        }

        // Extract ZIP
        $zip = new ZipArchive();
        try {
            $zip_result = $zip->open( $zip_path );
            if ( $zip_result !== true ) {
                // Clean up if we created a temp file
                if ( $zip_path !== $zip_url && file_exists( $zip_path ) ) {
                    unlink( $zip_path );
                }
                error_log( '[Nova-X] Theme installation failed - Could not open ZIP file: ' . basename( $zip_path ) . ' (error code: ' . $zip_result . ') — User ID: ' . get_current_user_id() );
                return [
                    'success' => false,
                    'message' => 'Failed to open theme ZIP file. The file may be corrupted.',
                ];
            }
        } catch ( Exception $e ) {
            // Clean up if we created a temp file
            if ( $zip_path !== $zip_url && file_exists( $zip_path ) ) {
                unlink( $zip_path );
            }
            error_log( '[Nova-X] Theme installation failed - Exception opening ZIP file: ' . basename( $zip_path ) . ' - ' . $e->getMessage() . ' — User ID: ' . get_current_user_id() );
            return [
                'success' => false,
                'message' => 'Failed to open theme ZIP file. The file may be corrupted.',
            ];
        }

        // Create extraction directory
        $extract_dir = trailingslashit( $temp_dir ) . 'extract-' . time();
        if ( ! wp_mkdir_p( $extract_dir ) ) {
            $zip->close();
            error_log( '[Nova-X] Theme installation failed - Cannot create extraction directory: ' . $extract_dir . ' — User ID: ' . get_current_user_id() );
            return [
                'success' => false,
                'message' => 'Failed to create extraction directory. Please check file permissions.',
            ];
        }

        // Extract all files
        try {
            if ( ! $zip->extractTo( $extract_dir ) ) {
                $zip->close();
                error_log( '[Nova-X] Theme installation failed - Could not extract ZIP file: ' . basename( $zip_path ) . ' — User ID: ' . get_current_user_id() );
                return [
                    'success' => false,
                    'message' => 'Failed to extract theme files. Please check file permissions.',
                ];
            }
            $zip->close();
        } catch ( Exception $e ) {
            $zip->close();
            error_log( '[Nova-X] Theme installation failed - Exception extracting ZIP file: ' . basename( $zip_path ) . ' - ' . $e->getMessage() . ' — User ID: ' . get_current_user_id() );
            return [
                'success' => false,
                'message' => 'Failed to extract theme files. Please check file permissions.',
            ];
        }

        // Remove temporary ZIP if we created it
        if ( $zip_path !== $zip_url && file_exists( $zip_path ) ) {
            if ( ! unlink( $zip_path ) ) {
                error_log( '[Nova-X] Failed to delete temporary ZIP file: ' . $zip_path . ' — User ID: ' . get_current_user_id() );
            }
        }

        // Find theme directory (handle nested ZIP structure)
        $theme_dir = self::find_theme_directory( $extract_dir );
        
        if ( ! $theme_dir ) {
            self::delete_directory( $extract_dir );
            error_log( '[Nova-X] Theme installation failed - Theme directory not found in ZIP file — User ID: ' . get_current_user_id() );
            return [
                'success' => false,
                'message' => 'Could not find theme directory in ZIP file. The ZIP may be incorrectly structured.',
            ];
        }

        // Get theme slug from directory name
        $slug = basename( $theme_dir );

        return [
            'success'   => true,
            'theme_dir' => $theme_dir,
            'slug'      => $slug,
        ];
    }

    /**
     * Find theme directory in extracted files
     *
     * @param string $extract_dir Extraction directory.
     * @return string|false Theme directory path or false if not found.
     */
    private static function find_theme_directory( $extract_dir ) {
        // Check if extract_dir itself contains style.css
        if ( file_exists( trailingslashit( $extract_dir ) . 'style.css' ) ) {
            return $extract_dir;
        }

        // Look for subdirectories with style.css
        $items = scandir( $extract_dir );
        foreach ( $items as $item ) {
            if ( $item === '.' || $item === '..' ) {
                continue;
            }

            $item_path = trailingslashit( $extract_dir ) . $item;
            if ( is_dir( $item_path ) && file_exists( trailingslashit( $item_path ) . 'style.css' ) ) {
                return $item_path;
            }
        }

        return false;
    }

    /**
     * Copy directory recursively
     *
     * @param string $source Source directory.
     * @param string $dest   Destination directory.
     * @return bool Success status.
     */
    private static function copy_directory( $source, $dest ) {
        if ( ! is_dir( $source ) ) {
            error_log( '[Nova-X] Copy directory failed - Source is not a directory: ' . $source . ' — User ID: ' . get_current_user_id() );
            return false;
        }

        if ( ! is_dir( $dest ) ) {
            if ( ! wp_mkdir_p( $dest ) ) {
                error_log( '[Nova-X] Copy directory failed - Cannot create destination directory: ' . $dest . ' — User ID: ' . get_current_user_id() );
                return false;
            }
        }

        $files = array_diff( scandir( $source ), [ '.', '..' ] );

        foreach ( $files as $file ) {
            $source_file = trailingslashit( $source ) . $file;
            $dest_file   = trailingslashit( $dest ) . $file;

            if ( is_dir( $source_file ) ) {
                if ( ! self::copy_directory( $source_file, $dest_file ) ) {
                    return false;
                }
            } else {
                if ( ! copy( $source_file, $dest_file ) ) {
                    error_log( '[Nova-X] Copy directory failed - Cannot copy file: ' . $source_file . ' to ' . $dest_file . ' — User ID: ' . get_current_user_id() );
                    return false;
                }
            }
        }

        return true;
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
            $result = unlink( $dir );
            if ( ! $result ) {
                error_log( '[Nova-X] Failed to delete file: ' . $dir . ' — User ID: ' . get_current_user_id() );
            }
            return $result;
        }

        $files = array_diff( scandir( $dir ), [ '.', '..' ] );
        
        foreach ( $files as $file ) {
            $file_path = trailingslashit( $dir ) . $file;
            if ( is_dir( $file_path ) ) {
                self::delete_directory( $file_path );
            } else {
                if ( ! unlink( $file_path ) ) {
                    error_log( '[Nova-X] Failed to delete file: ' . $file_path . ' — User ID: ' . get_current_user_id() );
                }
            }
        }

        $result = rmdir( $dir );
        if ( ! $result ) {
            error_log( '[Nova-X] Failed to delete directory: ' . $dir . ' — User ID: ' . get_current_user_id() );
        }
        return $result;
    }
}

