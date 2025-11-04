# TODO-3099: Migrate wp-agency to Generic Tab Pattern

**Priority**: MEDIUM
**Type**: Migration / Enhancement
**Created**: 2025-11-01
**Depends On**: wp-app-core TODO-1193 ✅ COMPLETED
**Related**: wp-customer TODO-2187 Review-03

## Background

After wp-app-core implements generic entity support in wpapp-tab-manager.js, wp-agency should migrate to use the explicit generic pattern for better clarity and consistency.

## Current Implementation

wp-agency currently works because wpapp-tab-manager.js is hardcoded for 'agency':
- Uses `data-agency-id` attribute
- Sends `agency_id` in AJAX requests
- No explicit `data-entity-type` specification

This works but is implicit rather than explicit.

## Recommended Changes (After wp-app-core Fix)

### 1. Add Entity Type to Panel

**File**: `/wp-agency/src/Controllers/Agency/AgencyDashboardController.php`

Update the panel template or ensure TabSystemTemplate receives entity type:
```php
// If manually rendering panel wrapper:
echo '<div class="wpapp-panel" data-entity-type="agency">';
```

Or if using TabSystemTemplate, pass entity parameter.

### 2. Verify Tab Views

**Files**:
- `/wp-agency/src/Views/agency/tabs/divisions.php`
- `/wp-agency/src/Views/agency/tabs/employees.php`

Ensure they use `data-agency-id` (should already be correct):
```php
<div class="wpapp-divisions-tab wpapp-tab-autoload"
     data-agency-id="<?php echo esc_attr($agency_id); ?>"
     data-load-action="load_agency_divisions_tab"
     ...>
```

### 3. Verify AJAX Handlers

**File**: `/wp-agency/src/Controllers/Agency/AgencyDashboardController.php`

Ensure handlers accept `agency_id` parameter:
```php
public function handle_load_divisions_tab(): void {
    $agency_id = isset($_POST['agency_id']) ? (int) $_POST['agency_id'] : 0;
    // ...
}
```

Should already be correct, just verify.

## Benefits of Migration

1. **Explicit Configuration**: Clear entity type declaration
2. **Consistency**: Same pattern across all plugins
3. **Future-Proof**: Ready for any wp-app-core enhancements
4. **Documentation**: Easier to understand and maintain

## Testing After Migration

- [ ] Agency detail panel opens correctly
- [ ] Divisions tab loads data
- [ ] Employees tab loads data
- [ ] No console errors
- [ ] AJAX requests send `agency_id` correctly

## Implementation Priority

**Can wait until**:
1. wp-app-core implements generic pattern
2. wp-customer verifies the pattern works
3. Pattern is documented in wp-app-core README

Then implement this migration during next maintenance cycle.

## Notes

- This is NOT urgent since wp-agency currently works
- Main benefit is consistency and explicitness
- Consider doing this when making other agency-related changes
- Update wp-agency documentation to reference generic pattern
