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
        add_action( 'admin_menu', [ $this, 'register_settings_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_settings_assets' ] );
    }

    /**
     * Register settings menu page as submenu under Nova-X Dashboard
     */
    public function register_settings_menu() {
        add_submenu_page(
            'nova-x-dashboard',                    // Parent slug (matches Nova_X_Admin main menu)
            esc_html__( 'Nova-X Settings', 'nova-x' ), // Page title
            esc_html__( 'Settings', 'nova-x' ),    // Menu label
            'manage_options',                      // Capability
            'nova-x-settings',                     // Menu slug
            [ $this, 'render_settings_page' ]      // Callback
        );
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
            ]
        );
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        // Start session if not already started
        if ( ! session_id() ) {
            session_start();
        }

        // Handle logout
        if ( isset( $_POST['nova_x_logout'] ) ) {
            if ( isset( $_SESSION['nova_x_user'] ) ) {
                unset( $_SESSION['nova_x_user'] );
            }
            session_destroy();
            wp_safe_redirect( admin_url( 'admin.php?page=nova-x-settings' ) );
            exit;
        }

        // Handle form submission
        if ( isset( $_POST['nova_x_settings_nonce'] ) && wp_verify_nonce( $_POST['nova_x_settings_nonce'], 'nova_x_settings_save' ) ) {
            $this->handle_settings_save();
        }

        // Check if user is logged in
        $is_logged_in = isset( $_SESSION['nova_x_user'] ) && ! empty( $_SESSION['nova_x_user'] );

        // Get all supported providers
        $supported_providers = Nova_X_Provider_Manager::get_supported_providers();
        
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

        ?>
        <div class="wrap nova-x-settings-wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <?php settings_errors( 'nova_x_settings' ); ?>
            
            <?php if ( $is_logged_in ) : ?>
                <div class="nova-x-auth-welcome" style="margin-bottom: 20px; padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1; border-radius: 4px;">
                    <p style="margin: 0 0 10px 0;">
                        <strong><?php esc_html_e( 'Welcome,', 'nova-x' ); ?></strong> 
                        <?php echo esc_html( $_SESSION['nova_x_user']['name'] ); ?>
                        <span style="color: #646970;">(<?php echo esc_html( $_SESSION['nova_x_user']['email'] ); ?>)</span>
                    </p>
                    <form method="post" action="" style="margin: 0;">
                        <button type="submit" name="nova_x_logout" value="1" class="button">
                            <?php esc_html_e( 'Logout', 'nova-x' ); ?>
                        </button>
                    </form>
                </div>
            <?php else : ?>
                <div class="nova-x-auth-section" style="margin-bottom: 30px;">
                    <?php
                    include NOVA_X_PATH . 'admin/partials/nova-x-auth-login.php';
                    include NOVA_X_PATH . 'admin/partials/nova-x-auth-register.php';
                    ?>
                </div>
            <?php endif; ?>
            
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
                                            class="button rotate-token-btn" 
                                            data-provider="<?php echo esc_attr( $provider ); ?>"
                                            title="<?php echo esc_attr( 'Rotate token for ' . $provider_label ); ?>">
                                            🔄 Rotate Token
                                        </button>
                                    </div>
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
        </div>
        <?php
    }

    /**
     * Handle settings form submission
     */
    public function handle_settings_save() {
        if ( ! isset( $_POST['nova_x_settings_nonce'] ) || ! wp_verify_nonce( $_POST['nova_x_settings_nonce'], 'nova_x_settings_save' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $saved_count = 0;
        $error_count = 0;

        // Handle API keys array
        if ( isset( $_POST['nova_x_api_key'] ) && is_array( $_POST['nova_x_api_key'] ) ) {
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
        } elseif ( $error_count === 0 ) {
            add_settings_error(
                'nova_x_settings',
                'settings_saved',
                'Settings saved successfully. No changes detected.',
                'updated'
            );
        }
    }
}

