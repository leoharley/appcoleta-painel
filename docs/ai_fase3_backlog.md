# Módulo IA — Backlog da Fase 3 (refinamentos)

Documento de continuidade depois das **Fases 1 (fundação)** e **2 (features)** do módulo `/ai`.
Tudo aqui é refinamento — o módulo está funcional sem.

> **Pré-requisito:** ler primeiro o que já existe.
> - Migration: `database/sql/ai_module.sql`
> - Library OpenAI: `application/libraries/Ai_service.php`
> - Library prompts: `application/libraries/Ai_prompt_service.php`
> - Model: `application/models/Ai_model.php`
> - Controller admin: `application/controllers/Ai.php`
> - Controller API mobile: `application/controllers/api/Ai.php`
> - Views: `application/views/admin/ai/*.php` (18 telas)

---

## 1. Endpoints API mobile que ficaram como stub

Localização: `application/controllers/api/Ai.php`, método `stub()`.

Hoje retornam `{"success": false, "message": "Endpoint X ainda não implementado nesta fase."}` se a feature estiver habilitada, ou `"Feature X desativada."` se não.

### 1.1 `POST /api/ai/transcriptions_sync`
**Propósito:** o app envia em lote transcrições feitas offline (gravadas no celular, transcritas localmente ou apenas armazenadas) para o painel persistir e dedup por `app_id`.

**Contrato sugerido:**
```json
POST /api/ai/transcriptions_sync
{
  "items": [
    {
      "app_id": "uuid-v4",
      "form_response_id": 123,
      "question_id": 45,
      "questionnaire_id": 7,
      "transcription_text": "...",
      "duration_ms": 12340,
      "timestamp_app": "2026-06-01T14:23:00Z"
    }
  ]
}
```

**Implementar:**
- Loop sobre `items`, fazer `UPSERT` por `app_id` (a coluna já é `UNIQUE` na tabela).
- Retornar `{success, inserted, updated, skipped}`.
- Rate-limit via `Ai_model->check_rate_limit($user_id, 'transcriptions_sync', 30)`.

### 1.2 `POST /api/ai/standardize` (data_correction)
**Propósito:** o app envia uma resposta finalizada (ou um campo) e recebe sugestões de correção em tempo real para mostrar ao aplicador antes de confirmar.

**Contrato:**
```json
POST /api/ai/standardize
{ "form_response_id": 123 }
```
**Resposta:** mesma forma que o admin `analyze_corrections()` retorna (lista de correções). Reaproveitar `ai_prompt_service->prepare_correction_data()` + `ai_service->chat_completion_json('data_correction', ...)`.

### 1.3 `POST /api/ai/smart_suggestions` (smart_fill no app)
**Contrato:**
```json
POST /api/ai/smart_suggestions
{
  "questionnaire_id": 7,
  "answers": { "12": "São Paulo", "15": "Centro" },
  "pending_question_ids": [18, 19, 22]
}
```
A lógica já está em `Ai::generate_suggestions()` — basta espelhar para o app, retornando JSON em vez de persistir.

### 1.4 `POST /api/ai/reformulate_questions`
**Propósito:** o app pede uma reformulação ad-hoc de uma pergunta para o aplicador (sem persistir no banco do painel).
**Contrato:** `{ question_id, objective }`. Reaproveita `prepare_reformulation_data` + `chat_completion_json('question_reformulation', ...)`.

### 1.5 `POST /api/ai/adaptive_next`
**Propósito:** o app envia o estado atual do formulário e recebe a próxima pergunta sugerida (skip-logic em tempo real).
**Contrato:**
```json
POST /api/ai/adaptive_next
{ "questionnaire_id": 7, "current_question_id": 12, "answers": {"12": "Não"} }
```
**Implementação local:** ler `ai_adaptive_rules` aprovadas (`is_active=1`) para `questionnaire_id`, avaliar `condition_logic` em PHP contra `answers`, retornar `target_question_id` ou `fallback_target_id`. **Não precisa de chamada à OpenAI** — é só execução das regras já aprovadas.

### 1.6 `POST /api/ai/follow_up`
**Contrato:** `{ questionnaire_id, question_id }`. Reaproveita `prepare_followup_data` + `followup_suggestions`. Retorna sem persistir; o aplicador escolhe se aplica.

### 1.7 `POST /api/ai/generate_report` (mobile)
**Propósito:** gerar relatório para visualização in-app (não persistir). Mesma lógica de `Ai::generate_report()` mas retornando o conteúdo direto.

### 1.8 `GET /api/ai/approved_suggestions`
Hoje devolve lista vazia. Refinar: retornar `ai_field_suggestions` e `ai_followup_suggestions` com `status='approved'` filtrados por `questionnaire_id` (GET param) — útil para o app pré-carregar sugestões aprovadas pelo gestor.

---

## 2. Cache de respostas (ai_cache)

Tabela e métodos prontos no `Ai_model`: `get_cache`, `set_cache`, `clear_expired_cache`.

**O que falta:** plugar em chamadas determinísticas. Bons candidatos:
- `question_reformulation` quando `objective` é fixo (cache de 7 dias por `question_id + objective`)
- `adaptive_routing` (cache de 1 dia por `questionnaire_id` desde que o questionário não mude — invalidar em `Questionnaires::update`)

**Onde plugar:** dentro de `Ai_service::chat_completion_json`, adicionar `if ($options['cache_key'] ?? null) { ... }` checando antes de bater na OpenAI e gravando depois.

**NÃO usar cache em:**
- `inconsistency_detection` (depende de respostas específicas)
- `data_correction` (idem)
- `statistical_analysis` / `natural_reports` (varia com dados)

---

## 3. Rate-limit ativo nos endpoints da API mobile

Método `Ai_model->check_rate_limit($user_id, $endpoint, $max_per_minute)` existe e popula `ai_rate_limits`.

**Plugar em:**
- `api/Ai::transcribe()` — limite 10/min por usuário (custos)
- `api/Ai::check_inconsistencies()` — limite 30/min
- `api/Ai::suggest_fill()` — limite 30/min
- `api/Ai::audit_log_batch()` — limite 5/min (já é batch)

**Padrão:**
```php
if (!$this->Ai_model->check_rate_limit($user_id, 'transcribe', 10)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Rate limit excedido. Aguarde 1 minuto.']);
    return;
}
```

**Manutenção:** rodar `Ai_model->cleanup_rate_limits()` em um cron diário (apaga linhas > 1h).

---

## 4. Gráficos reais em `/ai/charts`

Hoje a tela [application/views/admin/ai/charts.php](../application/views/admin/ai/charts.php) só lista as **descrições** de gráficos sugeridas pela IA.

**Para virar gráfico real:**
1. Padronizar o que `statistical_analysis` retorna em `chart_suggestions`:
   ```json
   [
     {
       "type": "bar",
       "title": "Distribuição por gênero",
       "x_axis": "label",
       "y_axis": "count",
       "data_source": {
         "table": "question_responses",
         "question_id": 12,
         "aggregate": "count_by_option"
       }
     }
   ]
   ```
2. No backend, adicionar endpoint `Ai::chart_data($chart_index, $analysis_id)` que, lendo o `data_source`, monta o array `{labels: [], datasets: []}` no formato Chart.js.
3. Na view, incluir `<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>` e renderizar com `new Chart(ctx, {...})`.
4. Atualizar o prompt `smart_charts` em `ai_settings`/`ai_prompts` para forçar esse contrato JSON.

**Decisão pendente:** queremos que o admin possa **editar** o gráfico (filtros, tipo) e regerar, ou só visualizar o que a IA sugeriu?

---

## 5. Exportação de relatório (PDF/DOCX)

`ai_reports` já guarda `narrative_text + sections + charts_data`. Falta exportar.

**Opções:**
- **PDF:** integrar `tcpdf` ou `dompdf` (composer). Render do HTML do `report_view.php` → PDF.
- **DOCX:** `phpoffice/phpword`. Mais trabalho mas resultado editável.
- **Markdown:** trivial, gera download `.md` direto da `narrative_text` + `sections` — bom como MVP.

**Sugestão:** começar pelo MD; PDF depois com `dompdf` se necessário.

Adicionar no controller:
```php
public function export_report($id, $format = 'md') { ... }
```
E botão na `report_view.php`. Atualizar `ai_reports.exported_at` quando exportar.

---

## 6. UX e refinos visuais

### 6.1 Tabela de respostas no menu de inconsistências/correções
Hoje o admin precisa **saber o `form_response_id`** para analisar. Melhoria: lista de respostas mais recentes com botão "Analisar com IA" inline.

**Onde:** em `application/views/admin/responses/view.php` (já existe a tela `responses/view/:id`), adicionar uma seção "Análise IA" com botões:
- Detectar inconsistências
- Sugerir correções
- Ver inconsistências já registradas (link para `/ai/inconsistencies?form_response_id=X`)

### 6.2 Filtros nas listas de pendências
As views `inconsistencies.php`, `corrections.php`, `smart_fill.php`, `followup.php`, `reformulations.php` listam **tudo**. Adicionar:
- Filtro por status (pending/approved/rejected/resolved)
- Filtro por questionário
- Filtro por intervalo de datas
- Paginação (todas estão limitadas a 200 fixo no controller)

### 6.3 Modal de detalhe da inconsistência
Hoje a coluna "Descrição" só mostra `mb_strimwidth` (240 chars). Modal com:
- Descrição completa
- Justificativa da IA
- Lista clicável de perguntas afetadas (`affected_questions` JSON)
- Ação sugerida
- Campo de notas de resolução

### 6.4 Histórico de versões de prompt
Hoje `ai_prompts` tem `version`, mas a UI [application/views/admin/ai/prompts.php](../application/views/admin/ai/prompts.php) só mostra o ativo. Adicionar:
- Lista de versões anteriores por feature
- Diff visual entre versões
- Botão "Reverter para essa versão"

### 6.5 Dashboard de custos por dia
[application/views/admin/ai/dashboard.php](../application/views/admin/ai/dashboard.php) tem totais de 30d. Adicionar gráfico de linha (Chart.js) com custo diário dos últimos 30 dias — ajuda a detectar pico anômalo.

---

## 7. Diretrizes — categorias predefinidas

Hoje `ai_directives.category` é texto livre. Padronizar com `<select>` na view de criação:
- `tom_voz` — "use linguagem coloquial brasileira", "evite jargão"
- `escopo_dados` — "nunca inferir CPF", "não sugerir endereço completo"
- `comportamento` — "se confiança < 0.7, recuse a sugestão"
- `formato_saida` — "sempre cite a pergunta original entre aspas"

E mostrar quais diretivas foram **efetivamente injetadas** em cada `ai_execution_log` (gravar `injected_directive_ids` no `input_data`).

---

## 8. Logs com payload completo (debug)

`ai_execution_logs.input_data` já guarda o payload enviado. Mas `output_data` só guarda o `content` (string da resposta). Para debugging real, considerar guardar também `raw` (o JSON completo da OpenAI, incluindo `usage`, `finish_reason`, etc.) em uma coluna `raw_response LONGTEXT`.

**Migration:**
```sql
ALTER TABLE `ai_execution_logs` ADD COLUMN `raw_response` LONGTEXT NULL AFTER `output_data`;
```

**Ai_service.php:** após `$data = json_decode($body, true);`, gravar `$data` (ou substring) em `raw_response` no `update_execution_log`.

**Risco:** consumo de espaço — uma resposta pode ser 50KB+. Truncar a 100KB ou só ativar via setting `debug_log_full_response`.

---

## 9. CORS e segurança da API

`api/Ai.php` envia `Access-Control-Allow-Origin: *` por simplicidade. Em produção:
- Restringir a domínios conhecidos (app móvel via webview se aplicável)
- Validar `Origin` antes de aceitar `OPTIONS`
- Considerar JWT/HMAC no lugar do token base64 atual (`api/Forms.php` usa o mesmo padrão fraco — esse é um problema mais amplo do projeto, não específico do módulo IA)

---

## 10. Cron jobs sugeridos

Adicionar em `application/controllers/Cron.php` (se já existe um, senão criar):

```php
public function ai_cleanup() {
    if ($this->input->is_cli_request() === false) show_error('CLI only', 403);
    $this->load->model('Ai_model');
    $this->Ai_model->clear_expired_cache();
    $this->Ai_model->cleanup_rate_limits();
    echo "OK\n";
}
```

Configurar no painel da hospedagem:
```
0 3 * * * /usr/bin/php /home/.../index.php cron ai_cleanup
```

---

## 11. Testes manuais para validar a Fase 3

Quando implementar cada bloco acima, rodar:

- [ ] `POST /api/ai/transcriptions_sync` com 2 items, sendo 1 duplicado (mesmo `app_id`) → deve responder `inserted: 1, updated: 1` ou `skipped: 1`.
- [ ] `POST /api/ai/adaptive_next` com regra ativa que case → retorna `target_question_id` correto.
- [ ] Rate limit: chamar `/api/ai/transcribe` 11 vezes em 1 minuto → 11ª retorna `429`.
- [ ] Reformular a mesma pergunta com mesmo objective duas vezes seguidas → 2ª chamada sai do cache (zero tokens em `ai_execution_logs`).
- [ ] Exportar relatório em MD → arquivo gera e `ai_reports.exported_at` é populado.
- [ ] Gráficos: gerar análise nova → `/ai/charts` renderiza gráfico Chart.js, não só texto.
- [ ] Dashboard: gráfico de custo por dia aparece e bate com `SUM(cost_usd) GROUP BY DATE(created_at)`.

---

## 12. Ordem sugerida de execução

Por valor entregue × esforço:

1. **Plugar rate-limit nos endpoints API** (1h, alto valor — protege custo OpenAI)
2. **Adicionar botão "Analisar com IA" na tela de respostas** (2h, UX enorme — admin não precisa mais saber IDs)
3. **Filtros e paginação nas listas de pendências** (3h, qualidade de vida)
4. **Endpoints `adaptive_next` + `smart_suggestions` + `standardize`** (4h, libera o app)
5. **Cache em reformulações** (1h, economiza tokens)
6. **Exportação MD de relatório** (2h, fecha o fluxo)
7. **Gráficos reais Chart.js** (6-8h, com decisão de contrato)
8. **Histórico de prompts + diff** (4h, debugability)
9. **PDF de relatório (dompdf)** (4h)
10. **CORS estrito + revisão de auth** (4h, segurança)

Total estimado: ~30h de trabalho focado.

---

## Mapeamento de FKs (referência rápida)

Já documentado na entrega da Fase 1; recopiando para consulta sem ter que abrir histórico:

| Tabela do núcleo | Usada por |
|---|---|
| `users.id` | `ai_prompts.created_by`, `ai_execution_logs.executed_by`, `ai_transcriptions.processed_by/applicator_id`, `ai_inconsistencies.resolved_by`, `ai_corrections.reviewed_by`, `ai_field_suggestions.reviewed_by`, `ai_reformulations.approved_by`, `ai_adaptive_rules.approved_by`, `ai_followup_suggestions.reviewed_by`, `ai_statistical_analyses.generated_by`, `ai_reports.generated_by`, `ai_directives.created_by`, `question_followup_tips.created_by`, `ai_audit_logs.user_id`, `ai_rate_limits.user_id` |
| `questionnaires.id` | `ai_transcriptions`, `ai_inconsistencies`, `ai_field_suggestions`, `ai_adaptive_rules`, `ai_followup_suggestions`, `ai_statistical_analyses`, `ai_reports`, `ai_audit_logs` |
| `questions.id` | `ai_transcriptions`, `ai_corrections`, `ai_field_suggestions`, `ai_reformulations`, `ai_adaptive_rules` (source/target/fallback), `ai_followup_suggestions` (question_id/added_as_question_id), `question_followup_tips`, `ai_audit_logs` |
| `form_responses.id` | `ai_transcriptions`, `ai_inconsistencies`, `ai_corrections`, `ai_field_suggestions` |
| `question_responses.id` | `ai_corrections` |
