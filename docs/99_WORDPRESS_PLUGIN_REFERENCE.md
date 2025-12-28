# Nova-X — WordPress Plugin Development Reference (Beginner Friendly)

This document explains how WordPress plugins work, in very simple terms,
so you can understand what you are building in Nova-X.

---

## 1. Folder Structure (Very Important)

Your plugin should look like this:

/nova-x/
├── nova-x.php
├── inc/
├── admin/
├── assets/
├── templates/
├── build/
├── docs/

- nova-x.php → main plugin file
- inc/ → PHP logic (AI, REST, generator)
- admin/ → settings page
- assets/ → CSS & JS
- build/ → compiled React files
- docs/ → documentation only

---

## 2. Main Plugin File (nova-x.php)

Every WordPress plugin MUST have a header like this:

```php
/**
 * Plugin Name: Nova-X
 * Description: AI-powered WordPress theme generator.
 * Version: 1.0.0
 * Author: OGC NewFinity
 */
3. Security Rule (ABSPATH)
Every PHP file must start with:

php
Copy code
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
This prevents hackers from opening plugin files directly.

4. Saving & Reading Settings (Options API)
WordPress has its own system for saving settings.

Save data:

php
Copy code
update_option( 'nova_x_api_key', $value );
Read data:

php
Copy code
$api_key = get_option( 'nova_x_api_key' );
5. Security for Forms (Very Important)
Before saving settings, always check:

php
Copy code
check_admin_referer( 'nova_x_save_settings' );
current_user_can( 'manage_options' );
This protects your admin pages.

6. WordPress Hooks (How WP Calls Your Code)
Common hooks you will use:

admin_menu → add admin menu

admin_enqueue_scripts → load CSS/JS

rest_api_init → create REST APIs

Example:

php
Copy code
add_action( 'admin_menu', [ $this, 'add_menu' ] );
7. REST API Example
Nova-X will use REST APIs.

Example route:

php
Copy code
register_rest_route( 'nova-x/v1', '/generate-theme', [
    'methods'  => 'POST',
    'callback' => [ $this, 'generate_theme' ],
    'permission_callback' => function () {
        return current_user_can( 'manage_options' );
    },
]);
8. Cleaning Data (Sanitization)
Always clean user input.

sanitize_text_field()

sanitize_textarea_field()

Always escape output:

esc_html()

esc_attr()

9. Final Rules to Remember
Never create a Documents/ folder again

Use docs/ only

Never trust user input

Never echo raw data

Always use WordPress functions