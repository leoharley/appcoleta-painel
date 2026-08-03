-- =====================================================================
-- Migration: adiciona `client_id` para idempotência de POST /api/forms/submit
-- Banco: MySQL 5.7+/8.0
-- =====================================================================
-- Contexto: o app móvel pode reenviar o mesmo formulário (retry após falha
-- parcial de sincronização) — sem uma chave estável, cada reenvio criaria
-- uma resposta duplicada no painel.
--
-- Solução: o app envia um `client_id` (uuid gerado localmente) junto com o
-- payload. O backend verifica antes de inserir e, se já existir uma resposta
-- com esse client_id, devolve o `form_response_id` original sem duplicar.
--
-- FAÇA BACKUP DO BANCO ANTES DE EXECUTAR.
-- =====================================================================

ALTER TABLE `form_responses`
    ADD COLUMN `client_id` VARCHAR(64) NULL AFTER `id`;

-- Índice único: registros com client_id preenchido não podem ser duplicados;
-- múltiplos NULLs continuam permitidos (MySQL trata NULL como distinto).
CREATE UNIQUE INDEX `uq_form_responses_client_id`
    ON `form_responses` (`client_id`);
