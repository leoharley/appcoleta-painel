-- =====================================================================
-- Patch: eliminar NULL em stratification_categories.question_id
-- =====================================================================
-- Causa do erro no app:
--   "Estratificação: type 'Null' is not a subtype of type 'int' in type cast"
-- O app faz parse de question_id como int não-nulo, mas no banco
-- categorias com dimension_type = 'applicator' ou 'weekday' têm
-- question_id = NULL. Vamos trocar NULL por 0 (sentinel) — o PHP só usa
-- question_id quando dimension_type = 'question', então 0 nas outras
-- dimensões é inerte.
--
-- Faça BACKUP do banco antes de executar.
-- =====================================================================


-- ---------------------------------------------------------------------
-- 1) Diagnóstico — quantas linhas estão com NULL?
-- ---------------------------------------------------------------------
SELECT
    dimension_type,
    COUNT(*) AS total,
    SUM(question_id IS NULL) AS com_null
FROM stratification_categories
GROUP BY dimension_type;


-- ---------------------------------------------------------------------
-- 2) Descobrir o nome da FK em question_id (precisa dropar antes de
--    gravar 0, porque 0 não existe em `questions`)
-- ---------------------------------------------------------------------
SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_NAME    = 'stratification_categories'
  AND COLUMN_NAME   = 'question_id'
  AND TABLE_SCHEMA  = DATABASE()
  AND REFERENCED_TABLE_NAME IS NOT NULL;
-- Anote o CONSTRAINT_NAME que aparecer (algo como
-- `stratification_categories_question_id_fkey`).


-- ---------------------------------------------------------------------
-- 3) Dropar a FK (troque o nome se for diferente do que apareceu acima)
-- ---------------------------------------------------------------------
ALTER TABLE `stratification_categories`
    DROP FOREIGN KEY `stratification_categories_question_id_fkey`;


-- ---------------------------------------------------------------------
-- 4) Substituir NULL por 0 e travar a coluna como NOT NULL
-- ---------------------------------------------------------------------
UPDATE `stratification_categories`
   SET `question_id` = 0
 WHERE `question_id` IS NULL;

ALTER TABLE `stratification_categories`
    MODIFY `question_id` INT NOT NULL DEFAULT 0;


-- ---------------------------------------------------------------------
-- 5) (Opcional) Recriar a FK com ON DELETE SET DEFAULT — mantém a
--    integridade quando dimension_type = 'question' e permite valor 0
--    nas demais dimensões.
--    Se preferir simplicidade, pule este passo (o LEFT JOIN do PHP
--    em `stratification_categories.question_id = questions.id` continua
--    funcionando, e dimensões 'applicator'/'weekday' nunca consultam
--    questions pelo question_id).
-- ---------------------------------------------------------------------
-- Para recriar precisaria que exista uma row em `questions` com id = 0,
-- o que normalmente não há. Por isso o recomendado é NÃO recriar a FK
-- e confiar na lógica de dimension_type no app/backend.


-- ---------------------------------------------------------------------
-- 6) Conferência final — não pode haver mais NULL
-- ---------------------------------------------------------------------
SELECT COUNT(*) AS ainda_com_null
FROM stratification_categories
WHERE question_id IS NULL;
-- Esperado: 0
