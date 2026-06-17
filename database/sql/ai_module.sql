-- =====================================================================
-- Migration: módulo de Inteligência Artificial (Fase 1 — fundação)
-- Banco: MySQL 5.7+ / 8.0
-- =====================================================================
-- FAÇA BACKUP DO BANCO ANTES DE EXECUTAR.
-- Pode ser executado várias vezes (idempotente quando possível).
-- =====================================================================

SET FOREIGN_KEY_CHECKS=0;

-- ---------------------------------------------------------------------
-- 1) ai_settings  — feature toggles + parâmetros do modelo
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_settings` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `feature_key`      VARCHAR(64)     NOT NULL UNIQUE,
    `feature_name`     VARCHAR(128)    NOT NULL,
    `category`         VARCHAR(32)     NOT NULL DEFAULT 'coleta',
    `is_enabled`       TINYINT(1)      NOT NULL DEFAULT 0,
    `model`            VARCHAR(64)     NULL,
    `temperature`      DECIMAL(3,2)    NOT NULL DEFAULT 0.30,
    `max_tokens`       INT             NOT NULL DEFAULT 1024,
    `timeout_seconds`  INT             NOT NULL DEFAULT 60,
    `retry_attempts`   TINYINT         NOT NULL DEFAULT 2,
    `custom_params`    TEXT            NULL,
    `environment`      VARCHAR(16)     NOT NULL DEFAULT 'production',
    `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ai_settings_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2) ai_prompts  — prompts versionados por feature
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_prompts` (
    `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `feature_key`           VARCHAR(64)  NOT NULL,
    `prompt_name`           VARCHAR(128) NOT NULL,
    `system_prompt`         TEXT         NULL,
    `user_prompt_template`  TEXT         NULL,
    `version`               INT          NOT NULL DEFAULT 1,
    `is_active`             TINYINT(1)   NOT NULL DEFAULT 1,
    `created_by`            INT          NULL,
    `created_at`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ai_prompts_feature_active` (`feature_key`, `is_active`),
    CONSTRAINT `fk_ai_prompts_user`
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3) ai_execution_logs  — log de cada chamada à IA
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_execution_logs` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `feature_key`      VARCHAR(64)    NOT NULL,
    `model_used`       VARCHAR(64)    NULL,
    `prompt_id`        INT UNSIGNED   NULL,
    `input_data`       LONGTEXT       NULL,
    `output_data`      LONGTEXT       NULL,
    `tokens_input`     INT            NOT NULL DEFAULT 0,
    `tokens_output`    INT            NOT NULL DEFAULT 0,
    `cost_usd`         DECIMAL(10,6)  NOT NULL DEFAULT 0,
    `duration_ms`      INT            NOT NULL DEFAULT 0,
    `status`           VARCHAR(16)    NOT NULL DEFAULT 'pending',
    `error_message`    TEXT           NULL,
    `executed_by`      INT            NULL,
    `resource_type`    VARCHAR(64)    NULL,
    `resource_id`      INT            NULL,
    `created_at`       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ai_logs_feature_created` (`feature_key`, `created_at`),
    INDEX `idx_ai_logs_status` (`status`),
    CONSTRAINT `fk_ai_logs_prompt`
        FOREIGN KEY (`prompt_id`) REFERENCES `ai_prompts`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ai_logs_user`
        FOREIGN KEY (`executed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4) ai_transcriptions
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_transcriptions` (
    `id`                         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `form_response_id`           INT          NULL,
    `question_id`                INT          NULL,
    `questionnaire_id`           INT          NULL,
    `audio_file_path`            VARCHAR(512) NULL,
    `audio_duration_seconds`     DECIMAL(10,2) NULL,
    `transcription_text`         LONGTEXT     NULL,
    `transcription_edited`       LONGTEXT     NULL,
    `language`                   VARCHAR(16)  NOT NULL DEFAULT 'pt-BR',
    `confidence_score`           DECIMAL(5,4) NULL,
    `status`                     VARCHAR(16)  NOT NULL DEFAULT 'pending',
    `model_used`                 VARCHAR(64)  NULL,
    `processed_at`               DATETIME     NULL,
    `processed_by`               INT          NULL,
    `error_message`              TEXT         NULL,
    `app_id`                     VARCHAR(36)  NULL UNIQUE,
    `question_text`              TEXT         NULL,
    `edited_text`                LONGTEXT     NULL,
    `duration_ms`                INT          NULL,
    `recording_duration_secs`    DECIMAL(10,2) NULL,
    `applicator_name`            VARCHAR(128) NULL,
    `applicator_id`              INT          NULL,
    `timestamp_app`              DATETIME     NULL,
    `source`                     VARCHAR(32)  NOT NULL DEFAULT 'upload',
    `created_at`                 TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                 TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ai_transc_form_response` (`form_response_id`),
    INDEX `idx_ai_transc_questionnaire` (`questionnaire_id`),
    INDEX `idx_ai_transc_status` (`status`),
    CONSTRAINT `fk_ai_transc_form_response`
        FOREIGN KEY (`form_response_id`) REFERENCES `form_responses`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_transc_question`
        FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_transc_questionnaire`
        FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_transc_processed_by`
        FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ai_transc_applicator`
        FOREIGN KEY (`applicator_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5) ai_inconsistencies
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_inconsistencies` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `form_response_id`    INT          NULL,
    `questionnaire_id`    INT          NULL,
    `inconsistency_type`  VARCHAR(64)  NULL,
    `severity`            VARCHAR(16)  NOT NULL DEFAULT 'low',
    `consistency_score`   DECIMAL(5,2) NULL,
    `description`         TEXT         NULL,
    `ai_justification`    TEXT         NULL,
    `affected_questions`  JSON         NULL,
    `suggested_action`    TEXT         NULL,
    `resolution_status`   VARCHAR(16)  NOT NULL DEFAULT 'pending',
    `resolved_by`         INT          NULL,
    `resolved_at`         DATETIME     NULL,
    `resolution_notes`    TEXT         NULL,
    `execution_log_id`    INT UNSIGNED NULL,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ai_inc_status` (`resolution_status`),
    CONSTRAINT `fk_ai_inc_form_response`
        FOREIGN KEY (`form_response_id`) REFERENCES `form_responses`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_inc_questionnaire`
        FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_inc_resolved_by`
        FOREIGN KEY (`resolved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ai_inc_exec_log`
        FOREIGN KEY (`execution_log_id`) REFERENCES `ai_execution_logs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6) ai_corrections
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_corrections` (
    `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `form_response_id`      INT          NULL,
    `question_id`           INT          NULL,
    `question_response_id`  INT          NULL,
    `field_name`            VARCHAR(128) NULL,
    `original_value`        TEXT         NULL,
    `suggested_value`       TEXT         NULL,
    `correction_type`       VARCHAR(64)  NULL,
    `confidence_score`      DECIMAL(5,4) NULL,
    `status`                VARCHAR(16)  NOT NULL DEFAULT 'pending',
    `reviewed_by`           INT          NULL,
    `reviewed_at`           DATETIME     NULL,
    `applied_value`         TEXT         NULL,
    `execution_log_id`      INT UNSIGNED NULL,
    `created_at`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ai_corr_status` (`status`),
    CONSTRAINT `fk_ai_corr_form_response`
        FOREIGN KEY (`form_response_id`) REFERENCES `form_responses`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_corr_question`
        FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_corr_qresponse`
        FOREIGN KEY (`question_response_id`) REFERENCES `question_responses`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_corr_reviewed_by`
        FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ai_corr_exec_log`
        FOREIGN KEY (`execution_log_id`) REFERENCES `ai_execution_logs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7) ai_field_suggestions  — preenchimento inteligente
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_field_suggestions` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `questionnaire_id`  INT          NULL,
    `question_id`       INT          NULL,
    `form_response_id`  INT          NULL,
    `suggested_value`   TEXT         NULL,
    `context_data`      JSON         NULL,
    `confidence_score`  DECIMAL(5,4) NULL,
    `status`            VARCHAR(16)  NOT NULL DEFAULT 'pending',
    `reviewed_by`       INT          NULL,
    `reviewed_at`       DATETIME     NULL,
    `execution_log_id`  INT UNSIGNED NULL,
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ai_field_status` (`status`),
    CONSTRAINT `fk_ai_field_questionnaire`
        FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_field_question`
        FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_field_form_response`
        FOREIGN KEY (`form_response_id`) REFERENCES `form_responses`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_field_reviewed_by`
        FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ai_field_exec_log`
        FOREIGN KEY (`execution_log_id`) REFERENCES `ai_execution_logs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8) ai_reformulations
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_reformulations` (
    `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `question_id`          INT          NULL,
    `original_text`        TEXT         NULL,
    `reformulated_text`    TEXT         NULL,
    `objective`            VARCHAR(255) NULL,
    `status`               VARCHAR(16)  NOT NULL DEFAULT 'pending',
    `approved_by`          INT          NULL,
    `approved_at`          DATETIME     NULL,
    `execution_log_id`     INT UNSIGNED NULL,
    `created_at`           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ai_ref_status` (`status`),
    CONSTRAINT `fk_ai_ref_question`
        FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_ref_approved_by`
        FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ai_ref_exec_log`
        FOREIGN KEY (`execution_log_id`) REFERENCES `ai_execution_logs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9) ai_adaptive_rules
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_adaptive_rules` (
    `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `questionnaire_id`     INT          NULL,
    `source_question_id`   INT          NULL,
    `target_question_id`   INT          NULL,
    `condition_logic`      JSON         NULL,
    `ai_generated`         TINYINT(1)   NOT NULL DEFAULT 1,
    `fallback_target_id`   INT          NULL,
    `priority`             INT          NOT NULL DEFAULT 0,
    `is_active`            TINYINT(1)   NOT NULL DEFAULT 1,
    `approved_by`          INT          NULL,
    `approved_at`          DATETIME     NULL,
    `execution_log_id`     INT UNSIGNED NULL,
    `created_at`           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ai_adap_questionnaire` (`questionnaire_id`),
    CONSTRAINT `fk_ai_adap_questionnaire`
        FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_adap_source`
        FOREIGN KEY (`source_question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_adap_target`
        FOREIGN KEY (`target_question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_adap_fallback`
        FOREIGN KEY (`fallback_target_id`) REFERENCES `questions`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ai_adap_approved_by`
        FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ai_adap_exec_log`
        FOREIGN KEY (`execution_log_id`) REFERENCES `ai_execution_logs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 10) ai_followup_suggestions
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_followup_suggestions` (
    `id`                       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `questionnaire_id`         INT          NULL,
    `question_id`              INT          NULL,
    `suggested_question_text`  TEXT         NULL,
    `question_type`            VARCHAR(32)  NULL,
    `suggested_options`        JSON         NULL,
    `rationale`                TEXT         NULL,
    `status`                   VARCHAR(16)  NOT NULL DEFAULT 'pending',
    `added_as_question_id`     INT          NULL,
    `reviewed_by`              INT          NULL,
    `reviewed_at`              DATETIME     NULL,
    `execution_log_id`         INT UNSIGNED NULL,
    `created_at`               TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ai_followup_status` (`status`),
    CONSTRAINT `fk_ai_followup_questionnaire`
        FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_followup_question`
        FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_followup_added`
        FOREIGN KEY (`added_as_question_id`) REFERENCES `questions`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ai_followup_reviewed_by`
        FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ai_followup_exec_log`
        FOREIGN KEY (`execution_log_id`) REFERENCES `ai_execution_logs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 11) ai_statistical_analyses
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_statistical_analyses` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `questionnaire_id`    INT          NULL,
    `analysis_type`       VARCHAR(64)  NULL,
    `filters_applied`     JSON         NULL,
    `summary_text`        LONGTEXT     NULL,
    `patterns`            JSON         NULL,
    `outliers`            JSON         NULL,
    `trends`              JSON         NULL,
    `insights`            JSON         NULL,
    `chart_suggestions`   JSON         NULL,
    `status`              VARCHAR(16)  NOT NULL DEFAULT 'completed',
    `generated_by`        INT          NULL,
    `execution_log_id`    INT UNSIGNED NULL,
    `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ai_stats_questionnaire` (`questionnaire_id`),
    CONSTRAINT `fk_ai_stats_questionnaire`
        FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_stats_user`
        FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ai_stats_exec_log`
        FOREIGN KEY (`execution_log_id`) REFERENCES `ai_execution_logs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 12) ai_reports
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_reports` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title`             VARCHAR(255) NOT NULL,
    `questionnaire_id`  INT          NULL,
    `report_type`       VARCHAR(64)  NULL,
    `filters_applied`   JSON         NULL,
    `narrative_text`    LONGTEXT     NULL,
    `sections`          JSON         NULL,
    `charts_data`       JSON         NULL,
    `status`            VARCHAR(16)  NOT NULL DEFAULT 'completed',
    `generated_by`      INT          NULL,
    `execution_log_id`  INT UNSIGNED NULL,
    `exported_at`       DATETIME     NULL,
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ai_reports_questionnaire` (`questionnaire_id`),
    CONSTRAINT `fk_ai_reports_questionnaire`
        FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ai_reports_user`
        FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ai_reports_exec_log`
        FOREIGN KEY (`execution_log_id`) REFERENCES `ai_execution_logs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 13) ai_directives  — instruções do admin para o agente
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_directives` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category`        VARCHAR(64)  NULL,
    `title`           VARCHAR(255) NOT NULL,
    `content`         TEXT         NOT NULL,
    `directive_type`  VARCHAR(64)  NULL,
    `priority`        INT          NOT NULL DEFAULT 0,
    `is_active`       TINYINT(1)   NOT NULL DEFAULT 1,
    `applies_to`      JSON         NULL,
    `created_by`      INT          NULL,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ai_dir_active_priority` (`is_active`, `priority`),
    CONSTRAINT `fk_ai_dir_user`
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 14) question_followup_tips  — dicas exibidas no app por pergunta
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `question_followup_tips` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `question_id`       INT          NULL,
    `tip`               TEXT         NOT NULL,
    `source`            VARCHAR(16)  NOT NULL DEFAULT 'manual',
    `ai_suggestion_id`  INT UNSIGNED NULL,
    `created_by`        INT          NULL,
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_q_tips_question` (`question_id`),
    CONSTRAINT `fk_qtips_question`
        FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_qtips_suggestion`
        FOREIGN KEY (`ai_suggestion_id`) REFERENCES `ai_followup_suggestions`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_qtips_user`
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 15) ai_audit_logs  — eventos de uso de IA vindos do app
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_audit_logs` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_type`        VARCHAR(64)  NOT NULL,
    `question_id`       INT          NULL,
    `questionnaire_id`  INT          NULL,
    `form_id`           INT          NULL,
    `user_id`           INT          NULL,
    `metadata`          TEXT         NULL,
    `timestamp`         DATETIME     NULL,
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ai_audit_event_created` (`event_type`, `created_at`),
    CONSTRAINT `fk_ai_audit_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ai_audit_question`
        FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ai_audit_questionnaire`
        FOREIGN KEY (`questionnaire_id`) REFERENCES `questionnaires`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 16) ai_cache  — cache de respostas
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_cache` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cache_key`    VARCHAR(255) NOT NULL UNIQUE,
    `cache_value`  LONGTEXT     NULL,
    `expires_at`   DATETIME     NULL,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ai_cache_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 17) ai_rate_limits
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_rate_limits` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`        INT          NULL,
    `endpoint`       VARCHAR(128) NOT NULL,
    `request_count`  INT          NOT NULL DEFAULT 0,
    `window_start`   DATETIME     NOT NULL,
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ai_rl_user_endpoint_window` (`user_id`, `endpoint`, `window_start`),
    CONSTRAINT `fk_ai_rl_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;

-- =====================================================================
-- SEEDS — idempotentes (só inserem se ainda não existir)
-- =====================================================================

-- API key da OpenAI fica em ai_settings com feature_key = '_openai_api_key'.
-- Após rodar a migration, edite a linha pelo phpMyAdmin e cole sua chave em
-- `custom_params`. O campo `is_enabled` controla se o cliente está ativo.
INSERT INTO `ai_settings`
    (`feature_key`, `feature_name`, `category`, `is_enabled`, `model`, `temperature`, `max_tokens`, `timeout_seconds`, `retry_attempts`, `custom_params`)
SELECT '_openai_api_key', 'Chave da API OpenAI', 'config', 0, NULL, 0.00, 0, 60, 0, ''
WHERE NOT EXISTS (SELECT 1 FROM `ai_settings` WHERE `feature_key` = '_openai_api_key');

-- 10 features (todas desligadas por padrão)
INSERT INTO `ai_settings` (`feature_key`,`feature_name`,`category`,`is_enabled`,`model`,`temperature`,`max_tokens`,`timeout_seconds`,`retry_attempts`)
SELECT 'transcription','Transcrição de áudio (Whisper)','coleta',0,'whisper-1',0.00,4096,90,2
WHERE NOT EXISTS (SELECT 1 FROM `ai_settings` WHERE `feature_key`='transcription');

INSERT INTO `ai_settings` (`feature_key`,`feature_name`,`category`,`is_enabled`,`model`,`temperature`,`max_tokens`,`timeout_seconds`,`retry_attempts`)
SELECT 'inconsistency_detection','Detecção de inconsistências','qualidade',0,'gpt-4o-mini',0.20,2048,60,2
WHERE NOT EXISTS (SELECT 1 FROM `ai_settings` WHERE `feature_key`='inconsistency_detection');

INSERT INTO `ai_settings` (`feature_key`,`feature_name`,`category`,`is_enabled`,`model`,`temperature`,`max_tokens`,`timeout_seconds`,`retry_attempts`)
SELECT 'data_correction','Correção e padronização','qualidade',0,'gpt-4o-mini',0.20,2048,60,2
WHERE NOT EXISTS (SELECT 1 FROM `ai_settings` WHERE `feature_key`='data_correction');

INSERT INTO `ai_settings` (`feature_key`,`feature_name`,`category`,`is_enabled`,`model`,`temperature`,`max_tokens`,`timeout_seconds`,`retry_attempts`)
SELECT 'smart_fill','Preenchimento inteligente','coleta',0,'gpt-4o-mini',0.30,1024,45,2
WHERE NOT EXISTS (SELECT 1 FROM `ai_settings` WHERE `feature_key`='smart_fill');

INSERT INTO `ai_settings` (`feature_key`,`feature_name`,`category`,`is_enabled`,`model`,`temperature`,`max_tokens`,`timeout_seconds`,`retry_attempts`)
SELECT 'question_reformulation','Reformulação de perguntas','questionario',0,'gpt-4o-mini',0.50,1024,60,2
WHERE NOT EXISTS (SELECT 1 FROM `ai_settings` WHERE `feature_key`='question_reformulation');

INSERT INTO `ai_settings` (`feature_key`,`feature_name`,`category`,`is_enabled`,`model`,`temperature`,`max_tokens`,`timeout_seconds`,`retry_attempts`)
SELECT 'adaptive_routing','Questionário adaptativo','questionario',0,'gpt-4o-mini',0.30,2048,60,2
WHERE NOT EXISTS (SELECT 1 FROM `ai_settings` WHERE `feature_key`='adaptive_routing');

INSERT INTO `ai_settings` (`feature_key`,`feature_name`,`category`,`is_enabled`,`model`,`temperature`,`max_tokens`,`timeout_seconds`,`retry_attempts`)
SELECT 'followup_suggestions','Sugestão de follow-up','coleta',0,'gpt-4o-mini',0.40,1024,60,2
WHERE NOT EXISTS (SELECT 1 FROM `ai_settings` WHERE `feature_key`='followup_suggestions');

INSERT INTO `ai_settings` (`feature_key`,`feature_name`,`category`,`is_enabled`,`model`,`temperature`,`max_tokens`,`timeout_seconds`,`retry_attempts`)
SELECT 'statistical_analysis','Análise estatística','analise',0,'gpt-4o',0.30,4096,120,2
WHERE NOT EXISTS (SELECT 1 FROM `ai_settings` WHERE `feature_key`='statistical_analysis');

INSERT INTO `ai_settings` (`feature_key`,`feature_name`,`category`,`is_enabled`,`model`,`temperature`,`max_tokens`,`timeout_seconds`,`retry_attempts`)
SELECT 'smart_charts','Gráficos inteligentes','analise',0,'gpt-4o-mini',0.30,2048,60,2
WHERE NOT EXISTS (SELECT 1 FROM `ai_settings` WHERE `feature_key`='smart_charts');

INSERT INTO `ai_settings` (`feature_key`,`feature_name`,`category`,`is_enabled`,`model`,`temperature`,`max_tokens`,`timeout_seconds`,`retry_attempts`)
SELECT 'natural_reports','Relatórios em linguagem natural','analise',0,'gpt-4o',0.50,4096,180,2
WHERE NOT EXISTS (SELECT 1 FROM `ai_settings` WHERE `feature_key`='natural_reports');

-- ---------------------------------------------------------------------
-- Prompts padrão (versão 1, ativos)
-- ---------------------------------------------------------------------
INSERT INTO `ai_prompts` (`feature_key`,`prompt_name`,`system_prompt`,`user_prompt_template`,`version`,`is_active`)
SELECT 'inconsistency_detection','Detecção padrão',
    'Você é um auditor de pesquisas. Identifique inconsistências lógicas, temporais ou numéricas entre respostas. Responda em JSON estrito com {"score":0-100,"items":[{"severity":"low|medium|high|critical","description":"...","justification":"...","affected_questions":[]}]}.',
    'Questionário: {{questionnaire_title}}\n\nRespostas:\n{{responses_block}}\n\nAnalise as inconsistências.',
    1,1
WHERE NOT EXISTS (SELECT 1 FROM `ai_prompts` WHERE `feature_key`='inconsistency_detection' AND `is_active`=1);

INSERT INTO `ai_prompts` (`feature_key`,`prompt_name`,`system_prompt`,`user_prompt_template`,`version`,`is_active`)
SELECT 'data_correction','Correção padrão',
    'Você é um revisor de dados. Sugira correções de digitação, padronização (capitalização, acentuação, formato) sem inventar dados. Responda em JSON {"corrections":[{"field":"...","original":"...","suggested":"...","type":"typo|format|normalize","confidence":0-1}]}.',
    'Respostas a revisar:\n{{responses_block}}',
    1,1
WHERE NOT EXISTS (SELECT 1 FROM `ai_prompts` WHERE `feature_key`='data_correction' AND `is_active`=1);

INSERT INTO `ai_prompts` (`feature_key`,`prompt_name`,`system_prompt`,`user_prompt_template`,`version`,`is_active`)
SELECT 'smart_fill','Preenchimento padrão',
    'Você é um assistente de coleta. Dadas respostas já preenchidas, sugira valores plausíveis para perguntas pendentes que possam ser inferidas com alta confiança. NÃO sugira se houver dúvida. JSON: {"suggestions":[{"question_id":..,"value":"..","confidence":0-1,"reason":".."}]}.',
    'Já respondidas:\n{{existing_block}}\n\nPendentes:\n{{pending_block}}',
    1,1
WHERE NOT EXISTS (SELECT 1 FROM `ai_prompts` WHERE `feature_key`='smart_fill' AND `is_active`=1);

INSERT INTO `ai_prompts` (`feature_key`,`prompt_name`,`system_prompt`,`user_prompt_template`,`version`,`is_active`)
SELECT 'question_reformulation','Reformulação padrão',
    'Você é um especialista em redação de pesquisas. Reescreva a pergunta atendendo ao objetivo, mantendo a intenção. JSON: {"reformulated":"...","rationale":"..."}.',
    'Pergunta original: {{question_text}}\nObjetivo: {{objective}}',
    1,1
WHERE NOT EXISTS (SELECT 1 FROM `ai_prompts` WHERE `feature_key`='question_reformulation' AND `is_active`=1);

INSERT INTO `ai_prompts` (`feature_key`,`prompt_name`,`system_prompt`,`user_prompt_template`,`version`,`is_active`)
SELECT 'adaptive_routing','Roteamento padrão',
    'Você desenha lógica condicional. Proponha regras (source→target) baseadas em padrões esperados de respostas. JSON: {"rules":[{"source_question_id":..,"target_question_id":..,"condition":{..},"priority":..,"rationale":".."}]}.',
    'Questionário:\n{{questionnaire_block}}',
    1,1
WHERE NOT EXISTS (SELECT 1 FROM `ai_prompts` WHERE `feature_key`='adaptive_routing' AND `is_active`=1);

INSERT INTO `ai_prompts` (`feature_key`,`prompt_name`,`system_prompt`,`user_prompt_template`,`version`,`is_active`)
SELECT 'followup_suggestions','Follow-up padrão',
    'Sugira perguntas de follow-up úteis para aprofundar respostas. JSON: {"suggestions":[{"question_id":..,"text":"..","type":"text|radio|checkbox","options":[],"rationale":".."}]}.',
    'Pergunta atual: {{question_text}}\nRespostas recebidas (amostra): {{sample_block}}',
    1,1
WHERE NOT EXISTS (SELECT 1 FROM `ai_prompts` WHERE `feature_key`='followup_suggestions' AND `is_active`=1);

INSERT INTO `ai_prompts` (`feature_key`,`prompt_name`,`system_prompt`,`user_prompt_template`,`version`,`is_active`)
SELECT 'statistical_analysis','Análise padrão',
    'Você é analista de dados. Identifique padrões, outliers, tendências. JSON: {"summary":"..","patterns":[],"outliers":[],"trends":[],"insights":[],"chart_suggestions":[]}.',
    'Questionário: {{questionnaire_title}}\nResumo agregado:\n{{aggregate_block}}',
    1,1
WHERE NOT EXISTS (SELECT 1 FROM `ai_prompts` WHERE `feature_key`='statistical_analysis' AND `is_active`=1);

INSERT INTO `ai_prompts` (`feature_key`,`prompt_name`,`system_prompt`,`user_prompt_template`,`version`,`is_active`)
SELECT 'natural_reports','Relatório padrão',
    'Você produz relatórios narrativos em PT-BR. Use seções claras. JSON: {"title":"..","narrative":"..","sections":[{"heading":"..","body":".."}],"charts":[]}.',
    'Questionário: {{questionnaire_title}}\nFiltros: {{filters_block}}\nDados:\n{{data_block}}',
    1,1
WHERE NOT EXISTS (SELECT 1 FROM `ai_prompts` WHERE `feature_key`='natural_reports' AND `is_active`=1);

-- =====================================================================
-- Permissões (RODE só se o usuário da migration for diferente do app)
-- =====================================================================
-- GRANT SELECT, INSERT, UPDATE, DELETE ON `hg75df38_institutoleandrolima`.*
--   TO 'hg75df38_institutoleandrolima'@'localhost';
-- FLUSH PRIVILEGES;
