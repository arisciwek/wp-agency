# WP Agency - Fixes Log

## Issue: Stats Cards Hilang Saat Panel Dibuka

### Problem:
1. ❌ Console kosong (JS tidak load)
2. ❌ Cards hilang saat reload
3. ❌ Cards hilang/naik ke atas saat klik view button

### Root Causes Identified:

#### Issue #1: Panel Manager JS Tidak Load
**Cause**: Hook `toplevel_page_wp-agency-disnaker` tidak ada di whitelist `DataTableAssetsController`

**Fix**: Added hook to allowed list
```php
// File: wp-app-core/src/Controllers/DataTable/DataTableAssetsController.php
// Line: 71

$allowed_hooks = apply_filters('wpapp_datatable_allowed_hooks', [
    'toplevel_page_wp-customer',
    'toplevel_page_wp-agency',
    'toplevel_page_wp-agency-disnaker',  // ← ADDED THIS
    'wp-customer_page_wp-customer-companies',
    'wp-customer_page_wp-customer-company-invoice',
    'wp-agency_page_wp-agency-employees',
]);
```

**Result**: ✅ Panel manager now loads, console shows debug logs

---

#### Issue #2: Cards Di Hook Ke Tempat Yang Salah
**Cause**: Cards di-hook ke `wpapp_page_header_center` (page header top) instead of `wpapp_dashboard_before_stats` (navigation container)

**Fix**: Moved hook to correct location
```php
// File: wp-agency/src/Controllers/Agency/AgencyDashboardController.php
// Line: 98

// BEFORE:
add_action('wpapp_page_header_center', [$this, 'render_header_cards'], 10, 2);

// AFTER:
add_action('wpapp_dashboard_before_stats', [$this, 'render_header_cards'], 10, 2);
```

**Result**: ✅ Cards appear in navigation container, stay visible on page reload

---

#### Issue #3: Cards Masih Naik 50% Saat Klik View Button (IN PROGRESS)
**Cause**: TBD - Layout shift during panel opening

**Status**: 🔍 Investigating

**Next Steps**:
1. Check console log for navigationTop delta value
2. Check if there's CSS causing height change
3. Check if viewport auto-scrolls to panel

---

## Testing Checklist:
- [x] Panel manager JS loads
- [x] Console shows debug logs
- [x] Cards visible on page load
- [x] Cards stay visible on reload
- [ ] Cards stay visible when clicking view button (50% working)

## Date: 2025-10-24

---

#### Issue #3: Horizontal Split Layout - Filters LEFT | Stats RIGHT

**Reason**: User request untuk improve layout dan prevent scroll jump

**Changes Made:**

1. **Restructured NavigationTemplate.php**
   ```html
   <!-- BEFORE (Vertical Stack): -->
   <div class="wpapp-navigation-container">
     <div class="stats">...</div>
     <div class="filters">...</div>
   </div>

   <!-- AFTER (Horizontal Split): -->
   <div class="wpapp-navigation-container">
     <div class="wpapp-navigation-split">
       <div class="wpapp-navigation-left">
         <!-- Filters here -->
       </div>
       <div class="wpapp-navigation-right">
         <!-- Stats cards here -->
       </div>
     </div>
   </div>
   ```

2. **Added CSS for Horizontal Layout**
   ```css
   /* File: wp-app-core/assets/css/datatable/wpapp-datatable.css */
   
   .wpapp-navigation-split {
       display: flex;
       gap: 20px;
       align-items: flex-start;
   }

   .wpapp-navigation-left {
       flex: 1;                    /* Filters take remaining space */
       min-width: 0;
   }

   .wpapp-navigation-right {
       flex-shrink: 0;             /* Stats cards don't shrink */
       min-width: fit-content;
   }
   ```

3. **Added Responsive CSS**
   ```css
   @media (max-width: 1024px) {
       /* Stack vertically on tablets/mobile */
       .wpapp-navigation-split {
           flex-direction: column;
       }
   }
   ```

**Benefits:**
- ✅ Filters di kiri (easy access)
- ✅ Stats cards di kanan (fixed position, tidak bergeser)
- ✅ Cleaner separation between sections
- ✅ Responsive: stacks vertically on mobile
- ✅ No scope mixing (wpapp-* global, agency-* local properly nested)

**Status**: ⏳ Testing required

---

## Summary of All Fixes:

| Issue | Status | Impact |
|-------|--------|--------|
| #1: Panel Manager JS tidak load | ✅ FIXED | Console now shows debug logs |
| #2: Cards di hook ke tempat salah | ✅ FIXED | Cards appear in navigation container |
| #3: Horizontal split layout | ✅ IMPLEMENTED | Filters LEFT, Stats RIGHT |

**Next**: User testing required to confirm cards stay visible when clicking view button.

