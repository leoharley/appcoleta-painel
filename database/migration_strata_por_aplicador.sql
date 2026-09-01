-- =====================================================================
-- Metas de perguntas estratificadas POR APLICADOR
-- =====================================================================
-- Objetivo: permitir cota individual explicita por aplicador para cada
-- opcao de uma pergunta. Ex.: Aplicador2 -> Masculino 5, Feminino 7.
--
-- Modelo: stratification_strata.target_quota continua sendo a META GERAL
-- (soma de todos). A tabela abaixo DETALHA essa meta por aplicador.
-- Nao ha alteracao destrutiva: estratificacoes existentes continuam
-- funcionando exatamente como antes (sem linhas nesta tabela = sem cota
-- individual definida).
--
-- Este script e IDEMPOTENTE: pode ser executado varias vezes sem erro.
-- Ja trata os dois erros comuns:
--   #3780 - tipos incompativeis na FK (resolvido pelo passo 2, que copia
--           o tipo exato das tabelas referenciadas)
--   #1826 - constraint duplicada (resolvido pelo passo 3, que so cria a
--           FK se ela ainda nao existir)
--
-- Faca BACKUP do banco antes de executar.
-- =====================================================================


-- ---------------------------------------------------------------------
-- 1) Tabela base (sem FK ainda)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stratification_strata_applicator` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `stratum_id`   INT NOT NULL,
    `user_id`      INT NOT NULL,
    `target_quota` INT NOT NULL DEFAULT 0,
    `created_at`   DATETIME NULL DEFAULT NULL,
    UNIQUE KEY `uq_stratum_user` (`stratum_id`, `user_id`),
    KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ---------------------------------------------------------------------
-- 2) Alinhar os tipos de stratum_id e user_id aos das tabelas
--    referenciadas (INT, INT UNSIGNED ou BIGINT — detectado sozinho).
-- ---------------------------------------------------------------------
SET @t_stratum = (SELECT COLUMN_TYPE FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'stratification_strata'
                    AND COLUMN_NAME = 'id');

SET @t_user = (SELECT COLUMN_TYPE FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'users'
                 AND COLUMN_NAME = 'id');

SET @sql = CONCAT('ALTER TABLE `stratification_strata_applicator` ',
                  'MODIFY `stratum_id` ', @t_stratum, ' NOT NULL, ',
                  'MODIFY `user_id` ',    @t_user,    ' NOT NULL');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- ---------------------------------------------------------------------
-- 3) Criar as foreign keys APENAS se ainda nao existirem
-- ---------------------------------------------------------------------
SET @fk1 = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'stratification_strata_applicator'
              AND CONSTRAINT_NAME = 'fk_ssa_stratum');

SET @sql = IF(@fk1 = 0,
    'ALTER TABLE `stratification_strata_applicator`
        ADD CONSTRAINT `fk_ssa_stratum`
        FOREIGN KEY (`stratum_id`) REFERENCES `stratification_strata` (`id`)
        ON DELETE CASCADE',
    'SELECT ''fk_ssa_stratum ja existe — nada a fazer'' AS aviso');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


SET @fk2 = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'stratification_strata_applicator'
              AND CONSTRAINT_NAME = 'fk_ssa_user');

SET @sql = IF(@fk2 = 0,
    'ALTER TABLE `stratification_strata_applicator`
        ADD CONSTRAINT `fk_ssa_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE',
    'SELECT ''fk_ssa_user ja existe — nada a fazer'' AS aviso');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- ---------------------------------------------------------------------
-- 4) Conferencia final
-- ---------------------------------------------------------------------
SELECT
    (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'stratification_strata_applicator')      AS tabela_existe,
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'stratification_strata_applicator'
        AND CONSTRAINT_TYPE = 'FOREIGN KEY')                      AS total_fks,
    (SELECT COUNT(*) FROM `stratification_strata_applicator`)      AS linhas;
-- Esperado: tabela_existe = 1, total_fks = 2, linhas = 0 (na 1a vez)
