# TODO-2067: Agency Generator Runtime Flow Migration

## Status: 🚧 IN PROGRESS

## Date: 2025-01-22

## Priority: HIGH

## Context

Migrate demo data generation from **bulk generation approach** to **runtime flow pattern** following wp-customer Tasks 2167-2168. This transforms demo generator from a simple data creation tool into an **automated testing tool** for production code.

**Reference Tasks:**
- `/wp-customer/TODO/TODO-2168-runtime-flow-customer-generator.md`
- `/wp-customer/TODO/TODO-2167-branch-generator-runtime-flow.md`
- `/wp-customer/TODO/TODO-2165-auto-entity-creation.md`

**Related Task:**
- Task-2066: HOOK system for auto entity creation (COMPLETED)

---

## Problem Statement

### Current Implementation Issues

**1. Production Code Pollution:**
```php
// ❌ Demo method in production Controller (lines 997-1022)
AgencyController::createDemoAgency(array $data): bool {
    $created = $this->model->createDemoData($data);  // Special demo method
    // ...
}
```

**2. Validation Bypass:**
- No `AgencyValidator::validateForm()` call
- No permission checks
- No business rule validation
- Demo data bypasses all production validation logic

**3. HOOK System Untested:**
```php
// Hook exists from Task-2066 but demo doesn't trigger it
do_action('wp_agency_agency_created', $new_id, $insert_data);

// Result:
// - Division pusat auto-creation NOT tested
// - Employee auto-creation NOT tested
// - Hook chain reliability unknown
```

**4. Manual User Creation:**
```php
// ❌ Direct DB INSERT instead of wp_insert_user()
$wpdb->insert($wpdb->users, [
    'ID' => $data['id'],  // Force fixed ID
    'user_login' => $username,
    'user_pass' => wp_hash_password('Demo_Data-2025'),
    // ...
]);

// Problems:
// - Skips WordPress hooks and validation
// - Manual role serialization error-prone
// - Not realistic for testing
```

---

## Solution: Runtime Flow Pattern

### Paradigm Shift

```
❌ OLD: Generate = Bulk data creation tool (bypass validation)
✅ NEW: Generate = Automated form submission testing (full validation)
```

### Target Architecture

```
AgencyDemoData::generate()
  → 1. Create WP User via wp_insert_user()
  → 2. Update user ID to static value (FOREIGN_KEY_CHECKS=0)
  → 3. Validate data via AgencyValidator::validateForm()
  → 4. Create agency via AgencyModel::create()
    → HOOK: wp_agency_agency_created fires
      → AutoEntityCreator::handleAgencyCreated()
        → Create division pusat
        → HOOK: wp_agency_division_created fires
          → AutoEntityCreator::handleDivisionCreated()
            → Create employee with position='Admin'
```

**Benefits:**
- ✅ Tests all production validation logic
- ✅ Tests complete HOOK chain (agency → division → employee)
- ✅ Zero production code pollution
- ✅ Consistent with wp-customer pattern
- ✅ Better maintainability

---

## Implementation Plan

### Phase 1: Agency Runtime Flow (THIS TASK)

**Scope Decision (from Diskusi-01):**
1. ✅ **Scope**: Agency only (Phase 1)
2. ✅ **Fixed IDs**: STATIC - use wp_insert_user → update ID
3. ✅ **Hook Testing**: Test full chain (agency → division → employee)
4. ✅ **Production Cleanup**: DELETE createDemoAgency() from production
5. ✅ **Approach**: Runtime flow with HOOK system

---

## Files to Modify

### 1. Production Code Cleanup (CRITICAL)

#### A. Delete Demo Method from AgencyController
**File**: `/wp-agency/src/Controllers/AgencyController.php`
**Action**: DELETE lines 997-1022

```php
// ❌ DELETE THIS METHOD (production pollution)
public function createDemoAgency(array $data): bool {
    try {
        $created = $this->model->createDemoData($data);

        if (!$created) {
            throw new \Exception('Failed to create demo agency');
        }

        $access = $this->validator->validateAccess(0);

        $this->cache->invalidateAgencyCache($data['id']);
        $this->cache->delete('agency_total_count', $access['access_type']);
        $this->cache->invalidateDataTableCache('agency_list');

        return true;

    } catch (\Exception $e) {
        $this->debug_log('Error creating demo agency: ' . $e->getMessage());
        throw $e;
    }
}
```

**Result**: Zero demo code in production Controller

---

#### B. Check if AgencyModel::createDemoData() Exists

**File**: `/wp-agency/src/Models/Agency/AgencyModel.php`
**Action**: Search for `createDemoData()` method

If found:
```php
// ❌ DELETE THIS METHOD if it exists
public function createDemoData(array $data): bool {
    // Force INSERT with fixed ID
    // Bypass validation
    // Skip hooks
}
```

**Result**: Model only has standard `create()` method

---

### 2. Update WPUserGenerator Pattern

**File**: `/wp-agency/src/Database/Demo/WPUserGenerator.php`
**Current Version**: Unknown
**Target Version**: 2.0.0

**Changes:**

#### A. Update generateUser() Method

```php
/**
 * Generate user via wp_insert_user() then update ID to static value
 *
 * Following wp-customer pattern for proper WordPress integration
 *
 * @param array $data User data with fixed ID
 * @return int User ID
 * @throws \Exception On user creation failure
 */
public function generateUser(array $data): int {
    global $wpdb;

    // 1. Create user via wp_insert_user() for proper WordPress integration
    $user_id = wp_insert_user([
        'user_login' => $data['username'],
        'user_pass' => 'Demo_Data-2025',  // Will be hashed by wp_insert_user()
        'user_email' => $data['username'] . '@example.com',
        'display_name' => $data['display_name'],
        'role' => 'agency'  // Base role
    ]);

    if (is_wp_error($user_id)) {
        throw new \Exception('Failed to create user: ' . $user_id->get_error_message());
    }

    // 2. Add additional roles (agency pattern: base + admin role)
    $user = get_user_by('ID', $user_id);
    if ($user && isset($data['roles'])) {
        foreach ($data['roles'] as $role) {
            if ($role !== 'agency') {  // Skip base role already set
                $user->add_role($role);
            }
        }
    }

    // 3. Update user ID to match static data (for consistency across environments)
    if (isset($data['id']) && $data['id'] !== $user_id) {
        // Disable foreign key checks temporarily
        $wpdb->query('SET FOREIGN_KEY_CHECKS=0');

        // Update user ID in wp_users table
        $wpdb->update(
            $wpdb->users,
            ['ID' => $data['id']],
            ['ID' => $user_id],
            ['%d'],
            ['%d']
        );

        // Update user ID in wp_usermeta table
        $wpdb->update(
            $wpdb->usermeta,
            ['user_id' => $data['id']],
            ['user_id' => $user_id],
            ['%d'],
            ['%d']
        );

        // Re-enable foreign key checks
        $wpdb->query('SET FOREIGN_KEY_CHECKS=1');

        return $data['id'];
    }

    return $user_id;
}
```

**Benefits:**
- ✅ Uses `wp_insert_user()` for proper WordPress integration
- ✅ Tests WordPress user creation hooks
- ✅ Maintains static IDs for consistency
- ✅ Handles dual-role pattern correctly

---

#### B. Update deleteUsers() Method

```php
/**
 * Delete users and cleanup related data
 *
 * @param array $user_ids Array of user IDs to delete
 * @return void
 */
public function deleteUsers(array $user_ids): void {
    require_once(ABSPATH . 'wp-admin/includes/user.php');

    foreach ($user_ids as $user_id) {
        if (get_user_by('ID', $user_id)) {
            // Use wp_delete_user() for proper WordPress cleanup
            wp_delete_user($user_id);
        }
    }
}
```

---

### 3. Create Runtime Flow Method in AgencyDemoData

**File**: `/wp-agency/src/Database/Demo/AgencyDemoData.php`
**Current Version**: Unknown
**Target Version**: 2.0.0

**Changes:**

#### A. Add Dependencies to Constructor

```php
private $agencyValidator;
private $agencyModel;

public function __construct() {
    parent::__construct();
    $this->agency_users = AgencyUsersData::$data;

    // Add production dependencies
    $this->agencyValidator = new \WPAgency\Validators\AgencyValidator();
    $this->agencyModel = new \WPAgency\Models\Agency\AgencyModel();
}
```

---

#### B. Create Runtime Flow Method

```php
/**
 * Create agency via runtime flow (simulating real production flow)
 *
 * This method replicates the exact flow that happens in production:
 * 1. Validate data via AgencyValidator::validateForm()
 * 2. Create agency via AgencyModel::create()
 * 3. Fire wp_agency_agency_created HOOK (auto-creates division pusat + employee)
 * 4. Cache invalidation (handled by Model)
 *
 * NO special demo methods, NO validation bypass, NO production pollution
 *
 * @param array $agency_data Agency data
 * @return int|null Agency ID or null on failure
 * @throws \Exception On validation or creation error
 */
private function createAgencyViaRuntimeFlow(array $agency_data): ?int {
    // 1. Validate data using production validator
    $validation_errors = $this->agencyValidator->validateForm($agency_data);

    if (!empty($validation_errors)) {
        $error_msg = 'Validation failed: ' . implode(', ', $validation_errors);
        $this->debug($error_msg);
        throw new \Exception($error_msg);
    }

    // 2. Create agency using production Model::create()
    // This triggers wp_agency_agency_created HOOK automatically
    $agency_id = $this->agencyModel->create($agency_data);

    if (!$agency_id) {
        throw new \Exception('Failed to create agency via Model');
    }

    $this->debug("✓ Agency created: ID={$agency_id}, Name={$agency_data['name']}");

    // 3. Cache invalidation handled automatically by Model
    // 4. HOOK fired automatically by Model
    //    → AutoEntityCreator::handleAgencyCreated()
    //      → Creates division pusat
    //        → HOOK: wp_agency_division_created
    //          → Creates employee

    return $agency_id;
}
```

---

#### C. Update generate() Method

```php
/**
 * Generate demo agencies via runtime flow
 *
 * Tests full production flow including:
 * - User creation via wp_insert_user()
 * - Form validation via AgencyValidator
 * - Agency creation via AgencyModel
 * - HOOK cascade (agency → division → employee)
 */
protected function generate(): void {
    if (!$this->isDevelopmentMode()) {
        $this->debug('Not in development mode, skipping generation');
        return;
    }

    // PHASE 1: Cleanup existing demo data
    if ($this->shouldClearData()) {
        $this->cleanupDemoData();
    }

    // PHASE 2: Generate agencies via runtime flow
    $this->generateAgenciesViaRuntimeFlow();
}

/**
 * Cleanup demo data using HOOK-based cascade delete
 */
private function cleanupDemoData(): void {
    global $wpdb;

    $this->debug('Starting cleanup of existing demo data...');

    // 1. Enable hard delete temporarily (for complete cleanup)
    $original_settings = get_option('wp_agency_general_options', []);
    $cleanup_settings = array_merge($original_settings, [
        'enable_hard_delete' => true
    ]);
    update_option('wp_agency_general_options', $cleanup_settings);

    // 2. Get all demo agencies (reg_type = 'generate')
    $demo_agencies = $wpdb->get_col(
        "SELECT id FROM {$wpdb->prefix}app_agencies
         WHERE reg_type = 'generate'
         ORDER BY id ASC"
    );

    if (!empty($demo_agencies)) {
        $this->debug('Found ' . count($demo_agencies) . ' demo agencies to delete');

        // 3. Delete via Model (triggers HOOK cascade)
        foreach ($demo_agencies as $agency_id) {
            try {
                $this->agencyModel->delete($agency_id);
                // → Triggers wp_agency_agency_deleted hook
                //   → Cascade deletes divisions
                //     → Cascade deletes employees

                $this->debug("✓ Deleted agency ID={$agency_id} (with cascade)");
            } catch (\Exception $e) {
                $this->debug("✗ Failed to delete agency ID={$agency_id}: " . $e->getMessage());
            }
        }
    }

    // 4. Clean demo users
    $user_ids = array_column($this->agency_users, 'id');
    $userGenerator = new WPUserGenerator();
    $userGenerator->deleteUsers($user_ids);
    $this->debug('✓ Cleaned ' . count($user_ids) . ' demo users');

    // 5. Restore original settings
    update_option('wp_agency_general_options', $original_settings);

    $this->debug('✓ Cleanup completed');
}

/**
 * Generate agencies via runtime flow
 */
private function generateAgenciesViaRuntimeFlow(): void {
    $this->debug('Starting agency generation via runtime flow...');

    $userGenerator = new WPUserGenerator();
    $generated_count = 0;
    $failed_count = 0;

    foreach (self::$agencies as $index => $agency) {
        try {
            // 1. Get user data for this agency
            $user_data = $this->agency_users[$index] ?? null;
            if (!$user_data) {
                throw new \Exception("User data not found for agency index {$index}");
            }

            // 2. Create WP User via wp_insert_user() (with static ID)
            $user_id = $userGenerator->generateUser([
                'id' => $user_data['id'],
                'username' => $user_data['username'],
                'display_name' => $user_data['display_name'],
                'roles' => $user_data['roles']
            ]);

            $this->debug("✓ User created: ID={$user_id}, Username={$user_data['username']}");

            // 3. Get location codes
            $province_name = $agency['province'];
            $provinsi_id = $this->getProvinceIdByName($province_name);

            if (!$provinsi_id) {
                throw new \Exception("Province not found: {$province_name}");
            }

            $provinsi_code = $this->getProvinceCodeById($provinsi_id);
            $regency_code = $this->getRandomRegencyCode($provinsi_id);

            // 4. Prepare agency data (NO fixed ID, let Model auto-generate)
            $agency_data = [
                'name' => $agency['name'],
                'status' => 'active',
                'provinsi_code' => $provinsi_code,
                'regency_code' => $regency_code,
                'user_id' => $user_id,
                'reg_type' => 'generate',  // Mark as demo data
                'created_by' => $user_id
            ];

            // 5. Create agency via runtime flow
            // This tests:
            // ✓ AgencyValidator::validateForm()
            // ✓ AgencyModel::create()
            // ✓ HOOK: wp_agency_agency_created
            //   ✓ AutoEntityCreator creates division pusat
            //     ✓ HOOK: wp_agency_division_created
            //       ✓ AutoEntityCreator creates employee
            $agency_id = $this->createAgencyViaRuntimeFlow($agency_data);

            $generated_count++;
            $this->debug("✓ Agency #{$generated_count} created successfully");

        } catch (\Exception $e) {
            $failed_count++;
            $this->debug("✗ Failed to create agency: " . $e->getMessage());

            // Continue with next agency instead of stopping
            continue;
        }
    }

    $this->debug("Generation completed: {$generated_count} succeeded, {$failed_count} failed");
}
```

---

### 4. Update AgencyValidator (if needed)

**File**: `/wp-agency/src/Validators/AgencyValidator.php`
**Action**: Verify validateForm() method exists and handles all required fields

**Required validations:**
- `name` (required, max 100 chars, unique check)
- `status` (required, enum: 'active', 'inactive')
- `provinsi_code` (required if regency_code provided)
- `regency_code` (required if provinsi_code provided)
- `user_id` (optional, must be valid user)
- `reg_type` (optional, enum: 'self', 'by_admin', 'generate')

If method doesn't exist or is incomplete, need to implement it.

---

## Testing Plan

### Test Cases

#### 1. User Creation Test
```php
// Verify wp_insert_user() works
$user_id = $userGenerator->generateUser([
    'id' => 130,
    'username' => 'disnaker_jabar',
    'display_name' => 'Disnaker Jawa Barat',
    'roles' => ['agency', 'agency_admin_dinas']
]);

// Verify:
// ✓ User exists in wp_users
// ✓ Both roles assigned correctly
// ✓ ID updated to 130
```

#### 2. Validation Test
```php
// Test invalid data rejected
$invalid_data = [
    'name' => '',  // Empty name should fail
    'status' => 'invalid',  // Invalid enum
];

$errors = $agencyValidator->validateForm($invalid_data);
// Verify: $errors is not empty
```

#### 3. Agency Creation Test
```php
// Valid data should create agency
$agency_id = $agencyModel->create($valid_data);
// Verify: Returns numeric ID
// Verify: Record exists in database
// Verify: reg_type = 'generate'
```

#### 4. HOOK Cascade Test (CRITICAL)
```php
// After agency created, verify cascade:
$agency_id = $this->createAgencyViaRuntimeFlow($agency_data);

// Verify division pusat created
$divisions = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}app_agency_divisions
     WHERE agency_id = {$agency_id} AND type = 'pusat'"
);
// Assert: count($divisions) === 1

// Verify employee created
$employees = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}app_agency_employees
     WHERE division_id = {$divisions[0]->id} AND position = 'Admin'"
);
// Assert: count($employees) === 1
```

#### 5. Cleanup Test
```php
// Delete agency should cascade
$this->agencyModel->delete($agency_id);

// Verify divisions deleted
// Verify employees deleted
// Verify cache cleared
```

---

## Success Criteria

### Must Have:
- ✅ Zero demo code in production namespace (Controller/Model)
- ✅ User creation via `wp_insert_user()` with static ID
- ✅ Full validation via `AgencyValidator::validateForm()`
- ✅ Agency creation via `AgencyModel::create()`
- ✅ HOOK cascade tested (agency → division → employee)
- ✅ Cleanup via Model with cascade delete

### Nice to Have:
- ✅ Comprehensive error handling and logging
- ✅ Success/failure counters
- ✅ Detailed debug output

---

## Migration Checklist

### Step 1: Production Code Cleanup
- [ ] Delete `AgencyController::createDemoAgency()` method (lines 997-1022)
- [ ] Search and delete `AgencyModel::createDemoData()` if exists
- [ ] Verify no other production code references demo methods
- [ ] Update AgencyController version (1.0.2 → 1.0.3)

### Step 2: Update WPUserGenerator
- [ ] Update `generateUser()` to use `wp_insert_user()`
- [ ] Add static ID update logic (FOREIGN_KEY_CHECKS=0)
- [ ] Update `deleteUsers()` to use `wp_delete_user()`
- [ ] Update version (current → 2.0.0)
- [ ] Add changelog

### Step 3: Update AgencyDemoData
- [ ] Add AgencyValidator and AgencyModel dependencies
- [ ] Create `createAgencyViaRuntimeFlow()` method
- [ ] Create `cleanupDemoData()` method
- [ ] Create `generateAgenciesViaRuntimeFlow()` method
- [ ] Update `generate()` method
- [ ] Update version (current → 2.0.0)
- [ ] Add comprehensive logging

### Step 4: Verify AgencyValidator
- [ ] Check `validateForm()` method exists
- [ ] Verify all required field validations
- [ ] Test with valid and invalid data

### Step 5: Testing
- [ ] Test user creation with static ID
- [ ] Test validation rejection of invalid data
- [ ] Test agency creation via Model
- [ ] Test HOOK cascade (agency → division → employee)
- [ ] Test cleanup with cascade delete
- [ ] Run full generate cycle

### Step 6: Documentation
- [ ] Update this TODO with test results
- [ ] Update main TODO.md with Task-2067 completion
- [ ] Add comments to new methods
- [ ] Document any issues or gotchas

---

## Rollback Plan

If migration fails:

1. **Restore deleted methods:**
   - Re-add `AgencyController::createDemoAgency()`
   - Keep `createDemoData()` if it was deleted

2. **Revert WPUserGenerator:**
   - Restore direct DB INSERT approach
   - Remove wp_insert_user() logic

3. **Revert AgencyDemoData:**
   - Remove runtime flow methods
   - Restore old generate() logic

4. **Document failures:**
   - What went wrong
   - What needs fixing
   - Next steps

---

## Related Tasks

### Completed:
- ✅ Task-2066: HOOK system (wp_agency_agency_created, wp_agency_division_created)
- ✅ Task-2065: Form sync (shared component, registration fields)

### Future:
- ⏳ Task-2XXX: Division generator runtime flow (Phase 2)
- ⏳ Task-2XXX: Employee generator runtime flow (Phase 3)
- ⏳ Task-2XXX: Delete HOOK cascade cleanup handlers

---

## Notes

1. **IMPORTANT: Keep Static IDs**
   - User explicitly requested: "SEKARANG MENGGUNAKAN STATIC!!!! SAYA TULIS AGAR ANDA TIDAK SEMBARANGAN MENGGUNAKAN AUTO INCREMENT"
   - Use wp_insert_user() → update ID pattern (like wp-customer)

2. **HOOK Chain is Critical**
   - This is the main benefit of runtime flow
   - Must verify full cascade: agency → division → employee
   - Any HOOK failures indicate production bugs

3. **Zero Production Pollution**
   - NO demo methods in Controller
   - NO demo methods in Model
   - Keep ALL demo logic in Database/Demo namespace

4. **Consistent with wp-customer**
   - Follow exact same pattern
   - Reference TODO-2168 for customer example
   - Reference TODO-2167 for branch example

---

## Questions / Issues

*Document any questions or issues that arise during implementation*

---

## Completion Date

Target: 2025-01-22
Actual: TBD
