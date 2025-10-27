/**
 * Agency Management Interface
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS
 * @version     1.0.1
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/agency-script.js
 *
 * Description: Main JavaScript handler untuk halaman agency.
 *              Mengatur interaksi antar komponen seperti DataTable,
 *              form, panel kanan, dan notifikasi.
 *              Includes state management dan event handling.
 *              Terintegrasi dengan WordPress AJAX API.
 *
 * Dependencies:
 * - jQuery
 * - AgencyDataTable
 * - AgencyForm
 * - AgencyToast
 * - WordPress AJAX
 *
 * Changelog:
 * 1.0.1 - 2025-10-26 (TODO-1180)
 * - FIXED: Disabled legacy hash handling when WPAppPanelHandler exists
 * - Added detection for centralized panel handler from wp-app-core
 * - Skip handleInitialState() when centralized handler is active
 * - Skip hashchange listener when centralized handler is active
 * - Enhanced agency:loaded event to handle data from centralized handler
 * - Prevents conflict with wp-app-core/assets/js/datatable/panel-handler.js
 *
 * 1.0.0 - 2024-12-03
 * - Added proper jQuery no-conflict handling
 * - Added panel kanan integration
 * - Added CRUD event handlers
 * - Added toast notifications
 * - Improved error handling
 * - Added loading states
 *
 * Last modified: 2025-10-26 10:30:00
 */