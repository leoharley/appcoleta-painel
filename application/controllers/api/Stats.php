<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stats extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Response_model');
        $this->load->model('Questionnaire_model');
        $this->load->model('User_model');
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        if ($this->input->method() === 'options') {
            exit();
        }
    }

    /**
     * GET /api/stats/user/{user_id}
     * Retorna estatísticas específicas de um usuário aplicador
     */
    public function user($user_id = null) {
        if ($this->input->method() !== 'get') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        // Verificar autenticação
        $authenticated_user = $this->verify_auth();
        if (!$authenticated_user) {
            return;
        }

        // Se não foi especificado user_id, usar o do token
        if (!$user_id) {
            $user_id = $authenticated_user;
        }

        // CORREÇÃO: Administradores podem ver dados agregados de todos os aplicadores
        $is_admin_or_supervisor = $this->Response_model->is_supervisor_or_admin($authenticated_user);
        
        // Verificar se o usuário pode acessar essas estatísticas
        if ($authenticated_user != $user_id && !$is_admin_or_supervisor) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        try {
            // CORREÇÃO: Para administradores, mostrar dados agregados se não especificar user_id
            if ($is_admin_or_supervisor && !$this->input->get('user_id')) {
                // Dados agregados para administradores/supervisores
                $filters = [];
                
                $total_forms = $this->Response_model->count_by_filters($filters);
                $today_forms = $this->Response_model->count_by_filters([
                    'date_from' => date('Y-m-d'),
                    'date_to' => date('Y-m-d')
                ]);
                $pending_sync = $this->Response_model->count_by_filters([
                    'sync_status' => 'pending'
                ]);
                $photos_captured = $this->Response_model->count_photos($filters);
                
                // Taxa de sucesso geral
                $synced_forms = $this->Response_model->count_by_filters([
                    'sync_status' => 'synced'
                ]);
                $success_rate = $total_forms > 0 ? round(($synced_forms / $total_forms) * 100) : 100;
                
                // Dados agregados de todos os aplicadores
                $total_applicators = $this->Response_model->count_applicators('active');
                $active_days = $this->get_system_active_days(30);
                
                // Atividade recente de todo o sistema
                $recent_activity = $this->format_system_recent_activity(
                    $this->Response_model->get_recent(10)
                );
                
                // Estatísticas de período do sistema
                $weekly_stats = $this->get_system_period_stats(7);
                $monthly_stats = $this->get_system_period_stats(30);
                
                // Top questionários do sistema
                $top_questionnaires = $this->format_top_questionnaires(
                    $this->Response_model->get_questionnaires_popularity([], 5)
                );

                $stats = [
                    'user_id' => (int)$authenticated_user,
                    'user_type' => 'admin_aggregated',
                    'summary' => [
                        'total_forms' => (int)$total_forms,
                        'today_forms' => (int)$today_forms,
                        'pending_sync' => (int)$pending_sync,
                        'success_rate' => (int)$success_rate,
                        'active_days' => (int)$active_days,
                        'photos_captured' => (int)$photos_captured
                    ],
                    'recent_activity' => $recent_activity,
                    'period_stats' => [
                        'weekly' => $weekly_stats,
                        'monthly' => $monthly_stats
                    ],
                    'top_questionnaires' => $top_questionnaires,
                    'system_info' => [
                        'total_applicators' => (int)$total_applicators,
                        'data_source' => 'aggregated_from_all_applicators'
                    ],
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            } else {
                // Dados específicos de um usuário (comportamento original)
                $filters = ['applied_by' => $user_id];
                
                $total_forms = $this->Response_model->count_by_filters($filters);
                $today_forms = $this->Response_model->count_by_filters(array_merge($filters, [
                    'date_from' => date('Y-m-d'),
                    'date_to' => date('Y-m-d')
                ]));
                $pending_sync = $this->Response_model->count_by_filters(array_merge($filters, [
                    'sync_status' => 'pending'
                ]));
                $photos_captured = $this->Response_model->count_photos($filters);

                // Taxa de sucesso
                $synced_forms = $this->Response_model->count_by_filters(array_merge($filters, [
                    'sync_status' => 'synced'
                ]));
                $success_rate = $total_forms > 0 ? round(($synced_forms / $total_forms) * 100) : 100;

                // Dados usando métodos específicos do model
                $active_days = $this->Response_model->get_active_days($user_id, 30);
                $recent_activity = $this->format_recent_activity(
                    $this->Response_model->get_recent_activity($user_id, 10)
                );
                $weekly_stats = $this->Response_model->get_period_stats($user_id, 7);
                $monthly_stats = $this->Response_model->get_period_stats($user_id, 30);
                $top_questionnaires = $this->format_top_questionnaires(
                    $this->Response_model->get_top_questionnaires_by_user($user_id, 5)
                );

                $stats = [
                    'user_id' => (int)$user_id,
                    'user_type' => 'individual',
                    'summary' => [
                        'total_forms' => (int)$total_forms,
                        'today_forms' => (int)$today_forms,
                        'pending_sync' => (int)$pending_sync,
                        'success_rate' => (int)$success_rate,
                        'active_days' => (int)$active_days,
                        'photos_captured' => (int)$photos_captured
                    ],
                    'recent_activity' => $recent_activity,
                    'period_stats' => [
                        'weekly' => $weekly_stats,
                        'monthly' => $monthly_stats
                    ],
                    'top_questionnaires' => $top_questionnaires,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to get user stats: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * GET /api/stats/overview
     * Retorna estatísticas gerais do sistema (apenas para admins)
     */
    public function overview() {
        if ($this->input->method() !== 'get') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        // Verificar autenticação
        $authenticated_user = $this->verify_auth();
        if (!$authenticated_user) {
            return;
        }

        // Verificar se é admin
        if (!$this->is_admin($authenticated_user)) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            return;
        }

        try {
            $filters = [];
            
            // Usar métodos do model para estatísticas gerais
            $total_responses = $this->Response_model->count_by_filters($filters);
            $unique_respondents = $this->Response_model->count_unique_respondents($filters);
            $total_locations = $this->Response_model->count_locations($filters);
            $total_photos = $this->Response_model->count_photos($filters);
            $consent_rate = $this->Response_model->get_consent_rate($filters);

            // Questionários
            $active_questionnaires = $this->Questionnaire_model->count_active();
            $total_questionnaires = $this->Questionnaire_model->count_all();

            // Top aplicadores e dados por dia
            $top_applicators = $this->Response_model->get_top_applicators($filters, 10);
            $responses_by_day = $this->Response_model->get_responses_by_day_filtered($filters, 30);

            $overview = [
                'summary' => [
                    'total_responses' => (int)$total_responses,
                    'unique_respondents' => (int)$unique_respondents,
                    'total_locations' => (int)$total_locations,
                    'total_photos' => (int)$total_photos,
                    'consent_rate' => (float)$consent_rate,
                    'active_questionnaires' => (int)$active_questionnaires,
                    'total_questionnaires' => (int)$total_questionnaires
                ],
                'top_applicators' => $top_applicators,
                'responses_by_day' => $responses_by_day,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            echo json_encode([
                'success' => true,
                'data' => $overview
            ]);

        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to get overview stats: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * GET /api/stats/applicators
     * Retorna estatísticas de todos os aplicadores (apenas para supervisores/admins)
     */
    public function applicators() {
        if ($this->input->method() !== 'get') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        // Verificar autenticação
        $authenticated_user = $this->verify_auth();
        if (!$authenticated_user) {
            return;
        }

        // Verificar se é supervisor ou admin
        if (!$this->Response_model->is_supervisor_or_admin($authenticated_user)) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Supervisor/Admin access required']);
            return;
        }

        try {
            // Obter filtros da URL
            $filters = [
                'date_from' => $this->input->get('date_from'),
                'date_to' => $this->input->get('date_to'),
                'status' => $this->input->get('status'), // active, inactive, all
            ];

            // Usar métodos do model
            $applicators_stats = $this->format_applicators_stats(
                $this->Response_model->get_applicators_stats($filters)
            );
            $location_stats = $this->Response_model->get_location_stats($filters);
            $summary = $this->Response_model->get_supervisor_summary($filters);

            $response = [
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'applicators' => $applicators_stats,
                    'locations' => $location_stats,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to get applicators stats: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * GET /api/stats/locations
     * Retorna dados de localizações para o mapa (apenas para supervisores/admins)
     */
    public function locations() {
        if ($this->input->method() !== 'get') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        // Verificar autenticação
        $authenticated_user = $this->verify_auth();
        if (!$authenticated_user) {
            return;
        }

        // Verificar se é supervisor ou admin
        if (!$this->Response_model->is_supervisor_or_admin($authenticated_user)) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Supervisor/Admin access required']);
            return;
        }

        try {
            // Obter filtros da URL
            $filters = [
                'date_from' => $this->input->get('date_from') ?: date('Y-m-d', strtotime('-30 days')),
                'date_to' => $this->input->get('date_to') ?: date('Y-m-d'),
                'applicator_id' => $this->input->get('applicator_id'),
            ];

            // Usar métodos do model
            $map_data = $this->format_map_data(
                $this->Response_model->get_map_locations($filters)
            );
            $coverage_stats = $this->Response_model->get_coverage_stats($filters);

            $response = [
                'success' => true,
                'data' => [
                    'map_data' => $map_data,
                    'coverage' => $coverage_stats,
                    'filters_applied' => $filters,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to get location data: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * GET /api/stats/history/{user_id}
     * Retorna histórico de aplicação de questionários de um usuário
     */
    public function history($user_id = null) {
        if ($this->input->method() !== 'get') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

    // Verificar autenticação
        $authenticated_user = $this->verify_auth();
        if (!$authenticated_user) {
            return;
        }

        // Se não foi especificado user_id, usar o do token
        if (!$user_id) {
            $user_id = $authenticated_user;
        }

        // Verificar permissões
        $is_admin_or_supervisor = $this->Response_model->is_supervisor_or_admin($authenticated_user);
        
        if ($authenticated_user != $user_id && !$is_admin_or_supervisor) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        try {
            // Obter filtros da URL
            $filters = [
                'period' => $this->input->get('period') ?: 'all', // all, today, week, month
                'sync_status' => $this->input->get('sync_status'), // pending, synced, error
                'questionnaire_id' => $this->input->get('questionnaire_id'),
                'limit' => $this->input->get('limit') ?: 50,
                'offset' => $this->input->get('offset') ?: 0
            ];

            // Buscar histórico usando o Questionnaire_model
            $history_data = $this->Questionnaire_model->get_application_history($user_id, $filters);
            
            // Buscar contadores para os filtros
            $counters = $this->Questionnaire_model->get_history_counters($user_id);

            // Formatar resposta
            $response = [
                'success' => true,
                'data' => [
                    'user_id' => (int)$user_id,
                    'total_applications' => (int)$counters['total'],
                    'counters' => [
                        'all' => (int)$counters['total'],
                        'today' => (int)$counters['today'],
                        'week' => (int)$counters['week'],
                        'pending' => (int)$counters['pending'],
                        'synced' => (int)$counters['synced'],
                        'error' => (int)$counters['error']
                    ],
                    'applications' => $this->format_history_applications($history_data),
                    'pagination' => [
                        'limit' => (int)$filters['limit'],
                        'offset' => (int)$filters['offset'],
                        'has_more' => count($history_data) === (int)$filters['limit']
                    ],
                    'filters_applied' => $filters,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to get application history: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * GET /api/stats/history/{user_id}/summary
     * Retorna resumo do histórico de aplicações
     */
    public function history_summary($user_id = null) {
        if ($this->input->method() !== 'get') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        // Verificar autenticação
        $authenticated_user = $this->verify_auth();
        if (!$authenticated_user) {
            return;
        }

        // Se não foi especificado user_id, usar o do token
        if (!$user_id) {
            $user_id = $authenticated_user;
        }

        // Verificar permissões
        $is_admin_or_supervisor = $this->Response_model->is_supervisor_or_admin($authenticated_user);
        
        if ($authenticated_user != $user_id && !$is_admin_or_supervisor) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        try {
            // Buscar dados de resumo
            $summary = $this->Questionnaire_model->get_history_summary($user_id);
            $recent_activity = $this->Questionnaire_model->get_recent_applications($user_id, 5);
            $questionnaires_stats = $this->Questionnaire_model->get_user_questionnaire_stats($user_id);

            $response = [
                'success' => true,
                'data' => [
                    'user_id' => (int)$user_id,
                    'summary' => $summary,
                    'recent_activity' => $this->format_recent_applications($recent_activity),
                    'questionnaires_stats' => $questionnaires_stats,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to get history summary: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Formatar dados de atividade recente
     */
    private function format_recent_activity($activities) {
        $formatted_activities = [];
        
        foreach ($activities as $activity) {
            $time_diff = $this->time_elapsed_string($activity->completed_at);
            
            $formatted_activities[] = [
                'id' => (int)$activity->id,
                'action' => 'Formulário aplicado',
                'description' => $activity->questionnaire_title,
                'time' => $time_diff,
                'sync_status' => $activity->sync_status,
                'respondent_name' => $activity->respondent_name
            ];
        }
        
        return $formatted_activities;
    }

    /**
     * Formatar dados de questionários mais aplicados
     */
    private function format_top_questionnaires($questionnaires) {
        $formatted = [];
        
        foreach ($questionnaires as $questionnaire) {
            // Verificar se as propriedades existem antes de acessá-las
            $total_applications = isset($questionnaire->total_applications) 
                ? $questionnaire->total_applications 
                : (isset($questionnaire->total_responses) ? $questionnaire->total_responses : 0);
                
            $last_application = isset($questionnaire->last_application) 
                ? $questionnaire->last_application 
                : null;
            
            $formatted[] = [
                'id' => (int)$questionnaire->id,
                'title' => $questionnaire->title,
                'total_applications' => (int)$total_applications,
                'last_application' => $last_application,
                'last_application_time' => $last_application ? $this->time_elapsed_string($last_application) : 'Nunca'
            ];
        }
        
        return $formatted;
    }

    /**
     * Formatar dados de aplicadores
     */
    private function format_applicators_stats($applicators) {
        foreach ($applicators as $applicator) {
            $applicator->last_activity_formatted = $applicator->last_activity 
                ? $this->time_elapsed_string($applicator->last_activity) 
                : 'Nunca';
            $applicator->status = $applicator->is_active ? 'active' : 'inactive';
            $applicator->primary_location = $this->Response_model->get_applicator_primary_location($applicator->id);
        }
        
        return $applicators;
    }

    /**
     * Formatar dados do mapa
     */
    private function format_map_data($locations) {
        $map_points = [];
        
        foreach ($locations as $location) {
            $map_points[] = [
                'lat' => (float)$location->latitude,
                'lng' => (float)$location->longitude,
                'applicator_id' => (int)$location->applied_by,
                'applicator_name' => $location->applicator_name,
                'location_name' => $location->location_name,
                'forms_count' => (int)$location->forms_count,
                'last_activity' => $location->last_activity,
                'last_activity_formatted' => $this->time_elapsed_string($location->last_activity)
            ];
        }
        
        return $map_points;
    }

    /**
     * Formatar dados das aplicações para o frontend
     */
    private function format_history_applications($applications) {
        $formatted = [];
        
        foreach ($applications as $app) {
            $status_info = $this->get_sync_status_info($app->sync_status);
            
            $formatted[] = [
                'id' => (int)$app->id,
                'questionnaire' => [
                    'id' => (int)$app->questionnaire_id,
                    'title' => $app->questionnaire_title,
                    'code' => $app->questionnaire_code ?: sprintf('#%03d', $app->questionnaire_id)
                ],
                'respondent' => [
                    'name' => $app->respondent_name,
                    'email' => $app->respondent_email
                ],
                'location' => [
                    'name' => $app->location_name,
                    'latitude' => $app->latitude ? (float)$app->latitude : null,
                    'longitude' => $app->longitude ? (float)$app->longitude : null,
                    'full_address' => $this->format_location_address($app)
                ],
                'timing' => [
                    'started_at' => $app->started_at,
                    'completed_at' => $app->completed_at,
                    'duration_minutes' => $this->calculate_duration($app->started_at, $app->completed_at),
                    'completed_at_formatted' => $this->format_datetime_for_app($app->completed_at),
                    'time_ago' => $this->time_elapsed_string($app->completed_at)
                ],
                'sync' => [
                    'status' => $app->sync_status,
                    'status_label' => $status_info['label'],
                    'status_color' => $status_info['color'],
                    'icon' => $status_info['icon']
                ],
                'additional_data' => [
                    'has_photo' => !empty($app->photo_path),
                    'photo_path' => $app->photo_path,
                    'consent_given' => (bool)$app->consent_given,
                    'has_location' => !empty($app->latitude) && !empty($app->longitude)
                ],
                'created_at' => $app->created_at
            ];
        }
        
        return $formatted;
    }

    /**
     * Formatar aplicações recentes para resumo
     */
    private function format_recent_applications($applications) {
        $formatted = [];
        
        foreach ($applications as $app) {
            $formatted[] = [
                'id' => (int)$app->id,
                'questionnaire_title' => $app->questionnaire_title,
                'questionnaire_code' => $app->questionnaire_code ?: sprintf('#%03d', $app->questionnaire_id),
                'respondent_name' => $app->respondent_name,
                'location_name' => $app->location_name,
                'completed_at' => $app->completed_at,
                'completed_at_formatted' => $this->format_datetime_for_app($app->completed_at),
                'sync_status' => $app->sync_status,
                'time_ago' => $this->time_elapsed_string($app->completed_at)
            ];
        }
        
        return $formatted;
    }

    /**
     * Obter informações visuais do status de sincronização
     */
    private function get_sync_status_info($status) {
        switch ($status) {
            case 'synced':
                return [
                    'label' => 'Sincronizado',
                    'color' => '#4CAF50',
                    'icon' => 'check_circle'
                ];
            case 'pending':
                return [
                    'label' => 'Pendente',
                    'color' => '#FF9800',
                    'icon' => 'sync'
                ];
            case 'error':
                return [
                    'label' => 'Erro',
                    'color' => '#F44336',
                    'icon' => 'error'
                ];
            default:
                return [
                    'label' => 'Sincronizando',
                    'color' => '#2196F3',
                    'icon' => 'sync'
                ];
        }
    }

    /**
     * Formatar endereço da localização
     */
    private function format_location_address($app) {
        $parts = [];
        
        if ($app->location_name) {
            $parts[] = $app->location_name;
        }
        
        // Adicionar coordenadas se disponíveis
        if ($app->latitude && $app->longitude) {
            $parts[] = sprintf('%.6f, %.6f', $app->latitude, $app->longitude);
        }
        
        return implode(' • ', $parts) ?: 'Localização não informada';
    }

    /**
     * Calcular duração em minutos
     */
    private function calculate_duration($started_at, $completed_at) {
        if (!$started_at || !$completed_at) {
            return null;
        }
        
        $start = new DateTime($started_at);
        $end = new DateTime($completed_at);
        $diff = $start->diff($end);
        
        return ($diff->h * 60) + $diff->i;
    }

    /**
     * Formatar data/hora para o app
     */
    private function format_datetime_for_app($datetime) {
        if (!$datetime) {
            return null;
        }
        
        $date = new DateTime($datetime);
        
        // Formato: 09/06/2025 09:50
        return $date->format('d/m/Y H:i');
    }

    /**
     * Verificar autenticação e retornar user_id
     */
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

    /**
     * Verificar token e retornar user_id
     */
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

    /**
     * Verificar se usuário é admin
     */
    private function is_admin($user_id) {
        // Usar User_model se disponível, senão usar Response_model
        if (method_exists($this->User_model, 'get_by_id')) {
            $user = $this->User_model->get_by_id($user_id);
        } else {
            // Fallback usando database diretamente
            $this->db->select('role');
            $this->db->where('id', $user_id);
            $user = $this->db->get('users')->row();
        }
        
        return $user && ($user->role === 'admin' || $user->role === 'administrador');
    }

    /**
     * Calcular tempo decorrido em formato legível
     */
    private function time_elapsed_string($datetime, $full = false) {
        if (!$datetime) {
            return 'Nunca';
        }
        
        $now = new DateTime();
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'ano',
            'm' => 'mês',
            'w' => 'semana',
            'd' => 'dia',
            'h' => 'hora',
            'i' => 'minuto',
            's' => 'segundo',
        );
        
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' atrás' : 'agora mesmo';
    }

    /**
     * Obter dias ativos do sistema (todos os aplicadores)
     */
    private function get_system_active_days($days = 30) {
        $this->db->select('COUNT(DISTINCT DATE(completed_at)) as active_days');
        $this->db->from('form_responses');
        $this->db->where('completed_at IS NOT NULL');
        $this->db->where('completed_at >=', date('Y-m-d', strtotime("-{$days} days")));
        
        $result = $this->db->get()->row();
        return $result ? $result->active_days : 0;
    }

    /**
     * Obter estatísticas de período do sistema
     */
    private function get_system_period_stats($days) {
        $filters = [
            'date_from' => date('Y-m-d', strtotime("-{$days} days")),
            'date_to' => date('Y-m-d')
        ];
        
        $total = $this->Response_model->count_by_filters($filters);
        $photos = $this->Response_model->count_photos($filters);
        $locations = $this->Response_model->count_locations($filters);
        $avg_per_day = $days > 0 ? round($total / $days, 1) : 0;
        
        return [
            'total_forms' => (int)$total,
            'photos_captured' => (int)$photos,
            'locations_captured' => (int)$locations,
            'avg_per_day' => (float)$avg_per_day,
            'period_days' => (int)$days
        ];
    }

    /**
     * Formatar atividade recente do sistema
     */
    private function format_system_recent_activity($activities) {
        $formatted_activities = [];
        
        foreach ($activities as $activity) {
            $time_diff = $this->time_elapsed_string($activity->completed_at);
            
            $formatted_activities[] = [
                'id' => (int)$activity->id,
                'action' => 'Formulário aplicado',
                'description' => $activity->questionnaire_title,
                'time' => $time_diff,
                'sync_status' => $activity->sync_status,
                'applicator_name' => $activity->applied_by_name,
                'respondent_name' => $activity->respondent_name
            ];
        }
        
        return $formatted_activities;
    }

    public function questionnaires_analysis() {
        if ($this->input->method() !== 'get') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $authenticated_user = $this->verify_auth();
        if (!$authenticated_user) return;

        if (!$this->Response_model->is_supervisor_or_admin($authenticated_user)) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            return;
        }

        try {
            $filters = [
                'date_from' => $this->input->get('date_from'),
                'date_to' => $this->input->get('date_to'),
                'applied_by' => $this->input->get('applied_by'),
            ];

            $questionnaires_stats = $this->Response_model->get_questionnaires_popularity($filters, 50);
            
            $formatted_stats = [];
            foreach ($questionnaires_stats as $q) {
                $this->db->select('COUNT(*) as total_questions');
                $this->db->where('questionnaire_id', $q->id);
                $questions_result = $this->db->get('questions')->row();
                $total_questions = $questions_result ? $questions_result->total_questions : 0;
                
                $this->db->select('MAX(updated_at) as last_response, description');
                $this->db->from('questionnaires');
                $this->db->where('id', $q->id);
                $this->db->group_by('description');
                $questionnaire_info = $this->db->get()->row();
                
                $formatted_stats[] = [
                    'id' => (int)$q->id,
                    'title' => $q->title,
                    'description' => $questionnaire_info ? $questionnaire_info->description : '',
                    'total_questions' => (int)$total_questions,
                    'total_responses' => (int)$q->total_applications,
                    'response_rate' => 85.0, // Cálculo simplificado
                    'last_response' => date('Y-m-d H:i:s')
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $formatted_stats
            ]);

        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/stats/questionnaire-analysis/{id}
     */
    public function questionnaire_analysis($questionnaire_id) {
        if ($this->input->method() !== 'get') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $authenticated_user = $this->verify_auth();
        if (!$authenticated_user) return;

        if (!$this->Response_model->is_supervisor_or_admin($authenticated_user)) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            return;
        }

        try {
            $filters = ['questionnaire_id' => $questionnaire_id];
            $analysis = $this->Response_model->get_specific_questionnaire_analysis($questionnaire_id, $filters);
            
            echo json_encode([
                'success' => true,
                'data' => $analysis
            ]);

        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/stats/questions-analysis
     */
    public function questions_analysis() {
        if ($this->input->method() !== 'get') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $authenticated_user = $this->verify_auth();
        if (!$authenticated_user) return;

        if (!$this->Response_model->is_supervisor_or_admin($authenticated_user)) {
            $this->output->set_status_header(403);
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            return;
        }

        try {
            $filters = [
                'questionnaire_id' => $this->input->get('questionnaire_id'),
                'date_from' => $this->input->get('date_from'),
                'date_to' => $this->input->get('date_to'),
            ];

            $analysis = $this->Response_model->get_question_analysis($filters);
            
            echo json_encode([
                'success' => true,
                'data' => $analysis
            ]);

        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function applicators_app() {
        if ($this->input->method() !== 'get') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $authenticated_user = $this->verify_auth();
        if (!$authenticated_user) return;

        try {
            $this->db->select('id, full_name, username, role');
            $this->db->where('role', 'aplicador');
            $this->db->where('is_active', true);
            $this->db->order_by('full_name', 'ASC');
            
            $applicators = $this->db->get('users')->result();
            
            $formatted = [];
            foreach ($applicators as $app) {
                $formatted[] = [
                    'id' => (int)$app->id,
                    'full_name' => $app->full_name,
                    'role' => $app->role
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $formatted
            ]);

        } catch (Exception $e) {
            $this->output->set_status_header(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}