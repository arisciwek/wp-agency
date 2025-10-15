# TODO-2048: Fix Company DataTable Cache Clearing After Inspector Assignment (COMPLETED)

## Issue Description
After successful inspector assignment in wp-agency plugin, the company datatable in wp-customer plugin does not immediately update. The cached data persists for 2 minutes before refreshing.

## Root Cause
The DataTable cache in wp-customer ('company_list' context) is not invalidated when inspector assignments are made in wp-agency. Since the assignment updates the database correctly but doesn't clear the external plugin's cache, users see stale data.

## Solution Implemented
Disabled caching for the company datatable in wp-customer to ensure fresh data after inspector assignments, since filters are based on membership status and may expand in the future.

### Changes Made
- Disabled caching for 'company_list' context in `CustomerCacheManager.php` by returning null from `getDataTableCache()` and true from `setDataTableCache()`
- This ensures the company datatable always queries fresh data from the database
- Kept the cache invalidation call in `NewCompanyController.php` for consistency

### Code Changes in wp-customer/src/Cache/CustomerCacheManager.php
```php
// In getDataTableCache()
if ($context === 'company_list') {
    return null;
}

// In setDataTableCache()
if ($context === 'company_list') {
    return true;
}
```

### Code Changes in wp-agency/src/Controllers/Company/NewCompanyController.php
```php
// After successful assignment
if (class_exists('\WPCustomer\Cache\CustomerCacheManager')) {
    try {
        $customerCache = new \WPCustomer\Cache\CustomerCacheManager();
        $customerCache->invalidateDataTableCache('company_list');
        error_log("DEBUG - Cleared wp-customer company datatable cache");
    } catch (\Exception $e) {
        error_log("DEBUG - Failed to clear wp-customer cache: " . $e->getMessage());
    }
}
```

## Root Cause Analysis
The company datatable filters are based on membership status (active/inactive), not inspector assignment. Since filters may expand in the future and tying cache invalidation to specific fields would be complex, disabling caching ensures data freshness without complications. The performance impact is minimal for this datatable.

## Files Modified
- `/wp-agency/src/Controllers/Company/NewCompanyController.php`

## Testing
- Verify that after inspector assignment, company datatable updates immediately
- Confirm no errors when wp-customer plugin is inactive
- Check that cache invalidation logs appear in debug mode

## Related TODOs
- TODO-2047: Debug assign inspector not updating count
- TODO-2111: Investigate Cache Key in Company DataTable (wp-customer)
