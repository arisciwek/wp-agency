# Permission Filter Hooks

Filter hooks for modifying permission checks in WP Agency plugin.

## Table of Contents

- [wp_agency_can_create_employee](#wp_agency_can_create_employee)
- [wp_agency_can_create_division](#wp_agency_can_create_division)
- [wp_agency_max_inspector_assignments](#wp_agency_max_inspector_assignments)

---

## wp_agency_can_create_employee

**Purpose**: Override employee creation permission check

**Location**: `src/Validators/Employee/AgencyEmployeeValidator.php:153`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$can_create` | bool | Default permission (false) |
| `$agency_id` | int | Agency ID |
| `$division_id` | int | Division ID |
| `$current_user_id` | int | Current user ID |

### Return Value

- Type: `bool`
- `true` - Allow employee creation
- `false` - Deny employee creation

### Use Cases

1. **Custom Role Support**: Allow custom roles to create employees
2. **Business Hours Restriction**: Only allow creation during business hours
3. **Quota System**: Limit employees per division
4. **Conditional Access**: Based on membership level or subscription

### Example - Business Hours Restriction

```php
add_filter('wp_agency_can_create_employee', 'restrict_employee_creation_hours', 10, 4);

function restrict_employee_creation_hours($can_create, $agency_id, $division_id, $user_id) {
    $current_hour = (int) current_time('H');

    // Only allow 8 AM - 5 PM
    if ($current_hour < 8 || $current_hour >= 17) {
        return false;
    }

    // Check other permissions
    return current_user_can('add_agency_employee');
}
```

### Example - Employee Quota per Division

```php
add_filter('wp_agency_can_create_employee', 'check_employee_quota', 10, 4);

function check_employee_quota($can_create, $agency_id, $division_id, $user_id) {
    global $wpdb;

    // Max 50 employees per division
    $max_employees = 50;

    $current_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_employees
         WHERE division_id = %d AND status = 'active'",
        $division_id
    ));

    if ($current_count >= $max_employees) {
        add_filter('wp_agency_employee_validation_error', function() {
            return 'Employee quota exceeded! Maximum ' . $max_employees . ' employees per division.';
        });
        return false;
    }

    return $can_create;
}
```

### Notes

- Filter called during validation
- Return `false` to prevent employee creation
- Always return a boolean value
- Called before database insert

---

## wp_agency_can_create_division

**Purpose**: Override division creation permission check

**Location**: `src/Validators/Division/DivisionValidator.php:218`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$can_create` | bool | Default permission (false) |
| `$agency_id` | int | Agency ID |
| `$current_user_id` | int | Current user ID |

### Return Value

- Type: `bool`
- `true` - Allow division creation
- `false` - Deny division creation

### Use Cases

1. **Custom Role Support**: Allow custom roles to create divisions
2. **Quota System**: Limit divisions per agency
3. **Membership Restriction**: Based on agency membership level

### Example - Division Quota per Agency

```php
add_filter('wp_agency_can_create_division', 'check_division_quota', 10, 3);

function check_division_quota($can_create, $agency_id, $user_id) {
    global $wpdb;

    // Max 10 divisions per agency (including pusat)
    $max_divisions = 10;

    $current_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_divisions
         WHERE agency_id = %d",
        $agency_id
    ));

    if ($current_count >= $max_divisions) {
        return false;
    }

    return $can_create || current_user_can('add_agency_division');
}
```

### Notes

- Filter called during validation
- Return `false` to prevent division creation
- Always return a boolean value

---

## wp_agency_max_inspector_assignments

**Purpose**: Set maximum number of companies an inspector can be assigned to

**Location**: `src/Validators/Company/NewCompanyValidator.php:231`

**Version**: Since 1.0.0

### Parameters

None

### Return Value

- Type: `int`
- Default: `50`
- Maximum number of company assignments per inspector

### Use Cases

1. **Load Balancing**: Limit inspector workload
2. **Performance Tuning**: Adjust based on system capacity
3. **Business Rules**: Different limits for different inspector types

### Example - Custom Limit

```php
add_filter('wp_agency_max_inspector_assignments', function() {
    return 100; // Increase to 100
});
```

### Example - Role-based Limits

```php
add_filter('wp_agency_max_inspector_assignments', 'inspector_limit_by_role');

function inspector_limit_by_role() {
    $user = wp_get_current_user();

    if (in_array('agency_admin_dinas', $user->roles)) {
        return 200; // Admin can handle more
    }

    if (in_array('agency_kepala_unit', $user->roles)) {
        return 100; // Unit heads handle more
    }

    return 50; // Default for regular inspectors
}
```

### Notes

- Called during company assignment validation
- Must return an integer
- Affects inspector workload distribution

---

**See Also**:
- [UI Filters](ui-filters.md)
- [System Filters](system-filters.md)
- [Hook Examples](../examples/)
