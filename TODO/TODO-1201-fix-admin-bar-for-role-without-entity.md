# TODO-1201-FIX: Admin Bar Not Showing for Users with Agency Role but No Entity Link

## Status: ✅ COMPLETED

## Problem

User dengan role `agency_admin_dinas` tidak bisa melihat admin bar dari wp-app-core, padahal:
- wp-app-core sudah terinstall ✅
- wp-agency sudah terintegrasi ✅
- User memiliki role yang terdaftar (`agency_admin_dinas`) ✅

## Root Cause

### Original Logic (class-app-core-integration.php):

Integration hanya mengecek 3 kondisi:
1. **Agency Owner**: User yang `user_id` ada di `agencies.user_id`
2. **Division Admin**: User yang `user_id` ada di `divisions.user_id`
3. **Employee**: User yang `user_id` ada di `employees.user_id`

### Missing Case:

Role `agency_admin_dinas` adalah **administrative role** yang:
- ❌ **TIDAK** memiliki agency ownership (tidak ada di `agencies.user_id`)
- ❌ **TIDAK** memiliki division assignment (tidak ada di `divisions.user_id`)
- ❌ **TIDAK** memiliki employee record (tidak ada di `employees.user_id`)
- ✅ **HANYA** memiliki WordPress role saja

Jadi meskipun user punya role registered, function `get_user_info()` return `null`, sehingga admin bar **tidak muncul**.

## Impact

**Affected Roles:**
- `agency_admin_dinas` - Admin Dinas (system administrator)
- Potentially other administrative roles yang tidak di-link ke entity

**Symptom:**
- User login berhasil
- Dashboard bisa diakses
- **Admin bar tidak muncul** ❌
- User info tidak ditampilkan

## Solution

### Add Fallback Logic

Tambahkan fallback untuk user yang:
1. Memiliki agency role (registered di `WP_Agency_Role_Manager`)
2. Tapi tidak punya entity link di database
3. Tampilkan info generic berdasarkan role

### Implementation

**File Modified:** `/wp-agency/includes/class-app-core-integration.php`

**Added Code (after line 145):**

```php
// Fallback: If user has agency role but no entity link, show role-based info
if (!$result) {
    $user = get_user_by('ID', $user_id);
    if ($user) {
        $agency_roles = WP_Agency_Role_Manager::getRoleSlugs();
        $user_roles = (array) $user->roles;

        // Check if user has any agency role
        $has_agency_role = false;
        foreach ($agency_roles as $role_slug) {
            if (in_array($role_slug, $user_roles)) {
                $has_agency_role = true;
                break;
            }
        }

        if ($has_agency_role) {
            // Get first agency role for display
            $first_agency_role = null;
            foreach ($agency_roles as $role_slug) {
                if (in_array($role_slug, $user_roles)) {
                    $first_agency_role = $role_slug;
                    break;
                }
            }

            $role_name = WP_Agency_Role_Manager::getRoleName($first_agency_role);

            $result = [
                'entity_name' => 'Dinas Tenaga Kerja', // Generic agency name
                'entity_code' => 'DISNAKER',
                'branch_name' => $role_name ?? 'Staff', // Use role name as branch
                'branch_type' => 'admin',
                'relation_type' => 'admin',
                'icon' => '🏛️'
            ];
        }
    }
}
```

## How Fallback Works

### Priority Order:

1. **Check Agency Owner** (agencies.user_id)
   - If found → return agency info ✅

2. **Check Division Admin** (divisions.user_id)
   - If found → return division info ✅

3. **Check Employee** (employees.user_id)
   - If found → return employee info ✅

4. **NEW: Check Role-Based Fallback**
   - If user has any agency role → return generic info ✅
   - If no agency role → return null (no admin bar)

### Fallback Display:

For user with `agency_admin_dinas` role:

```php
[
    'entity_name' => 'Dinas Tenaga Kerja',  // Generic name
    'entity_code' => 'DISNAKER',            // Generic code
    'branch_name' => 'Admin Dinas',         // Role display name
    'branch_type' => 'admin',               // Type: admin
    'relation_type' => 'admin',             // Relation: admin
    'icon' => '🏛️'                          // Government icon
]
```

**Admin Bar Will Show:**
- Entity: Dinas Tenaga Kerja
- Branch: Admin Dinas ← Role name
- Icon: 🏛️
- Roles: agency_admin_dinas

## Testing

### Test Case 1: User with Entity Link (Already Working)
- User dengan `agencies.user_id` → Shows agency info ✅
- User dengan `divisions.user_id` → Shows division info ✅
- User dengan `employees.user_id` → Shows employee info ✅

### Test Case 2: User with Role Only (NEW - Now Fixed)
- User dengan role `agency_admin_dinas` → Shows "Dinas Tenaga Kerja - Admin Dinas" ✅
- User dengan role `agency_pengawas` → Shows "Dinas Tenaga Kerja - Pengawas" ✅

### Test Case 3: User without Agency Role
- User dengan role `subscriber` → No admin bar (correct behavior) ✅

## Benefits

✅ **All agency users now visible** in admin bar
✅ **Role-based users** (admin dinas, etc) can see their info
✅ **No breaking changes** to existing entity-linked users
✅ **Graceful fallback** for administrative roles
✅ **Consistent UX** across all agency roles

## Comparison

| User Type | Before Fix | After Fix |
|-----------|-----------|-----------|
| Agency Owner | ✅ Shows | ✅ Shows |
| Division Admin | ✅ Shows | ✅ Shows |
| Employee | ✅ Shows | ✅ Shows |
| Admin Dinas (role only) | ❌ Hidden | ✅ Shows |
| Pengawas (role only) | ❌ Hidden | ✅ Shows |

## Notes

### Why Generic Info?

User `agency_admin_dinas` adalah **system administrator** yang:
- Bisa melihat semua agency
- Tidak terikat ke agency tertentu
- Role-nya adalah akses management, bukan ownership

Jadi wajar ditampilkan info generic "Dinas Tenaga Kerja" dengan role name sebagai "branch".

### Extensibility

Fallback ini juga berlaku untuk **semua 9 agency roles**:
1. agency
2. agency_admin_dinas ← Fixed this
3. agency_admin_unit
4. agency_pengawas ← Will also work
5. agency_pengawas_spesialis ← Will also work
6. agency_kepala_unit
7. agency_kepala_seksi
8. agency_kepala_bidang
9. agency_kepala_dinas

## Similar Issue in wp-customer

**Note:** wp-customer memiliki masalah yang sama untuk role `customer_admin`.

**Recommendation:** Apply similar fallback logic to:
- `/wp-customer/includes/class-app-core-integration.php`

For role `customer_admin` without entity link:
```php
'entity_name' => 'Customer Management',
'entity_code' => 'ADMIN',
'branch_name' => 'Customer Admin',
'relation_type' => 'admin'
```

## Implementation Date

- **Issue Reported**: 2025-01-18 (Review-02)
- **Root Cause Identified**: 2025-01-18
- **Fix Applied**: 2025-01-18
- **Status**: ✅ COMPLETED

## Related Files

- `/wp-agency/includes/class-app-core-integration.php` ✅ Modified
- `/wp-agency/TODO/TODO-1201-wp-app-core-integration.md` - Original integration
- `/wp-app-core/includes/class-admin-bar-info.php` - Core admin bar system

## Summary

✅ **FIXED**: User dengan role `agency_admin_dinas` (dan role lain tanpa entity link) sekarang bisa melihat admin bar dengan info generic yang sesuai dengan role mereka.

**Before:**
- Login → No admin bar ❌

**After:**
- Login → Admin bar shows "Dinas Tenaga Kerja - Admin Dinas" ✅

---

## Review-03: FALSE ALARM (Reverted)

**Date**: 2025-01-18

**Reported Issue**: Customer users stopped seeing their admin bar after agency integration.

**Root Cause Analysis**:
❌ **NOT A CODE BUG** - Issue was caused by "Login as User" plugin behavior:
- User menggunakan plugin "Login as User" untuk testing
- Ketika wp-app-core diaktifkan, logout dari role yang sedang digunakan
- Default WordPress admin bar tampil (normal untuk administrator role)
- Setelah login as user lagi, admin bar plugin tampil dengan benar ✅

**Conclusion**:
- Code is working correctly
- Both customer and agency admin bars work as expected
- No customer role exclusion needed in agency fallback
- Review-03 fix was unnecessary and has been **REVERTED**

**Lesson Learned**:
- Always verify issue with direct login, not just "Login as User" plugin
- Logout/login transitions may show different admin bar temporarily
- Test with actual user accounts before making code changes

**Status**: ✅ No fix needed - working as intended
