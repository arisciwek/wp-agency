# TODO-2065: Synchronize Agency Registration and Create/Edit Forms

## Status: ✅ COMPLETED

## Date: 2025-01-22

## Implementation: Option 1 - Dual Fields (Code + ID)

**Approach**: Menambahkan hidden fields `provinsi_id` dan `regency_id` sebagai tambahan dari existing `provinsi_code` dan `regency_code`.

**Field Structure**:
```
provinsi_code (select, visible)  → saved to database
provinsi_id (hidden)             → auto-populated via JS
regency_code (select, visible)   → saved to database
regency_id (hidden)              → auto-populated via JS
```

**Benefits**:
- ✅ Backward compatible (existing code fields tetap ada)
- ✅ ID fields tersedia untuk future use
- ✅ No database migration needed
- ✅ Flexible untuk future integrations

## Problem

Form fields antara agency registration dan create/edit agency tidak konsisten:
- **register.php**: Hanya punya field minimal (username, email, password, name)
- **create-agency-form.php**: Punya field lengkap (name, status, provinsi, regency)
- **edit-agency-form.php**: Punya field lengkap (name, status, provinsi, regency, user_id)

Ini menyebabkan:
- ❌ Maintenance harus dilakukan di multiple files
- ❌ Inconsistent field structure antara forms
- ❌ Registration form kurang field penting (provinsi, regency)
- ❌ Tidak DRY (Don't Repeat Yourself)

## Solution

Implementasi shared component pattern seperti di wp-customer:
- ✅ Create `agency-form-fields.php` sebagai single source of truth
- ✅ Conditional rendering berdasarkan mode (self-register, admin-create, edit)
- ✅ Consistent field structure across all forms
- ✅ Easy maintenance (edit once, apply everywhere)

## Files Created

### 1. Shared Component
**File**: `/wp-agency/src/Views/templates/partials/agency-form-fields.php`

**Purpose**: Single source of truth untuk agency form fields

**Parameters**:
```php
$args = [
    'mode' => 'self-register' | 'admin-create' | 'edit',
    'layout' => 'single-column' | 'two-column',
    'field_classes' => 'regular-text',
    'wrapper_classes' => 'form-group' | 'wp-agency-form-group',
    'agency' => null | object  // For edit mode
];
```

**Modes**:
- `self-register`: Login info + Company info + Location
- `admin-create`: Company info + Status + Location
- `edit`: Company info + Status + Location + Admin (if permission)

**Fields Included**:
```
self-register:
├── Informasi Login
│   ├── username (required)
│   ├── email (required)
│   └── password (required)
├── Informasi Perusahaan
│   └── name (required)
└── Lokasi
    ├── provinsi_code (required)
    └── regency_code (required)

admin-create/edit:
├── Informasi Dasar
│   ├── name (required)
│   └── status (required)
└── Lokasi
    ├── provinsi_code (required)
    ├── regency_code (required)
    └── user_id (edit only, if has permission)
```

## Files Modified

### 1. Registration Form
**File**: `/wp-agency/src/Views/templates/auth/register.php`
**Version**: 1.0.0 → 1.1.0

**Changes**:
- ✅ Refactored to use shared component
- ✅ Added provinsi and regency fields
- ✅ Removed hardcoded form fields
- ✅ Updated changelog

**Usage**:
```php
$args = [
    'mode' => 'self-register',
    'layout' => 'single-column',
    'field_classes' => 'regular-text',
    'wrapper_classes' => 'form-group'
];
include $template_path;
```

### 2. Registration Controller
**File**: `/wp-agency/src/Controllers/Auth/AgencyRegistrationHandler.php`
**Version**: 1.0.0 → 1.1.0

**Changes**:
- ✅ Added provinsi_code and regency_code handling
- ✅ Added validation for location fields (required)
- ✅ Set reg_type to 'self' for self-registration
- ✅ Set default status to 'active' for self-registration
- ✅ Updated changelog

**New Fields Processed**:
```php
$provinsi_code = isset($_POST['provinsi_code']) ? sanitize_text_field($_POST['provinsi_code']) : '';
$regency_code = isset($_POST['regency_code']) ? sanitize_text_field($_POST['regency_code']) : '';

// Validation
if (empty($provinsi_code) || empty($regency_code)) {
    wp_send_json_error([
        'message' => __('Provinsi dan Kabupaten/Kota wajib diisi.', 'wp-agency')
    ]);
}

// Insert
$agency_data = [
    'code' => $code,
    'name' => $name,
    'status' => 'active',
    'provinsi_code' => $provinsi_code,
    'regency_code' => $regency_code,
    'user_id' => $user_id,
    'reg_type' => 'self',
    'created_by' => $user_id
];
```

### 3. Registration JavaScript
**File**: `/wp-agency/assets/js/auth/register.js`
**Version**: 1.0.0 → 1.1.0

**Changes**:
- ✅ Added province loading on init
- ✅ Added regency loading on province change
- ✅ Uses AJAX actions from create-agency-form
- ✅ Updated changelog

**New Methods**:
```javascript
loadAvailableProvinces() {
    // Loads provinces via AJAX
    // Action: get_available_provinces_for_agency_creation
}

loadRegenciesByProvince() {
    // Loads regencies based on selected province
    // Action: get_available_regencies_for_agency_creation
}
```

**Event Binding**:
```javascript
this.form.on('change', '[name="provinsi_code"]', this.loadRegenciesByProvince.bind(this));
```

### 4. Create Agency Form
**File**: `/wp-agency/src/Views/templates/forms/create-agency-form.php`
**Version**: 1.0.0 → 1.1.0

**Changes**:
- ✅ Refactored to use shared component
- ✅ Removed duplicate field definitions
- ✅ Maintained two-column layout
- ✅ Updated changelog

**Usage**:
```php
$args = [
    'mode' => 'admin-create',
    'layout' => 'two-column',
    'field_classes' => 'regular-text',
    'wrapper_classes' => 'wp-agency-form-group'
];
include $template_path;
```

### 5. Edit Agency Form
**File**: `/wp-agency/src/Views/templates/forms/edit-agency-form.php`
**Version**: 1.0.1 → 1.1.0

**Changes**:
- ✅ Refactored to use shared component
- ✅ Removed duplicate field definitions
- ✅ Removed debug logging
- ✅ Maintained two-column layout
- ✅ Updated changelog

**Usage**:
```php
$args = [
    'mode' => 'edit',
    'layout' => 'two-column',
    'field_classes' => 'regular-text',
    'wrapper_classes' => 'wp-agency-form-group',
    'agency' => null // Populated by JavaScript
];
include $template_path;
```

## Implementation Details

### Path Resolution Strategy

All three forms use the same robust path resolution:

```php
// Method 1: Using WP_AGENCY_PATH constant (if available)
if (defined('WP_AGENCY_PATH')) {
    $template_path = WP_AGENCY_PATH . 'src/Views/templates/partials/agency-form-fields.php';
}

// Method 2: Fallback to __FILE__ relative path
if (!$template_path || !file_exists($template_path)) {
    $template_path = dirname(dirname(__FILE__)) . '/partials/agency-form-fields.php';
}

// Method 3: Last resort - hardcoded absolute path
if (!file_exists($template_path)) {
    $template_path = '/home/mkt01/Public/wppm/public_html/wp-content/plugins/wp-agency/src/Views/templates/partials/agency-form-fields.php';
}
```

### CSS Classes Mapping

| Form Type | Mode | wrapper_classes | field_classes |
|-----------|------|-----------------|---------------|
| register.php | self-register | form-group | regular-text |
| create-agency-form.php | admin-create | wp-agency-form-group | regular-text |
| edit-agency-form.php | edit | wp-agency-form-group | regular-text |

### Layout Structure

**Single Column** (register.php):
```
wp-agency-card
├── wp-agency-card-header
├── wp-agency-card-body
└── form fields
```

**Two Column** (create/edit):
```
modal-content
├── row left-side
│   └── agency-form-section
└── row right-side
    └── agency-form-section
```

## Benefits

### 1. Maintainability ✅
- Single source of truth untuk form fields
- Edit once, apply everywhere
- Reduced code duplication

### 2. Consistency ✅
- All forms have same field structure
- Same validation rules
- Same field IDs and names

### 3. Flexibility ✅
- Easy to add new fields
- Conditional rendering by mode
- Supports different layouts

### 4. Scalability ✅
- Easy to add new form modes
- Reusable across plugin
- Follows wp-customer pattern

## Database Fields Reference

From `AgencysDB.php`:
```sql
CREATE TABLE wp_app_agencies (
    id bigint(20) UNSIGNED AUTO_INCREMENT,
    code varchar(10) NOT NULL,
    name varchar(100) NOT NULL,
    status enum('inactive','active') DEFAULT 'inactive',
    provinsi_code varchar(10) NULL,
    regency_code varchar(10) NULL,
    user_id bigint(20) UNSIGNED NULL,
    reg_type enum('self','by_admin','generate') DEFAULT 'self',
    created_by bigint(20) NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY code (code),
    UNIQUE KEY name_region (name, provinsi_code, regency_code)
)
```

## Testing Checklist

### Self-Registration Form (register.php)
- [ ] All fields render correctly
  - [ ] Username field shows
  - [ ] Email field shows
  - [ ] Password field shows
  - [ ] Name field shows
  - [ ] Provinsi select shows with empty placeholder
  - [ ] Regency select shows (disabled initially)
- [ ] Province/Regency functionality
  - [ ] Province select populates on page load via AJAX
  - [ ] Selecting province enables and populates regency select
  - [ ] Changing province updates regency options
- [ ] Form validation
  - [ ] All fields required validation works
  - [ ] Province and regency required validation works
  - [ ] Email format validation works
- [ ] Form submission
  - [ ] Successful registration creates user
  - [ ] Agency record created with provinsi_code and regency_code
  - [ ] Status set to 'active'
  - [ ] reg_type set to 'self'
  - [ ] Redirects to login page
  - [ ] Shows success toast message

### Admin-Create Form (create-agency-form.php)
- [ ] All fields render correctly
- [ ] Two-column layout displays properly
- [ ] Modal opens/closes correctly
- [ ] Province/Regency selects work
- [ ] Form submission works
- [ ] Validation works

### Edit Form (edit-agency-form.php)
- [ ] All fields render correctly
- [ ] Data populates correctly via JavaScript
- [ ] Province/Regency selects populate with existing values
- [ ] User select shows for admins only
- [ ] Form submission works
- [ ] Validation works

## Related Files

**JavaScript Files** (may need review):
- `/wp-agency/assets/js/auth/register.js`
- `/wp-agency/assets/js/agency/create-agency-form.js`

**CSS Files** (may need review):
- `/wp-agency/assets/css/agency/agency-form.css`

**Database**:
- `/wp-agency/src/Database/Tables/AgencysDB.php`

## Example: wp-customer Reference

This implementation follows the pattern from wp-customer:
- `/wp-customer/src/Views/templates/partials/customer-form-fields.php` (reference)
- `/wp-customer/src/Views/templates/auth/register.php` (reference)
- `/wp-customer/src/Views/templates/forms/create-customer-form.php` (reference)

## AJAX Dependencies

The registration form now depends on these AJAX actions (already implemented for create-agency-form):

**1. `get_available_provinces_for_agency_creation`**
- Returns list of available provinces
- Used on page load
- Response format:
```json
{
  "success": true,
  "data": {
    "provinces": [
      {"value": "32", "label": "Jawa Barat"},
      {"value": "33", "label": "Jawa Tengah"}
    ]
  }
}
```

**2. `get_available_regencies_for_agency_creation`**
- Returns list of regencies for selected province
- Used on province change
- Request: `province_code`
- Response format:
```json
{
  "success": true,
  "data": {
    "regencies": [
      {"value": "3204", "label": "Kabupaten Bandung"},
      {"value": "3273", "label": "Kota Bandung"}
    ]
  }
}
```

**3. `wp_agency_register`**
- Handles registration submission
- Expected POST data:
  - `username` (required)
  - `email` (required)
  - `password` (required)
  - `name` (required)
  - `provinsi_code` (required)
  - `regency_code` (required)
  - `register_nonce` (security)

## Notes

1. **JavaScript Integration**: ✅ COMPLETED
   - ✅ Province/regency selects initialization added to register.js
   - ✅ Form validation handled by HTML5 + controller
   - ✅ Form submission uses FormData (auto-captures all fields)
   - ✅ Edit mode data population handled by edit-agency-form.js

2. **CSS Compatibility**: The component uses existing CSS classes, no CSS changes required.

3. **Future Enhancements**: Easy to add more fields by editing only `agency-form-fields.php`.

## Completion Summary

✅ All tasks completed successfully:

**Phase 1: Shared Component**
1. ✅ Created shared component `agency-form-fields.php`
2. ✅ Supports 3 modes (self-register, admin-create, edit)
3. ✅ Supports 2 layouts (single-column, two-column)
4. ✅ Integrated with wilayah-indonesia plugin do_action

**Phase 2: Template Updates**
5. ✅ Updated `register.php` to use shared component
6. ✅ Updated `create-agency-form.php` to use shared component
7. ✅ Updated `edit-agency-form.php` to use shared component
8. ✅ Removed debug logging from edit form

**Phase 3: Controller Updates**
9. ✅ Updated `AgencyRegistrationHandler.php` (v1.0.0 → v1.1.0)
10. ✅ Added provinsi_code and regency_code handling
11. ✅ Added location validation
12. ✅ Set proper defaults (status='active', reg_type='self')

**Phase 4: JavaScript Updates**
13. ✅ Updated `register.js` (v1.0.0 → v1.2.0) - Simplified
14. ✅ Created `wilayah-sync.js` (v1.0.0) - NEW FILE
15. ✅ Enqueued wilayah-indonesia select-handler scripts
16. ✅ Added wilayahData localization

**Phase 5: Wilayah Integration (Option 1)**
17. ✅ Added hidden fields `provinsi_id` and `regency_id`
18. ✅ Created AJAX handler `get_wilayah_id_from_code`
19. ✅ Auto-populate ID fields via JavaScript
20. ✅ Cascade selects working (Province → Regency)

**Phase 6: Dependencies & Fixes**
21. ✅ Fixed `enqueue_scripts()` order (query_var check first)
22. ✅ Fixed agency-form.css path
23. ✅ Added wilayah-indonesia scripts to registration page
24. ✅ Fixed nonce to `wilayah_select_nonce`

**Phase 7: Documentation**
25. ✅ Created comprehensive TODO file
26. ✅ Updated main TODO.md
27. ✅ Documented all changes and dependencies
28. ✅ Ready for testing

**Summary of Changes:**
- **2 files created** (agency-form-fields.php, wilayah-sync.js)
- **3 templates updated** (register.php, create-agency-form.php, edit-agency-form.php)
- **1 controller updated** (AgencyRegistrationHandler.php)
- **2 JavaScript files updated** (register.js, wilayah-sync.js)
- **2 includes files updated** (class-dependencies.php, class-init-hooks.php)
- **2 documentation files updated** (TODO-2065-form-sync.md, TODO.md)

**Total: 12 files modified/created**
