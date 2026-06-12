<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stratification extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Stratification_model');
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($this->input->method() === 'options') {
            exit();
        }
    }

    /**
     * GET /api/stratification/{questionnaire_id}?applied_by=USER_ID
     *
     * Item 2: retorna LISTA de estratificacoes ativas do questionario.
     * Item 1 (A1) + D2: se applied_by for informado, o progresso e
     * calculado contando apenas as respostas aplicadas pelo usuario X
     * — cada pesquisador ve sua propria fatia.
     */
    public function index($questionnaire_id = null) {
        if ($this->input->method() !== 'get') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        if (!$questionnaire_id || !is_numeric($questionnaire_id)) {
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => 'questionnaire_id is required']);
            return;
        }

        $applied_by = $this->input->get('applied_by');
        $applied_by = ($applied_by !== null && $applied_by !== '') ? (int)$applied_by : null;

        $progress_list = $this->Stratification_model->get_all_progress_for_questionnaire(
            (int)$questionnaire_id,
            $applied_by
        );

        $stratifications_out = array();
        foreach ($progress_list as $progress) {
            $categories_out = array();
            foreach ($progress['categories'] as $cat) {
                $strata_out = array();
                foreach ($cat->strata as $s) {
                    $strata_out[] = array(
                        'stratum_id' => (int)$s->id,
                        'option_value' => $s->option_value,
                        'option_label' => $s->option_label,
                        'target_quota' => (int)$s->target_quota,
                        'current_count' => (int)$s->current_count,
                        'percentage' => (float)$s->percentage,
                        'quota_reached' => (bool)$s->quota_reached
                    );
                }

                $categories_out[] = array(
                    'category_id' => (int)$cat->id,
                    'category_name' => $cat->category_name,
                    'dimension_type' => $cat->dimension_type,
                    'question_id' => $cat->question_id !== null ? (int)$cat->question_id : null,
                    'total_target' => (int)$cat->total_target,
                    'total_current' => (int)$cat->total_current,
                    'percentage' => (float)$cat->percentage,
                    'strata' => $strata_out
                );
            }

            $stratifications_out[] = array(
                'stratification_id' => (int)$progress['stratification']->id,
                'title' => $progress['stratification']->title,
                'is_active' => (bool)$progress['stratification']->is_active,
                'overall_progress' => array(
                    'total_target' => (int)$progress['overall']->total_target,
                    'total_current' => (int)$progress['overall']->total_current,
                    'percentage' => (float)$progress['overall']->percentage
                ),
                'categories' => $categories_out
            );
        }

        echo json_encode([
            'success' => true,
            'data' => array(
                'questionnaire_id' => (int)$questionnaire_id,
                'applied_by_filter' => $applied_by,
                'stratifications' => $stratifications_out
            )
        ]);
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
