<?php
/**
 * Generate Theme Panel Template
 * 
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get current provider
$current_provider = get_option( 'nova_x_provider', 'openai' );

// Load Provider Manager to get provider names
require_once NOVA_X_PATH . 'inc/classes/class-nova-x-provider-manager.php';

// Define provider options
$providers = [
    'openai'    => 'OpenAI',
    'anthropic' => 'Anthropic',
    'groq'      => 'Groq',
    'mistral'   => 'Mistral',
    'gemini'    => 'Gemini',
];
?>

<div class="nova-x-tab-pane" id="nova-x-generate-tab">
    <h2><?php esc_html_e( 'Generate Theme', 'nova-x' ); ?></h2>
    
    <div class="nova-x-generate-form">
        <form id="nova-x-generate-form">
            <?php wp_nonce_field( 'nova_x_generate_theme', 'nova_x_generate_nonce' ); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="nova-x-site-title">
                            <span class="dashicons dashicons-art" style="vertical-align: middle; margin-right: 5px; font-size: 18px; width: 18px; height: 18px;"></span>
                            <?php esc_html_e( 'Site Title', 'nova-x' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               id="nova-x-site-title" 
                               name="site_title" 
                               class="regular-text" 
                               placeholder="<?php esc_attr_e( 'Enter site title...', 'nova-x' ); ?>" 
                               required />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="nova-x-theme-prompt">
                            <span class="dashicons dashicons-format-aside" style="vertical-align: middle; margin-right: 5px; font-size: 18px; width: 18px; height: 18px;"></span>
                            <?php esc_html_e( 'Prompt', 'nova-x' ); ?>
                        </label>
                    </th>
                    <td>
                        <textarea id="nova-x-theme-prompt" 
                                  name="prompt" 
                                  class="large-text" 
                                  rows="8" 
                                  placeholder="<?php esc_attr_e( 'Describe the theme you want to generate...', 'nova-x' ); ?>" 
                                  required></textarea>
                        <p class="description"><?php esc_html_e( 'Provide a detailed description of the theme you want to create.', 'nova-x' ); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="nova-x-provider-select">
                            <span class="dashicons dashicons-lightbulb" style="vertical-align: middle; margin-right: 5px; font-size: 18px; width: 18px; height: 18px;"></span>
                            <?php esc_html_e( 'Select Provider', 'nova-x' ); ?>
                        </label>
                    </th>
                    <td>
                        <select id="nova-x-provider-select" name="provider" class="regular-text">
                            <?php foreach ( $providers as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_provider, $value ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php esc_html_e( 'Choose the AI provider to use for theme generation.', 'nova-x' ); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary button-large" id="nova-x-generate-btn">
                    ⚡ <?php esc_html_e( 'Generate Theme', 'nova-x' ); ?>
                </button>
                <span id="nova-x-generate-status" class="nova-x-status"></span>
                <div class="nova-x-loader nova-x-hidden" id="theme-loader"></div>
            </p>
        </form>
    </div>
    
    <!-- Theme Output Section -->
    <div id="nova-x-theme-output" class="nova-x-theme-output nova-x-hidden">
        <h3><?php esc_html_e( 'Generated Theme Code', 'nova-x' ); ?></h3>
        <div class="nova-x-output-container">
            <pre><code id="nova-x-theme-code" class="language-php"></code></pre>
        </div>
        
        <div class="nova-x-action-buttons">
            <button type="button" class="button button-secondary" id="nova-x-export-theme">
                📦 <?php esc_html_e( 'Export Theme', 'nova-x' ); ?>
            </button>
            
            <button type="button" class="button button-secondary" id="nova-x-preview-theme">
                👁️ <?php esc_html_e( 'Preview', 'nova-x' ); ?>
            </button>
            
            <button type="button" class="button button-primary" id="nova-x-install-theme">
                ⚡ <?php esc_html_e( 'Install', 'nova-x' ); ?>
            </button>
        </div>
        
        <div id="nova-x-action-status" class="nova-x-status"></div>
    </div>
</div>

