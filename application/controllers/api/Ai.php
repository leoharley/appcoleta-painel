<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * API REST do módulo de IA para o app móvel.
 *
 * IMPORTANTE: o transcribe() instala um handler de erro que respeita o
 * operador `@` / error_reporting() — sem isso, o @unlink() de limpeza
 * vira "Erro interno: unlink ..." mesmo com a transcrição bem-sucedida.
 */
class Ai extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Ai_model');
        $this->load->library('ai_service');
        $this->load->library('ai_prompt_service');

        $this->load->helper('cors');
        cors_setup();
        header('Content-Type: application/json');
    }

    // ---------------------------------------------------------------------
    // STATUS
    // ---------------------------------------------------------------------
    public function status() {
        if ($this->input->method() !== 'get') return $this->method_not_allowed();
        $settings = $this->Ai_model->get_all_settings();
        $features = array();
        foreach ($settings as $s) {
            $features[$s['feature_key']] = (int)$s['is_enabled'] === 1;
        }
        echo json_encode(array(
            'success'        => true,
            'is_configured'  => $this->ai_service->is_configured(),
            'features'       => $features,
        ));
    }

    // ---------------------------------------------------------------------
    // TRANSCRIBE
    // ---------------------------------------------------------------------
    public function transcribe() {
        if ($this->input->method() !== 'post') return $this->method_not_allowed();
        $user_id = $this->verify_auth();
        if (!$user_id) return;

        // Handler que respeita o operador `@` — não derruba o transcribe
        // por causa de warnings benignos (unlink, fileperms, etc.).
        set_error_handler(function($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        $file_path = null;
        try {
            if (empty($_FILES['audio']) && empty($_FILES['file'])) {
                http_response_code(400);
                echo json_encode(array('success' => false, 'message' => 'Campo de arquivo "audio" ou "file" obrigatório.'));
                return;
            }

            $field = !empty($_FILES['audio']) ? 'audio' : 'file';
            $upload_dir = FCPATH . 'uploads/audio/';
            if (!is_dir($upload_dir)) @mkdir($upload_dir, 0775, true);

            $this->load->library('upload', array(
                'upload_path'   => $upload_dir,
                'allowed_types' => 'mp3|wav|m4a|ogg|webm|aac|flac|mpeg|mp4',
                'max_size'      => 25600,
                'encrypt_name'  => true,
            ));

            if (!$this->upload->do_upload($field)) {
                http_response_code(400);
                echo json_encode(array('success' => false, 'message' => $this->upload->display_errors('', '')));
                return;
            }

            $file_path = $this->upload->data('full_path');

            $questionnaire_id = (int)$this->input->post('questionnaire_id') ?: null;
            $question_id      = (int)$this->input->post('question_id') ?: null;
            $form_response_id = (int)$this->input->post('form_response_id') ?: null;
            $app_id           = $this->input->post('app_id');

            $result = $this->ai_service->transcribe_audio($file_path, array(
                'executed_by'   => $user_id,
                'resource_type' => 'transcription',
                'language'      => $this->input->post('language') ?: 'pt',
            ));

            if (!$result['success']) {
                http_response_code(502);
                echo json_encode(array('success' => false, 'message' => $result['message']));
                return;
            }

            $duration   = isset($result['duration']) ? (float)$result['duration'] : 0;
            $now        = date('Y-m-d H:i:s');
            $insert     = array(
                'form_response_id'         => $form_response_id,
                'question_id'              => $question_id,
                'questionnaire_id'         => $questionnaire_id,
                'audio_file_path'          => 'uploads/audio/' . basename($file_path),
                'audio_duration_seconds'   => $duration,
                'transcription_text'       => $result['text'],
                'language'                 => isset($result['language']) ? $result['language'] : 'pt-BR',
                'status'                   => 'completed',
                'model_used'               => 'whisper-1',
                'processed_at'             => $now,
                'processed_by'             => $user_id,
                'app_id'                   => $app_id ?: null,
                'question_text'            => $this->input->post('question_text'),
                'duration_ms'              => (int)$this->input->post('duration_ms') ?: ($duration ? (int)($duration * 1000) : null),
                'recording_duration_secs'  => $duration,
                'applicator_name'          => $this->input->post('applicator_name'),
                'applicator_id'            => $user_id,
                'timestamp_app'            => $this->input->post('timestamp_app'),
                'source'                   => $this->input->post('source') ?: 'upload',
            );
            $tid = $this->Ai_model->create_transcription($insert);

            echo json_encode(array(
                'success' => true,
                'data'    => array(
                    'id'         => (int)$tid,
                    'text'       => $result['text'],
                    'language'   => isset($result['language']) ? $result['language'] : null,
                    'duration_ms'=> $duration ? (int)($duration * 1000) : null,
                    'confidence' => null,
                ),
            ));
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(array('success' => false, 'message' => 'Erro interno: ' . $e->getMessage()));
        } finally {
            restore_error_handler();
            // Limpeza segura — só remove se for arquivo válido.
            if (!empty($file_path) && is_file($file_path)) {
                @unlink($file_path);
            }
        }
    }

    // ---------------------------------------------------------------------
    // FOLLOWUP TIPS — usados pelo app para exibir dicas por pergunta
    // ---------------------------------------------------------------------
    public function followup_tips() {
        if ($this->input->method() !== 'get') return $this->method_not_allowed();
        if (!$this->verify_auth()) return;
        $question_id = (int)$this->input->get('question_id');
        if (!$question_id) {
            echo json_encode(array('success' => true, 'tips' => array()));
            return;
        }
        $tips = $this->Ai_model->get_followup_tips($question_id);
        echo json_encode(array('success' => true, 'tips' => $tips));
    }

    // ---------------------------------------------------------------------
    // AUDIT LOG — eventos do app
    // ---------------------------------------------------------------------
    public function audit_log() {
        if ($this->input->method() !== 'post') return $this->method_not_allowed();
        $user_id = $this->verify_auth();
        if (!$user_id) return;
        $json = json_decode(file_get_contents('php://input'), true);
        if (!is_array($json)) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'message' => 'JSON inválido.'));
            return;
        }
        $id = $this->Ai_model->insert_audit_log(array(
            'event_type'       => $json['event_type'] ?? null,
            'question_id'      => isset($json['question_id']) ? (int)$json['question_id'] : null,
            'questionnaire_id' => isset($json['questionnaire_id']) ? (int)$json['questionnaire_id'] : null,
            'form_id'          => isset($json['form_id']) ? (int)$json['form_id'] : null,
            'user_id'          => $user_id,
            'metadata'         => isset($json['metadata']) ? json_encode($json['metadata']) : null,
            'timestamp'        => isset($json['timestamp']) ? date('Y-m-d H:i:s', strtotime($json['timestamp'])) : date('Y-m-d H:i:s'),
        ));
        echo json_encode(array('success' => (bool)$id, 'id' => (int)$id));
    }

    public function audit_log_batch() {
        if ($this->input->method() !== 'post') return $this->method_not_allowed();
        $user_id = $this->verify_auth();
        if (!$user_id) return;
        $json = json_decode(file_get_contents('php://input'), true);
        if (!is_array($json) || empty($json['events'])) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'message' => 'Esperado {events: [...]}.'));
            return;
        }
        $rows = array();
        foreach ($json['events'] as $ev) {
            $rows[] = array(
                'event_type'       => $ev['event_type'] ?? 'unknown',
                'question_id'      => isset($ev['question_id']) ? (int)$ev['question_id'] : null,
                'questionnaire_id' => isset($ev['questionnaire_id']) ? (int)$ev['questionnaire_id'] : null,
                'form_id'          => isset($ev['form_id']) ? (int)$ev['form_id'] : null,
                'user_id'          => $user_id,
                'metadata'         => isset($ev['metadata']) ? json_encode($ev['metadata']) : null,
                'timestamp'        => isset($ev['timestamp']) ? date('Y-m-d H:i:s', strtotime($ev['timestamp'])) : date('Y-m-d H:i:s'),
                'created_at'       => date('Y-m-d H:i:s'),
            );
        }
        $ok = $rows ? $this->db->insert_batch('ai_audit_logs', $rows) : 0;
        echo json_encode(array('success' => true, 'inserted' => (int)$ok));
    }

    // ---------------------------------------------------------------------
    // CHECK INCONSISTENCIES (app aciona depois de finalizar formulário)
    // ---------------------------------------------------------------------
    public function check_inconsistencies() {
        if ($this->input->method() !== 'post') return $this->method_not_allowed();
        $user_id = $this->verify_auth();
        if (!$user_id) return;
        $json = json_decode(file_get_contents('php://input'), true);
        $fr_id = isset($json['form_response_id']) ? (int)$json['form_response_id'] : 0;
        if (!$fr_id) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'message' => 'form_response_id obrigatório.'));
            return;
        }
        $ctx = $this->ai_prompt_service->prepare_inconsistency_data($fr_id);
        if (!$ctx) {
            http_response_code(404);
            echo json_encode(array('success' => false, 'message' => 'form_response não encontrado.'));
            return;
        }
        $built = $this->ai_prompt_service->build_messages('inconsistency_detection', $ctx);
        if (!empty($built['missing_prompt'])) {
            echo json_encode(array('success' => false, 'message' => 'Prompt inativo.'));
            return;
        }
        $res = $this->ai_service->chat_completion_json('inconsistency_detection', $built['messages'], array(
            'prompt_id'     => $built['prompt_id'],
            'executed_by'   => $user_id,
            'resource_type' => 'form_response',
            'resource_id'   => $fr_id,
        ));
        echo json_encode($res);
    }

    public function suggest_fill() {
        if ($this->input->method() !== 'post') return $this->method_not_allowed();
        $user_id = $this->verify_auth();
        if (!$user_id) return;
        $json = json_decode(file_get_contents('php://input'), true);
        $qid = isset($json['questionnaire_id']) ? (int)$json['questionnaire_id'] : 0;
        if (!$qid) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'message' => 'questionnaire_id obrigatório.'));
            return;
        }
        $ctx = $this->ai_prompt_service->prepare_smart_fill_data(
            $qid,
            isset($json['answers']) ? $json['answers'] : array(),
            isset($json['pending_question_ids']) ? $json['pending_question_ids'] : array()
        );
        $built = $this->ai_prompt_service->build_messages('smart_fill', $ctx);
        if (!empty($built['missing_prompt'])) {
            echo json_encode(array('success' => false, 'message' => 'Prompt inativo.'));
            return;
        }
        $res = $this->ai_service->chat_completion_json('smart_fill', $built['messages'], array(
            'prompt_id'     => $built['prompt_id'],
            'executed_by'   => $user_id,
            'resource_type' => 'questionnaire',
            'resource_id'   => $qid,
        ));
        echo json_encode($res);
    }

    // ---------------------------------------------------------------------
    // Endpoints adicionais (stubs deliberados — feature toggle controla)
    // ---------------------------------------------------------------------
    public function transcriptions_sync()  { $this->stub('transcriptions_sync'); }
    public function standardize()          { $this->stub('data_correction'); }
    public function smart_suggestions()    { $this->stub('smart_fill'); }
    public function reformulate_questions(){ $this->stub('question_reformulation'); }
    public function adaptive_next()        { $this->stub('adaptive_routing'); }
    public function follow_up()            { $this->stub('followup_suggestions'); }
    public function generate_report()      { $this->stub('natural_reports'); }
    public function approved_suggestions() {
        if ($this->input->method() !== 'get') return $this->method_not_allowed();
        if (!$this->verify_auth()) return;
        echo json_encode(array('success' => true, 'data' => array()));
    }

    private function stub($feature_key) {
        if (!$this->verify_auth()) return;
        $enabled = $this->Ai_model->is_feature_enabled($feature_key);
        echo json_encode(array(
            'success' => false,
            'message' => $enabled
                ? "Endpoint '{$feature_key}' ainda não implementado nesta fase."
                : "Feature '{$feature_key}' desativada.",
        ));
    }

    // ---------------------------------------------------------------------
    // Auth (mesmo padrão dos outros controllers de API)
    // ---------------------------------------------------------------------
    private function verify_auth() {
        $headers = $this->input->request_headers();
        if (!isset($headers['Authorization'])) {
            $this->output->set_status_header(401);
            echo json_encode(array('success' => false, 'message' => 'Authorization required'));
            return false;
        }
        $auth_header = $headers['Authorization'];
        if (strpos($auth_header, 'Bearer ') !== 0) {
            $this->output->set_status_header(401);
            echo json_encode(array('success' => false, 'message' => 'Invalid authorization format'));
            return false;
        }
        $token = substr($auth_header, 7);
        $payload = json_decode(base64_decode($token), true);
        if (!$payload || !isset($payload['user_id']) || !isset($payload['expires_at'])) {
            $this->output->set_status_header(401);
            echo json_encode(array('success' => false, 'message' => 'Invalid token'));
            return false;
        }
        if (time() > $payload['expires_at']) {
            $this->output->set_status_header(401);
            echo json_encode(array('success' => false, 'message' => 'Token expired'));
            return false;
        }
        return (int)$payload['user_id'];
    }

    private function method_not_allowed() {
        $this->output->set_status_header(405);
        echo json_encode(array('success' => false, 'message' => 'Method not allowed'));
    }
}
