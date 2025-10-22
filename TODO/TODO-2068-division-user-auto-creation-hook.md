# TODO-2068: Division User Auto-Creation via Hook

## Status
- **Created**: 2025-01-22
- **Status**: Planning
- **Priority**: High
- **Related**: TODO-2067 (Agency Generator Runtime Flow)

## Problem

**Current Flow (DivisionController):**
```
1. Controller creates user FIRST (lines 862-887)
2. Controller creates division WITH user_id
3. HOOK wp_agency_division_created fires
4. Hook creates employee (uses existing user_id)
```

**Inconsistency:**
- **Agency**: User created BEFORE controller → Controller creates agency → HOOK creates division + employee
- **Division**: **Controller creates user** → Controller creates division → HOOK creates employee
- **Employee**: HOOK creates employee (using user_id from division)

**Production Code Pollution:**
- User creation logic is in Controller (lines 862-887)
- Should be in Hook handler for consistency

## Target

Move user creation from Controller to Hook handler, making it consistent with agency pattern.

**New Flow:**
```
1. Controller validates admin data (username, email, firstname, lastname)
2. Controller creates division WITHOUT user (temp user_id = null or agency user)
3. HOOK wp_agency_division_created fires
4. Hook handler:
   - Creates user if admin data provided
   - Updates division.user_id
   - Creates employee with new user_id
```

## Design Questions

### Q1: Hook Timing - Before or After?

**Option A: Use existing wp_agency_division_created (after creation)**
- Pros: No new hook, uses existing infrastructure
- Cons: Division already created, need UPDATE query

**Option B: New hook wp_agency_division_before_created**
- Pros: User created before division, no UPDATE needed
- Cons: New hook, need to modify Model::create()

**Recommendation**: Option A (use existing hook, simpler)

### Q2: Admin Data Passing

How to pass admin data to hook handler?

**Option A: Via $division_data parameter**
```php
$division_data = [
    'agency_id' => $agency_id,
    'name' => 'Unit ABC',
    'type' => 'unit',
    'admin_username' => 'admin_abc',  // ← Admin data
    'admin_email' => 'admin@example.com',
    'admin_firstname' => 'Admin',
    'admin_lastname' => 'ABC'
];

$division_id = $this->model->create($division_data);
// → HOOK receives admin_* fields
```

**Option B: Via separate parameter**
```php
do_action('wp_agency_division_created', $division_id, $division_data, $admin_data);
```

**Recommendation**: Option A (keep hook signature simple)

### Q3: Temporary user_id

What user_id to use before hook creates real user?

**Option A: null**
- Division created with user_id = NULL
- Hook creates user, updates division

**Option B: Agency user_id (inherited)**
- Division created with agency's user_id temporarily
- Hook creates new user, updates division

**Recommendation**: Option B (safer, always has valid user_id)

### Q4: Backward Compatibility

What if admin fields NOT provided (existing flow)?

**Option A: Keep old logic in Controller (dual path)**
- If admin fields provided → Controller creates user (old way)
- If no admin fields → Hook skip user creation

**Option B: Always use hook (single path)**
- Controller never creates user
- Hook checks admin fields and creates if provided

**Recommendation**: Option B (clean, single responsibility)

## Implementation Plan

### Phase 1: Update AutoEntityCreator Hook Handler

**File**: `/src/Handlers/AutoEntityCreator.php`

**Method**: `handleDivisionCreated()`

**Changes**:
1. Check if admin data in $division_data (admin_username, admin_email, etc.)
2. If provided:
   - Create user via wp_insert_user()
   - Add dual role (agency + agency_admin_unit)
   - Update division.user_id
   - Create employee with new user_id
3. If NOT provided:
   - Use division.user_id (inherited from agency)
   - Create employee as before

**Code**:
```php
public function handleDivisionCreated(int $division_id, array $division_data): void {
    // 1. Check if admin data provided
    $has_admin_data = !empty($division_data['admin_username']) && !empty($division_data['admin_email']);

    $user_id = null;

    if ($has_admin_data) {
        // 2. Create user via hook
        $user_id = $this->createDivisionUser($division_data);

        // 3. Update division.user_id
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'app_agency_divisions',
            ['user_id' => $user_id],
            ['id' => $division_id],
            ['%d'],
            ['%d']
        );

        error_log("[AutoEntityCreator] Created user {$user_id} for division {$division_id}");
    } else {
        // Use existing user_id from division data
        $user_id = $division_data['user_id'] ?? null;
    }

    // 4. Create employee as before
    // ...existing employee creation logic...
}

private function createDivisionUser(array $division_data): int {
    $user_data = [
        'user_login' => sanitize_user($division_data['admin_username']),
        'user_email' => sanitize_email($division_data['admin_email']),
        'first_name' => sanitize_text_field($division_data['admin_firstname'] ?? ''),
        'last_name' => sanitize_text_field($division_data['admin_lastname'] ?? ''),
        'user_pass' => wp_generate_password(),
        'role' => 'agency'
    ];

    $user_id = wp_insert_user($user_data);

    if (is_wp_error($user_id)) {
        throw new \Exception($user_id->get_error_message());
    }

    // Add dual role
    $user = get_user_by('ID', $user_id);
    if ($user) {
        $user->add_role('agency_admin_unit');
    }

    // Send notification
    wp_new_user_notification($user_id, null, 'user');

    return $user_id;
}
```

### Phase 2: Simplify DivisionController

**File**: `/src/Controllers/Division/DivisionController.php`

**Method**: `store()`

**Changes**:
1. Remove user creation logic (lines 862-887)
2. Pass admin data to Model::create() (will reach hook via $division_data)
3. Let hook handle user creation

**Before** (lines 862-890):
```php
// Buat user untuk admin division jika data admin diisi
if (!empty($_POST['admin_email'])) {
    $user_data = [
        'user_login' => sanitize_user($_POST['admin_username']),
        'user_email' => sanitize_email($_POST['admin_email']),
        // ...
    ];

    $user_id = wp_insert_user($user_data);
    // ...
    $data['user_id'] = $user_id;
}

// Simpan division
$division_id = $this->model->create($data);
```

**After**:
```php
// Pass admin data to hook (don't create user here)
if (!empty($_POST['admin_email'])) {
    $data['admin_username'] = sanitize_user($_POST['admin_username']);
    $data['admin_email'] = sanitize_email($_POST['admin_email']);
    $data['admin_firstname'] = sanitize_text_field($_POST['admin_firstname']);
    $data['admin_lastname'] = sanitize_text_field($_POST['admin_lastname'] ?? '');

    // Use agency user temporarily (hook will create new user)
    $data['user_id'] = $agency->user_id;
}

// Simpan division (hook will create user + employee)
$division_id = $this->model->create($data);
```

### Phase 3: Update DivisionModel (Optional)

**File**: `/src/Models/Division/DivisionModel.php`

**Method**: `create()`

**Changes** (if needed):
- Ensure admin_* fields passed to hook (don't filter them out)
- Current code should already pass full $data array to hook

### Phase 4: Testing

1. Test dengan admin data (admin_username, admin_email provided)
   - ✓ User auto-created via hook
   - ✓ Division.user_id updated
   - ✓ Employee created with new user_id
   - ✓ Dual role assigned

2. Test tanpa admin data (inherited from agency)
   - ✓ No user creation
   - ✓ Division uses agency.user_id
   - ✓ Employee created with agency user_id

3. Test error handling
   - Username already exists
   - Invalid email
   - User creation fails

## Files to Modify

1. `/src/Handlers/AutoEntityCreator.php` (v2.1.0 → v2.2.0)
   - Add createDivisionUser() method
   - Update handleDivisionCreated() logic

2. `/src/Controllers/Division/DivisionController.php` (v2.1.0 → v2.?.?)
   - Remove user creation logic (lines 862-887)
   - Pass admin_* fields to Model::create()

## Testing Checklist

- [ ] Division dengan admin data (via form)
- [ ] Division tanpa admin data (inherited)
- [ ] Division via demo generator
- [ ] Error: duplicate username
- [ ] Error: invalid email
- [ ] Rollback on failure

## References

- TODO-2067: Agency Generator Runtime Flow
- TODO-2066: Auto Entity Creation Hooks
- Hook naming: `wp_agency_division_created` (entity ALWAYS explicit)
- Pattern: wp-customer TODO-2168, TODO-2170

## Questions for User

1. **Backward compatibility**: Hapus logic lama di Controller completely? Atau keep dual path?
   - Option A: Hapus completely (clean, hook-only)
   - Option B: Keep dual path (safer, gradual migration)

2. **Temporary user_id**: Use NULL atau agency.user_id sebelum hook creates user?
   - Option A: NULL (need to handle nullable FK)
   - Option B: agency.user_id (safer, always valid)

3. **Hook timing**: Tetap pakai `wp_agency_division_created` atau buat hook baru?
   - Option A: Use existing hook (simpler)
   - Option B: New hook `wp_agency_division_before_created` (cleaner but more changes)
