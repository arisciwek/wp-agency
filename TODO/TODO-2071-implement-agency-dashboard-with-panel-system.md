# TODO-2071: Implement Agency Dashboard with Panel System

**Status**: 🔵 Ready to Start
**Priority**: High
**Plugin**: wp-agency
**Created**: 2025-10-23
**Context**: Integration testing for TODO-2179 (Base Panel System)

**Dependencies**:
- ✅ TODO-2179 (Base Panel/Dashboard System - Phase 1-7 COMPLETED)
- ✅ TODO-2178 (Backend DataTable System - COMPLETED)
- ✅ TODO-2174 (Companies DataTable Pattern - COMPLETED)
- ✅ SQL Query Verification (COMPLETED - 2025-10-23)

---

## 📋 Overview

Implementasi **Agency Dashboard** ("Disnaker") menggunakan base panel system dari wp-app-core (TODO-2179).

**Key Features**:
- Main DataTable: List Agencies with filter by user permissions
- Right Panel with 3 Tabs:
  - Tab 1: Agency Details (loaded immediately)
  - Tab 2: Divisions DataTable (lazy load on click)
  - Tab 3: Agency Employees DataTable (lazy load on click)
- Cross-plugin hook integration (wp-customer filters wp-agency data)
- Permission-based access via hooks (no hard-coded access_type)

**This serves as**:
- **Integration testing** for base panel system
- **Real-world implementation** of TODO-2179
- **Cross-plugin hook pattern** demonstration

---

## 🔗 Context & References

### Related TODOs:

1. **TODO-2179** (wp-app-core) - Base Panel System ✅ Phase 1-7 COMPLETED
   - Path: `/wp-app-core/TODO/TODO-2179-implement-base-panel-dashboard-system.md`
   - Delivered: DashboardTemplate, PanelLayout, TabSystem, StatsBox, CSS, JS, Assets Controller
   - Result: 11 files created, 4,500+ lines, 77% complete
   - **This TODO is Phase 8 testing!**

2. **TODO-2178** (wp-app-core) - Backend DataTable System ✅ COMPLETED
   - Path: `/wp-app-core/TODO/TODO-2178-implement-base-datatable-system.md`
   - Delivered: DataTableModel, QueryBuilder, Controller, Hook System

3. **TODO-2174** (wp-customer) - Companies Implementation ✅ COMPLETED
   - Path: `/wp-customer/TODO/TODO-2174-implement-companies-datatable.md`
   - Delivered: Companies DataTable with hook-based permissions
   - **Pattern Reference**: We follow this pattern exactly!

### Referenced Files:

**Database Tables**:
- `/wp-agency/src/Database/Tables/AgencysDB.php` (Target table)
- `/wp-agency/src/Database/Tables/DivisionsDB.php` (Tab 2 data)
- `/wp-agency/src/Database/Tables/AgencyEmployeesDB.php` (Tab 3 data)
- `/wp-customer/src/Database/Tables/CustomersDB.php` (For permissions)
- `/wp-customer/src/Database/Tables/BranchesDB.php` (Bridge table)
- `/wp-customer/src/Database/Tables/CustomerEmployeesDB.php` (User context)

**Pattern Reference**:
- `/wp-agency/src/Models/Company/NewCompanyModel.php` (JOIN pattern)
- `/wp-customer/TODO/TODO-2174-implement-companies-datatable.md` (Implementation guide)

**Documentation**:
- `/wp-app-core/src/Views/DataTable/README.md` (Base panel system guide)
- `/wp-app-core/src/Views/DataTable/MIGRATION-EXAMPLE.md` (Real migration example)
- `/wp-app-core/src/Views/DataTable/QUICK-REFERENCE.md` (Cheatsheet)

---

## ✅ SQL Query Verification (2025-10-23)

**Status**: VERIFIED ✅

### Test Query: User to Agency Access
```sql
SELECT
    a.id,
    a.code,
    a.name as agency_name,
    a.status,
    COUNT(DISTINCT b.id) as total_branches,
    GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as customer_names,
    GROUP_CONCAT(DISTINCT b.name SEPARATOR ', ') as branch_names
FROM wp_app_agencies a
INNER JOIN wp_app_customer_branches b ON a.id = b.agency_id
INNER JOIN wp_app_customer_employees ce ON b.id = ce.branch_id
INNER JOIN wp_app_customers c ON b.customer_id = c.id
WHERE ce.user_id = ?  -- User yang login
AND a.status = 'active'
GROUP BY a.id, a.code, a.name, a.status;
```

### Test Results:
```
user_id=2 (Andi Budi) can access:
- Agency: Disnaker Provinsi Maluku (ID: 18)
- Via Customer: PT Maju Bersama
- Via Branch: PT Maju Bersama Cabang Kabupaten Maluku Tenggara
- Total accessible agencies: 1
- Total accessible branches: 1
```

**Access Pattern Verified**:
- ✅ CustomerEmployee → Branch → Agency (1:1:1 for Branch Admin)
- ✅ Multiple employees can access same agency (via different branches)
- ✅ Query performance: < 50ms for typical dataset
- ✅ JOIN structure correct
- ✅ GROUP BY prevents duplicates

---

## 🎯 Goals & Requirements

### Primary Goals:
1. **Test Base Panel System** from TODO-2179 in real implementation
2. **Implement Agency Dashboard** using base templates
3. **Cross-plugin integration** (wp-customer filters wp-agency)
4. **Hook-based permissions** (no access_type pattern)
5. **Lazy-loading tabs** (Perfex CRM pattern)

### Technical Requirements:
- Use `DashboardTemplate::render()` from wp-app-core
- Implement all 3 tabs with proper lazy loading
- Filter agencies based on user's customer branches
- Fire action hooks on CRUD operations
- Apply filter hooks for permissions & data filtering
- Support multiple user roles (Customer Admin, Branch Admin, Employee)

### UI Requirements:
- Statistics boxes: Total Agencies, Active, Inactive
- DataTable with: Code, Name, Province, Regency, Status, Actions
- Right panel opens smoothly (no flicker)
- Tabs switch without page reload
- Divisions & Employees DataTables in tabs 2 & 3

---

## 📊 Database Relations Diagram

```
┌─────────────┐
│  wp_users   │ (Logged User)
└──────┬──────┘
       │ user_id
       ↓
┌────────────────────────┐
│ CustomerEmployee       │ (User Context)
│ - user_id              │
│ - customer_id          │
│ - branch_id            │◄─── Determines access scope
└────────┬───────────────┘
         │ branch_id
         ↓
┌────────────────────────┐
│ Branch                 │ (Bridge Table)
│ - id                   │
│ - customer_id          │
│ - agency_id            │◄─── KEY FILTER POINT
│ - division_id          │
│ - inspector_id         │
└────────┬───────────────┘
         │ agency_id
         ↓
┌────────────────────────┐
│ Agency                 │ ⭐ TARGET TABLE
│ - id                   │
│ - code                 │
│ - name                 │
│ - status               │
│ - provinsi_code        │
│ - regency_code         │
└────────┬───────────────┘
         │
         ├─────┐ agency_id
         │     ↓
         │  ┌───────────────┐
         │  │ Division      │ (Tab 2 Data)
         │  │ - agency_id   │
         │  │ - code        │
         │  │ - name        │
         │  └───────────────┘
         │
         └─────┐ agency_id
               ↓
            ┌─────────────────┐
            │ AgencyEmployee  │ (Tab 3 Data)
            │ - agency_id     │
            │ - division_id   │
            │ - user_id       │
            │ - name          │
            └─────────────────┘
```

**Permission Logic**:
```
IF user has CustomerEmployee record:
    IF branch_id IS NULL:
        → Customer Admin (access all agencies from customer's branches)
    ELSE:
        → Branch Admin (access 1 agency from that branch)
ELSE:
    → Platform/Agency user (different logic, not in this TODO)
```

---

## 📦 Tasks Breakdown

### Phase 1: Model Layer (Backend - 2 days)

#### Task 1.1: Create AgencyDataTableModel

**File**: `/wp-agency/src/Models/Agency/AgencyDataTableModel.php`

**Purpose**: Handle DataTable server-side processing for agencies

**Extends**: `WPAppCore\Models\DataTable\DataTableModel`

**Table**: `wp_app_agencies`

**Columns**:
```php
[
    'code' => [
        'data' => 'code',
        'title' => 'Code',
        'searchable' => true,
        'orderable' => true
    ],
    'name' => [
        'data' => 'name',
        'title' => 'Nama Disnaker',
        'searchable' => true,
        'orderable' => true
    ],
    'provinsi_name' => [
        'data' => 'provinsi_name',
        'title' => 'Provinsi',
        'searchable' => true,
        'orderable' => true
    ],
    'regency_name' => [
        'data' => 'regency_name',
        'title' => 'Kabupaten/Kota',
        'searchable' => true,
        'orderable' => true
    ],
    'status' => [
        'data' => 'status',
        'title' => 'Status',
        'searchable' => false,
        'orderable' => true
    ],
    'actions' => [
        'data' => 'actions',
        'title' => 'Actions',
        'searchable' => false,
        'orderable' => false
    ]
]
```

**JOINs**:
```php
protected function get_joins(): array {
    return [
        [
            'type' => 'LEFT',
            'table' => 'wi_provinces p',
            'on' => 'a.provinsi_code = p.code'
        ],
        [
            'type' => 'LEFT',
            'table' => 'wi_regencies r',
            'on' => 'a.regency_code = r.code'
        ]
    ];
}
```

**format_row() Implementation**:
```php
protected function format_row($row): array {
    return [
        'DT_RowId' => 'agency-' . $row->id,
        'DT_RowData' => [
            'id' => $row->id
        ],
        'code' => esc_html($row->code),
        'name' => esc_html($row->name),
        'provinsi_name' => esc_html($row->provinsi_name ?? '-'),
        'regency_name' => esc_html($row->regency_name ?? '-'),
        'status' => $this->format_status_badge($row->status),
        'actions' => $this->generate_action_buttons($row)
    ];
}
```

**Checklist**:
- [ ] Create class extending DataTableModel
- [ ] Define columns array
- [ ] Implement get_joins() with provinces & regencies
- [ ] Implement format_row() with proper escaping
- [ ] Add format_status_badge() helper
- [ ] Add generate_action_buttons() helper
- [ ] Add PHPDoc comments
- [ ] Test with sample data

---

#### Task 1.2: Create AgencyModel (CRUD)

**File**: `/wp-agency/src/Models/Agency/AgencyModel.php`

**Purpose**: Handle CRUD operations for agencies

**Methods**:
```php
public function find(int $id): ?object
public function findByCode(string $code): ?object
public function create(array $data): int
public function update(int $id, array $data): bool
public function delete(int $id): bool
public function exists(int $id): bool
```

**Action Hooks to Fire**:
- `wp_agency_agency_created` - After create
- `wp_agency_agency_updated` - After update
- `wp_agency_agency_before_delete` - Before delete
- `wp_agency_agency_deleted` - After delete

**Checklist**:
- [ ] Create class with WPDB integration
- [ ] Implement find() with caching
- [ ] Implement create() with validation
- [ ] Implement update() with cache invalidation
- [ ] Implement delete() with cascade check
- [ ] Fire action hooks at appropriate points
- [ ] Add error handling
- [ ] Add PHPDoc comments

---

### Phase 2: Controller Layer (Backend - 2 days)

#### Task 2.1: Create AgencyController

**File**: `/wp-agency/src/Controllers/Agency/AgencyController.php`

**Purpose**: Handle AJAX requests for agency DataTable

**Methods**:
```php
public function handle_datatable_request(): void
public function handle_get_agency(): void
public function handle_create_agency(): void
public function handle_update_agency(): void
public function handle_delete_agency(): void
```

**AJAX Actions to Register**:
- `wp_ajax_get_agencies_datatable`
- `wp_ajax_get_agency_details`
- `wp_ajax_create_agency`
- `wp_ajax_update_agency`
- `wp_ajax_delete_agency`
- `wp_ajax_get_agency_stats`

**Checklist**:
- [ ] Create controller class
- [ ] Implement handle_datatable_request()
- [ ] Implement handle_get_agency() (for panel)
- [ ] Implement CRUD handlers
- [ ] Implement handle_get_agency_stats()
- [ ] Add nonce verification
- [ ] Add permission checks
- [ ] Register AJAX actions
- [ ] Add error handling
- [ ] Test all endpoints

---

#### Task 2.2: Create AgencyDashboardController

**File**: `/wp-agency/src/Controllers/Agency/AgencyDashboardController.php`

**Purpose**: Register hooks for dashboard components

**Following TODO-2174 Pattern & wp-app-core/MIGRATION-EXAMPLE.md**

**Hooks to Register**:
```php
// Panel content
add_action('wpapp_left_panel_content', [$this, 'render_datatable'], 10, 1);

// Statistics
add_filter('wpapp_datatable_stats', [$this, 'register_stats'], 10, 2);

// Tabs
add_filter('wpapp_datatable_tabs', [$this, 'register_tabs'], 10, 2);

// AJAX handlers
add_action('wp_ajax_get_agency_details', [$this, 'handle_ajax_get_details']);
add_action('wp_ajax_get_agency_stats', [$this, 'handle_ajax_get_stats']);

// Tab lazy loading
add_action('wp_ajax_load_divisions_tab', [$this, 'handle_load_divisions_tab']);
add_action('wp_ajax_load_employees_tab', [$this, 'handle_load_employees_tab']);
```

**register_tabs() Implementation**:
```php
public function register_tabs($tabs, $entity) {
    if ($entity !== 'agency') {
        return $tabs;
    }

    return [
        'agency-details' => [
            'title' => __('Data Disnaker', 'wp-agency'),
            'template' => WP_AGENCY_PATH . 'src/Views/agency/tabs/details.php',
            'priority' => 10
        ],
        'divisions' => [
            'title' => __('Unit Kerja', 'wp-agency'),
            'template' => WP_AGENCY_PATH . 'src/Views/agency/tabs/divisions.php',
            'priority' => 20
        ],
        'employees' => [
            'title' => __('Staff', 'wp-agency'),
            'template' => WP_AGENCY_PATH . 'src/Views/agency/tabs/employees.php',
            'priority' => 30
        ]
    ];
}
```

**Checklist**:
- [ ] Create controller class
- [ ] Implement render_datatable()
- [ ] Implement register_stats()
- [ ] Implement register_tabs()
- [ ] Implement handle_ajax_get_details()
- [ ] Implement handle_ajax_get_stats()
- [ ] Implement lazy load handlers for tabs 2 & 3
- [ ] Initialize in main plugin file

---

### Phase 3: Validator Layer (Backend - 1 day)

#### Task 3.1: Create AgencyValidator

**File**: `/wp-agency/src/Validators/Agency/AgencyValidator.php`

**Purpose**: Validate permissions and data

**Permission Methods**:
```php
public function canAccessAgenciesPage(): bool
public function canViewAgency(int $agency_id): bool
public function canCreateAgency(): bool
public function canEditAgency(int $agency_id): bool
public function canDeleteAgency(int $agency_id): bool
```

**Filter Hooks to Apply**:
```php
// Base capability check
$can_access = current_user_can('view_agencies');

// Apply filter
$can_access = apply_filters('wp_agency_can_access_agencies_page', $can_access, $context);
```

**Checklist**:
- [ ] Create validator class
- [ ] Implement permission methods
- [ ] Apply filter hooks (allow wp-customer to extend)
- [ ] Add data validation methods
- [ ] Add PHPDoc comments
- [ ] Test permission logic

---

### Phase 4: View Layer (Frontend - 2 days)

#### Task 4.1: Create Dashboard View

**File**: `/wp-agency/src/Views/agency/dashboard.php`

**Following wp-app-core/MIGRATION-EXAMPLE.md Pattern**

**Implementation** (Only 7 lines!):
```php
<?php
/**
 * Agency Dashboard - Disnaker
 *
 * @package WP_Agency
 */

use WPAppCore\Views\DataTable\Templates\DashboardTemplate;

defined('ABSPATH') || exit;

DashboardTemplate::render([
    'entity' => 'agency',
    'title' => __('Disnaker', 'wp-agency'),
    'ajax_action' => 'get_agency_details',
    'has_stats' => true,
    'has_tabs' => true,
]);
```

**That's it! Base panel system handles:**
- ✅ Panel layout (left/right)
- ✅ Smooth animations
- ✅ Hash navigation
- ✅ Tab system
- ✅ Statistics boxes
- ✅ Close button
- ✅ Responsive design

**Checklist**:
- [ ] Create dashboard.php (7 lines)
- [ ] Verify entity name consistency ('agency')
- [ ] Test rendering

---

#### Task 4.2: Create DataTable HTML

**File**: `/wp-agency/src/Views/agency/datatable.php`

**Purpose**: DataTable HTML structure

**Implementation**:
```php
<?php
defined('ABSPATH') || exit;
?>

<table id="agency-list-table" class="wpapp-datatable">
    <thead>
        <tr>
            <th><?php esc_html_e('Code', 'wp-agency'); ?></th>
            <th><?php esc_html_e('Nama Disnaker', 'wp-agency'); ?></th>
            <th><?php esc_html_e('Provinsi', 'wp-agency'); ?></th>
            <th><?php esc_html_e('Kabupaten/Kota', 'wp-agency'); ?></th>
            <th><?php esc_html_e('Status', 'wp-agency'); ?></th>
            <th><?php esc_html_e('Actions', 'wp-agency'); ?></th>
        </tr>
    </thead>
    <tbody>
        <!-- DataTables will populate via AJAX -->
    </tbody>
</table>

<script>
jQuery(document).ready(function($) {
    $('#agency-list-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: ajaxurl,
            type: 'POST',
            data: function(d) {
                d.action = 'get_agencies_datatable';
                d.nonce = wpAgency.nonce;
                return d;
            }
        },
        columns: [
            { data: 'code' },
            { data: 'name' },
            { data: 'provinsi_name' },
            { data: 'regency_name' },
            { data: 'status' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });
});
</script>
```

**Checklist**:
- [ ] Create datatable.php
- [ ] Define table structure
- [ ] Init DataTable with correct columns
- [ ] Test AJAX loading

---

#### Task 4.3: Create Tab Templates

**Tab 1: Agency Details (Immediate Load)**

**File**: `/wp-agency/src/Views/agency/tabs/details.php`

**Content**: Agency information display (name, code, address, etc.)

**Tab 2: Divisions (Lazy Load)**

**File**: `/wp-agency/src/Views/agency/tabs/divisions.php`

**Content**:
```php
<div id="divisions-tab-content">
    <!-- Loading state -->
    <div class="wpapp-tab-loading">
        <span class="spinner is-active"></span>
        <p><?php esc_html_e('Loading divisions...', 'wp-agency'); ?></p>
    </div>

    <!-- DataTable will be inserted here via AJAX -->
</div>

<script>
jQuery(document).ready(function($) {
    // Lazy load on tab switch
    $(document).on('wpapp:tab-switched', function(e, data) {
        if (data.tabId !== 'divisions') return;
        if ($('#divisions-tab-content').data('loaded')) return;

        // Load divisions DataTable via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'load_divisions_tab',
                agency_id: data.id,
                nonce: wpAgency.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#divisions-tab-content').html(response.data.html);

                    // Init DataTable (Perfex CRM pattern)
                    init_datatable();

                    $('#divisions-tab-content').data('loaded', true);
                }
            }
        });
    });
});
</script>
```

**Tab 3: Employees (Lazy Load)**

**File**: `/wp-agency/src/Views/agency/tabs/employees.php`

**Content**: Similar to Tab 2, but for agency employees

**Checklist**:
- [ ] Create details.php (immediate load)
- [ ] Create divisions.php (lazy load with DataTable)
- [ ] Create employees.php (lazy load with DataTable)
- [ ] Test lazy loading behavior
- [ ] Test DataTable initialization in tabs

---

### Phase 5: Hook System Implementation (Backend - 2 days)

#### Task 5.1: Implement Action Hooks

**Following TODO-2174 Pattern**

**Action Hooks to Fire**:
1. `wp_agency_agency_created`
2. `wp_agency_agency_updated`
3. `wp_agency_agency_before_delete`
4. `wp_agency_agency_deleted`

**Checklist**:
- [ ] Add do_action() calls in AgencyModel
- [ ] Document parameters in PHPDoc
- [ ] Test hooks fire correctly

---

#### Task 5.2: Implement Filter Hooks

**Permission Filters** (wp-agency provides):
1. `wp_agency_can_access_agencies_page`
2. `wp_agency_can_view_agency`
3. `wp_agency_can_create_agency`
4. `wp_agency_can_edit_agency`
5. `wp_agency_can_delete_agency`

**DataTable Filters** (wp-app-core provides, wp-customer uses):
6. `wpapp_datatable_app_agencies_columns`
7. `wpapp_datatable_app_agencies_where` ⭐ KEY HOOK
8. `wpapp_datatable_app_agencies_joins`
9. `wpapp_datatable_app_agencies_row_data`

**Checklist**:
- [ ] Add apply_filters() in validator
- [ ] Add apply_filters() in DataTableModel (inherited)
- [ ] Document all filters
- [ ] Test filter application

---

### Phase 6: Cross-Plugin Integration (wp-customer - 2 days)

#### Task 6.1: Create Customer Agency Access Filter

**File**: `/wp-customer/src/Integrations/AgencyAccessFilter.php`

**Purpose**: Filter wp-agency data based on customer permissions

**Key Implementation**:
```php
<?php
namespace WPCustomer\Integrations;

class AgencyAccessFilter {

    public function __construct() {
        // Hook into wp-agency DataTable WHERE filter
        add_filter('wpapp_datatable_app_agencies_where', [$this, 'filter_agencies_by_customer'], 10, 3);

        // Hook into wp-agency permission filters
        add_filter('wp_agency_can_view_agency', [$this, 'check_customer_agency_permission'], 10, 2);
    }

    /**
     * Filter agencies based on customer's branches
     *
     * This is the KEY filter that implements the SQL query we verified!
     */
    public function filter_agencies_by_customer($where, $request, $model) {
        global $wpdb;

        $user_id = get_current_user_id();

        // Check if user is customer employee
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}app_customer_employees WHERE user_id = %d",
            $user_id
        ));

        if (!$employee) {
            return $where; // Not a customer employee, no filtering
        }

        // Get accessible agency IDs via branches
        $accessible_agencies = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT b.agency_id
             FROM {$wpdb->prefix}app_customer_branches b
             INNER JOIN {$wpdb->prefix}app_customer_employees ce ON b.id = ce.branch_id
             WHERE ce.user_id = %d
             AND b.agency_id IS NOT NULL",
            $user_id
        ));

        if (empty($accessible_agencies)) {
            // User has no accessible agencies, block all
            $where[] = "1=0";
        } else {
            // Filter to accessible agencies only
            $ids = implode(',', array_map('intval', $accessible_agencies));
            $where[] = "a.id IN ({$ids})";
        }

        return $where;
    }

    /**
     * Check if customer employee can view specific agency
     */
    public function check_customer_agency_permission($can_view, $agency_id) {
        global $wpdb;

        $user_id = get_current_user_id();

        // Check if user has access to this agency via branches
        $has_access = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->prefix}app_customer_branches b
             INNER JOIN {$wpdb->prefix}app_customer_employees ce ON b.id = ce.branch_id
             WHERE ce.user_id = %d
             AND b.agency_id = %d",
            $user_id,
            $agency_id
        ));

        if ($has_access > 0) {
            return true; // Customer employee can view this agency
        }

        return $can_view; // Fallback to original permission
    }
}

// Initialize
new AgencyAccessFilter();
```

**Checklist**:
- [ ] Create AgencyAccessFilter class in wp-customer
- [ ] Implement filter_agencies_by_customer() using verified SQL
- [ ] Implement check_customer_agency_permission()
- [ ] Initialize in wp-customer main file
- [ ] Test filtering with different users
- [ ] Test permission checks

---

#### Task 6.2: Create Menu Page Registration

**File**: `/wp-agency/src/Controllers/MenuController.php` (update existing)

**Add Menu**:
```php
add_menu_page(
    __('Disnaker', 'wp-agency'),
    __('Disnaker', 'wp-agency'),
    'view_agencies', // Base capability
    'wp-agency-disnaker',
    function() {
        include WP_AGENCY_PATH . 'src/Views/agency/dashboard.php';
    },
    'dashicons-building',
    30
);
```

**Register Page Hook for Assets**:
```php
add_filter('wpapp_datatable_allowed_hooks', function($hooks) {
    $hooks[] = 'toplevel_page_wp-agency-disnaker';
    return $hooks;
});
```

**Checklist**:
- [ ] Add menu page
- [ ] Set correct capability
- [ ] Register page hook for assets
- [ ] Test menu appears
- [ ] Test page loads

---

### Phase 7: Documentation (1 day)

#### Task 7.1: Document Hooks

**Files to Create/Update**:
- `/wp-agency/docs/hooks/actions/agency-actions.md`
- `/wp-agency/docs/hooks/filters/permission-filters.md`
- `/wp-agency/docs/hooks/README.md`

**Document**:
- All action hooks
- All permission filter hooks
- Cross-plugin integration example
- Usage examples

**Checklist**:
- [ ] Document action hooks
- [ ] Document filter hooks
- [ ] Add cross-plugin example
- [ ] Update README index

---

#### Task 7.2: Create Integration Guide

**File**: `/wp-agency/docs/integration/wp-customer-integration.md`

**Content**:
- How wp-customer filters wp-agency data
- SQL query explanation
- Permission flow diagram
- Testing different user scenarios

**Checklist**:
- [ ] Create integration guide
- [ ] Add SQL query examples
- [ ] Add permission flow diagram
- [ ] Add testing scenarios

---

### Phase 8: Testing (2 days)

#### Task 8.1: Unit Testing

**Test Scenarios**:
- AgencyDataTableModel columns defined correctly
- AgencyModel CRUD operations work
- AgencyValidator permissions work
- Hooks fire at correct times
- Filters apply correctly

**Checklist**:
- [ ] Test model methods
- [ ] Test controller endpoints
- [ ] Test validator logic
- [ ] Test hook execution
- [ ] Test filter application

---

#### Task 8.2: Integration Testing

**Test Scenarios**:
1. **Platform Admin**: Sees all agencies
2. **Customer Admin**: Sees agencies from all their branches
3. **Branch Admin**: Sees 1 agency from their branch
4. **Employee**: Same as Branch Admin
5. **No Access**: Cannot access page

**Test Flow**:
```
1. Login as Customer Admin (user_id=2)
2. Navigate to Disnaker menu
3. Should see filtered agencies only
4. Click agency row
5. Right panel opens smoothly
6. Tab 1 shows details immediately
7. Click Tab 2 (Divisions)
8. Loading spinner shows
9. DataTable loads via AJAX
10. Click Tab 3 (Employees)
11. Loading spinner shows
12. DataTable loads via AJAX
13. Close panel
14. Panel closes smoothly
15. Hash cleared from URL
```

**Checklist**:
- [ ] Test with Platform Admin
- [ ] Test with Customer Admin (multiple branches)
- [ ] Test with Branch Admin (single branch)
- [ ] Test unauthorized access
- [ ] Test DataTable AJAX loading
- [ ] Test panel open/close
- [ ] Test tab switching
- [ ] Test lazy loading tabs 2 & 3
- [ ] Test hash navigation
- [ ] Test browser back/forward
- [ ] Test responsive design
- [ ] Test error handling

---

#### Task 8.3: Cross-Plugin Testing

**Test wp-customer Integration**:
- [ ] AgencyAccessFilter initializes correctly
- [ ] WHERE filter applies to DataTable
- [ ] User sees only accessible agencies
- [ ] Permission check works for individual agency
- [ ] No SQL errors
- [ ] Performance acceptable (< 500ms)

**Test Data Consistency**:
- [ ] Agency data matches verified SQL query results
- [ ] Filtering logic matches TODO-2174 pattern
- [ ] No data leakage between users

**Checklist**:
- [ ] Test filter application
- [ ] Test with multiple user types
- [ ] Test performance
- [ ] Test edge cases (no branches, no agencies, etc.)

---

### Phase 9: Cleanup & Polish (1 day)

#### Task 9.1: Code Quality

**Checklist**:
- [ ] Add/verify PHPDoc comments
- [ ] Check coding standards (PHPCS)
- [ ] Remove debug logs
- [ ] Optimize queries
- [ ] Add error handling everywhere
- [ ] Security audit (nonce, capability, sanitize, escape)

---

#### Task 9.2: UI/UX Polish

**Checklist**:
- [ ] Test all animations smooth
- [ ] Check responsive on mobile
- [ ] Verify loading states clear
- [ ] Test error messages helpful
- [ ] Check success messages shown
- [ ] Verify tab transitions smooth

---

#### Task 9.3: Performance Optimization

**Checklist**:
- [ ] Check query performance (EXPLAIN)
- [ ] Add database indexes if needed
- [ ] Implement caching where appropriate
- [ ] Optimize AJAX responses
- [ ] Test with large datasets (1000+ agencies)

---

## 🔧 Technical Implementation Notes

### Hook Pattern: wp-agency provides, wp-customer extends

**wp-agency responsibility**:
```php
// Provide base hooks
apply_filters('wp_agency_can_view_agency', $can_view, $agency_id);
apply_filters('wpapp_datatable_app_agencies_where', $where, $request, $model);
```

**wp-customer responsibility**:
```php
// Hook into wp-agency filters
add_filter('wpapp_datatable_app_agencies_where', 'filter_by_branches', 10, 3);
add_filter('wp_agency_can_view_agency', 'check_branch_access', 10, 2);
```

**Benefits**:
- ✅ Loose coupling between plugins
- ✅ wp-agency works standalone
- ✅ wp-customer adds filtering when active
- ✅ Other plugins can also extend (wp-inspector, etc.)

---

### Directory Structure

```
wp-agency/
├── src/
│   ├── Models/
│   │   └── Agency/
│   │       ├── AgencyModel.php              # CRUD operations
│   │       └── AgencyDataTableModel.php     # DataTable logic
│   │
│   ├── Controllers/
│   │   └── Agency/
│   │       ├── AgencyController.php         # AJAX handlers
│   │       └── AgencyDashboardController.php # Hook registration
│   │
│   ├── Validators/
│   │   └── Agency/
│   │       └── AgencyValidator.php          # Permissions
│   │
│   └── Views/
│       └── agency/
│           ├── dashboard.php                # 7 lines!
│           ├── datatable.php                # DataTable HTML
│           └── tabs/
│               ├── details.php              # Tab 1 (immediate)
│               ├── divisions.php            # Tab 2 (lazy load)
│               └── employees.php            # Tab 3 (lazy load)
│
├── docs/
│   ├── hooks/
│   │   ├── actions/
│   │   │   └── agency-actions.md
│   │   └── filters/
│   │       └── permission-filters.md
│   └── integration/
│       └── wp-customer-integration.md
│
└── TODO/
    └── TODO-2071-implement-agency-dashboard-with-panel-system.md

wp-customer/src/Integrations/
└── AgencyAccessFilter.php                   # Cross-plugin filter
```

---

## 📊 Database Indexes Required

**Check wp_app_agencies table** (AgencysDB.php):
- [x] id (PRIMARY KEY)
- [x] code (UNIQUE)
- [x] status (indexed? - add if missing)
- [x] provinsi_code (indexed? - add if missing)
- [x] regency_code (indexed? - add if missing)

**Check wp_app_customer_branches table** (BranchesDB.php):
- [x] id (PRIMARY KEY)
- [x] customer_id (indexed)
- [x] agency_id (indexed - CRITICAL for query performance)
- [x] status (indexed? - add if missing)

**Check wp_app_customer_employees table** (CustomerEmployeesDB.php):
- [x] id (PRIMARY KEY)
- [x] user_id (indexed - CRITICAL for permission checks)
- [x] branch_id (indexed)

**Add if missing**:
```sql
ALTER TABLE wp_app_agencies
ADD INDEX status_index (status),
ADD INDEX region_index (provinsi_code, regency_code);

ALTER TABLE wp_app_customer_branches
ADD INDEX agency_status_index (agency_id, status);

ALTER TABLE wp_app_customer_employees
ADD INDEX user_branch_index (user_id, branch_id);
```

---

## 🔍 Testing Checklist Summary

### Functionality
- [ ] Dashboard loads
- [ ] DataTable displays agencies
- [ ] Search works
- [ ] Sorting works
- [ ] Pagination works
- [ ] Statistics show correct numbers
- [ ] Panel opens on row click
- [ ] Panel closes smoothly
- [ ] Tab 1 loads immediately
- [ ] Tab 2 lazy loads on click
- [ ] Tab 3 lazy loads on click
- [ ] Hash navigation works
- [ ] Browser back/forward works

### Permissions (KEY TESTING!)
- [ ] Platform Admin sees all agencies
- [ ] Customer Admin sees filtered agencies
- [ ] Branch Admin sees 1 agency
- [ ] Unauthorized user blocked
- [ ] Cross-plugin filter applies correctly
- [ ] SQL query matches verified results

### Performance
- [ ] < 500ms page load
- [ ] < 200ms AJAX responses
- [ ] No N+1 queries
- [ ] Smooth animations (60fps)

### Security
- [ ] Nonce verified
- [ ] Permissions checked
- [ ] Input sanitized
- [ ] Output escaped
- [ ] No SQL injection possible

### UI/UX
- [ ] Responsive on mobile
- [ ] Loading states clear
- [ ] Error messages helpful
- [ ] Success messages shown
- [ ] Animations smooth (Perfex CRM style)

---

## 📈 Success Criteria

✅ **Phase 8 Complete** when:
1. Agency dashboard fully functional
2. All 3 tabs working (1 immediate, 2 lazy)
3. Cross-plugin filtering works correctly
4. Permissions properly enforced
5. All tests passing
6. Base panel system proven in real implementation
7. Documentation complete
8. Code reviewed and polished

✅ **Ready for Phase 9** (Cleanup) when:
- TODO-2179 Phase 8 complete
- wp-agency implementation verified
- Ready to remove duplicate code from wp-customer

---

## 🚀 Next Steps After Completion

1. **Phase 9: Cleanup** (TODO-2179)
   - Remove duplicate CSS/JS from wp-customer
   - Migrate customer/company-invoice to base panel system
   - Final performance optimization

2. **Documentation Updates**
   - Add wp-agency example to wp-app-core docs
   - Update TODO-2179 with Phase 8 results
   - Create cross-plugin integration guide

3. **Future Enhancements**
   - Add export functionality
   - Add bulk actions
   - Add advanced filters
   - Add agency dashboard (agency user view)

---

## 📚 Reference Links

**Base Panel System**:
- [README.md](../../wp-app-core/src/Views/DataTable/README.md)
- [MIGRATION-EXAMPLE.md](../../wp-app-core/src/Views/DataTable/MIGRATION-EXAMPLE.md)
- [QUICK-REFERENCE.md](../../wp-app-core/src/Views/DataTable/QUICK-REFERENCE.md)

**TODO References**:
- [TODO-2179](../../wp-app-core/TODO/TODO-2179-implement-base-panel-dashboard-system.md)
- [TODO-2178](../../wp-app-core/TODO/TODO-2178-implement-base-datatable-system.md)
- [TODO-2174](../../wp-customer/TODO/TODO-2174-implement-companies-datatable.md)

**Documentation**:
- [wp-app-core DataTable Docs](../../wp-app-core/docs/datatable/)
- [wp-customer Hooks](../../wp-customer/docs/hooks/)

---

**Created**: 2025-10-23
**Status**: Ready to Start
**Estimated Time**: 12-15 days (with testing)
**Priority**: High - Critical for TODO-2179 Phase 8 completion
