# TODO-2048: Fix Company DataTable Cache Clearing After Inspector Assignment

## Issue Description
After successful inspector assignment in wp-agency plugin, the company datatable in wp-customer plugin does not immediately update. The cached data persists for 2 minutes before refreshing.

## Root Cause
The DataTable cache in wp-customer ('company_list' context) is not invalidated when inspector assignments are made in wp-agency. Since the assignment updates the database correctly but doesn't clear the external plugin's cache, users see stale data.

## Solution Implemented
Modified the `assignInspector` method in `NewCompanyController.php` to clear the wp-customer DataTable cache after successful assignment.

### Changes Made
- Added cache clearing logic for wp-customer DataTable after successful inspector assignment
- Check if `CustomerCacheManager` class exists (wp-customer plugin active)
- Call `invalidateDataTableCache('company_list')` to clear all cached DataTable responses
- Added error handling to prevent failures if wp-customer plugin is not active

### Code Changes
```php
// After successful assignment
if (class_exists('WPCustomer\Cache\CustomerCacheManager')) {
    try {
        $customerCache = new \WPCustomer\Cache\CustomerCacheManager();
        $customerCache->invalidateDataTableCache('company_list');
        error_log("DEBUG - Cleared wp-customer company datatable cache");
    } catch (\Exception $e) {
        error_log("DEBUG - Failed to clear wp-customer cache: " . $e->getMessage());
    }
}
```

## Files Modified
- `/wp-agency/src/Controllers/Company/NewCompanyController.php`

## Testing
- Verify that after inspector assignment, company datatable updates immediately
- Confirm no errors when wp-customer plugin is inactive
- Check that cache invalidation logs appear in debug mode

## Related TODOs
- TODO-2047: Debug assign inspector not updating count
- TODO-2111: Investigate Cache Key in Company DataTable (wp-customer)
