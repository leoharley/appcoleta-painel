<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class System extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->output->set_content_type('application/json');
    }

    /**
     * Health check endpoint
     * GET /api/health
     */
    public function health() {
        try {
            // Verificar conexão com banco de dados
            $db_status = $this->db->conn_id ? 'connected' : 'disconnected';
            
            // Verificar se tabelas principais existem
            $tables_check = [];
            $required_tables = ['users', 'questionnaires', 'form_responses', 'questions'];
            
            foreach ($required_tables as $table) {
                $tables_check[$table] = $this->db->table_exists($table);
            }
            
            // Status geral
            $all_tables_ok = !in_array(false, $tables_check);
            $status = ($db_status === 'connected' && $all_tables_ok) ? 'healthy' : 'unhealthy';
            
            $response = [
                'status' => $status,
                'timestamp' => date('Y-m-d H:i:s'),
                'database' => $db_status,
                'tables' => $tables_check,
                'version' => '1.0.0',
                'environment' => ENVIRONMENT
            ];
            
            $status_code = ($status === 'healthy') ? 200 : 503;
            
            $this->output
                ->set_status_header($status_code)
                ->set_output(json_encode($response, JSON_UNESCAPED_UNICODE));
                
        } catch (Exception $e) {
            $this->output
                ->set_status_header(503)
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Health check failed',
                    'timestamp' => date('Y-m-d H:i:s')
                ], JSON_UNESCAPED_UNICODE));
        }
    }
}