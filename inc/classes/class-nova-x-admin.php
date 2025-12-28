<?php
/**
 * Admin Dashboard & Menu Logic
 *
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_Admin {

    /**
     * Plugin slug
     *
     * @var string
     */
    private $plugin_slug;

    /**
     * Plugin version
     *
     * @var string
     */
    private $plugin_version;

    /**
     * Constructor
     *
     * @param string $version Plugin version.
     */
    public function __construct( $version ) {
        $this->plugin_slug    = 'nova-x';
        $this->plugin_version = $version;

        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Add admin menu & submenus
     */
    public function add_admin_menu() {
        // Add Settings submenu under the main Nova-X menu (React dashboard)
        add_submenu_page(
            'nova-x',                  // Parent slug (must match the React root menu)
            'Nova-X Settings',         // Page title
            'Settings',                // Menu title
            'manage_options',          // Capability
            'nova-x-settings',         // Menu slug
            [ $this, 'render_settings_page' ]  // Callback
        );
    }

    /**
     * Register settings using WordPress Settings API
     */
    public function register_settings() {
        // Load Token Manager
        require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-token-manager.php';

        register_setting(
            'nova_x_settings_group',
            'nova_x_api_key',
            [
                'type'              => 'string',
                'sanitize_callback' => [ $this, 'sanitize_and_encrypt_api_key' ],
            ]
        );

        register_setting(
            'nova_x_settings_group',
            'nova_x_provider',
            [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ]
        );
    }

    /**
     * Sanitize and encrypt API key before saving
     *
     * @param string $raw_key Raw API key input.
     * @return string Empty string (key is stored encrypted separately).
     */
    public function sanitize_and_encrypt_api_key( $raw_key ) {
        // Get the selected provider
        $provider = isset( $_POST['nova_x_provider'] ) ? sanitize_text_field( $_POST['nova_x_provider'] ) : get_option( 'nova_x_provider', 'openai' );
        
        // Sanitize the key
        $raw_key = sanitize_text_field( trim( $raw_key ) );
        
        // Only save if key is not empty and not a masked placeholder
        if ( ! empty( $raw_key ) && strpos( $raw_key, '*' ) === false ) {
            // Store encrypted key using Token Manager
            Nova_X_Token_Manager::store_encrypted_key( $provider, $raw_key );
            
            // Also update legacy option for backward compatibility (will be deprecated)
            update_option( 'nova_x_api_key', $raw_key );
        }
        
        // Return empty string to prevent plain text storage in options
        return '';
    }

    /**
     * Render admin settings page
     */
    public function render_settings_page() {
        ?>
        <style>
            .fade-success {
                animation: fadeInSuccess 0.5s ease-in;
            }
            @keyframes fadeInSuccess {
                0% { opacity: 0; transform: translateY(-5px); }
                100% { opacity: 1; transform: translateY(0); }
            }
            .rotate-token-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
            .rotate-token-btn .spinner {
                float: none;
                margin: 0 5px 0 0;
                visibility: visible;
            }
        </style>
        <div class="wrap">
            <h1>Nova-X Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'nova_x_settings_group' );
                do_settings_sections( 'nova_x_settings_group' );
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">API Provider</th>
                        <td>
                            <select name="nova_x_provider">
                                <?php
                                $selected = esc_attr( get_option( 'nova_x_provider', 'openai' ) );
                                $providers = [ 'openai' => 'OpenAI', 'anthropic' => 'Anthropic', 'groq' => 'Groq', 'mistral' => 'Mistral', 'gemini' => 'Gemini' ];
                                foreach ( $providers as $value => $label ) {
                                    echo "<option value='" . esc_attr( $value ) . "' " . selected( $selected, $value, false ) . ">$label</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="text" name="nova_x_api_key" value="<?php echo esc_attr( get_option( 'nova_x_api_key' ) ); ?>" class="regular-text" />
                            <p class="description">Enter your API key for the selected provider.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Token Management</th>
                        <td>
                            <button type="button" class="button rotate-token-btn" data-provider="<?php echo esc_attr( get_option( 'nova_x_provider', 'openai' ) ); ?>">
                                🔁 Rotate Token
                            </button>
                            <span class="rotate-token-status" style="margin-left: 10px; font-weight: bold;"></span>
                            <p class="description">Rotate the encrypted API token for the selected provider. This will replace the existing token.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( 'Save Settings' ); ?>
            </form>

            <hr>

            <h2>Usage Statistics</h2>
            <?php
            // Load Usage Tracker
            require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-usage-tracker.php';
            $total_tokens = Nova_X_Usage_Tracker::get_formatted_tokens();
            $total_cost = Nova_X_Usage_Tracker::get_formatted_cost();
            $provider_data = Nova_X_Usage_Tracker::get_all_provider_data();
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Total Tokens Used</th>
                    <td>
                        <strong style="font-size: 16px;">🔢 <?php echo esc_html( $total_tokens ); ?></strong>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Total Estimated Cost</th>
                    <td>
                        <strong style="font-size: 16px;">💵 $<?php echo esc_html( $total_cost ); ?> USD</strong>
                    </td>
                </tr>
            </table>

            <h3>Per-Provider Breakdown</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 200px;">Provider</th>
                        <th style="text-align: right;">Tokens Used</th>
                        <th style="text-align: right;">Cost (USD)</th>
                        <th style="text-align: right;">% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_tokens_num = Nova_X_Usage_Tracker::get_total_tokens();
                    $total_cost_num = Nova_X_Usage_Tracker::get_total_cost();
                    
                    if ( empty( $provider_data ) || $total_tokens_num === 0 ) {
                        echo '<tr><td colspan="4" style="text-align: center; padding: 20px; color: #999;">No usage data yet. Generate a theme to see statistics.</td></tr>';
                    } else {
                        foreach ( $provider_data as $provider => $data ) {
                            if ( $data['tokens'] > 0 ) {
                                $token_percent = $total_tokens_num > 0 
                                    ? round( ( $data['tokens'] / $total_tokens_num ) * 100, 1 ) 
                                    : 0;
                                $cost_percent = $total_cost_num > 0 
                                    ? round( ( $data['cost'] / $total_cost_num ) * 100, 1 ) 
                                    : 0;
                                
                                echo '<tr>';
                                echo '<td><strong>' . esc_html( $data['label'] ) . '</strong></td>';
                                echo '<td style="text-align: right;">' . esc_html( number_format( $data['tokens'] ) ) . '</td>';
                                echo '<td style="text-align: right;">$' . esc_html( number_format( $data['cost'], 4 ) ) . '</td>';
                                echo '<td style="text-align: right;">' . esc_html( $token_percent ) . '%</td>';
                                echo '</tr>';
                            }
                        }
                    }
                    ?>
                </tbody>
            </table>
            <p>
                <button type="button" class="button" id="nova_x_reset_tracker">🔄 Reset Tracker</button>
                <span id="nova_x_reset_status" style="margin-left: 10px; font-weight: bold;"></span>
            </p>

            <hr>

            <h2>Generate Theme</h2>
            <p>
                <input type="text" id="nova_x_site_title" placeholder="Site Title" class="regular-text" />
                <br><br>
                <textarea id="nova_x_prompt" placeholder="Describe the theme..." class="large-text" rows="4"></textarea><br><br>
                <button type="button" class="button button-primary" id="nova_x_generate_theme">Generate Theme</button>
                <span id="nova_x_response" style="margin-left: 10px; font-weight: bold;"></span>
            </p>
        </div>
        <?php
    }
}

