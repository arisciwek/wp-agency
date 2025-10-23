# Agency Action Hooks

Action hooks for Agency entity lifecycle events in WP Agency plugin.

## Table of Contents

- [wp_agency_agency_created](#wp_agency_agency_created)
- [wp_agency_agency_before_delete](#wp_agency_agency_before_delete)
- [wp_agency_agency_deleted](#wp_agency_agency_deleted)

---

## wp_agency_agency_created

**Fired When**: After a new agency is successfully created and saved to database

**Location**: `src/Models/Agency/AgencyModel.php:274`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$agency_id` | int | The newly created agency ID |
| `$agency_data` | array | Agency data array |

### Agency Data Array Structure

```php
[
    'code' => 'AGC001',                  // string - Unique agency code
    'name' => 'Dinas Tenaga Kerja',      // string - Agency name
    'provinsi_code' => '32',             // string|null - Province code
    'regency_code' => '3204',            // string|null - Regency code
    'user_id' => 123,                    // int|null - WordPress user ID (owner)
    'created_by' => 1,                   // int - WordPress user ID who created
    'reg_type' => 'self',                // string - self|by_admin|generate
    'status' => 'active',                // string - active|inactive (optional)
    'created_at' => '2025-01-23 10:30:00', // string - MySQL datetime
    'updated_at' => '2025-01-23 10:30:00'  // string - MySQL datetime
]
```

### Use Cases

1. **Auto-create Division Pusat**: Plugin uses this to auto-create main division
2. **Auto-create Employee**: Chain with division_created to create employee
3. **External Integration**: Sync agency data to external systems
4. **Audit Logging**: Log agency creation for compliance
5. **Welcome Email**: Send welcome email to agency owner

### Example - Auto-create Division Pusat

This is the default handler registered by the plugin:

```php
add_action('wp_agency_agency_created', [$auto_entity_creator, 'handleAgencyCreated'], 10, 2);

// In AutoEntityCreator class
public function handleAgencyCreated($agency_id, $data) {
    // Check if division pusat already exists
    $existing = $this->division_model->getByAgencyAndType($agency_id, 'pusat');

    if ($existing) {
        return; // Already exists, skip
    }

    // Create division pusat
    $division_data = [
        'agency_id' => $agency_id,
        'code' => 'DIV-PUSAT-' . $agency_id,
        'name' => 'Pusat',
        'type' => 'pusat',
        'user_id' => $data['user_id'],
        'created_by' => $data['created_by'] ?? get_current_user_id()
    ];

    $division_id = $this->division_model->create($division_data);

    if ($division_id) {
        // This fires wp_agency_division_created hook
        // which creates admin employee automatically
    }
}
```

### Example - Send Welcome Email

```php
add_action('wp_agency_agency_created', 'send_agency_welcome_email', 10, 2);

function send_agency_welcome_email($agency_id, $agency_data) {
    if (empty($agency_data['user_id'])) {
        return; // No user assigned
    }

    $user = get_user_by('ID', $agency_data['user_id']);

    if (!$user) {
        return;
    }

    $to = $user->user_email;
    $subject = 'Welcome to Platform - Agency Created';
    $message = sprintf(
        "Hello %s,\n\nYour agency \"%s\" has been successfully registered.\n\nAgency Code: %s\n\nYou can now login and manage your agency.",
        $user->display_name,
        $agency_data['name'],
        $agency_data['code']
    );

    wp_mail($to, $subject, $message);
}
```

### Example - External CRM Integration

```php
add_action('wp_agency_agency_created', 'sync_agency_to_crm', 10, 2);

function sync_agency_to_crm($agency_id, $agency_data) {
    $response = wp_remote_post('https://crm.example.com/api/agencies', [
        'body' => json_encode([
            'external_id' => $agency_id,
            'code' => $agency_data['code'],
            'name' => $agency_data['name'],
            'province_code' => $agency_data['provinsi_code'],
            'regency_code' => $agency_data['regency_code'],
            'created_at' => $agency_data['created_at']
        ]),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer YOUR_API_KEY'
        ],
        'timeout' => 15
    ]);

    if (is_wp_error($response)) {
        error_log('Failed to sync agency to CRM: ' . $response->get_error_message());
    }
}
```

### Related Hooks

- `wp_agency_division_created` - Fired after division created (auto-triggered by default handler)
- `wp_agency_employee_created` - Fired after employee created (cascade from division)
- `wp_agency_agency_before_delete` - Before agency deletion
- `wp_agency_agency_deleted` - After agency deletion

### Notes

- This hook is fired AFTER data is saved to database
- Fires from admin panel, public registration, or demo generator
- Validation already completed before this hook fires
- Default handler: `AutoEntityCreator::handleAgencyCreated()` auto-creates division pusat
- Division pusat creation fires `wp_agency_division_created` hook
- Employee creation fires `wp_agency_employee_created` hook (cascade chain)

### Debugging

```php
add_action('wp_agency_agency_created', function($agency_id, $agency_data) {
    error_log(sprintf(
        '[HOOK] Agency created: ID=%d, Name=%s, Code=%s',
        $agency_id,
        $agency_data['name'],
        $agency_data['code']
    ));
}, 10, 2);
```

### Security Considerations

- Data is already validated via AgencyValidator
- user_id is verified to exist
- Code uniqueness already checked
- Province/regency codes already validated
- Safe to use data directly in external calls

### Performance Considerations

- Avoid heavy operations (use `wp_schedule_single_event` for async tasks)
- External API calls should use `wp_remote_post` with timeout
- Consider using action hook priority to order operations
- Default handler priority is 10

---

## wp_agency_agency_before_delete

**Fired When**: Before an agency is deleted from database

**Location**: `src/Models/Agency/AgencyModel.php:542`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | int | Agency ID to be deleted |
| `$agency_data` | array | Complete agency data array |

### Agency Data Array Structure

```php
[
    'id' => 1,                           // int - Agency ID
    'code' => 'AGC001',                  // string - Unique agency code
    'name' => 'Dinas Tenaga Kerja',      // string - Agency name
    'provinsi_code' => '32',             // string|null - Province code
    'regency_code' => '3204',            // string|null - Regency code
    'user_id' => 123,                    // int|null - WordPress user ID
    'created_by' => 1,                   // int - Creator user ID
    'reg_type' => 'self',                // string - Registration type
    'created_at' => '2025-01-23 10:30:00', // string - MySQL datetime
    'updated_at' => '2025-01-23 10:30:00'  // string - MySQL datetime
]
```

### Use Cases

1. **Prevent Deletion**: Stop deletion based on business rules
2. **Validation**: Check if agency can be safely deleted
3. **Pre-deletion Logging**: Log before deletion for audit
4. **External Notification**: Alert external systems before deletion
5. **Backup Data**: Archive agency data before deletion

### Example - Prevent Deletion with Children

```php
add_action('wp_agency_agency_before_delete', 'prevent_agency_with_active_divisions', 10, 2);

function prevent_agency_with_active_divisions($agency_id, $agency_data) {
    global $wpdb;

    // Check for active divisions
    $active_divisions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_divisions
         WHERE agency_id = %d AND status = 'active'",
        $agency_id
    ));

    if ($active_divisions > 0) {
        wp_die(sprintf(
            'Cannot delete agency "%s": %d active divisions exist. Please delete divisions first.',
            $agency_data['name'],
            $active_divisions
        ));
    }
}
```

### Example - Audit Logging Before Deletion

```php
add_action('wp_agency_agency_before_delete', 'log_agency_deletion', 10, 2);

function log_agency_deletion($agency_id, $agency_data) {
    $current_user = wp_get_current_user();

    error_log(sprintf(
        '[AUDIT] Agency deletion initiated: ID=%d, Name=%s, Code=%s, By User=%s (ID: %d)',
        $agency_id,
        $agency_data['name'],
        $agency_data['code'],
        $current_user->user_login,
        $current_user->ID
    ));

    // Also save to audit table if exists
    do_action('wp_agency_audit_log', 'agency_delete_initiated', [
        'entity_type' => 'agency',
        'entity_id' => $agency_id,
        'entity_name' => $agency_data['name'],
        'user_id' => $current_user->ID
    ]);
}
```

### Example - Archive Before Deletion

```php
add_action('wp_agency_agency_before_delete', 'archive_agency_data', 10, 2);

function archive_agency_data($agency_id, $agency_data) {
    global $wpdb;

    // Get all related data
    $divisions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}app_agency_divisions WHERE agency_id = %d",
        $agency_id
    ), ARRAY_A);

    $employees = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}app_agency_employees WHERE agency_id = %d",
        $agency_id
    ), ARRAY_A);

    // Create archive
    $archive = [
        'agency' => $agency_data,
        'divisions' => $divisions,
        'employees' => $employees,
        'archived_at' => current_time('mysql'),
        'archived_by' => get_current_user_id()
    ];

    // Save to archive table or file
    update_option('wp_agency_archive_' . $agency_id, $archive);
}
```

### Related Hooks

- `wp_agency_agency_created` - After agency creation
- `wp_agency_agency_deleted` - After agency deletion (pairs with this hook)

### Notes

- Hook fires BEFORE deletion query execution
- Can prevent deletion using `wp_die()` or exception
- Use this for validation and safety checks
- Data is still in database at this point
- Fires for both soft delete (status='inactive') and hard delete (DELETE)

### Debugging

```php
add_action('wp_agency_agency_before_delete', function($id, $agency_data) {
    error_log(sprintf(
        '[HOOK] Before agency delete: ID=%d, Name=%s',
        $id,
        $agency_data['name']
    ));
}, 999, 2); // High priority to run last
```

### Security Considerations

- Validate user permissions before allowing deletion
- Check for related records that could cause data integrity issues
- Consider cascade delete impact
- Log all deletion attempts for audit trail

---

## wp_agency_agency_deleted

**Fired When**: After an agency is deleted from database (soft or hard delete)

**Location**: `src/Models/Agency/AgencyModel.php:578`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | int | Agency ID that was deleted |
| `$agency_data` | array | Complete agency data array (before deletion) |
| `$is_hard_delete` | bool | True if hard deleted (removed from DB), false if soft deleted (status='inactive') |

### Agency Data Array Structure

Same as `wp_agency_agency_before_delete` (see above).

### Hard Delete vs Soft Delete

**Soft Delete** (`$is_hard_delete = false`):
- Record remains in database
- `status` field set to 'inactive'
- Data can be recovered
- Default behavior

**Hard Delete** (`$is_hard_delete = true`):
- Record removed from database (DELETE query)
- Data cannot be recovered
- Enabled via: `wp_agency_general_options['enable_hard_delete_branch'] = true`

### Use Cases

1. **Cascade Cleanup**: Delete related records in external systems
2. **Cache Invalidation**: Clear caches after deletion
3. **Post-deletion Logging**: Log successful deletion for audit
4. **Cleanup Resources**: Remove files, attachments, related data
5. **External Notification**: Notify external systems of deletion

### Example - Cascade Delete in External System

```php
add_action('wp_agency_agency_deleted', 'delete_agency_from_crm', 10, 3);

function delete_agency_from_crm($agency_id, $agency_data, $is_hard_delete) {
    // Only sync hard deletes to CRM
    if (!$is_hard_delete) {
        return; // Soft delete, keep in CRM
    }

    $response = wp_remote_request('https://crm.example.com/api/agencies/' . $agency_id, [
        'method' => 'DELETE',
        'headers' => [
            'Authorization' => 'Bearer YOUR_API_KEY'
        ],
        'timeout' => 15
    ]);

    if (is_wp_error($response)) {
        error_log('Failed to delete agency from CRM: ' . $response->get_error_message());
    }
}
```

### Example - Cleanup Related Files

```php
add_action('wp_agency_agency_deleted', 'cleanup_agency_files', 10, 3);

function cleanup_agency_files($agency_id, $agency_data, $is_hard_delete) {
    // Only cleanup files on hard delete
    if (!$is_hard_delete) {
        return;
    }

    $upload_dir = wp_upload_dir();
    $agency_dir = $upload_dir['basedir'] . '/agencies/' . $agency_id;

    if (is_dir($agency_dir)) {
        // Recursively delete directory
        delete_directory_recursive($agency_dir);

        error_log("Cleaned up agency files for ID: $agency_id");
    }
}

function delete_directory_recursive($dir) {
    if (!is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);

    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? delete_directory_recursive($path) : unlink($path);
    }

    rmdir($dir);
}
```

### Example - Audit Logging After Deletion

```php
add_action('wp_agency_agency_deleted', 'log_agency_deleted', 10, 3);

function log_agency_deleted($agency_id, $agency_data, $is_hard_delete) {
    $current_user = wp_get_current_user();

    $log_entry = sprintf(
        '[AUDIT] Agency %s: ID=%d, Name=%s, Code=%s, By User=%s (ID: %d)',
        $is_hard_delete ? 'HARD DELETED' : 'SOFT DELETED',
        $agency_id,
        $agency_data['name'],
        $agency_data['code'],
        $current_user->user_login,
        $current_user->ID
    );

    error_log($log_entry);

    // Save to audit log table
    do_action('wp_agency_audit_log', 'agency_deleted', [
        'entity_type' => 'agency',
        'entity_id' => $agency_id,
        'entity_name' => $agency_data['name'],
        'is_hard_delete' => $is_hard_delete,
        'user_id' => $current_user->ID,
        'data_snapshot' => json_encode($agency_data)
    ]);
}
```

### Related Hooks

- `wp_agency_agency_before_delete` - Before agency deletion (pairs with this hook)
- `wp_agency_division_deleted` - Child divisions deleted (cascade)
- `wp_agency_employee_deleted` - Child employees deleted (cascade)

### Notes

- Hook fires AFTER successful deletion
- For soft delete: record exists with status='inactive'
- For hard delete: record removed from database
- Cache already invalidated by model
- Use `$is_hard_delete` parameter to determine delete type
- Cannot prevent deletion at this point (use `before_delete` hook for that)

### Debugging

```php
add_action('wp_agency_agency_deleted', function($id, $agency_data, $is_hard_delete) {
    error_log(sprintf(
        '[HOOK] Agency deleted: ID=%d, Name=%s, Hard Delete=%s',
        $id,
        $agency_data['name'],
        $is_hard_delete ? 'YES' : 'NO'
    ));
}, 10, 3);
```

### Security Considerations

- Ensure proper cleanup of sensitive data on hard delete
- Log all deletions for audit trail
- Consider data retention policies
- Handle external system errors gracefully

### Performance Considerations

- Avoid blocking operations (use async tasks for heavy cleanup)
- Batch cleanup operations if deleting multiple agencies
- Use wp_schedule_single_event for file cleanup
- Monitor external API call timeouts

---

**See Also**:
- [Division Action Hooks](division-actions.md)
- [Employee Action Hooks](employee-actions.md)
- [Hook Examples](../examples/)
