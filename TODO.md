# TODO WP-Agency

## Fix Inspector Assignment Count Display (TODO-2047)

### Issue
Setelah assign inspector berhasil, saat assign lagi dengan inspector yang sama, modal menampilkan "Pengawas ini saat ini memiliki 0 penugasan" padahal seharusnya menunjukkan jumlah penugasan sebenarnya.

### Root Cause
JavaScript onInspectorChange hanya set placeholder text tanpa load data aktual dari database.

### Steps to Fix
- [x] Modify getAvailableInspectors to include assignment count for each inspector
- [x] Update JS to display assignment count in inspector select options
- [x] Update onInspectorChange to show actual count from selected inspector
- [x] Test that count displays correctly in modal

### Files to Edit
- `assets/js/company/new-company-datatable.js`
- `src/Controllers/Company/NewCompanyController.php`
- `src/Models/Company/NewCompanyModel.php`
- `src/Validators/Company/NewCompanyValidator.php`

### Followup
- Test assign inspector dan verify count berkurang
- Check database inspector_id terupdate
- Verify cache cleared properly

## Fix Company DataTable Not Updating After Inspector Assignment (PENDING)

### Issue
Setelah assign inspector berhasil di wp-agency, datatable company di wp-customer tidak langsung terupdate. Data baru muncul setelah 2 menit (waktu expiry cache).

### Root Cause
Cache datatable 'company_list' di wp-customer tidak di-clear setelah assignment berhasil. Cache expiry 120 detik menyebabkan data lama ditampilkan. Cache plugin yang diperlukan untuk flush cache belum terinstall.

### Information Gathered
- Cache key: 'datatable' dengan context 'company_list', komponen access_type, start, length, md5(search), orderColumn, orderDir
- Expiry: 120 detik
- JS datatable tidak ada cache, hanya server-side
- Assignment terjadi di wp-agency, cache di wp-customer

### Plan
- Modify assignInspector di NewCompanyController.php untuk clear cache wp-customer setelah assignment sukses
- Check jika class CustomerCacheManager ada, instantiate dan panggil clearAll()
- Clear juga cache branch_membership untuk branch yang di-assign jika perlu

### Files to Edit
- `src/Controllers/Company/NewCompanyController.php`

### Dependent Files
- Tidak ada perubahan di wp-customer, hanya akses class jika tersedia

### Followup
- Install cache plugin yang mendukung wp_cache_flush_group
- Test setelah assign, datatable company di wp-customer update langsung
- Verify tidak error jika plugin wp-customer tidak aktif
- Check cache ter-clear dengan benar

## Ubah Teks Nama Agency dan New Company (TODO-2046)

### Issue
Teks "Nama Agency" dan "Cabang" di datatable agency perlu diubah untuk konsistensi terminologi.
Juga teks "New Company" di tab agency right panel perlu diubah ke bahasa Indonesia.

### Target
- [x] Ubah "Nama Agency" menjadi "Disnaker" di `assets/js/agency/agency-datatable.js`
- [x] Ubah "Cabang" menjadi "Unit Kerja" di `assets/js/agency/agency-datatable.js`
- [x] Ubah "New Company" menjadi "Perusahaan Baru" di `src/Views/templates/agency-right-panel.php`

### Files to Edit
- `assets/js/agency/agency-datatable.js`
- `src/Views/templates/agency-right-panel.php`

### Followup
- Test datatable agency menampilkan teks yang benar
- Test tab "Perusahaan Baru" muncul dengan benar
