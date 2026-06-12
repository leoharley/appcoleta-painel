<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_all_settings() {
        $query = $this->db->get('system_settings');
        $settings = array();
        
        foreach ($query->result() as $setting) {
            $settings[$setting->setting_key] = $setting->setting_value;
        }
        
        return $settings;
    }

    public function get_setting($key) {
        $query = $this->db->get_where('system_settings', array('setting_key' => $key));
        $result = $query->row();
        
        return $result ? $result->setting_value : null;
    }

    public function update_setting($key, $value) {
        $data = array(
            'setting_value' => $value,
            'updated_at' => date('Y-m-d H:i:s')
        );
        
        $this->db->where('setting_key', $key);
        $updated = $this->db->update('system_settings', $data);
        
        if (!$updated) {
            // Se não atualizou, inserir novo
            $data['setting_key'] = $key;
            return $this->db->insert('system_settings', $data);
        }
        
        return $updated;
    }

    public function delete_setting($key) {
        $this->db->where('setting_key', $key);
        return $this->db->delete('system_settings');
    }
}