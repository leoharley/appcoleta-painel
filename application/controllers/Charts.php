<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Charts extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->database();
        $this->check_auth();
    }

    public function index() {
        $data['title']          = 'Gráficos & Relatórios';
        $data['questionnaires'] = $this->db
            ->select('q.id, q.title, q.description, p.name AS project_name, q.created_at,
                      (SELECT COUNT(*) FROM form_responses fr WHERE fr.questionnaire_id = q.id) AS responses_count')
            ->from('questionnaires q')
            ->join('projects p', 'p.id = q.project_id', 'left')
            ->order_by('q.created_at', 'DESC')
            ->get()->result();

        $this->load->view('admin/header', $data);
        $this->load->view('admin/charts/index', $data);
        $this->load->view('admin/footer');
    }

    public function report($questionnaire_id) {
        $questionnaire_id = (int)$questionnaire_id;
        $q = $this->db->get_where('questionnaires', array('id' => $questionnaire_id))->row();
        if (!$q) show_404();

        $questions = $this->db
            ->where('questionnaire_id', $questionnaire_id)
            ->order_by('order_index', 'ASC')
            ->order_by('id', 'ASC')
            ->get('questions')->result();

        $filters = array(
            'from'       => $this->input->get('from'),
            'to'         => $this->input->get('to'),
            'applied_by' => (int)$this->input->get('applied_by') ?: null,
        );

        // Métricas gerais (aplica os filtros)
        $apply_filters = function($db) use ($questionnaire_id, $filters) {
            $db->where('questionnaire_id', $questionnaire_id);
            if (!empty($filters['from']))       $db->where('completed_at >=', $filters['from'] . ' 00:00:00');
            if (!empty($filters['to']))         $db->where('completed_at <=', $filters['to']   . ' 23:59:59');
            if (!empty($filters['applied_by'])) $db->where('applied_by', $filters['applied_by']);
        };

        $apply_filters($this->db);
        $total_responses = (int)$this->db->count_all_results('form_responses');

        // Período coberto
        $this->db->select('MIN(completed_at) AS first_at, MAX(completed_at) AS last_at,
                           COUNT(DISTINCT applied_by) AS applicators_count', false);
        $apply_filters($this->db);
        $range = $this->db->get('form_responses')->row();

        // Lista de aplicadores que participaram (para filtro)
        $this->db->select('u.id, u.full_name, COUNT(fr.id) AS total')
                 ->from('form_responses fr')
                 ->join('users u', 'u.id = fr.applied_by', 'left')
                 ->where('fr.questionnaire_id', $questionnaire_id)
                 ->group_by('u.id, u.full_name')
                 ->order_by('total', 'DESC');
        $applicators = $this->db->get()->result();

        $data = array(
            'title'           => 'Relatório — ' . $q->title,
            'questionnaire'   => $q,
            'questions'       => $questions,
            'filters'         => $filters,
            'total_responses' => $total_responses,
            'range'           => $range,
            'applicators'     => $applicators,
            'project'         => $q->project_id ? $this->db->get_where('projects', array('id' => $q->project_id))->row() : null,
        );

        $this->load->view('admin/charts/report', $data);
    }

    /**
     * AJAX: devolve os dados agregados de uma pergunta no formato Chart.js.
     * @return JSON {success, type, chart_type, labels, values, total, raw?}
     */
    public function chart_data($questionnaire_id, $question_id) {
        $this->output->set_content_type('application/json');

        $questionnaire_id = (int)$questionnaire_id;
        $question_id      = (int)$question_id;
        $question = $this->db->get_where('questions', array('id' => $question_id))->row();
        if (!$question || (int)$question->questionnaire_id !== $questionnaire_id) {
            return $this->output->set_output(json_encode(array('success' => false, 'message' => 'Pergunta não encontrada.')));
        }

        $filters = array(
            'from'       => $this->input->get('from'),
            'to'         => $this->input->get('to'),
            'applied_by' => (int)$this->input->get('applied_by') ?: null,
        );

        $base = function($db) use ($questionnaire_id, $filters, $question_id) {
            $db->from('question_responses qr')
               ->join('form_responses fr', 'fr.id = qr.form_response_id', 'inner')
               ->where('fr.questionnaire_id', $questionnaire_id)
               ->where('qr.question_id', $question_id);
            if (!empty($filters['from']))       $db->where('fr.completed_at >=', $filters['from'] . ' 00:00:00');
            if (!empty($filters['to']))         $db->where('fr.completed_at <=', $filters['to']   . ' 23:59:59');
            if (!empty($filters['applied_by'])) $db->where('fr.applied_by', $filters['applied_by']);
        };

        $type = $question->question_type;
        $result = array('success' => true, 'question_type' => $type, 'question_text' => $question->question_text);

        if (in_array($type, array('radio', 'select', 'checkbox'))) {
            // Carrega opções oficiais (mantém ordem e exibe zerados quando ninguém marcou)
            $options = $this->db->where('question_id', $question_id)
                                ->order_by('id', 'ASC')
                                ->get('question_options')->result();

            $counts = array();
            foreach ($options as $o) {
                $counts[$o->option_text] = 0;
            }

            // Lê as respostas e desserializa selected_options
            $this->db->select('qr.selected_options, qr.response_text');
            $base($this->db);
            $rows = $this->db->get()->result();

            $total = 0;
            foreach ($rows as $r) {
                $opts = array();
                if (!empty($r->selected_options)) {
                    $decoded = json_decode($r->selected_options, true);
                    if (is_array($decoded)) $opts = $decoded;
                    elseif (is_string($decoded) && $decoded !== '') $opts = array($decoded);
                } elseif (!empty($r->response_text)) {
                    $opts = array($r->response_text);
                }
                if (empty($opts)) continue;

                foreach ($opts as $picked) {
                    $picked = (string)$picked;
                    // Tenta casar pelo option_value primeiro, senão pelo texto
                    $matched_label = null;
                    foreach ($options as $o) {
                        if ((string)$o->option_value === $picked || (string)$o->option_text === $picked) {
                            $matched_label = $o->option_text;
                            break;
                        }
                    }
                    if ($matched_label === null) $matched_label = $picked;
                    if (!isset($counts[$matched_label])) $counts[$matched_label] = 0;
                    $counts[$matched_label]++;
                    $total++;
                }
            }

            // Ordenar por quantidade desc para barras
            arsort($counts);
            $result['chart_type'] = (count($counts) <= 5 && $type !== 'checkbox') ? 'doughnut' : 'bar';
            $result['labels'] = array_keys($counts);
            $result['values'] = array_values($counts);
            $result['total']  = $total;

        } elseif ($type === 'number') {
            $this->db->select('qr.response_number');
            $base($this->db);
            $this->db->where('qr.response_number IS NOT NULL', null, false);
            $nums = array_map(function($r) { return (float)$r->response_number; }, $this->db->get()->result());
            if (empty($nums)) {
                $result['chart_type'] = 'bar';
                $result['labels'] = array();
                $result['values'] = array();
                $result['total']  = 0;
            } else {
                $min = min($nums); $max = max($nums);
                $bucket_count = 8;
                $range = max(0.0001, $max - $min);
                $step  = $range / $bucket_count;
                $buckets = array_fill(0, $bucket_count, 0);
                foreach ($nums as $n) {
                    $idx = min($bucket_count - 1, (int) floor(($n - $min) / $step));
                    $buckets[$idx]++;
                }
                $labels = array();
                for ($i = 0; $i < $bucket_count; $i++) {
                    $lo = round($min + $i * $step, 1);
                    $hi = round($min + ($i + 1) * $step, 1);
                    $labels[] = $lo . '–' . $hi;
                }
                $result['chart_type'] = 'bar';
                $result['labels'] = $labels;
                $result['values'] = $buckets;
                $result['total']  = count($nums);
                $result['stats']  = array(
                    'min' => $min, 'max' => $max,
                    'avg' => round(array_sum($nums) / count($nums), 2)
                );
            }
        } elseif ($type === 'date' || $type === 'datetime') {
            $col = $type === 'date' ? 'qr.response_date' : 'qr.response_datetime';
            $this->db->select("DATE($col) AS d, COUNT(*) AS c", false);
            $base($this->db);
            $this->db->where("$col IS NOT NULL", null, false);
            $this->db->group_by('d')->order_by('d', 'ASC');
            $rows = $this->db->get()->result();
            $result['chart_type'] = 'bar';
            $result['labels'] = array_map(function($r) { return $r->d; }, $rows);
            $result['values'] = array_map(function($r) { return (int)$r->c; }, $rows);
            $result['total']  = array_sum($result['values']);
        } else {
            // text/textarea/email — top N respostas mais frequentes
            $this->db->select('qr.response_text');
            $base($this->db);
            $this->db->where("qr.response_text IS NOT NULL AND qr.response_text != ''", null, false);
            $rows = $this->db->get()->result();
            $counts = array();
            foreach ($rows as $r) {
                $key = trim(mb_strtolower((string)$r->response_text));
                if ($key === '') continue;
                if (!isset($counts[$key])) $counts[$key] = array('label' => trim((string)$r->response_text), 'n' => 0);
                $counts[$key]['n']++;
            }
            uasort($counts, function($a, $b) { return $b['n'] - $a['n']; });
            $counts = array_values(array_slice($counts, 0, 15)); // reindexa para virar JSON array
            $result['chart_type'] = 'bar';
            $result['labels'] = array_map(function($c) { return $c['label']; }, $counts);
            $result['values'] = array_map(function($c) { return $c['n']; }, $counts);
            $result['total']  = count($rows);
        }

        return $this->output->set_output(json_encode($result));
    }

    private function check_auth() {
        if (!$this->session->userdata('admin_logged_in')) redirect('auth/login');
    }
}
