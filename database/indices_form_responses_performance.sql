-- =====================================================================
-- Indices para acelerar a listagem de respostas do painel
-- =====================================================================
-- A tela /responses ordena por fr.created_at DESC e filtra por
-- questionnaire_id, applied_by, completed_at e sync_status. Sem indice,
-- o MySQL varre a tabela inteira e ordena tudo em memoria a cada
-- abertura da pagina.
--
-- Script IDEMPOTENTE: pode rodar varias vezes sem erro.
-- Faca BACKUP antes de executar.
-- =====================================================================

-- Ordenacao principal da listagem
SET @x = (SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'form_responses'
            AND INDEX_NAME = 'idx_fr_created_at');
SET @sql = IF(@x = 0,
  'CREATE INDEX `idx_fr_created_at` ON `form_responses` (`created_at`)',
  'SELECT ''idx_fr_created_at ja existe'' AS aviso');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Filtro por questionario + ordenacao
SET @x = (SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'form_responses'
            AND INDEX_NAME = 'idx_fr_questionnaire_created');
SET @sql = IF(@x = 0,
  'CREATE INDEX `idx_fr_questionnaire_created` ON `form_responses` (`questionnaire_id`, `created_at`)',
  'SELECT ''idx_fr_questionnaire_created ja existe'' AS aviso');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Filtro por aplicador + ordenacao
SET @x = (SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'form_responses'
            AND INDEX_NAME = 'idx_fr_applied_created');
SET @sql = IF(@x = 0,
  'CREATE INDEX `idx_fr_applied_created` ON `form_responses` (`applied_by`, `created_at`)',
  'SELECT ''idx_fr_applied_created ja existe'' AS aviso');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Filtro por data de conclusao (tambem usado pela estratificacao)
SET @x = (SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'form_responses'
            AND INDEX_NAME = 'idx_fr_completed_at');
SET @sql = IF(@x = 0,
  'CREATE INDEX `idx_fr_completed_at` ON `form_responses` (`completed_at`)',
  'SELECT ''idx_fr_completed_at ja existe'' AS aviso');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Busca de respostas por formulario (tela de detalhe e exportacao)
SET @x = (SELECT COUNT(*) FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'question_responses'
            AND INDEX_NAME = 'idx_qr_form_response');
SET @sql = IF(@x = 0,
  'CREATE INDEX `idx_qr_form_response` ON `question_responses` (`form_response_id`)',
  'SELECT ''idx_qr_form_response ja existe'' AS aviso');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- ---------------------------------------------------------------------
-- Conferencia
-- ---------------------------------------------------------------------
-- Lista os indices criados.
SELECT TABLE_NAME, INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS colunas
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('form_responses','question_responses')
GROUP BY TABLE_NAME, INDEX_NAME
ORDER BY TABLE_NAME, INDEX_NAME;

-- Total de respostas.
-- ATENCAO: rode esta linha com o SEU BANCO selecionado na lateral do
-- phpMyAdmin. Depois de consultar information_schema acima, alguns
-- clientes trocam o banco ativo e o SELECT simples falha com
-- "#1109 - Tabela desconhecida em information_schema".
SELECT COUNT(*) AS total_respostas FROM form_responses;
