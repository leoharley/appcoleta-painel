<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('format_response_number')) {
    /**
     * Formata um valor numerico de resposta para exibicao.
     *
     * Distingue NUMERO (quantidade/valor, que merece separador de milhar e
     * casas decimais) de IDENTIFICADOR (telefone, CEP, CPF, matricula, que
     * deve sair exatamente como digitado).
     *
     * O criterio e o formato do proprio dado:
     *   - Tem parte decimal      -> valor: 1234.5   -> "1.234,50"
     *   - Inteiro com >= 7 digitos -> identificador: 34984215272 -> "34984215272"
     *   - Inteiro curto          -> quantidade: 30  -> "30"
     *
     * O corte em 7 digitos separa quantidades plausiveis (idade, filhos,
     * renda em milhares) de identificadores brasileiros: telefone com DDD
     * tem 10-11 digitos, CEP 8, CPF 11.
     *
     * @param mixed $value
     * @return string Pronto para exibicao (ja escapado — so digitos e pontuacao).
     */
    function format_response_number($value) {
        if ($value === null || $value === '') return '';

        $raw = trim((string)$value);

        // Guarda o texto original quando ele ja e um inteiro puro: assim
        // preservamos zeros a esquerda (ex.: CEP "01310200").
        $is_plain_integer = (bool)preg_match('/^-?\d+$/', $raw);

        if (!is_numeric($raw)) {
            return htmlspecialchars($raw);
        }

        $num = (float)$raw;

        // Tem casas decimais significativas -> e valor/medida
        if (floor($num) != $num) {
            return number_format($num, 2, ',', '.');
        }

        // Inteiro longo -> identificador: devolve como esta, sem separador
        if ($is_plain_integer && strlen(ltrim($raw, '-')) >= 7) {
            return htmlspecialchars($raw);
        }

        // Inteiro curto -> quantidade, sem casas decimais inuteis
        return number_format($num, 0, ',', '.');
    }
}
