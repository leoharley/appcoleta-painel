-- =====================================================================
-- Migration: soft-delete em question_options
-- Banco: MySQL 5.7+/8.0
-- =====================================================================
-- Contexto: quando o admin edita um questionário com respostas coletadas,
-- excluir uma alternativa quebrava a edição por causa de referências
-- (option_value gravado em question_responses.selected_options como JSON).
--
-- Solução: adicionar coluna `is_active` para desativar alternativas em vez
-- de excluí-las. O app do coletor deixa de exibir as inativas, mas os
-- registros históricos continuam válidos.
--
-- FAÇA BACKUP DO BANCO ANTES DE EXECUTAR.
-- =====================================================================

ALTER TABLE `question_options`
    ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `option_value`;

-- Índice para acelerar filtros por (question_id, is_active)
CREATE INDEX `idx_qopts_question_active`
    ON `question_options` (`question_id`, `is_active`);
