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
        // Constructor can be empty or used for other initialization
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        // Handle form submission
        if ( isset( $_POST['nova_x_settings_nonce'] ) && wp_verify_nonce( $_POST['nova_x_settings_nonce'], 'nova_x_settings_save' ) ) {
            $this->handle_settings_save();
        }

        // Get saved values
        $selected_provider = get_option( 'nova_x_selected_provider', 'OpenAI' );
        $api_key = get_option( 'nova_x_api_key', '' );

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <?php settings_errors( 'nova_x_settings' ); ?>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'nova_x_settings_save', 'nova_x_settings_nonce' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="nova_x_selected_provider">AI Provider</label>
                        </th>
                        <td>
                            <select name="nova_x_selected_provider" id="nova_x_selected_provider">
                                <option value="OpenAI" <?php selected( $selected_provider, 'OpenAI' ); ?>>OpenAI</option>
                                <option value="Gemini" <?php selected( $selected_provider, 'Gemini' ); ?>>Gemini</option>
                                <option value="Claude" <?php selected( $selected_provider, 'Claude' ); ?>>Claude</option>
                                <option value="Mistral" <?php selected( $selected_provider, 'Mistral' ); ?>>Mistral</option>
                                <option value="Cohere" <?php selected( $selected_provider, 'Cohere' ); ?>>Cohere</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="nova_x_api_key">API Key</label>
                        </th>
                        <td>
                            <input 
                                type="password" 
                                name="nova_x_api_key" 
                                id="nova_x_api_key" 
                                value="<?php echo esc_attr( $api_key ); ?>" 
                                class="regular-text"
                                placeholder="sk-..."
                            />
                            <p class="description">Enter your API key to enable AI theme generation.</p>
                        </td>
                    </tr>
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

        // Save selected provider
        if ( isset( $_POST['nova_x_selected_provider'] ) ) {
            $provider = sanitize_text_field( $_POST['nova_x_selected_provider'] );
            $allowed_providers = [ 'OpenAI', 'Gemini', 'Claude', 'Mistral', 'Cohere' ];
            
            if ( in_array( $provider, $allowed_providers, true ) ) {
                update_option( 'nova_x_selected_provider', $provider );
            }
        }

        // Save API key
        if ( isset( $_POST['nova_x_api_key'] ) ) {
            $api_key = sanitize_text_field( $_POST['nova_x_api_key'] );
            
            // Only save if it's not a masked placeholder
            if ( strpos( $api_key, '*' ) === false && ! empty( $api_key ) ) {
                update_option( 'nova_x_api_key', $api_key );
            }
        }

        // Show success message
        add_settings_error( 'nova_x_settings', 'settings_saved', 'Settings saved successfully.', 'updated' );
    }
}

