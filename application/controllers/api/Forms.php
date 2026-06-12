<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Forms extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Response_model');
        $this->load->model('Questionnaire_model');
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        if ($this->input->method() === 'options') {
            exit();
        }
    }

    public function submit() {
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

        $json = json_decode(file_get_contents('php://input'), true);
        
        if (!$json) {
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            return;
        }

        // Validar dados obrigatórios
        $required_fields = ['questionnaire_id', 'applied_by', 'responses'];
        foreach ($required_fields as $field) {
            if (!isset($json[$field])) {
                $this->output->set_status_header(400);
                echo json_encode(['success' => false, 'message' => "Field '{$field}' is required"]);
                return;
            }
        }

        try {
            $this->db->trans_start();

            // Normalização da localização — preserva NULL quando ausente, vazio ou inválido.
            // Nunca converter null/"" para 0 (0,0 é um ponto no oceano).
            $lat_in = $json['latitude'] ?? null;
            $lng_in = $json['longitude'] ?? null;
            $latitude  = (is_numeric($lat_in) && $lat_in >= -90  && $lat_in <= 90)  ? (float)$lat_in : null;
            $longitude = (is_numeric($lng_in) && $lng_in >= -180 && $lng_in <= 180) ? (float)$lng_in : null;
            $location_name_in = $json['location_name'] ?? null;
            $location_name = (is_string($location_name_in) && trim($location_name_in) !== '') ? $location_name_in : null;

            // Criar resposta do formulário
            $form_data = [
                'questionnaire_id' => $json['questionnaire_id'],
                'respondent_name' => $json['respondent_name'] ?? null,
                'respondent_email' => $json['respondent_email'] ?? null,
                'applied_by' => $json['applied_by'],
                'latitude' => $latitude,
                'longitude' => $longitude,
                'location_name' => $location_name,
                'photo_path' => $json['photo_path'] ?? null,
                'photo_path_2' => $json['photo_path_2'] ?? null,
                'audio_path' => $json['audio_path'] ?? null,
                'consent_given' => isset($json['consent_given']) ? (bool)$json['consent_given'] : false,
                'sync_status' => 'synced',
                'started_at' => isset($json['started_at']) ? date('Y-m-d H:i:s', strtotime($json['started_at'])) : null,
                'completed_at' => isset($json['completed_at']) ? date('Y-m-d H:i:s', strtotime($json['completed_at'])) : date('Y-m-d H:i:s')
            ];

            $form_response_id = $this->Response_model->create($form_data);

            if (!$form_response_id) {
                throw new Exception('Failed to create form response');
            }

            // Salvar respostas individuais
            foreach ($json['responses'] as $response) {
                if (!isset($response['question_id'])) {
                    continue;
                }

                $answer_data = [
                    'form_response_id' => $form_response_id,
                    'question_id' => $response['question_id'],
                    'response_text' => $response['response_text'] ?? null,
                    'response_number' => $response['response_number'] ?? null,
                    'response_date' => isset($response['response_date']) ? date('Y-m-d', strtotime($response['response_date'])) : null,
                    'response_datetime' => isset($response['response_datetime']) ? date('Y-m-d H:i:s', strtotime($response['response_datetime'])) : null,
                    'selected_options' => isset($response['selected_options']) ? json_encode($response['selected_options']) : null
                ];

                $this->Response_model->create_answer($answer_data);
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }

            echo json_encode([
                'success' => true,
                'form_response_id' => $form_response_id,
                'message' => 'Form submitted successfully'
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->output->set_status_header(500);
            echo json_encode(['success' => false, 'message' => 'Failed to submit form: ' . $e->getMessage()]);
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