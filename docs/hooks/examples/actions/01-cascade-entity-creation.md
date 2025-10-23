# Example: Understanding Cascade Entity Creation

This example demonstrates how the hook system creates a cascade of entities when an agency is created.

## The Cascade Chain

When you create an agency, the following cascade happens automatically:

```
1. Agency Created
   ↓ (fires wp_agency_agency_created)
   
2. Division Pusat Auto-created
   ↓ (fires wp_agency_division_created)
   
3. Employee Admin Auto-created
   ↓ (fires wp_agency_employee_created)
   
4. All entities ready
```

## Code Flow

### Step 1: Create Agency

```php
// In your code or via admin panel
$agency_data = [
    'code' => 'AGC001',
    'name' => 'Dinas Tenaga Kerja',
    'provinsi_code' => '32',
    'user_id' => 123,
    'created_by' => 1
];

$agency_model = new AgencyModel();
$agency_id = $agency_model->create($agency_data);

// This fires: wp_agency_agency_created($agency_id, $agency_data)
```

### Step 2: Hook Fires → Division Created

```php
// In wp-agency.php - hook is registered
$auto_creator = new AutoEntityCreator();
add_action('wp_agency_agency_created', [$auto_creator, 'handleAgencyCreated'], 10, 2);

// In AutoEntityCreator::handleAgencyCreated()
public function handleAgencyCreated($agency_id, $data) {
    // Auto-create division pusat
    $division_data = [
        'agency_id' => $agency_id,
        'code' => 'DIV-PUSAT-' . $agency_id,
        'name' => 'Pusat',
        'type' => 'pusat',
        'user_id' => $data['user_id'],
        'created_by' => $data['created_by']
    ];

    $division_id = $this->division_model->create($division_data);
    
    // This fires: wp_agency_division_created($division_id, $division_data)
}
```

### Step 3: Division Hook Fires → Employee Created

```php
// Hook is registered
add_action('wp_agency_division_created', [$auto_creator, 'handleDivisionCreated'], 10, 2);

// In AutoEntityCreator::handleDivisionCreated()
public function handleDivisionCreated($division_id, $data) {
    if (empty($data['user_id'])) {
        return; // No user, skip
    }

    $user = get_userdata($data['user_id']);

    // Auto-create employee
    $employee_data = [
        'agency_id' => $data['agency_id'],
        'division_id' => $division_id,
        'user_id' => $data['user_id'],
        'name' => $user->display_name,
        'position' => 'Admin ' . $data['name'],
        'email' => $user->user_email,
        'created_by' => $data['created_by']
    ];

    $employee_id = $this->employee_model->create($employee_data);
    
    // This fires: wp_agency_employee_created($employee_id, $employee_data)
}
```

## Tracking the Cascade

### Example: Log All Entity Creations

```php
// Track agency creation
add_action('wp_agency_agency_created', function($agency_id, $agency_data) {
    error_log("[CASCADE] Step 1: Agency created - ID: $agency_id, Name: {$agency_data['name']}");
}, 10, 2);

// Track division creation
add_action('wp_agency_division_created', function($division_id, $division_data) {
    error_log("[CASCADE] Step 2: Division created - ID: $division_id, Name: {$division_data['name']}, Type: {$division_data['type']}");
}, 10, 2);

// Track employee creation
add_action('wp_agency_employee_created', function($employee_id, $employee_data) {
    error_log("[CASCADE] Step 3: Employee created - ID: $employee_id, Name: {$employee_data['name']}, Position: {$employee_data['position']}");
}, 10, 2);
```

### Output in Error Log

```
[CASCADE] Step 1: Agency created - ID: 1, Name: Dinas Tenaga Kerja
[CASCADE] Step 2: Division created - ID: 1, Name: Pusat, Type: pusat
[CASCADE] Step 3: Employee created - ID: 1, Name: John Doe, Position: Admin Pusat
```

## Customizing the Cascade

### Example: Send Welcome Email After Full Cascade

```php
add_action('wp_agency_employee_created', 'send_welcome_after_cascade', 10, 2);

function send_welcome_after_cascade($employee_id, $employee_data) {
    // Check if this is the first employee (cascade-created)
    global $wpdb;
    
    $employee_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_employees
         WHERE agency_id = %d",
        $employee_data['agency_id']
    ));

    if ($employee_count == 1) {
        // This is the first employee, send comprehensive welcome email
        $user = get_user_by('ID', $employee_data['user_id']);
        
        wp_mail(
            $user->user_email,
            'Welcome - Your Agency is Ready!',
            "Your agency setup is complete!\n\n" .
            "Agency: " . get_agency_name($employee_data['agency_id']) . "\n" .
            "Division: " . get_division_name($employee_data['division_id']) . "\n" .
            "You can now login and start using the platform."
        );
    }
}
```

### Example: Prevent Default Cascade

```php
// Remove default handlers
remove_action('wp_agency_agency_created', [$auto_creator, 'handleAgencyCreated'], 10);
remove_action('wp_agency_division_created', [$auto_creator, 'handleDivisionCreated'], 10);

// Add custom handlers
add_action('wp_agency_agency_created', 'my_custom_agency_handler', 10, 2);

function my_custom_agency_handler($agency_id, $agency_data) {
    // Custom logic instead of auto-creation
    error_log("Custom handler: Agency $agency_id created, but NOT auto-creating division");
}
```

## Understanding Hook Priority

The cascade works because hooks fire in order:

```php
// Priority 10 (default) - Runs first
add_action('wp_agency_agency_created', [$auto_creator, 'handleAgencyCreated'], 10, 2);

// Priority 20 - Runs after default handler
add_action('wp_agency_agency_created', 'my_custom_action', 20, 2);

function my_custom_action($agency_id, $agency_data) {
    // At this point:
    // - Agency exists
    // - Division pusat exists (created by priority 10 handler)
    // - Employee exists (cascade from division)
}
```

## Common Pitfalls

### Pitfall 1: Duplicate Entity Creation

```php
// ❌ WRONG - This creates duplicate divisions!
add_action('wp_agency_agency_created', function($agency_id, $agency_data) {
    // Don't create division here - default handler already does it!
    $division_model->create([
        'agency_id' => $agency_id,
        'name' => 'Pusat',
        'type' => 'pusat'
    ]);
}, 10, 2);
```

### Pitfall 2: Missing User ID

```php
// ❌ WRONG - Division without user_id won't create employee
$division_data = [
    'agency_id' => $agency_id,
    'name' => 'Pusat',
    // Missing user_id - employee won't be auto-created!
];
```

### Pitfall 3: Infinite Loop

```php
// ❌ WRONG - This creates infinite loop!
add_action('wp_agency_division_created', function($division_id, $data) {
    // Don't create another division in division_created hook!
    $division_model->create([
        'agency_id' => $data['agency_id'],
        'name' => 'Another Division'
    ]); // This fires wp_agency_division_created again!
}, 10, 2);
```

## Testing the Cascade

```php
// Test function
function test_cascade_creation() {
    $agency_model = new AgencyModel();
    
    $agency_id = $agency_model->create([
        'code' => 'TEST001',
        'name' => 'Test Agency',
        'user_id' => 1,
        'created_by' => 1
    ]);

    if (!$agency_id) {
        return 'Agency creation failed';
    }

    // Check cascade results
    global $wpdb;
    
    $divisions = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_divisions WHERE agency_id = %d",
        $agency_id
    ));

    $employees = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}app_agency_employees WHERE agency_id = %d",
        $agency_id
    ));

    return sprintf(
        "Agency: %d, Divisions: %d, Employees: %d",
        $agency_id,
        $divisions,
        $employees
    );
}

// Expected output: "Agency: 1, Divisions: 1, Employees: 1"
```

---

**See Also**:
- [Agency Action Hooks](../../actions/agency-actions.md)
- [Division Action Hooks](../../actions/division-actions.md)
- [Employee Action Hooks](../../actions/employee-actions.md)
