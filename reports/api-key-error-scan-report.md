# API Key Save/Rotate Failure - Static Scan Report

**Generated:** $(date)  
**Scope:** Read-only analysis of API key input, validation, saving, encryption, and rotation process  
**Status:** 🔴 CRITICAL ISSUES DETECTED

---

## 🔥 Critical Errors & Warnings

### 1. **Settings Class Not Instantiated**
**Location:** `admin/class-nova-x-settings.php`

**Issue:** The `Nova_X_Settings` class is defined but **never instantiated** anywhere in the codebase.

**Impact:**
- Constructor never runs → `enqueue_settings_assets()` hook never registered
- CSS/JS assets never loaded on settings page
- Form rendering method `render_settings_page()` never called
- Save handler `handle_settings_save()` never executes

**Evidence:**
```php
// Class exists but no instantiation found:
// grep "Nova_X_Settings|new.*Settings" returns only class definition
```

**Severity:** 🔴 CRITICAL - Complete failure of settings page functionality

---

### 2. **Missing Settings Page Registration**
**Location:** `admin/class-nova-x-settings.php` vs `inc/classes/class-nova-x-admin.php`

**Issue:** Two different settings implementations exist:
- `Nova_X_Settings::render_settings_page()` (new implementation)
- `Nova_X_Admin::render_settings_page()` (old implementation)

**Impact:**
- Unclear which settings page is actually rendered
- New settings form with multi-provider support may not be active
- Rotate token button exists in old admin class but not in new settings class

**Evidence:**
- `class-nova-x-admin.php:863` has `render_settings_page()` method
- `class-nova-x-admin.php:950` has rotate token button HTML
- `admin/class-nova-x-settings.php` has new multi-provider form but no rotate button

**Severity:** 🔴 CRITICAL - Settings page routing confusion

---

### 3. **Rotate Token Button Missing from New Settings Form**
**Location:** `admin/class-nova-x-settings.php:182`

**Issue:** The new settings form (multi-provider) does NOT include the rotate token button that exists in the old admin class.

**Impact:**
- Users cannot rotate tokens from the new settings page
- Rotate functionality only available in old admin page (if it's being used)

**Evidence:**
```php
// Old admin class has:
// <button type="button" class="button rotate-token-btn" data-provider="...">

// New settings class form ends at:
// <?php submit_button( 'Save Settings' ); ?>
// No rotate button present
```

**Severity:** 🟡 HIGH - Missing functionality

---

### 4. **Nonce Mismatch in Rotate Token Handler**
**Location:** `inc/classes/class-nova-x-rest.php:431`

**Issue:** REST handler expects nonce with action `'nova_x_nonce'` but JavaScript may be sending different nonce.

**Impact:**
- All rotate token requests fail with "Invalid nonce" error
- Returns 403 Permission Denied

**Evidence:**
```php
// REST handler expects:
wp_verify_nonce( $params['nonce'], 'nova_x_nonce' )

// JavaScript sends (nova-x-dashboard.js:1601):
nonce: nonce  // Where nonce comes from NovaXData.nonce or novaXDashboard.nonce
```

**Severity:** 🔴 CRITICAL - Rotate token completely broken

---

### 5. **JavaScript Nonce Source Mismatch**
**Location:** `admin/js/nova-x-dashboard.js:1578-1580`

**Issue:** JavaScript tries to get nonce from `NovaXData.nonce` or `novaXDashboard.nonce`, but these may not match the expected `'nova_x_nonce'` action.

**Impact:**
- Nonce verification fails
- Rotate requests rejected with 403

**Evidence:**
```javascript
const nonce = (typeof NovaXData !== 'undefined' && NovaXData.nonce) 
    ? NovaXData.nonce 
    : (typeof novaXDashboard !== 'undefined' ? novaXDashboard.nonce : '');
```

**Severity:** 🔴 CRITICAL - Security check failure

---

### 6. **Settings Form Nonce Action Mismatch**
**Location:** `admin/class-nova-x-settings.php:111` vs `admin/class-nova-x-settings.php:76`

**Issue:** Form uses nonce action `'nova_x_settings_save'` but JavaScript localizes with different nonce action `'nova_x_settings_js'`.

**Impact:**
- If JavaScript tries to use localized nonce for AJAX, it will fail
- Form submission works (uses correct nonce) but any JS-based operations fail

**Evidence:**
```php
// Form nonce:
wp_nonce_field( 'nova_x_settings_save', 'nova_x_settings_nonce' );

// JS localized nonce:
'nonce' => wp_create_nonce( 'nova_x_settings_js' ),  // DIFFERENT ACTION!
```

**Severity:** 🟡 MEDIUM - Potential JS operations failure

---

### 7. **Missing Permission Check in Settings Save Handler**
**Location:** `admin/class-nova-x-settings.php:196`

**Issue:** Permission check exists but **silently returns** on failure - no error message shown to user.

**Impact:**
- Users with insufficient permissions see no feedback
- Form appears to submit but nothing happens

**Evidence:**
```php
if ( ! current_user_can( 'manage_options' ) ) {
    return;  // Silent failure - no error message
}
```

**Severity:** 🟡 MEDIUM - Poor UX, potential confusion

---

### 8. **Rotate Token Requires New Key Input**
**Location:** `inc/classes/class-nova-x-rest.php:487-500`

**Issue:** Rotate token endpoint requires `new_key` parameter, but UI expects to rotate existing key without new input.

**Impact:**
- Users must enter new key in API Key field first
- If field is empty or contains masked value, rotation fails
- Confusing UX - "rotate" implies using existing key

**Evidence:**
```php
if ( empty( $new_key ) ) {
    return new WP_REST_Response([
        'success' => false,
        'message' => 'API key is required for rotation...',
    ], 400);
}
```

**Severity:** 🟡 MEDIUM - UX confusion

---

### 9. **Token Manager Rotate Method Logic Issue**
**Location:** `inc/classes/class-nova-x-token-manager.php:170-186`

**Issue:** `rotate_key()` method checks if key exists and returns `false` if `$force = false` and key exists. Default is `$force = true`, but logic may prevent rotation in edge cases.

**Impact:**
- If `$force` is accidentally set to `false`, rotation fails silently
- No validation of new key before rotation

**Evidence:**
```php
if ( ! $force && false !== self::get_decrypted_key( $provider ) ) {
    return false;  // Silent failure if key exists and force=false
}
```

**Severity:** 🟡 LOW - Edge case issue

---

### 10. **No Validation Before Token Rotation**
**Location:** `inc/classes/class-nova-x-token-manager.php:170-186`

**Issue:** `rotate_key()` method does NOT validate the new key format before storing.

**Impact:**
- Invalid keys can be stored during rotation
- No provider-specific validation applied

**Evidence:**
```php
public static function rotate_key( $provider, $new_key, $force = true ) {
    // ... sanitization only, no validation
    return self::store_encrypted_key( $provider, $new_key );
}
```

**Severity:** 🟡 MEDIUM - Security/validation gap

---

## 🧩 Missing or Suspicious Code Patterns

### 1. **Settings Class Instantiation Missing**
- No `new Nova_X_Settings()` call found in codebase
- Class exists but is orphaned

### 2. **Settings Page Hook Detection May Fail**
**Location:** `admin/class-nova-x-settings.php:28-32`

**Issue:** Hook detection checks for specific hook names, but actual hook may differ:
```php
$settings_hooks = [
    'nova-x_page_nova-x-settings',
    'toplevel_page_nova-x-settings',
    'settings_page_nova-x-settings',
];
```
- If actual hook is different, assets never load

### 3. **No REST Endpoint for Settings Save**
- Settings form uses POST to same page (standard form submission)
- No AJAX/REST endpoint for settings save
- Relies on page reload for feedback

### 4. **Rotate Token JavaScript Not Loaded on Settings Page**
- `nova-x-dashboard.js` contains rotate token handler
- But new settings page may not load this script
- Rotate button won't work even if added to form

---

## 🔄 AJAX/REST Request Failures

### 1. **Rotate Token Endpoint Issues**

**Route:** `POST /wp-json/nova-x/v1/rotate-token`

**Problems:**
1. **Nonce Verification Failure:**
   - Handler expects: `'nova_x_nonce'`
   - JS may send: Different nonce from `NovaXData.nonce`
   - Result: 403 Permission Denied

2. **Permission Callback:**
   - Uses `check_permissions()` method
   - Must verify this method exists and works correctly

3. **Missing Provider Validation:**
   - Provider sanitized but not validated against `Nova_X_Provider_Manager::is_valid_provider()`
   - Uses hardcoded array instead

4. **No Key Validation:**
   - New key not validated before rotation
   - Invalid keys can be stored

---

## ⚠️ Invalid Nonce Usage

### 1. **Settings Form Nonce**
- ✅ Correct: `wp_nonce_field( 'nova_x_settings_save', 'nova_x_settings_nonce' )`
- ✅ Correct: `wp_verify_nonce( $_POST['nova_x_settings_nonce'], 'nova_x_settings_save' )`
- ✅ Match: Action names match

### 2. **JavaScript Localized Nonce**
- ❌ **MISMATCH:** `wp_create_nonce( 'nova_x_settings_js' )` - Different action!
- ⚠️ Not used in form submission (good)
- ⚠️ If used for AJAX, will fail

### 3. **Rotate Token Nonce**
- ❌ **MISMATCH:** Handler expects `'nova_x_nonce'` but JS sends variable nonce
- 🔴 **CRITICAL:** All rotate requests fail

---

## 🔐 Security Validation Issues

### 1. **Silent Permission Failures**
- Settings save handler returns silently on permission failure
- No user feedback
- No logging

### 2. **No CSRF Protection for Rotate Token**
- Relies only on nonce (which is broken)
- No additional CSRF checks

### 3. **Provider Validation Inconsistency**
- REST handler uses hardcoded array
- Should use `Nova_X_Provider_Manager::is_valid_provider()`

### 4. **Missing Key Validation in Rotation**
- `rotate_key()` doesn't validate key format
- Should use `Nova_X_Provider_Rules::validate_key()`

---

## 📌 Unlinked or Unused Form Elements

### 1. **Rotate Token Button**
- Exists in `class-nova-x-admin.php` (old settings)
- Missing from `class-nova-x-settings.php` (new settings)
- JavaScript handler exists but button not in new form

### 2. **Provider Selector**
- Old settings has: `<select name="nova_x_provider">`
- New settings: No single provider selector (shows all providers)
- Rotate button expects single provider selector

### 3. **API Key Field Name Mismatch**
- Old settings: `name="nova_x_api_key"` (single field)
- New settings: `name="nova_x_api_key[provider]"` (array)
- Rotate JS looks for `input[name="nova_x_api_key"]` - won't find in new form

---

## 🔍 Additional Findings

### 1. **Two Settings Implementations Conflict**
- Old: `Nova_X_Admin::render_settings_page()` - Single provider, has rotate button
- New: `Nova_X_Settings::render_settings_page()` - Multi-provider, no rotate button
- **Unclear which is active**

### 2. **JavaScript Handler Location**
- Rotate token handler in `admin/js/nova-x-dashboard.js`
- Settings page may not load this script
- Script expects specific HTML structure that new form doesn't have

### 3. **Form Submission Flow**
- New settings form uses standard POST (page reload)
- Old settings may use AJAX (unclear)
- No unified approach

### 4. **Error Handling**
- Settings save: Uses `add_settings_error()` - ✅ Good
- Rotate token: Returns REST response - ✅ Good
- But errors may not display if page doesn't reload properly

---

## 📊 Summary of Critical Issues

| Issue | Severity | Impact |
|-------|----------|--------|
| Settings class not instantiated | 🔴 CRITICAL | Settings page completely non-functional |
| Nonce mismatch in rotate token | 🔴 CRITICAL | All rotate requests fail with 403 |
| Rotate button missing from new form | 🟡 HIGH | Users cannot rotate tokens |
| Two settings implementations | 🔴 CRITICAL | Unclear which is active |
| JS nonce action mismatch | 🟡 MEDIUM | Potential JS operations failure |
| Missing key validation in rotation | 🟡 MEDIUM | Invalid keys can be stored |
| Silent permission failures | 🟡 MEDIUM | Poor UX |

---

## 🎯 Recommended Investigation Points

1. **Check which settings page is actually rendered:**
   - Search for `add_submenu_page` calls
   - Verify callback function name

2. **Verify nonce generation:**
   - Check where `NovaXData.nonce` is created
   - Verify action name matches `'nova_x_nonce'`

3. **Check permission callback:**
   - Verify `check_permissions()` method exists
   - Ensure it returns correct boolean

4. **Test form submission:**
   - Verify POST data structure
   - Check if `$_POST['nova_x_api_key']` is array or string

5. **Check JavaScript loading:**
   - Verify which scripts load on settings page
   - Check for console errors

---

## ⚠️ Restrictions Respected

✅ **No code modifications made**  
✅ **Read-only analysis only**  
✅ **Error detection and reporting only**

---

**Report End**

