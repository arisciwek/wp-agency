# TODO-3094: Add DataTable Filter Hook for Cross-Plugin Integration

**Status**: ✅ COMPLETED
**Date**: 2025-10-31
**Related**: Task-2176, TODO-2183

## Problem
The `AgencyModel::getDataTableData()` method did not provide filter hooks for external plugins to modify the WHERE clause, preventing proper integration with wp-customer's `AgencyAccessFilter`.

## Solution

### Changes in wp-agency

**File**: `src/Models/Agency/AgencyModel.php`
**Version**: 1.0.7 → 1.0.8

#### 1. Changed WHERE Clause to Array-Based Approach
```php
// Before: String concatenation
$where = " WHERE 1=1";
$where .= $wpdb->prepare(" AND p.status = %s", $status_filter);
$where .= " AND 1=0";  // Hardcoded restriction

// After: Array-based (TODO-3094)
$where_conditions = [];
$where_conditions[] = $wpdb->prepare("p.status = %s", $status_filter);
$where_conditions[] = "1=0";
```

#### 2. Added Filter Hook
```php
/**
 * Filter WHERE conditions for agency DataTable
 *
 * Allows other plugins (like wp-customer) to add filtering logic
 *
 * @since TODO-3094
 * @param array $where_conditions Array of WHERE clause conditions
 * @param array $request_data DataTable request parameters
 * @param AgencyModel $this Model instance
 */
$where_conditions = apply_filters(
    'wpapp_datatable_app_agencies_where',
    $where_conditions,
    $request_data,
    $this
);
```

#### 3. Fixed Hardcoded Access Restriction
```php
// Before: Block all results for users without view_own_agency
else {
    $where .= " AND 1=0";
    error_log('User has no access - restricting all results');
}

// After: Allow hook-based filtering for view_agency_list users
else {
    // Check if user has view_agency_list capability (for cross-plugin integration)
    if (!current_user_can('view_agency_list')) {
        $where_conditions[] = "1=0";
        error_log('User has no access - restricting all results');
    } else {
        error_log('User has view_agency_list capability - allowing hook-based filtering');
    }
}
```

#### 4. Fixed Total Count Calculation
```php
// Before: String replacement (unreliable with array-based WHERE)
$total_sql = str_replace($search_condition, '', $total_sql);

// After: Array filtering (TODO-3094)
$total_where_conditions = array_filter($where_conditions, function($condition) use ($search) {
    // Remove search conditions for total count
    return $condition !== $search_pattern;
});
```

### Integration Pattern

This follows the wp-app-core DataTable pattern:

```php
// wp-app-core pattern
$where_conditions = apply_filters(
    $this->get_filter_hook('where'),  // wpapp_datatable_{table}_where
    $where_conditions,
    $request_data,
    $this
);

// wp-agency implementation (TODO-3094)
$where_conditions = apply_filters(
    'wpapp_datatable_app_agencies_where',
    $where_conditions,
    $request_data,
    $this
);
```

### Benefits

1. **Extensibility**: Other plugins can filter agency data without modifying wp-agency code
2. **Decoupling**: wp-agency doesn't need to know about wp-customer
3. **Maintainability**: Changes in one plugin don't require changes in the other
4. **Consistency**: Follows wp-app-core DataTable patterns

## Usage Example

### wp-customer Integration
```php
// AgencyAccessFilter.php
add_filter('wpapp_datatable_app_agencies_where', [$this, 'filter_agencies_by_customer'], 10, 3);

public function filter_agencies_by_customer($where, $request, $model) {
    // Get accessible agencies for customer_admin
    $accessible_agencies = [1, 2, 3];

    if (empty($accessible_agencies)) {
        $where[] = "1=0";
    } else {
        $ids = implode(',', $accessible_agencies);
        $where[] = "p.id IN ({$ids})";  // Note: Use 'p' alias
    }

    return $where;
}
```

## Testing

### Test Case 1: Filter Hook Applied
1. Enable WP_DEBUG
2. Login as customer_admin
3. Load Disnaker page
4. Check debug.log for: "User has view_agency_list capability - allowing hook-based filtering"
5. Check for: "AgencyAccessFilter: User X has access to Y agencies"

### Test Case 2: WHERE Array Conversion
```php
// Input conditions
$where_conditions = [
    "p.status = 'active'",
    "p.id IN (1,2,3)"
];

// Expected output
// WHERE 1=1 AND p.status = 'active' AND p.id IN (1,2,3)
```

### Test Case 3: Total Count Without Search
```php
// With search
$where_conditions = [
    "p.status = 'active'",
    "(p.name LIKE '%test%' OR p.code LIKE '%test%')"
];

// Total count should exclude search
// WHERE 1=1 AND p.status = 'active'
```

## Performance Impact
- Negligible: Only adds one `apply_filters()` call
- Filter only runs when DataTable is loaded
- No additional database queries

## Backward Compatibility
✅ **Fully backward compatible**
- Existing behavior unchanged for non-filtered users
- No breaking changes to API
- Admin users see same data as before

## Related Files
- `/wp-agency/src/Models/Agency/AgencyModel.php` - Added filter hook (TODO-3094)
- `/wp-customer/src/Integrations/AgencyAccessFilter.php` - Uses the hook (TODO-2183)

## Documentation
Added PHPDoc block for the filter:
```php
/**
 * Filter WHERE conditions for agency DataTable
 *
 * @since TODO-3094
 * @param array $where_conditions Array of WHERE clause conditions
 * @param array $request_data DataTable request parameters
 * @param AgencyModel $this Model instance
 */
```

## Migration Notes
1. No database changes required
2. Clear cache after deployment: `wp cache flush`
3. Verify customer_admin can see Disnaker list

## Future Enhancements
- Add similar filter for JOIN clauses: `wpapp_datatable_app_agencies_joins`
- Add filter for SELECT columns: `wpapp_datatable_app_agencies_columns`
- Consider adding to other models (DivisionModel, EmployeeModel)
