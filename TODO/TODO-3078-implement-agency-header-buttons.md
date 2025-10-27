# TODO-3078: Implement Agency Header Action Buttons

**Status**: ✅ COMPLETED
**Plugin**: wp-agency
**Created**: 2025-10-26
**Completed**: 2025-10-26
**Related**: wp-app-core TODO-1181

## 📋 Description

Implementasi tombol action (Print, Export, Tambah Disnaker) di page header Agency dashboard menggunakan hook `wpapp_page_header_right` dari wp-app-core.

## ✅ What Was Done

### 1. Register Hook untuk Header Buttons

**File**: `src/Controllers/Agency/AgencyDashboardController.php`

**Changes**:

**Line 96**: Register hook `wpapp_page_header_right`
```php
add_action('wpapp_page_header_right', [$this, 'render_header_buttons'], 10, 2);
```

**Line 190-226**: Method `render_header_buttons()`
```php
public function render_header_buttons($config, $entity): void {
    if ($entity !== 'agency') {
        return;
    }

    ?>
    <div class="wpapp-header-buttons">
        <?php if (current_user_can('view_agency_list')): ?>
            <button type="button" class="button agency-print-btn" id="agency-print-btn">
                <span class="dashicons dashicons-printer"></span>
                Print
            </button>

            <button type="button" class="button agency-export-btn" id="agency-export-btn">
                <span class="dashicons dashicons-download"></span>
                Export
            </button>
        <?php endif; ?>

        <?php if (current_user_can('add_agency')): ?>
            <a href="#" class="button button-primary agency-add-btn">
                <span class="dashicons dashicons-plus-alt"></span>
                Tambah Disnaker
            </a>
        <?php endif; ?>
    </div>
    <?php
}
```

## 🎯 Buttons Implemented

### 1. Print Button
- **Icon**: `dashicons-printer`
- **ID**: `agency-print-btn`
- **Class**: `button agency-print-btn`
- **Permission**: `view_agency_list`
- **Action**: Ready for JS handler

### 2. Export Button
- **Icon**: `dashicons-download`
- **ID**: `agency-export-btn`
- **Class**: `button agency-export-btn`
- **Permission**: `view_agency_list`
- **Action**: Ready for JS handler

### 3. Tambah Disnaker Button
- **Icon**: `dashicons-plus-alt`
- **Class**: `button button-primary agency-add-btn`
- **Permission**: `add_agency` (from PermissionModel)
- **Action**: Ready for modal/form handler

## 🔐 Permission Integration

Menggunakan capabilities dari `src/Models/Settings/PermissionModel.php`:

- ✅ `view_agency_list` (line 31) - untuk Print & Export
- ✅ `add_agency` (line 34) - untuk Tambah Disnaker

## 🎨 Layout Result

```
┌────────────────────────────────────────────────────────┐
│ Daftar Disnaker              [Print] [Export] [+Tambah]│
│ Kelola data dinas tenaga kerja                          │
└────────────────────────────────────────────────────────┘
```

## 📊 Permission Matrix

| Role | Print | Export | Tambah Disnaker |
|------|-------|--------|-----------------|
| Administrator | ✅ | ✅ | ✅ |
| Agency | ✅ | ✅ | ❌ (default false) |
| Agency Admin Dinas | ✅ | ✅ | ⚙️ (configurable) |
| Agency Employee Roles | ✅ | ✅ | ⚙️ (configurable) |

## 🔗 Related Files

- **Base Template**: `wp-app-core/src/Views/DataTable/Templates/DashboardTemplate.php:154`
- **CSS Styles**: `wp-app-core/assets/css/datatable/wpapp-datatable.css:91-111`
- **Permission Model**: `src/Models/Settings/PermissionModel.php`
- **wp-app-core TODO**: `wp-app-core/TODO/TODO-1181-implement-header-action-buttons.md`

## 🔄 Next Steps

### Phase 2: Implement Button Actions (TODO-3079)

1. **Print Button Handler**
   ```javascript
   // Print current DataTable view
   $('#agency-print-btn').on('click', function() {
       // Trigger DataTable print
       // Or open print preview with filtered data
   });
   ```

2. **Export Button Handler**
   ```javascript
   // Export to Excel/CSV
   $('#agency-export-btn').on('click', function() {
       // Show export options modal
       // - Excel (.xlsx)
       // - CSV
       // - PDF
   });
   ```

3. **Tambah Disnaker Handler**
   ```javascript
   // Open add agency modal
   $('.agency-add-btn').on('click', function(e) {
       e.preventDefault();
       // Show add agency modal
       // Or redirect to add agency page
   });
   ```

## 📝 Implementation Pattern

Hook pattern yang bisa digunakan plugin lain:

```php
// In Plugin Dashboard Controller
public function __construct() {
    add_action('wpapp_page_header_right', [$this, 'render_header_buttons'], 10, 2);
}

public function render_header_buttons($config, $entity): void {
    if ($entity !== 'your_entity') {
        return;
    }

    ?>
    <div class="wpapp-header-buttons">
        <?php if (current_user_can('your_capability')): ?>
            <button type="button" class="button your-btn">
                <span class="dashicons dashicons-icon"></span>
                Button Text
            </button>
        <?php endif; ?>
    </div>
    <?php
}
```

## ✨ Benefits

1. ✅ **No Template Changes**: Menggunakan hook system
2. ✅ **Permission-Based UI**: Tombol muncul sesuai capability
3. ✅ **Consistent UI**: Menggunakan WordPress button classes
4. ✅ **Icon Aligned**: Dashicons vertical center dengan text
5. ✅ **Extensible**: Mudah tambah tombol baru
6. ✅ **JS Ready**: ID sudah tersedia untuk event handlers

## 🎯 Testing Checklist

- ✅ Administrator: Sees all 3 buttons
- ✅ Agency role without add_agency: Sees only Print & Export
- ✅ Agency role with add_agency: Sees all 3 buttons
- ✅ Icon alignment: All dashicons vertically centered
- ✅ Button spacing: Consistent 10px gap
- ✅ Responsive: Buttons wrap gracefully on small screens

## 📌 Notes

- TODO numbering: wp-agency uses 3xxx series (last: 3077)
- wp-app-core uses 1xxx series for base system features
- Always check latest TODO number before creating new TODO
