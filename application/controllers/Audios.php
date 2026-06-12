<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reconciliacao de audios:
 * - Lista entrevistas com audio_path apontando para arquivo inexistente
 * - Lista arquivos fisicos em uploads/audios/ que nao estao vinculados a
 *   nenhum form_response (orfaos)
 * - Permite associar um arquivo orfao a uma entrevista (atualiza audio_path)
 *   ou limpar audio_path de um form (quando o audio se perdeu de vez)
 *
 * Causa raiz: gravacoes regravadas no app geram filenames novos em
 * offline_audios/ que sobrescrevem o audio_path do banco, mas o arquivo
 * fisico antigo continua no tablet e pode acabar enviado via "Reenviar
 * Audios", ficando dessincronizado do registro.
 */
class Audios extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->database();
        $this->check_auth();
    }

    private function check_auth() {
        if (!$this->session->userdata('admin_logged_in')) {
            redirect('auth/login');
        }
    }

    public function reconcile() {
        $upload_dir = FCPATH . 'uploads/audios/';

        $physical_files = array();
        if (is_dir($upload_dir)) {
            foreach (scandir($upload_dir) as $f) {
                if ($f === '.' || $f === '..') continue;
                $full = $upload_dir . $f;
                if (!is_file($full)) continue;
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                if (!in_array($ext, array('m4a','mp3','aac','wav','ogg'), true)) continue;
                $physical_files[$f] = array(
                    'filename' => $f,
                    'size' => filesize($full),
                    'mtime' => filemtime($full),
                    'used_by' => array(), // form_response_ids que apontam para este arquivo
                );
            }
        }

        // Forms com audio_path
        $this->db->select('fr.id, fr.questionnaire_id, fr.audio_path, fr.applied_by, fr.completed_at, fr.created_at, q.title as questionnaire_title, u.full_name as applied_by_name');
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id', 'left');
        $this->db->join('users u', 'fr.applied_by = u.id', 'left');
        $this->db->where('fr.audio_path IS NOT NULL');
        $this->db->where("fr.audio_path != ''");
        $this->db->order_by('fr.completed_at', 'DESC');
        $forms = $this->db->get()->result();

        $broken_forms = array();   // audio_path no banco mas arquivo nao existe
        $healthy_forms = array();  // audio_path bate com arquivo

        foreach ($forms as $form) {
            $name = $form->audio_path;
            if (isset($physical_files[$name])) {
                $physical_files[$name]['used_by'][] = $form->id;
                $healthy_forms[] = $form;
            } else {
                $broken_forms[] = $form;
            }
        }

        // Orfaos = arquivos fisicos sem nenhum form apontando
        $orphan_files = array();
        foreach ($physical_files as $name => $info) {
            if (empty($info['used_by'])) {
                $orphan_files[] = $info;
            }
        }

        $data['title'] = 'Reconciliação de Áudios - AppColeta';
        $data['broken_forms'] = $broken_forms;
        $data['healthy_count'] = count($healthy_forms);
        $data['orphan_files'] = $orphan_files;
        $data['total_physical'] = count($physical_files);

        $this->load->view('admin/header', $data);
        $this->load->view('admin/audios/reconcile', $data);
        $this->load->view('admin/footer');
    }

    /**
     * POST: associa um arquivo orfao a uma entrevista (atualiza audio_path).
     */
    public function assign() {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
            return;
        }

        $form_id = (int)$this->input->post('form_id');
        $filename = trim((string)$this->input->post('filename'));

        if ($form_id <= 0 || $filename === '') {
            $this->session->set_flashdata('error', 'Parâmetros inválidos.');
            redirect('audios/reconcile');
            return;
        }

        // Validacao: arquivo precisa existir em uploads/audios
        $full = FCPATH . 'uploads/audios/' . $filename;
        if (!is_file($full)) {
            $this->session->set_flashdata('error', "Arquivo '{$filename}' não encontrado em uploads/audios/.");
            redirect('audios/reconcile');
            return;
        }

        // Validacao: form precisa existir
        $form = $this->db->get_where('form_responses', array('id' => $form_id))->row();
        if (!$form) {
            $this->session->set_flashdata('error', "Entrevista #{$form_id} não encontrada.");
            redirect('audios/reconcile');
            return;
        }

        $this->db->where('id', $form_id);
        $this->db->update('form_responses', array('audio_path' => $filename));

        $this->session->set_flashdata('success', "Áudio '{$filename}' vinculado à entrevista #{$form_id}.");
        redirect('audios/reconcile');
    }

    /**
     * POST: limpa o audio_path de um form (quando o áudio se perdeu).
     */
    public function clear() {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
            return;
        }

        $form_id = (int)$this->input->post('form_id');
        if ($form_id <= 0) {
            $this->session->set_flashdata('error', 'ID inválido.');
            redirect('audios/reconcile');
            return;
        }

        $this->db->where('id', $form_id);
        $this->db->update('form_responses', array('audio_path' => null));

        $this->session->set_flashdata('success', "Áudio removido da entrevista #{$form_id}.");
        redirect('audios/reconcile');
    }
}
