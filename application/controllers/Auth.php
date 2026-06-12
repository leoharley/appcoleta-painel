<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('User_model');
        $this->load->library('form_validation');
    }

    public function login() {
        if ($this->session->userdata('admin_logged_in')) {
            redirect('dashboard');
        }

        if ($this->input->post()) {
            $this->form_validation->set_rules('username', 'Usuário', 'required');
            $this->form_validation->set_rules('password', 'Senha', 'required');

            if ($this->form_validation->run()) {
                $username = $this->input->post('username');
                $password = $this->input->post('password');

                $user = $this->User_model->authenticate($username, $password);

                if ($user && in_array($user->role, ['administrador', 'supervisor'])) {
                    $this->session->set_userdata(array(
                        'admin_logged_in' => TRUE,
                        'admin_id' => $user->id,
                        'admin_username' => $user->username,
                        'admin_name' => $user->full_name,
                        'admin_role' => $user->role
                    ));
                    redirect('dashboard');
                } else {
                    $data['error'] = 'Credenciais inválidas ou sem permissão de acesso.';
                }
            }
        }

        $data['title'] = 'Login - AppColeta Admin';
        $this->load->view('admin/login', $data);
    }

    public function logout() {
        $this->session->sess_destroy();
        redirect('auth/login');
    }
}