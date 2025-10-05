# TODO WP-Agency

## Fix PHP Fatal Error in AgencyEmployeeValidator

### Issue
- PHP Warning: Undefined property: WPAgency\Validators\Employee\AgencyEmployeeValidator::$model
- PHP Fatal error: Call to a member function find() on null in AgencyEmployeeValidator.php:262

### Root Cause
In `validateUpdate` method, line 262 uses `$this->model->find($id)` but the property is named `$employee_model`, not `$model`.

### Steps to Fix
- [x] Edit `src/Validators/Employee/AgencyEmployeeValidator.php` line 262: Change `$this->model` to `$this->employee_model`
- [ ] Test the employee update functionality to ensure the fix works
- [x] Verify no other similar typos in the codebase

### Files to Edit
- `src/Validators/Employee/AgencyEmployeeValidator.php`

### Followup
- Run the update employee action in the plugin to confirm the error is resolved.

## Support Multi-Role pada Datatable Staff Agency

### Issue
- Datatable karyawan agency hanya menampilkan satu role (primary role) pada kolom "Wewenang"
- Karyawan dapat memiliki multiple roles, namun tidak ditampilkan di datatable

### Root Cause
- Method `getUserRole` di `AgencyEmployeeController` hanya mengembalikan role pertama dari array roles user

### Steps to Fix
- [x] Modify `getUserRole` method in `src/Controllers/Employee/AgencyEmployeeController.php` to return all roles as formatted string
- [x] Test datatable display to ensure multiple roles show correctly
- [x] Verify column width is sufficient for multiple roles display (increased from 18% to 22%)

### Files to Edit
- `src/Controllers/Employee/AgencyEmployeeController.php`
- `assets/js/employee/employee-datatable.js`

### Followup
- Check datatable in agency right panel to confirm multiple roles are displayed

## Support Pencarian Multi-Column pada Datatable Staff Agency

### Issue
- Datatable karyawan agency menggunakan single search box global
- Pencarian hanya mencari di kolom nama saja, tidak di kolom lain seperti jabatan, wewenang, cabang, status
- Contoh: mencari "pengawas" tidak menemukan data karena "pengawas" ada di kolom wewenang, bukan nama

### Root Cause
- Query pencarian di model hanya mencari di e.name, e.position, b.name (division)
- Tidak termasuk pencarian di status dan wewenang (role dari user capabilities)

### Steps to Fix
- [x] Update model `src/Models/Employee/AgencyEmployeeModel.php` method getDataTableData untuk menambahkan pencarian di kolom status dan wewenang (role)
- [x] Untuk wewenang, gunakan subquery ke wp_usermeta untuk mencari di capabilities
- [x] Test pencarian global untuk memastikan mencari di semua kolom: nama, jabatan, wewenang, cabang, status
- [x] Verifikasi pencarian case-insensitive dan partial match
- [x] Perbaiki pencarian multiple words dengan split pada spasi dan AND logic

### Files to Edit
- `src/Models/Employee/AgencyEmployeeModel.php`

### Followup
- Test pencarian kata seperti "pengawas" (di wewenang), "aktif" (di status), dll.
- Pastikan pencarian tetap efisien dengan dataset besar
