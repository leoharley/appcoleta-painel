<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Forms extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Response_model');
        $this->load->model('Questionnaire_model');
        $this->load->helper('cors');
        cors_setup(); // seta CORS e trata preflight OPTIONS antes de qualquer coisa
        header('Content-Type: application/json');
    }

    /**
     * Garante resposta JSON mesmo em erro fatal do PHP (memoria estourada,
     * timeout, erro nao capturavel por try/catch). Sem isso o app recebe
     * HTML, nao consegue interpretar, e o diagnostico fica as cegas.
     */
    private function register_fatal_json_handler() {
        register_shutdown_function(function () {
            $err = error_get_last();
            if (!$err || !in_array($err['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
                return;
            }
            if (headers_sent()) return;

            // Erro fatal apos trans_begin: a transacao nao foi commitada,
            // o MySQL desfaz ao encerrar a conexao. Nenhum id e devolvido,
            // entao o app mantem a coleta e reenvia.
            log_message('error', 'forms/submit FATAL — ' . $err['message']
                . ' em ' . $err['file'] . ':' . $err['line']);

            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erro interno ao gravar o formulário. Reenvie.',
                'fatal'   => true
            ]);
        });
    }

    public function submit() {
        $this->register_fatal_json_handler();

        if ($this->input->method() !== 'post') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        // Verificar autenticação
        $user_id = $this->verify_auth();
        if (!$user_id) {
            return;
        }

        $raw = file_get_contents('php://input');

        // Payload maior que post_max_size: o PHP descarta o corpo e entrega
        // vazio, mas o Content-Length informado vem grande. Sem distinguir
        // esse caso, o app recebia "Invalid JSON" e o motivo real ficava
        // invisivel — uma das causas possiveis de coleta perdida.
        $declared_len = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
        if ($raw === '' && $declared_len > 0) {
            log_message('error', 'forms/submit — corpo vazio com Content-Length='
                . $declared_len . '. Provavel post_max_size ('
                . ini_get('post_max_size') . ') excedido.');
            $this->output->set_status_header(413);
            echo json_encode([
                'success' => false,
                'message' => 'Payload excede o limite do servidor (post_max_size='
                    . ini_get('post_max_size') . ').',
                'limit_exceeded' => true
            ]);
            return;
        }

        $json = json_decode($raw, true);

        if (!$json) {
            log_message('error', 'forms/submit — JSON invalido. bytes=' . strlen($raw)
                . ' json_error=' . json_last_error_msg());
            $this->output->set_status_header(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid JSON data',
                'json_error' => json_last_error_msg()
            ]);
            return;
        }

        // Validar dados obrigatórios
        $required_fields = ['questionnaire_id', 'applied_by', 'responses'];
        foreach ($required_fields as $field) {
            if (!isset($json[$field])) {
                $this->output->set_status_header(400);
                echo json_encode(['success' => false, 'message' => "Field '{$field}' is required"]);
                return;
            }
        }

        try {
            // Transacao MANUAL (trans_begin/commit/rollback) em vez de
            // trans_start/trans_complete: com o modo automatico, depois do
            // trans_complete a transacao ja foi encerrada e o
            // trans_rollback do catch nao tem efeito. O app trata o id como
            // prova definitiva de gravacao, entao o commit precisa ser
            // explicito e verificavel.
            $this->db->trans_begin();

            // Normalização da localização — preserva NULL quando ausente, vazio ou inválido.
            // Nunca converter null/"" para 0 (0,0 é um ponto no oceano).
            $lat_in = $json['latitude'] ?? null;
            $lng_in = $json['longitude'] ?? null;
            $latitude  = (is_numeric($lat_in) && $lat_in >= -90  && $lat_in <= 90)  ? (float)$lat_in : null;
            $longitude = (is_numeric($lng_in) && $lng_in >= -180 && $lng_in <= 180) ? (float)$lng_in : null;
            $location_name_in = $json['location_name'] ?? null;
            $location_name = (is_string($location_name_in) && trim($location_name_in) !== '') ? $location_name_in : null;

            // Idempotência: se o app enviar um `client_id` (uuid gerado localmente),
            // qualquer reenvio do mesmo formulário será deduplicado.
            $client_id_in = $json['client_id'] ?? null;
            $client_id = (is_string($client_id_in) && $client_id_in !== '') ? substr($client_id_in, 0, 64) : null;

            // Se o app enviar um `id` (server id de edição) e ele existir, é UPDATE.
            // Caso contrário, INSERT — nunca reutilizar id do cliente para PK.
            $server_id_in = isset($json['id']) ? (int)$json['id'] : 0;
            $is_edit = false;
            if ($server_id_in > 0) {
                $existing_row = $this->db->get_where('form_responses', array('id' => $server_id_in))->row();
                if ($existing_row) {
                    $is_edit = true;
                }
            }

            // Se não for edição, checar dedup por client_id
            if (!$is_edit && $client_id) {
                $dup = $this->db->select('id')
                    ->get_where('form_responses', array('client_id' => $client_id))
                    ->row();
                if ($dup) {
                    $this->db->trans_commit();   // nada a gravar; so encerra
                    $this->respond_success((int)$dup->id, false, true,
                        'Form already synced (deduplicated by client_id)');
                    return;
                }
            }

            $form_data = [
                'questionnaire_id' => $json['questionnaire_id'],
                'respondent_name'  => $json['respondent_name'] ?? null,
                'respondent_email' => $json['respondent_email'] ?? null,
                'applied_by'       => $json['applied_by'],
                'latitude'         => $latitude,
                'longitude'        => $longitude,
                'location_name'    => $location_name,
                'photo_path'       => $json['photo_path'] ?? null,
                'photo_path_2'     => $json['photo_path_2'] ?? null,
                'audio_path'       => $json['audio_path'] ?? null,
                'consent_given'    => isset($json['consent_given']) ? (bool)$json['consent_given'] : false,
                'sync_status'      => 'synced',
                'started_at'       => isset($json['started_at']) ? date('Y-m-d H:i:s', strtotime($json['started_at'])) : null,
                'completed_at'     => isset($json['completed_at']) ? date('Y-m-d H:i:s', strtotime($json['completed_at'])) : date('Y-m-d H:i:s')
            ];
            if ($client_id !== null) {
                $form_data['client_id'] = $client_id;
            }

            if ($is_edit) {
                // Atualiza cabeçalho + apaga respostas antigas e regrava.
                $this->db->where('id', $server_id_in)->update('form_responses', $form_data);
                $form_response_id = $server_id_in;
                $this->db->where('form_response_id', $form_response_id)->delete('question_responses');
            } else {
                // db_debug OFF no INSERT: uma violacao do UNIQUE de client_id
                // (dois envios simultaneos do mesmo formulario) e um caso
                // ESPERADO, nao um erro — precisa ser tratado, nao abortar.
                $prev_debug = $this->db->db_debug;
                $this->db->db_debug = FALSE;
                $form_response_id = $this->Response_model->create($form_data);
                $this->db->db_debug = $prev_debug;

                if (!$form_response_id) {
                    // Corrida no UNIQUE: o outro envio ja gravou. Recupera o
                    // id existente e responde sucesso — sem isso o app
                    // receberia 500 e reenviaria indefinidamente.
                    if ($client_id) {
                        $this->db->trans_rollback();
                        $existing = $this->db->select('id')
                            ->get_where('form_responses', array('client_id' => $client_id))
                            ->row();
                        if ($existing) {
                            $this->respond_success((int)$existing->id, false, true,
                                'Form already synced (concurrent submit)');
                            return;
                        }
                        // Nao foi duplicidade: segue para o erro normal.
                        throw new Exception('Failed to create form response');
                    }
                    throw new Exception('Failed to create form response');
                }
            }

            // Salvar respostas individuais
            $saved_answers = 0;
            foreach ($json['responses'] as $response) {
                if (!isset($response['question_id'])) {
                    continue;
                }

                $answer_data = [
                    'form_response_id'  => $form_response_id,
                    'question_id'       => $response['question_id'],
                    'response_text'     => $response['response_text'] ?? null,
                    'response_number'   => $response['response_number'] ?? null,
                    'response_date'     => isset($response['response_date']) ? date('Y-m-d', strtotime($response['response_date'])) : null,
                    'response_datetime' => isset($response['response_datetime']) ? date('Y-m-d H:i:s', strtotime($response['response_datetime'])) : null,
                    'selected_options'  => isset($response['selected_options']) ? json_encode($response['selected_options']) : null
                ];

                // Verifica CADA item: antes o retorno era ignorado e um item
                // rejeitado (ex.: question_id inexistente) passava despercebido,
                // gravando a coleta incompleta.
                if (!$this->Response_model->create_answer($answer_data)) {
                    throw new Exception('Failed to save answer for question '
                        . $response['question_id']);
                }
                $saved_answers++;
            }

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }

            // Commit EXPLICITO. So depois dele o id pode ser devolvido: o app
            // trata o id como prova definitiva de gravacao e para de reenviar.
            $this->db->trans_commit();

            log_message('info', 'forms/submit OK — id=' . $form_response_id
                . ' itens=' . $saved_answers
                . ' client_id=' . ($client_id ?: '-')
                . ' edit=' . ($is_edit ? '1' : '0'));

            $this->respond_success((int)$form_response_id, $is_edit, false,
                $is_edit ? 'Form updated successfully' : 'Form submitted successfully');

        } catch (Exception $e) {
            // Rollback real: com trans_begin a transacao ainda esta aberta aqui.
            $this->db->trans_rollback();

            log_message('error', 'forms/submit FALHOU — ' . $e->getMessage()
                . ' | client_id=' . (isset($client_id) ? ($client_id ?: '-') : '-')
                . ' | questionnaire_id=' . ($json['questionnaire_id'] ?? '-')
                . ' | applied_by=' . ($json['applied_by'] ?? '-'));

            $this->output->set_status_header(500);
            // NUNCA devolver id aqui: o app mantem a resposta no aparelho.
            echo json_encode([
                'success' => false,
                'message' => 'Failed to submit form: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Resposta de sucesso do submit.
     *
     * Devolve o id em VARIOS formatos de proposito: `form_response_id` na
     * raiz (o que o app em producao ja le hoje), mais `id` e `data.id`.
     * Assim nenhuma versao do app em campo deixa de confirmar a gravacao.
     *
     * So deve ser chamada APOS o commit.
     */
    private function respond_success($id, $is_edit, $duplicated, $message) {
        $this->output->set_status_header(200);
        echo json_encode([
            'success'          => true,
            'id'               => (int)$id,
            'form_response_id' => (int)$id,
            'data'             => ['id' => (int)$id],
            'edited'           => (bool)$is_edit,
            'duplicated'       => (bool)$duplicated,
            'message'          => $message
        ]);
    }

    /**
     * GET /api/forms/diag — limites do servidor que podem derrubar envios.
     * Exige token (mesma autenticacao do submit). Nao expoe dado sensivel:
     * apenas configuracao de limites e presenca de indice.
     */
    public function diag() {
        $user_id = $this->verify_auth();
        if (!$user_id) return;

        $has_client_id = FALSE;
        $has_unique = FALSE;
        try {
            $cols = $this->db->query("SHOW COLUMNS FROM form_responses LIKE 'client_id'")->result();
            $has_client_id = !empty($cols);
            $idx = $this->db->query("SHOW INDEX FROM form_responses WHERE Key_name = 'uq_form_responses_client_id'")->result();
            $has_unique = !empty($idx);
        } catch (Exception $e) { /* ignora */ }

        echo json_encode([
            'success' => true,
            'php' => [
                'post_max_size'       => ini_get('post_max_size'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'max_input_vars'      => ini_get('max_input_vars'),
                'memory_limit'        => ini_get('memory_limit'),
                'max_execution_time'  => ini_get('max_execution_time'),
                'version'             => PHP_VERSION,
            ],
            'idempotencia' => [
                'coluna_client_id' => $has_client_id,
                'unique_client_id' => $has_unique,
            ],
            'log_path' => APPPATH . 'logs/',
        ], JSON_PRETTY_PRINT);
    }

    private function verify_auth() {
        $headers = $this->input->request_headers();
        
        if (!isset($headers['Authorization'])) {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Authorization required']);
            return false;
        }

        $auth_header = $headers['Authorization'];
        if (strpos($auth_header, 'Bearer ') !== 0) {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Invalid authorization format']);
            return false;
        }

        $token = substr($auth_header, 7);
        $user_id = $this->verify_token($token);
        
        if (!$user_id) {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            return false;
        }

        return $user_id;
    }

    private function verify_token($token) {
        try {
            $payload = json_decode(base64_decode($token), true);
            
            if (!$payload || !isset($payload['user_id']) || !isset($payload['expires_at'])) {
                return false;
            }
            
            if (time() > $payload['expires_at']) {
                return false;
            }
            
            return $payload['user_id'];
        } catch (Exception $e) {
            return false;
        }
    }
}