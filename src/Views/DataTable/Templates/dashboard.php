<?php
/**
 * Agency Dashboard - Disnaker
 *
 * @package     WP_Agency
 * @subpackage  Views/DataTable/Templates
 * @version     1.0.1
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/DataTable/Templates/dashboard.php
 *
 * Description: Agency dashboard menggunakan base panel system dari wp-app-core.
 *              ONLY 7 LINES! Base panel system handles everything else:
 *              - Left/right panel layout
 *              - Tab system
 *              - Statistics boxes
 *              - Smooth animations
 *              - Hash navigation
 *              - Close functionality
 *
 * Changelog:
 * 1.0.1 - 2025-10-25
 * - Moved from /Views/agency/ to /Views/DataTable/Templates/
 * - Updated path to match DataTable structure
 * - Updated subpackage to Views/DataTable/Templates
 * 1.0.0 - 2025-10-23
 * - Initial implementation (TODO-2071 Phase 4, Task 4.1)
 * - Uses DashboardTemplate from wp-app-core
 * - Replaces 1,154 lines of custom code with 7 lines!
 */

use WPAppCore\Views\DataTable\Templates\DashboardTemplate;

defined('ABSPATH') || exit;

// Render dashboard using base panel system
DashboardTemplate::render([
    'entity' => 'agency',
    'title' => __('Disnaker', 'wp-agency'),
    'ajax_action' => 'get_agency_details',
    'has_stats' => true,
    'has_tabs' => true,
]);
