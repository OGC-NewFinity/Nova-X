# Architect-Generator Merge Plan

**Date:** 2024  
**Status:** ✅ **MIGRATION COMPLETED**  
**Decision:** Deprecate `Nova_X_Architect` in favor of `Nova_X_Generator`

---

## Executive Summary

Both `class-nova-x-architect.php` and `class-nova-x-generator.php` contain overlapping functionality for theme generation. `Nova_X_Generator` is the more robust implementation with better slug collision handling, more complete theme file generation, and improved error handling. This document outlines the merge strategy to consolidate on `Nova_X_Generator` and deprecate `Nova_X_Architect`.

---

## 1. Method Comparison Table

| Method | Architect | Generator | Status | Notes |
|--------|-----------|-----------|--------|-------|
| `__construct()` | ✅ | ✅ | **DUPLICATE** | Generator uses `get_theme_root()` (better) vs Architect uses `WP_CONTENT_DIR . '/themes/'` |
| `build_theme($site_title, $prompt)` | ✅ Public | ✅ Public | **DUPLICATE** | Generator has superior slug collision handling |
| `generate_files()` | ✅ Private | ❌ | **ARCHITECT ONLY** | Logic merged into Generator's `build_theme()` |
| `write_file($path, $content)` | ❌ | ✅ Private | **GENERATOR ONLY** | Better error handling with fopen/fwrite pattern |

---

## 2. Key Differences Analysis

### 2.1 Slug Generation & Collision Handling

**Architect (Basic):**
```php
$slug = sanitize_title( $site_title );
if ( empty( $slug ) ) {
    $slug = 'nova-x-theme';
}
// Returns error if directory exists
if ( file_exists( $target_dir ) ) {
    return ['success' => false, 'message' => 'Theme folder already exists'];
}
```

**Generator (Advanced):**
```php
$slug = sanitize_title( $site_title );
if ( empty( $slug ) ) {
    $slug = 'nova-x-theme';
}
// Append timestamp to ensure uniqueness
if ( ! preg_match( '/-\d{10}$/', $slug ) ) {
    $timestamp = time();
    $slug .= '-' . $timestamp;
}
// Retry with microtime if collision detected
if ( file_exists( $target_dir ) ) {
    $microtime = (int) ( microtime( true ) * 1000 );
    $slug .= '-' . $microtime;
    // Final check before error
}
```

**Decision:** ✅ **Generator's approach is superior** - Prevents slug collisions automatically instead of failing.

---

### 2.2 Theme Directory Path

**Architect:**
```php
$this->themes_dir = WP_CONTENT_DIR . '/themes/';
```

**Generator:**
```php
$this->themes_dir = get_theme_root();
```

**Decision:** ✅ **Generator's approach is better** - Uses WordPress core function which handles multisite and filters.

---

### 2.3 Generated Theme Files

**Architect generates:**
- `style.css` (basic)
- `functions.php` (minimal)
- `index.php` (basic)

**Generator generates:**
- `style.css` (same)
- `functions.php` (includes theme support: `title-tag`, `post-thumbnails`)
- `index.php` (more complete with prompt display)
- `header.php` (full HTML structure)
- `footer.php` (full HTML structure)

**Decision:** ✅ **Generator creates more complete themes** - Includes header/footer templates and better theme support.

---

### 2.4 File Writing Implementation

**Architect:**
```php
$written = file_put_contents( $file_path, $content );
if ( false === $written ) {
    return error;
}
```

**Generator:**
```php
private function write_file( $path, $content ) {
    $handle = fopen( $path, 'w' );
    if ( $handle ) {
        $written = fwrite( $handle, $content );
        fclose( $handle );
        // Better error logging
    }
}
```

**Decision:** ✅ **Generator's approach is more explicit** - Better error handling and logging.

---

## 3. Migration/Merge Decisions

### 3.1 Methods to Keep in Generator

| Method | Action | Reason |
|--------|--------|--------|
| `__construct()` | ✅ Keep as-is | Already uses `get_theme_root()` |
| `build_theme()` | ✅ Keep as-is | Superior implementation with collision handling |
| `write_file()` | ✅ Keep as-is | Better error handling |

### 3.2 Methods to Remove from Architect

| Method | Action | Reason |
|--------|--------|--------|
| `__construct()` | ❌ Remove | Redundant |
| `build_theme()` | ❌ Remove | Generator version is superior |
| `generate_files()` | ❌ Remove | Logic already in Generator's `build_theme()` |

### 3.3 No Logic Loss

✅ **All Architect functionality is preserved in Generator:**
- Slug generation: ✅ (Generator is better)
- Directory creation: ✅ (Generator is better)
- File writing: ✅ (Generator is better)
- Error handling: ✅ (Generator is better)

**Conclusion:** No migration needed - Generator already contains all Architect functionality with improvements.

---

## 4. Reference Usage Map

### 4.1 Current Instantiations

| File | Line | Usage | Action Required |
|------|------|-------|-----------------|
| `inc/classes/class-nova-x-rest.php` | 302 | `require_once` Architect | ✅ Replace with Generator |
| `inc/classes/class-nova-x-rest.php` | 303 | `new Nova_X_Architect()` | ✅ Replace with `new Nova_X_Generator()` |
| `inc/classes/class-nova-x-rest.php` | 304 | `$architect->build_theme()` | ✅ Replace with `$generator->build_theme()` |

### 4.2 Class Loading

| File | Line | Status | Action |
|------|------|--------|--------|
| `nova-x.php` | 73 | ✅ Generator loaded | No change needed |
| `nova-x.php` | - | ❌ Architect NOT loaded | No change needed (loaded dynamically in REST) |

---

## 5. Deprecated Methods List

### 5.1 Entire Class Deprecation

**Class:** `Nova_X_Architect`  
**File:** `inc/classes/class-nova-x-architect.php`  
**Status:** 🚫 **DEPRECATED**  
**Replacement:** `Nova_X_Generator`  
**Deprecation Date:** TBD (after migration)  
**Removal Date:** TBD (after full migration and testing)

### 5.2 Deprecation Notice Template

```php
/**
 * Theme Building Logic
 * 
 * @deprecated 0.2.0 Use Nova_X_Generator instead.
 * @see Nova_X_Generator
 */
class Nova_X_Architect {
    // ... existing code ...
}
```

---

## 6. Final Merge Checklist

### Phase 1: Code Updates ✅ COMPLETED
- [x] Update `inc/classes/class-nova-x-rest.php` line 302: Replace `require_once` for Architect with Generator
- [x] Update `inc/classes/class-nova-x-rest.php` line 303: Replace `new Nova_X_Architect()` with `new Nova_X_Generator()`
- [x] Update `inc/classes/class-nova-x-rest.php` line 304: Replace `$architect->build_theme()` with `$generator->build_theme()`
- [x] Update variable name from `$architect` to `$generator` in REST API
- [x] Update error log messages to reference "Generator" instead of "Architect"

### Phase 2: Deprecation ✅ COMPLETED
- [x] Add `@deprecated` PHPDoc to `Nova_X_Architect` class
- [x] Add deprecation notice in constructor (optional, for developers)
- [x] Update any inline comments referencing Architect
- [x] Move deprecated class to `_deprecated/class-nova-x-architect.php` for rollback safety

### Phase 3: Testing
- [ ] Test theme generation via REST API endpoint
- [ ] Verify slug collision handling works correctly
- [ ] Verify all theme files are generated (including header.php and footer.php)
- [ ] Test error handling scenarios
- [ ] Verify response format matches expected structure

### Phase 4: Documentation ✅ COMPLETED
- [x] Update API documentation if needed
- [x] Update developer documentation (this file)
- [ ] Add migration notes to CHANGELOG (if CHANGELOG exists)

### Phase 5: Cleanup (Future)
- [x] Remove `inc/classes/class-nova-x-architect.php` after deprecation period (moved to `_deprecated/`)
- [ ] Remove any remaining references in comments/docs (UI references to "Architecture" are intentional - they refer to the feature, not the class)

---

## 7. Response Format Compatibility

### Architect Response Format:
```php
[
    'success' => true,
    'slug' => 'theme-slug',
    'message' => 'Theme successfully generated.',
]
```

### Generator Response Format:
```php
[
    'success' => true,
    'message' => 'Theme built successfully!',
    'path' => '/full/path/to/theme',
    'slug' => 'theme-slug-1234567890',
]
```

**Impact:** ✅ **Compatible** - Generator includes all Architect fields plus additional `path` field. REST API already handles both formats.

---

## 8. Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Breaking changes in REST API | Low | Medium | Generator response includes all Architect fields |
| Slug format change | Medium | Low | Generator adds timestamp, but slug is still valid |
| Missing theme files | Low | Low | Generator creates more files than Architect |
| File permission issues | Low | Medium | Generator has better error handling |

**Overall Risk:** ✅ **LOW** - Generator is a superset of Architect functionality.

---

## 9. Implementation Notes

### 9.1 REST API Update Example

**Before:**
```php
require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-architect.php';
$architect = new Nova_X_Architect();
$result = $architect->build_theme( $site_title, $prompt );
```

**After:**
```php
// Generator is already loaded in nova-x.php, no require needed
$generator = new Nova_X_Generator();
$result = $generator->build_theme( $site_title, $prompt );
```

### 9.2 Error Log Updates

Update error log messages in REST API:
- Change "Architect error" → "Generator error"
- Update any references to Architect class

---

## 10. Timeline Recommendation

1. **Immediate:** Update REST API to use Generator
2. **Immediate:** Add deprecation notice to Architect class
3. **After Testing:** Mark Architect for removal in next major version
4. **Future:** Remove Architect class file after deprecation period

---

## 11. Summary

✅ **Generator is the clear winner:**
- Better slug collision handling
- More complete theme generation
- Better error handling
- Uses WordPress core functions
- Already loaded in plugin initialization

✅ **No logic loss:** All Architect functionality exists in Generator with improvements.

✅ **Simple migration:** Only requires updating REST API endpoint (3 lines of code).

✅ **Low risk:** Generator response format is compatible with existing code.

**Recommendation:** Proceed with migration immediately. Architect can be deprecated and removed in a future version.

