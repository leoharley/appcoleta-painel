<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Acesso a dados do módulo de Inteligência Artificial.
 *
 * Convenção de retorno (vide seção 13 do plano):
 *   - get_all_settings()         -> array (result_array) — uso programático/API
 *   - get_settings_by_category() -> objeto (result)      — view consome com $s->prop
 *   - get_prompts()              -> objeto (result)
 *   - listagens das telas seguem o padrão da view correspondente
 */
class Ai_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    // ==================== SETTINGS ====================

    /** @return array[] */
    public function get_all_settings() {
        $this->db->where('feature_key !=', '_openai_api_key');
        $this->db->order_by('category', 'ASC')->order_by('feature_name', 'ASC');
        return $this->db->get('ai_settings')->result_array();
    }

    /** @return array<string, object[]>  category => objects */
    public function get_settings_by_category() {
        $this->db->where('feature_key !=', '_openai_api_key');
        $this->db->order_by('category', 'ASC')->order_by('feature_name', 'ASC');
        $rows = $this->db->get('ai_settings')->result();
        $out = array();
        foreach ($rows as $r) {
            $cat = $r->category ?: 'outros';
            if (!isset($out[$cat])) $out[$cat] = array();
            $out[$cat][] = $r;
        }
        return $out;
    }

    /** @return object|null */
    public function get_setting($feature_key) {
        return $this->db->get_where('ai_settings', array('feature_key' => $feature_key))->row();
    }

    public function update_setting($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('ai_settings', $data);
    }

    public function toggle_feature($feature_key, $enabled) {
        $this->db->where('feature_key', $feature_key);
        return $this->db->update('ai_settings', array(
            'is_enabled' => $enabled ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ));
    }

    public function is_feature_enabled($feature_key) {
        $row = $this->get_setting($feature_key);
        return $row && (int)$row->is_enabled === 1;
    }

    /** Atualiza a chave da OpenAI armazenada em ai_settings._openai_api_key. */
    public function set_api_key($api_key) {
        $this->db->where('feature_key', '_openai_api_key');
        return $this->db->update('ai_settings', array(
            'custom_params' => $api_key,
            'is_enabled'    => $api_key ? 1 : 0,
            'updated_at'    => date('Y-m-d H:i:s')
        ));
    }

    // ==================== PROMPTS ====================

    /** @return object[] */
    public function get_prompts() {
        $this->db->order_by('feature_key', 'ASC')->order_by('version', 'DESC');
        return $this->db->get('ai_prompts')->result();
    }

    /** @return object|null */
    public function get_active_prompt($feature_key) {
        $this->db->where('feature_key', $feature_key)->where('is_active', 1);
        $this->db->order_by('version', 'DESC')->limit(1);
        return $this->db->get('ai_prompts')->row();
    }

    public function get_prompt($id) {
        return $this->db->get_where('ai_prompts', array('id' => $id))->row();
    }

    public function create_prompt($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('ai_prompts', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update_prompt($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('ai_prompts', $data);
    }

    // ==================== DIRECTIVES ====================

    /** @return object[] */
    public function get_directives($active_only = false) {
        if ($active_only) $this->db->where('is_active', 1);
        $this->db->order_by('priority', 'DESC')->order_by('created_at', 'DESC');
        return $this->db->get('ai_directives')->result();
    }

    public function get_active_directives_for_feature($feature_key) {
        $this->db->where('is_active', 1);
        $rows = $this->db->order_by('priority', 'DESC')->get('ai_directives')->result();
        $out = array();
        foreach ($rows as $r) {
            $applies = $r->applies_to ? json_decode($r->applies_to, true) : null;
            if (!$applies || !is_array($applies) || in_array($feature_key, $applies) || in_array('*', $applies)) {
                $out[] = $r;
            }
        }
        return $out;
    }

    public function get_directive($id) {
        return $this->db->get_where('ai_directives', array('id' => $id))->row();
    }

    public function create_directive($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('ai_directives', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update_directive($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update('ai_directives', $data);
    }

    public function delete_directive($id) {
        return $this->db->where('id', $id)->delete('ai_directives');
    }

    // ==================== EXECUTION LOGS ====================

    public function log_execution($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('ai_execution_logs', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update_execution_log($id, $data) {
        if (!$id) return false;
        $this->db->where('id', $id);
        return $this->db->update('ai_execution_logs', $data);
    }

    /** @return object[] */
    public function get_execution_logs($filters = array(), $limit = 50, $offset = 0) {
        $this->db->select('l.*, u.full_name AS executed_by_name');
        $this->db->from('ai_execution_logs l');
        $this->db->join('users u', 'l.executed_by = u.id', 'left');
        if (!empty($filters['feature_key'])) $this->db->where('l.feature_key', $filters['feature_key']);
        if (!empty($filters['status']))      $this->db->where('l.status', $filters['status']);
        if (!empty($filters['from']))        $this->db->where('l.created_at >=', $filters['from']);
        if (!empty($filters['to']))          $this->db->where('l.created_at <=', $filters['to']);
        $this->db->order_by('l.created_at', 'DESC')->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function count_execution_logs($filters = array()) {
        if (!empty($filters['feature_key'])) $this->db->where('feature_key', $filters['feature_key']);
        if (!empty($filters['status']))      $this->db->where('status', $filters['status']);
        if (!empty($filters['from']))        $this->db->where('created_at >=', $filters['from']);
        if (!empty($filters['to']))          $this->db->where('created_at <=', $filters['to']);
        return (int)$this->db->count_all_results('ai_execution_logs');
    }

    /** Estatísticas para o dashboard. */
    public function get_execution_stats($days = 30) {
        $from = date('Y-m-d 00:00:00', strtotime("-{$days} days"));
        $this->db->select('
            COUNT(*) AS total,
            SUM(CASE WHEN status="success" THEN 1 ELSE 0 END) AS success_count,
            SUM(CASE WHEN status="error"   THEN 1 ELSE 0 END) AS error_count,
            COALESCE(SUM(tokens_input),0)  AS tokens_input_total,
            COALESCE(SUM(tokens_output),0) AS tokens_output_total,
            COALESCE(SUM(cost_usd),0)      AS cost_total,
            COALESCE(AVG(duration_ms),0)   AS avg_duration_ms
        ', false);
        $this->db->from('ai_execution_logs')->where('created_at >=', $from);
        $totals = $this->db->get()->row();

        $this->db->select('feature_key, COUNT(*) AS total, COALESCE(SUM(cost_usd),0) AS cost')
                 ->from('ai_execution_logs')
                 ->where('created_at >=', $from)
                 ->group_by('feature_key');
        $by_feature = $this->db->get()->result();

        $this->db->select('id, feature_key, error_message, created_at')
                 ->from('ai_execution_logs')
                 ->where('status', 'error')
                 ->where('created_at >=', $from)
                 ->order_by('created_at', 'DESC')
                 ->limit(10);
        $recent_errors = $this->db->get()->result();

        return array(
            'totals'        => $totals,
            'by_feature'    => $by_feature,
            'recent_errors' => $recent_errors,
            'window_days'   => $days
        );
    }

    // ==================== TRANSCRIPTIONS ====================

    /** @return object[] */
    public function get_transcriptions($limit = 100, $offset = 0) {
        $this->db->select('t.*,
                           q.question_text AS q_text,
                           qn.title AS questionnaire_title,
                           u.full_name AS processed_by_name,
                           ua.full_name AS applicator_full_name')
                 ->from('ai_transcriptions t')
                 ->join('questions q', 'q.id = t.question_id', 'left')
                 ->join('questionnaires qn', 'qn.id = t.questionnaire_id', 'left')
                 ->join('users u', 'u.id = t.processed_by', 'left')
                 ->join('users ua', 'ua.id = t.applicator_id', 'left')
                 ->order_by('t.created_at', 'DESC')
                 ->limit($limit, $offset);
        return $this->db->get()->result();
    }

    /** Contagem por status para mostrar resumo no topo da tela. */
    public function get_transcription_stats() {
        $this->db->select('status, COUNT(*) AS n')->from('ai_transcriptions')->group_by('status');
        $out = array('pending' => 0, 'completed' => 0, 'error' => 0, 'total' => 0);
        foreach ($this->db->get()->result() as $r) {
            $out[$r->status] = (int)$r->n;
            $out['total'] += (int)$r->n;
        }
        return $out;
    }

    public function get_transcription($id) {
        return $this->db->get_where('ai_transcriptions', array('id' => (int)$id))->row();
    }

    public function create_transcription($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('ai_transcriptions', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update_transcription($id, $data) {
        $this->db->where('id', (int)$id);
        return $this->db->update('ai_transcriptions', $data);
    }

    // ==================== INCONSISTENCIES ====================

    public function get_inconsistencies($limit = 100, $offset = 0) {
        $this->db->select('i.*, q.title AS questionnaire_title, u.full_name AS resolved_by_name')
                 ->from('ai_inconsistencies i')
                 ->join('questionnaires q', 'q.id = i.questionnaire_id', 'left')
                 ->join('users u', 'u.id = i.resolved_by', 'left')
                 ->order_by('i.created_at', 'DESC')
                 ->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function create_inconsistency($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        if (isset($data['affected_questions']) && is_array($data['affected_questions'])) {
            $data['affected_questions'] = json_encode($data['affected_questions']);
        }
        return $this->db->insert('ai_inconsistencies', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update_inconsistency($id, $data) {
        $this->db->where('id', (int)$id);
        return $this->db->update('ai_inconsistencies', $data);
    }

    public function clear_inconsistencies($questionnaire_id = null) {
        if ($questionnaire_id) $this->db->where('questionnaire_id', (int)$questionnaire_id);
        return $this->db->delete('ai_inconsistencies');
    }

    // ==================== CORRECTIONS ====================

    public function get_corrections($limit = 100, $offset = 0) {
        $this->db->select('c.*, q.question_text, u.full_name AS reviewed_by_name')
                 ->from('ai_corrections c')
                 ->join('questions q', 'q.id = c.question_id', 'left')
                 ->join('users u', 'u.id = c.reviewed_by', 'left')
                 ->order_by('c.created_at', 'DESC')
                 ->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function create_correction($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('ai_corrections', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update_correction($id, $data) {
        $this->db->where('id', (int)$id);
        return $this->db->update('ai_corrections', $data);
    }

    public function clear_corrections($questionnaire_id = null) {
        if ($questionnaire_id) {
            $this->db->where('form_response_id IN (SELECT id FROM form_responses WHERE questionnaire_id = '.(int)$questionnaire_id.')', null, false);
        }
        return $this->db->delete('ai_corrections');
    }

    // ==================== FIELD SUGGESTIONS ====================

    public function get_field_suggestions($limit = 100, $offset = 0) {
        $this->db->select('s.*, q.question_text, u.full_name AS reviewed_by_name')
                 ->from('ai_field_suggestions s')
                 ->join('questions q', 'q.id = s.question_id', 'left')
                 ->join('users u', 'u.id = s.reviewed_by', 'left')
                 ->order_by('s.created_at', 'DESC')
                 ->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function create_field_suggestion($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        if (isset($data['context_data']) && is_array($data['context_data'])) {
            $data['context_data'] = json_encode($data['context_data']);
        }
        return $this->db->insert('ai_field_suggestions', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update_field_suggestion($id, $data) {
        $this->db->where('id', (int)$id);
        return $this->db->update('ai_field_suggestions', $data);
    }

    // ==================== REFORMULATIONS ====================

    public function get_reformulations($limit = 100, $offset = 0) {
        $this->db->select('r.*, q.question_text, q.questionnaire_id, u.full_name AS approved_by_name')
                 ->from('ai_reformulations r')
                 ->join('questions q', 'q.id = r.question_id', 'left')
                 ->join('users u', 'u.id = r.approved_by', 'left')
                 ->order_by('r.created_at', 'DESC')
                 ->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function get_reformulations_by_questionnaire($questionnaire_id) {
        $this->db->select('r.*, q.question_text')
                 ->from('ai_reformulations r')
                 ->join('questions q', 'q.id = r.question_id', 'left')
                 ->where('q.questionnaire_id', (int)$questionnaire_id)
                 ->order_by('r.created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function create_reformulation($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('ai_reformulations', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update_reformulation($id, $data) {
        $this->db->where('id', (int)$id);
        return $this->db->update('ai_reformulations', $data);
    }

    // ==================== ADAPTIVE RULES ====================

    public function get_adaptive_rules($questionnaire_id = null) {
        $this->db->select('a.*, qs.question_text AS source_text, qt.question_text AS target_text, u.full_name AS approved_by_name')
                 ->from('ai_adaptive_rules a')
                 ->join('questions qs', 'qs.id = a.source_question_id', 'left')
                 ->join('questions qt', 'qt.id = a.target_question_id', 'left')
                 ->join('users u', 'u.id = a.approved_by', 'left');
        if ($questionnaire_id) $this->db->where('a.questionnaire_id', (int)$questionnaire_id);
        $this->db->order_by('a.priority', 'DESC')->order_by('a.created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function create_adaptive_rule($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        if (isset($data['condition_logic']) && is_array($data['condition_logic'])) {
            $data['condition_logic'] = json_encode($data['condition_logic']);
        }
        return $this->db->insert('ai_adaptive_rules', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update_adaptive_rule($id, $data) {
        $this->db->where('id', (int)$id);
        return $this->db->update('ai_adaptive_rules', $data);
    }

    public function clear_adaptive_rules($questionnaire_id) {
        return $this->db->where('questionnaire_id', (int)$questionnaire_id)->delete('ai_adaptive_rules');
    }

    // ==================== FOLLOWUP SUGGESTIONS ====================

    public function get_followup_suggestions($limit = 100, $offset = 0) {
        $this->db->select('f.*, q.question_text, u.full_name AS reviewed_by_name')
                 ->from('ai_followup_suggestions f')
                 ->join('questions q', 'q.id = f.question_id', 'left')
                 ->join('users u', 'u.id = f.reviewed_by', 'left')
                 ->order_by('f.created_at', 'DESC')
                 ->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function create_followup_suggestion($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        if (isset($data['suggested_options']) && is_array($data['suggested_options'])) {
            $data['suggested_options'] = json_encode($data['suggested_options']);
        }
        return $this->db->insert('ai_followup_suggestions', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update_followup_suggestion($id, $data) {
        $this->db->where('id', (int)$id);
        return $this->db->update('ai_followup_suggestions', $data);
    }

    public function clear_followup_suggestions($questionnaire_id = null) {
        if ($questionnaire_id) $this->db->where('questionnaire_id', (int)$questionnaire_id);
        return $this->db->delete('ai_followup_suggestions');
    }

    public function get_followup_tips($question_id) {
        return $this->db->where('question_id', (int)$question_id)
                        ->order_by('created_at', 'DESC')
                        ->get('question_followup_tips')->result();
    }

    public function create_followup_tip($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('question_followup_tips', $data) ? $this->db->insert_id() : FALSE;
    }

    // ==================== STATISTICAL ANALYSES ====================

    public function get_statistical_analyses($limit = 50) {
        $this->db->select('a.*, q.title AS questionnaire_title, u.full_name AS generated_by_name')
                 ->from('ai_statistical_analyses a')
                 ->join('questionnaires q', 'q.id = a.questionnaire_id', 'left')
                 ->join('users u', 'u.id = a.generated_by', 'left')
                 ->order_by('a.created_at', 'DESC')
                 ->limit($limit);
        return $this->db->get()->result();
    }

    public function get_statistical_analysis($id) {
        return $this->db->get_where('ai_statistical_analyses', array('id' => (int)$id))->row();
    }

    public function create_statistical_analysis($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        foreach (array('filters_applied','patterns','outliers','trends','insights','chart_suggestions') as $k) {
            if (isset($data[$k]) && is_array($data[$k])) $data[$k] = json_encode($data[$k]);
        }
        return $this->db->insert('ai_statistical_analyses', $data) ? $this->db->insert_id() : FALSE;
    }

    public function delete_statistical_analysis($id) {
        return $this->db->where('id', (int)$id)->delete('ai_statistical_analyses');
    }

    // ==================== REPORTS ====================

    public function get_reports($limit = 50) {
        $this->db->select('r.*, q.title AS questionnaire_title, u.full_name AS generated_by_name')
                 ->from('ai_reports r')
                 ->join('questionnaires q', 'q.id = r.questionnaire_id', 'left')
                 ->join('users u', 'u.id = r.generated_by', 'left')
                 ->order_by('r.created_at', 'DESC')
                 ->limit($limit);
        return $this->db->get()->result();
    }

    public function get_report($id) {
        return $this->db->get_where('ai_reports', array('id' => (int)$id))->row();
    }

    public function create_report($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        foreach (array('filters_applied','sections','charts_data') as $k) {
            if (isset($data[$k]) && is_array($data[$k])) $data[$k] = json_encode($data[$k]);
        }
        return $this->db->insert('ai_reports', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update_report($id, $data) {
        $this->db->where('id', (int)$id);
        return $this->db->update('ai_reports', $data);
    }

    // ==================== AUDIT LOGS ====================

    public function insert_audit_log($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('ai_audit_logs', $data) ? $this->db->insert_id() : FALSE;
    }

    // ==================== CACHE / RATE LIMIT ====================

    public function get_cache($key) {
        $row = $this->db->where('cache_key', $key)
                        ->where('(expires_at IS NULL OR expires_at > NOW())', null, false)
                        ->get('ai_cache')->row();
        return $row ? $row->cache_value : null;
    }

    public function set_cache($key, $value, $ttl_seconds = 3600) {
        $exp = $ttl_seconds > 0 ? date('Y-m-d H:i:s', time() + $ttl_seconds) : null;
        $this->db->delete('ai_cache', array('cache_key' => $key));
        return $this->db->insert('ai_cache', array(
            'cache_key'   => $key,
            'cache_value' => is_string($value) ? $value : json_encode($value),
            'expires_at'  => $exp,
            'created_at'  => date('Y-m-d H:i:s'),
        ));
    }

    public function clear_expired_cache() {
        return $this->db->where('expires_at IS NOT NULL AND expires_at <= NOW()', null, false)->delete('ai_cache');
    }

    public function check_rate_limit($user_id, $endpoint, $max_per_minute = 60) {
        $minute_ago = date('Y-m-d H:i:s', time() - 60);
        $count = (int)$this->db->where('user_id', (int)$user_id)
                               ->where('endpoint', $endpoint)
                               ->where('window_start >=', $minute_ago)
                               ->count_all_results('ai_rate_limits');
        if ($count >= $max_per_minute) return false;
        $this->db->insert('ai_rate_limits', array(
            'user_id'       => (int)$user_id,
            'endpoint'      => $endpoint,
            'request_count' => 1,
            'window_start'  => date('Y-m-d H:i:s'),
            'created_at'    => date('Y-m-d H:i:s'),
        ));
        return true;
    }

    public function cleanup_rate_limits() {
        $hour_ago = date('Y-m-d H:i:s', time() - 3600);
        return $this->db->where('window_start <', $hour_ago)->delete('ai_rate_limits');
    }

    // ==================== DASHBOARD ====================

    public function get_dashboard_stats() {
        $pending_inconsistencies = $this->db->where('resolution_status','pending')->count_all_results('ai_inconsistencies');
        $pending_corrections     = $this->db->where('status','pending')->count_all_results('ai_corrections');
        $pending_suggestions     = $this->db->where('status','pending')->count_all_results('ai_field_suggestions');
        $pending_reformulations  = $this->db->where('status','pending')->count_all_results('ai_reformulations');
        $pending_followups       = $this->db->where('status','pending')->count_all_results('ai_followup_suggestions');
        $pending_adaptive        = $this->db->where('approved_by IS NULL', null, false)->count_all_results('ai_adaptive_rules');
        $transcriptions_total    = $this->db->count_all('ai_transcriptions');

        return array(
            'pending_inconsistencies' => $pending_inconsistencies,
            'pending_corrections'     => $pending_corrections,
            'pending_suggestions'     => $pending_suggestions,
            'pending_reformulations'  => $pending_reformulations,
            'pending_followups'       => $pending_followups,
            'pending_adaptive'        => $pending_adaptive,
            'transcriptions_total'    => $transcriptions_total,
        );
    }
}
