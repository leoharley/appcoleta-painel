<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Monta mensagens (system + user) a partir dos prompts cadastrados
 * em ai_prompts, injetando as ai_directives ativas no system, e
 * prepara o contexto de cada feature a partir do banco.
 */
class Ai_prompt_service {

    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Ai_model');
        $this->CI->load->database();
    }

    /**
     * @return array {messages, prompt_id, missing_prompt?}
     */
    public function build_messages($feature_key, $variables = array()) {
        $prompt = $this->CI->Ai_model->get_active_prompt($feature_key);
        if (!$prompt) {
            return array(
                'messages' => array(),
                'prompt_id' => null,
                'missing_prompt' => true,
            );
        }

        $system = (string)$prompt->system_prompt;
        $user   = $this->interpolate((string)$prompt->user_prompt_template, $variables);

        $directives = $this->CI->Ai_model->get_active_directives_for_feature($feature_key);
        if (!empty($directives)) {
            $blocks = array("\n\n--- Diretrizes do administrador ---");
            foreach ($directives as $d) {
                $blocks[] = '• ' . $d->title . ': ' . trim((string)$d->content);
            }
            $system .= implode("\n", $blocks);
        }

        return array(
            'messages' => array(
                array('role' => 'system', 'content' => $system),
                array('role' => 'user',   'content' => $user),
            ),
            'prompt_id' => (int)$prompt->id,
            'missing_prompt' => false,
        );
    }

    private function interpolate($template, $vars) {
        if (!is_array($vars)) return $template;
        foreach ($vars as $k => $v) {
            if (is_array($v) || is_object($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            $template = str_replace('{{' . $k . '}}', (string)$v, $template);
        }
        return $template;
    }

    // ==================== prepare_* (contexto por feature) ====================

    public function prepare_inconsistency_data($form_response_id) {
        $form_response_id = (int)$form_response_id;
        $resp = $this->CI->db->get_where('form_responses', array('id' => $form_response_id))->row();
        if (!$resp) return null;
        $q = $this->CI->db->get_where('questionnaires', array('id' => (int)$resp->questionnaire_id))->row();

        $rows = $this->CI->db
            ->select('qr.id, qr.question_id, qr.response_text, qr.response_number, qr.response_date, qr.selected_options, q.question_text, q.question_type')
            ->from('question_responses qr')
            ->join('questions q', 'q.id = qr.question_id', 'left')
            ->where('qr.form_response_id', $form_response_id)
            ->order_by('q.order_index', 'ASC')
            ->get()->result();

        $lines = array();
        foreach ($rows as $r) {
            $val = $r->response_text;
            if ($val === null || $val === '') $val = $r->response_number;
            if ($val === null || $val === '') $val = $r->response_date;
            if (($val === null || $val === '') && $r->selected_options) $val = $r->selected_options;
            $lines[] = sprintf('[Q#%d %s] %s => %s', $r->question_id, $r->question_type, $r->question_text, $val);
        }

        return array(
            'questionnaire_title' => $q ? $q->title : '',
            'questionnaire_id'    => $resp->questionnaire_id,
            'responses_block'     => implode("\n", $lines),
            'form_response_id'    => $form_response_id,
        );
    }

    public function prepare_correction_data($form_response_id) {
        $data = $this->prepare_inconsistency_data($form_response_id);
        return $data;
    }

    public function prepare_smart_fill_data($questionnaire_id, $existing_answers = array(), $pending_ids = array()) {
        $questionnaire_id = (int)$questionnaire_id;
        $q = $this->CI->db->get_where('questionnaires', array('id' => $questionnaire_id))->row();

        $existing_lines = array();
        foreach ((array)$existing_answers as $qid => $val) {
            $existing_lines[] = "Q#{$qid}: {$val}";
        }

        $pending_lines = array();
        if (!empty($pending_ids)) {
            $this->CI->db->where_in('id', $pending_ids);
            $rows = $this->CI->db->get('questions')->result();
            foreach ($rows as $r) {
                $pending_lines[] = sprintf('[Q#%d %s] %s', $r->id, $r->question_type, $r->question_text);
            }
        }

        return array(
            'questionnaire_title' => $q ? $q->title : '',
            'questionnaire_id'    => $questionnaire_id,
            'existing_block'      => implode("\n", $existing_lines),
            'pending_block'       => implode("\n", $pending_lines),
        );
    }

    public function prepare_reformulation_data($question_id, $objective = '') {
        $q = $this->CI->db->get_where('questions', array('id' => (int)$question_id))->row();
        return array(
            'question_id'   => (int)$question_id,
            'question_text' => $q ? $q->question_text : '',
            'objective'     => $objective ?: 'tornar mais clara e neutra',
        );
    }

    public function prepare_adaptive_data($questionnaire_id) {
        $questionnaire_id = (int)$questionnaire_id;
        $q = $this->CI->db->get_where('questionnaires', array('id' => $questionnaire_id))->row();
        $rows = $this->CI->db
            ->where('questionnaire_id', $questionnaire_id)
            ->order_by('order_index', 'ASC')
            ->get('questions')->result();
        $lines = array();
        foreach ($rows as $r) {
            $lines[] = sprintf('Q#%d (%s) "%s"', $r->id, $r->question_type, $r->question_text);
        }
        return array(
            'questionnaire_id'    => $questionnaire_id,
            'questionnaire_title' => $q ? $q->title : '',
            'questionnaire_block' => implode("\n", $lines),
        );
    }

    public function prepare_followup_data($questionnaire_id, $question_id = null) {
        $questionnaire_id = (int)$questionnaire_id;
        $q = $this->CI->db->get_where('questionnaires', array('id' => $questionnaire_id))->row();

        $question = null;
        if ($question_id) {
            $question = $this->CI->db->get_where('questions', array('id' => (int)$question_id))->row();
        }

        $sample_lines = array();
        if ($question_id) {
            $this->CI->db->select('response_text, response_number, response_date, selected_options')
                         ->from('question_responses')
                         ->where('question_id', (int)$question_id)
                         ->order_by('id', 'DESC')
                         ->limit(20);
            $rows = $this->CI->db->get()->result();
            foreach ($rows as $r) {
                $val = $r->response_text ?: $r->response_number ?: $r->response_date ?: $r->selected_options;
                if ($val !== null && $val !== '') $sample_lines[] = '- ' . $val;
            }
        }

        return array(
            'questionnaire_id'    => $questionnaire_id,
            'questionnaire_title' => $q ? $q->title : '',
            'question_id'         => $question_id,
            'question_text'       => $question ? $question->question_text : '',
            'sample_block'        => implode("\n", $sample_lines),
        );
    }

    public function prepare_statistical_data($questionnaire_id, $filters = array()) {
        $questionnaire_id = (int)$questionnaire_id;
        $q = $this->CI->db->get_where('questionnaires', array('id' => $questionnaire_id))->row();

        $this->CI->db->where('questionnaire_id', $questionnaire_id);
        if (!empty($filters['from'])) $this->CI->db->where('completed_at >=', $filters['from']);
        if (!empty($filters['to']))   $this->CI->db->where('completed_at <=', $filters['to']);
        $total = (int)$this->CI->db->count_all_results('form_responses');

        $aggregate = array(
            'total_responses' => $total,
        );

        // contagem por aplicador (top 5)
        $this->CI->db->select('u.full_name AS name, COUNT(*) AS total')
                     ->from('form_responses fr')
                     ->join('users u', 'u.id = fr.applied_by', 'left')
                     ->where('fr.questionnaire_id', $questionnaire_id);
        if (!empty($filters['from'])) $this->CI->db->where('fr.completed_at >=', $filters['from']);
        if (!empty($filters['to']))   $this->CI->db->where('fr.completed_at <=', $filters['to']);
        $this->CI->db->group_by('fr.applied_by, u.full_name')
                     ->order_by('total', 'DESC')
                     ->limit(5);
        $aggregate['top_applicators'] = $this->CI->db->get()->result();

        return array(
            'questionnaire_id'    => $questionnaire_id,
            'questionnaire_title' => $q ? $q->title : '',
            'aggregate_block'     => json_encode($aggregate, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        );
    }

    public function prepare_report_data($questionnaire_id, $filters = array()) {
        $base = $this->prepare_statistical_data($questionnaire_id, $filters);
        $base['filters_block'] = json_encode($filters, JSON_UNESCAPED_UNICODE);
        $base['data_block']    = $base['aggregate_block'];
        return $base;
    }
}
