<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ai extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Ai_model');
        $this->load->model('Questionnaire_model');
        $this->load->library('ai_service');
        $this->load->library('ai_prompt_service');
        $this->check_auth();
    }

    // ==================== Dashboard ====================

    public function index() {
        $data['title']           = 'Inteligência Artificial';
        $data['is_configured']   = $this->ai_service->is_configured();
        $data['stats']           = $this->Ai_model->get_dashboard_stats();
        $data['execution_stats'] = $this->Ai_model->get_execution_stats(30);
        $data['settings_by_cat'] = $this->Ai_model->get_settings_by_category();
        $this->render('admin/ai/dashboard', $data);
    }

    // ==================== Configurações ====================

    public function settings() {
        $data['title']           = 'Configurações de IA';
        $data['settings_by_cat'] = $this->Ai_model->get_settings_by_category();
        $data['api_key_row']     = $this->Ai_model->get_setting('_openai_api_key');
        $this->render('admin/ai/settings', $data);
    }

    public function update_setting() {
        $this->force_post();
        $id              = (int)$this->input->post('id');
        $is_enabled      = $this->input->post('is_enabled') ? 1 : 0;
        $model           = $this->input->post('model');
        $temperature     = (float)$this->input->post('temperature');
        $max_tokens      = (int)$this->input->post('max_tokens');
        $timeout_seconds = (int)$this->input->post('timeout_seconds');
        $retry_attempts  = (int)$this->input->post('retry_attempts');

        if ($id <= 0) return $this->json(array('success' => false, 'message' => 'ID inválido.'));

        $ok = $this->Ai_model->update_setting($id, array(
            'is_enabled'      => $is_enabled,
            'model'           => $model,
            'temperature'     => $temperature,
            'max_tokens'      => $max_tokens,
            'timeout_seconds' => $timeout_seconds,
            'retry_attempts'  => $retry_attempts,
        ));
        return $this->json(array('success' => (bool)$ok));
    }

    public function toggle_feature() {
        $this->force_post();
        $feature_key = $this->input->post('feature_key');
        $enabled     = (int)$this->input->post('enabled') === 1;
        if (!$feature_key) return $this->json(array('success' => false, 'message' => 'feature_key obrigatório.'));
        $ok = $this->Ai_model->toggle_feature($feature_key, $enabled);
        return $this->json(array('success' => (bool)$ok));
    }

    public function save_api_key() {
        $this->force_post();
        $key = trim((string)$this->input->post('api_key'));
        $this->Ai_model->set_api_key($key);
        return $this->json(array('success' => true));
    }

    public function test_connection() {
        return $this->json($this->ai_service->test_connection());
    }

    // ==================== Prompts ====================

    public function prompts() {
        $data['title']   = 'Prompts da IA';
        $data['prompts'] = $this->Ai_model->get_prompts();
        $this->render('admin/ai/prompts', $data);
    }

    public function save_prompt() {
        $this->force_post();
        $id                   = (int)$this->input->post('id');
        $feature_key          = $this->input->post('feature_key');
        $prompt_name          = $this->input->post('prompt_name');
        $system_prompt        = $this->input->post('system_prompt');
        $user_prompt_template = $this->input->post('user_prompt_template');
        $is_active            = $this->input->post('is_active') ? 1 : 0;
        $version              = (int)$this->input->post('version');
        if ($version < 1) $version = 1;

        if (!$feature_key || !$prompt_name) {
            return $this->json(array('success' => false, 'message' => 'feature_key e prompt_name são obrigatórios.'));
        }

        $payload = array(
            'feature_key'          => $feature_key,
            'prompt_name'          => $prompt_name,
            'system_prompt'        => $system_prompt,
            'user_prompt_template' => $user_prompt_template,
            'is_active'            => $is_active,
            'version'              => $version,
        );

        if ($id > 0) {
            $ok = $this->Ai_model->update_prompt($id, $payload);
        } else {
            $payload['created_by'] = $this->session->userdata('admin_id') ?? $this->session->userdata('user_id');
            $ok = (bool)$this->Ai_model->create_prompt($payload);
        }
        return $this->json(array('success' => (bool)$ok));
    }

    // ==================== Logs ====================

    public function logs() {
        $filters = array(
            'feature_key' => $this->input->get('feature_key'),
            'status'      => $this->input->get('status'),
            'from'        => $this->input->get('from'),
            'to'          => $this->input->get('to'),
        );
        $page  = max(1, (int)$this->input->get('page'));
        $per   = 50;
        $total = $this->Ai_model->count_execution_logs($filters);

        $data['title']    = 'Logs de execução';
        $data['filters']  = $filters;
        $data['logs']     = $this->Ai_model->get_execution_logs($filters, $per, ($page - 1) * $per);
        $data['settings'] = $this->Ai_model->get_all_settings();
        $data['page']     = $page;
        $data['per_page'] = $per;
        $data['total']    = $total;
        $data['pages']    = max(1, (int)ceil($total / $per));
        $this->render('admin/ai/logs', $data);
    }

    // ==================== Transcrições ====================

    public function transcriptions() {
        $data['title'] = 'Transcrições';
        $data['transcriptions'] = $this->Ai_model->get_transcriptions(500);
        $data['stats']          = $this->Ai_model->get_transcription_stats();
        // Quantos áudios em form_responses ainda não foram importados?
        $data['available_to_import'] = (int)$this->db->query("
            SELECT COUNT(*) AS n FROM form_responses fr
            WHERE fr.audio_path IS NOT NULL AND fr.audio_path != ''
              AND fr.id NOT IN (SELECT COALESCE(form_response_id, 0) FROM ai_transcriptions WHERE form_response_id IS NOT NULL)
        ")->row()->n;
        $this->render('admin/ai/transcriptions', $data);
    }

    public function save_transcription_edit() {
        $this->force_post();
        $id   = (int)$this->input->post('id');
        $text = (string)$this->input->post('edited_text');
        if (!$id) return $this->json(array('success' => false, 'message' => 'ID inválido.'));
        $ok = $this->Ai_model->update_transcription($id, array(
            'edited_text'          => $text,
            'transcription_edited' => $text,
        ));
        return $this->json(array('success' => (bool)$ok));
    }

    public function upload_audio() {
        $this->force_post();
        $user_id = $this->session->userdata('admin_id') ?? $this->session->userdata('user_id');
        if (empty($_FILES['audio'])) return $this->json(array('success' => false, 'message' => 'Arquivo "audio" obrigatório.'));
        $dir = FCPATH . 'uploads/audio/';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $this->load->library('upload', array(
            'upload_path'   => $dir,
            'allowed_types' => 'mp3|wav|m4a|ogg|webm|aac|flac|mpeg|mp4',
            'max_size'      => 25600,
            'encrypt_name'  => true,
        ));
        if (!$this->upload->do_upload('audio')) {
            return $this->json(array('success' => false, 'message' => $this->upload->display_errors('', '')));
        }
        $path = $this->upload->data('full_path');
        $res  = $this->ai_service->transcribe_audio($path, array('executed_by' => $user_id));
        if (!$res['success']) {
            @unlink($path);
            return $this->json($res);
        }
        $id = $this->Ai_model->create_transcription(array(
            'audio_file_path'    => 'uploads/audio/' . basename($path),
            'transcription_text' => $res['text'],
            'language'           => isset($res['language']) ? $res['language'] : 'pt-BR',
            'status'             => 'completed',
            'model_used'         => 'whisper-1',
            'processed_at'       => date('Y-m-d H:i:s'),
            'processed_by'       => $user_id,
            'source'             => 'admin_upload',
        ));
        return $this->json(array('success' => true, 'id' => (int)$id, 'text' => $res['text']));
    }

    public function process_transcription($id) {
        $row = $this->Ai_model->get_transcription($id);
        if (!$row) return $this->json(array('success' => false, 'message' => 'Não encontrado.'));
        $abs = FCPATH . ltrim((string)$row->audio_file_path, '/\\');
        if (!is_file($abs)) return $this->json(array('success' => false, 'message' => 'Arquivo de áudio não disponível: ' . $row->audio_file_path));
        $user_id = $this->session->userdata('admin_id') ?? $this->session->userdata('user_id');
        $res = $this->ai_service->transcribe_audio($abs, array(
            'executed_by'   => $user_id,
            'resource_type' => 'transcription',
            'resource_id'   => (int)$id,
        ));
        if (!$res['success']) {
            $this->Ai_model->update_transcription($id, array(
                'status'        => 'error',
                'error_message' => isset($res['message']) ? $res['message'] : 'Falha desconhecida.',
            ));
            return $this->json($res);
        }
        $this->Ai_model->update_transcription($id, array(
            'transcription_text'     => $res['text'],
            'language'               => isset($res['language']) ? $res['language'] : 'pt-BR',
            'audio_duration_seconds' => isset($res['duration']) ? (float)$res['duration'] : null,
            'status'                 => 'completed',
            'model_used'             => 'whisper-1',
            'processed_at'           => date('Y-m-d H:i:s'),
            'processed_by'           => $user_id,
            'error_message'          => null,
        ));
        return $this->json(array('success' => true, 'text' => $res['text']));
    }

    /**
     * Importa áudios já anexados em form_responses para a fila de transcrições.
     * Cria uma linha em ai_transcriptions (status='pending') por resposta com
     * audio_path preenchido que ainda não tenha entrada na tabela.
     */
    public function import_response_audios() {
        $this->force_post();
        $only_existing = $this->input->post('only_existing') !== '0'; // por padrão, ignora arquivos faltantes

        // Já existem em ai_transcriptions? Coleta os form_response_id já vinculados
        $existing = $this->db->select('form_response_id')
            ->where('form_response_id IS NOT NULL', null, false)
            ->get('ai_transcriptions')->result();
        $already = array();
        foreach ($existing as $e) $already[(int)$e->form_response_id] = true;

        // Busca respostas com audio_path preenchido
        $this->db->select('fr.id, fr.questionnaire_id, fr.audio_path, fr.applied_by, fr.completed_at, u.full_name AS applicator_name');
        $this->db->from('form_responses fr');
        $this->db->join('users u', 'u.id = fr.applied_by', 'left');
        $this->db->where('fr.audio_path IS NOT NULL', null, false);
        $this->db->where("fr.audio_path != ''", null, false);
        $rows = $this->db->order_by('fr.completed_at', 'DESC')->get()->result();

        $imported = 0; $skipped_already = 0; $skipped_missing = 0;
        $upload_dir = FCPATH . 'uploads/audios/';

        foreach ($rows as $fr) {
            if (isset($already[(int)$fr->id])) { $skipped_already++; continue; }

            $audio_name = basename((string)$fr->audio_path);
            $abs = $upload_dir . $audio_name;
            if ($only_existing && !is_file($abs)) { $skipped_missing++; continue; }

            $this->Ai_model->create_transcription(array(
                'form_response_id'  => (int)$fr->id,
                'questionnaire_id'  => (int)$fr->questionnaire_id,
                'audio_file_path'   => 'uploads/audios/' . $audio_name,
                'status'            => is_file($abs) ? 'pending' : 'error',
                'error_message'     => is_file($abs) ? null : 'Arquivo não encontrado em uploads/audios/',
                'applicator_id'     => $fr->applied_by ? (int)$fr->applied_by : null,
                'applicator_name'   => $fr->applicator_name,
                'timestamp_app'     => $fr->completed_at,
                'source'            => 'import',
            ));
            $imported++;
        }

        return $this->json(array(
            'success'          => true,
            'imported'         => $imported,
            'skipped_already'  => $skipped_already,
            'skipped_missing'  => $skipped_missing,
            'total_with_audio' => count($rows),
        ));
    }

    /**
     * Processa todas as transcrições pendentes em lote (chama Whisper para cada uma).
     */
    public function process_pending_transcriptions() {
        $this->force_post();
        $limit = max(1, min(50, (int)$this->input->post('limit') ?: 10));
        $pending = $this->db
            ->where('status', 'pending')
            ->order_by('id', 'ASC')
            ->limit($limit)
            ->get('ai_transcriptions')->result();

        $user_id = $this->current_user_id();
        $ok = 0; $err = 0;
        foreach ($pending as $t) {
            $abs = FCPATH . ltrim((string)$t->audio_file_path, '/\\');
            if (!is_file($abs)) {
                $this->Ai_model->update_transcription($t->id, array(
                    'status' => 'error',
                    'error_message' => 'Arquivo não encontrado: ' . $t->audio_file_path,
                ));
                $err++;
                continue;
            }
            $res = $this->ai_service->transcribe_audio($abs, array(
                'executed_by'   => $user_id,
                'resource_type' => 'transcription',
                'resource_id'   => (int)$t->id,
            ));
            if (!$res['success']) {
                $this->Ai_model->update_transcription($t->id, array(
                    'status' => 'error',
                    'error_message' => isset($res['message']) ? $res['message'] : 'Falha.',
                ));
                $err++;
                continue;
            }
            $this->Ai_model->update_transcription($t->id, array(
                'transcription_text'     => $res['text'],
                'language'               => isset($res['language']) ? $res['language'] : 'pt-BR',
                'audio_duration_seconds' => isset($res['duration']) ? (float)$res['duration'] : null,
                'status'                 => 'completed',
                'model_used'             => 'whisper-1',
                'processed_at'           => date('Y-m-d H:i:s'),
                'processed_by'           => $user_id,
                'error_message'          => null,
            ));
            $ok++;
        }
        return $this->json(array('success' => true, 'processed' => $ok + $err, 'completed' => $ok, 'errors' => $err));
    }

    // ==================== Inconsistências ====================

    public function inconsistencies() {
        $data['title'] = 'Inconsistências';
        $data['items'] = $this->Ai_model->get_inconsistencies(200);
        $this->render('admin/ai/inconsistencies', $data);
    }

    public function analyze_inconsistencies() {
        $this->force_post();
        $fr_id = (int)$this->input->post('form_response_id');
        if (!$fr_id) return $this->json(array('success' => false, 'message' => 'form_response_id obrigatório.'));
        return $this->run_form_response_feature(
            'inconsistency_detection', $fr_id,
            function($ctx) { return $this->ai_prompt_service->build_messages('inconsistency_detection', $ctx); },
            function($data, $log_id, $fr) {
                $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : array();
                $count = 0;
                foreach ($items as $it) {
                    $this->Ai_model->create_inconsistency(array(
                        'form_response_id'    => $fr->id,
                        'questionnaire_id'    => $fr->questionnaire_id,
                        'inconsistency_type'  => isset($it['type']) ? $it['type'] : 'logic',
                        'severity'            => isset($it['severity']) ? $it['severity'] : 'low',
                        'consistency_score'   => isset($data['score']) ? (float)$data['score'] : null,
                        'description'         => isset($it['description']) ? $it['description'] : null,
                        'ai_justification'    => isset($it['justification']) ? $it['justification'] : null,
                        'affected_questions'  => isset($it['affected_questions']) ? $it['affected_questions'] : null,
                        'suggested_action'    => isset($it['suggested_action']) ? $it['suggested_action'] : null,
                        'execution_log_id'    => $log_id,
                    ));
                    $count++;
                }
                return $count;
            }
        );
    }

    public function resolve_inconsistency() {
        $this->force_post();
        $id = (int)$this->input->post('id');
        $status = $this->input->post('status') ?: 'resolved';
        $notes  = $this->input->post('notes');
        if (!$id) return $this->json(array('success' => false, 'message' => 'ID inválido.'));
        $this->Ai_model->update_inconsistency($id, array(
            'resolution_status' => $status,
            'resolution_notes'  => $notes,
            'resolved_by'       => $this->current_user_id(),
            'resolved_at'       => date('Y-m-d H:i:s'),
        ));
        return $this->json(array('success' => true));
    }

    public function clear_inconsistencies() {
        $this->force_post();
        $q = $this->input->post('questionnaire_id');
        return $this->json(array('success' => (bool)$this->Ai_model->clear_inconsistencies($q ?: null)));
    }

    // ==================== Correções ====================

    public function corrections() {
        $data['title'] = 'Correções sugeridas';
        $data['items'] = $this->Ai_model->get_corrections(200);
        $this->render('admin/ai/corrections', $data);
    }

    public function analyze_corrections() {
        $this->force_post();
        $fr_id = (int)$this->input->post('form_response_id');
        if (!$fr_id) return $this->json(array('success' => false, 'message' => 'form_response_id obrigatório.'));
        return $this->run_form_response_feature(
            'data_correction', $fr_id,
            function($ctx) { return $this->ai_prompt_service->build_messages('data_correction', $ctx); },
            function($data, $log_id, $fr) {
                $items = isset($data['corrections']) && is_array($data['corrections']) ? $data['corrections'] : array();
                $count = 0;
                foreach ($items as $it) {
                    $this->Ai_model->create_correction(array(
                        'form_response_id'  => $fr->id,
                        'field_name'        => isset($it['field']) ? $it['field'] : null,
                        'original_value'    => isset($it['original']) ? $it['original'] : null,
                        'suggested_value'   => isset($it['suggested']) ? $it['suggested'] : null,
                        'correction_type'   => isset($it['type']) ? $it['type'] : null,
                        'confidence_score'  => isset($it['confidence']) ? (float)$it['confidence'] : null,
                        'execution_log_id'  => $log_id,
                    ));
                    $count++;
                }
                return $count;
            }
        );
    }

    public function review_correction() {
        $this->force_post();
        $id = (int)$this->input->post('id');
        $status = $this->input->post('status') ?: 'approved';
        $applied = $this->input->post('applied_value');
        if (!$id) return $this->json(array('success' => false, 'message' => 'ID inválido.'));
        $this->Ai_model->update_correction($id, array(
            'status'        => $status,
            'reviewed_by'   => $this->current_user_id(),
            'reviewed_at'   => date('Y-m-d H:i:s'),
            'applied_value' => $applied,
        ));
        return $this->json(array('success' => true));
    }

    public function clear_corrections() {
        $this->force_post();
        $q = $this->input->post('questionnaire_id');
        return $this->json(array('success' => (bool)$this->Ai_model->clear_corrections($q ?: null)));
    }

    // ==================== Smart fill ====================

    public function smart_fill() {
        $data['title'] = 'Preenchimento inteligente';
        $data['items'] = $this->Ai_model->get_field_suggestions(200);
        $this->render('admin/ai/smart_fill', $data);
    }

    public function generate_suggestions() {
        $this->force_post();
        $qid = (int)$this->input->post('questionnaire_id');
        if (!$qid) return $this->json(array('success' => false, 'message' => 'questionnaire_id obrigatório.'));
        $ctx = $this->ai_prompt_service->prepare_smart_fill_data($qid, array(), array());
        $built = $this->ai_prompt_service->build_messages('smart_fill', $ctx);
        if (!empty($built['missing_prompt'])) return $this->json(array('success' => false, 'message' => 'Prompt inativo.'));
        $res = $this->ai_service->chat_completion_json('smart_fill', $built['messages'], array(
            'prompt_id'     => $built['prompt_id'],
            'executed_by'   => $this->current_user_id(),
            'resource_type' => 'questionnaire',
            'resource_id'   => $qid,
        ));
        if (!$res['success']) return $this->json($res);
        $items = isset($res['data']['suggestions']) ? $res['data']['suggestions'] : array();
        $count = 0;
        foreach ($items as $it) {
            $this->Ai_model->create_field_suggestion(array(
                'questionnaire_id' => $qid,
                'question_id'      => isset($it['question_id']) ? (int)$it['question_id'] : null,
                'suggested_value'  => isset($it['value']) ? $it['value'] : null,
                'confidence_score' => isset($it['confidence']) ? (float)$it['confidence'] : null,
                'context_data'     => array('reason' => isset($it['reason']) ? $it['reason'] : null),
                'execution_log_id' => isset($res['log_id']) ? $res['log_id'] : null,
            ));
            $count++;
        }
        return $this->json(array('success' => true, 'created' => $count));
    }

    public function approve_suggestion() {
        $this->force_post();
        $id = (int)$this->input->post('id');
        if (!$id) return $this->json(array('success' => false, 'message' => 'ID inválido.'));
        $this->Ai_model->update_field_suggestion($id, array(
            'status'      => 'approved',
            'reviewed_by' => $this->current_user_id(),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ));
        return $this->json(array('success' => true));
    }

    public function reject_suggestion() {
        $this->force_post();
        $id = (int)$this->input->post('id');
        $this->Ai_model->update_field_suggestion($id, array(
            'status'      => 'rejected',
            'reviewed_by' => $this->current_user_id(),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ));
        return $this->json(array('success' => true));
    }

    // ==================== Reformulações ====================

    public function reformulations() {
        $data['title'] = 'Reformulação de perguntas';
        $data['items'] = $this->Ai_model->get_reformulations(200);
        $this->render('admin/ai/reformulations', $data);
    }

    public function generate_reformulation() {
        $this->force_post();
        $qid = (int)$this->input->post('question_id');
        $obj = (string)$this->input->post('objective');
        if (!$qid) return $this->json(array('success' => false, 'message' => 'question_id obrigatório.'));
        $ctx = $this->ai_prompt_service->prepare_reformulation_data($qid, $obj);
        $built = $this->ai_prompt_service->build_messages('question_reformulation', $ctx);
        if (!empty($built['missing_prompt'])) return $this->json(array('success' => false, 'message' => 'Prompt inativo.'));
        $res = $this->ai_service->chat_completion_json('question_reformulation', $built['messages'], array(
            'prompt_id'     => $built['prompt_id'],
            'executed_by'   => $this->current_user_id(),
            'resource_type' => 'question',
            'resource_id'   => $qid,
        ));
        if (!$res['success']) return $this->json($res);
        $reform = isset($res['data']['reformulated']) ? $res['data']['reformulated'] : '';
        if (!$reform) return $this->json(array('success' => false, 'message' => 'IA não retornou texto reformulado.'));
        $id = $this->Ai_model->create_reformulation(array(
            'question_id'       => $qid,
            'original_text'     => $ctx['question_text'],
            'reformulated_text' => $reform,
            'objective'         => $obj,
            'execution_log_id'  => isset($res['log_id']) ? $res['log_id'] : null,
        ));
        return $this->json(array('success' => true, 'id' => (int)$id, 'reformulated' => $reform));
    }

    public function approve_reformulation() {
        $this->force_post();
        $id = (int)$this->input->post('id');
        $apply = (int)$this->input->post('apply') === 1;
        $row = $this->db->get_where('ai_reformulations', array('id' => $id))->row();
        if (!$row) return $this->json(array('success' => false, 'message' => 'Não encontrado.'));
        $this->Ai_model->update_reformulation($id, array(
            'status'      => 'approved',
            'approved_by' => $this->current_user_id(),
            'approved_at' => date('Y-m-d H:i:s'),
        ));
        if ($apply && $row->question_id && $row->reformulated_text) {
            $this->db->where('id', (int)$row->question_id)
                     ->update('questions', array('question_text' => $row->reformulated_text));
        }
        return $this->json(array('success' => true));
    }

    public function generate_reformulations_batch() {
        $this->force_post();
        $qid = (int)$this->input->post('questionnaire_id');
        $obj = (string)$this->input->post('objective');
        if (!$qid) return $this->json(array('success' => false, 'message' => 'questionnaire_id obrigatório.'));
        $rows = $this->db->where('questionnaire_id', $qid)->get('questions')->result();
        $created = 0; $errors = 0;
        foreach ($rows as $r) {
            $ctx = $this->ai_prompt_service->prepare_reformulation_data($r->id, $obj);
            $built = $this->ai_prompt_service->build_messages('question_reformulation', $ctx);
            if (!empty($built['missing_prompt'])) { $errors++; continue; }
            $res = $this->ai_service->chat_completion_json('question_reformulation', $built['messages'], array(
                'prompt_id'     => $built['prompt_id'],
                'executed_by'   => $this->current_user_id(),
                'resource_type' => 'question',
                'resource_id'   => $r->id,
            ));
            if (!$res['success'] || empty($res['data']['reformulated'])) { $errors++; continue; }
            $this->Ai_model->create_reformulation(array(
                'question_id'       => $r->id,
                'original_text'     => $r->question_text,
                'reformulated_text' => $res['data']['reformulated'],
                'objective'         => $obj,
                'execution_log_id'  => isset($res['log_id']) ? $res['log_id'] : null,
            ));
            $created++;
        }
        return $this->json(array('success' => true, 'created' => $created, 'errors' => $errors));
    }

    // ==================== Adaptativo ====================

    public function adaptive() {
        $data['title'] = 'Questionário adaptativo';
        $data['questionnaires'] = $this->db->order_by('title', 'ASC')->get('questionnaires')->result();
        $qid = (int)$this->input->get('questionnaire_id');
        $data['selected_qid'] = $qid;
        $data['rules'] = $qid ? $this->Ai_model->get_adaptive_rules($qid) : array();
        $this->render('admin/ai/adaptive', $data);
    }

    public function generate_adaptive_rules() {
        $this->force_post();
        $qid = (int)$this->input->post('questionnaire_id');
        if (!$qid) return $this->json(array('success' => false, 'message' => 'questionnaire_id obrigatório.'));
        $ctx = $this->ai_prompt_service->prepare_adaptive_data($qid);
        $built = $this->ai_prompt_service->build_messages('adaptive_routing', $ctx);
        if (!empty($built['missing_prompt'])) return $this->json(array('success' => false, 'message' => 'Prompt inativo.'));
        $res = $this->ai_service->chat_completion_json('adaptive_routing', $built['messages'], array(
            'prompt_id'     => $built['prompt_id'],
            'executed_by'   => $this->current_user_id(),
            'resource_type' => 'questionnaire',
            'resource_id'   => $qid,
        ));
        if (!$res['success']) return $this->json($res);
        $rules = isset($res['data']['rules']) ? $res['data']['rules'] : array();
        $count = 0;
        foreach ($rules as $r) {
            $this->Ai_model->create_adaptive_rule(array(
                'questionnaire_id'    => $qid,
                'source_question_id'  => isset($r['source_question_id']) ? (int)$r['source_question_id'] : null,
                'target_question_id'  => isset($r['target_question_id']) ? (int)$r['target_question_id'] : null,
                'condition_logic'     => isset($r['condition']) ? $r['condition'] : null,
                'priority'            => isset($r['priority']) ? (int)$r['priority'] : 0,
                'is_active'           => 0,
                'execution_log_id'    => isset($res['log_id']) ? $res['log_id'] : null,
            ));
            $count++;
        }
        return $this->json(array('success' => true, 'created' => $count));
    }

    public function approve_adaptive_rule()  { return $this->update_adaptive_status('approved', 1); }
    public function reject_adaptive_rule()   { return $this->update_adaptive_status('rejected', 0); }
    public function restore_adaptive_rule()  { return $this->update_adaptive_status('approved', 1); }

    private function update_adaptive_status($status, $active) {
        $this->force_post();
        $id = (int)$this->input->post('id');
        if (!$id) return $this->json(array('success' => false, 'message' => 'ID inválido.'));
        $this->Ai_model->update_adaptive_rule($id, array(
            'is_active'   => $active,
            'approved_by' => $this->current_user_id(),
            'approved_at' => date('Y-m-d H:i:s'),
        ));
        return $this->json(array('success' => true));
    }

    public function clear_adaptive_rules() {
        $this->force_post();
        $qid = (int)$this->input->post('questionnaire_id');
        return $this->json(array('success' => (bool)$this->Ai_model->clear_adaptive_rules($qid)));
    }

    // ==================== Follow-up ====================

    public function followup() {
        $data['title'] = 'Follow-up';
        $data['items'] = $this->Ai_model->get_followup_suggestions(200);
        $this->render('admin/ai/followup', $data);
    }

    public function generate_followup() {
        $this->force_post();
        $qid     = (int)$this->input->post('questionnaire_id');
        $question_id = (int)$this->input->post('question_id') ?: null;
        if (!$qid) return $this->json(array('success' => false, 'message' => 'questionnaire_id obrigatório.'));
        $ctx = $this->ai_prompt_service->prepare_followup_data($qid, $question_id);
        $built = $this->ai_prompt_service->build_messages('followup_suggestions', $ctx);
        if (!empty($built['missing_prompt'])) return $this->json(array('success' => false, 'message' => 'Prompt inativo.'));
        $res = $this->ai_service->chat_completion_json('followup_suggestions', $built['messages'], array(
            'prompt_id'     => $built['prompt_id'],
            'executed_by'   => $this->current_user_id(),
            'resource_type' => 'questionnaire',
            'resource_id'   => $qid,
        ));
        if (!$res['success']) return $this->json($res);
        $items = isset($res['data']['suggestions']) ? $res['data']['suggestions'] : array();
        $count = 0;
        foreach ($items as $it) {
            $this->Ai_model->create_followup_suggestion(array(
                'questionnaire_id'         => $qid,
                'question_id'              => isset($it['question_id']) ? (int)$it['question_id'] : $question_id,
                'suggested_question_text'  => isset($it['text']) ? $it['text'] : null,
                'question_type'            => isset($it['type']) ? $it['type'] : 'text',
                'suggested_options'        => isset($it['options']) ? $it['options'] : null,
                'rationale'                => isset($it['rationale']) ? $it['rationale'] : null,
                'execution_log_id'         => isset($res['log_id']) ? $res['log_id'] : null,
            ));
            $count++;
        }
        return $this->json(array('success' => true, 'created' => $count));
    }

    public function action_followup() {
        $this->force_post();
        $id = (int)$this->input->post('id');
        $action = $this->input->post('action'); // approve | reject | add_as_tip
        if (!$id) return $this->json(array('success' => false, 'message' => 'ID inválido.'));
        $row = $this->db->get_where('ai_followup_suggestions', array('id' => $id))->row();
        if (!$row) return $this->json(array('success' => false, 'message' => 'Não encontrado.'));
        if ($action === 'reject') {
            $this->Ai_model->update_followup_suggestion($id, array(
                'status' => 'rejected', 'reviewed_by' => $this->current_user_id(), 'reviewed_at' => date('Y-m-d H:i:s'),
            ));
        } elseif ($action === 'add_as_tip') {
            $this->Ai_model->create_followup_tip(array(
                'question_id'      => $row->question_id,
                'tip'              => $row->suggested_question_text,
                'source'           => 'ai',
                'ai_suggestion_id' => $id,
                'created_by'       => $this->current_user_id(),
            ));
            $this->Ai_model->update_followup_suggestion($id, array(
                'status' => 'approved', 'reviewed_by' => $this->current_user_id(), 'reviewed_at' => date('Y-m-d H:i:s'),
            ));
        } else {
            $this->Ai_model->update_followup_suggestion($id, array(
                'status' => 'approved', 'reviewed_by' => $this->current_user_id(), 'reviewed_at' => date('Y-m-d H:i:s'),
            ));
        }
        return $this->json(array('success' => true));
    }

    public function edit_followup() {
        $this->force_post();
        $id = (int)$this->input->post('id');
        $text = (string)$this->input->post('text');
        if (!$id) return $this->json(array('success' => false, 'message' => 'ID inválido.'));
        $this->Ai_model->update_followup_suggestion($id, array('suggested_question_text' => $text));
        return $this->json(array('success' => true));
    }

    public function clear_followups() {
        $this->force_post();
        $qid = $this->input->post('questionnaire_id');
        return $this->json(array('success' => (bool)$this->Ai_model->clear_followup_suggestions($qid ?: null)));
    }

    // ==================== Análise estatística / gráficos / relatórios ====================

    public function analysis() {
        $data['title']  = 'Análise estatística';
        $data['items']  = $this->Ai_model->get_statistical_analyses(50);
        $data['questionnaires'] = $this->db->order_by('title','ASC')->get('questionnaires')->result();
        $this->render('admin/ai/analysis', $data);
    }

    public function view_analysis($id) {
        $data['title'] = 'Análise';
        $data['item']  = $this->Ai_model->get_statistical_analysis($id);
        if (!$data['item']) show_404();
        $this->render('admin/ai/analysis_view', $data);
    }

    public function generate_analysis() {
        $this->force_post();
        $qid = (int)$this->input->post('questionnaire_id');
        if (!$qid) return $this->json(array('success' => false, 'message' => 'questionnaire_id obrigatório.'));
        $filters = array(
            'from' => $this->input->post('from') ?: null,
            'to'   => $this->input->post('to')   ?: null,
        );
        $ctx = $this->ai_prompt_service->prepare_statistical_data($qid, $filters);
        $built = $this->ai_prompt_service->build_messages('statistical_analysis', $ctx);
        if (!empty($built['missing_prompt'])) return $this->json(array('success' => false, 'message' => 'Prompt inativo.'));
        $res = $this->ai_service->chat_completion_json('statistical_analysis', $built['messages'], array(
            'prompt_id'     => $built['prompt_id'],
            'executed_by'   => $this->current_user_id(),
            'resource_type' => 'questionnaire',
            'resource_id'   => $qid,
        ));
        if (!$res['success']) return $this->json($res);
        $d = $res['data'];
        $id = $this->Ai_model->create_statistical_analysis(array(
            'questionnaire_id'  => $qid,
            'analysis_type'     => 'general',
            'filters_applied'   => $filters,
            'summary_text'      => isset($d['summary']) ? $d['summary'] : null,
            'patterns'          => isset($d['patterns']) ? $d['patterns'] : null,
            'outliers'          => isset($d['outliers']) ? $d['outliers'] : null,
            'trends'            => isset($d['trends']) ? $d['trends'] : null,
            'insights'          => isset($d['insights']) ? $d['insights'] : null,
            'chart_suggestions' => isset($d['chart_suggestions']) ? $d['chart_suggestions'] : null,
            'generated_by'      => $this->current_user_id(),
            'execution_log_id'  => isset($res['log_id']) ? $res['log_id'] : null,
        ));
        return $this->json(array('success' => true, 'id' => (int)$id));
    }

    public function delete_analysis() {
        $this->force_post();
        $id = (int)$this->input->post('id');
        return $this->json(array('success' => (bool)$this->Ai_model->delete_statistical_analysis($id)));
    }

    public function charts() {
        $data['title']  = 'Gráficos inteligentes';
        $data['items']  = $this->Ai_model->get_statistical_analyses(20);
        $this->render('admin/ai/charts', $data);
    }

    public function generate_charts() { return $this->generate_analysis(); }

    public function reports() {
        $data['title'] = 'Relatórios em linguagem natural';
        $data['items'] = $this->Ai_model->get_reports(50);
        $data['questionnaires'] = $this->db->order_by('title','ASC')->get('questionnaires')->result();
        $this->render('admin/ai/reports', $data);
    }

    public function view_report($id) {
        $data['title'] = 'Relatório';
        $data['item']  = $this->Ai_model->get_report($id);
        if (!$data['item']) show_404();
        $this->render('admin/ai/report_view', $data);
    }

    public function generate_report() {
        $this->force_post();
        $qid = (int)$this->input->post('questionnaire_id');
        $title = (string)$this->input->post('title') ?: 'Relatório gerado por IA';
        if (!$qid) return $this->json(array('success' => false, 'message' => 'questionnaire_id obrigatório.'));
        $filters = array(
            'from' => $this->input->post('from') ?: null,
            'to'   => $this->input->post('to')   ?: null,
        );
        $ctx = $this->ai_prompt_service->prepare_report_data($qid, $filters);
        $built = $this->ai_prompt_service->build_messages('natural_reports', $ctx);
        if (!empty($built['missing_prompt'])) return $this->json(array('success' => false, 'message' => 'Prompt inativo.'));
        $res = $this->ai_service->chat_completion_json('natural_reports', $built['messages'], array(
            'prompt_id'     => $built['prompt_id'],
            'executed_by'   => $this->current_user_id(),
            'resource_type' => 'questionnaire',
            'resource_id'   => $qid,
        ));
        if (!$res['success']) return $this->json($res);
        $d = $res['data'];
        $id = $this->Ai_model->create_report(array(
            'title'             => isset($d['title']) ? $d['title'] : $title,
            'questionnaire_id'  => $qid,
            'report_type'       => 'narrative',
            'filters_applied'   => $filters,
            'narrative_text'    => isset($d['narrative']) ? $d['narrative'] : null,
            'sections'          => isset($d['sections']) ? $d['sections'] : null,
            'charts_data'       => isset($d['charts'])   ? $d['charts']   : null,
            'generated_by'      => $this->current_user_id(),
            'execution_log_id'  => isset($res['log_id']) ? $res['log_id'] : null,
        ));
        return $this->json(array('success' => true, 'id' => (int)$id));
    }

    public function update_report() {
        $this->force_post();
        $id = (int)$this->input->post('id');
        if (!$id) return $this->json(array('success' => false, 'message' => 'ID inválido.'));
        $this->Ai_model->update_report($id, array(
            'title'          => $this->input->post('title'),
            'narrative_text' => $this->input->post('narrative_text'),
        ));
        return $this->json(array('success' => true));
    }

    // ==================== Diretrizes ====================

    public function directives() {
        $data['title']      = 'Diretrizes do agente';
        $data['directives'] = $this->Ai_model->get_directives();
        $this->render('admin/ai/directives', $data);
    }

    public function save_directive() {
        $this->force_post();
        $id       = (int)$this->input->post('id');
        $applies  = $this->input->post('applies_to');
        $applies_arr = $applies ? array_filter(array_map('trim', explode(',', $applies))) : array();
        $payload = array(
            'category'       => $this->input->post('category'),
            'title'          => $this->input->post('title'),
            'content'        => $this->input->post('content'),
            'directive_type' => $this->input->post('directive_type'),
            'priority'       => (int)$this->input->post('priority'),
            'is_active'      => $this->input->post('is_active') ? 1 : 0,
            'applies_to'     => $applies_arr ? json_encode($applies_arr) : null,
        );
        if ($id) {
            $ok = $this->Ai_model->update_directive($id, $payload);
        } else {
            $payload['created_by'] = $this->current_user_id();
            $ok = (bool)$this->Ai_model->create_directive($payload);
        }
        return $this->json(array('success' => (bool)$ok));
    }

    public function get_directive($id) {
        $row = $this->Ai_model->get_directive($id);
        return $this->json(array('success' => (bool)$row, 'directive' => $row));
    }

    public function toggle_directive() {
        $this->force_post();
        $id = (int)$this->input->post('id');
        $active = $this->input->post('active') ? 1 : 0;
        $this->Ai_model->update_directive($id, array('is_active' => $active));
        return $this->json(array('success' => true));
    }

    public function delete_directive() {
        $this->force_post();
        $id = (int)$this->input->post('id');
        return $this->json(array('success' => (bool)$this->Ai_model->delete_directive($id)));
    }

    // ==================== Security / diagnóstico ====================

    public function security() {
        $data['title']   = 'Segurança & diagnóstico';
        $data['configured'] = $this->ai_service->is_configured();
        $data['execution_stats'] = $this->Ai_model->get_execution_stats(7);
        $this->render('admin/ai/security', $data);
    }

    public function batch_analyze() {
        $this->force_post();
        $qid = (int)$this->input->post('questionnaire_id');
        if (!$qid) return $this->json(array('success' => false, 'message' => 'questionnaire_id obrigatório.'));
        $limit = max(1, min(50, (int)$this->input->post('limit') ?: 10));
        $rows = $this->db->where('questionnaire_id', $qid)
                         ->order_by('completed_at', 'DESC')
                         ->limit($limit)
                         ->get('form_responses')->result();
        $created = 0;
        foreach ($rows as $r) {
            $ctx = $this->ai_prompt_service->prepare_inconsistency_data($r->id);
            if (!$ctx) continue;
            $built = $this->ai_prompt_service->build_messages('inconsistency_detection', $ctx);
            if (!empty($built['missing_prompt'])) continue;
            $res = $this->ai_service->chat_completion_json('inconsistency_detection', $built['messages'], array(
                'prompt_id' => $built['prompt_id'], 'executed_by' => $this->current_user_id(),
                'resource_type' => 'form_response', 'resource_id' => $r->id
            ));
            if (!$res['success']) continue;
            foreach ((array)($res['data']['items'] ?? array()) as $it) {
                $this->Ai_model->create_inconsistency(array(
                    'form_response_id'   => $r->id,
                    'questionnaire_id'   => $qid,
                    'severity'           => $it['severity'] ?? 'low',
                    'consistency_score'  => $res['data']['score'] ?? null,
                    'description'        => $it['description'] ?? null,
                    'ai_justification'   => $it['justification'] ?? null,
                    'affected_questions' => $it['affected_questions'] ?? null,
                    'execution_log_id'   => $res['log_id'] ?? null,
                ));
                $created++;
            }
        }
        return $this->json(array('success' => true, 'created' => $created, 'processed' => count($rows)));
    }

    public function debug_analyze() {
        $this->force_post();
        $messages = array(
            array('role' => 'system', 'content' => 'Você é um eco. Responda apenas com "ok" em JSON: {"echo":"ok"}.'),
            array('role' => 'user', 'content' => (string)$this->input->post('payload') ?: 'ping'),
        );
        $res = $this->ai_service->chat_completion_json('inconsistency_detection', $messages);
        return $this->json($res);
    }

    public function get_question_detail() {
        $qid = (int)$this->input->get('question_id');
        if (!$qid) return $this->json(array('success' => false, 'message' => 'question_id obrigatório.'));
        $q = $this->db->get_where('questions', array('id' => $qid))->row();
        $opts = $this->db->where('question_id', $qid)->get('question_options')->result();
        return $this->json(array('success' => (bool)$q, 'question' => $q, 'options' => $opts));
    }

    public function get_report_data() {
        $id = (int)$this->input->get('id');
        $row = $this->Ai_model->get_report($id);
        return $this->json(array('success' => (bool)$row, 'report' => $row));
    }

    // ==================== Listagens auxiliares (selects em cascata) ====================

    /** Retorna a lista de questionários com contagem de respostas, para popular <select>s. */
    public function list_questionnaires() {
        $rows = $this->db
            ->select('q.id, q.title, p.name AS project_name,
                      (SELECT COUNT(*) FROM form_responses fr WHERE fr.questionnaire_id = q.id) AS responses_count,
                      (SELECT COUNT(*) FROM questions qq WHERE qq.questionnaire_id = q.id) AS questions_count')
            ->from('questionnaires q')
            ->join('projects p', 'p.id = q.project_id', 'left')
            ->order_by('q.title', 'ASC')
            ->get()->result();
        return $this->json(array('success' => true, 'data' => $rows));
    }

    /** Retorna respostas (form_responses) de um questionário, para popular <select>s. */
    public function list_responses($questionnaire_id) {
        $questionnaire_id = (int)$questionnaire_id;
        if (!$questionnaire_id) return $this->json(array('success' => false, 'message' => 'questionnaire_id obrigatório.'));
        $rows = $this->db
            ->select('fr.id, fr.respondent_name, fr.completed_at, u.full_name AS applied_by_name')
            ->from('form_responses fr')
            ->join('users u', 'u.id = fr.applied_by', 'left')
            ->where('fr.questionnaire_id', $questionnaire_id)
            ->order_by('fr.completed_at', 'DESC')
            ->limit(500)
            ->get()->result();
        return $this->json(array('success' => true, 'data' => $rows));
    }

    /** Retorna perguntas de um questionário, para popular <select>s. */
    public function list_questions($questionnaire_id) {
        $questionnaire_id = (int)$questionnaire_id;
        if (!$questionnaire_id) return $this->json(array('success' => false, 'message' => 'questionnaire_id obrigatório.'));
        $rows = $this->db
            ->select('id, question_text, question_type, order_index')
            ->where('questionnaire_id', $questionnaire_id)
            ->order_by('order_index', 'ASC')
            ->order_by('id', 'ASC')
            ->get('questions')->result();
        return $this->json(array('success' => true, 'data' => $rows));
    }

    // ==================== Helpers ====================

    /** Helper para features que partem de um form_response_id. */
    private function run_form_response_feature($feature_key, $form_response_id, $build_fn, $persist_fn) {
        $fr = $this->db->get_where('form_responses', array('id' => (int)$form_response_id))->row();
        if (!$fr) return $this->json(array('success' => false, 'message' => 'form_response não encontrado.'));
        $ctx = $this->ai_prompt_service->prepare_inconsistency_data($form_response_id);
        if (!$ctx) return $this->json(array('success' => false, 'message' => 'Sem respostas para analisar.'));
        $built = $build_fn($ctx);
        if (!empty($built['missing_prompt'])) return $this->json(array('success' => false, 'message' => 'Prompt inativo.'));
        $res = $this->ai_service->chat_completion_json($feature_key, $built['messages'], array(
            'prompt_id'     => $built['prompt_id'],
            'executed_by'   => $this->current_user_id(),
            'resource_type' => 'form_response',
            'resource_id'   => (int)$form_response_id,
        ));
        if (!$res['success']) return $this->json($res);
        $created = $persist_fn(isset($res['data']) ? $res['data'] : array(), isset($res['log_id']) ? $res['log_id'] : null, $fr);
        return $this->json(array('success' => true, 'created' => (int)$created));
    }

    private function current_user_id() {
        return $this->session->userdata('admin_id') ?? $this->session->userdata('user_id');
    }

    // ==================== Helpers ====================

    private function render($view, $data) {
        $this->load->view('admin/header', $data);
        $this->load->view($view, $data);
        $this->load->view('admin/footer');
    }

    private function json($data) {
        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($data));
    }

    private function force_post() {
        if ($this->input->method() !== 'post') {
            $this->output->set_status_header(405);
            $this->json(array('success' => false, 'message' => 'Method not allowed'));
            exit;
        }
    }

    private function check_auth() {
        if (!$this->session->userdata('admin_logged_in')) {
            redirect('auth/login');
        }
        $role = $this->session->userdata('admin_role');
        if ($role && !in_array($role, array('administrador', 'supervisor'))) {
            show_error('Acesso negado', 403);
        }
    }
}
