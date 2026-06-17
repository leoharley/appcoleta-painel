<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1">Correções sugeridas</h2><p class="text-muted mb-0">Padronização e correção de respostas</p></div>
    <a href="<?= base_url('ai') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form id="analyzeForm" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small mb-1">Questionário</label>
                <select class="form-select form-select-sm" id="selQuestionnaire" required></select>
            </div>
            <div class="col-md-5">
                <label class="form-label small mb-1">Resposta</label>
                <select class="form-select form-select-sm" id="selResponse" required disabled></select>
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
                    <tr><th>#</th><th>Resposta</th><th>Pergunta</th><th>Original</th><th>Sugerido</th><th>Tipo</th><th>Conf.</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Sem correções.</td></tr>
                <?php else: foreach ($items as $it): ?>
                    <tr data-id="<?= (int)$it->id ?>">
                        <td><code>#<?= (int)$it->id ?></code></td>
                        <td><small><a href="<?= base_url('responses/view/' . (int)$it->form_response_id) ?>" target="_blank">#<?= (int)$it->form_response_id ?></a></small></td>
                        <td><small><?= htmlspecialchars((string)$it->question_text) ?></small></td>
                        <td><small><code><?= htmlspecialchars((string)$it->original_value) ?></code></small></td>
                        <td><small><code class="text-success"><?= htmlspecialchars((string)$it->suggested_value) ?></code></small></td>
                        <td><small><?= htmlspecialchars((string)$it->correction_type) ?></small></td>
                        <td><small><?= $it->confidence_score !== null ? number_format((float)$it->confidence_score, 2) : '—' ?></small></td>
                        <td><span class="badge bg-<?= $it->status === 'approved' ? 'success' : ($it->status === 'rejected' ? 'danger' : 'secondary') ?>"><?= htmlspecialchars((string)$it->status) ?></span></td>
                        <td>
                            <?php if ($it->status === 'pending'): ?>
                            <button class="btn btn-sm btn-outline-success btn-approve" data-id="<?= (int)$it->id ?>" data-suggested="<?= htmlspecialchars((string)$it->suggested_value, ENT_QUOTES) ?>"><i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-outline-danger btn-reject" data-id="<?= (int)$it->id ?>"><i class="fas fa-times"></i></button>
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
        out.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        fetch(base + 'ai/analyze_corrections', {
            method:'POST', credentials:'same-origin',
            body: new URLSearchParams({ form_response_id: respId })
        }).then(r=>r.json()).then(d => {
            out.innerHTML = d.success
                ? '<span class="text-success">' + (d.created || 0) + ' correções criadas.</span>'
                : '<span class="text-danger">' + (d.message || 'Falha') + '</span>';
            if (d.success) setTimeout(() => location.reload(), 700);
        });
    });
    function review(id, status, applied) {
        const body = new URLSearchParams({ id, status, applied_value: applied || '' });
        fetch(base + 'ai/review_correction', { method:'POST', credentials:'same-origin', body }).then(()=>location.reload());
    }
    document.querySelectorAll('.btn-approve').forEach(b => b.addEventListener('click', function() { review(this.dataset.id, 'approved', this.dataset.suggested); }));
    document.querySelectorAll('.btn-reject').forEach(b => b.addEventListener('click', function() { review(this.dataset.id, 'rejected'); }));
    document.getElementById('btnClear').addEventListener('click', function() {
        if (!confirm('Limpar todas as correções?')) return;
        fetch(base + 'ai/clear_corrections', { method:'POST', credentials:'same-origin', body: new URLSearchParams({}) }).then(()=>location.reload());
    });
})();
</script>
