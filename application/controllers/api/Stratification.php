<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stratification extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Stratification_model');
        $this->load->model('User_model');
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

        // ---------------------------------------------------------------
        // Regra por perfil (quando o token e enviado):
        //   aplicador  -> ve SOMENTE as proprias metas. O applied_by da
        //                 query string e ignorado, para que ninguem
        //                 consulte a meta de outro trocando a URL.
        //   supervisor -> ve o geral e pode filtrar por aplicador.
        //   admin      -> idem supervisor.
        // Sem token (transicao), mantem o comportamento antigo.
        // ---------------------------------------------------------------
        $current_user = $this->current_user_or_null();
        $is_applicator_view = false;

        if ($current_user !== null) {
            if ($current_user->role === 'aplicador') {
                $applied_by = (int)$current_user->id;   // forca: so o dele
                $is_applicator_view = true;
            }
            // supervisor/administrador: respeita o applied_by recebido
        }

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
            $strat_my_target = 0;
            $strat_my_current = 0;

            foreach ($progress['categories'] as $cat) {
                // Visao de aplicador: numa categoria "Por Aplicador", esconde
                // os estratos dos OUTROS pesquisadores — cada um so enxerga
                // a propria linha.
                if ($is_applicator_view && $cat->dimension_type === 'applicator') {
                    $only_mine = array();
                    foreach ($cat->strata as $s) {
                        if ((int)$s->option_value === (int)$applied_by) $only_mine[] = $s;
                    }
                    if (empty($only_mine)) continue;   // nao tem meta aqui
                    $cat->strata = $only_mine;
                }

                $mine_cat = ($progress_mine !== null && isset($mine_index[$sid]['categories'][(int)$cat->id]))
                    ? $mine_index[$sid]['categories'][(int)$cat->id] : null;

                $strata_out = array();
                $cat_my_target = 0;
                $cat_my_current = 0;
                foreach ($cat->strata as $s) {
                    $mine_stratum = ($progress_mine !== null && isset($mine_index[$sid]['strata'][(int)$s->id]))
                        ? $mine_index[$sid]['strata'][(int)$s->id] : null;

                    // Meta individual do aplicador neste estrato.
                    // Vem da grade explicita (stratification_strata_applicator).
                    // Sem cota individual configurada -> null: o app deve
                    // exibir "sem meta definida", NAO a meta geral.
                    $my_target = null;
                    if ($cat->dimension_type === 'applicator') {
                        // Aqui o estrato JA e o aplicador: a cota dele e a
                        // propria target_quota, nao vem da grade.
                        if ($applied_by !== null && (int)$s->option_value === (int)$applied_by) {
                            $my_target = (int)$s->target_quota;
                        }
                    } elseif ($applied_by !== null && isset($s->applicator_quotas[$applied_by])) {
                        $my_target = (int)$s->applicator_quotas[$applied_by];
                    }

                    $my_current = $mine_stratum !== null ? (int)$mine_stratum->current_count : null;
                    $my_pct = null;
                    if ($my_current !== null && $my_target !== null && $my_target > 0) {
                        $my_pct = round(($my_current / $my_target) * 100, 1);
                    }

                    if ($is_applicator_view) {
                        // Aplicador ve SOMENTE a meta dele: os campos
                        // principais ja vem individuais, para o app nao
                        // precisar de logica condicional nem arriscar
                        // exibir numero do time.
                        //
                        // Sem cota individual configurada (ex.: dimensao
                        // 'weekday', ou pergunta sem grade preenchida), cai
                        // na meta geral — melhor que exibir 0 e sumir da
                        // tela. quota_source diz ao app qual e o caso.
                        $has_own = ($my_target !== null);
                        $t = $has_own ? $my_target : (int)$s->target_quota;
                        $c = $my_current !== null ? $my_current : 0;
                        $strata_out[] = array(
                            'stratum_id'      => (int)$s->id,
                            'option_value'    => $s->option_value,
                            'option_label'    => $s->option_label,
                            'target_quota'    => $t,
                            'current_count'   => $c,
                            'percentage'      => $t > 0 ? (float)round(($c / $t) * 100, 1) : 0.0,
                            'quota_reached'   => $t > 0 && $c >= $t,
                            'quota_source'    => $has_own ? 'individual' : 'geral',
                            'my_current_count' => $c,
                            'my_target_quota'  => $my_target,
                            'my_percentage'    => $my_pct !== null ? (float)$my_pct : null,
                        );
                        $cat_my_target += $t;
                        $cat_my_current += $c;
                        continue;
                    }

                    $strata_out[] = array(
                        'stratum_id'      => (int)$s->id,
                        'option_value'    => $s->option_value,
                        'option_label'    => $s->option_label,
                        'target_quota'    => (int)$s->target_quota,
                        'current_count'   => (int)$s->current_count,
                        'percentage'      => (float)$s->percentage,
                        'quota_reached'   => (bool)$s->quota_reached,
                        // Contadores individuais do pesquisador (quando applied_by informado)
                        'my_current_count' => $my_current,
                        'my_target_quota'  => $my_target,
                        'my_percentage'    => $my_pct !== null ? (float)$my_pct : null,
                    );
                }

                // Na visao de aplicador os totais sao os DELE.
                $c_target = $is_applicator_view ? $cat_my_target : (int)$cat->total_target;
                $c_current = $is_applicator_view ? $cat_my_current : (int)$cat->total_current;
                $c_pct = $is_applicator_view
                    ? ($c_target > 0 ? (float)round(($c_current / $c_target) * 100, 1) : 0.0)
                    : (float)$cat->percentage;

                $categories_out[] = array(
                    'category_id'     => (int)$cat->id,
                    'category_name'   => $cat->category_name,
                    'dimension_type'  => $cat->dimension_type,
                    // Sempre int: o app faz cast nao-nulo. Dimensoes
                    // 'applicator'/'weekday' usam o sentinel 0.
                    'question_id'     => (int)$cat->question_id,
                    'total_target'    => $c_target,
                    'total_current'   => $c_current,
                    'percentage'      => $c_pct,
                    'my_total_current'=> $is_applicator_view
                        ? $cat_my_current
                        : ($mine_cat !== null ? (int)$mine_cat->total_current : null),
                    'my_percentage'   => $is_applicator_view
                        ? $c_pct
                        : ($mine_cat !== null ? (float)$mine_cat->percentage : null),
                    'strata'          => $strata_out
                );

                if ($is_applicator_view) {
                    $strat_my_target += $c_target;
                    $strat_my_current += $c_current;
                }
            }

            $mine_overall = ($progress_mine !== null && isset($mine_index[$sid]['overall']))
                ? $mine_index[$sid]['overall'] : null;

            // Na visao de aplicador o "geral" e o total DELE.
            $o_target = $is_applicator_view ? $strat_my_target : (int)$progress['overall']->total_target;
            $o_current = $is_applicator_view ? $strat_my_current : (int)$progress['overall']->total_current;
            $o_pct = $is_applicator_view
                ? ($o_target > 0 ? (float)round(($o_current / $o_target) * 100, 1) : 0.0)
                : (float)$progress['overall']->percentage;

            $stratifications_out[] = array(
                'stratification_id' => (int)$progress['stratification']->id,
                'title'             => $progress['stratification']->title,
                'is_active'         => (bool)$progress['stratification']->is_active,
                'overall_progress'  => array(
                    'total_target'  => $o_target,
                    'total_current' => $o_current,
                    'percentage'    => $o_pct
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
                // 'individual' = numeros do proprio aplicador logado.
                // 'geral'      = numeros consolidados da pesquisa.
                'view_mode'         => $is_applicator_view ? 'individual' : 'geral',
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

    /**
     * Identifica o usuario pelo token SEM emitir 401 quando ausente.
     *
     * Fase de transicao: o app ainda nao envia Authorization nesta rota.
     * Enquanto nao enviar, retorna null e o endpoint mantem o
     * comportamento antigo (visao geral). Quando o app passar a enviar,
     * a regra por perfil entra em vigor automaticamente.
     *
     * TODO: apos o app enviar o token, trocar por verify_auth() para
     * tornar a autenticacao obrigatoria.
     *
     * @return object|null Usuario logado, ou null se nao autenticado.
     */
    private function current_user_or_null() {
        $headers = $this->input->request_headers();

        $auth_header = '';
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $auth_header = $headers['authorization'];
        }

        if (empty($auth_header) || strpos($auth_header, 'Bearer ') !== 0) {
            return null;
        }

        $user_id = $this->verify_token(substr($auth_header, 7));
        if (!$user_id) {
            return null;
        }

        $user = $this->User_model->get_by_id($user_id);
        return ($user && $user->is_active) ? $user : null;
    }
}
