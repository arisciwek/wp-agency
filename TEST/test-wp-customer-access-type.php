<?php
/**
 * Test WP Customer Access Type for Agency Employees
 *
 * Run with: wp eval-file test-wp-customer-access-type.php
 */

error_reporting(0);
require_once dirname(__FILE__) . '/../../../wp-load.php';

echo "\n=== Testing Agency Employee Access Type ===\n\n";

// Get first agency employee (from employee table, NOT by role)
global $wpdb;
$employee = $wpdb->get_row("
    SELECT e.user_id, u.user_login
    FROM {$wpdb->prefix}app_agency_employees e
    INNER JOIN {$wpdb->prefix}users u ON e.user_id = u.ID
    LIMIT 1
");

if (!$employee) {
    echo "Error: No agency employees found in app_agency_employees table!\n";
    exit(1);
}

$test_user = get_userdata($employee->user_id);
echo "Testing with: {$test_user->user_login} (ID: {$test_user->ID})\n";
echo "Roles: " . implode(', ', $test_user->roles) . "\n";
echo "Source: app_agency_employees table (employee-based, NOT role-based)\n\n";

// Switch to agency user
wp_set_current_user($test_user->ID);

// Test CustomerModel getUserRelation
echo "--- CustomerModel::getUserRelation() ---\n";
$customerModel = new \WPCustomer\Models\Customer\CustomerModel();
$relation = $customerModel->getUserRelation(0);

echo "access_type: {$relation['access_type']}\n";
echo "is_admin: " . ($relation['is_admin'] ? 'yes' : 'no') . "\n";
echo "is_customer_admin: " . ($relation['is_customer_admin'] ? 'yes' : 'no') . "\n\n";

// Test agency province retrieval
echo "--- Agency Province Info ---\n";
$province_code = WP_Agency_WP_Customer_Integration::get_user_agency_province($test_user->ID);
echo "Province code: " . ($province_code ?? 'null') . "\n";

if ($province_code) {
    $province_id = WP_Agency_WP_Customer_Integration::get_province_id_from_code($province_code);
    echo "Province ID: " . ($province_id ?? 'null') . "\n";

    // Get province name
    global $wpdb;
    $province_name = $wpdb->get_var($wpdb->prepare(
        "SELECT name FROM {$wpdb->prefix}wi_provinces WHERE code = %s",
        $province_code
    ));
    echo "Province name: " . ($province_name ?? 'unknown') . "\n";
}

echo "\n--- Summary ---\n";
if ($relation['access_type'] === 'agency') {
    echo "✅ SUCCESS: access_type = 'agency'\n";
    echo "✅ Integration working correctly\n";
    if ($province_code) {
        echo "✅ Province code: {$province_code}\n";
        echo "\nNext: Update CustomerModel to filter by province\n";
    } else {
        echo "⚠️  Warning: No province code found for this user\n";
    }
} else {
    echo "❌ FAILED: access_type = '{$relation['access_type']}' (expected 'agency')\n";
}

echo "\n=== Test Complete ===\n";

// Restore admin user
wp_set_current_user(1);
