<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1">Preenchimento inteligente</h2><p class="text-muted mb-0">Sugestões de valores para perguntas pendentes</p></div>
    <a href="<?= base_url('ai') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form id="genForm" class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label small mb-1">Questionário</label>
                <select class="form-select form-select-sm" id="selQuestionnaire" name="questionnaire_id" required></select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary btn-sm w-100"><i class="fas fa-magic me-1"></i> Gerar sugestões</button>
            </div>
        </form>
        <div id="genResult" class="small mt-2"></div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>#</th><th>Pergunta</th><th>Valor sugerido</th><th>Conf.</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Sem sugestões.</td></tr>
                <?php else: foreach ($items as $it): ?>
                    <tr>
                        <td><code>#<?= (int)$it->id ?></code></td>
                        <td><small><?= htmlspecialchars((string)$it->question_text) ?></small></td>
                        <td><code class="text-success"><?= htmlspecialchars((string)$it->suggested_value) ?></code></td>
                        <td><?= $it->confidence_score !== null ? number_format((float)$it->confidence_score, 2) : '—' ?></td>
                        <td><span class="badge bg-<?= $it->status === 'approved' ? 'success' : ($it->status === 'rejected' ? 'danger' : 'secondary') ?>"><?= htmlspecialchars((string)$it->status) ?></span></td>
                        <td>
                            <?php if ($it->status === 'pending'): ?>
                            <button class="btn btn-sm btn-outline-success btn-approve" data-id="<?= (int)$it->id ?>"><i class="fas fa-check"></i></button>
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
    AiSelects.questionnaire(document.getElementById('selQuestionnaire'));
    document.getElementById('genForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const body = new URLSearchParams(new FormData(this));
        document.getElementById('genResult').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        fetch(base + 'ai/generate_suggestions', { method:'POST', credentials:'same-origin', body })
            .then(r=>r.json()).then(d => {
                document.getElementById('genResult').innerHTML = d.success
                    ? '<span class="text-success">' + (d.created || 0) + ' sugestões criadas.</span>'
                    : '<span class="text-danger">' + (d.message || 'Falha') + '</span>';
                if (d.success) setTimeout(() => location.reload(), 700);
            });
    });
    document.querySelectorAll('.btn-approve').forEach(b => b.addEventListener('click', function() {
        fetch(base + 'ai/approve_suggestion', { method:'POST', credentials:'same-origin', body: new URLSearchParams({ id: this.dataset.id }) }).then(()=>location.reload());
    }));
    document.querySelectorAll('.btn-reject').forEach(b => b.addEventListener('click', function() {
        fetch(base + 'ai/reject_suggestion', { method:'POST', credentials:'same-origin', body: new URLSearchParams({ id: this.dataset.id }) }).then(()=>location.reload());
    }));
})();
</script>
