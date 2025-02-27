
<?php


?>


<div class="wp-agency-division-panel-header">
    <h2>Detail Agency: <span id="agency-header-name"></span></h2>
    <button type="button" class="wp-agency-division-close-panel">×</button>
</div>

<div class="wp-agency-division-panel-content">


<div class="nav-tab-wrapper">
    <a href="#" class="nav-tab nav-tab-agency-details nav-tab-active" data-tab="agency-details">Data Perusahaan</a>
    <a href="#" class="nav-tab" data-tab="membership-info">Membership</a>
    <a href="#" class="nav-tab" data-tab="employee-list">Staff</a>
</div>

<?php
// Pass data ke semua partial templates


foreach ([
    'agency/partials/_agency_details.php',
    'agency/partials/_agency_membership.php',
    'employee/partials/_employee_list.php'
] as $template) {
    include_once WP_AGENCY_PATH . 'src/Views/templates/' . $template;
}
?>

</div>
