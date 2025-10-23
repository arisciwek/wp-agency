# Division Action Hooks

Action hooks for Division entity lifecycle events in WP Agency plugin.

## Table of Contents

- [wp_agency_division_created](#wp_agency_division_created)
- [wp_agency_division_before_delete](#wp_agency_division_before_delete)
- [wp_agency_division_deleted](#wp_agency_division_deleted)

---

## wp_agency_division_created

**Fired When**: After a new division is successfully created and saved to database

**Location**: `src/Models/Division/DivisionModel.php:212`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$division_id` | int | The newly created division ID |
| `$division_data` | array | Division data array |

### Division Data Array Structure

```php
[
    'agency_id' => 1,                    // int - Parent agency ID
    'code' => 'DIV-001',                 // string - Unique division code
    'name' => 'Kabupaten Bandung',       // string - Division name
    'type' => 'cabang',                  // string - pusat|cabang
    'nitku' => '1234567890',             // string|null - NITKU number
    'postal_code' => '40251',            // string|null - Postal code
    'latitude' => -6.9147,               // float|null - Latitude
    'longitude' => 107.6098,             // float|null - Longitude
    'address' => 'Jl. Example',          // string|null - Address
    'phone' => '022123456',              // string|null - Phone number
    'email' => 'div@example.com',        // string|null - Email
    'provinsi_code' => '32',             // string|null - Province code
    'regency_code' => '3204',            // string|null - Regency code
    'user_id' => 130,                    // int|null - Division admin user ID
    'created_by' => 1,                   // int - Creator user ID
    'status' => 'active',                // string - active|inactive
    'created_at' => '2025-01-23 10:30:00', // string - MySQL datetime
    'updated_at' => '2025-01-23 10:30:00'  // string - MySQL datetime
]
```

### Use Cases

1. **Auto-create Employee**: Plugin uses this to auto-create division admin employee
2. **Audit Logging**: Log division creation for compliance
3. **External Integration**: Sync division data to external systems
4. **Notification**: Send notification to agency owner

### Example - Auto-create Division Admin Employee

This is the default handler registered by the plugin:

```php
add_action('wp_agency_division_created', [$auto_entity_creator, 'handleDivisionCreated'], 10, 2);

// In AutoEntityCreator class
public function handleDivisionCreated($division_id, $data) {
    if (empty($data['user_id'])) {
        return; // No user assigned, skip
    }

    // Check if employee already exists
    $existing = $this->employee_model->findByUserAndDivision($data['user_id'], $division_id);

    if ($existing) {
        return; // Already exists
    }

    // Get user data
    $user = get_userdata($data['user_id']);
    if (!$user) {
        return;
    }

    // Create employee as division admin
    $employee_data = [
        'agency_id' => $data['agency_id'],
        'division_id' => $division_id,
        'user_id' => $data['user_id'],
        'name' => $user->display_name,
        'position' => 'Admin ' . $data['name'],
        'email' => $user->user_email,
        'phone' => get_user_meta($data['user_id'], 'phone', true) ?: '',
        'created_by' => $data['created_by'] ?? get_current_user_id()
    ];

    $this->employee_model->create($employee_data);
}
```

### Related Hooks

- `wp_agency_agency_created` - Fired before this (auto-creates division pusat)
- `wp_agency_employee_created` - Fired after this (cascade from employee creation)
- `wp_agency_division_before_delete` - Before division deletion
- `wp_agency_division_deleted` - After division deletion

---

## wp_agency_division_before_delete

**Fired When**: Before a division is deleted from database

**Location**: `src/Models/Division/DivisionModel.php:367`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | int | Division ID to be deleted |
| `$division_data` | array | Complete division data array |

### Use Cases

1. **Prevent Deletion**: Stop deletion of division pusat (main division)
2. **Validation**: Check if division has active employees
3. **Pre-deletion Logging**: Log before deletion for audit

### Example - Prevent Pusat Deletion

```php
add_action('wp_agency_division_before_delete', 'prevent_pusat_deletion', 10, 2);

function prevent_pusat_deletion($division_id, $division_data) {
    if ($division_data['type'] === 'pusat') {
        wp_die(sprintf(
            'Cannot delete main division (Pusat) for agency "%s"!',
            $division_data['agency_name'] ?? 'Unknown'
        ));
    }
}
```

### Related Hooks

- `wp_agency_division_created` - After division creation
- `wp_agency_division_deleted` - After division deletion (pairs with this hook)

---

## wp_agency_division_deleted

**Fired When**: After a division is deleted from database (soft or hard delete)

**Location**: `src/Models/Division/DivisionModel.php:403`

**Version**: Since 1.0.0

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | int | Division ID that was deleted |
| `$division_data` | array | Complete division data array (before deletion) |
| `$is_hard_delete` | bool | True if hard deleted, false if soft deleted |

### Hard Delete vs Soft Delete

**Soft Delete** (`$is_hard_delete = false`):
- Record remains in database with `status='inactive'`
- Default behavior

**Hard Delete** (`$is_hard_delete = true`):
- Record removed from database (DELETE query)
- Enabled via: `wp_agency_general_options['enable_hard_delete_branch'] = true`

### Use Cases

1. **Cascade Cleanup**: Delete related employees and jurisdictions
2. **Cache Invalidation**: Clear caches after deletion
3. **Post-deletion Logging**: Log successful deletion for audit
4. **External Notification**: Notify external systems of deletion

### Example - Audit Logging

```php
add_action('wp_agency_division_deleted', 'log_division_deleted', 10, 3);

function log_division_deleted($division_id, $division_data, $is_hard_delete) {
    error_log(sprintf(
        '[AUDIT] Division %s: ID=%d, Name=%s, Type=%s',
        $is_hard_delete ? 'HARD DELETED' : 'SOFT DELETED',
        $division_id,
        $division_data['name'],
        $division_data['type']
    ));
}
```

### Related Hooks

- `wp_agency_division_before_delete` - Before division deletion (pairs with this hook)
- `wp_agency_employee_deleted` - Child employees deleted (cascade)

---

**See Also**:
- [Agency Action Hooks](agency-actions.md)
- [Employee Action Hooks](employee-actions.md)
- [Hook Examples](../examples/)
