<?php
/**
 * Debug Admin Bar Output
 *
 * Menampilkan raw HTML output dari admin bar untuk debugging
 */

// Load WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Check if user is logged in
if (!is_user_logged_in()) {
    die('Please login first');
}

$user_id = get_current_user_id();

echo "<h2>Debug Admin Bar untuk User ID: {$user_id}</h2>";
echo "<hr>";

// Get user info dari AgencyEmployeeModel
if (class_exists('WPAgency\Models\Employee\AgencyEmployeeModel')) {
    $model = new \WPAgency\Models\Employee\AgencyEmployeeModel();
    $user_info = $model->getUserInfo($user_id);

    echo "<h3>1. User Info dari Model:</h3>";
    echo "<pre>";
    print_r($user_info);
    echo "</pre>";
    echo "<hr>";

    // Simulate get_detailed_info_html
    if ($user_info) {
        $user = get_user_by('ID', $user_id);

        echo "<h3>2. Simulated Detailed Info HTML:</h3>";

        $html = '<div class="wp-app-core-detailed-info">';

        // User Info Section
        $html .= '<div class="info-section">';
        $html .= '<strong>User Information:</strong><br>';
        $html .= 'ID: ' . $user_id . '<br>';
        $html .= 'Username: ' . esc_html($user->user_login) . '<br>';
        $html .= 'Email: ' . esc_html($user->user_email) . '<br>';
        $html .= '</div>';

        // Entity Info Section
        $html .= '<div class="info-section">';
        $html .= '<strong>Entity Information:</strong><br>';
        if (isset($user_info['entity_name'])) {
            $html .= 'Entity: ' . esc_html($user_info['entity_name']) . '<br>';
        }
        if (isset($user_info['division_name'])) {
            $html .= 'Division: ' . esc_html($user_info['division_name']) . '<br>';
        }
        $html .= '</div>';

        // Roles Section
        $html .= '<div class="info-section">';
        $html .= '<strong>Roles:</strong><br>';
        if (isset($user_info['role_names']) && is_array($user_info['role_names']) && !empty($user_info['role_names'])) {
            foreach ($user_info['role_names'] as $role_name) {
                $html .= '• ' . esc_html($role_name) . '<br>';
            }
        }
        $html .= '</div>';

        // Capabilities Section
        $html .= '<div class="info-section">';
        $html .= '<strong>Key Capabilities:</strong><br>';

        echo "<h4>Permission Names Array:</h4>";
        echo "<pre>";
        print_r($user_info['permission_names'] ?? 'NOT SET');
        echo "</pre>";

        if (isset($user_info['permission_names']) && is_array($user_info['permission_names']) && !empty($user_info['permission_names'])) {
            echo "<p style='color: green;'><strong>✓ Permissions FOUND! Count: " . count($user_info['permission_names']) . "</strong></p>";
            foreach ($user_info['permission_names'] as $permission) {
                $html .= '✓ ' . esc_html($permission) . '<br>';
            }
        } else {
            echo "<p style='color: red;'><strong>✗ No permissions found</strong></p>";
            $html .= 'No key capabilities found<br>';
        }

        $html .= '</div>';
        $html .= '</div>';

        echo "<h3>3. Raw HTML Output:</h3>";
        echo "<textarea style='width: 100%; height: 300px;'>";
        echo htmlspecialchars($html);
        echo "</textarea>";

        echo "<h3>4. Rendered HTML:</h3>";
        echo "<div style='border: 2px solid #000; padding: 20px; background: #f5f5f5;'>";
        echo $html;
        echo "</div>";
    } else {
        echo "<p style='color: red;'>No user info found from model</p>";
    }
} else {
    echo "<p style='color: red;'>AgencyEmployeeModel class not found</p>";
}

echo "<hr>";
echo "<h3>5. Browser Cache Check:</h3>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . " - If this doesn't update when you refresh, you have browser cache issue.</p>";
echo "<p>Random: " . rand(1000, 9999) . "</p>";
