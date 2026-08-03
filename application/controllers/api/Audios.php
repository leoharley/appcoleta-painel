<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Audios extends CI_Controller {

    private $allowed_extensions = array('m4a', 'mp3', 'aac', 'wav', 'ogg');
    private $max_size_bytes = 104857600; // 100 MB

    public function __construct() {
        parent::__construct();
        $this->load->helper('cors');
        cors_setup();
    }

    public function upload() {
        if ($this->input->method() !== 'post') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        if (!$this->verify_auth()) {
            return;
        }

        if (!isset($_FILES['audio']) || !is_array($_FILES['audio'])) {
            log_message('error', 'Audios::upload — $_FILES[audio] ausente. POST keys: ' . implode(',', array_keys($_FILES)));
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => 'No audio file provided']);
            return;
        }

        $file = $_FILES['audio'];

        // Erro do upload do PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $msg = $this->php_upload_error_message($file['error']);
            log_message('error', "Audios::upload — erro PHP no upload: {$msg} (code {$file['error']})");
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => $msg]);
            return;
        }

        // Tamanho
        if ($file['size'] > $this->max_size_bytes) {
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => 'Arquivo excede o limite de 100MB']);
            return;
        }

        // Extensao (whitelist) — validamos pelo nome, NAO pelo MIME, porque
        // os MIMEs reportados por Flutter/Android variam (audio/mp4, audio/aac, audio/x-m4a)
        // e o validador do CodeIgniter Upload library rejeita silenciosamente.
        $original_name = $file['name'];
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowed_extensions, true)) {
            log_message('error', "Audios::upload — extensao nao permitida: {$ext} (nome: {$original_name})");
            $this->output->set_status_header(400);
            echo json_encode([
                'success' => false,
                'message' => "Extensao '.{$ext}' nao permitida. Aceitos: " . implode(', ', $this->allowed_extensions)
            ]);
            return;
        }

        // Sanitizar nome final (evita path traversal e caracteres estranhos)
        $safe_name = preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', basename($original_name));
        if ($safe_name === '' || $safe_name[0] === '.') {
            $safe_name = 'audio_' . time() . '.' . $ext;
        }

        $upload_dir = FCPATH . 'uploads/audios/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                log_message('error', "Audios::upload — falha ao criar diretorio: {$upload_dir}");
                $this->output->set_status_header(500);
                echo json_encode(['success' => false, 'message' => 'Falha ao preparar diretorio de upload']);
                return;
            }
        }

        if (!is_writable($upload_dir)) {
            log_message('error', "Audios::upload — diretorio sem permissao de escrita: {$upload_dir}");
            $this->output->set_status_header(500);
            echo json_encode(['success' => false, 'message' => 'Diretorio de upload sem permissao de escrita']);
            return;
        }

        $dest_path = $upload_dir . $safe_name;

        if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
            log_message('error', "Audios::upload — move_uploaded_file falhou: tmp={$file['tmp_name']} -> {$dest_path}");
            $this->output->set_status_header(500);
            echo json_encode(['success' => false, 'message' => 'Falha ao salvar arquivo no servidor']);
            return;
        }

        log_message('info', "Audios::upload — gravado {$safe_name} ({$file['size']} bytes)");

        echo json_encode([
            'success' => true,
            'filename' => $safe_name,
            'file_size' => $file['size'],
            'file_type' => isset($file['type']) ? $file['type'] : null
        ]);
    }

    private function php_upload_error_message($code) {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:   return 'Arquivo excede upload_max_filesize do php.ini';
            case UPLOAD_ERR_FORM_SIZE:  return 'Arquivo excede MAX_FILE_SIZE do formulario';
            case UPLOAD_ERR_PARTIAL:    return 'Upload parcial (interrompido)';
            case UPLOAD_ERR_NO_FILE:    return 'Nenhum arquivo enviado';
            case UPLOAD_ERR_NO_TMP_DIR: return 'Diretorio temporario do PHP nao configurado';
            case UPLOAD_ERR_CANT_WRITE: return 'Falha ao escrever no disco';
            case UPLOAD_ERR_EXTENSION:  return 'Upload bloqueado por uma extensao do PHP';
            default:                    return 'Erro de upload desconhecido';
        }
    }

    private function verify_auth() {
        $headers = $this->input->request_headers();

        if (!isset($headers['Authorization'])) {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Authorization required']);
            return false;
        }

        return true;
    }
}
