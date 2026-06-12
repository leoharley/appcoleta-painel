<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Responses extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Response_model');
        $this->load->model('Questionnaire_model');
        $this->load->model('Question_model');
        $this->load->model('User_model');
        $this->load->library('form_validation');
        
        // Verificar autenticação para API
        $this->_check_api_auth();
        
        // Configurar headers JSON
        $this->output->set_content_type('application/json');
    }

    /**
     * Verificar autenticação da API
     */
    private function _check_api_auth() {
        // Pegar token do header Authorization
        $headers = apache_request_headers();
        $auth_header = '';
        
        // Verificar diferentes formas de receber o header
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $auth_header = $headers['authorization'];
        } elseif (function_exists('getallheaders')) {
            $all_headers = getallheaders();
            $auth_header = $all_headers['Authorization'] ?? $all_headers['authorization'] ?? '';
        }
        
        // Se não tem header, tentar pegar da query string (para debugging)
        if (empty($auth_header) && ENVIRONMENT === 'development') {
            $token = $this->input->get('token');
            if ($token) {
                $auth_header = 'Bearer ' . $token;
            }
        }
        
        if (empty($auth_header)) {
            $this->_api_response([
                'success' => false, 
                'message' => 'Token de acesso necessário',
                'code' => 'MISSING_TOKEN'
            ], 401);
            return;
        }
        
        // Extrair token do header Bearer
        if (!preg_match('/Bearer\s+(.+)/i', $auth_header, $matches)) {
            $this->_api_response([
                'success' => false, 
                'message' => 'Formato de token inválido. Use: Bearer {token}',
                'code' => 'INVALID_TOKEN_FORMAT'
            ], 401);
            return;
        }
        
        $token = trim($matches[1]);
        
        // Verificar token
        $user = $this->_verify_token($token);
        if (!$user) {
            $this->_api_response([
                'success' => false, 
                'message' => 'Token inválido ou expirado',
                'code' => 'INVALID_TOKEN'
            ], 401);
            return;
        }
        
        // Verificar se o usuário tem permissão para acessar dados
        if (!in_array($user->role, ['aplicador', 'supervisor', 'administrador'])) {
            $this->_api_response([
                'success' => false, 
                'message' => 'Acesso negado para este tipo de usuário',
                'code' => 'ACCESS_DENIED'
            ], 403);
            return;
        }
        
        // Armazenar dados do usuário
        $this->current_user = $user;
        
        // Log da requisição (opcional)
        log_message('info', "API Request: {$this->uri->uri_string()} - User: {$user->id} ({$user->username})");
    }

    /**
     * Verificar token (implementar de acordo com seu sistema)
     */
    private function _verify_token($token) {
        try {
            // Se você estiver usando JWT, descomente e ajuste este código:
            /*
            $this->load->library('jwt');
            $decoded = $this->jwt->decode($token, $this->config->item('jwt_secret'));
            
            if (!$decoded || !isset($decoded->user_id)) {
                return false;
            }
            
            $user = $this->User_model->get_by_id($decoded->user_id);
            return $user && $user->is_active ? $user : false;
            */
            
            // Para implementação simples com sessão/banco:
            // Verificar se o token existe na tabela de tokens (se você tiver uma)
            $this->db->where('token', $token);
            $this->db->where('expires_at >', date('Y-m-d H:i:s'));
            $token_record = $this->db->get('user_tokens')->row(); // Assumindo que você tem esta tabela
            
            if (!$token_record) {
                // Fallback: verificar se é um token de sessão válido
                $this->db->where('session_id', $token);
                $session_record = $this->db->get('ci_sessions')->row();
                
                if ($session_record) {
                    $session_data = $session_record->data ?? '';
                    if (strpos($session_data, 'admin_logged_in') !== false) {
                        // Extrair user_id da sessão
                        preg_match('/user_id.*?i:(\d+)/', $session_data, $matches);
                        $user_id = isset($matches[1]) ? (int)$matches[1] : null;
                        
                        if ($user_id) {
                            return $this->User_model->get_by_id($user_id);
                        }
                    }
                }
                return false;
            }
            
            // Buscar dados do usuário
            $user = $this->User_model->get_by_id($token_record->user_id);
            return $user && $user->is_active ? $user : false;
            
        } catch (Exception $e) {
            log_message('error', 'Erro na verificação do token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Resposta padronizada da API
     */
    private function _api_response($data, $status_code = 200) {
        // Adicionar timestamp se não existir
        if (!isset($data['timestamp'])) {
            $data['timestamp'] = date('c'); // ISO 8601
        }
        
        // Adicionar informações de debug em desenvolvimento
        if (ENVIRONMENT === 'development' && $status_code >= 400) {
            $data['debug'] = [
                'method' => $this->input->method(),
                'uri' => $this->uri->uri_string(),
                'user_agent' => $this->input->user_agent(),
                'ip' => $this->input->ip_address()
            ];
        }
        
        // Configurar headers CORS se necessário
        $this->output->set_header('Access-Control-Allow-Origin: *');
        $this->output->set_header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        $this->output->set_header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        $this->output
            ->set_status_header($status_code)
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
        // Log de erro se necessário
        if ($status_code >= 400) {
            log_message('error', "API Error {$status_code}: " . json_encode($data));
        }
    }

    public function _remap($method, $params = []) {
        // Lidar com requisições OPTIONS para CORS
        if ($this->input->method() === 'options') {
            $this->output
                ->set_status_header(200)
                ->set_header('Access-Control-Allow-Origin: *')
                ->set_header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS')
                ->set_header('Access-Control-Allow-Headers: Content-Type, Authorization')
                ->set_output('');
            return;
        }
        
        // Chamar o método normal
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $params);
        } else {
            $this->_api_response([
                'success' => false,
                'message' => 'Endpoint não encontrado',
                'code' => 'ENDPOINT_NOT_FOUND'
            ], 404);
        }
    }

    /**
     * ENDPOINT: Obter questões de um questionário específico
     * GET /api/questionnaires/{id}/questions
     */
    public function questionnaire_questions($questionnaire_id) {
        try {
            if (!$questionnaire_id || !is_numeric($questionnaire_id)) {
                $this->_api_response([
                    'success' => false,
                    'message' => 'ID do questionário inválido'
                ], 400);
                return;
            }

            // Verificar se o questionário existe
            $questionnaire = $this->Questionnaire_model->get_by_id($questionnaire_id);
            if (!$questionnaire) {
                $this->_api_response([
                    'success' => false,
                    'message' => 'Questionário não encontrado'
                ], 404);
                return;
            }

            // Buscar questões do questionário
            $questions = $this->Question_model->get_by_questionnaire($questionnaire_id);
            
            $formatted_questions = [];
            foreach ($questions as $question) {
                $formatted_questions[] = [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'order_index' => $question->order_index,
                    'is_required' => $question->is_required
                ];
            }

            $this->_api_response([
                'success' => true,
                'data' => $formatted_questions,
                'message' => count($formatted_questions) . ' questões encontradas'
            ]);

        } catch (Exception $e) {
            log_message('error', 'Erro em questionnaire_questions: ' . $e->getMessage());
            $this->_api_response([
                'success' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * ENDPOINT: Obter dados brutos para exportação
     * GET /api/responses/raw-data
     */
    public function raw_data() {
        try {
            // Obter filtros da query string
            $filters = [];
            
            if ($this->input->get('questionnaire_id')) {
                $filters['questionnaire_id'] = $this->input->get('questionnaire_id');
            }
            if ($this->input->get('date_from')) {
                $filters['date_from'] = $this->input->get('date_from');
            }
            if ($this->input->get('date_to')) {
                $filters['date_to'] = $this->input->get('date_to');
            }
            if ($this->input->get('applied_by')) {
                $filters['applied_by'] = $this->input->get('applied_by');
            }

            // Validar filtros
            $validation_errors = $this->Response_model->validate_export_filters($filters);
            if (!empty($validation_errors)) {
                $this->_api_response([
                    'success' => false,
                    'message' => 'Filtros inválidos',
                    'errors' => $validation_errors
                ], 400);
                return;
            }

            // Buscar dados brutos
            $raw_data = $this->Response_model->get_raw_data_for_export($filters);

            $this->_api_response([
                'success' => true,
                'data' => $raw_data,
                'count' => count($raw_data),
                'message' => count($raw_data) . ' registros encontrados'
            ]);

        } catch (Exception $e) {
            log_message('error', 'Erro em raw_data: ' . $e->getMessage());
            $this->_api_response([
                'success' => false,
                'message' => 'Erro ao buscar dados brutos'
            ], 500);
        }
    }

    /**
     * ENDPOINT: Contar registros para exportação
     * GET /api/responses/count
     */
    public function count_records() {
        try {
            // Obter filtros da query string
            $filters = [];
            
            if ($this->input->get('questionnaire_id')) {
                $filters['questionnaire_id'] = $this->input->get('questionnaire_id');
            }
            if ($this->input->get('date_from')) {
                $filters['date_from'] = $this->input->get('date_from');
            }
            if ($this->input->get('date_to')) {
                $filters['date_to'] = $this->input->get('date_to');
            }
            if ($this->input->get('applied_by')) {
                $filters['applied_by'] = $this->input->get('applied_by');
            }

            // Contar registros
            $count = $this->Response_model->count_by_filters($filters);

            $this->_api_response([
                'success' => true,
                'data' => [
                    'count' => $count,
                    'filters_applied' => count($filters)
                ],
                'message' => $count . ' registros encontrados'
            ]);

        } catch (Exception $e) {
            log_message('error', 'Erro em count_records: ' . $e->getMessage());
            $this->_api_response([
                'success' => false,
                'message' => 'Erro ao contar registros'
            ], 500);
        }
    }

    /**
     * ENDPOINT: Validar filtros de exportação
     * POST /api/responses/validate-export
     */
    public function validate_export() {
        try {
            // Obter dados do JSON
            $input = json_decode($this->input->raw_input_stream, true);
            
            if (!$input) {
                $this->_api_response([
                    'success' => false,
                    'message' => 'Dados inválidos'
                ], 400);
                return;
            }

            // Validar filtros
            $validation_errors = $this->Response_model->validate_export_filters($input);
            
            if (empty($validation_errors)) {
                $count = $this->Response_model->count_by_filters($input);
                $statistics = $this->Response_model->get_export_statistics($input);
                
                $this->_api_response([
                    'success' => true,
                    'data' => [
                        'count' => $count,
                        'statistics' => $statistics
                    ],
                    'message' => 'Filtros válidos. ' . $count . ' registros serão exportados.'
                ]);
            } else {
                $this->_api_response([
                    'success' => false,
                    'errors' => $validation_errors,
                    'message' => implode(' ', $validation_errors)
                ], 400);
            }

        } catch (Exception $e) {
            log_message('error', 'Erro em validate_export: ' . $e->getMessage());
            $this->_api_response([
                'success' => false,
                'message' => 'Erro na validação'
            ], 500);
        }
    }

    /**
     * ENDPOINT: Preview dos dados de exportação
     * GET /api/responses/export-preview
     */
    public function export_preview() {
        try {
            // Obter filtros da query string
            $filters = [];
            $limit = (int)$this->input->get('limit') ?: 5;
            
            if ($this->input->get('questionnaire_id')) {
                $filters['questionnaire_id'] = $this->input->get('questionnaire_id');
            }
            if ($this->input->get('date_from')) {
                $filters['date_from'] = $this->input->get('date_from');
            }
            if ($this->input->get('date_to')) {
                $filters['date_to'] = $this->input->get('date_to');
            }
            if ($this->input->get('applied_by')) {
                $filters['applied_by'] = $this->input->get('applied_by');
            }

            // Buscar preview
            $preview_data = $this->Response_model->get_export_preview($filters, $limit);
            $total_count = $this->Response_model->count_by_filters($filters);

            $this->_api_response([
                'success' => true,
                'data' => [
                    'preview' => $preview_data,
                    'total_count' => $total_count,
                    'preview_count' => count($preview_data)
                ],
                'message' => 'Preview de ' . count($preview_data) . ' registros de um total de ' . $total_count
            ]);

        } catch (Exception $e) {
            log_message('error', 'Erro em export_preview: ' . $e->getMessage());
            $this->_api_response([
                'success' => false,
                'message' => 'Erro ao gerar preview'
            ], 500);
        }
    }

    /**
     * ENDPOINT: Estatísticas da exportação
     * GET /api/responses/export-statistics
     */
    public function export_statistics() {
        try {
            // Obter filtros da query string
            $filters = [];
            
            if ($this->input->get('questionnaire_id')) {
                $filters['questionnaire_id'] = $this->input->get('questionnaire_id');
            }
            if ($this->input->get('date_from')) {
                $filters['date_from'] = $this->input->get('date_from');
            }
            if ($this->input->get('date_to')) {
                $filters['date_to'] = $this->input->get('date_to');
            }
            if ($this->input->get('applied_by')) {
                $filters['applied_by'] = $this->input->get('applied_by');
            }

            // Buscar estatísticas
            $statistics = $this->Response_model->get_export_statistics($filters);

            $this->_api_response([
                'success' => true,
                'data' => $statistics,
                'message' => 'Estatísticas da exportação carregadas'
            ]);

        } catch (Exception $e) {
            log_message('error', 'Erro em export_statistics: ' . $e->getMessage());
            $this->_api_response([
                'success' => false,
                'message' => 'Erro ao carregar estatísticas'
            ], 500);
        }
    }

    /**
     * ENDPOINT: Registrar log de atividade de exportação
     * POST /api/responses/log-export
     */
    public function log_export() {
        try {
            // Obter dados do JSON
            $input = json_decode($this->input->raw_input_stream, true);
            
            if (!$input) {
                $this->_api_response([
                    'success' => false,
                    'message' => 'Dados inválidos'
                ], 400);
                return;
            }

            // Registrar log
            $this->Response_model->log_export_activity(
                $this->current_user->id,
                $input['export_type'] ?? 'raw_data',
                $input['filters'] ?? [],
                $input['record_count'] ?? 0,
                $input['status'] ?? 'unknown',
                $input['error_message'] ?? null
            );

            $this->_api_response([
                'success' => true,
                'message' => 'Log registrado com sucesso'
            ]);

        } catch (Exception $e) {
            log_message('error', 'Erro em log_export: ' . $e->getMessage());
            $this->_api_response([
                'success' => false,
                'message' => 'Erro ao registrar log'
            ], 500);
        }
    }

    /**
     * ENDPOINT: Verificar limites de exportação
     * GET /api/responses/export-limits
     */
    public function export_limits() {
        try {
            $limits = $this->Response_model->check_export_limits($this->current_user->id);

            $this->_api_response([
                'success' => true,
                'data' => $limits,
                'message' => 'Limites de exportação verificados'
            ]);

        } catch (Exception $e) {
            log_message('error', 'Erro em export_limits: ' . $e->getMessage());
            $this->_api_response([
                'success' => false,
                'message' => 'Erro ao verificar limites'
            ], 500);
        }
    }

    /**
     * ENDPOINT: Histórico de exportações
     * GET /api/responses/export-history
     */
    public function export_history() {
        try {
            $limit = (int)$this->input->get('limit') ?: 10;
            
            $history = $this->Response_model->get_user_export_history($this->current_user->id, $limit);

            $this->_api_response([
                'success' => true,
                'data' => $history,
                'count' => count($history),
                'message' => count($history) . ' registros no histórico'
            ]);

        } catch (Exception $e) {
            log_message('error', 'Erro em export_history: ' . $e->getMessage());
            $this->_api_response([
                'success' => false,
                'message' => 'Erro ao carregar histórico'
            ], 500);
        }
    }
    
}