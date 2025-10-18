<!DOCTYPE html>
<html>
<head>
    <title>Inspect Admin Bar DOM</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        h2 { color: #0073aa; }
        .code { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; margin: 10px 0; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
<?php
// Load WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Must be logged in
if (!is_user_logged_in()) {
    die('Please login first');
}

$user_id = get_current_user_id();

echo "<h2>Admin Bar DOM Inspector - User ID: {$user_id}</h2>";
echo "<hr>";

// Create WP_Admin_Bar instance
global $wp_admin_bar;
if (!is_admin_bar_showing()) {
    show_admin_bar(true);
}

// Trigger admin_bar_menu action
do_action('wp_before_admin_bar_render');
do_action('admin_bar_menu');

// Get admin bar nodes
$nodes = $wp_admin_bar->get_nodes();

echo "<h3>1. Admin Bar Nodes:</h3>";
echo "<div class='code'>";
echo "Total nodes: " . count($nodes) . "<br><br>";

// Find our node
$our_node = null;
$our_child = null;
foreach ($nodes as $node) {
    if ($node->id === 'wp-app-core-user-info') {
        $our_node = $node;
        echo "<p class='success'>✓ Found parent node: wp-app-core-user-info</p>";
        echo "<pre>";
        print_r($node);
        echo "</pre>";
    }
    if ($node->id === 'wp-app-core-user-details') {
        $our_child = $node;
        echo "<p class='success'>✓ Found child node: wp-app-core-user-details</p>";
        echo "<pre>";
        print_r($node);
        echo "</pre>";
    }
}

if (!$our_node) {
    echo "<p class='error'>✗ Parent node NOT FOUND!</p>";
}
if (!$our_child) {
    echo "<p class='error'>✗ Child node NOT FOUND!</p>";
}

echo "</div>";

// Check child HTML
if ($our_child) {
    echo "<h3>2. Child Node HTML:</h3>";
    echo "<div class='code'>";
    echo "<p><strong>HTML Length:</strong> " . strlen($our_child->title) . " chars</p>";
    echo "<p><strong>HTML Preview (first 1000 chars):</strong></p>";
    echo "<textarea style='width:100%; height:200px;'>";
    echo htmlspecialchars(substr($our_child->title, 0, 1000));
    echo "</textarea>";

    echo "<p><strong>Full HTML:</strong></p>";
    echo "<textarea style='width:100%; height:400px;'>";
    echo htmlspecialchars($our_child->title);
    echo "</textarea>";

    echo "<p><strong>Rendered Preview:</strong></p>";
    echo "<div style='background:#32373c; color:#ccc; padding:20px;'>";
    echo $our_child->title;
    echo "</div>";
    echo "</div>";
}

// Get user info directly
echo "<h3>3. Direct User Info Check:</h3>";
if (class_exists('WPAgency\Models\Employee\AgencyEmployeeModel')) {
    $model = new \WPAgency\Models\Employee\AgencyEmployeeModel();
    $user_info = $model->getUserInfo($user_id);

    echo "<div class='code'>";
    echo "<p><strong>Role Names:</strong></p>";
    echo "<pre>";
    print_r($user_info['role_names'] ?? 'NOT SET');
    echo "</pre>";

    echo "<p><strong>Permission Names:</strong></p>";
    if (isset($user_info['permission_names']) && !empty($user_info['permission_names'])) {
        echo "<p class='success'>✓ Permissions FOUND! Count: " . count($user_info['permission_names']) . "</p>";
        echo "<pre>";
        print_r($user_info['permission_names']);
        echo "</pre>";
    } else {
        echo "<p class='error'>✗ No permissions!</p>";
    }
    echo "</div>";
}
?>

<hr>
<h3>Instructions:</h3>
<ol>
    <li>Check if parent and child nodes are FOUND</li>
    <li>Check if HTML Length > 0</li>
    <li>Check "Rendered Preview" - should show all sections with permissions</li>
    <li>If preview shows correctly but admin bar doesn't, it's a CSS/JavaScript issue</li>
</ol>

</body>
</html>
