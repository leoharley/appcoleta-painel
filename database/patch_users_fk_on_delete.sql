-- =====================================================================
-- Patch: ajustar ON DELETE das foreign keys que referenciam `users`
-- =====================================================================
-- Faça BACKUP do banco antes de executar.
-- Pode rodar tudo de uma vez no phpMyAdmin (aba SQL).
--
-- Tabelas e estratégia:
--   activity_logs.user_id        -> CASCADE   (log de auditoria, segue o usuário)
--   sync_sessions.user_id        -> CASCADE   (sessão de sincronização do usuário)
--   form_responses.applied_by    -> SET NULL  (preserva as respostas)
--   projects.created_by          -> SET NULL  (preserva o projeto)
--   questionnaires.created_by    -> SET NULL  (preserva o questionário)
--   system_settings.updated_by   -> SET NULL  (preserva a configuração)
-- =====================================================================


-- ---------------------------------------------------------------------
-- 1) activity_logs.user_id  ->  ON DELETE CASCADE
-- ---------------------------------------------------------------------
ALTER TABLE `activity_logs`
    DROP FOREIGN KEY `activity_logs_user_id_fkey`;

ALTER TABLE `activity_logs`
    ADD CONSTRAINT `activity_logs_user_id_fkey`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;


-- ---------------------------------------------------------------------
-- 2) sync_sessions.user_id  ->  ON DELETE CASCADE
-- ---------------------------------------------------------------------
ALTER TABLE `sync_sessions`
    DROP FOREIGN KEY `sync_sessions_user_id_fkey`;

ALTER TABLE `sync_sessions`
    ADD CONSTRAINT `sync_sessions_user_id_fkey`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;


-- ---------------------------------------------------------------------
-- 3) form_responses.applied_by  ->  ON DELETE SET NULL
-- ---------------------------------------------------------------------
ALTER TABLE `form_responses`
    MODIFY `applied_by` INT NULL;

ALTER TABLE `form_responses`
    DROP FOREIGN KEY `form_responses_applied_by_fkey`;

ALTER TABLE `form_responses`
    ADD CONSTRAINT `form_responses_applied_by_fkey`
    FOREIGN KEY (`applied_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;


-- ---------------------------------------------------------------------
-- 4) projects.created_by  ->  ON DELETE SET NULL
-- ---------------------------------------------------------------------
ALTER TABLE `projects`
    MODIFY `created_by` INT NULL;

ALTER TABLE `projects`
    DROP FOREIGN KEY `projects_created_by_fkey`;

ALTER TABLE `projects`
    ADD CONSTRAINT `projects_created_by_fkey`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;


-- ---------------------------------------------------------------------
-- 5) questionnaires.created_by  ->  ON DELETE SET NULL
-- ---------------------------------------------------------------------
ALTER TABLE `questionnaires`
    MODIFY `created_by` INT NULL;

ALTER TABLE `questionnaires`
    DROP FOREIGN KEY `questionnaires_created_by_fkey`;

ALTER TABLE `questionnaires`
    ADD CONSTRAINT `questionnaires_created_by_fkey`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;


-- ---------------------------------------------------------------------
-- 6) system_settings.updated_by  ->  ON DELETE SET NULL
-- ---------------------------------------------------------------------
ALTER TABLE `system_settings`
    MODIFY `updated_by` INT NULL;

ALTER TABLE `system_settings`
    DROP FOREIGN KEY `system_settings_updated_by_fkey`;

ALTER TABLE `system_settings`
    ADD CONSTRAINT `system_settings_updated_by_fkey`
    FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;


-- ---------------------------------------------------------------------
-- 7) Verificação final
-- ---------------------------------------------------------------------
SELECT
    kcu.TABLE_NAME,
    kcu.COLUMN_NAME,
    kcu.CONSTRAINT_NAME,
    rc.DELETE_RULE,
    rc.UPDATE_RULE
FROM information_schema.REFERENTIAL_CONSTRAINTS rc
JOIN information_schema.KEY_COLUMN_USAGE kcu
  ON rc.CONSTRAINT_NAME    = kcu.CONSTRAINT_NAME
 AND rc.CONSTRAINT_SCHEMA  = kcu.TABLE_SCHEMA
WHERE kcu.REFERENCED_TABLE_NAME = 'users'
  AND kcu.TABLE_SCHEMA = DATABASE()
ORDER BY kcu.TABLE_NAME;
