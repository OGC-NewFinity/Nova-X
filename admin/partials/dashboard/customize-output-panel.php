<?php
/**
 * Customize Output Panel Template
 * 
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="nova-x-tab-pane" id="nova-x-customize-tab">
    <h2><?php esc_html_e( 'Customize Output', 'nova-x' ); ?></h2>
    
    <p class="nova-x-customize-help">
        <?php esc_html_e( 'Modify the generated theme files before proceeding with export or installation.', 'nova-x' ); ?>
    </p>

    <div class="nova-x-customize-container">
        <!-- File Tabs Navigation -->
        <div class="nova-x-file-tabs">
            <button type="button" class="nova-x-file-tab active" data-file="style">
                <span class="dashicons dashicons-art"></span>
                style.css
            </button>
            <button type="button" class="nova-x-file-tab" data-file="functions">
                <span class="dashicons dashicons-editor-code"></span>
                functions.php
            </button>
            <button type="button" class="nova-x-file-tab" data-file="index">
                <span class="dashicons dashicons-media-code"></span>
                index.php
            </button>
        </div>

        <!-- File Content Areas -->
        <div class="nova-x-file-contents">
            <!-- style.css Editor -->
            <div class="nova-x-file-content active" id="nova-x-file-style">
                <label for="nova-x-style-css">
                    <strong><?php esc_html_e( 'style.css', 'nova-x' ); ?></strong>
                </label>
                <textarea 
                    id="nova-x-style-css" 
                    class="nova-x-code-editor" 
                    rows="25" 
                    placeholder="<?php esc_attr_e( 'CSS code will appear here after theme generation...', 'nova-x' ); ?>"
                ></textarea>
            </div>

            <!-- functions.php Editor -->
            <div class="nova-x-file-content" id="nova-x-file-functions">
                <label for="nova-x-functions-php">
                    <strong><?php esc_html_e( 'functions.php', 'nova-x' ); ?></strong>
                </label>
                <textarea 
                    id="nova-x-functions-php" 
                    class="nova-x-code-editor" 
                    rows="25" 
                    placeholder="<?php esc_attr_e( 'PHP code will appear here after theme generation...', 'nova-x' ); ?>"
                ></textarea>
            </div>

            <!-- index.php Editor -->
            <div class="nova-x-file-content" id="nova-x-file-index">
                <label for="nova-x-index-php">
                    <strong><?php esc_html_e( 'index.php', 'nova-x' ); ?></strong>
                </label>
                <textarea 
                    id="nova-x-index-php" 
                    class="nova-x-code-editor" 
                    rows="25" 
                    placeholder="<?php esc_attr_e( 'PHP code will appear here after theme generation...', 'nova-x' ); ?>"
                ></textarea>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="nova-x-customize-actions">
            <button type="button" class="button button-primary" id="nova-x-save-changes">
                ✅ <?php esc_html_e( 'Save Changes', 'nova-x' ); ?>
            </button>
            
            <button type="button" class="button button-secondary" id="nova-x-reset-original">
                ♻️ <?php esc_html_e( 'Reset to Original', 'nova-x' ); ?>
            </button>

            <span id="nova-x-customize-status" class="nova-x-status"></span>
            <div class="nova-x-loader nova-x-hidden" id="nova-x-customize-loader"></div>
        </div>
    </div>

    <!-- Hidden storage for original code -->
    <div id="nova-x-original-code" class="nova-x-hidden" data-style="" data-functions="" data-index=""></div>
</div>

