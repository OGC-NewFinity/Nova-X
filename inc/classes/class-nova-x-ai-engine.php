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
        
        // Load Token Manager
        require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-token-manager.php';
        
        // Try to load decrypted key from Token Manager first (most secure)
        $this->api_key = Nova_X_Token_Manager::get_decrypted_key( $this->provider );
        
        // Fallback to Provider Manager if Token Manager fails
        if ( empty( $this->api_key ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-provider-manager.php';
            $this->api_key = Nova_X_Provider_Manager::get_api_key( $this->provider );
        }
        
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
        
        // Load Token Manager
        require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-token-manager.php';
        
        // Try to load decrypted key from Token Manager first (most secure)
        $this->api_key = Nova_X_Token_Manager::get_decrypted_key( $provider );
        
        // Fallback to Provider Manager if Token Manager fails
        if ( empty( $this->api_key ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-provider-manager.php';
            $this->api_key = Nova_X_Provider_Manager::get_api_key( $provider );
        }
        
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
     * @return array Response array with success status, message, and output.
     */
    private function call_openai( $prompt ) {
        // Validate API key
        if ( empty( $this->api_key ) ) {
            return [
                'success' => false,
                'message' => 'OpenAI API key is missing. Please save it in Nova-X settings.',
            ];
        }

        // Sanitize prompt input
        $prompt = sanitize_textarea_field( $prompt );
        if ( empty( $prompt ) ) {
            return [
                'success' => false,
                'message' => 'Prompt cannot be empty.',
            ];
        }

        // Prepare request body with system message for WordPress theme development
        $body = [
            'model'       => 'gpt-4',
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'You are an expert WordPress theme developer. Respond only with clean HTML, CSS, and PHP for WordPress themes.',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.7,
        ];

        // Make API request
        $response = wp_remote_post(
            $this->api_url,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode( $body ),
                'timeout' => 60, // Increased timeout for theme generation
            ]
        );

        // Handle WordPress HTTP errors
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            return [
                'success' => false,
                'message' => 'OpenAI Error: ' . esc_html( $error_message ),
            ];
        }

        // Get response code and body
        $code = wp_remote_retrieve_response_code( $response );
        $body_content = wp_remote_retrieve_body( $response );
        $data = json_decode( $body_content, true );

        // Handle non-200 status codes
        if ( $code !== 200 ) {
            $error_message = 'HTTP Error ' . $code;
            
            // Extract error message from OpenAI response if available
            if ( isset( $data['error']['message'] ) ) {
                $error_message = $data['error']['message'];
            } elseif ( isset( $data['error']['code'] ) ) {
                $error_message = $data['error']['code'] . ': ' . ( $data['error']['message'] ?? 'Unknown error' );
            }
            
            return [
                'success' => false,
                'message' => 'OpenAI Error: ' . esc_html( $error_message ),
            ];
        }

        // Validate response structure
        if ( empty( $data['choices'] ) || empty( $data['choices'][0]['message']['content'] ) ) {
            return [
                'success' => false,
                'message' => 'OpenAI Error: Invalid response format.',
            ];
        }

        // Extract and sanitize output
        $output = trim( $data['choices'][0]['message']['content'] );
        
        if ( empty( $output ) ) {
            return [
                'success' => false,
                'message' => 'OpenAI Error: Empty response from API.',
            ];
        }

        // Track usage (tokens and cost)
        if ( isset( $data['usage']['total_tokens'] ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-usage-tracker.php';
            Nova_X_Usage_Tracker::log_usage( 'openai', absint( $data['usage']['total_tokens'] ) );
        }

        // Return success response
        return [
            'success'  => true,
            'output'   => $output,
            'provider' => 'openai',
        ];
    }

    /**
     * Call Anthropic (Claude) API
     *
     * @param string $prompt User prompt.
     * @return array Response array.
     */
    private function call_anthropic( $prompt ) {
        // Check for API key (try multiple sources in order of security)
        $api_key = $this->api_key;
        
        // Try Token Manager first (most secure)
        if ( empty( $api_key ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-token-manager.php';
            $api_key = Nova_X_Token_Manager::get_decrypted_key( 'anthropic' );
        }
        
        // Fallback to Provider Manager
        if ( empty( $api_key ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-provider-manager.php';
            $api_key = Nova_X_Provider_Manager::get_api_key( 'anthropic' );
        }
        
        // Fallback to legacy option
        if ( empty( $api_key ) ) {
            $api_key = get_option( 'nova_x_api_key_anthropic', '' );
        }
        
        // Stub mode if no API key available
        if ( empty( $api_key ) ) {
            return [
                'success'  => true,
                'message'  => 'Stubbed response for Anthropic',
                'output'   => '<?php // Simulated theme output ?>',
                'provider' => 'anthropic',
            ];
        }

        // Sanitize prompt input
        $prompt = sanitize_textarea_field( $prompt );
        if ( empty( $prompt ) ) {
            return [
                'success' => false,
                'message' => 'Prompt cannot be empty.',
            ];
        }

        // Set API URL
        $api_url = 'https://api.anthropic.com/v1/messages';

        // Prepare request body
        $body = [
            'model'       => 'claude-2.1',
            'max_tokens'  => 2048,
            'temperature' => 0.7,
            'system'      => 'You are an expert WordPress theme developer. Output only PHP, HTML, CSS.',
            'messages'    => [
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        // Make API request
        $response = wp_remote_post(
            $api_url,
            [
                'headers' => [
                    'x-api-key'         => $api_key,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type'      => 'application/json',
                ],
                'body'    => wp_json_encode( $body ),
                'timeout' => 60,
            ]
        );

        // Handle WordPress HTTP errors
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            return [
                'success' => false,
                'message' => 'Anthropic Error: ' . esc_html( $error_message ),
            ];
        }

        // Get response code and body
        $code = wp_remote_retrieve_response_code( $response );
        $body_content = wp_remote_retrieve_body( $response );
        $data = json_decode( $body_content, true );

        // Handle non-200 status codes
        if ( $code !== 200 ) {
            $error_message = 'HTTP Error ' . $code;
            
            // Extract error message from Anthropic response if available
            if ( isset( $data['error']['message'] ) ) {
                $error_message = $data['error']['message'];
            } elseif ( isset( $data['error']['type'] ) ) {
                $error_message = $data['error']['type'] . ': ' . ( $data['error']['message'] ?? 'Unknown error' );
            }
            
            return [
                'success' => false,
                'message' => 'Anthropic Error: ' . esc_html( $error_message ),
            ];
        }

        // Validate response structure
        if ( empty( $data['content'] ) || empty( $data['content'][0]['text'] ) ) {
            return [
                'success' => false,
                'message' => 'Anthropic Error: Invalid response format.',
            ];
        }

        // Extract and sanitize output
        $output = trim( $data['content'][0]['text'] );
        
        if ( empty( $output ) ) {
            return [
                'success' => false,
                'message' => 'Anthropic Error: Empty response from API.',
            ];
        }

        // Track usage (tokens and cost)
        if ( isset( $data['usage']['input_tokens'] ) && isset( $data['usage']['output_tokens'] ) ) {
            $total_tokens = absint( $data['usage']['input_tokens'] ) + absint( $data['usage']['output_tokens'] );
            require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-usage-tracker.php';
            Nova_X_Usage_Tracker::log_usage( 'anthropic', $total_tokens );
        }

        // Return success response
        return [
            'success'  => true,
            'output'   => $output,
            'provider' => 'anthropic',
        ];
    }

    /**
     * Call Groq API
     *
     * @param string $prompt User prompt.
     * @return array Response array.
     */
    private function call_groq( $prompt ) {
        // Check for API key (try multiple sources in order of security)
        $api_key = $this->api_key;
        
        // Try Token Manager first (most secure)
        if ( empty( $api_key ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-token-manager.php';
            $api_key = Nova_X_Token_Manager::get_decrypted_key( 'groq' );
        }
        
        // Fallback to Provider Manager
        if ( empty( $api_key ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-provider-manager.php';
            $api_key = Nova_X_Provider_Manager::get_api_key( 'groq' );
        }
        
        // Fallback to legacy option
        if ( empty( $api_key ) ) {
            $api_key = get_option( 'nova_x_api_key_groq', '' );
        }
        
        // Stub mode if no API key available
        if ( empty( $api_key ) ) {
            return [
                'success'  => true,
                'message'  => 'Stubbed response for Groq',
                'output'   => '<?php // Simulated theme output ?>',
                'provider' => 'groq',
            ];
        }

        // Sanitize prompt input
        $prompt = sanitize_textarea_field( $prompt );
        if ( empty( $prompt ) ) {
            return [
                'success' => false,
                'message' => 'Prompt cannot be empty.',
            ];
        }

        // Set API URL
        $api_url = 'https://api.groq.com/openai/v1/chat/completions';

        // Prepare request body
        $body = [
            'model'       => 'mixtral-8x7b-32768',
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'You are an expert WordPress theme developer. Output only PHP, HTML, CSS.',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.7,
            'max_tokens'  => 2048,
        ];

        // Make API request
        $response = wp_remote_post(
            $api_url,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode( $body ),
                'timeout' => 60,
            ]
        );

        // Handle WordPress HTTP errors
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            return [
                'success' => false,
                'message' => 'Groq Error: ' . esc_html( $error_message ),
            ];
        }

        // Get response code and body
        $code = wp_remote_retrieve_response_code( $response );
        $body_content = wp_remote_retrieve_body( $response );
        $data = json_decode( $body_content, true );

        // Handle non-200 status codes
        if ( $code !== 200 ) {
            $error_message = 'HTTP Error ' . $code;
            
            // Extract error message from Groq response if available
            if ( isset( $data['error']['message'] ) ) {
                $error_message = $data['error']['message'];
            } elseif ( isset( $data['error']['code'] ) ) {
                $error_message = $data['error']['code'] . ': ' . ( $data['error']['message'] ?? 'Unknown error' );
            }
            
            return [
                'success' => false,
                'message' => 'Groq Error: ' . esc_html( $error_message ),
            ];
        }

        // Validate response structure
        if ( empty( $data['choices'] ) || empty( $data['choices'][0]['message']['content'] ) ) {
            return [
                'success' => false,
                'message' => 'Groq Error: Invalid response format.',
            ];
        }

        // Extract and sanitize output
        $output = trim( $data['choices'][0]['message']['content'] );
        
        if ( empty( $output ) ) {
            return [
                'success' => false,
                'message' => 'Groq Error: Empty response from API.',
            ];
        }

        // Track usage (tokens and cost)
        if ( isset( $data['usage']['total_tokens'] ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-usage-tracker.php';
            Nova_X_Usage_Tracker::log_usage( 'groq', absint( $data['usage']['total_tokens'] ) );
        }

        // Return success response
        return [
            'success'  => true,
            'output'   => $output,
            'provider' => 'groq',
        ];
    }

    /**
     * Call Mistral API
     *
     * @param string $prompt User prompt.
     * @return array Response array.
     */
    private function call_mistral( $prompt ) {
        // Check for API key (try multiple sources in order of security)
        $api_key = $this->api_key;
        
        // Try Token Manager first (most secure)
        if ( empty( $api_key ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-token-manager.php';
            $api_key = Nova_X_Token_Manager::get_decrypted_key( 'mistral' );
        }
        
        // Fallback to Provider Manager
        if ( empty( $api_key ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-provider-manager.php';
            $api_key = Nova_X_Provider_Manager::get_api_key( 'mistral' );
        }
        
        // Fallback to legacy option
        if ( empty( $api_key ) ) {
            $api_key = get_option( 'nova_x_api_key_mistral', '' );
        }
        
        // Stub mode if no API key available
        if ( empty( $api_key ) ) {
            return [
                'success'  => true,
                'message'  => 'Stubbed response for Mistral',
                'output'   => '<?php // Simulated theme output ?>',
                'provider' => 'mistral',
            ];
        }

        // Sanitize prompt input
        $prompt = sanitize_textarea_field( $prompt );
        if ( empty( $prompt ) ) {
            return [
                'success' => false,
                'message' => 'Prompt cannot be empty.',
            ];
        }

        // Set API URL
        $api_url = 'https://api.mistral.ai/v1/chat/completions';

        // Prepare request body
        $body = [
            'model'       => 'mistral-large-latest',
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'You are an expert WordPress theme developer. Respond only with clean HTML, CSS, and PHP for WordPress themes.',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.7,
            'max_tokens'  => 2048,
        ];

        // Make API request
        $response = wp_remote_post(
            $api_url,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode( $body ),
                'timeout' => 60,
            ]
        );

        // Handle WordPress HTTP errors
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            return [
                'success' => false,
                'message' => 'Mistral Error: ' . esc_html( $error_message ),
            ];
        }

        // Get response code and body
        $code = wp_remote_retrieve_response_code( $response );
        $body_content = wp_remote_retrieve_body( $response );
        $data = json_decode( $body_content, true );

        // Handle non-200 status codes
        if ( $code !== 200 ) {
            $error_message = 'HTTP Error ' . $code;
            
            // Extract error message from Mistral response if available
            if ( isset( $data['error']['message'] ) ) {
                $error_message = $data['error']['message'];
            } elseif ( isset( $data['error']['code'] ) ) {
                $error_message = $data['error']['code'] . ': ' . ( $data['error']['message'] ?? 'Unknown error' );
            }
            
            return [
                'success' => false,
                'message' => 'Mistral Error: ' . esc_html( $error_message ),
            ];
        }

        // Validate response structure
        if ( empty( $data['choices'] ) || empty( $data['choices'][0]['message']['content'] ) ) {
            return [
                'success' => false,
                'message' => 'Mistral Error: Invalid response format.',
            ];
        }

        // Extract and sanitize output
        $output = trim( $data['choices'][0]['message']['content'] );
        
        if ( empty( $output ) ) {
            return [
                'success' => false,
                'message' => 'Mistral Error: Empty response from API.',
            ];
        }

        // Track usage (tokens and cost)
        if ( isset( $data['usage']['total_tokens'] ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-usage-tracker.php';
            Nova_X_Usage_Tracker::log_usage( 'mistral', absint( $data['usage']['total_tokens'] ) );
        }

        // Return success response
        return [
            'success'  => true,
            'output'   => $output,
            'provider' => 'mistral',
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
