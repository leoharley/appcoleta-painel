<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Páginas institucionais públicas (sem autenticação).
 * Exemplo: /privacidade, /suporte
 */
class Pages extends CI_Controller {

    public function privacidade() {
        $data['title'] = 'Política de Privacidade';
        $data['active'] = 'privacidade';
        $this->load->view('pages/layout_head', $data);
        $this->load->view('pages/privacidade', $data);
        $this->load->view('pages/layout_foot', $data);
    }

    public function suporte() {
        $data['title'] = 'Suporte';
        $data['active'] = 'suporte';
        $this->load->view('pages/layout_head', $data);
        $this->load->view('pages/suporte', $data);
        $this->load->view('pages/layout_foot', $data);
    }

    public function exclusao_de_conta() {
        $data['title'] = 'Exclusão de Conta';
        $data['active'] = 'exclusao-de-conta';
        $this->load->view('pages/layout_head', $data);
        $this->load->view('pages/exclusao_de_conta', $data);
        $this->load->view('pages/layout_foot', $data);
    }
}
