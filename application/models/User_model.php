<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_all() {
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get('users')->result();
    }

    public function get_by_id($id) {
        return $this->db->get_where('users', array('id' => $id))->row();
    }

    public function get_aplicadores() {
        $this->db->where('role', 'aplicador');
        $this->db->where('is_active', TRUE);
        $this->db->order_by('full_name', 'ASC');
        return $this->db->get('users')->result();
    }

    public function get_assignable_users() {
        $this->db->where_in('role', array('aplicador', 'supervisor'));
        $this->db->where('is_active', TRUE);
        $this->db->order_by('role', 'ASC');
        $this->db->order_by('full_name', 'ASC');
        return $this->db->get('users')->result();
    }

    public function authenticate($username, $password) {
        $user = $this->db->get_where('users', array(
            'username' => $username,
            'is_active' => TRUE
        ))->row();

        if ($user && password_verify($password, $user->password_hash)) {
            // Atualizar último login
            $this->update_last_login($user->id);
            return $user;
        }
        return FALSE;
    }

    public function get_all_with_stats() {
        $this->db->select('
            u.*,
            COALESCE(COUNT(fr.id), 0) as total_responses,
            MAX(fr.completed_at) as last_response_date
        ');
        $this->db->from('users u');
        $this->db->join('form_responses fr', 'u.id = fr.applied_by', 'left');
        $this->db->group_by('u.id, u.full_name, u.username, u.email, u.role, u.is_active, u.created_at, u.updated_at, u.password_hash');
        $this->db->order_by('u.created_at', 'DESC');
        
        return $this->db->get()->result();
    }

    public function create($data) {
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->db->insert('users', $data) ? $this->db->insert_id() : FALSE;
    }

    public function update($id, $data) {
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $this->db->where('id', $id);
        return $this->db->update('users', $data);
    }

    public function delete($id) {
        $original_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        $this->db->where('id', $id);
        $this->db->delete('users');

        $affected = $this->db->affected_rows();
        $error = $this->db->error();

        $this->db->db_debug = $original_debug;

        if (!empty($error['code'])) {
            return array('success' => false, 'error' => $error);
        }
        if ($affected < 1) {
            return array(
                'success' => false,
                'error' => array('code' => 0, 'message' => 'Nenhum registro removido.')
            );
        }
        return array('success' => true);
    }

    public function count_all() {
        return $this->db->count_all('users');
    }

    public function get_stats() {
        $stats = array(
            'total' => 0,
            'aplicadores' => 0,
            'supervisores' => 0,
            'administradores' => 0,
            'ativos' => 0,
            'inativos' => 0
        );
        
        // Total geral
        $stats['total'] = $this->db->count_all('users');
        
        // Total por role
        $this->db->select('role, COUNT(*) as count');
        $this->db->group_by('role');
        $roles = $this->db->get('users')->result();
        
        foreach ($roles as $role) {
            switch ($role->role) {
                case 'aplicador':
                    $stats['aplicadores'] = $role->count;
                    break;
                case 'supervisor':
                    $stats['supervisores'] = $role->count;
                    break;
                case 'administrador':
                    $stats['administradores'] = $role->count;
                    break;
            }
        }
        
        // Total ativos/inativos
        $this->db->select('is_active, COUNT(*) as count');
        $this->db->group_by('is_active');
        $status_counts = $this->db->get('users')->result();
        
        foreach ($status_counts as $status) {
            if ($status->is_active == 1) {
                $stats['ativos'] = $status->count;
            } else {
                $stats['inativos'] = $status->count;
            }
        }
        
        return $stats;
    }

    public function update_last_login($user_id) {
        $data = array(
            'updated_at' => date('Y-m-d H:i:s')
        );
        
        // Se houver um campo last_login na tabela, descomente a linha abaixo
        // $data['last_login'] = date('Y-m-d H:i:s');
        
        $this->db->where('id', $user_id);
        return $this->db->update('users', $data);
    }

    public function is_username_available($username, $exclude_id = null) {
        $this->db->where('username', $username);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->count_all_results('users') == 0;
    }

    public function is_email_available($email, $exclude_id = null) {
        $this->db->where('email', $email);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->count_all_results('users') == 0;
    }
}