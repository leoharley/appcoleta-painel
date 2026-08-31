<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stratification extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Stratification_model');
        $this->load->helper('cors');
        cors_setup();
        header('Content-Type: application/json');
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

        // Progresso GERAL (todos os aplicadores) — sempre computa
        $progress_geral = $this->Stratification_model->get_all_progress_for_questionnaire(
            (int)$questionnaire_id,
            null
        );

        // Progresso INDIVIDUAL do pesquisador (se solicitado)
        $progress_mine = null;
        if ($applied_by !== null) {
            $progress_mine = $this->Stratification_model->get_all_progress_for_questionnaire(
                (int)$questionnaire_id,
                $applied_by
            );
        }

        // Indexa o mine por stratification_id + stratum_id para lookup rápido
        $mine_index = array();
        if ($progress_mine !== null) {
            foreach ($progress_mine as $p) {
                $sid = (int)$p['stratification']->id;
                $mine_index[$sid] = array('strata' => array(), 'categories' => array(), 'overall' => $p['overall']);
                foreach ($p['categories'] as $cat) {
                    $mine_index[$sid]['categories'][(int)$cat->id] = $cat;
                    foreach ($cat->strata as $s) {
                        $mine_index[$sid]['strata'][(int)$s->id] = $s;
                    }
                }
            }
        }

        $stratifications_out = array();
        foreach ($progress_geral as $progress) {
            $sid = (int)$progress['stratification']->id;
            $categories_out = array();

            foreach ($progress['categories'] as $cat) {
                $mine_cat = ($progress_mine !== null && isset($mine_index[$sid]['categories'][(int)$cat->id]))
                    ? $mine_index[$sid]['categories'][(int)$cat->id] : null;

                $strata_out = array();
                foreach ($cat->strata as $s) {
                    $mine_stratum = ($progress_mine !== null && isset($mine_index[$sid]['strata'][(int)$s->id]))
                        ? $mine_index[$sid]['strata'][(int)$s->id] : null;

                    // Meta individual = ceil(meta_geral / total_aplicadores_ativos)
                    // Calculada no controller aqui de forma leve: divide igualmente pelos
                    // aplicadores atribuídos ao questionário. O app pode exibir isso como
                    // referência; o servidor não impõe cota rígida por pesquisador.
                    $my_target = (int)$s->target_quota;  // fallback conservador
                    // Se quisermos dividir por total de aplicadores, precisaria de query extra.
                    // Por ora, deixamos o app calcular a cota individual como preferir.

                    $strata_out[] = array(
                        'stratum_id'      => (int)$s->id,
                        'option_value'    => $s->option_value,
                        'option_label'    => $s->option_label,
                        'target_quota'    => (int)$s->target_quota,
                        'current_count'   => (int)$s->current_count,
                        'percentage'      => (float)$s->percentage,
                        'quota_reached'   => (bool)$s->quota_reached,
                        // Contadores individuais do pesquisador (quando applied_by informado)
                        'my_current_count' => $mine_stratum !== null ? (int)$mine_stratum->current_count : null,
                        'my_target_quota'  => $mine_stratum !== null ? $my_target : null,
                        'my_percentage'    => $mine_stratum !== null ? (float)$mine_stratum->percentage : null,
                    );
                }

                $categories_out[] = array(
                    'category_id'     => (int)$cat->id,
                    'category_name'   => $cat->category_name,
                    'dimension_type'  => $cat->dimension_type,
                    // Sempre int: o app faz cast nao-nulo. Dimensoes
                    // 'applicator'/'weekday' usam o sentinel 0.
                    'question_id'     => (int)$cat->question_id,
                    'total_target'    => (int)$cat->total_target,
                    'total_current'   => (int)$cat->total_current,
                    'percentage'      => (float)$cat->percentage,
                    'my_total_current'=> $mine_cat !== null ? (int)$mine_cat->total_current : null,
                    'my_percentage'   => $mine_cat !== null ? (float)$mine_cat->percentage : null,
                    'strata'          => $strata_out
                );
            }

            $mine_overall = ($progress_mine !== null && isset($mine_index[$sid]['overall']))
                ? $mine_index[$sid]['overall'] : null;

            $stratifications_out[] = array(
                'stratification_id' => (int)$progress['stratification']->id,
                'title'             => $progress['stratification']->title,
                'is_active'         => (bool)$progress['stratification']->is_active,
                'overall_progress'  => array(
                    'total_target'  => (int)$progress['overall']->total_target,
                    'total_current' => (int)$progress['overall']->total_current,
                    'percentage'    => (float)$progress['overall']->percentage
                ),
                'my_overall_progress' => $mine_overall !== null ? array(
                    'total_current' => (int)$mine_overall->total_current,
                    'percentage'    => (float)$mine_overall->percentage,
                ) : null,
                'categories' => $categories_out
            );
        }

        echo json_encode([
            'success' => true,
            'data' => array(
                'questionnaire_id'  => (int)$questionnaire_id,
                'applied_by_filter' => $applied_by,
                'stratifications'   => $stratifications_out
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
