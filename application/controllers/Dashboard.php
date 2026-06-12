<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('User_model');
        $this->load->model('Questionnaire_model');
        $this->load->model('Response_model');
        $this->load->helper('url'); // Carrega o helper URL

        $this->check_auth();
    }

    public function index() {
        $data['title'] = 'Dashboard - AppColeta';
        
        // Estatísticas gerais
        $data['stats'] = array(
            'total_questionnaires' => $this->Questionnaire_model->count_all(),
            'active_questionnaires' => $this->Questionnaire_model->count_active(),
            'total_responses' => $this->Response_model->count_all(),
            'responses_today' => $this->Response_model->count_today(),
            'pending_sync' => $this->Response_model->count_pending_sync(),
            'total_users' => $this->User_model->count_all()
        );
        
        // Gráficos de dados
        $data['charts'] = array(
            'responses_by_day' => $this->Response_model->get_responses_by_day(30),
            'questionnaires_usage' => $this->Questionnaire_model->get_usage_stats(),
            'sync_status' => $this->Response_model->get_sync_status_stats()
        );
        
        // Atividade recente
        $data['recent_responses'] = $this->Response_model->get_recent(10);
        $data['recent_activities'] = $this->get_recent_activities();
        
        $this->load->view('admin/header', $data);
        $this->load->view('admin/dashboard', $data);
        $this->load->view('admin/footer');
    }

    private function check_auth() {
        if (!$this->session->userdata('admin_logged_in')) {
            redirect('auth/login');
        }
    }

    private function get_recent_activities() {
        // Implementar busca de atividades recentes
        return array();
    }
}