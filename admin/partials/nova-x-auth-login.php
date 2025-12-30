<?php
/**
 * Login Form Partial
 * 
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div id="nova-x-login-container" class="nova-x-auth-form-container">
    <h2><?php esc_html_e( 'Login', 'nova-x' ); ?></h2>
    
    <div id="nova-x-login-message" class="nova-x-auth-message" style="display: none;"></div>
    
    <form id="nova-x-login-form" class="nova-x-auth-form">
        <p>
            <label for="nova-x-login-email">
                <?php esc_html_e( 'Email', 'nova-x' ); ?>
            </label>
            <input 
                type="email" 
                name="email" 
                id="nova-x-login-email" 
                placeholder="<?php esc_attr_e( 'Email', 'nova-x' ); ?>" 
                required 
                class="regular-text"
            />
        </p>
        
        <p>
            <label for="nova-x-login-password">
                <?php esc_html_e( 'Password', 'nova-x' ); ?>
            </label>
            <input 
                type="password" 
                name="password" 
                id="nova-x-login-password" 
                placeholder="<?php esc_attr_e( 'Password', 'nova-x' ); ?>" 
                required 
                class="regular-text"
            />
        </p>
        
        <p class="submit">
            <button type="submit" class="button button-primary">
                <?php esc_html_e( 'Login', 'nova-x' ); ?>
            </button>
        </p>
        
        <p class="nx-auth-switch">
            <?php esc_html_e( 'Don\'t have an account?', 'nova-x' ); ?>
            <a href="#" id="switch-to-register"><?php esc_html_e( 'Register', 'nova-x' ); ?></a>
        </p>
    </form>
</div>

