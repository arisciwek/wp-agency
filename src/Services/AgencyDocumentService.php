<?php
/**
 * Agency Document Service
 *
 * @package     WP_Agency
 * @subpackage  Services
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Services/AgencyDocumentService.php
 *
 * Description: Handles document generation for agencies (PDF, DOCX).
 *              Extracted from AgencyController for cleaner architecture.
 *              Provides PDF generation via wp-mpdf and wp-docgen plugins.
 *
 * Changelog:
 * 1.0.0 - 2025-12-28 (Refactor to AbstractCRUD)
 * - Initial creation - extracted from AgencyController
 * - Methods: generatePdf(), generateDocx(), generatePdfFromDocx()
 * - Separation of concerns: document generation logic
 */

namespace WPAgency\Services;

use WPAgency\Models\Agency\AgencyModel;
use WPAgency\Validators\AgencyValidator;

defined('ABSPATH') || exit;

class AgencyDocumentService {

    private AgencyModel $model;
    private AgencyValidator $validator;

    public function __construct() {
        $this->model = new AgencyModel();
        $this->validator = new AgencyValidator();
    }

    /**
     * Generate PDF using wp-mpdf
     *
     * @param int $id Agency ID
     * @return void Dies after output
     * @throws \Exception
     */
    public function generatePdf(int $id): void {
        // Validate access
        $access = $this->validator->validateAccess($id);
        if (!$access['has_access']) {
            throw new \Exception('You do not have permission to view this agency');
        }

        // Load wp-mpdf
        if (!function_exists('wp_mpdf_load')) {
            throw new \Exception('PDF generator plugin tidak ditemukan');
        }

        if (!wp_mpdf_load()) {
            throw new \Exception('Gagal memuat PDF generator plugin');
        }

        if (!wp_mpdf_init()) {
            throw new \Exception('Gagal menginisialisasi PDF generator');
        }

        // Get agency data
        $agency = $this->model->find($id);
        if (!$agency) {
            throw new \Exception('Agency not found');
        }

        // Generate PDF
        ob_start();
        include WP_AGENCY_PATH . 'src/Views/templates/agency/pdf/agency-detail-pdf.php';
        $html = ob_get_clean();

        $mpdf = wp_mpdf()->generate_pdf($html, [
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16
        ]);

        // Output for download
        $mpdf->Output('agency-' . $agency->code . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);
    }

    /**
     * Generate DOCX using wp-docgen
     *
     * @param int $id Agency ID
     * @return array File URL and filename
     * @throws \Exception
     */
    public function generateDocx(int $id): array {
        // Validate access
        $access = $this->validator->validateAccess($id);
        if (!$access['has_access']) {
            throw new \Exception('You do not have permission to view this agency');
        }

        // Get agency data
        $agency = $this->model->find($id);
        if (!$agency) {
            throw new \Exception('Agency not found');
        }

        // Initialize WP DocGen
        $docgen = wp_docgen();

        // Set template variables
        $variables = [
            'agency_name' => $agency->name,
            'agency_code' => $agency->code,
            'total_divisiones' => $agency->division_count ?? 0,
            'created_date' => date('d F Y H:i', strtotime($agency->created_at)),
            'updated_date' => date('d F Y H:i', strtotime($agency->updated_at)),
            'generated_date' => date('d F Y H:i')
        ];

        // Get template path
        $template_path = WP_AGENCY_PATH . 'templates/docx/agency-detail.docx';

        // Generate DOCX
        $output_path = wp_upload_dir()['path'] . '/agency-' . $agency->code . '.docx';
        $docgen->generateFromTemplate($template_path, $variables, $output_path);

        // Return file info
        $file_url = wp_upload_dir()['url'] . '/agency-' . $agency->code . '.docx';
        return [
            'file_url' => $file_url,
            'filename' => 'agency-' . $agency->code . '.docx'
        ];
    }

    /**
     * Generate PDF from DOCX template
     *
     * @param int $id Agency ID
     * @return array File URL and filename
     * @throws \Exception
     */
    public function generatePdfFromDocx(int $id): array {
        // Validate access
        $access = $this->validator->validateAccess($id);
        if (!$access['has_access']) {
            throw new \Exception('You do not have permission to view this agency');
        }

        $agency = $this->model->find($id);
        if (!$agency) {
            throw new \Exception('Agency not found');
        }

        // Initialize WP DocGen
        $docgen = new \WPDocGen\Generator();

        // Generate DOCX first (similar logic as generateDocx)
        $variables = [
            'agency_name' => $agency->name,
            'agency_code' => $agency->code,
            'total_divisiones' => $agency->division_count ?? 0,
            'created_date' => date('d F Y H:i', strtotime($agency->created_at)),
            'updated_date' => date('d F Y H:i', strtotime($agency->updated_at)),
            'generated_date' => date('d F Y H:i')
        ];

        $template_path = WP_AGENCY_PATH . 'templates/docx/agency-detail.docx';
        $docx_path = wp_upload_dir()['path'] . '/agency-' . $agency->code . '.docx';

        // Generate DOCX first
        $docgen->generateFromTemplate($template_path, $variables, $docx_path);

        // Convert DOCX to PDF
        $pdf_path = wp_upload_dir()['path'] . '/agency-' . $agency->code . '.pdf';
        $docgen->convertToPDF($docx_path, $pdf_path);

        // Clean up DOCX file
        unlink($docx_path);

        // Send PDF URL back
        $pdf_url = wp_upload_dir()['url'] . '/agency-' . $agency->code . '.pdf';
        return [
            'file_url' => $pdf_url,
            'filename' => 'agency-' . $agency->code . '.pdf'
        ];
    }
}
