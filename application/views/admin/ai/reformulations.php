<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1">Reformulação de perguntas</h2><p class="text-muted mb-0">Reescrita inteligente de questões</p></div>
    <a href="<?= base_url('ai') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="text-muted small mb-2">Reformular uma pergunta específica</h6>
        <form id="oneForm" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Questionário</label>
                <select class="form-select form-select-sm" id="selQOne"></select>
            </div>
            <div class="col-md-5">
                <label class="form-label small mb-1">Pergunta</label>
                <select class="form-select form-select-sm" id="selQuestion" name="question_id" required disabled></select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary btn-sm w-100"><i class="fas fa-magic me-1"></i> Reformular</button>
            </div>
            <div class="col-12">
                <label class="form-label small mb-1">Objetivo</label>
                <input type="text" class="form-control form-control-sm" name="objective" placeholder="ex: tornar mais clara, neutralizar viés">
            </div>
        </form>
        <hr>
        <h6 class="text-muted small mb-2">Reformular todas as perguntas de um questionário</h6>
        <form id="batchForm" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Questionário</label>
                <select class="form-select form-select-sm" id="selQBatch" name="questionnaire_id" required></select>
            </div>
            <div class="col-md-5">
                <label class="form-label small mb-1">Objetivo (todas)</label>
                <input type="text" class="form-control form-control-sm" name="objective">
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-layer-group me-1"></i> Reformular todas</button>
            </div>
        </form>
        <div id="genResult" class="small mt-2"></div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>#</th><th>Original</th><th>Reformulado</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Sem reformulações.</td></tr>
                <?php else: foreach ($items as $it): ?>
                    <tr>
                        <td><code>#<?= (int)$it->id ?></code></td>
                        <td style="max-width:300px"><small><?= htmlspecialchars((string)$it->original_text) ?></small></td>
                        <td style="max-width:380px"><small class="text-success"><?= htmlspecialchars((string)$it->reformulated_text) ?></small></td>
                        <td><span class="badge bg-<?= $it->status === 'approved' ? 'success' : 'secondary' ?>"><?= htmlspecialchars((string)$it->status) ?></span></td>
                        <td>
                            <?php if ($it->status !== 'approved'): ?>
                            <button class="btn btn-sm btn-outline-success btn-approve" data-id="<?= (int)$it->id ?>" data-apply="0" title="Aprovar"><i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-success btn-approve" data-id="<?= (int)$it->id ?>" data-apply="1" title="Aprovar e aplicar à pergunta"><i class="fas fa-bolt"></i></button>
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
    function out(html) { document.getElementById('genResult').innerHTML = html; }

    AiSelects.questionnaire(document.getElementById('selQOne'));
    AiSelects.questionnaire(document.getElementById('selQBatch'));
    AiSelects.linkQuestions(document.getElementById('selQOne'), document.getElementById('selQuestion'));

    document.getElementById('oneForm').addEventListener('submit', function(e) {
        e.preventDefault();
        out('<i class="fas fa-spinner fa-spin"></i>');
        const body = new URLSearchParams(new FormData(this));
        fetch(base + 'ai/generate_reformulation', { method:'POST', credentials:'same-origin', body })
            .then(r=>r.json()).then(d => {
                out(d.success ? '<span class="text-success">OK.</span>' : '<span class="text-danger">' + (d.message || 'Falha') + '</span>');
                if (d.success) setTimeout(()=>location.reload(), 700);
            });
    });
    document.getElementById('batchForm').addEventListener('submit', function(e) {
        e.preventDefault();
        out('<i class="fas fa-spinner fa-spin"></i> processando lote…');
        const body = new URLSearchParams(new FormData(this));
        fetch(base + 'ai/generate_reformulations_batch', { method:'POST', credentials:'same-origin', body })
            .then(r=>r.json()).then(d => {
                out(d.success
                    ? '<span class="text-success">' + (d.created || 0) + ' criadas, ' + (d.errors || 0) + ' erros.</span>'
                    : '<span class="text-danger">' + (d.message || 'Falha') + '</span>');
                if (d.success) setTimeout(()=>location.reload(), 1200);
            });
    });

    document.querySelectorAll('.btn-approve').forEach(b => b.addEventListener('click', function() {
        const body = new URLSearchParams({ id: this.dataset.id, apply: this.dataset.apply || '0' });
        fetch(base + 'ai/approve_reformulation', { method:'POST', credentials:'same-origin', body }).then(()=>location.reload());
    }));
})();
</script>
