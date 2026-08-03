-- =====================================================================
-- Limpeza: remove duplicatas em question_options
-- Banco: MySQL 5.7+/8.0
-- =====================================================================
-- Contexto: o editor de questionários não enviava o `id` das opções ao
-- salvar. Como resultado, cada save do questionário INSERIA todas as
-- alternativas de novo — criando duplicatas visíveis no editor da
-- estratificação.
--
-- Estratégia (segura para produção):
--   1) Identificar duplicatas por (question_id, option_value)
--   2) Manter a linha de MENOR id de cada grupo (a original)
--   3) Marcar as demais como `is_active = 0` (soft-delete)
--      → não apaga de fato porque respostas antigas em question_responses
--        gravam apenas o `option_value`, então nenhum histórico se perde,
--        mas se algum dia quiser deletar de vez basta filtrar is_active=0.
--
-- Requer que a coluna `question_options.is_active` já exista
-- (migration `question_options_soft_delete.sql`).
--
-- FAÇA BACKUP DO BANCO ANTES DE EXECUTAR.
-- =====================================================================


-- ---------------------------------------------------------------------
-- 1) Diagnóstico — mostra quantas duplicatas existem por pergunta
-- ---------------------------------------------------------------------
SELECT
    question_id,
    option_value,
    COUNT(*)                    AS total_linhas,
    MIN(id)                     AS id_a_manter,
    GROUP_CONCAT(id ORDER BY id) AS todos_os_ids
FROM question_options
WHERE (is_active IS NULL OR is_active = 1)
GROUP BY question_id, option_value
HAVING COUNT(*) > 1
ORDER BY question_id, option_value;
-- Se retornar 0 linhas: sem duplicatas ativas, pule os próximos blocos.


-- ---------------------------------------------------------------------
-- 2) Desativar duplicatas (mantém apenas o MENOR id de cada grupo)
-- ---------------------------------------------------------------------
UPDATE question_options qo
JOIN (
    SELECT
        question_id,
        option_value,
        MIN(id) AS keep_id
    FROM question_options
    WHERE (is_active IS NULL OR is_active = 1)
    GROUP BY question_id, option_value
    HAVING COUNT(*) > 1
) dups
    ON qo.question_id  = dups.question_id
   AND qo.option_value = dups.option_value
   AND qo.id           <> dups.keep_id
SET qo.is_active = 0;


-- ---------------------------------------------------------------------
-- 3) Conferência final — não deve haver mais duplicatas ativas
-- ---------------------------------------------------------------------
SELECT
    question_id,
    option_value,
    COUNT(*) AS total
FROM question_options
WHERE (is_active IS NULL OR is_active = 1)
GROUP BY question_id, option_value
HAVING COUNT(*) > 1;
-- Resultado esperado: 0 linhas.
