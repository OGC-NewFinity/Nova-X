<?php
/**
 * Registration Form Partial
 * 
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div id="nx-register" class="nova-x-auth-form-container" style="display: none;">
    <h2><?php esc_html_e( 'Register', 'nova-x' ); ?></h2>
    
    <div id="nova-x-register-message" class="nova-x-auth-message" style="display: none;"></div>
    
    <form id="nova-x-register-form" class="nova-x-auth-form">
        <p>
            <label for="nova-x-register-name">
                <?php esc_html_e( 'Your Name', 'nova-x' ); ?>
            </label>
            <input 
                type="text" 
                name="name" 
                id="nova-x-register-name" 
                placeholder="<?php esc_attr_e( 'Your Name', 'nova-x' ); ?>" 
                required 
                class="regular-text"
            />
        </p>
        
        <p>
            <label for="nova-x-register-email">
                <?php esc_html_e( 'Email', 'nova-x' ); ?>
            </label>
            <input 
                type="email" 
                name="email" 
                id="nova-x-register-email" 
                placeholder="<?php esc_attr_e( 'Email', 'nova-x' ); ?>" 
                required 
                class="regular-text"
            />
        </p>
        
        <p>
            <label for="nova-x-register-password">
                <?php esc_html_e( 'Password', 'nova-x' ); ?>
            </label>
            <input 
                type="password" 
                name="password" 
                id="nova-x-register-password" 
                placeholder="<?php esc_attr_e( 'Password (min. 6 characters)', 'nova-x' ); ?>" 
                required 
                minlength="6"
                class="regular-text"
            />
        </p>
        
        <p class="submit">
            <button type="submit" class="button button-primary">
                <?php esc_html_e( 'Register', 'nova-x' ); ?>
            </button>
        </p>
        
        <p class="nx-auth-switch">
            <?php esc_html_e( 'Already have an account?', 'nova-x' ); ?>
            <a href="#" id="switch-to-login"><?php esc_html_e( 'Login', 'nova-x' ); ?></a>
        </p>
    </form>
</div>

