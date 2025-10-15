# TODO-2060: Add Default Role to Division Users

## Status
✅ COMPLETED

## Masalah
File `DivisionUsersData.php` menggunakan struktur `'role'` sebagai string, tidak konsisten dengan struktur baru yang menggunakan array `['agency', 'agency_xxx']`.

User sudah mengubah manually tapi terjadi syntax error:
```
PHP Parse error: syntax error, unexpected token ";", expecting "]"
in DivisionUsersData.php on line 95
```

**Root Cause**:
Setiap array user tidak ditutup dengan `]`. User mengubah:
```php
'role' => 'agency_admin_unit'
```
menjadi:
```php
'role' => ['agency', 'agency_admin_unit'],  // ❌ Missing closing ]
```

## Target
- Setiap division user harus memiliki default role `'agency'` plus role spesifik
- Format: `'role' => ['agency', 'agency_admin_unit']`
- Struktur array harus benar (semua brackets tertutup)

## Solusi

### File: DivisionUsersData.php (FIXED)
**Location**: `src/Database/Demo/Data/DivisionUsersData.php`

**Issue**: Syntax error karena missing closing bracket `]`

**Before (WRONG)**:
```php
1 => [  // Disnaker Provinsi Aceh
    'pusat' => ['id' => 140, 'username' => 'budi_citra',
                'display_name' => 'Budi Citra',
                'role' => ['agency', 'agency_admin_unit'],  // ❌ Missing ]
    'cabang1' => ['id' => 141, 'username' => 'dani_eko',
                  'display_name' => 'Dani Eko',
                  'role' => ['agency', 'agency_admin_unit'],  // ❌ Missing ]
    'cabang2' => ['id' => 142, 'username' => 'fajar_gita',
                  'display_name' => 'Fajar Gita',
                  'role' => ['agency', 'agency_admin_unit']  // ❌ Missing ]
],
```

**After (CORRECT)**:
```php
1 => [  // Disnaker Provinsi Aceh
    'pusat' => ['id' => 140, 'username' => 'budi_citra',
                'display_name' => 'Budi Citra',
                'role' => ['agency', 'agency_admin_unit']],  // ✅ Added ]
    'cabang1' => ['id' => 141, 'username' => 'dani_eko',
                  'display_name' => 'Dani Eko',
                  'role' => ['agency', 'agency_admin_unit']],  // ✅ Added ]
    'cabang2' => ['id' => 142, 'username' => 'fajar_gita',
                  'display_name' => 'Fajar Gita',
                  'role' => ['agency', 'agency_admin_unit']]  // ✅ Added ]
],
```

**Changes Applied**:
- ✅ Added closing bracket `]` untuk setiap user array
- ✅ Fixed untuk SEMUA 10 agencies (30 users total)
- ✅ Struktur role sudah benar: `['agency', 'agency_admin_unit']`

## Structure Details

### Complete Data Structure:
```php
public static $data = [
    1 => [  // Agency 1
        'pusat' => ['id' => 140, ..., 'role' => ['agency', 'agency_admin_unit']],
        'cabang1' => ['id' => 141, ..., 'role' => ['agency', 'agency_admin_unit']],
        'cabang2' => ['id' => 142, ..., 'role' => ['agency', 'agency_admin_unit']]
    ],
    2 => [  // Agency 2
        'pusat' => ['id' => 143, ..., 'role' => ['agency', 'agency_admin_unit']],
        'cabang1' => ['id' => 144, ..., 'role' => ['agency', 'agency_admin_unit']],
        'cabang2' => ['id' => 145, ..., 'role' => ['agency', 'agency_admin_unit']]
    ],
    // ... hingga Agency 10
    10 => [  // Agency 10
        'pusat' => ['id' => 167, ..., 'role' => ['agency', 'agency_admin_unit']],
        'cabang1' => ['id' => 168, ..., 'role' => ['agency', 'agency_admin_unit']],
        'cabang2' => ['id' => 169, ..., 'role' => ['agency', 'agency_admin_unit']]
    ]
];
```

### Role Structure:
- **Default Role**: `'agency'` - Base role untuk semua agency users
- **Specific Role**: `'agency_admin_unit'` - Division admin role
- **Format**: Array `['agency', 'agency_admin_unit']`

## Benefits
1. ✅ **Consistent Structure**: Sama dengan AgencyEmployeeUsersData (roles sebagai array)
2. ✅ **Default Role Included**: Setiap user punya default role 'agency'
3. ✅ **Syntax Valid**: Semua array brackets tertutup dengan benar
4. ✅ **Complete Coverage**: Fixed untuk semua 10 agencies × 3 divisions = 30 users

## Testing

### Test 1: Verify Syntax
```bash
php -l src/Database/Demo/Data/DivisionUsersData.php
# Should return: No syntax errors detected
```

### Test 2: Verify Data Structure
```php
$data = DivisionUsersData::$data;
foreach ($data as $agency_id => $divisions) {
    foreach ($divisions as $type => $user) {
        // Check role is array
        if (!is_array($user['role'])) {
            echo "ERROR: role should be array for agency {$agency_id}, {$type}\n";
        }

        // Check has 'agency' default role
        if (!in_array('agency', $user['role'])) {
            echo "ERROR: missing 'agency' default role for agency {$agency_id}, {$type}\n";
        }

        // Check has specific role
        if (!in_array('agency_admin_unit', $user['role'])) {
            echo "ERROR: missing 'agency_admin_unit' for agency {$agency_id}, {$type}\n";
        }

        echo "User {$user['id']} ({$user['display_name']}): OK\n";
    }
}
```

### Test 3: Generate Demo Data
```php
// Run demo data generation
$divisionDemo = new DivisionDemoData();
$divisionDemo->run();
// Should complete without syntax errors
```

## Files Modified
- ✅ `src/Database/Demo/Data/DivisionUsersData.php` (FIXED)
  - Added closing brackets for all 30 user arrays
  - Role structure: `['agency', 'agency_admin_unit']`
  - Coverage: All 10 agencies × 3 divisions

## Notes
- Syntax error terjadi karena missing closing bracket `]` pada setiap user array
- User sudah benar mengubah role menjadi array, hanya kurang closing bracket
- DivisionDemoData.php tidak perlu diubah karena tidak mengakses field 'role' langsung
- Total 30 users (10 agencies × 3 divisions per agency)
- Semua users sekarang memiliki default role 'agency' + specific role 'agency_admin_unit'

## Related Tasks
- TODO-2059: Agency Employee Users with roles array structure
- TODO-2058: Division Name Collection
- TODO-2056: Role Management

## References
- Role management: `/wp-agency/includes/class-role-manager.php`
- Pattern reference: `/wp-agency/src/Database/Demo/Data/AgencyEmployeeUsersData.php`


## review-01

### Masalah
Setelah generate division, role yang diberikan ke user hanya "agency", tetapi role tambahan 'agency_admin_unit' tidak dibuat.

**Root Cause**:
1. `WPUserGenerator::generateUser()` hanya menerima single role string (`$data['role']`), tidak support array
2. `DivisionDemoData.php` hardcoded `'role' => 'agency'` saat generate user, tidak menggunakan role dari `DivisionUsersData`

### Solusi

Ya, kita bisa assign multiple roles dengan mengupdate capabilities di usermeta. WordPress mendukung multiple roles per user.

#### File 1: WPUserGenerator.php (UPDATED)
**Location**: `src/Database/Demo/WPUserGenerator.php`

**Changes**:

1. **Support Multiple Roles** (Lines 124-156):
```php
// Handle roles - support both single role (string) and multiple roles (array)
$roles = [];
if (isset($data['roles']) && is_array($data['roles'])) {
    // Multiple roles provided as array
    $roles = $data['roles'];
} elseif (isset($data['role'])) {
    // Single role provided as string (backward compatibility)
    $roles = [$data['role']];
} else {
    // Default role if none provided
    $roles = ['agency'];
}

// Build capabilities array with all roles
$capabilities = [];
foreach ($roles as $role) {
    $capabilities[$role] = true;
}

// Add role capability
$wpdb->insert(
    $wpdb->usermeta,
    [
        'user_id' => $user_id,
        'meta_key' => $wpdb->prefix . 'capabilities',
        'meta_value' => serialize($capabilities)
    ],
    ['%d', '%s', '%s']
);
```

2. **Added updateUserRoles() Method** (Lines 179-205):
```php
/**
 * Update user roles (for existing users)
 */
private function updateUserRoles(int $user_id, array $roles): void {
    global $wpdb;

    // Build capabilities array with all roles
    $capabilities = [];
    foreach ($roles as $role) {
        $capabilities[$role] = true;
    }

    // Update capabilities in usermeta
    $wpdb->update(
        $wpdb->usermeta,
        ['meta_value' => serialize($capabilities)],
        [
            'user_id' => $user_id,
            'meta_key' => $wpdb->prefix . 'capabilities'
        ],
        ['%s'],
        ['%d', '%s']
    );

    $roles_string = implode(', ', $roles);
    $this->debug("Updated roles for user {$user_id}: {$roles_string}");
}
```

3. **Update Existing Users** (Lines 69-72):
```php
// Update roles if provided as array
if (isset($data['roles']) && is_array($data['roles'])) {
    $this->updateUserRoles($existing_user_id, $data['roles']);
}
```

#### File 2: DivisionDemoData.php (UPDATED)
**Location**: `src/Database/Demo/DivisionDemoData.php`

**Changes**:

1. **Pusat Division User** (Line 307):
```php
// BEFORE:
'role' => 'agency'  // ❌ Hardcoded single role

// AFTER:
'roles' => $user_data['role']  // ✅ Use roles array from DivisionUsersData
```

2. **Cabang Division User** (Line 374):
```php
// BEFORE:
'role' => 'agency'  // ❌ Hardcoded single role

// AFTER:
'roles' => $user_data['role']  // ✅ Use roles array from DivisionUsersData
```

### Benefits
1. ✅ **Multiple Roles Support**: WPUserGenerator sekarang support array roles
2. ✅ **Backward Compatible**: Tetap support single role string (`'role'` parameter)
3. ✅ **Uses Data from DivisionUsersData**: Tidak lagi hardcoded role
4. ✅ **Update Existing Users**: Jika user sudah ada, roles akan diupdate
5. ✅ **Proper WordPress Capabilities**: Menggunakan usermeta wp_capabilities

### How It Works

**WordPress User Capabilities Structure**:
```php
// In wp_usermeta table:
meta_key: 'wp_capabilities'
meta_value: serialize([
    'agency' => true,
    'agency_admin_unit' => true
])
```

**Data Flow**:
1. `DivisionUsersData::$data` contains `'role' => ['agency', 'agency_admin_unit']`
2. `DivisionDemoData` passes `'roles' => $user_data['role']` to WPUserGenerator
3. `WPUserGenerator` builds capabilities array from roles
4. Capabilities saved to usermeta as serialized array

### Testing

**Test 1: Verify User Roles After Generation**
```php
// After generating division demo data
$user_id = 140; // First division user
$user = get_userdata($user_id);
$roles = $user->roles;

// Expected: ['agency', 'agency_admin_unit']
var_dump($roles);
```

**Test 2: Check Capabilities in Database**
```php
global $wpdb;
$capabilities = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->usermeta}
     WHERE user_id = %d AND meta_key = %s",
    140,
    $wpdb->prefix . 'capabilities'
));

// Expected: a:2:{s:6:"agency";b:1;s:18:"agency_admin_unit";b:1;}
var_dump(unserialize($capabilities));
```

**Test 3: Verify All Division Users**
```php
// Check all 30 division users (ID 140-169)
for ($id = 140; $id <= 169; $id++) {
    $user = get_userdata($id);
    if (!$user) {
        echo "User {$id} not found\n";
        continue;
    }

    $has_agency = in_array('agency', $user->roles);
    $has_admin_unit = in_array('agency_admin_unit', $user->roles);

    if (!$has_agency || !$has_admin_unit) {
        echo "ERROR: User {$id} missing roles. Has: " . implode(', ', $user->roles) . "\n";
    } else {
        echo "User {$id} ({$user->display_name}): OK\n";
    }
}
```

### Files Modified
- ✅ `src/Database/Demo/WPUserGenerator.php`
  - Added support for multiple roles via 'roles' parameter (array)
  - Maintained backward compatibility with 'role' parameter (string)
  - Added updateUserRoles() method for existing users
  - Updated debug logging to show all assigned roles

- ✅ `src/Database/Demo/DivisionDemoData.php`
  - Updated pusat division user generation (line 307)
  - Updated cabang division user generation (line 374)
  - Changed from hardcoded 'role' => 'agency' to 'roles' => $user_data['role']

### Notes
- WordPress mendukung multiple roles per user melalui wp_capabilities meta
- Tidak perlu menggunakan `add_role()` karena kita langsung set capabilities di usermeta
- Format capabilities: `serialize(['role1' => true, 'role2' => true])`
- Backward compatible: existing code yang pass 'role' string tetap akan bekerja

