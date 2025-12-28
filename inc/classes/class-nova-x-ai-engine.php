<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_AI_Engine {

    private $provider;
    private $api_key;
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    
    /**
     * Fallback chain for provider selection when primary provider fails.
     * Providers are tried in order until one succeeds.
     *
     * @var array
     */
    private $fallback_chain = [ 'openai', 'groq', 'mistral', 'anthropic' ];

    /**
     * Constructor - Load provider and API key from WordPress options
     */
    public function __construct() {
        // Load provider from options (default: openai)
        $this->provider = get_option( 'nova_x_provider', 'openai' );
        
        // Load API key for the selected provider using Provider Manager
        require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-provider-manager.php';
        $this->api_key = Nova_X_Provider_Manager::get_api_key( $this->provider );
        
        // Fallback to legacy option if provider-specific key is empty
        if ( empty( $this->api_key ) ) {
            $this->api_key = trim( (string) get_option( 'nova_x_api_key', '' ) );
        }
        
        // Set API URL based on provider
        $this->set_api_url();
    }

    /**
     * Set API URL based on current provider
     *
     * @return void
     */
    private function set_api_url() {
        switch ( $this->provider ) {
            case 'openai':
                $this->api_url = 'https://api.openai.com/v1/chat/completions';
                break;
            case 'anthropic':
            case 'claude':
                $this->api_url = 'https://api.anthropic.com/v1/messages';
                break;
            case 'groq':
                $this->api_url = 'https://api.groq.com/openai/v1/chat/completions';
                break;
            case 'mistral':
                $this->api_url = 'https://api.mistral.ai/v1/chat/completions';
                break;
            case 'gemini':
                $this->api_url = 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent';
                break;
            case 'cohere':
                $this->api_url = 'https://api.cohere.ai/v1/chat';
                break;
            default:
                $this->api_url = 'https://api.openai.com/v1/chat/completions';
                break;
        }
    }

    /**
     * Set the AI provider to use.
     *
     * @param string $provider Provider name (openai, anthropic, groq, mistral, etc.)
     * @return void
     */
    public function set_provider( $provider ) {
        $supported_providers = defined( 'NOVA_X_SUPPORTED_PROVIDERS' ) ? NOVA_X_SUPPORTED_PROVIDERS : [];

        if ( in_array( $provider, $supported_providers, true ) ) {
            $this->provider = sanitize_text_field( $provider );
            $this->set_api_url();
            
            // Reload API key for the new provider
            require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-provider-manager.php';
            $this->api_key = Nova_X_Provider_Manager::get_api_key( $this->provider );
            
            // Fallback to legacy option if provider-specific key is empty
            if ( empty( $this->api_key ) ) {
                $this->api_key = trim( (string) get_option( 'nova_x_api_key', '' ) );
            }
        }
    }

    /**
     * Generate theme code using the selected AI provider with fallback logic.
     * If the primary provider fails, automatically tries providers from the fallback chain.
     *
     * @param string $prompt User prompt/description for theme generation.
     * @return array Response array with success status, message, output, and provider used.
     */
    public function generate_theme_code( $prompt ) {
        $tried = [];
        
        // Normalize the selected provider
        $selected_provider = $this->normalize_provider_name( $this->provider );
        
        // Normalize all providers in fallback chain
        $normalized_fallback = array_map( function( $provider ) {
            return $this->normalize_provider_name( $provider );
        }, $this->fallback_chain );
        
        // Merge selected provider with fallback chain, ensuring selected provider is tried first
        // Remove duplicates while preserving order
        $providers = array_values( array_unique( array_merge( [ $selected_provider ], $normalized_fallback ) ) );

        foreach ( $providers as $provider ) {
            // Skip if already tried
            if ( in_array( $provider, $tried, true ) ) {
                continue;
            }

            // Switch to this provider and reload configuration
            $this->switch_to_provider( $provider );

            // Attempt to generate with this provider
            $response = $this->dispatch_provider( $prompt );

            // If successful, return with provider information
            if ( ! empty( $response['success'] ) ) {
                $response['provider'] = $provider;
                return $response;
            }

            // Track failed provider
            $tried[] = $provider;
        }

        // All providers failed
        return [
            'success' => false,
            'message' => 'All providers failed.',
            'tried'   => $tried,
        ];
    }

    /**
     * Switch to a different provider and reload its configuration.
     *
     * @param string $provider Provider name to switch to.
     * @return void
     */
    private function switch_to_provider( $provider ) {
        $this->provider = $provider;
        $this->set_api_url();
        
        // Reload API key for the new provider
        require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-provider-manager.php';
        $this->api_key = Nova_X_Provider_Manager::get_api_key( $provider );
        
        // Fallback to legacy option if provider-specific key is empty
        if ( empty( $this->api_key ) ) {
            $this->api_key = trim( (string) get_option( 'nova_x_api_key', '' ) );
        }
    }

    /**
     * Dispatch request to the appropriate provider method.
     *
     * @param string $prompt User prompt.
     * @return array Response array with success status, message, and output.
     */
    private function dispatch_provider( $prompt ) {
        try {
            switch ( $this->provider ) {
                case 'openai':
                    return $this->call_openai( $prompt );
                case 'anthropic':
                    return $this->call_anthropic( $prompt );
                case 'groq':
                    return $this->call_groq( $prompt );
                case 'mistral':
                    return $this->call_mistral( $prompt );
                default:
                    return [
                        'success' => false,
                        'message' => 'Unsupported provider: ' . $this->provider,
                    ];
            }
        } catch ( \Exception $e ) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Normalize provider name (e.g., claude -> anthropic)
     *
     * @param string $provider Provider name.
     * @return string Normalized provider name.
     */
    private function normalize_provider_name( $provider ) {
        $mapping = [
            'claude' => 'anthropic',
        ];
        
        return isset( $mapping[ $provider ] ) ? $mapping[ $provider ] : $provider;
    }

    /**
     * Call OpenAI API
     *
     * @param string $prompt User prompt.
     * @return array Response array.
     */
    private function call_openai( $prompt ) {
        if ( empty( $this->api_key ) ) {
            return [
                'success' => false,
                'message' => 'OpenAI API key is missing. Please save it in Nova-X settings.',
            ];
        }

        $body = [
            'model'       => 'gpt-4o-mini',
            'messages'    => [
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.7,
        ];

        $response = wp_remote_post(
            $this->api_url,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode( $body ),
                'timeout' => 30,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => 'OpenAI request failed: ' . $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 || empty( $data['choices'][0]['message']['content'] ) ) {
            return [
                'success' => false,
                'message' => 'OpenAI API error.',
                'output'  => $data,
            ];
        }

        return [
            'success' => true,
            'message' => 'OpenAI used',
            'output'  => trim( $data['choices'][0]['message']['content'] ),
        ];
    }

    /**
     * Call Anthropic (Claude) API
     *
     * @param string $prompt User prompt.
     * @return array Response array.
     */
    private function call_anthropic( $prompt ) {
        if ( empty( $this->api_key ) ) {
            return [
                'success' => false,
                'message' => 'Anthropic API key is missing. Please save it in Nova-X settings.',
            ];
        }

        // Anthropic API stub - to be implemented later
        return [
            'success' => true,
            'message' => 'Anthropic used',
            'output'  => '...',
        ];
    }

    /**
     * Call Groq API
     *
     * @param string $prompt User prompt.
     * @return array Response array.
     */
    private function call_groq( $prompt ) {
        if ( empty( $this->api_key ) ) {
            return [
                'success' => false,
                'message' => 'Groq API key is missing. Please save it in Nova-X settings.',
            ];
        }

        // Groq API stub - to be implemented later
        // Simulated failure for testing fallback logic
        return [
            'success' => false,
            'message' => 'Groq test failure',
        ];
    }

    /**
     * Call Mistral API
     *
     * @param string $prompt User prompt.
     * @return array Response array.
     */
    private function call_mistral( $prompt ) {
        if ( empty( $this->api_key ) ) {
            return [
                'success' => false,
                'message' => 'Mistral API key is missing. Please save it in Nova-X settings.',
            ];
        }

        // Mistral API stub - to be implemented later
        return [
            'success' => true,
            'message' => 'Mistral used',
            'output'  => '...',
        ];
    }

    /**
     * Generate response from selected AI provider.
     * Legacy method for backward compatibility.
     *
     * @param string $prompt
     * @return string|WP_Error
     */
    public function generate( $prompt ) {
        $result = $this->generate_theme_code( $prompt );
        
        if ( ! $result['success'] ) {
            return new WP_Error(
                'nova_x_ai_error',
                $result['message'],
                $result
            );
        }
        
        return $result['output'];
    }
}
