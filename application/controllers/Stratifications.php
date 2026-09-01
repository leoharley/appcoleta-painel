<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stratifications extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->library('form_validation');
        $this->load->model('Stratification_model');
        $this->load->model('Questionnaire_model');
        $this->load->model('Question_model');
        $this->load->model('User_model');
        $this->check_auth();
    }

    private function check_auth() {
        if (!$this->session->userdata('admin_logged_in')) {
            redirect('auth/login');
        }
    }

    /**
     * Lista de estratificacoes
     */
    public function index() {
        $data['title'] = 'Estratificação - AppColeta';
        $data['stratifications'] = $this->Stratification_model->get_all_with_progress();

        $this->load->view('admin/header', $data);
        $this->load->view('admin/stratifications/index', $data);
        $this->load->view('admin/footer');
    }

    /**
     * Criar nova estratificacao
     * Item 2: um questionario pode ter varias estratificacoes (constraint UNIQUE
     * removida no schema). Nao bloqueamos mais por "ja existe".
     */
    public function create() {
        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('title', 'Título', 'required|max_length[200]');
            $this->form_validation->set_rules('questionnaire_id', 'Questionário', 'required|integer');

            if ($this->form_validation->run() === TRUE) {
                $questionnaire_id = $this->input->post('questionnaire_id');

                $this->db->trans_start();

                $strat_id = $this->Stratification_model->create(array(
                    'questionnaire_id' => $questionnaire_id,
                    'title' => $this->input->post('title'),
                    'description' => $this->input->post('description'),
                    'is_active' => TRUE,
                    'created_by' => $this->session->userdata('user_id')
                ));

                $categories = $this->input->post('categories');
                if ($strat_id && !empty($categories)) {
                    $this->Stratification_model->save_categories_and_strata($strat_id, $categories);
                }

                $this->db->trans_complete();

                if ($this->db->trans_status() === FALSE) {
                    $this->session->set_flashdata('error', 'Erro ao salvar a estratificação.');
                    redirect('stratifications/create');
                } else {
                    $this->session->set_flashdata('success', 'Estratificação criada com sucesso!');
                    redirect('stratifications/view/' . $strat_id);
                }
                return;
            }
        }

        $data['title'] = 'Nova Estratificação - AppColeta';
        $data['questionnaires'] = $this->db->select('id, title')->order_by('title', 'ASC')->get('questionnaires')->result();
        $data['stratification'] = null;
        $data['mode'] = 'create';

        $this->load->view('admin/header', $data);
        $this->load->view('admin/stratifications/create', $data);
        $this->load->view('admin/footer');
    }

    /**
     * Editar estratificacao
     */
    public function edit($id) {
        $stratification = $this->Stratification_model->get_by_id($id);
        if (!$stratification) {
            show_404();
            return;
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('title', 'Título', 'required|max_length[200]');

            if ($this->form_validation->run() === TRUE) {
                $this->db->trans_start();

                $this->Stratification_model->update($id, array(
                    'title' => $this->input->post('title'),
                    'description' => $this->input->post('description'),
                    'is_active' => $this->input->post('is_active') ? TRUE : FALSE
                ));

                $categories = $this->input->post('categories');
                $this->Stratification_model->save_categories_and_strata($id, $categories ?: array());

                $this->db->trans_complete();

                if ($this->db->trans_status() === FALSE) {
                    $this->session->set_flashdata('error', 'Erro ao atualizar a estratificação.');
                } else {
                    $this->session->set_flashdata('success', 'Estratificação atualizada com sucesso!');
                }
                redirect('stratifications/view/' . $id);
                return;
            }
        }

        $data['title'] = 'Editar Estratificação - AppColeta';
        $data['questionnaires'] = $this->db->select('id, title')->order_by('title', 'ASC')->get('questionnaires')->result();
        $data['stratification'] = $stratification;
        $data['mode'] = 'edit';

        $this->load->view('admin/header', $data);
        $this->load->view('admin/stratifications/create', $data);
        $this->load->view('admin/footer');
    }

    /**
     * Dashboard de acompanhamento.
     * Aceita query string ?applied_by=USER_ID para filtrar progresso por aplicador (item 1, A1).
     */
    public function view($id) {
        $stratification = $this->Stratification_model->get_by_id($id);
        if (!$stratification) {
            show_404();
            return;
        }

        $applied_by = $this->input->get('applied_by');
        $applied_by = ($applied_by !== null && $applied_by !== '') ? (int)$applied_by : null;

        $progress = $this->Stratification_model->get_progress_by_id($id, $applied_by);

        $data['title'] = 'Estratificação: ' . $stratification->title . ' - AppColeta';
        $data['stratification'] = $stratification;
        $data['progress'] = $progress;
        $data['applied_by_filter'] = $applied_by;
        $data['applicators'] = $this->User_model->get_assignable_users();

        $this->load->view('admin/header', $data);
        $this->load->view('admin/stratifications/view', $data);
        $this->load->view('admin/footer');
    }

    /**
     * Clonar estratificacao.
     * GET  -> formulario (titulo + questionario de destino)
     * POST -> executa a copia
     */
    public function duplicate($id) {
        $stratification = $this->Stratification_model->get_by_id($id);
        if (!$stratification) {
            show_404();
            return;
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('title', 'Título', 'required|max_length[200]');
            $this->form_validation->set_rules('questionnaire_id', 'Questionário', 'required|integer');

            if ($this->form_validation->run() === TRUE) {
                $this->db->trans_start();

                $result = $this->Stratification_model->duplicate(
                    $id,
                    $this->input->post('title'),
                    $this->input->post('questionnaire_id'),
                    $this->session->userdata('user_id')
                );

                $this->db->trans_complete();

                if ($this->db->trans_status() === FALSE || empty($result['id'])) {
                    $this->session->set_flashdata('error', 'Erro ao clonar a estratificação.');
                    redirect('stratifications');
                    return;
                }

                if (!empty($result['skipped'])) {
                    $this->session->set_flashdata('success',
                        'Estratificação clonada. Categorias omitidas porque a pergunta '
                        . 'não existe no questionário de destino: '
                        . implode('; ', $result['skipped']) . '.');
                } else {
                    $this->session->set_flashdata('success', 'Estratificação clonada com sucesso!');
                }

                redirect('stratifications/edit/' . $result['id']);
                return;
            }
        }

        $data['title'] = 'Clonar Estratificação - AppColeta';
        $data['stratification'] = $stratification;
        $data['questionnaires'] = $this->db->select('id, title')->order_by('title', 'ASC')->get('questionnaires')->result();

        $this->load->view('admin/header', $data);
        $this->load->view('admin/stratifications/duplicate', $data);
        $this->load->view('admin/footer');
    }

    /**
     * Excluir estratificacao
     */
    public function delete($id) {
        $stratification = $this->Stratification_model->get_by_id($id);
        if (!$stratification) {
            show_404();
            return;
        }

        $this->Stratification_model->delete($id);
        $this->session->set_flashdata('success', 'Estratificação removida com sucesso.');
        redirect('stratifications');
    }

    /**
     * AJAX: Retorna perguntas com opcoes para um questionario.
     * Mantido para compatibilidade — a view nova chama ajax_get_dimensions.
     */
    public function ajax_get_questions($questionnaire_id) {
        header('Content-Type: application/json');

        $questions = $this->Question_model->get_by_questionnaire($questionnaire_id);
        $eligible = $this->build_eligible_questions($questions);

        echo json_encode(array('success' => true, 'questions' => $eligible));
    }

    /**
     * AJAX: Retorna todas as dimensoes possiveis de estratificacao para o questionario:
     *   - questions  : perguntas radio/checkbox/select com suas opcoes
     *   - applicators: usuarios atribuiveis (aplicador + supervisor) — viram estratos
     *   - weekdays   : 7 dias da semana — viram estratos
     * Item 1+2: aplicador e dia da semana sao dimensoes nao-pergunta.
     */
    public function ajax_get_dimensions($questionnaire_id) {
        header('Content-Type: application/json');

        $questions = $this->Question_model->get_by_questionnaire($questionnaire_id);
        $eligible_questions = $this->build_eligible_questions($questions);

        $applicators_raw = $this->User_model->get_assignable_users();
        $applicators = array();
        foreach ($applicators_raw as $u) {
            $applicators[] = array(
                'id' => (int)$u->id,
                'full_name' => $u->full_name,
                'username' => $u->username,
                'role' => $u->role
            );
        }

        $weekdays = array(
            array('value' => 'monday',    'label' => 'Segunda-feira'),
            array('value' => 'tuesday',   'label' => 'Terça-feira'),
            array('value' => 'wednesday', 'label' => 'Quarta-feira'),
            array('value' => 'thursday',  'label' => 'Quinta-feira'),
            array('value' => 'friday',    'label' => 'Sexta-feira'),
            array('value' => 'saturday',  'label' => 'Sábado'),
            array('value' => 'sunday',    'label' => 'Domingo'),
        );

        echo json_encode(array(
            'success' => true,
            'questions' => $eligible_questions,
            'applicators' => $applicators,
            'weekdays' => $weekdays
        ));
    }

    private function build_eligible_questions($questions) {
        $eligible = array();
        foreach ($questions as $q) {
            if (!in_array($q->question_type, array('radio', 'checkbox', 'select'))) continue;
            if (empty($q->options)) continue;

            $options = array();
            foreach ($q->options as $opt) {
                $options[] = array(
                    'id' => $opt->id,
                    'option_text' => $opt->option_text,
                    'option_value' => $opt->option_value
                );
            }

            $eligible[] = array(
                'id' => $q->id,
                'question_text' => $q->question_text,
                'question_type' => $q->question_type,
                'options' => $options
            );
        }
        return $eligible;
    }

    /**
     * AJAX: Retorna progresso em tempo real (com filtro opcional por aplicador).
     */
    public function ajax_get_progress($id) {
        header('Content-Type: application/json');

        $stratification = $this->Stratification_model->get_by_id($id);
        if (!$stratification) {
            echo json_encode(array('success' => false, 'message' => 'Estratificação não encontrada.'));
            return;
        }

        $applied_by = $this->input->get('applied_by');
        $applied_by = ($applied_by !== null && $applied_by !== '') ? (int)$applied_by : null;

        $progress = $this->Stratification_model->get_progress_by_id($id, $applied_by);
        echo json_encode(array('success' => true, 'data' => $progress));
    }
}
