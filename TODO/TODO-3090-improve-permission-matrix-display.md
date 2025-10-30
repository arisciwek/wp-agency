# TODO-3090: Improve Permission Matrix Display

**Date**: 2025-10-29
**Type**: UI/UX Improvement
**Priority**: Medium
**Status**: ✅ Completed (Phase 1)
**Related**: task-3090.md

---

## 📋 Overview

Merubah pola matriks permission pada plugin wp-agency agar sama seperti yang ada pada plugin wp-app-core. Tujuannya untuk meningkatkan user experience dengan tampilan yang lebih informatif dan fokus hanya pada agency roles.

## 🎯 Problem Analysis

### Masalah yang Ditemukan:

1. **Tampilan Menu Setting Kurang Menarik**
   - Tidak ada header section yang informatif
   - Tidak ada visual indicator untuk agency roles
   - Section styling kurang

2. **Menampilkan Semua WordPress Roles**
   - Menampilkan SEMUA role di WordPress (subscriber, contributor, author, editor, dll)
   - User menjadi bingung role mana yang relevan dengan agency
   - Tidak consistent dengan wp-app-core pattern

3. **Kurang Informatif**
   - Description kurang jelas
   - Tidak ada penjelasan bahwa hanya agency roles yang relevan

## ✅ Solution Implemented

### Pattern Adopted from wp-app-core:

1. **Filter Only Plugin-Specific Roles**
   - Show ONLY agency roles (tidak semua WordPress roles)
   - Consistent dengan wp-app-core yang hanya show platform roles

2. **Add Visual Indicators**
   - Icon `dashicons-building` untuk agency roles
   - Similar dengan wp-app-core yang pakai `dashicons-admin-generic`

3. **Improve Section Styling**
   - Header section dengan background warna dan border
   - Reset section terpisah dengan better styling
   - Permission matrix section dengan header yang jelas

4. **Better Descriptions**
   - Lebih informatif dan jelas
   - Explain bahwa hanya agency roles yang ditampilkan

---

## 📝 Changes Made

### 1. tab-permissions.php (wp-agency)

**File**: `src/Views/templates/settings/tab-permissions.php`
**Version**: 1.0.7 → 1.1.0

#### A. Header Changes

**BEFORE:**
```php
/**
 * @version     1.0.7
 *
 * Description: Template untuk mengelola hak akses plugin WP Agency
 *              Menampilkan matrix permission untuk setiap role
 */
```

**AFTER:**
```php
/**
 * @version     1.1.0
 *
 * Description: Template untuk mengelola hak akses plugin WP Agency
 *              Menampilkan matrix permission untuk setiap role
 *              Hanya menampilkan agency roles (bukan semua WordPress roles)
 *
 * Changelog:
 * v1.1.0 - 2025-10-29 (TODO-3090)
 * - BREAKING: Show only agency roles (not all WordPress roles)
 * - Added: Header section with description
 * - Added: Icon indicator for agency roles
 * - Improved: Section styling following wp-app-core pattern
 * - Changed: Better descriptions and info messages
 */
```

#### B. Role Filtering Logic

**BEFORE (Lines 60-67):**
```php
// Get permission model instance
$permission_model = new \WPAgency\Models\Settings\PermissionModel();
$permission_labels = $permission_model->getAllCapabilities();
$capability_groups = $permission_model->getCapabilityGroups();
$all_roles = get_editable_roles();

// Get current active tab
$current_tab = isset($_GET['permission_tab']) ? sanitize_key($_GET['permission_tab']) : 'agency';
```

**AFTER (Lines 68-107):**
```php
// Get permission model instance
$permission_model = new \WPAgency\Models\Settings\PermissionModel();
$permission_labels = $permission_model->getAllCapabilities();
$capability_groups = $permission_model->getCapabilityGroups();

// Load RoleManager
require_once WP_AGENCY_PLUGIN_DIR . 'includes/class-role-manager.php';

// Get agency roles
$agency_roles = WP_Agency_Role_Manager::getRoleSlugs();
$existing_agency_roles = [];
foreach ($agency_roles as $role_slug) {
    if (WP_Agency_Role_Manager::roleExists($role_slug)) {
        $existing_agency_roles[] = $role_slug;
    }
}
$agency_roles_exist = !empty($existing_agency_roles);

// Get all editable roles
$all_roles = get_editable_roles();

// Display ONLY agency roles (exclude other plugin roles and standard WP roles)
// Agency permissions are specifically for agency management
$displayed_roles = [];
if ($agency_roles_exist) {
    // Show only agency roles with the dashicons-building icon indicator
    foreach ($existing_agency_roles as $role_slug) {
        if (isset($all_roles[$role_slug])) {
            $displayed_roles[$role_slug] = $all_roles[$role_slug];
        }
    }
}

// Get current active tab with validation
$current_tab = isset($_GET['permission_tab']) ? sanitize_key($_GET['permission_tab']) : 'agency';

// Validate that the tab exists in capability_groups
if (!isset($capability_groups[$current_tab])) {
    $current_tab = 'agency';
}
```

**Key Changes:**
- ✅ Load RoleManager
- ✅ Get only agency roles from RoleManager
- ✅ Check which agency roles exist
- ✅ Filter displayed_roles to show only agency roles
- ✅ Add tab validation

#### C. Form Submission Processing

**BEFORE (Lines 84-104):**
```php
$updated = false;
foreach ($all_roles as $role_name => $role_info) {
    if ($role_name === 'administrator') {
        continue;
    }

    $role = get_role($role_name);
    if ($role) {
        // Process capabilities...
    }
}
```

**AFTER (Lines 125-145):**
```php
$updated = false;

// Only process agency roles (consistent with display filter)
$temp_agency_roles = WP_Agency_Role_Manager::getRoleSlugs();
foreach ($temp_agency_roles as $role_name) {
    $role = get_role($role_name);
    if ($role) {
        // Only process capabilities from current tab
        foreach ($current_tab_caps as $cap) {
            $has_cap = isset($_POST['permissions'][$role_name][$cap]);
            if ($role->has_cap($cap) !== $has_cap) {
                if ($has_cap) {
                    $role->add_cap($cap);
                } else {
                    $role->remove_cap($cap);
                }
                $updated = true;
            }
        }
    }
}
```

**Key Changes:**
- ✅ Process only agency roles (not all roles)
- ✅ Consistent dengan display filter

#### D. HTML Structure

**1. Header Section (NEW)**

```php
<!-- Header Section -->
<div class="settings-header-section" style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px 20px; margin-top: 20px;">
    <h3 style="margin: 0; color: #1d2327;">
        <?php
        printf(
            __('Managing %s Permissions', 'wp-agency'),
            esc_html($capability_groups[$current_tab]['title'])
        );
        ?>
    </h3>
    <p style="margin: 5px 0 0 0; color: #646970; font-size: 13px;">
        <?php _e('Configure which agency roles <span class="dashicons dashicons-building" style="font-size: 14px; vertical-align: middle; color: #0073aa;"></span> have access to these capabilities. Only agency staff roles are shown here.', 'wp-agency'); ?>
    </p>
</div>
```

**2. Reset Section (IMPROVED)**

**BEFORE:**
```php
<div class="reset-permissions-section">
    <form id="wp-agency-permissions-form" method="post" ...>
        <button type="button" id="reset-permissions-btn" class="button button-secondary">
            <i class="dashicons dashicons-image-rotate"></i>
            <?php _e('Reset to Default', 'wp-agency'); ?>
        </button>
    </form>
    <p class="description">
        <?php _e('Reset permissions to plugin defaults. This will restore the original capability settings for all roles.', 'wp-agency'); ?>
    </p>
</div>
```

**AFTER:**
```php
<!-- Reset Section -->
<div class="settings-section" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
    <button type="button" class="button button-secondary button-reset-permissions">
        <span class="dashicons dashicons-image-rotate"></span>
        <?php _e('Reset to Default', 'wp-agency'); ?>
    </button>
    <p class="description">
        <?php
        printf(
            __('Reset <strong>%s</strong> permissions to plugin defaults. This will restore the original capability settings for all roles in this group.', 'wp-agency'),
            esc_html($capability_groups[$current_tab]['title'])
        );
        ?>
    </p>
</div>
```

**3. Permission Matrix Section (IMPROVED)**

**BEFORE:**
```php
<div class="permissions-section">
    <form id="wp-agency-permissions-form" method="post" ...>
        <p class="description">
            <?php _e('Configure role permissions for managing agency data. Administrators automatically have full access.', 'wp-agency'); ?>
        </p>

        <table class="widefat fixed striped permissions-matrix">
            <!-- table content -->
        </table>

        <?php submit_button(__('Save Changes', 'wp-agency')); ?>
    </form>
</div>
```

**AFTER:**
```php
<!-- Permission Matrix Section -->
<div class="permissions-section" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin-top: 20px;">
    <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #dcdcde;">
        <?php
        printf(
            __('Agency Settings - %s', 'wp-agency'),
            esc_html($capability_groups[$current_tab]['title'])
        );
        ?>
    </h2>

    <form method="post" id="wp-agency-permissions-form" ...>
        <p class="description" style="margin-bottom: 15px;">
            <?php _e('Check capabilities for each agency role. WordPress Administrators automatically have full access to all agency capabilities.', 'wp-agency'); ?>
        </p>

        <table class="widefat fixed striped permission-matrix-table">
            <!-- table content -->
        </table>
    </form>

    <!-- Sticky Footer with Action Buttons -->
    <div class="settings-footer">
        <p class="submit">
            <?php submit_button(__('Save Permission Changes', 'wp-agency'), 'primary', 'submit', false, ['form' => 'wp-agency-permissions-form']); ?>
        </p>
    </div>
</div><!-- .permissions-section -->
```

**4. Table Body with Role Filtering**

**BEFORE:**
```php
<tbody>
    <?php
    foreach ($all_roles as $role_name => $role_info):
        if ($role_name === 'administrator') continue;
        $role = get_role($role_name);
    ?>
        <tr>
            <td class="column-role">
                <strong><?php echo translate_user_role($role_info['name']); ?></strong>
            </td>
            <!-- permissions checkboxes -->
        </tr>
    <?php endforeach; ?>
</tbody>
```

**AFTER:**
```php
<tbody>
    <?php
    if (empty($displayed_roles)) {
        echo '<tr><td colspan="' . (count($capability_groups[$current_tab]['caps']) + 1) . '" style="text-align:center;">';
        _e('Tidak ada agency roles yang tersedia. Silakan buat agency roles terlebih dahulu.', 'wp-agency');
        echo '</td></tr>';
    } else {
        foreach ($displayed_roles as $role_name => $role_info):
            $role = get_role($role_name);
            if (!$role) continue;
    ?>
        <tr>
            <td class="column-role">
                <strong><?php echo translate_user_role($role_info['name']); ?></strong>
                <span class="dashicons dashicons-building" style="color: #0073aa; font-size: 14px; vertical-align: middle;" title="<?php _e('Agency Role', 'wp-agency'); ?>"></span>
            </td>
            <?php foreach ($capability_groups[$current_tab]['caps'] as $cap): ?>
                <td class="column-permission">
                    <input type="checkbox"
                           class="permission-checkbox"
                           name="permissions[<?php echo esc_attr($role_name); ?>][<?php echo esc_attr($cap); ?>]"
                           value="1"
                           data-role="<?php echo esc_attr($role_name); ?>"
                           data-capability="<?php echo esc_attr($cap); ?>"
                           <?php checked($role->has_cap($cap)); ?>>
                </td>
            <?php endforeach; ?>
        </tr>
    <?php
        endforeach;
    }
    ?>
</tbody>
```

**Key Changes:**
- ✅ Check if displayed_roles is empty
- ✅ Show message if no agency roles available
- ✅ Add icon indicator `dashicons-building` for each role
- ✅ Add data attributes to checkboxes
- ✅ Add permission-checkbox class

---

## 📊 Benefits Achieved

### 1. Better User Experience ✅
- Header section yang jelas menjelaskan apa yang sedang dikelola
- Visual indicator (building icon) untuk agency roles
- Section styling yang lebih menarik

### 2. Less Confusion ✅
- Hanya menampilkan agency roles (relevant roles only)
- Tidak ada lagi role WordPress standard yang membingungkan
- Clear description tentang apa yang ditampilkan

### 3. Consistency ✅
- Mengikuti pattern dari wp-app-core
- Consistent filtering logic
- Consistent processing logic

### 4. Better Information Architecture ✅
- Header section: Overview
- Reset section: Quick action
- Permission matrix: Detailed control
- Sticky footer: Save action

---

## 🔄 Pattern Comparison

### BEFORE (Show All Roles):
```
Permission Matrix
├─ administrator (hidden)
├─ editor ❌ (WordPress default role - irrelevant)
├─ author ❌ (WordPress default role - irrelevant)
├─ contributor ❌ (WordPress default role - irrelevant)
├─ subscriber ❌ (WordPress default role - irrelevant)
├─ agency ✅ (Relevant)
├─ agency_admin_dinas ✅ (Relevant)
├─ agency_admin_unit ✅ (Relevant)
└─ ... other agency roles ✅ (Relevant)
```

**Problems:**
- Too many irrelevant roles
- Confusing for users
- Violates scope separation

### AFTER (Show Only Agency Roles):
```
Header Section:
"Managing Agency Permissions"
"Configure which agency roles 🏢 have access..."

Reset Section:
[Reset to Default] button

Permission Matrix:
├─ agency 🏢 (Agency Role)
├─ agency_admin_dinas 🏢 (Agency Role)
├─ agency_admin_unit 🏢 (Agency Role)
└─ ... other agency roles 🏢 (Agency Role)
```

**Benefits:**
- Only relevant roles
- Clear and focused
- Visual indicators
- Better UX

---

## 🎨 Visual Improvements

### 1. Header Section
- Background: `#f0f6fc` (light blue)
- Border-left: `4px solid #2271b1` (WordPress blue)
- Clear heading and description

### 2. Reset Section
- Background: `#fff`
- Border: `1px solid #ccd0d4`
- Proper padding and spacing

### 3. Permission Matrix Section
- Background: `#fff`
- Border: `1px solid #ccd0d4`
- Section header with bottom border
- Better table styling

### 4. Icon Indicators
- Building icon: `dashicons-building`
- Color: `#0073aa` (WordPress blue)
- Size: `14px`
- Positioned next to role name

---

## 📁 Files Modified

| File | Type | Change | Version |
|------|------|--------|---------|
| tab-permissions.php | PHP | Filter only agency roles, add sections | 1.0.7 → 1.1.0 |
| PermissionModel.php | PHP | Refactor to clean pattern (Review-01) | 1.0.7 → 1.1.0 |

**Total Changes:**
- **tab-permissions.php**: ~150 lines modified
  - Major refactoring: Role filtering logic
  - New sections: Header, improved Reset, improved Matrix
- **PermissionModel.php**: ~200 lines refactored, ~240 lines added (Review-01)
  - Added: getDefaultCapabilitiesForRole() method
  - Refactored: addCapabilities(), resetToDefault()
  - Removed: wp_customer tab (scope violation)

---

## ✅ Testing Checklist

**Functional Testing:**
- [ ] Only agency roles are displayed (not WordPress default roles)
- [ ] Icon indicator appears next to each role name
- [ ] Header section displays correctly
- [ ] Reset section works
- [ ] Permission matrix displays correctly
- [ ] Checkboxes save correctly
- [ ] Tab switching works
- [ ] Form submission updates permissions

**Visual Testing:**
- [ ] Header section styling correct
- [ ] Reset section styling correct
- [ ] Permission matrix styling correct
- [ ] Icons display correctly
- [ ] Colors match WordPress admin
- [ ] Responsive design works
- [ ] No CSS conflicts

**Edge Cases:**
- [ ] Empty roles (no agency roles created yet)
- [ ] Single role
- [ ] Multiple roles
- [ ] Role without capabilities
- [ ] Role with all capabilities

---

## 🚀 Next Steps (Optional)

### Phase 2: CSS Improvements (If Needed)
- Update permissions-tab-style.css if needed
- Remove unused CSS for old structure
- Add specific CSS for new sections

### Phase 3: JavaScript Updates (If Needed)
- Update agency-permissions-tab-script.js if needed
- Ensure reset functionality works with new structure

---

## 📚 Design Philosophy

**Pattern Adopted:**

1. **Scope Separation**
   - wp-app-core: Shows platform roles only
   - wp-agency: Shows agency roles only
   - Consistent filtering across plugins

2. **Visual Indicators**
   - wp-app-core: `dashicons-admin-generic` for platform roles
   - wp-agency: `dashicons-building` for agency roles
   - Clear visual distinction

3. **Information Architecture**
   - Header: What you're managing
   - Reset: Quick action
   - Matrix: Detailed control
   - Footer: Save action

4. **User-Centric**
   - Show only relevant information
   - Clear descriptions
   - Visual feedback
   - Better UX

---

**Completed By**: Claude Code
**Date**: 2025-10-29
**Status**: ✅ Phase 1 & Review-01 Complete
**Next**: Phase 2 (CSS) & Phase 3 (JS) if needed

---

## 🔄 Review-01: PermissionModel.php Refactoring

**Date**: 2025-10-29
**Status**: ✅ COMPLETED
**Trigger**: User feedback - PermissionModel.php structure berbeda dengan wp-customer dan wp-app-core

### Problem

PermissionModel.php di wp-agency tidak mengikuti clean pattern dari wp-app-core dan wp-customer:
- Ada `'wp_customer'` tab di settings (scope violation)
- Hard-coded agency_roles array
- addCapabilities() method tidak clean
- resetToDefault() tidak menggunakan isPluginRole() check (bug potential)

### Solution: Adopt wp-app-core Clean Pattern

Refactor PermissionModel.php mengikuti PlatformPermissionModel.php pattern.

### Changes Made

**File**: `/wp-agency/src/Models/Settings/PermissionModel.php` (v1.0.7 → v1.1.0)

#### 1. Removed Scope Violation

❌ **Removed**: `'wp_customer'` tab dari `$displayed_capabilities_in_tabs`
- Tab tersebut membuat wp-customer permissions muncul di settings wp-agency
- wp-customer permissions HANYA boleh dikelola dari wp-customer plugin

#### 2. Added Private Method: `getDefaultCapabilitiesForRole()`

✅ **New Method** (~240 lines):
```php
private function getDefaultCapabilitiesForRole(string $role_slug): array {
    $defaults = [
        'agency' => [...],
        'agency_admin_dinas' => [...],
        'agency_admin_unit' => [...],
        // ... 7 more roles
    ];
    return $defaults[$role_slug] ?? [];
}
```

Defines default capabilities untuk 9 agency roles dengan dokumentasi lengkap per-role.

#### 3. Refactored `addCapabilities()` Method

**BEFORE** (Hard-coded):
```php
$agency_roles = [
    'agency_admin_dinas',
    'agency_admin_unit',
    // ... manual array
];
```

**AFTER** (Clean Pattern):
```php
require_once WP_AGENCY_PATH . 'includes/class-role-manager.php';
$agency_roles = \WP_Agency_Role_Manager::getRoleSlugs();
foreach ($agency_roles as $role_slug) {
    $default_caps = $this->getDefaultCapabilitiesForRole($role_slug);
    // ... apply caps
}
```

#### 4. Refactored `resetToDefault()` Method

✅ **BUG FIX**: Added `isPluginRole()` check
```php
$is_agency_role = \WP_Agency_Role_Manager::isPluginRole($role_name);
$is_admin = $role_name === 'administrator';

if (!$is_agency_role && !$is_admin) {
    continue; // Skip non-agency roles
}
```

Prevents accidentally removing capabilities from customer/platform/other plugin roles.

### Pattern Comparison

| Aspect | Before | After |
|--------|--------|-------|
| Role Config | Hard-coded array | getDefaultCapabilitiesForRole() ✅ |
| addCapabilities() | Manual loop | RoleManager pattern ✅ |
| resetToDefault() | Basic loop | isPluginRole() check ✅ |
| Scope | wp_customer tab ❌ | Agency only ✅ |

### Benefits

1. **Scope Separation** ✅
   - Each plugin manages its own permissions
   - Cross-plugin view capabilities retained for integration

2. **Maintainability** ✅
   - Centralized role configuration
   - Easy to update per-role defaults

3. **Consistency** ✅
   - Follows wp-app-core pattern
   - Same structure as wp-customer

4. **Bug Fixes** ✅
   - Safe in multi-plugin environment
   - Won't touch other plugins' capabilities

### Code Metrics

- Lines Refactored: ~200
- Lines Added: ~240 (getDefaultCapabilitiesForRole)
- Methods Changed: 3 (addCapabilities, resetToDefault, getDisplayedCapabilities)
- Methods Added: 1 (getDefaultCapabilitiesForRole)

**Reference**: See task-3090.md Review-01 section for detailed implementation notes

---

## 🎯 Enhancement: Filter Base Role from Matrix Display

**Date**: 2025-10-29
**Status**: ✅ COMPLETED

### Problem

Base role 'agency' hanya memiliki 'read' capability. Menampilkannya di permission matrix bisa membingungkan karena:

1. **No Management Capabilities**: Base role tidak punya agency management capabilities
2. **Dual-Role Pattern**: Base role digunakan untuk dual-role pattern (inherited by secondary roles)
3. **Not for Direct Assignment**: Base role jarang di-assign langsung
4. **Empty Matrix Row**: Row akan kosong atau hanya 'read', tidak informatif

### Solution

Filter out base role 'agency' dari tampilan permission matrix.

### Implementation

Added filter logic in tab-permissions.php:
```php
foreach ($existing_agency_roles as $role_slug) {
    // Skip base role 'agency'
    if ($role_slug === 'agency') {
        error_log('Skipping base role: agency (dual-role pattern)');
        continue;
    }

    if (isset($all_roles[$role_slug])) {
        $displayed_roles[$role_slug] = $all_roles[$role_slug];
    }
}
```

### Files Modified

✅ `/wp-agency/src/Views/templates/settings/tab-permissions.php` (lines 110-115)

### Benefits

1. **Less Confusion** - Matrix hanya tampilkan functional roles
2. **Better UX** - Focus pada roles yang actually used
3. **Prevent Duplication** - Clearer role hierarchy

### Dual-Role Pattern

**Architecture:**
```
User
├─ Primary Role: agency (base role - provides 'read' capability)
└─ Secondary Role: agency_admin_dinas (functional role - provides full capabilities)
```

**Why Filter:**
- Base role is inherited automatically
- Management happens at secondary role level
- Matrix should show only functional roles

### Notes

- Base role tetap ada di PermissionModel for users directly assigned
- Form processing tetap handle all roles including base role
- Filter hanya di display layer
