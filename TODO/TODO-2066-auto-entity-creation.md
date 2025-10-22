# TODO-2066: Auto Entity Creation & Lifecycle Hooks

## Status: ✅ COMPLETED

## Date: 2025-01-22

## Description

Implementasi complete hook system untuk entity lifecycle di wp-agency, mengikuti pattern dari wp-customer dengan naming convention yang benar.

**Lifecycle Hooks:**
1. **Creation**: Agency created → Auto-create division pusat → Auto-create employee
2. **Deletion**: Soft delete support + before/after hooks for cascade cleanup

**Hook Pattern (Following wp-customer naming convention):**
```
wp_agency_agency_created → handleAgencyCreated() → create division pusat
wp_agency_division_created → handleDivisionCreated() → create employee

wp_agency_agency_before_delete → for validation/prevention
wp_agency_agency_deleted → for cascade cleanup

wp_agency_division_before_delete → for validation/prevention
wp_agency_division_deleted → for cascade cleanup
```

**Naming Convention:** `wp_{plugin}_{entity}_{action}`
- ✅ Entity name ALWAYS explicit (even if redundant like `agency_agency`)
- ✅ Follows WordPress + wp-customer standard
- ✅ Predictable and scalable

## Reference Files (wp-customer)

### Documentation
- `/wp-customer/TODO/TODO-2165-auto-entity-creation.md`
- `/wp-customer/TODO/TODO-2165-refactor-hook-naming-convention.md`
- `/wp-customer/TODO/TODO-2169-hook-documentation-planning.md`

### Implementation
- `/wp-customer/src/Controllers/Branch/BranchController.php`
- `/wp-customer/src/Controllers/Employee/CustomerEmployeeController.php`
- `/wp-customer/src/Controllers/CustomerController.php`
- `/wp-customer/src/Models/Branch/BranchModel.php`
- `/wp-customer/src/Models/Customer/CustomerModel.php`
- `/wp-customer/src/Handlers/AutoEntityCreator.php`
- `/wp-customer/src/Validators/Branch/BranchValidator.php`
- `/wp-customer/src/Validators/Employee/CustomerEmployeeValidator.php`
- `/wp-customer/src/Validators/CustomerValidator.php`

## Implementation Details

### 1. Hook Points Added

#### AgencyModel.php (v2.0.0 → v2.1.0)

**Location:** `/wp-agency/src/Models/Agency/AgencyModel.php:257-260`

**Hook Name:** `wp_agency_agency_created`

```php
$new_id = (int) $wpdb->insert_id;
error_log('AgencyModel::create() - Insert successful. New ID: ' . $new_id);

// Task-2066: Fire hook for auto-create division pusat
if ($new_id) {
    do_action('wp_agency_agency_created', $new_id, $insert_data);
}

$this->cache->invalidateAgencyCache($new_id);
```

**Parameters:**
- `$new_id` (int): Newly created agency ID
- `$insert_data` (array): Agency data used for creation

**Purpose:** Triggers automatic division pusat creation

**Note:** Hook name follows naming convention `wp_{plugin}_{entity}_{action}`
Entity name "agency" is explicit (wp_agency_**agency**_created) for consistency

---

#### DivisionModel.php (v1.0.0 → v1.1.0)

**Location:** `/wp-agency/src/Models/Division/DivisionModel.php:207-210`

**Hook Name:** `wp_agency_division_created`

```php
$new_id = (int) $wpdb->insert_id;

// Task-2066: Fire hook for auto-create employee
if ($new_id) {
    do_action('wp_agency_division_created', $new_id, $insertData);
}

// Invalidate unrestricted count cache
$this->cache->delete('division_total_count_unrestricted');
```

**Parameters:**
- `$new_id` (int): Newly created division ID
- `$insertData` (array): Division data used for creation

**Purpose:** Triggers automatic employee creation

---

### 2. Delete Hook Points Added

#### AgencyModel Delete Hooks (v2.1.0)

**Location:** `/wp-agency/src/Models/Agency/AgencyModel.php:481-565`

**Hooks:**
- `wp_agency_agency_before_delete` (line 506)
- `wp_agency_agency_deleted` (line 542)

```php
public function delete(int $id): bool {
    global $wpdb;

    // 1. Get agency data before deletion
    $agency = $this->find($id);
    $agency_data = [...]; // Complete data array

    // 2. Fire before delete hook (for validation/prevention)
    do_action('wp_agency_agency_before_delete', $id, $agency_data);

    // 3. Check if hard delete is enabled
    $settings = get_option('wp_agency_general_options', []);
    $is_hard_delete = isset($settings['enable_hard_delete']) &&
                     $settings['enable_hard_delete'] === true;

    // 4. Perform delete (soft or hard)
    if ($is_hard_delete) {
        // Hard delete - actual DELETE from database
        $result = $wpdb->delete($this->table, ['id' => $id], ['%d']);
    } else {
        // Soft delete - set status to 'inactive'
        $result = $wpdb->update(
            $this->table,
            ['status' => 'inactive', 'updated_at' => current_time('mysql')],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );
    }

    // 5. Fire after delete hook (for cascade cleanup)
    if ($result !== false) {
        do_action('wp_agency_agency_deleted', $id, $agency_data, $is_hard_delete);
        // Cache invalidation...
    }
}
```

**before_delete Parameters:**
- `$id` (int): Agency ID to be deleted
- `$agency_data` (array): Complete agency data before deletion

**deleted Parameters:**
- `$id` (int): Deleted agency ID
- `$agency_data` (array): Agency data that was deleted
- `$is_hard_delete` (bool): true=hard delete, false=soft delete (status=inactive)

**Purpose:**
- `before_delete`: Validation, prevention, or preparation before deletion
- `deleted`: Cascade cleanup, external sync, audit logging

**Soft Delete vs Hard Delete:**
- **Soft Delete** (default): Sets `status='inactive'`, data remains in database
- **Hard Delete**: Actual DELETE from database (enabled via settings)
- Controlled by option: `wp_agency_general_options['enable_hard_delete']`

---

#### DivisionModel Delete Hooks (v1.1.0)

**Location:** `/wp-agency/src/Models/Division/DivisionModel.php:305-389`

**Hooks:**
- `wp_agency_division_before_delete` (line 338)
- `wp_agency_division_deleted` (line 374)

Same pattern as AgencyModel, with division-specific data structure.

**deleted Parameters:**
- `$id` (int): Deleted division ID
- `$division_data` (array): Division data that was deleted
- `$is_hard_delete` (bool): true=hard delete, false=soft delete

---

### 3. Handler Class Created

#### AutoEntityCreator.php (v1.0.0 - NEW)

**Location:** `/wp-agency/src/Handlers/AutoEntityCreator.php`

**Purpose:** Centralized handler for all auto entity creation logic

**Methods:**

##### handleAgencyCreated()
```php
public function handleAgencyCreated(int $agency_id, array $agency_data): void
```

**Responsibilities:**
1. Validate agency exists
2. Check if division pusat already exists (prevent duplicates)
3. Prepare division pusat data
4. Create division with type='pusat'
5. Log success/failure

**Division Data Structure:**
```php
$division_data = [
    'agency_id' => $agency_id,
    'name' => $agency->name . ' - Pusat',
    'type' => 'pusat',
    'status' => 'active',
    'provinsi_code' => $agency_data['provinsi_code'] ?? null,
    'regency_code' => $agency_data['regency_code'] ?? null,
    'user_id' => $agency_data['user_id'] ?? null,
    'created_by' => $agency_data['created_by'] ?? get_current_user_id()
];
```

**Validations:**
- ✅ Agency must exist
- ✅ Division pusat must not already exist
- ✅ All data properly sanitized

---

##### handleDivisionCreated()
```php
public function handleDivisionCreated(int $division_id, array $division_data): void
```

**Responsibilities:**
1. Validate division exists
2. Check if user_id present in division_data
3. Check if employee already exists (prevent duplicates)
4. Get WordPress user data
5. Create employee with default settings
6. Log success/failure

**Employee Data Structure:**
```php
$employee_data = [
    'agency_id' => $division->agency_id,
    'division_id' => $division_id,
    'user_id' => $user_id,
    'name' => $user->display_name,
    'position' => 'Admin',
    'finance' => 0,      // Not used in wp-agency
    'operation' => 0,    // Not used in wp-agency
    'legal' => 0,        // Not used in wp-agency
    'purchase' => 0,     // Not used in wp-agency
    'keterangan' => 'Auto-created by system',
    'email' => $user->user_email,
    'phone' => '-',
    'status' => 'active',
    'created_by' => $division_data['created_by'] ?? get_current_user_id()
];
```

**Validations:**
- ✅ Division must exist
- ✅ user_id must be present
- ✅ Employee must not already exist for user+division combination
- ✅ WordPress user must exist

**Note on Department Fields:**
Fields `finance`, `operation`, `legal`, `purchase` are set to 0 because they are not actively used in wp-agency (unlike wp-customer where they control access).

---

### 4. Supporting Method Added

#### AgencyEmployeeModel::findByUserAndDivision() (v1.0.0 → v1.1.0)

**Location:** `/wp-agency/src/Models/Employee/AgencyEmployeeModel.php:137-166`

```php
public function findByUserAndDivision(int $user_id, int $division_id): ?object
```

**Purpose:** Check if employee already exists before auto-creation

**Features:**
- Cache-enabled (5 minutes)
- Returns complete employee object with agency and division names
- Used by AutoEntityCreator to prevent duplicate employees

**Query:**
```sql
SELECT e.*,
       c.name as agency_name,
       b.name as division_name
FROM wp_app_agency_employees e
LEFT JOIN wp_app_agencies c ON e.agency_id = c.id
LEFT JOIN wp_app_agency_divisions b ON e.division_id = b.id
WHERE e.user_id = %d AND e.division_id = %d
```

---

### 5. Hook Registration

**Location:** `/wp-agency/wp-agency.php:134-137`

```php
// Task-2066: Register AutoEntityCreator hooks for automatic entity creation
$auto_entity_creator = new \WPAgency\Handlers\AutoEntityCreator();
add_action('wp_agency_agency_created', [$auto_entity_creator, 'handleAgencyCreated'], 10, 2);
add_action('wp_agency_division_created', [$auto_entity_creator, 'handleDivisionCreated'], 10, 2);
```

**Note:** Fixed hook name from `wp_agency_created` to `wp_agency_agency_created` following naming convention

**Priority:** 10 (default)
**Parameters:** 2 (id and data array)

---

## Files Modified/Created

### Created Files (1)
1. ✅ `/src/Handlers/AutoEntityCreator.php` - Main handler class

### Modified Files (5)

1. ✅ `/src/Models/Agency/AgencyModel.php` (v2.0.0 → v2.1.0)
   - Added `wp_agency_created` hook after insert
   - Updated changelog

2. ✅ `/src/Models/Division/DivisionModel.php` (v1.0.0 → v1.1.0)
   - Added `wp_agency_division_created` hook after insert
   - Updated changelog

3. ✅ `/src/Models/Employee/AgencyEmployeeModel.php` (v1.0.0 → v1.1.0)
   - Added `findByUserAndDivision()` method
   - Updated changelog

4. ✅ `/wp-agency.php` (v1.0.0 → v1.1.0)
   - Registered AutoEntityCreator hooks
   - Updated plugin version
   - Updated plugin changelog

5. ✅ `/TODO/TODO-2066-auto-entity-creation.md` - This documentation

---

## Hook Flow Diagram

```
┌─────────────────────┐
│   Agency Created    │
│  (AgencyModel)      │
└──────────┬──────────┘
           │
           │ do_action('wp_agency_created', $id, $data)
           ▼
┌─────────────────────┐
│  AutoEntityCreator  │
│ handleAgencyCreated │
└──────────┬──────────┘
           │
           │ Check: pusat exists?
           │ No → Create
           ▼
┌─────────────────────┐
│ Division Pusat      │
│   Created           │
│ (DivisionModel)     │
└──────────┬──────────┘
           │
           │ do_action('wp_agency_division_created', $id, $data)
           ▼
┌─────────────────────┐
│  AutoEntityCreator  │
│handleDivisionCreated│
└──────────┬──────────┘
           │
           │ Check: employee exists?
           │ No → Create
           ▼
┌─────────────────────┐
│  Employee Created   │
│ (EmployeeModel)     │
└─────────────────────┘
```

---

## Testing Scenarios

### Scenario 1: New Agency Self-Registration

**Steps:**
1. User registers new agency via registration form
2. Agency created with `reg_type='self'`, `status='active'`

**Expected Results:**
- ✅ Agency record created
- ✅ Hook `wp_agency_created` fires
- ✅ Division pusat auto-created with name "{agency_name} - Pusat"
- ✅ Division type = 'pusat', status = 'active'
- ✅ Division inherits provinsi_code and regency_code from agency
- ✅ Hook `wp_agency_division_created` fires
- ✅ Employee auto-created for the user
- ✅ Employee has position 'Admin', status 'active'
- ✅ Employee department fields (finance, operation, legal, purchase) = 0
- ✅ All error logs show successful creation

**Logs to Check:**
```
[AutoEntityCreator] handleAgencyCreated triggered for agency ID: {id}
[AutoEntityCreator] Creating division pusat with data: ...
[AutoEntityCreator] Successfully created division pusat ID: {id} for agency {agency_id}
[AutoEntityCreator] handleDivisionCreated triggered for division ID: {id}
[AutoEntityCreator] Creating employee with data: ...
[AutoEntityCreator] Successfully created employee ID: {id} for division {division_id}
```

---

### Scenario 2: Admin Creates Agency

**Steps:**
1. Admin creates new agency via admin panel
2. Agency created with `reg_type='by_admin'`

**Expected Results:**
- ✅ Agency record created
- ✅ Division pusat auto-created
- ✅ Employee auto-created if user_id is present

---

### Scenario 3: Agency Already Has Division Pusat

**Steps:**
1. Try to create agency with same name/region (duplicate)
2. OR manually trigger hook for existing agency

**Expected Results:**
- ✅ AutoEntityCreator checks for existing division pusat
- ✅ Skips creation if pusat already exists
- ✅ Logs: "Division pusat already exists for agency {id}"
- ✅ No duplicate division created

---

### Scenario 4: Division Created Without user_id

**Steps:**
1. Create division manually without user_id

**Expected Results:**
- ✅ Division created successfully
- ✅ Hook fires but employee creation skipped
- ✅ Logs: "No user_id in division data, skipping employee creation"

---

### Scenario 5: Employee Already Exists

**Steps:**
1. Create division for user who already has employee record

**Expected Results:**
- ✅ AutoEntityCreator checks for existing employee
- ✅ Skips creation if employee already exists
- ✅ Logs: "Employee already exists for user {user_id} in division {division_id}"
- ✅ No duplicate employee created

---

## Comparison with wp-customer

### Similarities ✅

| Aspect | wp-customer | wp-agency | Match |
|--------|-------------|-----------|-------|
| Hook pattern | `wp_customer_created` | `wp_agency_created` | ✅ |
| Handler class | `AutoEntityCreator` | `AutoEntityCreator` | ✅ |
| Auto-create flow | Customer → Branch → Employee | Agency → Division → Employee | ✅ |
| Duplicate prevention | ✅ Check before create | ✅ Check before create | ✅ |
| Error handling | try-catch + logging | try-catch + logging | ✅ |
| Default position | 'Admin' or 'Branch Manager' | 'Admin' | ✅ |
| Hook priority | 10 | 10 | ✅ |
| Hook parameters | (id, data) | (id, data) | ✅ |

### Differences ⚠️

| Aspect | wp-customer | wp-agency | Note |
|--------|-------------|-----------|------|
| Main entity | Customer | Agency | - |
| Sub entity | Branch | Division | - |
| Branch naming | "{name} Cabang {regency}" | "{name} - Pusat" | Simpler |
| Dept fields | All set to 1 | All set to 0 | Not used in wp-agency |
| Location IDs | agency_id, division_id, inspector_id | No such complex relations | Simpler structure |
| Phone default | '-' | '-' | Same |
| Keterangan | 'Auto-created from branch' | 'Auto-created by system' | Similar |

---

## Hook Documentation

### Creation Hooks

#### wp_agency_agency_created

**Fires:** After successful agency creation in database

**Location:** `AgencyModel::create()` line 259

**Parameters:**
1. `$agency_id` (int) - New agency ID
2. `$insert_data` (array) - Data used for insert

**Data Structure:**
```php
[
    'code' => string,
    'name' => string,
    'status' => 'active|inactive',
    'user_id' => int|null,
    'provinsi_code' => string|null,
    'regency_code' => string|null,
    'created_by' => int,
    'created_at' => datetime,
    'updated_at' => datetime
]
```

**Example Usage:**
```php
add_action('wp_agency_agency_created', function($agency_id, $data) {
    error_log("New agency created: ID {$agency_id}, Name: {$data['name']}");
}, 10, 2);
```

**Note:** Hook name follows convention `wp_{plugin}_{entity}_{action}` where entity is explicit

---

#### wp_agency_division_created

**Fires:** After successful division creation in database

**Location:** `DivisionModel::create()` line 209

**Parameters:**
1. `$division_id` (int) - New division ID
2. `$insert_data` (array) - Data used for insert

**Data Structure:**
```php
[
    'agency_id' => int,
    'code' => string,
    'name' => string,
    'type' => 'pusat|cabang',
    'nitku' => string|null,
    'postal_code' => string|null,
    'latitude' => float|null,
    'longitude' => float|null,
    'address' => string|null,
    'phone' => string|null,
    'email' => string|null,
    'provinsi_code' => string|null,
    'regency_code' => string|null,
    'user_id' => int|null,
    'created_by' => int,
    'created_at' => datetime,
    'updated_at' => datetime,
    'status' => 'active|inactive'
]
```

**Example Usage:**
```php
add_action('wp_agency_division_created', function($division_id, $data) {
    error_log("New division created: ID {$division_id}, Type: {$data['type']}");
}, 10, 2);
```

---

### Deletion Hooks

#### wp_agency_agency_before_delete

**Fires:** BEFORE agency deletion (for validation/prevention)

**Location:** `AgencyModel::delete()` line 506

**Parameters:**
1. `$agency_id` (int) - Agency ID to be deleted
2. `$agency_data` (array) - Complete agency data before deletion

**Use Cases:**
- Prevent deletion based on business rules
- Archive data to external system before deletion
- Send notifications
- Validate if safe to delete

**Example Usage:**
```php
add_action('wp_agency_agency_before_delete', function($agency_id, $data) {
    // Prevent deletion if agency has active divisions
    $division_count = get_division_count($agency_id);
    if ($division_count > 0) {
        wp_die('Cannot delete agency with active divisions');
    }
}, 10, 2);
```

---

#### wp_agency_agency_deleted

**Fires:** AFTER agency deletion (for cascade cleanup)

**Location:** `AgencyModel::delete()` line 542

**Parameters:**
1. `$agency_id` (int) - Deleted agency ID
2. `$agency_data` (array) - Agency data that was deleted
3. `$is_hard_delete` (bool) - true=hard delete, false=soft delete (status=inactive)

**Use Cases:**
- Cascade delete related entities
- Sync deletion to external systems
- Audit logging
- Cleanup external references

**Example Usage:**
```php
add_action('wp_agency_agency_deleted', function($agency_id, $data, $is_hard_delete) {
    error_log("Agency deleted: ID {$agency_id}, Hard: " . ($is_hard_delete ? 'YES' : 'NO'));

    // Sync to external CRM
    if ($is_hard_delete) {
        external_crm_delete_agency($agency_id);
    }
}, 10, 3);
```

---

#### wp_agency_division_before_delete

**Fires:** BEFORE division deletion (for validation/prevention)

**Location:** `DivisionModel::delete()` line 338

**Parameters:**
1. `$division_id` (int) - Division ID to be deleted
2. `$division_data` (array) - Complete division data before deletion

Same pattern as agency before_delete hook.

---

#### wp_agency_division_deleted

**Fires:** AFTER division deletion (for cascade cleanup)

**Location:** `DivisionModel::delete()` line 374

**Parameters:**
1. `$division_id` (int) - Deleted division ID
2. `$division_data` (array) - Division data that was deleted
3. `$is_hard_delete` (bool) - true=hard delete, false=soft delete

Same pattern as agency deleted hook.

---

## Benefits

### 1. Automation ✅
- No manual steps required after agency creation
- Consistent data structure across all agencies
- Reduces human error

### 2. Consistency ✅
- Every agency automatically gets division pusat
- Every division with user_id automatically gets employee
- Follows wp-customer pattern

### 3. Extensibility ✅
- Other plugins can hook into `wp_agency_created`
- Other plugins can hook into `wp_agency_division_created`
- Easy to add more auto-creation logic

### 4. Maintainability ✅
- Centralized logic in AutoEntityCreator
- Easy to debug with comprehensive logging
- Clear separation of concerns

---

## Error Handling

### Try-Catch Blocks
Both handler methods wrapped in try-catch:
```php
try {
    // Logic here
} catch (\Exception $e) {
    error_log("[AutoEntityCreator] Error: " . $e->getMessage());
    error_log("[AutoEntityCreator] Stack trace: " . $e->getTraceAsString());
}
```

### Validation Checks
1. ✅ Entity exists check
2. ✅ Duplicate prevention check
3. ✅ Required data presence check
4. ✅ User exists check (for employee)

### Graceful Degradation
- If auto-creation fails, main entity (agency/division) still created
- Errors logged but don't break the flow
- User can manually create missing entities if needed

---

## Logging Strategy

All logs prefixed with `[AutoEntityCreator]` for easy grep:

```bash
# View all AutoEntityCreator logs
grep "AutoEntityCreator" /path/to/debug.log

# View only errors
grep "AutoEntityCreator.*Error" /path/to/debug.log

# View specific agency creation
grep "AutoEntityCreator.*agency ID: 123" /path/to/debug.log
```

**Log Levels:**
- Info: Function triggered, creation success
- Error: Entity not found, creation failed, exceptions

---

## Cache Invalidation

AutoEntityCreator does NOT handle cache invalidation. Cache is handled by:
- `AgencyModel::create()` - Invalidates agency cache
- `DivisionModel::create()` - Invalidates division cache
- `AgencyEmployeeModel::create()` - Invalidates employee cache

This follows single responsibility principle.

---

## Future Enhancements

### Possible Extensions:
1. Hook for division update → update related employees
2. Hook for agency deletion → cleanup related entities
3. Hook for employee role assignment
4. Webhook notification to external systems
5. Email notification when entities auto-created

### Hook Naming Convention:
Follow pattern: `wp_agency_{entity}_{action}`

Examples:
- `wp_agency_updated`
- `wp_agency_deleted`
- `wp_agency_division_updated`
- `wp_agency_division_deleted`
- `wp_agency_employee_created`

---

## Troubleshooting

### Division Pusat Not Created

**Check:**
1. Is hook registered? → Check `wp-agency.php` line 127
2. Is AutoEntityCreator class loaded? → Check autoloader
3. Check error logs for exceptions
4. Verify agency created successfully
5. Check if pusat already exists

**Debug:**
```php
// Manually trigger
do_action('wp_agency_created', $agency_id, $agency_data);
```

---

### Employee Not Created

**Check:**
1. Is user_id present in division_data?
2. Does WordPress user exist?
3. Does employee already exist?
4. Check error logs for exceptions

**Debug:**
```php
// Check employee exists
global $wpdb;
$exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_employees
     WHERE user_id = %d AND division_id = %d",
    $user_id, $division_id
));
```

---

## Completion Summary

✅ **All Tasks Completed:**

**Creation Hooks:**
1. ✅ Added `wp_agency_agency_created` hook in AgencyModel::create()
2. ✅ Added `wp_agency_division_created` hook in DivisionModel::create()
3. ✅ Created AutoEntityCreator handler class
4. ✅ Implemented handleAgencyCreated() method
5. ✅ Implemented handleDivisionCreated() method
6. ✅ Added findByUserAndDivision() in AgencyEmployeeModel

**Deletion Hooks:**
7. ✅ Added `wp_agency_agency_before_delete` hook in AgencyModel::delete()
8. ✅ Added `wp_agency_agency_deleted` hook in AgencyModel::delete()
9. ✅ Added `wp_agency_division_before_delete` hook in DivisionModel::delete()
10. ✅ Added `wp_agency_division_deleted` hook in DivisionModel::delete()
11. ✅ Implemented soft delete support (status='inactive')
12. ✅ Implemented hard delete option (via settings)

**Integration:**
13. ✅ Registered creation hooks in main plugin file
14. ✅ Fixed hook naming convention (wp_agency_created → wp_agency_agency_created)
15. ✅ Updated plugin version to 1.1.0
16. ✅ Updated all changelogs
17. ✅ Created comprehensive documentation

**Summary of Changes:**
- **1 file created** (AutoEntityCreator.php)
- **4 files modified** (AgencyModel, DivisionModel, AgencyEmployeeModel, wp-agency.php)
- **1 documentation updated** (TODO-2066-auto-entity-creation.md)

**Total: 6 files changed**

**Hook Summary:**
- **2 Creation hooks** (agency_created, division_created)
- **4 Deletion hooks** (2x before_delete, 2x deleted)
- **Total: 6 lifecycle hooks** implemented

---

## Related Tasks

- **TODO-2065**: Form synchronization (completed)
- **TODO-2165** (wp-customer): Original auto entity creation implementation
- **TODO-2169** (wp-customer): Hook documentation planning

---

## Notes

1. **Naming Convention:** Hook names follow `wp_{plugin}_{entity}_{action}` pattern
   - Entity name ALWAYS explicit (wp_agency_**agency**_created)
   - Consistent with wp-customer TODO-2169 naming standard

2. **Soft Delete:** Default behavior is soft delete (status='inactive')
   - Data remains in database for recovery
   - Hard delete enabled via `wp_agency_general_options['enable_hard_delete']`

3. **Department Fields:** `finance`, `operation`, `legal`, `purchase` set to 0
   - Not actively used in wp-agency UI (unlike wp-customer)

4. **Division Naming:** Simpler than wp-customer
   - wp-agency: "{name} - Pusat"
   - wp-customer: "{name} Cabang {regency}"

5. **Employee Position:** Always 'Admin' for auto-created employees

6. **Logging:** All error logs use consistent [AutoEntityCreator] prefix

7. **Delete Hooks:** Enable cascade cleanup and external integrations
   - before_delete: For validation/prevention
   - deleted: For cascade cleanup (receives $is_hard_delete parameter)
