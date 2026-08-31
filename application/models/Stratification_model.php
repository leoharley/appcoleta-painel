<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stratification_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    // ==================== CRUD ====================

    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('stratifications', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('stratifications', $data);
    }

    public function delete($id) {
        $this->db->where('id', $id);
        return $this->db->delete('stratifications');
    }

    public function get_by_id($id) {
        $this->db->select('s.*, q.title as questionnaire_title');
        $this->db->from('stratifications s');
        $this->db->join('questionnaires q', 's.questionnaire_id = q.id', 'left');
        $this->db->where('s.id', $id);
        $stratification = $this->db->get()->row();

        if ($stratification) {
            $stratification->categories = $this->get_categories_with_strata($stratification->id);
        }

        return $stratification;
    }

    /**
     * Retorna todas as estratificacoes (ativas ou nao) de um questionario.
     */
    public function get_by_questionnaire($questionnaire_id, $only_active = false) {
        $this->db->select('s.*, q.title as questionnaire_title');
        $this->db->from('stratifications s');
        $this->db->join('questionnaires q', 's.questionnaire_id = q.id', 'left');
        $this->db->where('s.questionnaire_id', $questionnaire_id);
        if ($only_active) {
            $this->db->where('s.is_active', true);
        }
        $this->db->order_by('s.created_at', 'ASC');
        $stratifications = $this->db->get()->result();

        foreach ($stratifications as &$strat) {
            $strat->categories = $this->get_categories_with_strata($strat->id);
        }

        return $stratifications;
    }

    public function get_all() {
        $this->db->select('s.*, q.title as questionnaire_title, u.full_name as created_by_name');
        $this->db->from('stratifications s');
        $this->db->join('questionnaires q', 's.questionnaire_id = q.id', 'left');
        $this->db->join('users u', 's.created_by = u.id', 'left');
        $this->db->order_by('s.created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function get_all_with_progress() {
        $stratifications = $this->get_all();

        foreach ($stratifications as &$strat) {
            $progress = $this->get_progress_by_id($strat->id);
            if ($progress) {
                $strat->overall = $progress['overall'];
                $strat->categories_count = count($progress['categories']);
            } else {
                $strat->overall = (object)['total_target' => 0, 'total_current' => 0, 'percentage' => 0];
                $strat->categories_count = 0;
            }
        }

        return $stratifications;
    }

    // ==================== Categorias e Estratos ====================

    public function get_categories_with_strata($stratification_id) {
        $this->db->select('sc.*, q.question_text');
        $this->db->from('stratification_categories sc');
        $this->db->join('questions q', 'sc.question_id = q.id', 'left');
        $this->db->where('sc.stratification_id', $stratification_id);
        $this->db->order_by('sc.display_order', 'ASC');
        $categories = $this->db->get()->result();

        foreach ($categories as &$cat) {
            $this->db->where('category_id', $cat->id);
            $this->db->order_by('id', 'ASC');
            $cat->strata = $this->db->get('stratification_strata')->result();
        }

        return $categories;
    }

    public function save_categories_and_strata($stratification_id, $categories_data) {
        // Remover categorias e estratos existentes (cascade)
        $this->db->where('stratification_id', $stratification_id);
        $this->db->delete('stratification_categories');

        if (empty($categories_data) || !is_array($categories_data)) {
            return true;
        }

        foreach ($categories_data as $order => $cat) {
            $dimension_type = isset($cat['dimension_type']) ? $cat['dimension_type'] : 'question';
            if (!in_array($dimension_type, array('question', 'applicator', 'weekday'), true)) {
                $dimension_type = 'question';
            }

            $cat_data = array(
                'stratification_id' => $stratification_id,
                'dimension_type' => $dimension_type,
                // question_id e NOT NULL DEFAULT 0 no banco (ver
                // database/patch_stratification_categories_question_id.sql).
                // Dimensoes 'applicator'/'weekday' nao usam pergunta: gravam o
                // sentinel 0 em vez de NULL, que violaria a constraint.
                'question_id' => ($dimension_type === 'question' && !empty($cat['question_id']))
                    ? (int)$cat['question_id']
                    : 0,
                'category_name' => $cat['category_name'],
                'display_order' => $order,
                'created_at' => date('Y-m-d H:i:s')
            );
            $this->db->insert('stratification_categories', $cat_data);
            $category_id = $this->db->insert_id();

            if (!empty($cat['strata']) && is_array($cat['strata'])) {
                foreach ($cat['strata'] as $stratum) {
                    $target = isset($stratum['target_quota']) ? (int)$stratum['target_quota'] : 0;
                    if ($target <= 0) continue;

                    $stratum_data = array(
                        'category_id' => $category_id,
                        'option_value' => $stratum['option_value'],
                        'option_label' => $stratum['option_label'],
                        'target_quota' => $target,
                        'created_at' => date('Y-m-d H:i:s')
                    );
                    $this->db->insert('stratification_strata', $stratum_data);
                }
            }
        }

        return true;
    }

    // ==================== Progresso ====================

    /**
     * Calcula progresso de uma estratificacao especifica.
     *
     * @param int $stratification_id
     * @param int|null $applied_by  Quando informado, conta apenas as respostas
     *                              aplicadas pelo usuario X (visualizacao filtrada).
     */
    public function get_progress_by_id($stratification_id, $applied_by = null) {
        $stratification = $this->db->select('*')
            ->from('stratifications')
            ->where('id', $stratification_id)
            ->get()->row();

        if (!$stratification) return null;

        return $this->compute_progress($stratification, $applied_by);
    }

    /**
     * Retorna lista de estratificacoes ativas de um questionario com progresso calculado.
     * Item 2: um questionario pode ter varias estratificacoes.
     *
     * @param int $questionnaire_id
     * @param int|null $applied_by
     */
    public function get_all_progress_for_questionnaire($questionnaire_id, $applied_by = null) {
        $stratifications = $this->db->select('*')
            ->from('stratifications')
            ->where('questionnaire_id', $questionnaire_id)
            ->where('is_active', true)
            ->order_by('created_at', 'ASC')
            ->get()->result();

        $out = array();
        foreach ($stratifications as $strat) {
            $progress = $this->compute_progress($strat, $applied_by);
            if ($progress) $out[] = $progress;
        }
        return $out;
    }

    /**
     * Compatibilidade: retorna a primeira estratificacao ativa do questionario.
     * Mantido para nao quebrar callers antigos.
     */
    public function get_progress($questionnaire_id, $applied_by = null) {
        $list = $this->get_all_progress_for_questionnaire($questionnaire_id, $applied_by);
        return empty($list) ? null : $list[0];
    }

    private function compute_progress($stratification, $applied_by = null) {
        $categories = $this->get_categories_with_strata($stratification->id);

        $total_target = 0;
        $total_current = 0;

        foreach ($categories as &$category) {
            $category_target = 0;
            $category_current = 0;

            foreach ($category->strata as &$stratum) {
                $stratum->current_count = $this->count_responses_for_stratum(
                    $stratification->questionnaire_id,
                    $category,
                    $stratum,
                    $applied_by
                );
                $stratum->percentage = $stratum->target_quota > 0
                    ? round(($stratum->current_count / $stratum->target_quota) * 100, 1)
                    : 0;
                $stratum->quota_reached = ($stratum->current_count >= $stratum->target_quota);

                $category_target += $stratum->target_quota;
                $category_current += $stratum->current_count;
            }

            $category->total_target = $category_target;
            $category->total_current = $category_current;
            $category->percentage = $category_target > 0
                ? round(($category_current / $category_target) * 100, 1)
                : 0;

            $total_target += $category_target;
            $total_current += $category_current;
        }

        return array(
            'stratification' => $stratification,
            'categories' => $categories,
            'overall' => (object)array(
                'total_target' => $total_target,
                'total_current' => $total_current,
                'percentage' => $total_target > 0
                    ? round(($total_current / $total_target) * 100, 1)
                    : 0
            )
        );
    }

    /**
     * Conta respostas que se encaixam em um estrato especifico.
     * Branches por dimensao da categoria.
     *
     * @param int $questionnaire_id
     * @param object $category   stratification_categories (precisa ter dimension_type, question_id)
     * @param object $stratum    stratification_strata
     * @param int|null $applied_by  Filtro opcional para visualizacao individual.
     */
    private function count_responses_for_stratum($questionnaire_id, $category, $stratum, $applied_by = null) {
        switch ($category->dimension_type) {
            case 'applicator':
                return $this->count_by_applicator($questionnaire_id, $stratum->option_value, $applied_by);
            case 'weekday':
                return $this->count_by_weekday($questionnaire_id, $stratum->option_value, $applied_by);
            case 'question':
            default:
                return $this->count_by_question($questionnaire_id, $category->question_id, $stratum->option_value, $applied_by);
        }
    }

    private function count_by_applicator($questionnaire_id, $option_value, $applied_by = null) {
        // option_value armazena o user_id (como string) do aplicador
        $stratum_user_id = (int)$option_value;

        // Se a visualizacao esta filtrada por outro aplicador, este estrato nao bate.
        if ($applied_by !== null && (int)$applied_by !== $stratum_user_id) {
            return 0;
        }

        $this->db->from('form_responses fr');
        $this->db->where('fr.questionnaire_id', $questionnaire_id);
        $this->db->where('fr.completed_at IS NOT NULL');
        $this->db->where('fr.applied_by', $stratum_user_id);
        return (int)$this->db->count_all_results();
    }

    private function count_by_weekday($questionnaire_id, $option_value, $applied_by = null) {
        // option_value: monday, tuesday, ..., sunday (lowercase, mesma do DAYNAME do MySQL)
        $weekday = strtolower(trim($option_value));

        $this->db->from('form_responses fr');
        $this->db->where('fr.questionnaire_id', $questionnaire_id);
        $this->db->where('fr.completed_at IS NOT NULL');
        $this->db->where('LOWER(DAYNAME(fr.completed_at))', $weekday);
        if ($applied_by !== null) {
            $this->db->where('fr.applied_by', (int)$applied_by);
        }
        return (int)$this->db->count_all_results();
    }

    private function count_by_question($questionnaire_id, $question_id, $option_value, $applied_by = null) {
        $option_lower = strtolower(trim($option_value));

        // Contar respostas em response_text (radio/select como texto)
        $this->db->select('COUNT(DISTINCT fr.id) as cnt');
        $this->db->from('question_responses qr');
        $this->db->join('form_responses fr', 'qr.form_response_id = fr.id');
        $this->db->where('fr.questionnaire_id', $questionnaire_id);
        $this->db->where('fr.completed_at IS NOT NULL');
        $this->db->where('qr.question_id', $question_id);
        $this->db->where('LOWER(TRIM(qr.response_text))', $option_lower);
        if ($applied_by !== null) {
            $this->db->where('fr.applied_by', (int)$applied_by);
        }
        $text_count = (int)$this->db->get()->row()->cnt;

        // Contar respostas em selected_options (JSON)
        $this->db->select('qr.selected_options, fr.id as form_response_id');
        $this->db->from('question_responses qr');
        $this->db->join('form_responses fr', 'qr.form_response_id = fr.id');
        $this->db->where('fr.questionnaire_id', $questionnaire_id);
        $this->db->where('fr.completed_at IS NOT NULL');
        $this->db->where('qr.question_id', $question_id);
        $this->db->where('qr.selected_options IS NOT NULL');
        $this->db->where("qr.selected_options != ''");
        // Evitar dupla contagem com response_text
        $this->db->group_start();
        $this->db->where('qr.response_text IS NULL');
        $this->db->or_where("qr.response_text = ''");
        $this->db->group_end();
        if ($applied_by !== null) {
            $this->db->where('fr.applied_by', (int)$applied_by);
        }
        $rows = $this->db->get()->result();

        $json_count = 0;
        $counted_ids = array();

        foreach ($rows as $row) {
            if (in_array($row->form_response_id, $counted_ids)) continue;

            $selected = json_decode($row->selected_options, true);
            if (is_array($selected)) {
                foreach ($selected as $val) {
                    if (strtolower(trim($val)) === $option_lower) {
                        $json_count++;
                        $counted_ids[] = $row->form_response_id;
                        break;
                    }
                }
            } elseif (is_string($row->selected_options)) {
                if (strtolower(trim($row->selected_options)) === $option_lower) {
                    $json_count++;
                    $counted_ids[] = $row->form_response_id;
                }
            }
        }

        return $text_count + $json_count;
    }

    // ==================== Helpers ====================

    public function questionnaire_has_stratification($questionnaire_id) {
        $this->db->where('questionnaire_id', $questionnaire_id);
        return $this->db->count_all_results('stratifications') > 0;
    }
}
