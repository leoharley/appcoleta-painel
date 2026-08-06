<?php
// application/controllers/api/Auth.php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    /**
     * Validade do token em segundos (28 dias).
     *
     * O app de coleta funciona muito offline e só sincroniza esporadicamente,
     * por isso um TTL curto (era 24h) fazia com que o aplicador só descobrisse
     * que a sessão expirou quando ia subir uma coleta — perdendo o dia se a
     * conexão só aparecesse no fim do expediente. 28 dias + renovação
     * deslizante em /auth/verify garantem que quem abre o app pelo menos
     * uma vez ao mês nunca é expulso.
     */
    const TOKEN_TTL_SECONDS = 28 * 24 * 60 * 60;

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->library('encryption');
        $this->load->helper('cors');
        cors_setup();
        header('Content-Type: application/json');
    }

    public function login() {
        if ($this->input->method() !== 'post') {
            $this->output->set_status_header(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $json = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($json['username']) || !isset($json['password'])) {
            $this->output->set_status_header(400);
            echo json_encode(['success' => false, 'message' => 'Username and password required']);
            return;
        }

        $username = $json['username'];
        $password = $json['password'];

        $user = $this->User_model->authenticate($username, $password);

        if ($user) {
            // Gerar token JWT ou session token
            $token = $this->generate_token($user->id);
            
            // Log da atividade
            $this->log_activity($user->id, 'login', 'auth', null, [
                'ip_address' => $this->input->ip_address(),
                'user_agent' => $this->input->user_agent()
            ]);

            echo json_encode([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'full_name' => $user->full_name,
                    'role' => $user->role,
                    'is_active' => $user->is_active
                ]
            ]);
        } else {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
    }

    public function verify() {
        $headers = $this->input->request_headers();
        $token = null;

        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            if (strpos($auth_header, 'Bearer ') === 0) {
                $token = substr($auth_header, 7);
            }
        }

        if (!$token) {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Token required']);
            return;
        }

        $user_id = $this->verify_token($token);

        if ($user_id) {
            $user = $this->User_model->get_by_id($user_id);

            if ($user && $user->is_active) {
                // Renovação deslizante: emite um NOVO token com prazo cheio
                // (28 dias a partir de agora) para o app substituir o atual.
                // O token antigo continua válido até o vencimento original,
                // mas o app grava o novo por cima e ganha mais um mês.
                $renewed_token = $this->generate_token($user->id);

                echo json_encode([
                    'success' => true,
                    'token'   => $renewed_token,
                    'user'    => [
                        'id'        => $user->id,
                        'username'  => $user->username,
                        'email'     => $user->email,
                        'full_name' => $user->full_name,
                        'role'      => $user->role,
                        'is_active' => $user->is_active
                    ]
                ]);
            } else {
                $this->output->set_status_header(401);
                echo json_encode(['success' => false, 'message' => 'User not found or inactive']);
            }
        } else {
            $this->output->set_status_header(401);
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
        }
    }

    private function generate_token($user_id) {
        $now = time();
        $payload = [
            'user_id'    => $user_id,
            'issued_at'  => $now,
            'expires_at' => $now + self::TOKEN_TTL_SECONDS
        ];

        return base64_encode(json_encode($payload));
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

    private function log_activity($user_id, $action, $resource_type, $resource_id, $details) {
        $this->db->insert('activity_logs', [
            'user_id' => $user_id,
            'action' => $action,
            'resource_type' => $resource_type,
            'resource_id' => $resource_id,
            'details' => json_encode($details),
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}