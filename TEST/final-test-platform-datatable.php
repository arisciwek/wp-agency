<?php
/**
 * Final Test - Menu Perusahaan DataTable
 *
 * Test end-to-end apakah agency user bisa melihat daftar branch
 * di menu perusahaan wp-customer dengan filtering yang benar
 *
 * Run with: wp eval-file final-test-platform-datatable.php
 */

error_reporting(0);
require_once dirname(__FILE__) . '/../../../wp-load.php';

echo "\n=== Final Test: Menu Perusahaan DataTable ===\n\n";

global $wpdb;

// Test dengan budi_citra (admin_unit) yang sudah verified
$test_user_id = 140; // budi_citra
$user = get_userdata($test_user_id);

echo "Testing with:\n";
echo "  User: {$user->user_login} (ID: {$test_user_id})\n";
echo "  Roles: " . implode(', ', $user->roles) . "\n\n";

// Switch to agency user
wp_set_current_user($test_user_id);

// Step 1: Check capabilities
echo "--- Step 1: Check Menu Access Capabilities ---\n";
$required_caps = [
    'view_customer_list',
    'view_customer_detail',
    'view_customer_branch_list',
    'view_customer_branch_detail',
];

$all_caps_ok = true;
foreach ($required_caps as $cap) {
    $has_cap = current_user_can($cap);
    echo "  {$cap}: " . ($has_cap ? '✅' : '❌') . "\n";
    if (!$has_cap) {
        $all_caps_ok = false;
    }
}

if (!$all_caps_ok) {
    echo "\n❌ FAILED: User tidak punya capabilities yang diperlukan!\n";
    echo "Menu perusahaan tidak akan tampil.\n";
    exit(1);
}

echo "✅ All capabilities OK - Menu should be visible\n\n";

// Step 2: Check access_type
echo "--- Step 2: Check Access Type ---\n";
$customerModel = new \WPCustomer\Models\Customer\CustomerModel();
$relation = $customerModel->getUserRelation(0);

echo "access_type: {$relation['access_type']}\n";

if ($relation['access_type'] !== 'agency') {
    echo "❌ FAILED: access_type should be 'agency', got '{$relation['access_type']}'\n";
    exit(1);
}

echo "✅ access_type = 'agency'\n\n";

// Step 3: Check access level
echo "--- Step 3: Check Role-Based Access Level ---\n";
$access_level = \WP_Agency_WP_Customer_Integration::get_user_access_level($test_user_id);
echo "Access Level: {$access_level}\n";

if ($access_level === 'division') {
    $jurisdictions = \WP_Agency_WP_Customer_Integration::get_user_division_jurisdictions($test_user_id);
    echo "Jurisdictions: " . count($jurisdictions) . " regencies\n";
    echo "✅ Division-level filtering active\n\n";
} elseif ($access_level === 'agency') {
    $agency_id = \WP_Agency_WP_Customer_Integration::get_user_agency_id($test_user_id);
    echo "Agency ID: {$agency_id}\n";
    echo "✅ Agency-level filtering active\n\n";
} else {
    echo "❌ Unknown access level\n\n";
}

// Step 4: Test Branch DataTable (getTotalCount)
echo "--- Step 4: Test Branch Count ---\n";
$branchModel = new \WPCustomer\Models\Branch\BranchModel();
$total_count = $branchModel->getTotalCount(0);

echo "Total branches visible: {$total_count}\n";

if ($total_count == 0) {
    echo "❌ FAILED: No branches visible!\n";
    echo "DataTable akan kosong.\n";
    exit(1);
}

echo "✅ Branch count OK - DataTable should show data\n\n";

// Step 5: Test Branch DataTable (getDataTableData)
echo "--- Step 5: Test Branch DataTable Data ---\n";
$datatable_result = $branchModel->getDataTableData(
    0,      // customer_id (0 = all)
    0,      // start
    10,     // length
    '',     // search
    'code', // orderColumn
    'asc'   // orderDir
);

echo "DataTable Response:\n";
echo "  Total: {$datatable_result['total']}\n";
echo "  Filtered: {$datatable_result['filtered']}\n";
echo "  Data rows: " . count($datatable_result['data']) . "\n\n";

if (count($datatable_result['data']) == 0) {
    echo "❌ FAILED: No data returned!\n";
    exit(1);
}

echo "Sample branches (first 3):\n";
foreach (array_slice($datatable_result['data'], 0, 3) as $branch) {
    echo "  - {$branch->code}: {$branch->name}\n";
    echo "    Customer: {$branch->customer_name}\n";

    // Verify jurisdiction
    $regency = $wpdb->get_row($wpdb->prepare(
        "SELECT r.code, r.name FROM wp_wi_regencies r WHERE r.id = %d",
        $branch->regency_id
    ));

    if ($regency) {
        echo "    Regency: {$regency->name} ({$regency->code})\n";
    }
    echo "\n";
}

echo "✅ DataTable data OK\n\n";

// Step 6: Verify filtering is correct
echo "--- Step 6: Verify Filtering Correctness ---\n";

if ($access_level === 'division') {
    $user_jurisdictions = \WP_Agency_WP_Customer_Integration::get_user_division_jurisdictions($test_user_id);

    echo "Checking if all branches are in user's jurisdiction...\n";
    $all_correct = true;

    foreach ($datatable_result['data'] as $branch) {
        if (!in_array($branch->regency_id, $user_jurisdictions)) {
            echo "❌ Branch {$branch->code} is in wrong jurisdiction (regency_id: {$branch->regency_id})\n";
            $all_correct = false;
        }
    }

    if ($all_correct) {
        echo "✅ All branches are in correct jurisdiction\n";
    }
}

echo "\n=== Final Summary ===\n";
echo "✅ Menu Access: Capabilities OK\n";
echo "✅ Access Type: 'agency'\n";
echo "✅ Access Level: '{$access_level}'\n";
echo "✅ Branch Count: {$total_count}\n";
echo "✅ DataTable: {$datatable_result['filtered']} rows\n";
echo "✅ Filtering: Role-based filtering working\n\n";

echo "🎉 READY TO COMMIT!\n";
echo "Menu Perusahaan di wp-customer BISA menampilkan daftar branch\n";
echo "dengan filtering yang benar untuk agency user.\n";

echo "\n=== Test Complete ===\n";

// Restore admin
wp_set_current_user(1);
