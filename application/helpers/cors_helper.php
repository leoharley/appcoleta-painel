<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper de CORS para os controllers da API.
 *
 * Uso típico (no __construct de um controller de API):
 *
 *     $this->load->helper('cors');
 *     cors_setup();
 *
 * Isso:
 *   - Ecoa o Origin no Access-Control-Allow-Origin (se estiver na whitelist)
 *   - Define os cabeçalhos de métodos e headers permitidos
 *   - Responde 204 imediatamente para requisições OPTIONS (preflight)
 *     SEM exigir autenticação
 */

if (!function_exists('cors_setup')) {

    /**
     * Configura os headers CORS e trata preflight OPTIONS.
     *
     * @param bool $exit_on_options Se true, faz exit() após responder OPTIONS.
     *                              Padrão true (comportamento correto para preflight).
     * @return void
     */
    function cors_setup($exit_on_options = true) {
        $CI =& get_instance();
        $CI->config->load('cors', true);

        $allowed_origins = (array) $CI->config->item('cors_allowed_origins', 'cors');
        $allowed_methods = (string) $CI->config->item('cors_allowed_methods', 'cors');
        $allowed_headers = (string) $CI->config->item('cors_allowed_headers', 'cors');
        $max_age         = (int)    $CI->config->item('cors_max_age', 'cors');

        // Descobre o Origin da requisição
        $origin = '';
        if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN']) {
            $origin = trim($_SERVER['HTTP_ORIGIN']);
        }

        // Ecoa o Origin apenas se estiver na whitelist. Origens fora da lista
        // não recebem o header, e o browser bloqueia a chamada (default seguro).
        if ($origin !== '' && in_array($origin, $allowed_origins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin'); // evita cache errado por proxies/CDNs
        }

        header('Access-Control-Allow-Methods: ' . $allowed_methods);
        header('Access-Control-Allow-Headers: ' . $allowed_headers);
        if ($max_age > 0) {
            header('Access-Control-Max-Age: ' . $max_age);
        }

        // Preflight — responde imediatamente e não passa pelo restante do
        // controller (não exige Authorization, não roda queries).
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : '';
        if ($method === 'OPTIONS') {
            http_response_code(204);
            if ($exit_on_options) exit();
        }
    }
}
