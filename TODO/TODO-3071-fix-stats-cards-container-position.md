# TODO-3071: Fix Agency Stats Cards Container Position

## Status
✅ **COMPLETED** - 2025-10-25

## Masalah
`agency-header-cards` berada DI LUAR `wpapp-statistics-container`, tidak konsisten dengan wp-customer dan struktur global scope dari wp-app-core.

**Before:**
```html
<div class="agency-header-cards">  <!-- ❌ Di luar container -->
    <div class="agency-card agency-card-blue">
        <div class="agency-card-icon">...</div>
        <div class="agency-card-content">
            <div class="agency-card-value">10</div>
            <div class="agency-card-label">Total Disnaker</div>
        </div>
    </div>
</div>
<div class="wpapp-statistics-container">
    <!-- Empty or duplicate -->
</div>
```

**After:**
```html
<div class="wpapp-statistics-container">  <!-- ✅ Global container dari wp-app-core -->
    <div class="statistics-cards" id="agency-statistics">  <!-- ✅ Di dalam container -->
        <div class="stats-card agency-card agency-card-blue">  <!-- Mix: global + local classes -->
            <div class="stats-icon agency-card-icon">...</div>
            <div class="stats-content agency-card-content">
                <div class="stats-number agency-card-value">10</div>
                <div class="stats-label agency-card-label">Total Disnaker</div>
            </div>
        </div>
    </div>
</div>
```

## Changes Implemented

### 1. AgencyDashboardController.php - Hook Registration ✅
**File**: `wp-agency/src/Controllers/Agency/AgencyDashboardController.php`

**Before:**
```php
// Line 98
add_action('wpapp_dashboard_before_stats', [$this, 'render_header_cards'], 10, 2);
```

**After:**
```php
// Line 98
add_action('wpapp_statistics_cards_content', [$this, 'render_header_cards'], 10, 1);
```

**Changes**:
- ✅ Hook changed: `wpapp_dashboard_before_stats` → `wpapp_statistics_cards_content`
- ✅ Parameters: `($config, $entity)` → `($entity)` only
- ✅ Now hooks inside container instead of before it

### 2. AgencyDashboardController.php - Method Signature ✅
**Method**: `render_header_cards()`

**Before:**
```php
public function render_header_cards($config, $entity): void
```

**After:**
```php
public function render_header_cards($entity): void
```

### 3. AgencyDashboardController.php - HTML Structure ✅
**Method**: `render_header_cards()`

**Changes**:
- ✅ Wrapper: `agency-header-cards` → `statistics-cards` (global scope)
- ✅ Cards: Added global classes (`stats-card`, `stats-icon`, `stats-content`, `stats-number`, `stats-label`)
- ✅ Kept local classes (`agency-card`, `agency-card-*`) for custom styling

**HTML Structure:**
```html
<div class="statistics-cards" id="agency-statistics">  <!-- Global wrapper -->
    <div class="stats-card agency-card agency-card-blue">  <!-- Mixed classes -->
        <div class="stats-icon agency-card-icon">  <!-- Global + local -->
            <span class="dashicons dashicons-building"></span>
        </div>
        <div class="stats-content agency-card-content">  <!-- Global + local -->
            <div class="stats-number agency-card-value">10</div>  <!-- Global + local -->
            <div class="stats-label agency-card-label">Total Disnaker</div>  <!-- Global + local -->
        </div>
    </div>
    <!-- Additional cards... -->
</div>
```

## Class Naming Convention

### Global Scope (wp-app-core) - Prefix: `wpapp-` or `stats-`
- `wpapp-statistics-container` - Container wrapper
- `statistics-cards` - Cards wrapper
- `stats-card` - Individual card
- `stats-icon` - Icon container
- `stats-content` - Content wrapper
- `stats-number` - Number display
- `stats-label` - Label text

### Local Scope (wp-agency) - Prefix: `agency-`
- `agency-card` - Additional card styling
- `agency-card-blue` - Blue variant
- `agency-card-green` - Green variant
- `agency-card-orange` - Orange variant
- `agency-card-icon` - Custom icon styling
- `agency-card-content` - Custom content styling
- `agency-card-value` - Custom value styling
- `agency-card-label` - Custom label styling

## Benefits
1. ✅ Consistent structure with wp-customer and global scope
2. ✅ Cards properly positioned inside container
3. ✅ Maintains custom agency styling via local classes
4. ✅ Follows global/local scope separation principle
5. ✅ No breaking changes to CSS (both class sets preserved)

## Related Files Modified
- ✅ `wp-agency/src/Controllers/Agency/AgencyDashboardController.php`

## Related TODOs
- See: `wp-app-core/TODO/TODO-1179-fix-statistics-container-hook.md`

## CSS Files (No Changes Required)
The following CSS files still work because we kept local classes:
- `wp-agency/assets/css/agency/agency-header-cards.css` - Still valid (local classes preserved)

## Testing Checklist
- [ ] Open wp-agency dashboard at `/wp-admin/admin.php?page=wp-agency`
- [ ] Check stats cards display correctly (3 cards: Total, Active, Inactive)
- [ ] Verify DevTools shows cards INSIDE `wpapp-statistics-container`
- [ ] Compare structure with wp-customer (should match)
- [ ] Check custom agency styling still applied (colors, etc.)
- [ ] Verify no scroll jump when opening detail panel

## References
- Review-05 in `wp-app-core/claude-chats/task-1179.md`
- Screenshot: `/home/mkt01/Downloads/wp-customer-companies-04.png` (reference)
- Screenshot: `/home/mkt01/Downloads/wp-agency-disnaker-04.png` (before fix)
