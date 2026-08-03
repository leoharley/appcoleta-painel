<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Questionnaires extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Questionnaire_model');
        $this->load->model('User_model');
        $this->load->helper('cors');
        cors_setup();
        header('Content-Type: application/json');
    }

    public function index() {
        if ($this->input->method() !== 'get') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        // Verificar token de autenticação
        $user_id = $this->verify_auth();
        if (!$user_id) {
            return;
        }

        try {
            // Descobre o papel do usuário do token para decidir se filtra
            $user = $this->User_model->get_by_id($user_id);
            if (!$user) {
                $this->output->set_status_header(401);
                echo json_encode(['success' => false, 'message' => 'User not found']);
                return;
            }

            // Aplicadores só recebem os questionários atribuídos a eles;
            // supervisores/administradores continuam vendo todos os ativos.
            if ($user->role === 'aplicador') {
                $questionnaires = $this->Questionnaire_model->get_for_aplicador($user_id);
            } else {
                $questionnaires = $this->Questionnaire_model->get_for_api();
            }

            echo json_encode([
                'success' => true,
                'data'    => $questionnaires
            ]);
        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    private function verify_auth() {
        $headers = $this->input->request_headers();
        
        if (!isset($headers['Authorization'])) {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Authorization required']);
            return false;
        }

        $auth_header = $headers['Authorization'];
        if (strpos($auth_header, 'Bearer ') !== 0) {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Invalid authorization format']);
            return false;
        }

        $token = substr($auth_header, 7);
        $user_id = $this->verify_token($token);
        
        if (!$user_id) {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            return false;
        }

        return $user_id;
    }

    private function verify_token($token) {
        try {
            $payload = json_decode(base64_decode($token), true);
            
            if (!$payload || !isset($payload['user_id']) || !isset($payload['expires_at'])) {
                return false;
            }
            
            if (time() > $payload['expires_at']) {
                return false;
            }
            
            return $payload['user_id'];
        } catch (Exception $e) {
            return false;
        }
    }
}