-- =====================================================================
-- Migration: soft-delete em questions
-- Banco: MySQL 5.7+/8.0
-- =====================================================================
-- Contexto: quando o admin edita um questionário com respostas coletadas e
-- remove uma pergunta na tela de edição, o servidor tentava DELETE em
-- `questions`, que falhava com FK 1451 vindo de `question_responses.question_id`.
--
-- Solução: adicionar coluna `is_active` para desativar perguntas em vez de
-- excluí-las quando houver respostas. O app do coletor deixa de exibir as
-- inativas, mas as respostas antigas continuam válidas.
--
-- FAÇA BACKUP DO BANCO ANTES DE EXECUTAR.
-- =====================================================================

ALTER TABLE `questions`
    ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `order_index`;

-- Índice para acelerar filtros por (questionnaire_id, is_active)
CREATE INDEX `idx_questions_questionnaire_active`
    ON `questions` (`questionnaire_id`, `is_active`);
