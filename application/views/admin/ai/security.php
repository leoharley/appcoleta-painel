<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1">Segurança & diagnóstico</h2><p class="text-muted mb-0">Estado do cliente, atividade recente e ferramentas de teste</p></div>
    <a href="<?= base_url('ai') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">Cliente OpenAI</small>
                <h4 class="mb-0">
                    <?php if ($configured): ?>
                        <span class="text-success"><i class="fas fa-check-circle"></i> Configurado</span>
                    <?php else: ?>
                        <span class="text-danger"><i class="fas fa-times-circle"></i> Sem chave</span>
                    <?php endif; ?>
                </h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">Execuções (7d)</small>
                <h4 class="mb-0"><?= (int)($execution_stats['totals']->total ?? 0) ?>
                    <small class="text-success"><?= (int)($execution_stats['totals']->success_count ?? 0) ?> ok</small>
                    <small class="text-danger ms-1"><?= (int)($execution_stats['totals']->error_count ?? 0) ?> erros</small>
                </h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">Custo (7d)</small>
                <h4 class="mb-0">US$ <?= number_format((float)($execution_stats['totals']->cost_total ?? 0), 4) ?></h4>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Ferramentas de teste</strong></div>
            <div class="card-body">
                <div class="mb-3">
                    <button class="btn btn-outline-success btn-sm" id="btnTest"><i class="fas fa-plug me-1"></i> Testar conexão</button>
                    <div id="testOut" class="small mt-2"></div>
                </div>
                <hr>
                <div>
                    <label class="form-label small">Echo de IA (debug_analyze)</label>
                    <div class="input-group">
                        <input type="text" class="form-control form-control-sm" id="echoIn" value="ping">
                        <button class="btn btn-outline-primary btn-sm" id="btnEcho">Enviar</button>
                    </div>
                    <pre class="mt-2 bg-light p-2 rounded small" id="echoOut">—</pre>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Lote em massa (batch_analyze)</strong></div>
            <div class="card-body">
                <p class="text-muted small">Roda detecção de inconsistências nas N respostas mais recentes de um questionário.</p>
                <form id="batchForm" class="row g-2 align-items-end">
                    <div class="col-md-12">
                        <label class="form-label small mb-1">Questionário</label>
                        <select class="form-select form-select-sm" id="selQuestionnaire" name="questionnaire_id" required></select>
                    </div>
                    <div class="col-md-4 mt-2"><label class="form-label small mb-1">Limite (respostas mais recentes)</label><input type="number" min="1" max="50" class="form-control form-control-sm" name="limit" value="10"></div>
                    <div class="col-md-8 mt-2 d-flex align-items-end"><button class="btn btn-primary btn-sm w-100"><i class="fas fa-bolt me-1"></i> Rodar</button></div>
                </form>
                <div id="batchOut" class="small mt-2"></div>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/ai_selects.js') ?>"></script>
<script>
(function() {
    const base = '<?= base_url() ?>';
    if (document.getElementById('selQuestionnaire')) {
        AiSelects.questionnaire(document.getElementById('selQuestionnaire'));
    }
    document.getElementById('btnTest').addEventListener('click', function() {
        document.getElementById('testOut').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        fetch(base + 'ai/test_connection', { method:'POST', credentials:'same-origin' })
            .then(r=>r.json()).then(d => {
                document.getElementById('testOut').innerHTML = d.success
                    ? '<span class="text-success">' + d.message + '</span>'
                    : '<span class="text-danger">' + (d.message||'Falha') + '</span>';
            });
    });
    document.getElementById('btnEcho').addEventListener('click', function() {
        const payload = document.getElementById('echoIn').value;
        document.getElementById('echoOut').textContent = '...';
        fetch(base + 'ai/debug_analyze', { method:'POST', credentials:'same-origin', body: new URLSearchParams({ payload }) })
            .then(r=>r.json()).then(d => { document.getElementById('echoOut').textContent = JSON.stringify(d, null, 2); });
    });
    document.getElementById('batchForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const out = document.getElementById('batchOut');
        out.innerHTML = '<i class="fas fa-spinner fa-spin"></i> processando…';
        fetch(base + 'ai/batch_analyze', { method:'POST', credentials:'same-origin', body: new URLSearchParams(new FormData(this)) })
            .then(r=>r.json()).then(d => {
                out.innerHTML = d.success
                    ? '<span class="text-success">Processadas ' + d.processed + ', ' + d.created + ' inconsistências criadas.</span>'
                    : '<span class="text-danger">' + (d.message||'Falha') + '</span>';
            });
    });
})();
</script>
