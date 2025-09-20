
<?php
/**
 * Agency Right Panel Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/agency-right-panel.php
 *
 * Description: Template for the right panel displaying agency details, membership, divisions, and employees.
 *
 * Changelog:
 * 1.0.0 - 2024-12-XX
 * - Initial version
 */

defined('ABSPATH') || exit;

?>


<div class="wp-agency-panel-header">
    <h2>Detail Disnaker: <span id="agency-header-name"></span></h2>
    <button type="button" class="wp-agency-close-panel">×</button>
</div>

<div class="wp-agency-panel-content">


<div class="nav-tab-wrapper">
    <a href="#" class="nav-tab nav-tab-agency-details nav-tab-active" data-tab="agency-details">Data Disnaker</a>
    <a href="#" class="nav-tab" data-tab="membership-info">Membership</a>
    <a href="#" class="nav-tab" data-tab="division-list">Unit</a>
    <a href="#" class="nav-tab" data-tab="employee-list">Staff</a>
</div>

<?php
// Pass data ke semua partial templates


foreach ([
    'agency/partials/_agency_details.php',
    'agency/partials/_agency_membership.php',
    'division/partials/_agency_division_list.php',
    'employee/partials/_employee_list.php'
] as $template) {
    include_once WP_AGENCY_PATH . 'src/Views/templates/' . $template;
}
?>

</div>
