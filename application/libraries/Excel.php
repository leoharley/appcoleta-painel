<?php
// application/libraries/Excel.php
defined('BASEPATH') or exit('No direct script access allowed');

require_once FCPATH . 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class Excel
{

    public function create_export($responses, $format = 'xlsx', $questionnaire_id = null)
    {
        $spreadsheet = new Spreadsheet();

        // ===== PLANILHA 1: COLETAS COM RESPOSTAS =====
        $sheetColetas = $spreadsheet->getActiveSheet();
        $sheetColetas->setTitle('Coletas');

        // Buscar perguntas do questionário para criar colunas dinâmicas
        $CI =& get_instance();
        $CI->load->model('Question_model');

        $questions = array();
        if ($questionnaire_id) {
            $questions = $CI->Question_model->get_by_questionnaire($questionnaire_id);
        }

        // Configurar cabeçalhos fixos
        $fixedHeaders = ['ID', 'QUESTIONÁRIO', 'APLICADOR', 'RESPONDENTE', 'EMAIL', 'LATITUDE', 'LONGITUDE', 'LOCALIZAÇÃO', 'CONSENTIMENTO', 'DATA/HORA CONCLUSÃO', 'STATUS', 'FOTO'];

        // Adicionar cabeçalhos das perguntas
        $questionHeaders = array();
        $questionMap = array(); // Mapeia question_id para índice da coluna
        if (!empty($questions)) {
            foreach ($questions as $index => $question) {
                $questionHeaders[] = mb_strtoupper($question->question_text, 'UTF-8');
                $questionMap[$question->id] = count($fixedHeaders) + $index;
            }
        }

        $allHeaders = array_merge($fixedHeaders, $questionHeaders);

        // Escrever cabeçalhos
        $col = 1;
        foreach ($allHeaders as $header) {
            $sheetColetas->setCellValueByColumnAndRow($col, 1, $header);
            $col++;
        }

        // Estilizar cabeçalho
        $lastColLetter = $this->getColumnLetter($col - 1);
        $sheetColetas->getStyle('A1:' . $lastColLetter . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '23345F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        $statusMap = [
            'synced' => 'Sincronizado',
            'pending' => 'Pendente',
            'error' => 'Erro'
        ];

        // Log para debug
        log_message('info', 'Excel: Recebidos ' . count($responses) . ' registros para exportação com ' . count($questions) . ' perguntas');

        // Preencher dados
        $row = 2;
        if (!empty($responses) && is_array($responses)) {
            foreach ($responses as $response) {
                if (is_object($response)) {
                    // Dados fixos
                    $sheetColetas->setCellValueByColumnAndRow(1, $row, isset($response->id) ? $response->id : '');
                    $sheetColetas->setCellValueByColumnAndRow(2, $row, isset($response->questionnaire) ? $response->questionnaire : 'N/A');
                    $sheetColetas->setCellValueByColumnAndRow(3, $row, isset($response->aplicador) ? $response->aplicador : 'N/A');
                    $sheetColetas->setCellValueByColumnAndRow(4, $row, isset($response->respondent_name) ? $response->respondent_name : 'N/A');
                    $sheetColetas->setCellValueByColumnAndRow(5, $row, isset($response->respondent_email) ? $response->respondent_email : 'N/A');
                    $sheetColetas->setCellValueByColumnAndRow(6, $row, isset($response->latitude) ? $response->latitude : '');
                    $sheetColetas->setCellValueByColumnAndRow(7, $row, isset($response->longitude) ? $response->longitude : '');
                    $sheetColetas->setCellValueByColumnAndRow(8, $row, isset($response->location_name) ? $response->location_name : 'N/A');
                    $sheetColetas->setCellValueByColumnAndRow(9, $row, (isset($response->consent_given) && $response->consent_given) ? 'Sim' : 'Não');
                    $sheetColetas->setCellValueByColumnAndRow(10, $row, isset($response->completed_at) && $response->completed_at ? date('d/m/Y H:i:s', strtotime($response->completed_at)) : 'N/A');
                    $sheetColetas->setCellValueByColumnAndRow(11, $row, isset($response->sync_status) && isset($statusMap[$response->sync_status]) ? $statusMap[$response->sync_status] : 'N/A');
                    $sheetColetas->setCellValueByColumnAndRow(12, $row, !empty($response->photo_path) ? 'Sim' : 'Não');

                    // Respostas das perguntas
                    if (isset($response->answers) && is_array($response->answers)) {
                        foreach ($response->answers as $questionId => $answer) {
                            if (isset($questionMap[$questionId])) {
                                $colIndex = $questionMap[$questionId] + 1; // +1 porque setCellValueByColumnAndRow é 1-indexed
                                $value = isset($answer->value) ? $answer->value : '';
                                $sheetColetas->setCellValueByColumnAndRow($colIndex, $row, is_string($value) ? mb_strtoupper($value, 'UTF-8') : $value);
                            }
                        }
                    }

                    $row++;
                }
            }
            log_message('info', 'Excel: Exportados ' . ($row - 2) . ' registros com respostas');
        } else {
            log_message('warning', 'Excel: Nenhum registro para exportar. responses vazio ou não é array.');
        }

        // Auto-ajustar colunas
        for ($i = 1; $i <= count($allHeaders); $i++) {
            $sheetColetas->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        // ===== PLANILHA 2: FORMULÁRIO (PERGUNTAS) =====
        $sheetFormulario = $spreadsheet->createSheet();
        $sheetFormulario->setTitle('Formulário');

        // Buscar dados do questionário
        $CI =& get_instance();
        $CI->load->model('Questionnaire_model');
        $CI->load->model('Question_model');

        // Tradução dos tipos
        $type_labels = [
            'text' => 'Texto Curto',
            'textarea' => 'Texto Longo',
            'number' => 'Número',
            'date' => 'Data',
            'datetime' => 'Data e Hora',
            'radio' => 'Escolha Única',
            'checkbox' => 'Múltipla Escolha',
            'select' => 'Lista Suspensa',
            'photo' => 'Foto',
            'signature' => 'Assinatura',
            'location' => 'Localização'
        ];

        $rowForm = 1;

        if ($questionnaire_id) {
            $questionnaire = $CI->Questionnaire_model->get_by_id($questionnaire_id);
            $questions = $CI->Question_model->get_by_questionnaire($questionnaire_id);

            if ($questionnaire) {
                // Título do questionário
                $sheetFormulario->setCellValue('A' . $rowForm, 'QUESTIONÁRIO: ' . $questionnaire->title);
                $sheetFormulario->mergeCells('A' . $rowForm . ':F' . $rowForm);
                $sheetFormulario->getStyle('A' . $rowForm)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '23345F']],
                ]);
                $rowForm++;

                // Descrição
                if (!empty($questionnaire->description)) {
                    $sheetFormulario->setCellValue('A' . $rowForm, 'Descrição: ' . $questionnaire->description);
                    $sheetFormulario->mergeCells('A' . $rowForm . ':F' . $rowForm);
                    $rowForm++;
                }

                $rowForm++; // Linha em branco
            }

            // Cabeçalhos da tabela de perguntas
            $headersForm = ['ID', 'Ordem', 'Tipo', 'Texto da Pergunta', 'Obrigatória', 'Opções de Resposta'];
            $colForm = 'A';
            foreach ($headersForm as $header) {
                $sheetFormulario->setCellValue($colForm . $rowForm, $header);
                $colForm++;
            }
            $sheetFormulario->getStyle('A' . $rowForm . ':F' . $rowForm)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E87722']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            $rowForm++;

            // Dados das perguntas
            if (!empty($questions)) {
                foreach ($questions as $question) {
                    $sheetFormulario->setCellValue('A' . $rowForm, $question->id);
                    $sheetFormulario->setCellValue('B' . $rowForm, $question->order_index);
                    $sheetFormulario->setCellValue('C' . $rowForm, $type_labels[$question->question_type] ?? ucfirst($question->question_type));
                    $sheetFormulario->setCellValue('D' . $rowForm, $question->question_text);
                    $sheetFormulario->setCellValue('E' . $rowForm, $question->is_required ? 'Sim' : 'Não');

                    // Opções de resposta
                    $options_text = '';
                    if (!empty($question->options) && is_array($question->options)) {
                        $option_texts = array();
                        foreach ($question->options as $option) {
                            $option_texts[] = $option->option_text;
                        }
                        $options_text = implode(' | ', $option_texts);
                    }
                    $sheetFormulario->setCellValue('F' . $rowForm, $options_text);

                    $rowForm++;
                }
            } else {
                $sheetFormulario->setCellValue('A' . $rowForm, 'Nenhuma pergunta encontrada para este questionário');
                $sheetFormulario->mergeCells('A' . $rowForm . ':F' . $rowForm);
            }
        } else {
            $sheetFormulario->setCellValue('A1', 'Selecione um questionário específico para ver as perguntas');
            $sheetFormulario->mergeCells('A1:F1');
        }

        // Auto-ajustar colunas do formulário
        foreach (range('A', 'F') as $c) {
            $sheetFormulario->getColumnDimension($c)->setAutoSize(true);
        }

        // Definir a primeira planilha (Coletas) como ativa ao abrir
        $spreadsheet->setActiveSheetIndex(0);

        // Gerar arquivo
        $filename = 'appcoleta_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        // Limpar qualquer output anterior
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function create_detailed_export($questionnaire_id, $responses_with_answers)
    {
        $CI =& get_instance();
        $CI->load->model('Questionnaire_model');
        $CI->load->model('Question_model');

        $questionnaire = $CI->Questionnaire_model->get_by_id($questionnaire_id);
        $questions = $CI->Question_model->get_by_questionnaire($questionnaire_id);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalhos básicos
        $basic_headers = ['ID', 'APLICADOR', 'RESPONDENTE', 'EMAIL', 'DATA/HORA', 'LOCALIZAÇÃO', 'LATITUDE', 'LONGITUDE', 'CONSENTIMENTO'];
        $col = 1;

        foreach ($basic_headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 1, $header);
            $col++;
        }

        // Cabeçalhos das perguntas
        foreach ($questions as $question) {
            $sheet->setCellValueByColumnAndRow($col, 1, mb_strtoupper('P' . $question->order_index . ': ' . $question->question_text, 'UTF-8'));
            $col++;
        }

        // Estilizar cabeçalho
        $lastCol = $col - 1;
        $range = 'A1:' . chr(64 + $lastCol) . '1';
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E87722']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Preencher dados
        $row = 2;
        foreach ($responses_with_answers as $response) {
            $col = 1;

            // Dados básicos
            $sheet->setCellValueByColumnAndRow($col++, $row, $response->id);
            $sheet->setCellValueByColumnAndRow($col++, $row, $response->applied_by_name);
            $sheet->setCellValueByColumnAndRow($col++, $row, $response->respondent_name ?? 'N/A');
            $sheet->setCellValueByColumnAndRow($col++, $row, $response->respondent_email ?? 'N/A');
            $sheet->setCellValueByColumnAndRow($col++, $row, date('d/m/Y H:i', strtotime($response->completed_at)));
            $sheet->setCellValueByColumnAndRow($col++, $row, $response->location_name ?? 'N/A');
            $sheet->setCellValueByColumnAndRow($col++, $row, $response->latitude ?? '');
            $sheet->setCellValueByColumnAndRow($col++, $row, $response->longitude ?? '');
            $sheet->setCellValueByColumnAndRow($col++, $row, $response->consent_given ? 'Sim' : 'Não');

            // Respostas das perguntas
            foreach ($questions as $question) {
                $answer = '';
                if (isset($response->answers[$question->id])) {
                    $ans = $response->answers[$question->id];
                    if ($ans->response_text) {
                        $answer = $ans->response_text;
                    } elseif ($ans->response_number !== null) {
                        $answer = $ans->response_number;
                    } elseif ($ans->response_date) {
                        $answer = date('d/m/Y', strtotime($ans->response_date));
                    } elseif ($ans->selected_options) {
                        $options = json_decode($ans->selected_options);
                        // Converter valores para labels
                        if (is_array($options) && !empty($question->options)) {
                            // Criar mapa valor -> label
                            $value_to_label = array();
                            foreach ($question->options as $opt) {
                                $value_to_label[$opt->option_value] = $opt->option_text;
                            }
                            // Mapear valores para labels
                            $labels = array();
                            foreach ($options as $val) {
                                $labels[] = isset($value_to_label[$val]) ? $value_to_label[$val] : $val;
                            }
                            $answer = implode(', ', $labels);
                        } elseif (is_array($options)) {
                            $answer = implode(', ', $options);
                        } else {
                            // Tentar mapear valor único
                            if (!empty($question->options)) {
                                $value_to_label = array();
                                foreach ($question->options as $opt) {
                                    $value_to_label[$opt->option_value] = $opt->option_text;
                                }
                                $answer = isset($value_to_label[$ans->selected_options]) ? $value_to_label[$ans->selected_options] : $ans->selected_options;
                            } else {
                                $answer = $ans->selected_options;
                            }
                        }
                    }
                }
                $sheet->setCellValueByColumnAndRow($col++, $row, is_string($answer) ? mb_strtoupper($answer, 'UTF-8') : $answer);
            }

            $row++;
        }

        // Auto-ajustar colunas
        for ($i = 1; $i < $col; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        // Gerar arquivo
        $filename = 'appcoleta_' . strtolower(str_replace(' ', '_', $questionnaire->title)) . '_' . date('Y-m-d_H-i-s') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function get_location_name($latitude, $longitude)
    {
        // Verificar se as coordenadas são válidas
        if (empty($latitude) || empty($longitude) ||
            !is_numeric($latitude) || !is_numeric($longitude)) {
            return 'N/A';
        }

        // Validar range das coordenadas
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return 'N/A';
        }

        try {
            // Opção 1: Usando OpenStreetMap Nominatim (gratuito, sem necessidade de API key)
            $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$latitude}&lon={$longitude}&zoom=18&addressdetails=1";

            // Configurar contexto da requisição
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: AppColeta-App/1.0',
                        'Accept: application/json'
                    ],
                    'timeout' => 10
                ]
            ]);

            $response = file_get_contents($url, false, $context);

            if ($response === FALSE) {
                return 'N/A';
            }

            $data = json_decode($response, true);

            if (isset($data['display_name'])) {
                // Retornar o nome formatado da localização
                return $this->format_location_name($data);
            }

        } catch (Exception $e) {
            // Log do erro se necessário
            log_message('error', 'Erro na geocodificação: ' . $e->getMessage());
        }

        return 'N/A';
    }

    /**
     * Formatar o nome da localização de acordo com os dados retornados
     * @param array $data Dados da API de geocodificação
     * @return string Nome formatado da localização
     */
    private function format_location_name($data)
    {
        if (!isset($data['address'])) {
            return isset($data['display_name']) ? $data['display_name'] : 'N/A';
        }

        $address = $data['address'];
        $location_parts = [];

        // Priorizar informações mais específicas
        if (!empty($address['road'])) {
            $location_parts[] = $address['road'];
        }

        if (!empty($address['suburb']) || !empty($address['neighbourhood'])) {
            $location_parts[] = $address['suburb'] ?? $address['neighbourhood'];
        }

        if (!empty($address['city']) || !empty($address['town']) || !empty($address['village'])) {
            $location_parts[] = $address['city'] ?? $address['town'] ?? $address['village'];
        }

        if (!empty($address['state'])) {
            $location_parts[] = $address['state'];
        }

        if (!empty($address['country'])) {
            $location_parts[] = $address['country'];
        }

        return !empty($location_parts) ? implode(', ', $location_parts) : $data['display_name'];
    }

    /**
     * Converter índice de coluna numérico para letra (1=A, 2=B, ..., 27=AA, etc)
     * @param int $columnIndex Índice da coluna (1-based)
     * @return string Letra da coluna
     */
    private function getColumnLetter($columnIndex)
    {
        $letter = '';
        while ($columnIndex > 0) {
            $columnIndex--;
            $letter = chr(65 + ($columnIndex % 26)) . $letter;
            $columnIndex = intval($columnIndex / 26);
        }
        return $letter;
    }

}
