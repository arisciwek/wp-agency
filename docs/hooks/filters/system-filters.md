# System Filter Hooks

System configuration filter hooks in WP Agency plugin.

## Table of Contents

- [wp_agency_debug_mode](#wp_agency_debug_mode)
- [wilayah_indonesia_get_province_options](#wilayah_indonesia_get_province_options)
- [wilayah_indonesia_get_regency_options](#wilayah_indonesia_get_regency_options)

---

## wp_agency_debug_mode

**Purpose**: Enable debug logging for troubleshooting

**Location**: `src/Hooks/SelectListHooks.php:49`

**Version**: Since 1.0.0

### Parameters

None

### Return Value

- Type: `bool`
- `true` - Enable debug logging
- `false` - Disable debug logging (default)

### Use Cases

1. **Development**: Enable logging during development
2. **Troubleshooting**: Debug production issues
3. **Conditional Logging**: Enable for specific users or environments

### Example - Enable for Development

```php
add_filter('wp_agency_debug_mode', function() {
    return defined('WP_DEBUG') && WP_DEBUG;
});
```

### Example - Enable for Admins Only

```php
add_filter('wp_agency_debug_mode', function() {
    return current_user_can('manage_options');
});
```

### Notes

- Filter checked during select list rendering
- Debug logs written to PHP error log
- Consider performance impact in production

---

## wilayah_indonesia_get_province_options

**Purpose**: Get province dropdown options from wp-wilayah-indonesia plugin

**Location**: `src/Models/Agency/AgencyModel.php:794`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$options` | array | Default options array (empty by default) |

### Expected Return Value

```php
[
    [
        'code' => '11',
        'name' => 'Aceh'
    ],
    [
        'code' => '12',
        'name' => 'Sumatera Utara'
    ],
    // ... more provinces
]
```

### Use Cases

1. **Integration with wp-wilayah-indonesia**: Get province data
2. **Custom Data Source**: Override with custom province data
3. **Filtered List**: Show only certain provinces

### Example - Default Integration

```php
// Typically handled by wp-wilayah-indonesia plugin
add_filter('wilayah_indonesia_get_province_options', function($options) {
    global $wpdb;

    $provinces = $wpdb->get_results(
        "SELECT code, name FROM {$wpdb->prefix}wi_provinces ORDER BY name ASC",
        ARRAY_A
    );

    return $provinces;
});
```

### Notes

- Requires wp-wilayah-indonesia plugin
- Used in agency registration and edit forms
- Must return array of arrays with 'code' and 'name' keys

---

## wilayah_indonesia_get_regency_options

**Purpose**: Get regency dropdown options filtered by province

**Location**: `src/Models/Agency/AgencyModel.php:800`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$options` | array | Default options array (empty by default) |
| `$province_code` | string | Province code to filter regencies |

### Expected Return Value

```php
[
    [
        'code' => '1101',
        'name' => 'Kabupaten Aceh Barat'
    ],
    [
        'code' => '1102',
        'name' => 'Kabupaten Aceh Besar'
    ],
    // ... more regencies for the province
]
```

### Use Cases

1. **Integration with wp-wilayah-indonesia**: Get regency data by province
2. **Custom Data Source**: Override with custom regency data
3. **Filtered List**: Show only certain regencies

### Example - Default Integration

```php
// Typically handled by wp-wilayah-indonesia plugin
add_filter('wilayah_indonesia_get_regency_options', function($options, $province_code) {
    global $wpdb;

    if (empty($province_code)) {
        return [];
    }

    $regencies = $wpdb->get_results($wpdb->prepare(
        "SELECT code, name FROM {$wpdb->prefix}wi_regencies
         WHERE province_code = %s ORDER BY name ASC",
        $province_code
    ), ARRAY_A);

    return $regencies;
}, 10, 2);
```

### Notes

- Requires wp-wilayah-indonesia plugin
- Used in agency forms for cascade select (province → regency)
- Must return array of arrays with 'code' and 'name' keys
- Returns empty array if province_code is empty

---

**See Also**:
- [Permission Filters](permission-filters.md)
- [UI Filters](ui-filters.md)
- [Hook Examples](../examples/)
