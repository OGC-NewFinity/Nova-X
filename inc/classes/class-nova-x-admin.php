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
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    /**
     * Add admin menu & submenus
     */
    public function add_admin_menu() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Main menu item
        add_menu_page(
            esc_html__( 'Nova-X', 'nova-x' ),
            esc_html__( 'Nova-X', 'nova-x' ),
            'manage_options',
            'nova-x-dashboard',
            [ $this, 'render_dashboard_page' ],
            'dashicons-art',
            66
        );

        // Dashboard submenu (first item, same as main menu)
        add_submenu_page(
            'nova-x-dashboard',
            esc_html__( 'Nova-X Dashboard', 'nova-x' ),
            esc_html__( 'Dashboard', 'nova-x' ),
            'manage_options',
            'nova-x-dashboard',
            [ $this, 'render_dashboard_page' ]
        );

        // Settings submenu
        add_submenu_page(
            'nova-x-dashboard',
            esc_html__( 'Nova-X Settings', 'nova-x' ),
            esc_html__( 'Settings', 'nova-x' ),
            'manage_options',
            'nova-x-settings',
            [ $this, 'render_settings_page' ]
        );

        // Architecture Manager submenu
        add_submenu_page(
            'nova-x-dashboard',
            esc_html__( 'Architecture Manager', 'nova-x' ),
            esc_html__( 'Architecture', 'nova-x' ),
            'manage_options',
            'nova-x-architecture',
            [ $this, 'render_architecture_page' ]
        );

        // License submenu
        add_submenu_page(
            'nova-x-dashboard',
            esc_html__( 'License', 'nova-x' ),
            esc_html__( 'License', 'nova-x' ),
            'manage_options',
            'nova-x-license',
            [ $this, 'render_license_page' ]
        );

        // Exported Themes submenu (optional)
        add_submenu_page(
            'nova-x-dashboard',
            esc_html__( 'Exported Themes', 'nova-x' ),
            esc_html__( 'Exported Themes', 'nova-x' ),
            'manage_options',
            'nova-x-exports',
            [ $this, 'render_exports_page' ]
        );

        // Beta Tools submenu (optional, can be toggled)
        $beta_enabled = apply_filters( 'nova_x_enable_beta_tools', false );
        if ( $beta_enabled ) {
            add_submenu_page(
                'nova-x-dashboard',
                esc_html__( 'Beta Tools', 'nova-x' ),
                esc_html__( 'Beta Tools', 'nova-x' ),
                'manage_options',
                'nova-x-beta',
                [ $this, 'render_beta_page' ]
            );
        }
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
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets( $hook ) {
        // Check if we're on a Nova-X admin page
        $nova_x_pages = [
            'toplevel_page_nova-x-dashboard',
            'nova-x_page_nova-x-dashboard',
            'nova-x_page_nova-x-settings',
            'nova-x_page_nova-x-architecture',
            'nova-x_page_nova-x-license',
            'nova-x_page_nova-x-exports',
            'nova-x_page_nova-x-beta',
        ];

        if ( ! in_array( $hook, $nova_x_pages, true ) ) {
            return;
        }

        // Enqueue global theme CSS (load first for variable definitions)
        wp_enqueue_style(
            'nova-x-global',
            NOVA_X_URL . 'admin/assets/css/nova-x-global.css',
            [],
            $this->plugin_version
        );

        // Enqueue admin CSS
        wp_enqueue_style(
            'nova-x-admin-style',
            NOVA_X_URL . 'admin/css/nova-x-admin.css',
            [ 'nova-x-global' ],
            $this->plugin_version
        );

        // Enqueue notices CSS
        wp_enqueue_style(
            'nova-x-notices',
            NOVA_X_URL . 'admin/assets/css/nova-x-notices.css',
            [ 'nova-x-admin-style' ],
            $this->plugin_version
        );

        // Enqueue global theme toggle JS for all Nova-X pages
        // This ensures theme toggle works on Dashboard, Settings, License, Architecture, Exports, and Beta pages
        wp_enqueue_script(
            'nova-x-theme-toggle',
            NOVA_X_URL . 'admin/assets/js/theme-toggle.js',
            [], // No dependencies - pure vanilla JS
            $this->plugin_version,
            true
        );
        
        // Get theme preference from user meta (defaults to 'dark' if not set)
        $default_theme = get_user_meta( get_current_user_id(), 'nova_x_theme_preference', true );
        if ( empty( $default_theme ) ) {
            $default_theme = 'dark';
        }
        
        // Localize script to pass default theme to JavaScript
        wp_localize_script(
            'nova-x-theme-toggle',
            'novaXTheme',
            [
                'defaultTheme' => $default_theme,
                'restUrl'      => esc_url_raw( rest_url( 'nova-x/v1/' ) ),
                'nonce'        => wp_create_nonce( 'wp_rest' ),
            ]
        );

        // Enqueue dashboard JS for dashboard page
        if ( 'toplevel_page_nova-x-dashboard' === $hook || 'nova-x_page_nova-x-dashboard' === $hook ) {
            wp_enqueue_script(
                'nova-x-dashboard',
                NOVA_X_URL . 'admin/js/nova-x-dashboard.js',
                [ 'jquery' ],
                $this->plugin_version,
                true
            );

            wp_localize_script(
                'nova-x-dashboard',
                'novaXDashboard',
                [
                    'nonce'            => wp_create_nonce( 'wp_rest' ),
                    'generateNonce'    => wp_create_nonce( 'nova_x_nonce' ),
                    'restUrl'          => esc_url_raw( rest_url( 'nova-x/v1/' ) ),
                    'generateThemeUrl' => esc_url_raw( rest_url( 'nova-x/v1/generate-theme' ) ),
                    'previewThemeUrl'  => esc_url_raw( rest_url( 'nova-x/v1/preview-theme' ) ),
                    'usageStatsUrl'    => esc_url_raw( rest_url( 'nova-x/v1/get-usage-stats' ) ),
                    'dashboardUrl'     => esc_url_raw( admin_url( 'admin.php?page=nova-x-dashboard' ) ),
                ]
            );
        }

        // Enqueue settings JS for settings page
        if ( 'nova-x_page_nova-x-settings' === $hook ) {
            wp_enqueue_script(
                'nova-x-admin',
                NOVA_X_URL . 'assets/admin.js',
                [ 'jquery' ],
                $this->plugin_version,
                true
            );

            wp_localize_script(
                'nova-x-admin',
                'NovaXData',
                [
                    'nonce'            => wp_create_nonce( 'nova_x_nonce' ),
                    'restUrl'          => esc_url_raw( rest_url( 'nova-x/v1/generate-theme' ) ),
                    'rotateTokenUrl'   => esc_url_raw( rest_url( 'nova-x/v1/rotate-token' ) ),
                    'exportThemeUrl'   => esc_url_raw( rest_url( 'nova-x/v1/export-theme' ) ),
                    'previewThemeUrl'  => esc_url_raw( rest_url( 'nova-x/v1/preview-theme' ) ),
                    'installThemeUrl'  => esc_url_raw( rest_url( 'nova-x/v1/install-theme' ) ),
                    'resetTrackerUrl'  => esc_url_raw( rest_url( 'nova-x/v1/reset-usage-tracker' ) ),
                ]
            );
        }
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nova-x' ) );
        }

        // Get current tab from URL
        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'generate';

        // Render dashboard content with tabs
        $this->render_dashboard_content( $current_tab );
    }

    /**
     * Render dashboard content with tabs
     *
     * @param string $current_tab Current active tab.
     */
    private function render_dashboard_content( $current_tab ) {
        $tabs = [
            'generate'   => esc_html__( 'Generate Theme', 'nova-x' ),
            'customize'  => esc_html__( 'Customize Output', 'nova-x' ),
            'preview'    => esc_html__( 'Live Preview', 'nova-x' ),
            'usage'      => esc_html__( 'Usage Stats', 'nova-x' ),
            'exported'   => esc_html__( 'Exported Themes', 'nova-x' ),
        ];

        $dashboard_url = admin_url( 'admin.php?page=nova-x-dashboard' );
        
        // Get theme preference (default to dark)
        $theme = get_user_meta( get_current_user_id(), 'nova_x_theme_preference', true );
        if ( empty( $theme ) ) {
            $theme = 'dark'; // Default to dark theme
        }
        
        // Load UI utilities
        $ui_utils_path = NOVA_X_PATH . 'admin/includes/ui-utils.php';
        if ( file_exists( $ui_utils_path ) ) {
            require_once $ui_utils_path;
        }
        
        // Load sidebar navigation
        $sidebar_path = NOVA_X_PATH . 'admin/partials/dashboard/sidebar-navigation.php';
        ?>
        <div class="wrap nova-x-wrapper nova-x-dashboard-wrap" data-theme="<?php echo esc_attr( $theme ); ?>">
            <div id="nova-x-wrapper" class="nova-x-wrapper">
            <div class="nova-x-dashboard-layout">
                <?php if ( file_exists( $sidebar_path ) ) : ?>
                    <?php include $sidebar_path; ?>
                <?php endif; ?>
                
                <div class="nova-x-dashboard-main nova-x-main" id="nova-x-dashboard-main">
                    <?php
                    // Render unified header (fixed overlay)
                    if ( function_exists( 'render_plugin_header' ) ) {
                        render_plugin_header( [
                            'notification_count' => 3, // Can be made dynamic
                            'dashboard_url'      => $dashboard_url,
                        ] );
                    }
                    ?>
                    
                    <div class="nova-x-page-content">
                        <!-- Keep top nav tabs for backward compatibility and mobile -->
                        <nav class="nav-tab-wrapper nova-x-tab-wrapper nova-x-top-nav">
                            <?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
                                <a href="<?php echo esc_url( add_query_arg( 'tab', $tab_key, $dashboard_url ) ); ?>" 
                                   class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>"
                                   data-tab="<?php echo esc_attr( $tab_key ); ?>">
                                    <?php echo esc_html( $tab_label ); ?>
                                </a>
                            <?php endforeach; ?>
                        </nav>

                        <div class="nova-x-tab-content" id="nova-x-tab-content">
                        <?php
                        // Render all panels but hide inactive ones
                        $all_tabs = [ 'generate', 'customize', 'preview', 'usage', 'exported' ];
                        foreach ( $all_tabs as $tab_key ) {
                            $is_active = ( $current_tab === $tab_key );
                            echo '<div class="nova-x-tab-panel-wrapper" data-tab="' . esc_attr( $tab_key ) . '" style="' . ( $is_active ? '' : 'display: none;' ) . '">';
                            
                            switch ( $tab_key ) {
                                case 'generate':
                                    $this->render_generate_tab();
                                    break;
                                case 'customize':
                                    $this->render_customize_tab();
                                    break;
                                case 'preview':
                                    $this->render_preview_tab();
                                    break;
                                case 'usage':
                                    $this->render_usage_tab();
                                    break;
                                case 'exported':
                                    $this->render_exported_themes_tab();
                                    break;
                            }
                            
                            echo '</div>';
                        }
                        ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Generate Theme tab
     */
    private function render_generate_tab() {
        // Load the generate theme panel partial
        $template_path = NOVA_X_PATH . 'admin/partials/dashboard/generate-theme-panel.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            // Fallback if template doesn't exist
            ?>
            <div class="nova-x-tab-pane" id="nova-x-generate-tab">
                <h2><?php esc_html_e( 'Generate Theme', 'nova-x' ); ?></h2>
                <p><?php esc_html_e( 'Template file not found. Please ensure generate-theme-panel.php exists.', 'nova-x' ); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Render Customize Output tab
     */
    private function render_customize_tab() {
        // Load the customize output panel partial
        $template_path = NOVA_X_PATH . 'admin/partials/dashboard/customize-output-panel.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            // Fallback if template doesn't exist
            ?>
            <div class="nova-x-tab-pane" id="nova-x-customize-tab">
                <h2><?php esc_html_e( 'Customize Output', 'nova-x' ); ?></h2>
                <p><?php esc_html_e( 'Template file not found. Please ensure customize-output-panel.php exists.', 'nova-x' ); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Render Live Preview tab
     */
    private function render_preview_tab() {
        // Load the live preview panel partial
        $template_path = NOVA_X_PATH . 'admin/partials/dashboard/live-preview-panel.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            // Fallback if template doesn't exist
            ?>
            <div class="nova-x-tab-pane" id="nova-x-preview-tab">
                <h2><?php esc_html_e( 'Live Preview', 'nova-x' ); ?></h2>
                <p><?php esc_html_e( 'Template file not found. Please ensure live-preview-panel.php exists.', 'nova-x' ); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Render Usage Stats tab
     */
    private function render_usage_tab() {
        // Load the usage stats panel partial
        $template_path = NOVA_X_PATH . 'admin/partials/dashboard/usage-stats-panel.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            // Fallback if template doesn't exist
            ?>
            <div class="nova-x-tab-pane" id="nova-x-usage-tab">
                <h2><?php esc_html_e( 'Usage Statistics', 'nova-x' ); ?></h2>
                <p><?php esc_html_e( 'Template file not found. Please ensure usage-stats-panel.php exists.', 'nova-x' ); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Render Exported Themes tab
     */
    private function render_exported_themes_tab() {
        // Load the exported themes panel partial
        $template_path = NOVA_X_PATH . 'admin/partials/dashboard/exported-themes-panel.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            // Fallback if template doesn't exist
            ?>
            <div class="nova-x-tab-pane" id="nova-x-exported-themes-tab">
                <h2><?php esc_html_e( 'Exported Themes', 'nova-x' ); ?></h2>
                <p><?php esc_html_e( 'Template file not found. Please ensure exported-themes-panel.php exists.', 'nova-x' ); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Render architecture page
     */
    public function render_architecture_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nova-x' ) );
        }
        
        // Load UI utilities
        $ui_utils_path = NOVA_X_PATH . 'admin/includes/ui-utils.php';
        if ( file_exists( $ui_utils_path ) ) {
            require_once $ui_utils_path;
        }
        ?>
        <div class="wrap nova-x-wrapper nova-x-dashboard-wrap">
            <?php
            // Render unified header
            if ( function_exists( 'render_plugin_header' ) ) {
                render_plugin_header();
            }
            ?>
            <div class="nova-x-page-content">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <p><?php esc_html_e( 'Manage theme architecture and building logic.', 'nova-x' ); ?></p>
                <section class="nova-x-section nova-x-architecture-container">
                    <p><?php esc_html_e( 'Architecture management features coming soon.', 'nova-x' ); ?></p>
                </section>
            </div>
        </div>
        <?php
    }

    /**
     * Render license page
     */
    public function render_license_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nova-x' ) );
        }
        
        // Load UI utilities
        $ui_utils_path = NOVA_X_PATH . 'admin/includes/ui-utils.php';
        if ( file_exists( $ui_utils_path ) ) {
            require_once $ui_utils_path;
        }
        ?>
        <div class="wrap nova-x-wrapper nova-x-dashboard-wrap">
            <?php
            // Render unified header
            if ( function_exists( 'render_plugin_header' ) ) {
                render_plugin_header();
            }
            ?>
            <div class="nova-x-page-content">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <p><?php esc_html_e( 'Manage your Nova-X license key.', 'nova-x' ); ?></p>
                <section class="nova-x-section nova-x-license-container">
                    <form method="post" action="">
                        <?php wp_nonce_field( 'nova_x_license_save', 'nova_x_license_nonce' ); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="nova_x_license_key"><?php esc_html_e( 'License Key', 'nova-x' ); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="nova_x_license_key" 
                                           name="license_key" 
                                           class="regular-text" 
                                           value="<?php echo esc_attr( get_option( 'nova_x_license_key', '' ) ); ?>" 
                                           placeholder="<?php esc_attr_e( 'Enter your license key...', 'nova-x' ); ?>" />
                                    <p class="description"><?php esc_html_e( 'Enter your Nova-X license key to activate premium features.', 'nova-x' ); ?></p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button( esc_html__( 'Save License', 'nova-x' ) ); ?>
                    </form>
                </section>
            </div>
        </div>
        <?php
    }

    /**
     * Render exports page
     */
    public function render_exports_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nova-x' ) );
        }
        
        // Load UI utilities
        $ui_utils_path = NOVA_X_PATH . 'admin/includes/ui-utils.php';
        if ( file_exists( $ui_utils_path ) ) {
            require_once $ui_utils_path;
        }
        ?>
        <div class="wrap nova-x-wrapper nova-x-dashboard-wrap">
            <?php
            // Render unified header
            if ( function_exists( 'render_plugin_header' ) ) {
                render_plugin_header();
            }
            ?>
            <div class="nova-x-page-content">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <p><?php esc_html_e( 'View and manage your exported themes.', 'nova-x' ); ?></p>
                <section class="nova-x-section nova-x-exports-container">
                    <p><?php esc_html_e( 'Exported themes will appear here.', 'nova-x' ); ?></p>
                </section>
            </div>
        </div>
        <?php
    }

    /**
     * Render beta page
     */
    public function render_beta_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nova-x' ) );
        }
        
        // Load UI utilities
        $ui_utils_path = NOVA_X_PATH . 'admin/includes/ui-utils.php';
        if ( file_exists( $ui_utils_path ) ) {
            require_once $ui_utils_path;
        }
        ?>
        <div class="wrap nova-x-wrapper nova-x-dashboard-wrap">
            <?php
            // Render unified header
            if ( function_exists( 'render_plugin_header' ) ) {
                render_plugin_header();
            }
            ?>
            <div class="nova-x-page-content">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <p><?php esc_html_e( 'Beta tools and experimental features.', 'nova-x' ); ?></p>
                <?php echo Nova_X_Notifier::warning( '<strong>' . esc_html__( 'Warning:', 'nova-x' ) . '</strong> ' . esc_html__( 'These are beta features and may be unstable. Use at your own risk.', 'nova-x' ) ); ?>
                <div class="nova-x-beta-container">
                    <p><?php esc_html_e( 'Beta tools coming soon.', 'nova-x' ); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render admin settings page
     */
    public function render_settings_page() {
        // Load UI utilities
        $ui_utils_path = NOVA_X_PATH . 'admin/includes/ui-utils.php';
        if ( file_exists( $ui_utils_path ) ) {
            require_once $ui_utils_path;
        }
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
        <div class="wrap nova-x-wrapper nova-x-dashboard-wrap">
            <?php
            // Render unified header
            if ( function_exists( 'render_plugin_header' ) ) {
                render_plugin_header();
            }
            ?>
            <div class="nova-x-page-content">
                <h1>Nova-X Settings</h1>
                
                <section class="nova-x-section">
                    <h2><?php esc_html_e( 'API Configuration', 'nova-x' ); ?></h2>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( 'nova_x_settings_group' );
                        do_settings_sections( 'nova_x_settings_group' );
                        ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'API Provider', 'nova-x' ); ?></th>
                                <td>
                                    <select name="nova_x_provider">
                                        <?php
                                        $selected = esc_attr( get_option( 'nova_x_provider', 'openai' ) );
                                        $providers = [ 'openai' => 'OpenAI', 'anthropic' => 'Anthropic', 'groq' => 'Groq', 'mistral' => 'Mistral', 'gemini' => 'Gemini' ];
                                        foreach ( $providers as $value => $label ) {
                                            echo "<option value='" . esc_attr( $value ) . "' " . selected( $selected, $value, false ) . ">" . esc_html( $label ) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'API Key', 'nova-x' ); ?></th>
                                <td>
                                    <input type="text" name="nova_x_api_key" value="<?php echo esc_attr( get_option( 'nova_x_api_key' ) ); ?>" class="regular-text" />
                                    <p class="description"><?php esc_html_e( 'Enter your API key for the selected provider.', 'nova-x' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Token Management', 'nova-x' ); ?></th>
                                <td>
                                    <button type="button" class="button rotate-token-btn" data-provider="<?php echo esc_attr( get_option( 'nova_x_provider', 'openai' ) ); ?>">
                                        🔁 <?php esc_html_e( 'Rotate Token', 'nova-x' ); ?>
                                    </button>
                                    <span class="rotate-token-status" style="margin-left: 10px; font-weight: bold;"></span>
                                    <p class="description"><?php esc_html_e( 'Rotate the encrypted API token for the selected provider. This will replace the existing token.', 'nova-x' ); ?></p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button( esc_html__( 'Save Settings', 'nova-x' ) ); ?>
                    </form>
                </section>

                <section class="nova-x-section">
                    <h2><?php esc_html_e( 'Usage Statistics', 'nova-x' ); ?></h2>
                    <?php
                    // Load Usage Tracker
                    require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-usage-tracker.php';
                    $total_tokens = Nova_X_Usage_Tracker::get_formatted_tokens();
                    $total_cost = Nova_X_Usage_Tracker::get_formatted_cost();
                    $provider_data = Nova_X_Usage_Tracker::get_all_provider_data();
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Total Tokens Used', 'nova-x' ); ?></th>
                            <td>
                                <strong style="font-size: 16px;">🔢 <?php echo esc_html( $total_tokens ); ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Total Estimated Cost', 'nova-x' ); ?></th>
                            <td>
                                <strong style="font-size: 16px;">💵 $<?php echo esc_html( $total_cost ); ?> USD</strong>
                            </td>
                        </tr>
                    </table>

                    <h3><?php esc_html_e( 'Per-Provider Breakdown', 'nova-x' ); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 200px;"><?php esc_html_e( 'Provider', 'nova-x' ); ?></th>
                                <th style="text-align: right;"><?php esc_html_e( 'Tokens Used', 'nova-x' ); ?></th>
                                <th style="text-align: right;"><?php esc_html_e( 'Cost (USD)', 'nova-x' ); ?></th>
                                <th style="text-align: right;"><?php esc_html_e( '% of Total', 'nova-x' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_tokens_num = Nova_X_Usage_Tracker::get_total_tokens();
                            $total_cost_num = Nova_X_Usage_Tracker::get_total_cost();
                            
                            if ( empty( $provider_data ) || $total_tokens_num === 0 ) {
                                echo '<tr><td colspan="4" style="text-align: center; padding: 20px; color: #999;">' . esc_html__( 'No usage data yet. Generate a theme to see statistics.', 'nova-x' ) . '</td></tr>';
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
                        <button type="button" class="button" id="nova_x_reset_tracker">🔄 <?php esc_html_e( 'Reset Tracker', 'nova-x' ); ?></button>
                        <span id="nova_x_reset_status" style="margin-left: 10px; font-weight: bold;"></span>
                    </p>
                </section>
            </div>
        </div>
        <?php
    }
}

