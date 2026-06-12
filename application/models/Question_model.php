<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Question_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Criar nova pergunta
     * 
     * @param array $data Dados da pergunta
     * @return int|false ID da pergunta criada ou false
     */
    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('questions', $data) ? $this->db->insert_id() : FALSE;
    }

    /**
     * Atualizar pergunta
     * 
     * @param int $id ID da pergunta
     * @param array $data Dados para atualização
     * @return bool Sucesso da operação
     */
    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('questions', $data);
    }

    /**
     * Obter perguntas de um questionário com suas opções
     * 
     * @param int $questionnaire_id ID do questionário
     * @return array Array de perguntas com opções
     */
    public function get_by_questionnaire($questionnaire_id) {
        $this->db->select('*');
        $this->db->from('questions');
        $this->db->where('questionnaire_id', $questionnaire_id);
        $this->db->order_by('order_index', 'ASC');
        $questions = $this->db->get()->result();
        
        // Para cada pergunta, buscar suas opções
        foreach ($questions as $question) {
            $question->options = $this->get_options($question->id);
        }
        
        return $questions;
    }

    /**
     * Obter perguntas com lógica condicional - método robusto
     * 
     * @param int $questionnaire_id ID do questionário
     * @return array Array de perguntas com opções e lógica condicional
     */
    public function get_by_questionnaire_with_logic_robust($questionnaire_id) {
        try {
            // Versão compatível com PostgreSQL - sem CASE WHEN complexo
            $this->db->select('*');
            $this->db->from('questions');
            $this->db->where('questionnaire_id', $questionnaire_id);
            $this->db->order_by('order_index', 'ASC');
            
            $questions = $this->db->get()->result();
            
            foreach ($questions as $question) {
                // Buscar opções
                $question->options = $this->get_options($question->id);
                
                // Processar lógica condicional de forma mais segura
                $question->conditional_logic_decoded = null;
                
                // Verificar se existe lógica condicional válida
                if (isset($question->conditional_logic) && 
                    !is_null($question->conditional_logic) && 
                    trim($question->conditional_logic) !== '') {
                    
                    $logic = json_decode($question->conditional_logic, true);
                    if (json_last_error() === JSON_ERROR_NONE && !empty($logic)) {
                        $question->conditional_logic_decoded = $logic;
                    }
                }
            }
            
            return $questions;
            
        } catch (Exception $e) {
            log_message('error', 'Erro no método robust: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obter perguntas com lógica condicional - método com subquery
     * 
     * @param int $questionnaire_id ID do questionário
     * @return array Array de perguntas com opções e lógica condicional
     */
    public function get_by_questionnaire_with_logic_subquery($questionnaire_id) {
        try {
            // Primeira query: buscar perguntas
            $this->db->select('*');
            $this->db->from('questions');
            $this->db->where('questionnaire_id', $questionnaire_id);
            $this->db->order_by('order_index', 'ASC');
            
            $questions = $this->db->get()->result();
            
            // Segunda query: buscar todas as opções de uma vez
            if (!empty($questions)) {
                $question_ids = array_column($questions, 'id');
                
                $this->db->select('*');
                $this->db->from('question_options');
                $this->db->where_in('question_id', $question_ids);
                $this->db->order_by('question_id, order_index', 'ASC');
                
                $all_options = $this->db->get()->result();
                
                // Agrupar opções por pergunta
                $options_by_question = [];
                foreach ($all_options as $option) {
                    $options_by_question[$option->question_id][] = $option;
                }
                
                // Associar opções às perguntas
                foreach ($questions as $question) {
                    $question->options = isset($options_by_question[$question->id]) ? 
                                       $options_by_question[$question->id] : [];
                    
                    // Processar lógica condicional
                    $question->conditional_logic_decoded = null;
                    if (!empty($question->conditional_logic)) {
                        $logic = json_decode($question->conditional_logic, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $question->conditional_logic_decoded = $logic;
                        }
                    }
                }
            }
            
            return $questions;
            
        } catch (Exception $e) {
            log_message('error', 'Erro no método subquery: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obter perguntas com lógica condicional - método simples
     * 
     * @param int $questionnaire_id ID do questionário
     * @return array Array de perguntas com opções e lógica condicional
     */
    public function get_by_questionnaire_with_logic_simple($questionnaire_id) {
        try {
            $questions = $this->get_by_questionnaire($questionnaire_id);
            
            foreach ($questions as $question) {
                $question->conditional_logic_decoded = null;
                if (!empty($question->conditional_logic)) {
                    $logic = json_decode($question->conditional_logic, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $question->conditional_logic_decoded = $logic;
                    }
                }
            }
            
            return $questions;
            
        } catch (Exception $e) {
            log_message('error', 'Erro no método simple: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obter perguntas com lógica condicional - método individual
     * 
     * @param int $questionnaire_id ID do questionário
     * @return array Array de perguntas com opções e lógica condicional
     */
    public function get_by_questionnaire_with_logic_individual($questionnaire_id) {
        try {
            $this->db->select('id, questionnaire_id, question_text, question_type, is_required, order_index, conditional_logic');
            $this->db->from('questions');
            $this->db->where('questionnaire_id', $questionnaire_id);
            $this->db->order_by('order_index', 'ASC');
            
            $questions = $this->db->get()->result();
            
            foreach ($questions as $question) {
                // Buscar opções individualmente
                $question->options = $this->get_options($question->id);
                
                // Processar lógica condicional
                $question->conditional_logic_decoded = null;
                if (!empty($question->conditional_logic)) {
                    $logic = json_decode($question->conditional_logic, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $question->conditional_logic_decoded = $logic;
                    } else {
                        log_message('warning', "JSON inválido na pergunta {$question->id}: " . $question->conditional_logic);
                    }
                }
            }
            
            return $questions;
            
        } catch (Exception $e) {
            log_message('error', 'Erro no método individual: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obter perguntas com lógica condicional - método base
     * 
     * @param int $questionnaire_id ID do questionário
     * @return array Array de perguntas com opções e lógica condicional
     */
    public function get_by_questionnaire_with_logic($questionnaire_id) {
        return $this->get_by_questionnaire_with_logic_robust($questionnaire_id);
    }

    /**
     * Obter pergunta por ID com suas opções
     * 
     * @param int $id ID da pergunta
     * @return object|null Pergunta com opções
     */
    public function get_by_id($id) {
        $question = $this->db->get_where('questions', array('id' => $id))->row();
        if ($question) {
            $question->options = $this->get_options($id);
            
            // Processar lógica condicional
            $question->conditional_logic_decoded = null;
            if (!empty($question->conditional_logic)) {
                $logic = json_decode($question->conditional_logic, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $question->conditional_logic_decoded = $logic;
                }
            }
        }
        return $question;
    }

    /**
     * Obter opções de uma pergunta
     * 
     * @param int $question_id ID da pergunta
     * @return array Array de opções
     */
    public function get_options($question_id) {
        $this->db->select('*');
        $this->db->from('question_options');
        $this->db->where('question_id', $question_id);
        $this->db->order_by('order_index', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Excluir pergunta e suas opções
     * 
     * @param int $question_id ID da pergunta
     * @return bool Sucesso da operação
     */
    public function delete($question_id) {
        // Primeiro excluir as opções
        $this->delete_options($question_id);
        
        // Depois excluir a pergunta
        $this->db->where('id', $question_id);
        return $this->db->delete('questions');
    }

    /**
     * Criar nova opção de pergunta
     * 
     * @param array $data Dados da opção
     * @return int|false ID da opção criada ou false
     */
    public function create_option($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('question_options', $data) ? $this->db->insert_id() : FALSE;
    }

    /**
     * Atualizar opção
     * 
     * @param int $option_id ID da opção
     * @param array $data Dados para atualização
     * @return bool Sucesso da operação
     */
    public function update_option($option_id, $data) {
        $this->db->where('id', $option_id);
        return $this->db->update('question_options', $data);
    }

    /**
     * Excluir opção específica
     * 
     * @param int $option_id ID da opção
     * @return bool Sucesso da operação
     */
    public function delete_option($option_id) {
        $this->db->where('id', $option_id);
        return $this->db->delete('question_options');
    }

    /**
     * Excluir todas as opções de uma pergunta
     * 
     * @param int $question_id ID da pergunta
     * @return bool Sucesso da operação
     */
    public function delete_options($question_id) {
        $this->db->where('question_id', $question_id);
        return $this->db->delete('question_options');
    }

    /**
     * Reordenar perguntas de um questionário
     * 
     * @param int $questionnaire_id ID do questionário
     * @param array $question_orders Array com ID da pergunta e nova ordem
     * @return bool Sucesso da operação
     */
    public function reorder_questions($questionnaire_id, $question_orders) {
        $this->db->trans_start();
        
        foreach ($question_orders as $question_id => $order) {
            $this->db->where('id', $question_id);
            $this->db->where('questionnaire_id', $questionnaire_id);
            $this->db->update('questions', ['order_index' => $order]);
        }
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Duplicar pergunta com suas opções
     * 
     * @param int $question_id ID da pergunta original
     * @param int $new_questionnaire_id ID do novo questionário
     * @return int|false ID da nova pergunta ou false
     */
    public function duplicate_question($question_id, $new_questionnaire_id) {
        // Buscar pergunta original
        $original_question = $this->db->get_where('questions', ['id' => $question_id])->row();
        
        if (!$original_question) {
            return false;
        }
        
        // Criar nova pergunta
        $new_question_data = [
            'questionnaire_id' => $new_questionnaire_id,
            'question_text' => $original_question->question_text,
            'question_type' => $original_question->question_type,
            'is_required' => $original_question->is_required,
            'order_index' => $original_question->order_index,
            'conditional_logic' => $original_question->conditional_logic
        ];
        
        $new_question_id = $this->create($new_question_data);
        
        if ($new_question_id) {
            // Duplicar opções se existirem
            $options = $this->get_options($question_id);
            foreach ($options as $option) {
                $this->create_option([
                    'question_id' => $new_question_id,
                    'option_text' => $option->option_text,
                    'option_value' => $option->option_value,
                    'order_index' => $option->order_index
                ]);
            }
        }
        
        return $new_question_id;
    }

    /**
     * Validar estrutura de pergunta
     * 
     * @param array $question_data Dados da pergunta
     * @return array Array com status e mensagens de erro
     */
    public function validate_question($question_data) {
        $errors = [];
        
        // Validar texto da pergunta
        if (empty(trim($question_data['question_text']))) {
            $errors[] = 'O texto da pergunta é obrigatório.';
        }
        
        // Validar tipo
        $allowed_types = ['text', 'textarea', 'number', 'radio', 'checkbox', 'select', 'date', 'time', 'email', 'phone'];
        if (!in_array($question_data['question_type'], $allowed_types)) {
            $errors[] = 'Tipo de pergunta inválido.';
        }
        
        // Validar se perguntas de múltipla escolha têm opções
        if (in_array($question_data['question_type'], ['radio', 'checkbox', 'select'])) {
            if (empty($question_data['options']) || !is_array($question_data['options'])) {
                $errors[] = 'Perguntas de múltipla escolha devem ter pelo menos uma opção.';
            } else {
                $valid_options = 0;
                foreach ($question_data['options'] as $option) {
                    if (!empty(trim($option['option_text']))) {
                        $valid_options++;
                    }
                }
                if ($valid_options === 0) {
                    $errors[] = 'Pelo menos uma opção deve ter texto válido.';
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Obter estatísticas de perguntas por tipo
     * 
     * @param int $questionnaire_id ID do questionário
     * @return array Estatísticas
     */
    public function get_question_stats($questionnaire_id) {
        $this->db->select('question_type, COUNT(*) as count');
        $this->db->from('questions');
        $this->db->where('questionnaire_id', $questionnaire_id);
        $this->db->group_by('question_type');
        $stats = $this->db->get()->result();
        
        $formatted_stats = [];
        foreach ($stats as $stat) {
            $formatted_stats[$stat->question_type] = $stat->count;
        }
        
        return $formatted_stats;
    }

    /**
     * Obter estatísticas de lógica condicional
     * 
     * @param int $questionnaire_id ID do questionário (opcional)
     * @return array Estatísticas de lógica condicional
     */
    public function get_conditional_logic_stats($questionnaire_id = null) {
        $stats = [
            'total_questions' => 0,
            'questions_with_logic' => 0,
            'visibility_rules' => 0,
            'required_rules' => 0,
            'complex_logic' => 0,
            'invalid_logic' => 0
        ];
        
        try {
            // Query base
            $this->db->select('id, conditional_logic');
            $this->db->from('questions');
            
            if ($questionnaire_id) {
                $this->db->where('questionnaire_id', $questionnaire_id);
            }
            
            $questions = $this->db->get()->result();
            $stats['total_questions'] = count($questions);
            
            foreach ($questions as $question) {
                if (!empty($question->conditional_logic)) {
                    $logic = json_decode($question->conditional_logic, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $stats['questions_with_logic']++;
                        
                        if (isset($logic['visibility']) && !empty($logic['visibility']['conditions'])) {
                            $stats['visibility_rules']++;
                        }
                        
                        if (isset($logic['required']) && !empty($logic['required']['conditions'])) {
                            $stats['required_rules']++;
                        }
                        
                        // Lógica complexa: tem mais de 3 condições ou ambos os tipos
                        $total_conditions = 0;
                        $has_both_types = false;
                        
                        if (isset($logic['visibility'])) {
                            $total_conditions += count($logic['visibility']['conditions'] ?? []);
                        }
                        if (isset($logic['required'])) {
                            $total_conditions += count($logic['required']['conditions'] ?? []);
                        }
                        
                        if (isset($logic['visibility']) && isset($logic['required'])) {
                            $has_both_types = true;
                        }
                        
                        if ($total_conditions > 3 || $has_both_types) {
                            $stats['complex_logic']++;
                        }
                    } else {
                        $stats['invalid_logic']++;
                    }
                }
            }
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao obter estatísticas de lógica condicional: ' . $e->getMessage());
        }
        
        return $stats;
    }

    /**
     * Validar integridade da lógica condicional de um questionário
     * 
     * @param int $questionnaire_id ID do questionário
     * @return array Resultado da validação
     */
    public function validate_questionnaire_logic_integrity($questionnaire_id) {
        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'questions_analyzed' => 0,
            'logic_chains' => []
        ];
        
        try {
            $questions = $this->get_by_questionnaire_with_logic($questionnaire_id);
            $validation['questions_analyzed'] = count($questions);
            
            foreach ($questions as $index => $question) {
                if (!empty($question->conditional_logic_decoded)) {
                    $question_errors = $this->validate_question_logic_integrity($question, $index, $questions);
                    
                    $validation['errors'] = array_merge($validation['errors'], $question_errors['errors']);
                    $validation['warnings'] = array_merge($validation['warnings'], $question_errors['warnings']);
                }
            }
            
            // Detectar cadeias de dependência
            $validation['logic_chains'] = $this->detect_logic_chains($questions);
            
            $validation['valid'] = empty($validation['errors']);
            
        } catch (Exception $e) {
            $validation['valid'] = false;
            $validation['errors'][] = 'Erro interno: ' . $e->getMessage();
            log_message('error', 'Erro na validação de integridade: ' . $e->getMessage());
        }
        
        return $validation;
    }

    /**
     * Validar integridade da lógica de uma pergunta específica
     * 
     * @param object $question Pergunta
     * @param int $question_index Índice da pergunta
     * @param array $all_questions Todas as perguntas do questionário
     * @return array Resultado da validação
     */
    private function validate_question_logic_integrity($question, $question_index, $all_questions) {
        $errors = [];
        $warnings = [];
        $logic = $question->conditional_logic_decoded;
        
        // Validar regras de visibilidade
        if (isset($logic['visibility'])) {
            $visibility_validation = $this->validate_rule_integrity($logic['visibility'], $question_index, $all_questions, 'visibilidade');
            $errors = array_merge($errors, $visibility_validation['errors']);
            $warnings = array_merge($warnings, $visibility_validation['warnings']);
        }
        
        // Validar regras de obrigatoriedade
        if (isset($logic['required'])) {
            $required_validation = $this->validate_rule_integrity($logic['required'], $question_index, $all_questions, 'obrigatoriedade');
            $errors = array_merge($errors, $required_validation['errors']);
            $warnings = array_merge($warnings, $validation['warnings']);
        }
        
        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Validar integridade de uma regra específica
     * 
     * @param array $rule Regra a ser validada
     * @param int $question_index Índice da pergunta atual
     * @param array $all_questions Todas as perguntas
     * @param string $rule_type Tipo da regra
     * @return array Resultado da validação
     */
    private function validate_rule_integrity($rule, $question_index, $all_questions, $rule_type) {
        $errors = [];
        $warnings = [];
        
        if (!isset($rule['conditions']) || !is_array($rule['conditions'])) {
            $errors[] = "Pergunta " . ($question_index + 1) . ": Regra de {$rule_type} sem condições válidas";
            return ['errors' => $errors, 'warnings' => $warnings];
        }
        
        foreach ($rule['conditions'] as $condition_index => $condition) {
            if (!isset($condition['question']) || !is_numeric($condition['question'])) {
                $errors[] = "Pergunta " . ($question_index + 1) . ": Condição " . ($condition_index + 1) . " sem pergunta de referência válida";
                continue;
            }
            
            $target_question_index = intval($condition['question']);
            
            // Verificar se a pergunta referenciada existe
            if ($target_question_index >= count($all_questions)) {
                $errors[] = "Pergunta " . ($question_index + 1) . ": Referencia pergunta inexistente (" . ($target_question_index + 1) . ")";
                continue;
            }
            
            // Verificar se não referencia pergunta posterior
            if ($target_question_index >= $question_index) {
                $errors[] = "Pergunta " . ($question_index + 1) . ": Não pode referenciar pergunta posterior ou a si mesma";
                continue;
            }
            
            $target_question = $all_questions[$target_question_index];
            
            // Validar operador
            $valid_operators = ['equals', 'not_equals', 'contains', 'not_contains', 'greater_than', 'less_than', 'is_empty', 'is_not_empty'];
            if (!isset($condition['operator']) || !in_array($condition['operator'], $valid_operators)) {
                $errors[] = "Pergunta " . ($question_index + 1) . ": Operador inválido na condição " . ($condition_index + 1);
                continue;
            }
            
            // Validar valor baseado no tipo de pergunta e operador
            if (!in_array($condition['operator'], ['is_empty', 'is_not_empty'])) {
                if (!isset($condition['value']) || trim($condition['value']) === '') {
                    $warnings[] = "Pergunta " . ($question_index + 1) . ": Condição " . ($condition_index + 1) . " sem valor definido";
                }
                
                // Validar se valor é válido para pergunta de múltipla escolha
                if (in_array($target_question->question_type, ['radio', 'select']) && isset($condition['value'])) {
                    $valid_values = array_column($target_question->options, 'option_text');
                    if (!in_array($condition['value'], $valid_values)) {
                        $warnings[] = "Pergunta " . ($question_index + 1) . ": Valor '{$condition['value']}' pode não estar disponível na pergunta referenciada";
                    }
                }
            }
        }
        
        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Detectar cadeias de dependência lógica
     * 
     * @param array $questions Array de perguntas
     * @return array Cadeias detectadas
     */
    private function detect_logic_chains($questions) {
        $chains = [];
        $dependencies = [];
        
        // Mapear dependências
        foreach ($questions as $index => $question) {
            if (!empty($question->conditional_logic_decoded)) {
                $logic = $question->conditional_logic_decoded;
                $question_dependencies = [];
                
                // Coletar dependências de visibilidade
                if (isset($logic['visibility']['conditions'])) {
                    foreach ($logic['visibility']['conditions'] as $condition) {
                        if (isset($condition['question'])) {
                            $question_dependencies[] = intval($condition['question']);
                        }
                    }
                }
                
                // Coletar dependências de obrigatoriedade
                if (isset($logic['required']['conditions'])) {
                    foreach ($logic['required']['conditions'] as $condition) {
                        if (isset($condition['question'])) {
                            $question_dependencies[] = intval($condition['question']);
                        }
                    }
                }
                
                if (!empty($question_dependencies)) {
                    $dependencies[$index] = array_unique($question_dependencies);
                }
            }
        }
        
        // Detectar cadeias
        foreach ($dependencies as $question_index => $deps) {
            $chain = $this->trace_dependency_chain($question_index, $dependencies, []);
            if (count($chain) > 2) {
                $chains[] = [
                    'start' => $question_index + 1,
                    'chain' => array_map(function($i) { return $i + 1; }, $chain),
                    'length' => count($chain)
                ];
            }
        }
        
        return $chains;
    }

    /**
     * Rastrear cadeia de dependência
     * 
     * @param int $current_question Pergunta atual
     * @param array $all_dependencies Todas as dependências
     * @param array $visited Perguntas já visitadas
     * @return array Cadeia de dependência
     */
    private function trace_dependency_chain($current_question, $all_dependencies, $visited) {
        if (in_array($current_question, $visited)) {
            return $visited; // Evitar loops infinitos
        }
        
        $visited[] = $current_question;
        
        if (!isset($all_dependencies[$current_question])) {
            return $visited;
        }
        
        $longest_chain = $visited;
        
        foreach ($all_dependencies[$current_question] as $dependency) {
            $chain = $this->trace_dependency_chain($dependency, $all_dependencies, $visited);
            if (count($chain) > count($longest_chain)) {
                $longest_chain = $chain;
            }
        }
        
        return $longest_chain;
    }

    /**
     * Limpar lógica condicional inválida
     * 
     * @param int $questionnaire_id ID do questionário
     * @return int Número de perguntas corrigidas
     */
    public function clean_invalid_conditional_logic($questionnaire_id) {
        $cleaned_count = 0;
        
        try {
            $this->db->trans_start();
            
            $questions = $this->get_by_questionnaire($questionnaire_id);
            
            // Criar mapa de IDs válidos
            $valid_question_ids = array_column($questions, 'id');
            
            foreach ($questions as $question) {
                if (!empty($question->conditional_logic)) {
                    $logic = json_decode($question->conditional_logic, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // JSON inválido - limpar
                        $this->db->where('id', $question->id);
                        $this->db->update('questions', ['conditional_logic' => null]);
                        $cleaned_count++;
                    } else {
                        // Validar e limpar lógica inválida
                        $cleaned_logic = $this->clean_logic_structure_with_ids($logic, $valid_question_ids);
                        
                        if ($cleaned_logic !== $logic) {
                            $new_logic_json = !empty($cleaned_logic) ? json_encode($cleaned_logic) : null;
                            $this->db->where('id', $question->id);
                            $this->db->update('questions', ['conditional_logic' => $new_logic_json]);
                            $cleaned_count++;
                        }
                    }
                }
            }
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                return 0;
            }
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Erro ao limpar lógica condicional: ' . $e->getMessage());
            return 0;
        }
        
        return $cleaned_count;
    }

    private function clean_logic_structure_with_ids($logic, $valid_question_ids) {
        $cleaned = [];
        
        // Limpar regras de visibilidade
        if (isset($logic['visibility']) && is_array($logic['visibility'])) {
            $cleaned_visibility = $this->clean_rule_structure_with_ids($logic['visibility'], $valid_question_ids);
            if (!empty($cleaned_visibility)) {
                $cleaned['visibility'] = $cleaned_visibility;
            }
        }
        
        // Limpar regras de obrigatoriedade
        if (isset($logic['required']) && is_array($logic['required'])) {
            $cleaned_required = $this->clean_rule_structure_with_ids($logic['required'], $valid_question_ids);
            if (!empty($cleaned_required)) {
                $cleaned['required'] = $cleaned_required;
            }
        }
        
        return $cleaned;
    }

    private function clean_rule_structure_with_ids($rule, $valid_question_ids) {
        if (!isset($rule['conditions']) || !is_array($rule['conditions'])) {
            return [];
        }
        
        $valid_conditions = [];
        
        foreach ($rule['conditions'] as $condition) {
            if (is_array($condition) && 
                isset($condition['question']) && 
                in_array($condition['question'], $valid_question_ids) &&
                isset($condition['operator']) && !empty($condition['operator'])) {
                $valid_conditions[] = $condition;
            }
        }
        
        if (empty($valid_conditions)) {
            return [];
        }
        
        return [
            'operator' => isset($rule['operator']) && in_array($rule['operator'], ['AND', 'OR']) ? $rule['operator'] : 'AND',
            'conditions' => $valid_conditions
        ];
    }

    public function update_logic_question_ids($questionnaire_id, $id_mapping) {
        try {
            $this->db->trans_start();
            
            $questions = $this->get_by_questionnaire_with_logic($questionnaire_id);
            
            foreach ($questions as $question) {
                if (!empty($question->conditional_logic_decoded)) {
                    $logic = $question->conditional_logic_decoded;
                    $updated = false;
                    
                    foreach (['visibility', 'required'] as $rule_type) {
                        if (isset($logic[$rule_type]['conditions'])) {
                            foreach ($logic[$rule_type]['conditions'] as &$condition) {
                                if (isset($condition['question'])) {
                                    $old_id = $condition['question'];
                                    if (isset($id_mapping[$old_id])) {
                                        $condition['question'] = $id_mapping[$old_id];
                                        $updated = true;
                                    }
                                }
                            }
                        }
                    }
                    
                    if ($updated) {
                        $this->db->where('id', $question->id);
                        $this->db->update('questions', ['conditional_logic' => json_encode($logic)]);
                    }
                }
            }
            
            $this->db->trans_complete();
            return $this->db->trans_status();
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Erro ao atualizar IDs de lógica condicional: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Limpar estrutura de lógica inválida
     * 
     * @param array $logic Lógica a ser limpa
     * @return array Lógica limpa
     */
    private function clean_logic_structure($logic) {
        $cleaned = [];
        
        // Limpar regras de visibilidade
        if (isset($logic['visibility']) && is_array($logic['visibility'])) {
            $cleaned_visibility = $this->clean_rule_structure($logic['visibility']);
            if (!empty($cleaned_visibility)) {
                $cleaned['visibility'] = $cleaned_visibility;
            }
        }
        
        // Limpar regras de obrigatoriedade
        if (isset($logic['required']) && is_array($logic['required'])) {
            $cleaned_required = $this->clean_rule_structure($logic['required']);
            if (!empty($cleaned_required)) {
                $cleaned['required'] = $cleaned_required;
            }
        }
        
        return $cleaned;
    }

    /**
     * Limpar estrutura de regra inválida
     * 
     * @param array $rule Regra a ser limpa
     * @return array Regra limpa
     */
    private function clean_rule_structure($rule) {
        if (!isset($rule['conditions']) || !is_array($rule['conditions'])) {
            return [];
        }
        
        $valid_conditions = [];
        
        foreach ($rule['conditions'] as $condition) {
            if (is_array($condition) && 
                isset($condition['question']) && is_numeric($condition['question']) &&
                isset($condition['operator']) && !empty($condition['operator'])) {
                $valid_conditions[] = $condition;
            }
        }
        
        if (empty($valid_conditions)) {
            return [];
        }
        
        return [
            'operator' => isset($rule['operator']) && in_array($rule['operator'], ['AND', 'OR']) ? $rule['operator'] : 'AND',
            'conditions' => $valid_conditions
        ];
    }

    /**
     * Migrar lógica condicional (para atualizações futuras)
     * 
     * @param int $questionnaire_id ID do questionário específico
     * @return int Número de perguntas migradas
     */
    public function migrate_conditional_logic($questionnaire_id = null) {
        $migrated_count = 0;
        
        try {
            $this->db->trans_start();
            
            // Query base
            $this->db->select('id, conditional_logic');
            $this->db->from('questions');
            
            if ($questionnaire_id) {
                $this->db->where('questionnaire_id', $questionnaire_id);
            }
            
            // Apenas perguntas com lógica condicional
            $this->db->where('conditional_logic IS NOT NULL');
            $this->db->where('conditional_logic !=', '');
            
            $questions = $this->db->get()->result();
            
            foreach ($questions as $question) {
                $logic = json_decode($question->conditional_logic, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Aplicar migrações necessárias
                    $migrated_logic = $this->apply_logic_migrations($logic);
                    
                    if ($migrated_logic !== $logic) {
                        $this->db->where('id', $question->id);
                        $this->db->update('questions', ['conditional_logic' => json_encode($migrated_logic)]);
                        $migrated_count++;
                    }
                }
            }
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                return 0;
            }
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Erro na migração de lógica condicional: ' . $e->getMessage());
            return 0;
        }
        
        return $migrated_count;
    }

    /**
     * Aplicar migrações de lógica condicional
     * 
     * @param array $logic Lógica atual
     * @return array Lógica migrada
     */
    private function apply_logic_migrations($logic) {
        // Exemplo de migração: converter operadores antigos
        $operator_migrations = [
            'equal' => 'equals',
            'not_equal' => 'not_equals',
            'contain' => 'contains',
            'not_contain' => 'not_contains'
        ];
        
        foreach (['visibility', 'required'] as $rule_type) {
            if (isset($logic[$rule_type]['conditions'])) {
                foreach ($logic[$rule_type]['conditions'] as &$condition) {
                    if (isset($condition['operator']) && array_key_exists($condition['operator'], $operator_migrations)) {
                        $condition['operator'] = $operator_migrations[$condition['operator']];
                    }
                }
            }
        }
        
        return $logic;
    }

    /**
     * Exportar análise detalhada da lógica condicional
     * 
     * @param int $questionnaire_id ID do questionário
     * @return array Análise detalhada
     */
    public function export_conditional_logic_analysis($questionnaire_id) {
        $analysis = [
            'questionnaire_id' => $questionnaire_id,
            'generated_at' => date('Y-m-d H:i:s'),
            'summary' => [],
            'questions' => [],
            'dependencies' => [],
            'issues' => []
        ];
        
        try {
            $questions = $this->get_by_questionnaire_with_logic($questionnaire_id);
            $analysis['summary'] = $this->get_conditional_logic_stats($questionnaire_id);
            
            // Criar mapa de ID para pergunta
            $question_map = [];
            foreach ($questions as $index => $question) {
                $question_map[$question->id] = [
                    'index' => $index,
                    'question' => $question
                ];
            }
            
            foreach ($questions as $index => $question) {
                $question_analysis = [
                    'index' => $index,
                    'id' => $question->id,
                    'text' => $question->question_text,
                    'type' => $question->question_type,
                    'required' => (bool)$question->is_required,
                    'has_logic' => !empty($question->conditional_logic_decoded),
                    'logic' => null,
                    'dependencies' => [],
                    'dependents' => [],
                    'issues' => []
                ];
                
                if (!empty($question->conditional_logic_decoded)) {
                    $logic = $question->conditional_logic_decoded;
                    $question_analysis['logic'] = $logic;
                    
                    // Analisar dependências usando IDs
                    foreach (['visibility', 'required'] as $rule_type) {
                        if (isset($logic[$rule_type]['conditions'])) {
                            foreach ($logic[$rule_type]['conditions'] as $condition) {
                                if (isset($condition['question'])) {
                                    $dep_id = $condition['question'];
                                    $question_analysis['dependencies'][] = [
                                        'question_id' => $dep_id,
                                        'rule_type' => $rule_type,
                                        'operator' => $condition['operator'],
                                        'value' => $condition['value'] ?? null
                                    ];
                                }
                            }
                        }
                    }
                    
                    // Validar integridade
                    $validation = $this->validate_question_logic_integrity_with_ids($question, $index, $question_map);
                    $question_analysis['issues'] = array_merge($validation['errors'], $validation['warnings']);
                }
                
                $analysis['questions'][] = $question_analysis;
            }
            
            // Gerar mapa de dependências usando IDs
            $analysis['dependencies'] = $this->generate_dependency_map_with_ids($analysis['questions']);
            
            // Coletar todos os issues
            foreach ($analysis['questions'] as $q) {
                $analysis['issues'] = array_merge($analysis['issues'], $q['issues']);
            }
            
        } catch (Exception $e) {
            $analysis['error'] = 'Erro na análise: ' . $e->getMessage();
            log_message('error', 'Erro na análise de lógica condicional: ' . $e->getMessage());
        }
        
        return $analysis;
    }

    private function generate_dependency_map_with_ids($questions_analysis) {
        $map = [];
        
        foreach ($questions_analysis as $question) {
            if (!empty($question['dependencies'])) {
                foreach ($question['dependencies'] as $dep) {
                    $target_id = $dep['question_id'];
                    
                    if (!isset($map[$target_id])) {
                        $map[$target_id] = [
                            'question_id' => $target_id,
                            'dependents' => []
                        ];
                    }
                    
                    $map[$target_id]['dependents'][] = [
                        'question_id' => $question['id'],
                        'rule_type' => $dep['rule_type'],
                        'operator' => $dep['operator'],
                        'value' => $dep['value']
                    ];
                }
            }
        }
        
        return array_values($map);
    }

    /**
     * Gerar mapa de dependências
     * 
     * @param array $questions_analysis Análise das perguntas
     * @return array Mapa de dependências
     */
    private function generate_dependency_map($questions_analysis) {
        $map = [];
        
        foreach ($questions_analysis as $question) {
            if (!empty($question['dependencies'])) {
                foreach ($question['dependencies'] as $dep) {
                    $target_index = $dep['question_index'];
                    
                    if (!isset($map[$target_index])) {
                        $map[$target_index] = [
                            'question_index' => $target_index,
                            'dependents' => []
                        ];
                    }
                    
                    $map[$target_index]['dependents'][] = [
                        'question_index' => $question['index'],
                        'rule_type' => $dep['rule_type'],
                        'operator' => $dep['operator'],
                        'value' => $dep['value']
                    ];
                }
            }
        }
        
        return array_values($map);
    }

    /**
     * Contar perguntas com lógica condicional
     * 
     * @param int $questionnaire_id ID do questionário
     * @return int Número de perguntas com lógica
     */
    public function count_questions_with_logic($questionnaire_id) {
        $this->db->where('questionnaire_id', $questionnaire_id);
        $this->db->where('conditional_logic IS NOT NULL');
        $this->db->where('conditional_logic !=', '');
        
        return $this->db->count_all_results('questions');
    }

    /**
     * Obter perguntas que dependem de uma pergunta específica
     * 
     * @param int $questionnaire_id ID do questionário
     * @param int $target_question_index Índice da pergunta alvo
     * @return array Perguntas dependentes
     */
    public function get_dependent_questions($questionnaire_id, $target_question_id) {
        $dependent_questions = [];
        
        try {
            $questions = $this->get_by_questionnaire_with_logic($questionnaire_id);
            
            foreach ($questions as $index => $question) {
                if (!empty($question->conditional_logic_decoded)) {
                    $logic = $question->conditional_logic_decoded;
                    $has_dependency = false;
                    
                    foreach (['visibility', 'required'] as $rule_type) {
                        if (isset($logic[$rule_type]['conditions'])) {
                            foreach ($logic[$rule_type]['conditions'] as $condition) {
                                if (isset($condition['question']) && $condition['question'] == $target_question_id) {
                                    $has_dependency = true;
                                    break 2;
                                }
                            }
                        }
                    }
                    
                    if ($has_dependency) {
                        $dependent_questions[] = [
                            'id' => $question->id,
                            'index' => $index,
                            'text' => $question->question_text,
                            'type' => $question->question_type
                        ];
                    }
                }
            }
            
        } catch (Exception $e) {
            log_message('error', 'Erro ao buscar perguntas dependentes: ' . $e->getMessage());
        }
        
        return $dependent_questions;
    }

    /**
     * Atualizar índices de perguntas na lógica condicional após reordenação
     * 
     * @param int $questionnaire_id ID do questionário
     * @param array $index_mapping Mapeamento de índices antigos para novos
     * @return bool Sucesso da operação
     */
    public function update_logic_question_indices($questionnaire_id, $index_mapping) {
        try {
            $this->db->trans_start();
            
            $questions = $this->get_by_questionnaire_with_logic($questionnaire_id);
            
            foreach ($questions as $question) {
                if (!empty($question->conditional_logic_decoded)) {
                    $logic = $question->conditional_logic_decoded;
                    $updated = false;
                    
                    foreach (['visibility', 'required'] as $rule_type) {
                        if (isset($logic[$rule_type]['conditions'])) {
                            foreach ($logic[$rule_type]['conditions'] as &$condition) {
                                if (isset($condition['question'])) {
                                    $old_index = intval($condition['question']);
                                    if (isset($index_mapping[$old_index])) {
                                        $condition['question'] = $index_mapping[$old_index];
                                        $updated = true;
                                    }
                                }
                            }
                        }
                    }
                    
                    if ($updated) {
                        $this->db->where('id', $question->id);
                        $this->db->update('questions', ['conditional_logic' => json_encode($logic)]);
                    }
                }
            }
            
            $this->db->trans_complete();
            return $this->db->trans_status();
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Erro ao atualizar índices de lógica condicional: ' . $e->getMessage());
            return false;
        }
    }
}