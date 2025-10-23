# Employee Action Hooks

Action hooks for Employee entity lifecycle events in WP Agency plugin.

## Table of Contents

- [wp_agency_employee_created](#wp_agency_employee_created)
- [wp_agency_employee_before_delete](#wp_agency_employee_before_delete)
- [wp_agency_employee_deleted](#wp_agency_employee_deleted)

---

## wp_agency_employee_created

**Fired When**: After a new employee is successfully created and saved to database

**Location**: `src/Models/Employee/AgencyEmployeeModel.php:87`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$employee_id` | int | The newly created employee ID |
| `$employee_data` | array | Employee data array |

### Employee Data Array Structure

```php
[
    'agency_id' => 1,                    // int - Parent agency ID
    'division_id' => 2,                  // int - Parent division ID
    'user_id' => 170,                    // int - WordPress user ID
    'name' => 'John Doe',                // string - Employee name
    'position' => 'Staff Finance',       // string - Position/title
    'email' => 'john@example.com',       // string - Email address
    'phone' => '081234567890',           // string - Phone number
    'finance' => 1,                      // int - Has finance access (0|1)
    'operation' => 0,                    // int - Has operation access (0|1)
    'legal' => 0,                        // int - Has legal access (0|1)
    'purchase' => 0,                     // int - Has purchase access (0|1)
    'keterangan' => 'Notes',             // string|null - Additional notes
    'created_by' => 1,                   // int - Creator user ID
    'status' => 'active',                // string - active|inactive
    'created_at' => '2025-01-23 10:30:00', // string - MySQL datetime
    'updated_at' => '2025-01-23 10:30:00'  // string - MySQL datetime
]
```

### Use Cases

1. **Audit Logging**: Log employee creation for compliance
2. **External Integration**: Sync employee data to HR systems
3. **Welcome Email**: Send welcome email to employee
4. **Permission Setup**: Configure additional permissions
5. **Notification**: Notify agency/division admin of new employee

### Example - Send Welcome Email

```php
add_action('wp_agency_employee_created', 'send_employee_welcome_email', 10, 2);

function send_employee_welcome_email($employee_id, $employee_data) {
    $user = get_user_by('ID', $employee_data['user_id']);

    if (!$user) {
        return;
    }

    $to = $employee_data['email'];
    $subject = 'Welcome to Agency - Employee Account Created';
    $message = sprintf(
        "Hello %s,\n\nYour employee account has been created.\n\nPosition: %s\nDivision: %s\n\nYou can now login to access the platform.",
        $employee_data['name'],
        $employee_data['position'],
        $employee_data['division_name'] ?? 'N/A'
    );

    wp_mail($to, $subject, $message);
}
```

### Example - External HR System Integration

```php
add_action('wp_agency_employee_created', 'sync_employee_to_hr_system', 10, 2);

function sync_employee_to_hr_system($employee_id, $employee_data) {
    wp_remote_post('https://hr.example.com/api/employees', [
        'body' => json_encode([
            'external_id' => $employee_id,
            'name' => $employee_data['name'],
            'email' => $employee_data['email'],
            'phone' => $employee_data['phone'],
            'position' => $employee_data['position'],
            'department_id' => $employee_data['division_id'],
            'agency_id' => $employee_data['agency_id'],
            'created_at' => $employee_data['created_at']
        ]),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer YOUR_API_KEY'
        ],
        'timeout' => 15
    ]);
}
```

### Related Hooks

- `wp_agency_division_created` - Fired before this (auto-creates employee)
- `wp_agency_employee_before_delete` - Before employee deletion
- `wp_agency_employee_deleted` - After employee deletion

### Notes

- This hook is fired AFTER data is saved to database
- Employee is leaf node (no cascade creation)
- Validation already completed before this hook fires
- Automatically fired by `AutoEntityCreator::handleDivisionCreated()`

---

## wp_agency_employee_before_delete

**Fired When**: Before an employee is deleted from database

**Location**: `src/Models/Employee/AgencyEmployeeModel.php:305`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | int | Employee ID to be deleted |
| `$employee_data` | array | Complete employee data array |

### Use Cases

1. **Validation**: Check if employee can be safely deleted
2. **Pre-deletion Logging**: Log before deletion for audit
3. **External Notification**: Alert external systems before deletion
4. **Prevent Deletion**: Stop deletion based on business rules

### Example - Prevent Deletion of Last Employee

```php
add_action('wp_agency_employee_before_delete', 'prevent_last_employee_deletion', 10, 2);

function prevent_last_employee_deletion($employee_id, $employee_data) {
    global $wpdb;

    $active_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_employees
         WHERE division_id = %d AND status = 'active' AND id != %d",
        $employee_data['division_id'],
        $employee_id
    ));

    if ($active_count === '0') {
        wp_die('Cannot delete the last active employee in this division!');
    }
}
```

### Related Hooks

- `wp_agency_employee_created` - After employee creation
- `wp_agency_employee_deleted` - After employee deletion (pairs with this hook)

---

## wp_agency_employee_deleted

**Fired When**: After an employee is deleted from database (soft or hard delete)

**Location**: `src/Models/Employee/AgencyEmployeeModel.php:337`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | int | Employee ID that was deleted |
| `$employee_data` | array | Complete employee data array (before deletion) |
| `$is_hard_delete` | bool | True if hard deleted, false if soft deleted |

### Hard Delete vs Soft Delete

**Soft Delete** (`$is_hard_delete = false`):
- Record remains in database with `status='inactive'`
- Default behavior

**Hard Delete** (`$is_hard_delete = true`):
- Record removed from database (DELETE query)
- Enabled via: `wp_agency_general_options['enable_hard_delete_branch'] = true`

### Use Cases

1. **Cleanup Resources**: Remove employee-specific data
2. **Cache Invalidation**: Clear caches after deletion
3. **Post-deletion Logging**: Log successful deletion for audit
4. **External Notification**: Notify HR systems of employee deletion

### Example - Cleanup External Records

```php
add_action('wp_agency_employee_deleted', 'cleanup_employee_from_hr', 10, 3);

function cleanup_employee_from_hr($employee_id, $employee_data, $is_hard_delete) {
    // Only delete from external system on hard delete
    if (!$is_hard_delete) {
        return;
    }

    wp_remote_request('https://hr.example.com/api/employees/' . $employee_id, [
        'method' => 'DELETE',
        'headers' => [
            'Authorization' => 'Bearer YOUR_API_KEY'
        ]
    ]);
}
```

### Example - Audit Logging

```php
add_action('wp_agency_employee_deleted', 'log_employee_deleted', 10, 3);

function log_employee_deleted($employee_id, $employee_data, $is_hard_delete) {
    error_log(sprintf(
        '[AUDIT] Employee %s: ID=%d, Name=%s, Position=%s',
        $is_hard_delete ? 'HARD DELETED' : 'SOFT DELETED',
        $employee_id,
        $employee_data['name'],
        $employee_data['position']
    ));
}
```

### Related Hooks

- `wp_agency_employee_before_delete` - Before employee deletion (pairs with this hook)

### Notes

- Hook fires AFTER successful deletion
- Employee is leaf node (no cascade delete needed)
- Cache already invalidated by model
- Use `$is_hard_delete` parameter to determine delete type

---

**See Also**:
- [Agency Action Hooks](agency-actions.md)
- [Division Action Hooks](division-actions.md)
- [Hook Examples](../examples/)
