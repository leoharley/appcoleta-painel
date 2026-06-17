<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cliente da OpenAI (chat completions + Whisper) com retry, log de execução e cálculo de custo.
 *
 * A chave fica em ai_settings.feature_key = '_openai_api_key' (custom_params).
 */
class Ai_service {

    private $CI;
    private $base_url = 'https://api.openai.com/v1';
    private $api_key  = null;

    /** Custo USD por 1K tokens (input, output) — ajustar conforme tabela vigente. */
    private $cost_table = array(
        'gpt-4o'        => array('in' => 0.005,  'out' => 0.015),
        'gpt-4o-mini'   => array('in' => 0.00015,'out' => 0.0006),
        'gpt-4-turbo'   => array('in' => 0.01,   'out' => 0.03),
        'gpt-3.5-turbo' => array('in' => 0.0005, 'out' => 0.0015),
        'whisper-1'     => array('in' => 0.006,  'out' => 0),
    );

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('Ai_model');
        $this->load_api_key();
    }

    private function load_api_key() {
        $env = getenv('OPENAI_API_KEY');
        if (!$env && isset($_ENV['OPENAI_API_KEY'])) $env = $_ENV['OPENAI_API_KEY'];
        if ($env) {
            $this->api_key = $env;
            return;
        }
        $row = $this->CI->Ai_model->get_setting('_openai_api_key');
        if ($row && !empty($row->custom_params)) {
            $this->api_key = trim($row->custom_params);
        }
    }

    public function is_configured() {
        return !empty($this->api_key);
    }

    public function test_connection() {
        if (!$this->is_configured()) {
            return $this->error_response('OpenAI API key não configurada.');
        }
        list($body, $http_code, $duration_ms, $err) = $this->make_request('GET', '/models', null, 15);
        if ($err) return $this->error_response($err);
        if ($http_code !== 200) {
            return $this->error_response('HTTP '.$http_code, $body);
        }
        $data = json_decode($body, true);
        $count = is_array($data) && isset($data['data']) ? count($data['data']) : 0;
        return array(
            'success' => true,
            'message' => "Conexão OK. {$count} modelos disponíveis.",
            'details' => array('duration_ms' => $duration_ms)
        );
    }

    /**
     * Chat completion básico.
     *
     * @param string $feature_key
     * @param array  $messages    [{role,content}, ...]
     * @param array  $options     Override de model/temperature/max_tokens/response_format/resource_*
     * @return array {success, content?, raw?, usage?, log_id?, message?}
     */
    public function chat_completion($feature_key, $messages, $options = array()) {
        if (!$this->is_configured()) {
            return $this->error_response('OpenAI API key não configurada.');
        }

        $setting = $this->CI->Ai_model->get_setting($feature_key);
        if (!$setting) {
            return $this->error_response("Feature '{$feature_key}' não cadastrada em ai_settings.");
        }
        if (!$setting->is_enabled) {
            return $this->error_response("Feature '{$feature_key}' está desativada.");
        }

        $model       = isset($options['model'])           ? $options['model']           : ($setting->model ?: 'gpt-4o-mini');
        $temperature = isset($options['temperature'])     ? (float)$options['temperature'] : (float)$setting->temperature;
        $max_tokens  = isset($options['max_tokens'])      ? (int)$options['max_tokens']  : (int)$setting->max_tokens;
        $timeout     = isset($options['timeout_seconds']) ? (int)$options['timeout_seconds'] : (int)$setting->timeout_seconds;
        $retries     = isset($options['retry_attempts']) ? (int)$options['retry_attempts'] : (int)$setting->retry_attempts;

        $payload = array(
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => $temperature,
            'max_tokens'  => $max_tokens,
        );
        if (!empty($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        $log_id = $this->CI->Ai_model->log_execution(array(
            'feature_key'   => $feature_key,
            'model_used'    => $model,
            'prompt_id'     => isset($options['prompt_id']) ? $options['prompt_id'] : null,
            'input_data'    => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status'        => 'running',
            'executed_by'   => isset($options['executed_by']) ? $options['executed_by'] : null,
            'resource_type' => isset($options['resource_type']) ? $options['resource_type'] : null,
            'resource_id'   => isset($options['resource_id']) ? $options['resource_id'] : null,
        ));

        $attempt = 0;
        do {
            list($body, $http_code, $duration_ms, $err) = $this->make_request(
                'POST', '/chat/completions', $payload, $timeout
            );
            $attempt++;
            $should_retry = ($http_code >= 500 || $http_code === 429 || $err) && $attempt <= $retries;
            if ($should_retry) usleep(500000 * $attempt);
        } while ($should_retry);

        if ($err) {
            $this->CI->Ai_model->update_execution_log($log_id, array(
                'status' => 'error', 'error_message' => $err, 'duration_ms' => $duration_ms
            ));
            return $this->error_response($err, null, $log_id);
        }
        if ($http_code !== 200) {
            $this->CI->Ai_model->update_execution_log($log_id, array(
                'status' => 'error',
                'error_message' => 'HTTP '.$http_code.': '.substr((string)$body, 0, 500),
                'duration_ms' => $duration_ms
            ));
            return $this->error_response('HTTP '.$http_code, $body, $log_id);
        }

        $data = json_decode($body, true);
        $content = isset($data['choices'][0]['message']['content']) ? $data['choices'][0]['message']['content'] : '';
        $usage   = isset($data['usage']) ? $data['usage'] : array();
        $tokens_in  = isset($usage['prompt_tokens'])     ? (int)$usage['prompt_tokens']     : 0;
        $tokens_out = isset($usage['completion_tokens']) ? (int)$usage['completion_tokens'] : 0;
        $cost = $this->calculate_cost($model, $tokens_in, $tokens_out);

        $this->CI->Ai_model->update_execution_log($log_id, array(
            'status'         => 'success',
            'output_data'    => $content,
            'tokens_input'   => $tokens_in,
            'tokens_output'  => $tokens_out,
            'cost_usd'       => $cost,
            'duration_ms'    => $duration_ms
        ));

        return array(
            'success' => true,
            'content' => $content,
            'raw'     => $data,
            'usage'   => array('input' => $tokens_in, 'output' => $tokens_out, 'cost_usd' => $cost),
            'log_id'  => $log_id
        );
    }

    /** Força JSON no response_format e devolve o objeto decodificado. */
    public function chat_completion_json($feature_key, $messages, $options = array()) {
        $options['response_format'] = array('type' => 'json_object');
        $result = $this->chat_completion($feature_key, $messages, $options);
        if (!$result['success']) return $result;
        $decoded = json_decode($result['content'], true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'message' => 'Resposta da IA não é JSON válido: '.json_last_error_msg(),
                'log_id'  => isset($result['log_id']) ? $result['log_id'] : null,
                'content' => $result['content']
            );
        }
        $result['data'] = $decoded;
        return $result;
    }

    /**
     * Transcrição via Whisper. NÃO remove o arquivo — limpeza é do controller.
     *
     * @return array {success, text?, language?, duration?, segments?, log_id?, message?}
     */
    public function transcribe_audio($file_path, $options = array()) {
        if (!$this->is_configured()) {
            return $this->error_response('OpenAI API key não configurada.');
        }
        if (!is_file($file_path)) {
            return $this->error_response('Arquivo de áudio não encontrado: '.$file_path);
        }

        $setting = $this->CI->Ai_model->get_setting('transcription');
        if (!$setting || !$setting->is_enabled) {
            return $this->error_response("Feature 'transcription' desativada.");
        }
        $model   = isset($options['model'])           ? $options['model']           : ($setting->model ?: 'whisper-1');
        $timeout = isset($options['timeout_seconds']) ? (int)$options['timeout_seconds'] : (int)$setting->timeout_seconds;
        $language = isset($options['language']) ? $options['language'] : 'pt';

        $log_id = $this->CI->Ai_model->log_execution(array(
            'feature_key'   => 'transcription',
            'model_used'    => $model,
            'input_data'    => json_encode(array('file' => basename($file_path), 'language' => $language)),
            'status'        => 'running',
            'executed_by'   => isset($options['executed_by']) ? $options['executed_by'] : null,
            'resource_type' => isset($options['resource_type']) ? $options['resource_type'] : null,
            'resource_id'   => isset($options['resource_id']) ? $options['resource_id'] : null,
        ));

        $ch = curl_init($this->base_url.'/audio/transcriptions');
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$this->api_key,
            ),
            CURLOPT_POSTFIELDS => array(
                'file'            => new CURLFile($file_path),
                'model'           => $model,
                'language'        => $language,
                'response_format' => 'verbose_json',
            ),
        ));
        $start = microtime(true);
        $body  = curl_exec($ch);
        $err   = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $duration_ms = (int) round((microtime(true) - $start) * 1000);

        if ($err) {
            $this->CI->Ai_model->update_execution_log($log_id, array(
                'status' => 'error', 'error_message' => $err, 'duration_ms' => $duration_ms
            ));
            return $this->error_response($err, null, $log_id);
        }
        if ($http_code !== 200) {
            $this->CI->Ai_model->update_execution_log($log_id, array(
                'status' => 'error',
                'error_message' => 'HTTP '.$http_code.': '.substr((string)$body, 0, 500),
                'duration_ms' => $duration_ms
            ));
            return $this->error_response('HTTP '.$http_code, $body, $log_id);
        }

        $data = json_decode($body, true);
        $text     = isset($data['text']) ? $data['text'] : '';
        $duration = isset($data['duration']) ? (float)$data['duration'] : 0;
        $lang_out = isset($data['language']) ? $data['language'] : $language;
        $segments = isset($data['segments']) ? $data['segments'] : array();
        $cost = $duration > 0 ? round(($duration / 60) * 0.006, 6) : 0;

        $this->CI->Ai_model->update_execution_log($log_id, array(
            'status'      => 'success',
            'output_data' => json_encode(array('text' => $text, 'duration' => $duration, 'language' => $lang_out)),
            'cost_usd'    => $cost,
            'duration_ms' => $duration_ms
        ));

        return array(
            'success'  => true,
            'text'     => $text,
            'language' => $lang_out,
            'duration' => $duration,
            'segments' => $segments,
            'log_id'   => $log_id
        );
    }

    // ---------------------------------------------------------------------
    // Helpers privados
    // ---------------------------------------------------------------------

    private function make_request($method, $path, $payload, $timeout) {
        $ch = curl_init($this->base_url.$path);
        $headers = array(
            'Authorization: Bearer '.$this->api_key,
            'Content-Type: application/json',
        );
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST   => $method,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => $timeout,
            CURLOPT_HTTPHEADER      => $headers,
        ));
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }
        $start = microtime(true);
        $body  = curl_exec($ch);
        $err   = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $duration_ms = (int) round((microtime(true) - $start) * 1000);
        return array($body, $http_code, $duration_ms, $err);
    }

    private function calculate_cost($model, $tokens_in, $tokens_out) {
        if (!isset($this->cost_table[$model])) return 0;
        $c = $this->cost_table[$model];
        return round(($tokens_in / 1000) * $c['in'] + ($tokens_out / 1000) * $c['out'], 6);
    }

    private function error_response($message, $body = null, $log_id = null) {
        $out = array('success' => false, 'message' => $message);
        if ($body !== null) $out['body'] = is_string($body) ? substr($body, 0, 2000) : $body;
        if ($log_id !== null) $out['log_id'] = $log_id;
        return $out;
    }
}
