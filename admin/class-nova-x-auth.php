<?php
/**
 * Nova-X Authentication System
 * 
 * Handles plugin-specific user registration, login, and logout.
 * This is separate from WordPress native user system.
 * 
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Nova_X_Auth {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * Register REST API routes for authentication
     */
    public function register_routes() {
        register_rest_route(
            'nova-x/v1',
            '/register',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_register' ],
                'permission_callback' => '__return_true',
            ]
        );

        register_rest_route(
            'nova-x/v1',
            '/login',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_login' ],
                'permission_callback' => '__return_true',
            ]
        );

        register_rest_route(
            'nova-x/v1',
            '/logout',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_logout' ],
                'permission_callback' => '__return_true',
            ]
        );
    }

    /**
     * Handle user registration
     * 
     * @param WP_REST_Request $request The REST request object
     * @return WP_REST_Response|WP_Error
     */
    public function handle_register( WP_REST_Request $request ) {
        // Get and sanitize input
        $name     = sanitize_text_field( $request->get_param( 'name' ) );
        $email    = sanitize_email( $request->get_param( 'email' ) );
        $password = $request->get_param( 'password' );

        // Validate required fields
        if ( empty( $name ) ) {
            return new WP_Error(
                'missing_name',
                __( 'Name is required.', 'nova-x' ),
                [ 'status' => 400 ]
            );
        }

        if ( empty( $email ) ) {
            return new WP_Error(
                'missing_email',
                __( 'Email is required.', 'nova-x' ),
                [ 'status' => 400 ]
            );
        }

        // Validate email format
        if ( ! is_email( $email ) ) {
            return new WP_Error(
                'invalid_email',
                __( 'Invalid email format.', 'nova-x' ),
                [ 'status' => 400 ]
            );
        }

        if ( empty( $password ) ) {
            return new WP_Error(
                'missing_password',
                __( 'Password is required.', 'nova-x' ),
                [ 'status' => 400 ]
            );
        }

        // Check password length (minimum 6 characters)
        if ( strlen( $password ) < 6 ) {
            return new WP_Error(
                'weak_password',
                __( 'Password must be at least 6 characters long.', 'nova-x' ),
                [ 'status' => 400 ]
            );
        }

        // Get existing users
        $users = get_option( 'nova_x_users', [] );
        $email_lower = strtolower( $email );

        // Check if email already exists
        if ( isset( $users[ $email_lower ] ) ) {
            return new WP_Error(
                'email_exists',
                __( 'An account with this email already exists.', 'nova-x' ),
                [ 'status' => 409 ]
            );
        }

        // Hash password
        $hashed_password = password_hash( $password, PASSWORD_DEFAULT );

        // Save user
        $users[ $email_lower ] = [
            'name'     => $name,
            'password' => $hashed_password,
            'created'  => current_time( 'mysql' ),
        ];

        update_option( 'nova_x_users', $users );

        return new WP_REST_Response(
            [
                'success' => true,
                'message' => __( 'Registration successful.', 'nova-x' ),
            ],
            200
        );
    }

    /**
     * Handle user login
     * 
     * @param WP_REST_Request $request The REST request object
     * @return WP_REST_Response|WP_Error
     */
    public function handle_login( WP_REST_Request $request ) {
        // Start session if not already started
        if ( ! session_id() ) {
            session_start();
        }

        // Get and sanitize input
        $email    = sanitize_email( $request->get_param( 'email' ) );
        $password = $request->get_param( 'password' );

        // Validate required fields
        if ( empty( $email ) ) {
            return new WP_Error(
                'missing_email',
                __( 'Email is required.', 'nova-x' ),
                [ 'status' => 400 ]
            );
        }

        if ( empty( $password ) ) {
            return new WP_Error(
                'missing_password',
                __( 'Password is required.', 'nova-x' ),
                [ 'status' => 400 ]
            );
        }

        // Get users
        $users = get_option( 'nova_x_users', [] );
        $email_lower = strtolower( $email );

        // Check if user exists
        if ( ! isset( $users[ $email_lower ] ) ) {
            return new WP_Error(
                'invalid_credentials',
                __( 'Invalid email or password.', 'nova-x' ),
                [ 'status' => 401 ]
            );
        }

        $user = $users[ $email_lower ];

        // Verify password
        if ( ! password_verify( $password, $user['password'] ) ) {
            return new WP_Error(
                'invalid_credentials',
                __( 'Invalid email or password.', 'nova-x' ),
                [ 'status' => 401 ]
            );
        }

        // Set session
        $_SESSION['nova_x_user'] = [
            'email' => $email_lower,
            'name'  => $user['name'],
        ];

        return new WP_REST_Response(
            [
                'success' => true,
                'message' => __( 'Login successful.', 'nova-x' ),
                'user'    => [
                    'email' => $email_lower,
                    'name'  => $user['name'],
                ],
            ],
            200
        );
    }

    /**
     * Handle user logout
     * 
     * @param WP_REST_Request $request The REST request object
     * @return WP_REST_Response
     */
    public function handle_logout( WP_REST_Request $request ) {
        // Start session if not already started
        if ( ! session_id() ) {
            session_start();
        }

        // Unset session data
        if ( isset( $_SESSION['nova_x_user'] ) ) {
            unset( $_SESSION['nova_x_user'] );
        }

        // Destroy session
        session_destroy();

        return new WP_REST_Response(
            [
                'success' => true,
                'message' => __( 'Logout successful.', 'nova-x' ),
            ],
            200
        );
    }
}

