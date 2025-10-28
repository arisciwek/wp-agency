<?php
/**
 * Header Title - Agency Page Title and Subtitle
 *
 * @package     WP_Agency
 * @subpackage  Views/Agency/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/agency/partials/header-title.php
 *
 * Description: Displays agency page title and subtitle in dashboard header.
 *              Uses LOCAL SCOPE (agency-*) classes only.
 *              Called by: AgencyDashboardController::render_header_title()
 *
 * Context: Header component
 * Scope: LOCAL (agency-* prefix only)
 *
 * Variables available:
 * - None (static content)
 *
 * Changelog:
 * 1.0.0 - 2025-10-27
 * - Initial creation
 * - Extracted from AgencyDashboardController
 * - Follows {context}-{identifier} naming convention
 * - Pure presentation layer (no business logic)
 */

defined('ABSPATH') || exit;
?>

<h1 class="agency-page-title"><?php _e('Daftar Disnaker', 'wp-agency'); ?></h1>
<p class="agency-page-subtitle"><?php _e('Kelola data dinas tenaga kerja', 'wp-agency'); ?></p>
