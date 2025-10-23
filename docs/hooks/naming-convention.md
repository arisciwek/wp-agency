# Hook Naming Convention

Standard naming rules for all hooks in WP Agency plugin.

## Overview

The WP Agency plugin follows a consistent naming pattern for all action and filter hooks:

```
wp_agency_{entity}_{action}
```

This ensures:
- ✅ Predictable hook names
- ✅ Clear entity identification
- ✅ Scalable architecture
- ✅ Easy to remember and discover

## Action Hook Naming

### Pattern

```php
wp_agency_{entity}_{lifecycle_event}
```

### Entity Names

| Entity | Hook Prefix | Example |
|--------|-------------|---------|
| Agency | `wp_agency_agency_` | `wp_agency_agency_created` |
| Division | `wp_agency_division_` | `wp_agency_division_created` |
| Employee | `wp_agency_employee_` | `wp_agency_employee_created` |

### Lifecycle Events

| Event | When Fired | Example |
|-------|------------|---------|
| `created` | After entity created | `wp_agency_agency_created` |
| `before_delete` | Before entity deleted | `wp_agency_division_before_delete` |
| `deleted` | After entity deleted | `wp_agency_employee_deleted` |

### Complete Action Hook List

```php
// Agency Lifecycle
wp_agency_agency_created
wp_agency_agency_before_delete
wp_agency_agency_deleted

// Division Lifecycle
wp_agency_division_created
wp_agency_division_before_delete
wp_agency_division_deleted

// Employee Lifecycle
wp_agency_employee_created
wp_agency_employee_before_delete
wp_agency_employee_deleted
```

## Filter Hook Naming

### Permission Filters

Pattern: `wp_agency_can_{action}_{entity}`

```php
wp_agency_can_create_employee
wp_agency_can_create_division
```

### Configuration Filters

Pattern: `wp_agency_{config_name}`

```php
wp_agency_debug_mode
wp_agency_enable_export
wp_agency_max_inspector_assignments
```

### UI Filters

Pattern: `wp_{plugin}_{component}_{property}`

```php
wp_company_detail_tabs
```

## Naming Rules

### DO ✅

```php
// Good: Explicit entity name
wp_agency_agency_created

// Good: Clear lifecycle event
wp_agency_division_before_delete

// Good: Descriptive action
wp_agency_can_create_employee
```

### DON'T ❌

```php
// Bad: Missing entity (ambiguous)
wp_agency_created

// Bad: Inconsistent naming
wp_agency_new_division

// Bad: Using abbreviations
wp_agency_emp_created

// Bad: Too generic
wp_agency_before_delete
```

## Comparison with wp-customer Plugin

Both plugins follow the same naming convention:

### wp-customer Pattern

```php
wp_customer_customer_created
wp_customer_branch_created
wp_customer_employee_created
```

### wp-agency Pattern

```php
wp_agency_agency_created
wp_agency_division_created
wp_agency_employee_created
```

**Consistency Benefits**:
- Developers familiar with one plugin can easily understand the other
- External integrations can handle both plugins similarly
- Unified documentation structure

## Parameter Naming Convention

### Standard Parameters

**Created Hooks**: `($entity_id, $entity_data)`
```php
do_action('wp_agency_agency_created', $agency_id, $agency_data);
```

**Before Delete Hooks**: `($id, $entity_data)`
```php
do_action('wp_agency_division_before_delete', $id, $division_data);
```

**Deleted Hooks**: `($id, $entity_data, $is_hard_delete)`
```php
do_action('wp_agency_employee_deleted', $id, $employee_data, $is_hard_delete);
```

### Data Array Naming

Entity data arrays use consistent field naming:

```php
[
    'id' => 123,
    'code' => 'ABC001',
    'name' => 'Entity Name',
    'status' => 'active',
    'created_by' => 1,
    'created_at' => '2025-01-23 10:30:00',
    'updated_at' => '2025-01-23 10:30:00'
]
```

## Discovery Tips

### Finding Hooks

**Search for Actions**:
```bash
grep -r "do_action('wp_agency_" src/
```

**Search for Filters**:
```bash
grep -r "apply_filters('wp_agency_" src/
```

### Hook Documentation Location

All hooks are documented in:
- `docs/hooks/actions/` - Action hooks
- `docs/hooks/filters/` - Filter hooks
- `docs/hooks/README.md` - Complete index

---

**See Also**:
- [README](README.md) - Hook system overview
- [Action Hooks](actions/) - All action hooks
- [Filter Hooks](filters/) - All filter hooks
- [Examples](examples/) - Integration examples
