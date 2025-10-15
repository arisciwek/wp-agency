# TODO-2055: Add Read Capability to Agency Role

## Status
✅ COMPLETED

## Masalah
Role 'agency' belum memiliki capability 'read' yang diperlukan untuk dapat mengakses halaman wp-admin. Tanpa capability ini, user dengan role agency tidak bisa masuk ke area admin WordPress.

## Target
Menambahkan capability 'read' untuk role "agency" di method `addCapabilities()` di `PermissionModel.php`.

## Solusi

### File: PermissionModel.php - Add 'read' capability
**Location**: `src/Models/Settings/PermissionModel.php` line 133-139

```php
// Set agency role capabilities
$agency = get_role('agency');
if ($agency) {
    // Add 'read' capability - required for wp-admin access
    $agency->add_cap('read');

    $default_capabiities = [
        // ... rest of capabilities
    ];

    // ... rest of the code
}
```

**Penjelasan:**
- `read` capability **WAJIB** untuk user dengan role agency bisa akses wp-admin
- Ditambahkan **sebelum** default capabilities lainnya
- Konsisten dengan pattern capability management di PermissionModel
- Mengikuti implementasi yang sama dengan plugin wp-customer

## Benefits
1. ✅ **Admin Access**: User dengan role agency dapat mengakses wp-admin
2. ✅ **Consistency**: Mengikuti pattern yang sama dengan plugin wp-customer
3. ✅ **Centralized Management**: Capability dikelola di PermissionModel
4. ✅ **Best Practice**: Menggunakan WordPress core capability yang sudah standard

## Testing
1. Deactivate dan activate ulang plugin untuk trigger `addCapabilities()`
2. Verify role 'agency' memiliki capability 'read':
   ```php
   $agency = get_role('agency');
   var_dump($agency->has_cap('read')); // Should return true
   ```
3. Test login sebagai user dengan role agency dan akses wp-admin
4. Pastikan user agency bisa masuk wp-admin dengan normal

## Files Modified
- ✅ `src/Models/Settings/PermissionModel.php` (added 'read' capability in addCapabilities() method at line 136-137)

## Notes
- Capability 'read' adalah WordPress core capability
- Tidak perlu ditambahkan ke `$available_capabilities` array karena ini bukan custom capability
- Method `addCapabilities()` dipanggil saat plugin activation
- Capability ini akan dipersist di database setelah di-add

## References
- Similar implementation: `/wp-customer/src/Models/Settings/PermissionModel.php:136-137`
- Reference documentation: `/wp-customer/docs/TODO-2133-add-read-capability.md`
