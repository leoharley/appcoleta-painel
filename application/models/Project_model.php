<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Project_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_all() {
        $this->db->select('p.*, u.full_name as created_by_name');
        $this->db->from('projects p');
        $this->db->join('users u', 'p.created_by = u.id', 'left');
        $this->db->order_by('p.created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function get_all_with_stats() {
        $projects = $this->get_all();
        
        foreach ($projects as &$project) {
            // Contar questionários vinculados
            $this->db->where('project_id', $project->id);
            $project->questionnaire_count = $this->db->count_all_results('questionnaires');
            
            // Contar respostas totais do projeto
            $this->db->select('COUNT(fr.id) as total_responses');
            $this->db->from('form_responses fr');
            $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id');
            $this->db->where('q.project_id', $project->id);
            $response_result = $this->db->get()->row();
            $project->total_responses = $response_result ? $response_result->total_responses : 0;
            
            // Última atividade
            $this->db->select('MAX(fr.completed_at) as last_activity');
            $this->db->from('form_responses fr');
            $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id');
            $this->db->where('q.project_id', $project->id);
            $this->db->where('fr.completed_at IS NOT NULL');
            $last = $this->db->get()->row();
            $project->last_activity = $last ? $last->last_activity : NULL;
            
            // Calcular progresso baseado nas datas
            $project->progress = $this->calculate_progress($project);
        }
        
        return $projects;
    }

    public function get_by_id($id) {
        $this->db->select('p.*, u.full_name as created_by_name');
        $this->db->from('projects p');
        $this->db->join('users u', 'p.created_by = u.id', 'left');
        $this->db->where('p.id', $id);
        
        return $this->db->get()->row();
    }

    public function get_active() {
        $this->db->where('status', 'active');
        $this->db->order_by('name', 'ASC');
        return $this->db->get('projects')->result();
    }

    public function get_for_select() {
        $this->db->select('id, name');
        $this->db->where_in('status', ['active', 'paused']);
        $this->db->order_by('name', 'ASC');
        return $this->db->get('projects')->result();
    }

    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->insert('projects', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $this->db->where('id', $id);
        return $this->db->update('projects', $data);
    }

    public function delete($id) {
        // Primeiro, remover vinculação dos questionários
        $this->db->where('project_id', $id);
        $this->db->update('questionnaires', array('project_id' => NULL));
        
        // Depois deletar o projeto
        $this->db->where('id', $id);
        return $this->db->delete('projects');
    }

    public function count_all() {
        return $this->db->count_all('projects');
    }

    public function count_by_status($status) {
        $this->db->where('status', $status);
        return $this->db->count_all_results('projects');
    }

    public function get_project_stats($project_id) {
        $stats = array();
        
        // Questionários do projeto
        $this->db->where('project_id', $project_id);
        $stats['questionnaires'] = $this->db->count_all_results('questionnaires');
        
        // Respostas totais
        $this->db->select('COUNT(fr.id) as total');
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id');
        $this->db->where('q.project_id', $project_id);
        $result = $this->db->get()->row();
        $stats['responses'] = $result ? $result->total : 0;
        
        // Respostas por status
        $this->db->select('fr.sync_status, COUNT(fr.id) as count');
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id');
        $this->db->where('q.project_id', $project_id);
        $this->db->group_by('fr.sync_status');
        $status_results = $this->db->get()->result();
        
        $stats['by_status'] = array();
        foreach ($status_results as $status_result) {
            $stats['by_status'][$status_result->sync_status] = $status_result->count;
        }
        
        // Aplicadores mais ativos no projeto
        $this->db->select('u.full_name, COUNT(fr.id) as response_count');
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id');
        $this->db->join('users u', 'fr.applied_by = u.id');
        $this->db->where('q.project_id', $project_id);
        $this->db->group_by('u.id, u.full_name');
        $this->db->order_by('response_count', 'DESC');
        $this->db->limit(5);
        $stats['top_aplicadores'] = $this->db->get()->result();
        
        // Atividade recente (últimos 30 dias)
        $this->db->select('DATE(fr.completed_at) as date, COUNT(fr.id) as count');
        $this->db->from('form_responses fr');
        $this->db->join('questionnaires q', 'fr.questionnaire_id = q.id');
        $this->db->where('q.project_id', $project_id);
        $this->db->where('fr.completed_at >=', date('Y-m-d', strtotime('-30 days')));
        $this->db->where('fr.completed_at IS NOT NULL');
        $this->db->group_by('DATE(fr.completed_at)');
        $this->db->order_by('date', 'ASC');
        $stats['recent_activity'] = $this->db->get()->result();
        
        return $stats;
    }

    public function get_dashboard_stats() {
        $stats = array();
        
        // Total de projetos por status
        $this->db->select('status, COUNT(*) as count');
        $this->db->group_by('status');
        $status_results = $this->db->get('projects')->result();
        
        foreach ($status_results as $status_result) {
            $stats['by_status'][$status_result->status] = $status_result->count;
        }
        
        // Projetos com mais atividade (por respostas)
        $this->db->select('p.id, p.name, COUNT(fr.id) as response_count');
        $this->db->from('projects p');
        $this->db->join('questionnaires q', 'p.id = q.project_id', 'left');
        $this->db->join('form_responses fr', 'q.id = fr.questionnaire_id', 'left');
        $this->db->group_by('p.id, p.name');
        $this->db->order_by('response_count', 'DESC');
        $this->db->limit(5);
        $stats['most_active'] = $this->db->get()->result();
        
        return $stats;
    }

    private function calculate_progress($project) {
        if (!$project->start_date || !$project->end_date) {
            return null;
        }
        
        $start = strtotime($project->start_date);
        $end = strtotime($project->end_date);
        $now = time();
        
        if ($now < $start) {
            return 0; // Ainda não começou
        }
        
        if ($now > $end) {
            return 100; // Já terminou (pelo prazo)
        }
        
        $total_duration = $end - $start;
        $elapsed = $now - $start;
        
        return round(($elapsed / $total_duration) * 100, 1);
    }

    public function search($term) {
        $this->db->select('p.*, u.full_name as created_by_name');
        $this->db->from('projects p');
        $this->db->join('users u', 'p.created_by = u.id', 'left');
        $this->db->group_start();
            $this->db->like('p.name', $term);
            $this->db->or_like('p.description', $term);
            $this->db->or_like('p.client_name', $term);
        $this->db->group_end();
        $this->db->order_by('p.created_at', 'DESC');
        
        return $this->db->get()->result();
    }

    public function get_overdue_projects() {
        $this->db->select('p.*, u.full_name as created_by_name');
        $this->db->from('projects p');
        $this->db->join('users u', 'p.created_by = u.id', 'left');
        $this->db->where('p.end_date <', date('Y-m-d'));
        $this->db->where_in('p.status', ['active', 'paused']);
        $this->db->order_by('p.end_date', 'ASC');
        
        return $this->db->get()->result();
    }

    public function get_upcoming_deadlines($days = 7) {
        $this->db->select('p.*, u.full_name as created_by_name');
        $this->db->from('projects p');
        $this->db->join('users u', 'p.created_by = u.id', 'left');
        $this->db->where('p.end_date >=', date('Y-m-d'));
        $this->db->where('p.end_date <=', date('Y-m-d', strtotime("+{$days} days")));
        $this->db->where_in('p.status', ['active', 'paused']);
        $this->db->order_by('p.end_date', 'ASC');
        
        return $this->db->get()->result();
    }
}