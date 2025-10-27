# TODO-3072: Rename agency-card to agency-stats-card

## Status
✅ **COMPLETED** - 2025-10-25

## Requirement (Review-06)
Di plugin wp-agency:
1. Ubah class `agency-card` menjadi `agency-stats-card`
2. Salin property CSS `customer-stats-card` dari `companies.css` ke `agency-stats-card` di `agency-header-cards.css`

## Changes Implemented

### 1. AgencyDashboardController.php - HTML Class Names ✅
**File**: `wp-agency/src/Controllers/Agency/AgencyDashboardController.php`
**Method**: `render_header_cards()`

**Changes:**
All class names updated to use `agency-stats-*` prefix:

| Before | After |
|--------|-------|
| `agency-card` | `agency-stats-card` |
| `agency-card-blue` | `agency-stats-card-blue` |
| `agency-card-green` | `agency-stats-card-green` |
| `agency-card-orange` | `agency-stats-card-orange` |
| `agency-card-icon` | `agency-stats-icon` |
| `agency-card-content` | `agency-stats-content` |
| `agency-card-value` | `agency-stats-value` |
| `agency-card-label` | `agency-stats-label` |

**Example HTML Structure (After):**
```html
<div class="stats-card agency-stats-card agency-stats-card-blue">
    <div class="stats-icon agency-stats-icon">
        <span class="dashicons dashicons-building"></span>
    </div>
    <div class="stats-content agency-stats-content">
        <div class="stats-number agency-stats-value">10</div>
        <div class="stats-label agency-stats-label">Total Disnaker</div>
    </div>
</div>
```

### 2. agency-header-cards.css - CSS Properties ✅
**File**: `wp-agency/assets/css/agency/agency-header-cards.css`

**Changes:**
Updated all CSS selectors and copied properties from `customer-stats-card` (companies.css).

#### Main Card Styles (Copied from customer-stats-card)
```css
.agency-stats-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s, box-shadow 0.2s;
}

.agency-stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
```

**Key Property Changes:**
- `padding`: `10px 15px` → `20px` (matched with customer)
- `background`: `#f8f9fa` → `#fff` (matched with customer)
- `border-radius`: `6px` → `8px` (matched with customer)
- `box-shadow`: Added to match customer style
- Removed `min-width: 120px` (let grid handle sizing)

#### Icon Styles
```css
.agency-stats-icon {
    width: 55px;           /* was 40px */
    height: 55px;          /* was 40px */
    min-width: 55px;       /* added */
    background: #f0f0f0;   /* was rgba(0, 0, 0, 0.05) */
    font-size: 26px;       /* added */
}

.agency-stats-icon .dashicons {
    font-size: 26px;       /* was 20px */
    width: 26px;           /* was 20px */
    height: 26px;          /* was 20px */
}
```

#### Value/Label Styles
```css
.agency-stats-value {
    margin: 0 0 5px 0;     /* added margin */
    font-size: 32px;       /* was 18px */
    font-weight: 700;      /* was 600 */
    line-height: 1;        /* was 1.2 */
}

.agency-stats-label {
    margin: 0;             /* added */
    font-size: 13px;       /* was 11px */
    color: #7f8c8d;        /* was #666 */
    font-weight: 500;      /* added */
}
```

#### Color Variants
All color variants updated:
- `.agency-stats-card-blue`
- `.agency-stats-card-green`
- `.agency-stats-card-orange`
- `.agency-stats-card-red`

## Benefits
1. ✅ Consistent naming convention with stats system
2. ✅ Visual consistency with wp-customer stats cards
3. ✅ Proper sizing and spacing (larger, more readable)
4. ✅ Better visual hierarchy with updated font sizes
5. ✅ Maintained color variants for visual distinction

## Files Modified
1. ✅ `wp-agency/src/Controllers/Agency/AgencyDashboardController.php` (lines 216-247)
2. ✅ `wp-agency/assets/css/agency/agency-header-cards.css` (entire file)

## Before vs After Comparison

### Visual Changes:
- **Card size**: Compact (40px icon) → Standard (55px icon)
- **Value font**: 18px/600 → 32px/700 (larger, bolder)
- **Label font**: 11px → 13px (more readable)
- **Padding**: 10px 15px → 20px (more spacious)
- **Background**: Light gray (#f8f9fa) → White (#fff)

### HTML Structure Comparison:
```html
<!-- Before -->
<div class="stats-card agency-card agency-card-blue">
    <div class="stats-icon agency-card-icon">...</div>
    <div class="stats-content agency-card-content">...</div>
</div>

<!-- After -->
<div class="stats-card agency-stats-card agency-stats-card-blue">
    <div class="stats-icon agency-stats-icon">...</div>
    <div class="stats-content agency-stats-content">...</div>
</div>
```

## Testing Checklist
- [ ] Clear browser cache and WordPress cache
- [ ] Open wp-agency dashboard
- [ ] Verify stats cards display with new styling:
  - [ ] Larger icons (55px vs 40px)
  - [ ] Larger numbers (32px vs 18px)
  - [ ] White background (not gray)
  - [ ] Proper spacing (20px padding)
- [ ] Check hover effect (translateY + shadow)
- [ ] Verify color variants still work (blue, green, orange)
- [ ] Compare with wp-customer - should look visually similar

## Related TODOs
- See: `wp-app-core/TODO/TODO-1179-fix-statistics-container-hook.md`
- See: `wp-agency/TODO/TODO-3071-fix-stats-cards-container-position.md`

## References
- Review-06 in `wp-app-core/claude-chats/task-1179.md`
- Source CSS: `wp-customer/assets/css/companies/companies.css` (`.customer-stats-card`)
