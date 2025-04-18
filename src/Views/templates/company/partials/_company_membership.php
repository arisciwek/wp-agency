<?php
/**
 * Company Membership Tab Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates/Company/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/company/partials/_company_membership.php
 */

defined('ABSPATH') || exit;
?>

<div id="membership-info" class="tab-content">
    <div class="postbox membership-status-card">
        <h3 class="hndle">
            <span class="dashicons dashicons-buddicons-groups"></span>
            <?php _e('Status Membership', 'wp-agency'); ?>
        </h3>
        <div class="inside">
            <!-- Membership Information -->
            <table class="form-table">
                <tr>
                    <th><?php _e('Level', 'wp-agency'); ?></th>
                    <td><span id="company-level-name"></span></td>
                </tr>
                <tr>
                    <th><?php _e('Status', 'wp-agency'); ?></th>
                    <td><span id="company-membership-status"></span></td>
                </tr>
                <tr>
                    <th><?php _e('Periode', 'wp-agency'); ?></th>
                    <td><span id="company-membership-period"></span></td>
                </tr>
            </table>
        </div>
    </div>
</div>
