<?php
/**
 * Helper para validação de datas no CodeIgniter
 * 
 * Coloque este arquivo em: application/helpers/date_validation_helper.php
 * 
 * Para usar, carregue o helper no controller:
 * $this->load->helper('date_validation');
 */

defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('setup_date_validation')) {
    /**
     * Configura as validações de data no Form Validation do CodeIgniter
     * 
     * @param object $ci Instância do CodeIgniter
     */
    function setup_date_validation(&$ci) {
        // Configurar mensagens de erro
        $ci->form_validation->set_message('valid_date_format', 'O campo {field} deve conter uma data válida no formato DD/MM/AAAA.');
        $ci->form_validation->set_message('valid_date_range', 'O campo {field} deve conter uma data entre 2020 e 2050.');
        $ci->form_validation->set_message('end_date_after_start', 'A data de fim deve ser posterior à data de início.');
        
        // Criar callbacks para validação se não existirem
        if (!method_exists($ci, '_valid_date_format')) {
            $ci->_valid_date_format = function($date) use ($ci) {
                return validate_date_format($date);
            };
        }
        
        if (!method_exists($ci, '_valid_date_range')) {
            $ci->_valid_date_range = function($date) use ($ci) {
                return validate_date_range($date);
            };
        }
        
        if (!method_exists($ci, '_end_date_after_start')) {
            $ci->_end_date_after_start = function($end_date, $start_date) use ($ci) {
                return validate_end_date_after_start($end_date, $start_date);
            };
        }
    }
}

if (!function_exists('validate_date_format')) {
    /**
     * Valida se uma data está no formato correto e é válida
     * 
     * @param string $date Data no formato Y-m-d
     * @return bool
     */
    function validate_date_format($date) {
        if (empty($date)) {
            return TRUE; // Campo não obrigatório
        }
        
        // Verificar formato Y-m-d
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return FALSE;
        }
        
        // Verificar se é uma data válida
        $date_parts = explode('-', $date);
        $year = (int)$date_parts[0];
        $month = (int)$date_parts[1];
        $day = (int)$date_parts[2];
        
        return checkdate($month, $day, $year);
    }
}

if (!function_exists('validate_date_range')) {
    /**
     * Valida se uma data está dentro do range permitido
     * 
     * @param string $date Data no formato Y-m-d
     * @param int $min_year Ano mínimo (padrão: 2020)
     * @param int $max_year Ano máximo (padrão: 2050)
     * @return bool
     */
    function validate_date_range($date, $min_year = 2020, $max_year = 2050) {
        if (empty($date)) {
            return TRUE; // Campo não obrigatório
        }
        
        if (!validate_date_format($date)) {
            return FALSE; // Deve ser uma data válida primeiro
        }
        
        $year = (int)substr($date, 0, 4);
        return $year >= $min_year && $year <= $max_year;
    }
}

if (!function_exists('validate_end_date_after_start')) {
    /**
     * Valida se a data de fim é posterior à data de início
     * 
     * @param string $end_date Data de fim no formato Y-m-d
     * @param string $start_date Data de início no formato Y-m-d
     * @return bool
     */
    function validate_end_date_after_start($end_date, $start_date) {
        if (empty($end_date) || empty($start_date)) {
            return TRUE; // Se alguma data está vazia, não validar
        }
        
        if (!validate_date_format($end_date) || !validate_date_format($start_date)) {
            return FALSE; // Ambas devem ser datas válidas
        }
        
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        
        return $end_timestamp > $start_timestamp;
    }
}

if (!function_exists('format_date_for_display')) {
    /**
     * Formata uma data para exibição (DD/MM/AAAA)
     * 
     * @param string $date Data no formato Y-m-d
     * @return string Data formatada ou string vazia
     */
    function format_date_for_display($date) {
        if (empty($date) || $date === '0000-00-00') {
            return '';
        }
        
        $timestamp = strtotime($date);
        return $timestamp ? date('d/m/Y', $timestamp) : '';
    }
}

if (!function_exists('format_date_for_database')) {
    /**
     * Formata uma data para banco de dados (Y-m-d)
     * 
     * @param string $date Data no formato DD/MM/AAAA ou Y-m-d
     * @return string Data formatada ou NULL
     */
    function format_date_for_database($date) {
        if (empty($date)) {
            return NULL;
        }
        
        // Se já está no formato correto
        if (validate_date_format($date)) {
            return $date;
        }
        
        // Tentar converter de DD/MM/AAAA
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
            $formatted = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
            return validate_date_format($formatted) ? $formatted : NULL;
        }
        
        return NULL;
    }
}

if (!function_exists('get_date_validation_rules')) {
    /**
     * Retorna regras de validação padrão para datas
     * 
     * @param bool $required Se o campo é obrigatório
     * @param bool $check_range Se deve verificar o range de anos
     * @return string Regras de validação
     */
    function get_date_validation_rules($required = false, $check_range = true) {
        $rules = [];
        
        if ($required) {
            $rules[] = 'required';
        }
        
        $rules[] = 'callback__valid_date_format';
        
        if ($check_range) {
            $rules[] = 'callback__valid_date_range';
        }
        
        return implode('|', $rules);
    }
}

if (!function_exists('validate_date_pair')) {
    /**
     * Valida um par de datas (início e fim)
     * 
     * @param string $start_date Data de início
     * @param string $end_date Data de fim
     * @return array Array com 'valid' (bool) e 'errors' (array)
     */
    function validate_date_pair($start_date, $end_date) {
        $errors = [];
        
        if (!empty($start_date) && !validate_date_format($start_date)) {
            $errors[] = 'Data de início inválida';
        }
        
        if (!empty($end_date) && !validate_date_format($end_date)) {
            $errors[] = 'Data de fim inválida';
        }
        
        if (!empty($start_date) && !validate_date_range($start_date)) {
            $errors[] = 'Data de início fora do range permitido (2020-2050)';
        }
        
        if (!empty($end_date) && !validate_date_range($end_date)) {
            $errors[] = 'Data de fim fora do range permitido (2020-2050)';
        }
        
        if (empty($errors) && !empty($start_date) && !empty($end_date)) {
            if (!validate_end_date_after_start($end_date, $start_date)) {
                $errors[] = 'Data de fim deve ser posterior à data de início';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

if (!function_exists('calculate_days_between')) {
    /**
     * Calcula o número de dias entre duas datas
     * 
     * @param string $start_date Data de início
     * @param string $end_date Data de fim
     * @return int|false Número de dias ou false se inválido
     */
    function calculate_days_between($start_date, $end_date) {
        if (empty($start_date) || empty($end_date)) {
            return false;
        }
        
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        
        if (!$start_timestamp || !$end_timestamp) {
            return false;
        }
        
        return round(($end_timestamp - $start_timestamp) / (60 * 60 * 24));
    }
}

if (!function_exists('get_project_progress')) {
    /**
     * Calcula o progresso de um projeto baseado nas datas
     * 
     * @param string $start_date Data de início
     * @param string $end_date Data de fim
     * @param string $current_date Data atual (opcional)
     * @return array Array com 'percentage', 'status', 'days_remaining'
     */
    function get_project_progress($start_date, $end_date, $current_date = null) {
        if (empty($start_date) || empty($end_date)) {
            return null;
        }
        
        $current_date = $current_date ?: date('Y-m-d');
        
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        $current_timestamp = strtotime($current_date);
        
        if (!$start_timestamp || !$end_timestamp || !$current_timestamp) {
            return null;
        }
        
        $total_duration = $end_timestamp - $start_timestamp;
        $elapsed = $current_timestamp - $start_timestamp;
        $days_remaining = round(($end_timestamp - $current_timestamp) / (60 * 60 * 24));
        
        if ($current_timestamp < $start_timestamp) {
            $status = 'not_started';
            $percentage = 0;
        } elseif ($current_timestamp > $end_timestamp) {
            $status = 'overdue';
            $percentage = 100;
        } else {
            $status = 'in_progress';
            $percentage = round(($elapsed / $total_duration) * 100, 1);
        }
        
        return [
            'percentage' => $percentage,
            'status' => $status,
            'days_remaining' => $days_remaining,
            'total_days' => round($total_duration / (60 * 60 * 24))
        ];
    }
}