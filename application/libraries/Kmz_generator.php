<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class KMZ_Generator {
    
    public function generate($responses, $title = 'Localizações AppColeta', $filename = 'localizacoes_appcoleta', $options = []) {
        try {
            // Configurações padrão
            $default_options = [
                'include_photos' => true,
                'include_respondent' => true,
                'include_applicator' => true,
                'include_timestamps' => true,
                'include_questionnaire_info' => true
            ];
            
            $options = array_merge($default_options, $options);
            
            // Criar KML content
            $kml_content = $this->create_kml_content($responses, $title, $options);
            
            // Se não há dados, retornar erro
            if (empty($responses)) {
                throw new Exception('Nenhuma localização encontrada para gerar o arquivo KMZ.');
            }
            
            // Criar arquivo temporário
            $temp_dir = sys_get_temp_dir() . '/' . uniqid('kmz_', true);
            if (!mkdir($temp_dir, 0755, true)) {
                throw new Exception('Não foi possível criar diretório temporário.');
            }
            
            // Salvar KML
            $kml_file = $temp_dir . '/doc.kml';
            if (file_put_contents($kml_file, $kml_content) === false) {
                throw new Exception('Não foi possível criar arquivo KML.');
            }
            
            // Criar arquivo KMZ (ZIP)
            $kmz_filename = $filename . '_' . date('Y-m-d') . '.kmz';
            $kmz_path = $temp_dir . '/' . $kmz_filename;
            
            $zip = new ZipArchive();
            if ($zip->open($kmz_path, ZipArchive::CREATE) !== TRUE) {
                throw new Exception('Não foi possível criar arquivo KMZ.');
            }
            
            $zip->addFile($kml_file, 'doc.kml');
            $zip->close();
            
            // Verificar se arquivo foi criado
            if (!file_exists($kmz_path)) {
                throw new Exception('Arquivo KMZ não foi gerado corretamente.');
            }
            
            // Log da operação
            log_message('info', 'KMZ gerado com sucesso: ' . $kmz_filename . ' - ' . count($responses) . ' localizações');
            
            // Fazer download
            $this->download_file($kmz_path, $kmz_filename);
            
            // Limpar arquivos temporários
            $this->cleanup_temp_files($temp_dir);
            
        } catch (Exception $e) {
            log_message('error', 'Erro na geração de KMZ: ' . $e->getMessage());
            
            // Tentar limpar arquivos temporários
            if (isset($temp_dir) && is_dir($temp_dir)) {
                $this->cleanup_temp_files($temp_dir);
            }
            
            throw $e;
        }
    }
    
    /**
     * Criar conteúdo KML
     */
    private function create_kml_content($responses, $title, $options) {
        $kml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $kml .= '<kml xmlns="http://www.opengis.net/kml/2.2">' . "\n";
        $kml .= '  <Document>' . "\n";
        $kml .= '    <name>' . htmlspecialchars($title) . '</name>' . "\n";
        $kml .= '    <description>Arquivo KMZ gerado pelo sistema AppColeta em ' . date('d/m/Y H:i:s') . '</description>' . "\n";
        
        // Adicionar estilos
        $kml .= $this->create_kml_styles();
        
        // Criar pastas por questionário
        $by_questionnaire = $this->group_responses_by_questionnaire($responses);
        
        foreach ($by_questionnaire as $questionnaire_id => $questionnaire_responses) {
            $questionnaire_title = !empty($questionnaire_responses[0]->questionnaire_title) ? 
                $questionnaire_responses[0]->questionnaire_title : 'Questionário #' . $questionnaire_id;
            
            $kml .= '    <Folder>' . "\n";
            $kml .= '      <name>' . htmlspecialchars($questionnaire_title) . ' (' . count($questionnaire_responses) . ')</name>' . "\n";
            $kml .= '      <description>Localizações do questionário: ' . htmlspecialchars($questionnaire_title) . '</description>' . "\n";
            
            // Adicionar placemarks para cada resposta
            foreach ($questionnaire_responses as $response) {
                $kml .= $this->create_placemark($response, $options);
            }
            
            $kml .= '    </Folder>' . "\n";
        }
        
        $kml .= '  </Document>' . "\n";
        $kml .= '</kml>' . "\n";
        
        return $kml;
    }
    
    /**
     * Criar estilos KML
     */
    private function create_kml_styles() {
        return '
    <Style id="appcoleta_point">
      <IconStyle>
        <color>ff00ff00</color>
        <scale>1.2</scale>
        <Icon>
          <href>http://maps.google.com/mapfiles/kml/shapes/placemark_circle.png</href>
        </Icon>
      </IconStyle>
      <LabelStyle>
        <color>ff0000ff</color>
        <scale>0.8</scale>
      </LabelStyle>
    </Style>
    
    <Style id="appcoleta_point_photo">
      <IconStyle>
        <color>ffff0000</color>
        <scale>1.3</scale>
        <Icon>
          <href>http://maps.google.com/mapfiles/kml/shapes/camera.png</href>
        </Icon>
      </IconStyle>
      <LabelStyle>
        <color>ffff0000</color>
        <scale>0.9</scale>
      </LabelStyle>
    </Style>
    ';
    }
    
    /**
     * Agrupar respostas por questionário
     */
    private function group_responses_by_questionnaire($responses) {
        $grouped = [];
        
        foreach ($responses as $response) {
            $qid = $response->questionnaire_id;
            if (!isset($grouped[$qid])) {
                $grouped[$qid] = [];
            }
            $grouped[$qid][] = $response;
        }
        
        return $grouped;
    }
    
    /**
     * Criar placemark individual
     */
    private function create_placemark($response, $options) {
        // Determinar nome do ponto
        $placemark_name = $this->generate_placemark_name($response);
        
        // Determinar estilo baseado se tem foto
        $style = !empty($response->photo_path) ? 'appcoleta_point_photo' : 'appcoleta_point';
        
        // Criar descrição
        $description = $this->create_placemark_description($response, $options);
        
        $kml = '      <Placemark>' . "\n";
        $kml .= '        <name>' . htmlspecialchars($placemark_name) . '</name>' . "\n";
        $kml .= '        <description><![CDATA[' . $description . ']]></description>' . "\n";
        $kml .= '        <styleUrl>#' . $style . '</styleUrl>' . "\n";
        $kml .= '        <Point>' . "\n";
        $kml .= '          <coordinates>' . $response->longitude . ',' . $response->latitude . ',0</coordinates>' . "\n";
        $kml .= '        </Point>' . "\n";
        
        // Adicionar dados estendidos
        $kml .= '        <ExtendedData>' . "\n";
        $kml .= '          <Data name="response_id"><value>' . $response->id . '</value></Data>' . "\n";
        $kml .= '          <Data name="questionnaire_id"><value>' . $response->questionnaire_id . '</value></Data>' . "\n";
        $kml .= '          <Data name="completed_at"><value>' . $response->completed_at . '</value></Data>' . "\n";
        
        if ($options['include_respondent'] && !empty($response->respondent_name)) {
            $kml .= '          <Data name="respondent"><value>' . htmlspecialchars($response->respondent_name) . '</value></Data>' . "\n";
        }
        
        if ($options['include_applicator'] && !empty($response->applied_by_name)) {
            $kml .= '          <Data name="applicator"><value>' . htmlspecialchars($response->applied_by_name) . '</value></Data>' . "\n";
        }
        
        $kml .= '        </ExtendedData>' . "\n";
        $kml .= '      </Placemark>' . "\n";
        
        return $kml;
    }
    
    /**
     * Gerar nome do placemark
     */
    private function generate_placemark_name($response) {
        if (!empty($response->respondent_name)) {
            return $response->respondent_name;
        } elseif (!empty($response->location_name)) {
            return $response->location_name;
        } elseif (!empty($response->indexador)) {
            return 'Resposta: ' . substr($response->indexador, 0, 30) . '...';
        } else {
            return 'Resposta #' . $response->id;
        }
    }
    
    /**
     * Criar descrição HTML do placemark
     */
    private function create_placemark_description($response, $options) {
        $html = '<div style="font-family: Arial, sans-serif; max-width: 400px;">';
        
        // Título
        if ($options['include_questionnaire_info'] && !empty($response->questionnaire_title)) {
            $html .= '<h3 style="color: #E87722; margin: 0 0 10px 0;">' . htmlspecialchars($response->questionnaire_title) . '</h3>';
        }
        
        // Informações básicas
        $html .= '<table style="width: 100%; border-collapse: collapse;">';
        
        if ($options['include_respondent'] && !empty($response->respondent_name)) {
            $html .= '<tr><td style="padding: 3px; font-weight: bold;">Respondente:</td><td style="padding: 3px;">' . htmlspecialchars($response->respondent_name) . '</td></tr>';
        }
        
        if ($options['include_applicator'] && !empty($response->applied_by_name)) {
            $html .= '<tr><td style="padding: 3px; font-weight: bold;">Aplicador:</td><td style="padding: 3px;">' . htmlspecialchars($response->applied_by_name) . '</td></tr>';
        }
        
        if (!empty($response->location_name)) {
            $html .= '<tr><td style="padding: 3px; font-weight: bold;">Local:</td><td style="padding: 3px;">' . htmlspecialchars($response->location_name) . '</td></tr>';
        }
        
        if ($options['include_timestamps']) {
            $html .= '<tr><td style="padding: 3px; font-weight: bold;">Data/Hora:</td><td style="padding: 3px;">' . date('d/m/Y H:i', strtotime($response->completed_at)) . '</td></tr>';
        }
        
        // Coordenadas
        $html .= '<tr><td style="padding: 3px; font-weight: bold;">Coordenadas:</td><td style="padding: 3px;">' . 
                 number_format($response->latitude, 6) . ', ' . number_format($response->longitude, 6) . '</td></tr>';
        
        // Duração se disponível
        if (!empty($response->duration_minutes)) {
            $duration_text = $response->duration_minutes < 60 ? 
                round($response->duration_minutes) . ' min' : 
                floor($response->duration_minutes / 60) . 'h ' . round($response->duration_minutes % 60) . 'min';
            $html .= '<tr><td style="padding: 3px; font-weight: bold;">Duração:</td><td style="padding: 3px;">' . $duration_text . '</td></tr>';
        }
        
        $html .= '</table>';
        
        // Indicadores visuais
        if ($options['include_photos'] && !empty($response->photo_path)) {
            $html .= '<p style="margin: 10px 0 5px 0;"><span style="background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">📷 Foto</span></p>';
        }
        
        if (!empty($response->consent_given) && $response->consent_given) {
            $html .= '<p style="margin: 5px 0;"><span style="background: #17a2b8; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">✓ Consentimento</span></p>';
        }
        
        // Sample de respostas se disponível
        if (!empty($response->sample_answers)) {
            $html .= '<div style="margin-top: 10px; padding: 8px; background: #f8f9fa; border-left: 3px solid #E87722;">';
            $html .= '<strong>Amostra de Respostas:</strong><br>';
            $html .= '<small>' . htmlspecialchars(substr($response->sample_answers, 0, 200)) . '...</small>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Fazer download do arquivo
     */
    private function download_file($file_path, $filename) {
        if (!file_exists($file_path)) {
            throw new Exception('Arquivo não encontrado para download.');
        }
        
        // Limpar qualquer output anterior
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Headers para download
        header('Content-Type: application/vnd.google-earth.kmz');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        // Enviar arquivo
        readfile($file_path);
        exit;
    }
    
    /**
     * Limpar arquivos temporários
     */
    private function cleanup_temp_files($temp_dir) {
        if (!is_dir($temp_dir)) {
            return;
        }
        
        try {
            $files = glob($temp_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($temp_dir);
        } catch (Exception $e) {
            log_message('warning', 'Não foi possível limpar arquivos temporários: ' . $e->getMessage());
        }
    }
}