# WP Agency Plugin - Hooks Documentation

Complete reference for all action and filter hooks available in the WP Agency plugin.

## Table of Contents

- [Introduction](#introduction)
- [Hook Types](#hook-types)
- [Naming Convention](#naming-convention)
- [Available Hooks](#available-hooks)
  - [Action Hooks](#action-hooks)
  - [Filter Hooks](#filter-hooks)
- [Integration Examples](#integration-examples)

## Introduction

The WP Agency plugin provides a comprehensive hook system that allows external developers to:
- React to entity lifecycle events (creation, deletion)
- Modify permission checks
- Customize UI elements
- Integrate with external systems
- Extend functionality without modifying core code

## Hook Types

### Actions vs Filters

**Actions** - Event triggers that notify when something happens:
```php
// Example: React to agency creation
add_action('wp_agency_agency_created', function($agency_id, $agency_data) {
    // Send notification email
    // Sync to external CRM
    // Log audit trail
}, 10, 2);
```

**Filters** - Data modification that allows changing values:
```php
// Example: Modify permission check
add_filter('wp_agency_can_create_employee', function($can_create, $agency_id, $division_id, $user_id) {
    // Add custom logic
    return $can_create || custom_permission_check($user_id);
}, 10, 4);
```

## Naming Convention

All hooks follow the pattern: `wp_agency_{entity}_{action}`

**Entity Names**:
- `agency` - Agency entity
- `division` - Division entity (branch/unit)
- `employee` - Employee entity

**Action Types**:
- `created` - After entity created
- `before_delete` - Before entity deletion (can prevent)
- `deleted` - After entity deleted (with hard delete flag)

**Example Hooks**:
```php
wp_agency_agency_created           // Agency created
wp_agency_division_before_delete   // Before division deletion
wp_agency_employee_deleted         // Employee deleted
```

## Available Hooks

### Action Hooks

#### Agency Lifecycle (3 hooks)
| Hook | Parameters | Description |
|------|------------|-------------|
| `wp_agency_agency_created` | `($agency_id, $agency_data)` | Fired after agency created |
| `wp_agency_agency_before_delete` | `($id, $agency_data)` | Before agency deletion (validation) |
| `wp_agency_agency_deleted` | `($id, $agency_data, $is_hard_delete)` | After agency deleted |

#### Division Lifecycle (3 hooks)
| Hook | Parameters | Description |
|------|------------|-------------|
| `wp_agency_division_created` | `($division_id, $division_data)` | Fired after division created |
| `wp_agency_division_before_delete` | `($id, $division_data)` | Before division deletion (validation) |
| `wp_agency_division_deleted` | `($id, $division_data, $is_hard_delete)` | After division deleted |

#### Employee Lifecycle (3 hooks)
| Hook | Parameters | Description |
|------|------------|-------------|
| `wp_agency_employee_created` | `($employee_id, $employee_data)` | Fired after employee created |
| `wp_agency_employee_before_delete` | `($id, $employee_data)` | Before employee deletion (validation) |
| `wp_agency_employee_deleted` | `($id, $employee_data, $is_hard_delete)` | After employee deleted |

**Total Action Hooks**: 9

### Filter Hooks

#### Permission Filters (3 hooks)
| Hook | Parameters | Return | Description |
|------|------------|--------|-------------|
| `wp_agency_can_create_employee` | `($can_create, $agency_id, $division_id, $user_id)` | bool | Override employee creation permission |
| `wp_agency_can_create_division` | `($can_create, $agency_id, $user_id)` | bool | Override division creation permission |
| `wp_agency_max_inspector_assignments` | none | int | Maximum inspector assignments |

#### UI/UX Filters (2 hooks)
| Hook | Parameters | Return | Description |
|------|------------|--------|-------------|
| `wp_agency_enable_export` | none | bool | Enable/disable export button |
| `wp_company_detail_tabs` | `($tabs)` | array | Add/remove company detail tabs |

#### System Filters (1 hook)
| Hook | Parameters | Return | Description |
|------|------------|--------|-------------|
| `wp_agency_debug_mode` | none | bool | Enable debug logging |

#### External Integration Filters (2 hooks)
| Hook | Parameters | Return | Description |
|------|------------|--------|-------------|
| `wilayah_indonesia_get_province_options` | `($options)` | array | Get province dropdown options |
| `wilayah_indonesia_get_regency_options` | `($options, $province_id)` | array | Get regency dropdown options |

**Total Filter Hooks**: 8

**Grand Total**: 17 Hooks (9 Actions + 8 Filters)

## Integration Examples

### Example 1: Auto-create Division on Agency Creation
```php
add_action('wp_agency_agency_created', 'auto_create_division_pusat', 10, 2);

function auto_create_division_pusat($agency_id, $agency_data) {
    // Division "Pusat" automatically created by AutoEntityCreator handler
    // This is just an example of what you could do
    error_log("Agency {$agency_data['name']} created with ID: {$agency_id}");
}
```

### Example 2: Prevent Division Deletion
```php
add_action('wp_agency_division_before_delete', 'prevent_pusat_deletion', 10, 2);

function prevent_pusat_deletion($division_id, $division_data) {
    if ($division_data['type'] === 'pusat') {
        wp_die('Cannot delete main division (Pusat)!');
    }
}
```

### Example 3: Custom Permission Logic
```php
add_filter('wp_agency_can_create_employee', 'custom_employee_permission', 10, 4);

function custom_employee_permission($can_create, $agency_id, $division_id, $user_id) {
    // Allow creation only during business hours
    $current_hour = (int) current_time('H');

    if ($current_hour < 8 || $current_hour > 17) {
        return false; // Block outside business hours
    }

    return $can_create;
}
```

## Documentation Files

- **[naming-convention.md](naming-convention.md)** - Detailed naming rules
- **[actions/](actions/)** - Action hooks documentation
  - [agency-actions.md](actions/agency-actions.md) - Agency lifecycle hooks
  - [division-actions.md](actions/division-actions.md) - Division lifecycle hooks
  - [employee-actions.md](actions/employee-actions.md) - Employee lifecycle hooks
- **[filters/](filters/)** - Filter hooks documentation
  - [permission-filters.md](filters/permission-filters.md) - Permission override filters
  - [ui-filters.md](filters/ui-filters.md) - UI customization filters
  - [system-filters.md](filters/system-filters.md) - System configuration filters
- **[examples/](examples/)** - Real-world integration examples

## Support

For questions or issues:
- Check the [examples](examples/) directory for real-world use cases
- Review the detailed hook documentation in [actions/](actions/) and [filters/](filters/)
- Open an issue on the plugin repository

---

**Version**: 1.0.0
**Last Updated**: 2025-01-23
