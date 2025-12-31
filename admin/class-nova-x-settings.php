<?php
/**
 * Settings Page for Nova-X Plugin
 *
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        // Register settings menu with priority 20 to ensure it runs after parent menu (priority 10 default)
        add_action( 'admin_menu', [ $this, 'register_settings_menu' ], 20 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_settings_assets' ] );
    }

    /**
     * Register settings menu page as submenu under Nova-X Dashboard
     */
    public function register_settings_menu() {
        // Safety check: Ensure parent menu exists before registering submenu
        // This is a failsafe in case hook priorities don't work as expected
        // Check if the parent menu hook is registered
        if ( empty( $GLOBALS['admin_page_hooks']['nova-x-dashboard'] ) ) {
            // Parent menu doesn't exist yet - skip registration (should not happen with priority 20)
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[Nova-X Settings] Parent menu not found, skipping submenu registration. This should not happen with proper hook priority.' );
            }
            return;
        }
        
        add_submenu_page(
            'nova-x-dashboard',                    // Parent slug (matches Nova_X_Admin main menu)
            esc_html__( 'Nova-X Settings', 'nova-x' ), // Page title
            esc_html__( 'Settings', 'nova-x' ),    // Menu label
            'manage_options',                      // Capability
            'nova-x-settings',                     // Menu slug
            [ $this, 'render_settings_page' ]      // Callback
        );
        
        // Debug logging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Nova-X Settings] Menu registered successfully: nova-x-settings under nova-x-dashboard' );
        }
    }

    /**
     * Enqueue CSS and JS for settings page
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_settings_assets( $hook ) {
        // Get current screen to verify we're on the settings page
        $screen = get_current_screen();
        
        // Only load on settings page (submenu screen ID format: parent-slug_page_menu-slug)
        if ( ! $screen || $screen->id !== 'nova-x_page_nova-x-settings' ) {
            return;
        }

        // Get plugin URL and version
        $plugin_url = defined( 'NOVA_X_URL' ) ? NOVA_X_URL : plugin_dir_url( dirname( __FILE__ ) );
        $plugin_version = defined( 'NOVA_X_VERSION' ) ? NOVA_X_VERSION : '1.0.0';

        // Enqueue CSS
        wp_enqueue_style(
            'nova-x-settings',
            $plugin_url . 'admin/assets/css/nova-x-settings.css',
            [],
            $plugin_version
        );

        // Enqueue Auth CSS
        wp_enqueue_style(
            'nova-x-auth',
            $plugin_url . 'admin/assets/css/nova-x-auth.css',
            [],
            $plugin_version
        );

        // Enqueue JS
        wp_enqueue_script(
            'nova-x-settings',
            $plugin_url . 'admin/assets/js/nova-x-settings.js',
            [ 'jquery' ],
            $plugin_version,
            true
        );

        // Enqueue Auth JS
        wp_enqueue_script(
            'nova-x-auth',
            $plugin_url . 'admin/assets/js/nova-x-auth.js',
            [],
            $plugin_version,
            true
        );

        // Localize auth script with REST API URL
        wp_localize_script(
            'nova-x-auth',
            'novaXAuth',
            [
                'restUrl' => esc_url_raw( rest_url( 'nova-x/v1' ) ),
            ]
        );

        // Localize script with provider data
        $providers_data = [];
        $supported_providers = Nova_X_Provider_Manager::get_supported_providers();
        
        foreach ( $supported_providers as $provider ) {
            $status = Nova_X_Provider_Manager::get_provider_status( $provider );
            $providers_data[ $provider ] = [
                'masked_key' => $status['masked_key'],
                'status'     => $status['status'],
            ];
        }

        wp_localize_script(
            'nova-x-settings',
            'NovaXData',
            [
                'providers'        => $providers_data,
                'nonce'            => wp_create_nonce( 'nova_x_nonce' ),
                'ajax_url'         => admin_url( 'admin-ajax.php' ),
                'rotate_token_url' => esc_url_raw( rest_url( 'nova-x/v1/rotate-token' ) ),
                'validate_key_url' => esc_url_raw( rest_url( 'nova-x/v1/validate-key' ) ),
                'rest_url'         => esc_url_raw( rest_url( 'nova-x/v1/' ) ),
            ]
        );
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        // Handle logout
        if ( isset( $_POST['nova_x_logout'] ) ) {
            Nova_X_Session::destroy();
            wp_safe_redirect( admin_url( 'admin.php?page=nova-x-settings' ) );
            exit;
        }

        // Handle form submission
        if ( isset( $_POST['nova_x_settings_nonce'] ) && wp_verify_nonce( $_POST['nova_x_settings_nonce'], 'nova_x_settings_save' ) ) {
            $this->handle_settings_save();
        }

        // Debug logging: Log that settings page is being rendered
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Nova-X Settings] Rendering settings page. User ID: ' . get_current_user_id() );
        }

        // Check if user is logged in
        $is_logged_in = Nova_X_Session::is_logged_in();

        // Load UI utilities
        $ui_utils_path = NOVA_X_PATH . 'admin/includes/ui-utils.php';
        if ( file_exists( $ui_utils_path ) ) {
            require_once $ui_utils_path;
        }

        // Get theme preference (default to dark)
        $theme = get_user_meta( get_current_user_id(), 'nova_x_theme_preference', true );
        if ( empty( $theme ) ) {
            $theme = 'dark';
        }

        // Get all supported providers
        $supported_providers = Nova_X_Provider_Manager::get_supported_providers();
        
        // Debug logging: Log providers and their status
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Nova-X Settings] Loading providers: ' . count( $supported_providers ) . ' providers found' );
            foreach ( $supported_providers as $provider ) {
                $status = Nova_X_Provider_Manager::get_provider_status( $provider );
                error_log( sprintf(
                    '[Nova-X Settings] Provider: %s, Status: %s, Has Masked Key: %s',
                    $provider,
                    $status['status'],
                    ! empty( $status['masked_key'] ) ? 'yes' : 'no'
                ) );
            }
        }
        
        // Provider display names
        $provider_names = [
            'openai'    => 'OpenAI',
            'anthropic' => 'Anthropic Claude',
            'claude'    => 'Anthropic Claude',
            'gemini'    => 'Google Gemini',
            'mistral'   => 'Mistral AI',
            'cohere'    => 'Cohere',
            'groq'      => 'Groq',
        ];

        $dashboard_url = admin_url( 'admin.php?page=nova-x-dashboard' );
        ?>
        <div class="wrap nova-x-wrapper nova-x-dashboard-wrap" data-theme="<?php echo esc_attr( $theme ); ?>">
            <div id="nova-x-wrapper" class="nova-x-wrapper">
                <div class="nova-x-dashboard-layout">
                    <div class="nova-x-dashboard-main nova-x-main" id="nova-x-dashboard-main">
                        <?php
                        // Render unified header (fixed overlay)
                        if ( function_exists( 'render_plugin_header' ) ) {
                            render_plugin_header( [
                                'notification_count' => 0,
                                'dashboard_url'      => $dashboard_url,
                            ] );
                        }
                        ?>
                        
                        <div class="nova-x-page-content">
                            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                            
                            <?php settings_errors( 'nova_x_settings' ); ?>
                            
                            <?php if ( $is_logged_in ) : ?>
                                <?php $user = Nova_X_Session::get_user(); ?>
                                <div class="nova-x-auth-welcome" style="margin-bottom: 20px; padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1; border-radius: 4px;">
                                    <p style="margin: 0 0 10px 0;">
                                        <strong><?php esc_html_e( 'Welcome,', 'nova-x' ); ?></strong> 
                                        <?php echo esc_html( $user['name'] ); ?>
                                        <span style="color: #646970;">(<?php echo esc_html( $user['email'] ); ?>)</span>
                                    </p>
                                    <form method="post" action="" style="margin: 0;">
                                        <button type="submit" name="nova_x_logout" value="1" class="button">
                                            <?php esc_html_e( 'Logout', 'nova-x' ); ?>
                                        </button>
                                    </form>
                                </div>
                                
                                <form method="post" action="" id="nova-x-settings-form">
                                <?php wp_nonce_field( 'nova_x_settings_save', 'nova_x_settings_nonce' ); ?>
                                
                                <table class="form-table">
                                    <tbody>
                                        <?php foreach ( $supported_providers as $provider ) : 
                                            $status = Nova_X_Provider_Manager::get_provider_status( $provider );
                                            $provider_label = isset( $provider_names[ $provider ] ) ? $provider_names[ $provider ] : ucfirst( $provider );
                                            $field_id = 'nova_x_api_key_' . $provider;
                                            $field_name = 'nova_x_api_key[' . esc_attr( $provider ) . ']';
                                            
                                            // Determine status class and icon
                                            $status_class = 'nova-x-status-' . $status['status'];
                                            $status_icon = '';
                                            $status_text = '';
                                            
                                            switch ( $status['status'] ) {
                                                case 'valid':
                                                    $status_icon = '✅';
                                                    $status_text = 'Saved';
                                                    break;
                                                case 'invalid':
                                                    $status_icon = '⚠️';
                                                    $status_text = 'Invalid Key Format';
                                                    break;
                                                case 'missing':
                                                    $status_icon = '⛔';
                                                    $status_text = 'Missing';
                                                    break;
                                                default:
                                                    $status_icon = '🔒';
                                                    $status_text = 'Encrypted';
                                            }
                                        ?>
                                            <tr>
                                                <th scope="row">
                                                    <label for="<?php echo esc_attr( $field_id ); ?>">
                                                        <?php echo esc_html( $provider_label ); ?>
                                                        <span class="nova-x-key-status <?php echo esc_attr( $status_class ); ?>">
                                                            <?php echo esc_html( $status_icon . ' ' . $status_text ); ?>
                                                        </span>
                                                    </label>
                                                </th>
                                                <td>
                                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                                        <input 
                                                            type="text" 
                                                            name="<?php echo esc_attr( $field_name ); ?>" 
                                                            id="<?php echo esc_attr( $field_id ); ?>" 
                                                            value="<?php echo esc_attr( $status['masked_key'] ); ?>" 
                                                            class="regular-text nova-x-api-key-field"
                                                            data-provider="<?php echo esc_attr( $provider ); ?>" 
                                                            data-original-value="<?php echo esc_attr( $status['masked_key'] ); ?>"
                                                            placeholder="<?php echo esc_attr( $status['masked_key'] ? $status['masked_key'] : 'Enter your ' . $provider_label . ' API key' ); ?>"
                                                            autocomplete="off"
                                                            style="flex: 1;"
                                                        />
                                                        <button 
                                                            type="button" 
                                                            class="button test-key-btn" 
                                                            data-provider="<?php echo esc_attr( $provider ); ?>"
                                                            title="<?php echo esc_attr( 'Test API key format for ' . $provider_label ); ?>">
                                                            🧪 Test Key
                                                        </button>
                                                        <button 
                                                            type="button" 
                                                            class="button rotate-token-btn" 
                                                            data-provider="<?php echo esc_attr( $provider ); ?>"
                                                            title="<?php echo esc_attr( 'Rotate token for ' . $provider_label ); ?>">
                                                            🔄 Rotate Token
                                                        </button>
                                                    </div>
                                                    <span class="test-key-status" style="display: inline-block; margin-left: 0; font-weight: 500; min-height: 20px;"></span>
                                                    <span class="rotate-token-status" style="display: inline-block; margin-left: 0; font-weight: 500; min-height: 20px;"></span>
                                                    <p class="description">
                                                        <?php if ( $status['status'] === 'valid' ) : ?>
                                                            API key is stored securely. Enter a new key to replace the existing one, or use Rotate Token to update.
                                                        <?php elseif ( $status['status'] === 'invalid' ) : ?>
                                                            The stored key format is invalid. Please enter a valid API key.
                                                        <?php elseif ( $status['status'] === 'missing' ) : ?>
                                                            No API key configured. Enter your API key to enable this provider.
                                                        <?php else : ?>
                                                            Enter your API key to enable AI theme generation with <?php echo esc_html( $provider_label ); ?>.
                                                        <?php endif; ?>
                                                    </p>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <?php submit_button( 'Save Settings' ); ?>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        // Include auth modal at page level if user is not logged in
        if ( ! Nova_X_Session::is_logged_in() ) {
            include NOVA_X_PATH . 'admin/partials/nova-x-auth-modal.php';
        }
    }

    /**
     * Handle settings form submission
     */
    public function handle_settings_save() {
        // Debug logging: Log form submission attempt
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Nova-X Settings] Form submission detected. User ID: ' . get_current_user_id() );
        }

        if ( ! isset( $_POST['nova_x_settings_nonce'] ) || ! wp_verify_nonce( $_POST['nova_x_settings_nonce'], 'nova_x_settings_save' ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[Nova-X Settings] Form submission failed: Invalid or missing nonce' );
            }
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[Nova-X Settings] Form submission failed: User lacks manage_options capability' );
            }
            return;
        }

        $saved_count = 0;
        $error_count = 0;

        // Handle API keys array
        if ( isset( $_POST['nova_x_api_key'] ) && is_array( $_POST['nova_x_api_key'] ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[Nova-X Settings] Processing ' . count( $_POST['nova_x_api_key'] ) . ' API key entries' );
            }
            foreach ( $_POST['nova_x_api_key'] as $provider => $api_key_input ) {
                $provider = sanitize_key( $provider );
                $api_key  = trim( sanitize_text_field( $api_key_input ) );
                
                // Skip invalid provider
                if ( empty( $provider ) || ! Nova_X_Provider_Manager::is_valid_provider( $provider ) ) {
                    continue;
                }

                // Get current status to check if value is masked
                $current_status = Nova_X_Provider_Manager::get_provider_status( $provider );
                $is_masked_value = ( $api_key === $current_status['masked_key'] && ! empty( $current_status['masked_key'] ) );
                
                // If field contains masked value, user didn't change it - skip
                if ( $is_masked_value ) {
                    continue;
                }

                // If field is empty, delete the key
                if ( empty( $api_key ) ) {
                    $deleted = Nova_X_Token_Manager::delete_key( $provider );
                    if ( $deleted ) {
                        $saved_count++;
                    }
                    continue;
                }

                // Check if it's a masked placeholder (contains bullet points)
                if ( strpos( $api_key, '•' ) !== false || strpos( $api_key, '*' ) !== false ) {
                    // User entered masked characters - treat as unchanged
                    continue;
                }

                // Validate and save the key using Provider Manager
                $result = Nova_X_Provider_Manager::save_api_key( $provider, $api_key );
                
                // Debug logging: Log save attempt result
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( sprintf(
                        '[Nova-X Settings] Save attempt for %s: %s - %s',
                        $provider,
                        $result['success'] ? 'SUCCESS' : 'FAILED',
                        isset( $result['message'] ) ? $result['message'] : 'No message'
                    ) );
                }
                
                if ( $result['success'] ) {
                    $saved_count++;
                } else {
                    $error_count++;
                    add_settings_error(
                        'nova_x_settings',
                        'invalid_api_key_' . $provider,
                        sprintf( '%s: %s', Nova_X_Provider_Manager::get_provider_name( $provider ), esc_html( $result['message'] ) ),
                        'error'
                    );
                }
            }
        }

        // Show success/error messages
        if ( $saved_count > 0 ) {
            add_settings_error(
                'nova_x_settings',
                'settings_saved',
                sprintf( 'Settings saved successfully. %d provider key(s) updated.', $saved_count ),
                'updated'
            );
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[Nova-X Settings] Save completed: ' . $saved_count . ' key(s) saved successfully' );
            }
        } elseif ( $error_count === 0 ) {
            add_settings_error(
                'nova_x_settings',
                'settings_saved',
                'Settings saved successfully. No changes detected.',
                'updated'
            );
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[Nova-X Settings] Save completed: No changes detected' );
            }
        } else {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[Nova-X Settings] Save completed with errors: ' . $error_count . ' error(s)' );
            }
        }
    }
}

