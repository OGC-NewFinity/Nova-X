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
        add_menu_page(
            'Nova-X Architect',
            'Nova-X',
            'manage_options',
            $this->plugin_slug,
            [ $this, 'render_settings_page' ],
            'dashicons-hammer',
            66
        );
    }

    /**
     * Register settings using WordPress Settings API
     */
    public function register_settings() {
        register_setting(
            'nova_x_settings_group',
            'nova_x_api_key',
            [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
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
     * Render admin settings page
     */
    public function render_settings_page() {
        ?>
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
                                $providers = [ 'openai' => 'OpenAI', 'anthropic' => 'Anthropic', 'groq' => 'Groq', 'mistral' => 'Mistral' ];
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
                        </td>
                    </tr>
                </table>
                <?php submit_button( 'Save Settings' ); ?>
            </form>

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

