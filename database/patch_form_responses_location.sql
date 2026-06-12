-- =====================================================================
-- Patch: garantir colunas de localização em `form_responses`
-- =====================================================================
-- O painel já lê e exibe essas colunas em vários lugares
-- (responses/view.php, responses/index.php, Reports.php, etc.),
-- então elas devem existir. Este script é uma checagem + patch
-- defensivo, caso a tabela esteja desatualizada em algum ambiente.
-- =====================================================================


-- ---------------------------------------------------------------------
-- 1) Verificar quais colunas já existem
-- ---------------------------------------------------------------------
SELECT
    COLUMN_NAME,
    DATA_TYPE,
    NUMERIC_PRECISION,
    NUMERIC_SCALE,
    IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'form_responses'
  AND COLUMN_NAME IN ('latitude', 'longitude', 'location_name');
-- Se o resultado já mostrar as 3 linhas (latitude DECIMAL(10,8),
-- longitude DECIMAL(11,8), location_name VARCHAR), NÃO precisa rodar
-- os ALTER abaixo.


-- ---------------------------------------------------------------------
-- 2) Criar as colunas que faltarem (descomente o que precisar)
-- ---------------------------------------------------------------------
-- DECIMAL(10,8): faixa -90 a 90 com ~1mm de precisão (latitude)
-- DECIMAL(11,8): faixa -180 a 180 com ~1mm de precisão (longitude)
-- ATENÇÃO: NÃO usar FLOAT (perde precisão das coordenadas).

-- ALTER TABLE `form_responses`
--     ADD COLUMN `latitude` DECIMAL(10, 8) NULL AFTER `applied_by`;

-- ALTER TABLE `form_responses`
--     ADD COLUMN `longitude` DECIMAL(11, 8) NULL AFTER `latitude`;

-- ALTER TABLE `form_responses`
--     ADD COLUMN `location_name` VARCHAR(255) NULL AFTER `longitude`;


-- ---------------------------------------------------------------------
-- 3) Sanidade: corrigir registros antigos que tenham (0, 0) gravado
--    como coordenada inválida (vem do bug antigo "Golfo da Guiné")
-- ---------------------------------------------------------------------
-- Quantos registros estão exatamente em (0,0)?
SELECT COUNT(*) AS registros_em_zero_zero
FROM form_responses
WHERE latitude = 0 AND longitude = 0;

-- Para limpar (descomente se a contagem acima for > 0):
-- UPDATE form_responses
--    SET latitude = NULL, longitude = NULL, location_name = NULL
--  WHERE latitude = 0 AND longitude = 0;


-- ---------------------------------------------------------------------
-- 4) Conferir um envio de teste (5 últimos registros)
-- ---------------------------------------------------------------------
SELECT id, questionnaire_id, applied_by,
       latitude, longitude, location_name,
       completed_at
  FROM form_responses
 ORDER BY id DESC
 LIMIT 5;
