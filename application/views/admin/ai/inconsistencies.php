<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1">Inconsistências</h2><p class="text-muted mb-0">Detectadas pela IA em respostas finalizadas</p></div>
    <a href="<?= base_url('ai') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form id="analyzeForm" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small mb-1">Questionário</label>
                <select class="form-select form-select-sm" id="selQuestionnaire" required>
                    <option value="">Carregando…</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small mb-1">Resposta</label>
                <select class="form-select form-select-sm" name="form_response_id" id="selResponse" required disabled>
                    <option value="">Selecione um questionário primeiro</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fas fa-magic me-1"></i> Analisar</button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="btnClear" title="Limpar todas"><i class="fas fa-broom"></i></button>
            </div>
        </form>
        <div id="analyzeResult" class="small mt-2"></div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>Resposta</th><th>Severidade</th><th>Descrição</th><th>Status</th><th>Quem</th><th>Quando</th><th></th></tr>
                </thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Sem inconsistências registradas.</td></tr>
                <?php else: foreach ($items as $it): ?>
                    <tr data-id="<?= (int)$it->id ?>">
                        <td><code>#<?= (int)$it->id ?></code></td>
                        <td><small><a href="<?= base_url('responses/view/' . (int)$it->form_response_id) ?>" target="_blank">#<?= (int)$it->form_response_id ?></a></small></td>
                        <td><span class="badge bg-<?= $it->severity === 'critical' ? 'danger' : ($it->severity === 'high' ? 'warning text-dark' : 'secondary') ?>"><?= htmlspecialchars((string)$it->severity) ?></span></td>
                        <td style="max-width:480px"><small><?= htmlspecialchars((string)$it->description) ?></small></td>
                        <td><span class="badge bg-<?= $it->resolution_status === 'resolved' ? 'success' : 'secondary' ?>"><?= htmlspecialchars((string)$it->resolution_status) ?></span></td>
                        <td><small><?= htmlspecialchars((string)$it->resolved_by_name) ?></small></td>
                        <td><small><?= date('d/m H:i', strtotime($it->created_at)) ?></small></td>
                        <td>
                            <?php if ($it->resolution_status !== 'resolved'): ?>
                                <button class="btn btn-sm btn-outline-success btn-resolve" data-id="<?= (int)$it->id ?>"><i class="fas fa-check"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/ai_selects.js') ?>"></script>
<script>
(function() {
    const base = '<?= base_url() ?>';
    const out = document.getElementById('analyzeResult');

    AiSelects.questionnaire(document.getElementById('selQuestionnaire'));
    AiSelects.linkResponses(document.getElementById('selQuestionnaire'), document.getElementById('selResponse'));

    document.getElementById('analyzeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const respId = document.getElementById('selResponse').value;
        if (!respId) { out.innerHTML = '<span class="text-warning">Selecione uma resposta.</span>'; return; }
        out.innerHTML = '<i class="fas fa-spinner fa-spin"></i> analisando…';
        fetch(base + 'ai/analyze_inconsistencies', {
            method:'POST', credentials:'same-origin',
            body: new URLSearchParams({ form_response_id: respId })
        }).then(r=>r.json()).then(d => {
            out.innerHTML = d.success
                ? '<span class="text-success">' + (d.created || 0) + ' inconsistências criadas.</span>'
                : '<span class="text-danger">' + (d.message || 'Falha') + '</span>';
            if (d.success) setTimeout(() => location.reload(), 700);
        });
    });

    document.querySelectorAll('.btn-resolve').forEach(b => b.addEventListener('click', function() {
        const body = new URLSearchParams({ id: this.dataset.id, status: 'resolved' });
        fetch(base + 'ai/resolve_inconsistency', { method:'POST', credentials:'same-origin', body }).then(r=>r.json()).then(() => location.reload());
    }));

    document.getElementById('btnClear').addEventListener('click', function() {
        if (!confirm('Limpar TODAS as inconsistências registradas?')) return;
        fetch(base + 'ai/clear_inconsistencies', { method:'POST', credentials:'same-origin', body: new URLSearchParams({}) })
            .then(r=>r.json()).then(() => location.reload());
    });
})();
</script>
