<?php
/**
* Generate agency detail document using WP DocGen
* 
* File: class-agency-detail-document-provider.php
* Path: /wp-agency/includes/docgen/agency-detail/class-agency-detail-provider.php
*/ 

class WP_Agency_Agency_Detail_Provider implements WP_DocGen_Provider {
    private $agency;
    private $template_path;
    private $output_dir;
    
    public function __construct($agency) {
        $this->agency = $agency;
        $this->template_path = WP_AGENCY_PATH . 'templates/docx/agency-detail.docx';
        $this->output_dir = wp_upload_dir()['path'];
    }
    
    public function get_data() {
        return [
            'agency_name' => $this->agency->name,
            'agency_code' => $this->agency->code,
            'total_divisiones' => $this->agency->division_count,
            'created_date' => date('d F Y H:i', strtotime($this->agency->created_at)),
            'updated_date' => date('d F Y H:i', strtotime($this->agency->updated_at)),
            'npwp' => $this->agency->npwp ?? '-',
            'nib' => $this->agency->nib ?? '-',
            'generated_date' => date('d F Y H:i')
        ];
    }
    
    public function get_template_path() {
        return $this->template_path;
    }
    
    public function get_output_filename() {
        return 'agency-' . $this->agency->code;
    }
    
    public function get_output_format() {
        return 'docx';
    }
    
    public function get_temp_dir() {
        return $this->output_dir;
    }
}
