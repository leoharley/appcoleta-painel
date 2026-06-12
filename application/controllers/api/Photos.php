<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Photos extends CI_Controller {

    public function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        if ($this->input->method() === 'options') {
            exit();
        }
    }

    public function upload() {
        if ($this->input->method() !== 'post') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        // Verificar autenticação
        $user_id = $this->verify_auth();
        if (!$user_id) {
            return;
        }

        if (!isset($_FILES['photo'])) {
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => 'No photo file provided']);
            return;
        }

        $config['upload_path'] = './uploads/photos/';
        $config['allowed_types'] = 'jpg|jpeg|png';
        $config['max_size'] = 1000240; // 10MB
        $config['encrypt_name'] = FALSE;

        // Criar diretório se não existir
        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0755, true);
        }

        $this->load->library('upload', $config);

        if ($this->upload->do_upload('photo')) {
            $file_data = $this->upload->data();
            
            echo json_encode([
                'success' => true,
                'filename' => $file_data['file_name'],
                'file_size' => $file_data['file_size'],
                'file_type' => $file_data['file_type']
            ]);
        } else {
            $this->output->set_status_header(400);
            echo json_encode([
                'success' => false,
                'message' => $this->upload->display_errors('', '')
            ]);
        }
    }

    private function verify_auth() {
        $headers = $this->input->request_headers();
        
        if (!isset($headers['Authorization'])) {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Authorization required']);
            return false;
        }

        return true; // Simplified for this example
    }
}
