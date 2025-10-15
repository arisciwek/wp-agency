# TODO-2056: Role Management dan Delete Roles saat Plugin Deactivation

## Status
✅ COMPLETED

## Masalah
Saat plugin wp-agency dinonaktifkan, roles yang dibuat oleh plugin tidak dihapus. Selain itu, belum ada centralized role management class seperti yang ada di plugin wp-customer.

## Target
1. Membuat class RoleManager untuk centralized role management
2. Menghapus roles saat plugin dinonaktifkan
3. Menggunakan RoleManager di Activator dan Deactivator

## Solusi

### File 1: class-role-manager.php (NEW)
**Location**: `includes/class-role-manager.php`

Membuat class baru WP_Agency_Role_Manager sebagai single source of truth untuk role definitions.

**Features:**
- `getRoles()`: Get semua roles dengan display names
- `getRoleSlugs()`: Get array role slugs saja
- `isPluginRole($slug)`: Check apakah role dikelola plugin ini
- `roleExists($slug)`: Check apakah role exists di WordPress
- `getRoleName($slug)`: Get display name dari role

**Roles yang dikelola:**
- agency (Disnaker)
- admin_dinas (Admin Dinas)
- admin_unit (Admin Unit)
- pengawas (Pengawas)
- pengawas_spesialis (Pengawas Spesialis)
- kepala_unit (Kepala Unit)
- kepala_seksi (Kepala Seksi)
- kepala_bidang (Kepala Bidang)
- kepala_dinas (Kepala Dinas)

### File 2: class-activator.php (UPDATED)
**Location**: `includes/class-activator.php`

**Changes:**
1. Added RoleManager loading at line 38-39
2. Updated `activate()` method line 50-77:
   - Added textdomain loading
   - Changed `self::getRoles()` to `WP_Agency_Role_Manager::getRoles()`
   - Removed unnecessary array_diff_key for 'administrator'
3. Updated `getRoles()` method line 112-121:
   - Marked as DEPRECATED
   - Now calls `WP_Agency_Role_Manager::getRoles()`

### File 3: class-deactivator.php (UPDATED)
**Location**: `includes/class-deactivator.php`

**Changes:**
1. Added RoleManager loading at line 20-21
2. Updated `remove_capabilities()` method line 103-130:
   - Changed hardcoded roles array to `WP_Agency_Role_Manager::getRoleSlugs()`
   - Added debug logging for each removed role
   - Now removes ALL plugin-managed roles automatically

**Impact:**
- Saat plugin deactivate, semua roles yang dibuat plugin akan dihapus
- Tidak perlu manual update list roles di deactivator

### File 4: wp-agency.php (NO CHANGES)
**Location**: `wp-agency.php`

Tidak ada perubahan diperlukan karena RoleManager sudah di-load di Activator dan Deactivator.

## Benefits
1. ✅ **Centralized Management**: Single source of truth untuk role definitions
2. ✅ **Auto Cleanup**: Roles otomatis dihapus saat plugin deactivate
3. ✅ **Consistency**: Pattern sama dengan plugin wp-customer
4. ✅ **Maintainability**: Mudah menambah/update roles di satu tempat
5. ✅ **Reusability**: RoleManager dapat diakses dari komponen lain

## Testing

### Test 1: Verify RoleManager
```php
// Check roles list
$roles = WP_Agency_Role_Manager::getRoles();
var_dump($roles); // Should return array of 9 roles

// Check role slugs
$slugs = WP_Agency_Role_Manager::getRoleSlugs();
var_dump($slugs); // Should return array of 9 slugs

// Check if plugin role
$is_plugin_role = WP_Agency_Role_Manager::isPluginRole('agency');
var_dump($is_plugin_role); // Should return true
```

### Test 2: Plugin Activation
1. Deactivate plugin wp-agency
2. Activate plugin wp-agency
3. Check roles di WordPress:
   ```php
   $agency = get_role('agency');
   var_dump($agency); // Should exist
   ```

### Test 3: Plugin Deactivation
1. Check roles sebelum deactivate:
   ```php
   $agency = get_role('agency');
   var_dump($agency); // Should exist
   ```
2. Deactivate plugin wp-agency
3. Check roles setelah deactivate:
   ```php
   $agency = get_role('agency');
   var_dump($agency); // Should return null
   ```

### Test 4: Backward Compatibility
```php
// Old method should still work (deprecated)
$roles = WP_Agency_Activator::getRoles();
var_dump($roles); // Should work and return same as RoleManager
```

## Files Modified
- ✅ `includes/class-role-manager.php` (NEW - created)
- ✅ `includes/class-activator.php` (UPDATED - uses RoleManager)
- ✅ `includes/class-deactivator.php` (UPDATED - uses RoleManager, removes roles on deactivate)
- ✅ `wp-agency.php` (NO CHANGES - not needed)

## Notes
- Class RoleManager dibuat sebagai static class untuk kemudahan akses
- Deprecation warning ditambahkan di Activator::getRoles() untuk backward compatibility
- Roles akan dihapus HANYA saat plugin deactivate (bukan uninstall)
- Capability 'read' tetap ditambahkan via PermissionModel (lihat TODO-2055)

## Related Tasks
- TODO-2055: Add Read Capability to Agency Role

## References
- Similar implementation: `/wp-customer/includes/class-role-manager.php`
- Activator reference: `/wp-customer/includes/class-activator.php`
