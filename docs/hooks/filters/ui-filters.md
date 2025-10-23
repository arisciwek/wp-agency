# UI Filter Hooks

Filter hooks for customizing user interface elements in WP Agency plugin.

## Table of Contents

- [wp_agency_enable_export](#wp_agency_enable_export)
- [wp_company_detail_tabs](#wp_company_detail_tabs)

---

## wp_agency_enable_export

**Purpose**: Enable or disable export button in DataTables

**Location**: 
- `src/Views/templates/employee/partials/_employee_list.php:122`
- `src/Views/templates/division/partials/_agency_division_list.php:125`

**Version**: Since 1.0.0

### Parameters

None

### Return Value

- Type: `bool`
- `true` - Show export button
- `false` - Hide export button (default)

### Use Cases

1. **Enable Export Feature**: Show export button for data download
2. **Role-based Display**: Show only for certain roles
3. **Premium Feature**: Show only for premium agencies

### Example - Enable for Admins Only

```php
add_filter('wp_agency_enable_export', 'enable_export_for_admins');

function enable_export_for_admins() {
    return current_user_can('manage_options');
}
```

### Example - Enable for Agency Admins

```php
add_filter('wp_agency_enable_export', function() {
    $user = wp_get_current_user();
    return in_array('agency_admin_dinas', $user->roles);
});
```

### Notes

- Filter called in view templates
- Must return boolean
- Affects employee and division list tables
- Export functionality must be implemented separately

---

## wp_company_detail_tabs

**Purpose**: Add or remove tabs from company detail page

**Location**: `src/Views/templates/company/company-right-panel.php:25`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tabs` | array | Default tabs array |

### Default Tabs Array

```php
[
    'detail' => [
        'label' => 'Detail',
        'icon' => 'dashicons-info-outline',
        'active' => true
    ],
    'attachment' => [
        'label' => 'Attachment',
        'icon' => 'dashicons-paperclip',
        'active' => false
    ]
]
```

### Return Value

- Type: `array`
- Modified tabs array

### Use Cases

1. **Add Custom Tabs**: Add new tabs for custom functionality
2. **Remove Tabs**: Hide tabs based on permissions
3. **Reorder Tabs**: Change tab order
4. **Custom Icons**: Change tab icons

### Example - Add Custom Tab

```php
add_filter('wp_company_detail_tabs', 'add_company_audit_tab');

function add_company_audit_tab($tabs) {
    $tabs['audit'] = [
        'label' => 'Audit Log',
        'icon' => 'dashicons-list-view',
        'active' => false
    ];

    return $tabs;
}
```

### Example - Remove Tab Based on Permission

```php
add_filter('wp_company_detail_tabs', 'filter_company_tabs_by_permission');

function filter_company_tabs_by_permission($tabs) {
    if (!current_user_can('view_company_attachments')) {
        unset($tabs['attachment']);
    }

    return $tabs;
}
```

### Example - Reorder Tabs

```php
add_filter('wp_company_detail_tabs', 'reorder_company_tabs');

function reorder_company_tabs($tabs) {
    $new_order = [];

    // Put attachment first
    if (isset($tabs['attachment'])) {
        $new_order['attachment'] = $tabs['attachment'];
    }

    // Then detail
    if (isset($tabs['detail'])) {
        $new_order['detail'] = $tabs['detail'];
    }

    // Add any other tabs
    foreach ($tabs as $key => $tab) {
        if (!isset($new_order[$key])) {
            $new_order[$key] = $tab;
        }
    }

    return $new_order;
}
```

### Tab Template Loading

Tabs load templates from: `src/Views/templates/company/tabs/{tab_key}-tab.php`

You can override template path using another filter (if implemented).

### Notes

- Filter called when rendering company detail panel
- Must return array with same structure
- Tab templates must exist in templates directory
- Only one tab can be `'active' => true` at a time

---

**See Also**:
- [Permission Filters](permission-filters.md)
- [System Filters](system-filters.md)
- [Hook Examples](../examples/)
