<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1">Sugestões de follow-up</h2><p class="text-muted mb-0">Perguntas adicionais sugeridas pela IA para aprofundar coleta</p></div>
    <a href="<?= base_url('ai') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form id="genForm" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Questionário</label>
                <select class="form-select form-select-sm" id="selQuestionnaire" name="questionnaire_id" required></select>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Pergunta (opcional)</label>
                <select class="form-select form-select-sm" id="selQuestion" name="question_id" disabled></select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm w-100"><i class="fas fa-magic me-1"></i> Gerar</button>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm w-100" id="btnClear"><i class="fas fa-broom me-1"></i> Limpar</button>
            </div>
        </form>
        <div id="genResult" class="small mt-2"></div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>#</th><th>Pergunta-base</th><th>Sugestão</th><th>Tipo</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Sem sugestões.</td></tr>
                <?php else: foreach ($items as $it): ?>
                    <tr data-id="<?= (int)$it->id ?>">
                        <td><code>#<?= (int)$it->id ?></code></td>
                        <td style="max-width:240px"><small><?= htmlspecialchars((string)$it->question_text) ?></small></td>
                        <td style="max-width:380px"><small class="text-success suggestion-text"><?= htmlspecialchars((string)$it->suggested_question_text) ?></small></td>
                        <td><small><code><?= htmlspecialchars((string)$it->question_type) ?></code></small></td>
                        <td><span class="badge bg-<?= $it->status === 'approved' ? 'success' : ($it->status === 'rejected' ? 'danger' : 'secondary') ?>"><?= htmlspecialchars((string)$it->status) ?></span></td>
                        <td>
                            <?php if ($it->status === 'pending'): ?>
                                <button class="btn btn-sm btn-outline-primary btn-edit" data-id="<?= (int)$it->id ?>" data-text="<?= htmlspecialchars((string)$it->suggested_question_text, ENT_QUOTES) ?>"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-success btn-tip" data-id="<?= (int)$it->id ?>" title="Aprovar como dica"><i class="fas fa-lightbulb"></i></button>
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
    const out = (h) => document.getElementById('genResult').innerHTML = h;
    AiSelects.questionnaire(document.getElementById('selQuestionnaire'));
    AiSelects.linkQuestions(document.getElementById('selQuestionnaire'), document.getElementById('selQuestion'), { allowEmpty: true });

    document.getElementById('genForm').addEventListener('submit', function(e) {
        e.preventDefault();
        out('<i class="fas fa-spinner fa-spin"></i>');
        fetch(base + 'ai/generate_followup', { method:'POST', credentials:'same-origin', body: new URLSearchParams(new FormData(this)) })
            .then(r=>r.json()).then(d => {
                out(d.success ? '<span class="text-success">' + (d.created||0) + ' sugestões criadas.</span>'
                              : '<span class="text-danger">' + (d.message || 'Falha') + '</span>');
                if (d.success) setTimeout(()=>location.reload(), 700);
            });
    });

    function action(id, act) {
        fetch(base + 'ai/action_followup', { method:'POST', credentials:'same-origin',
            body: new URLSearchParams({ id, action: act }) }).then(()=>location.reload());
    }
    document.querySelectorAll('.btn-approve').forEach(b => b.addEventListener('click', function(){ action(this.dataset.id, 'approve'); }));
    document.querySelectorAll('.btn-reject').forEach(b => b.addEventListener('click', function(){ action(this.dataset.id, 'reject'); }));
    document.querySelectorAll('.btn-tip').forEach(b => b.addEventListener('click', function(){ action(this.dataset.id, 'add_as_tip'); }));

    document.querySelectorAll('.btn-edit').forEach(b => b.addEventListener('click', function() {
        const id = this.dataset.id;
        const text = prompt('Editar sugestão:', this.dataset.text);
        if (text === null) return;
        fetch(base + 'ai/edit_followup', { method:'POST', credentials:'same-origin',
            body: new URLSearchParams({ id, text }) }).then(()=>location.reload());
    }));

    document.getElementById('btnClear').addEventListener('click', function() {
        if (!confirm('Apagar todas as sugestões de follow-up?')) return;
        fetch(base + 'ai/clear_followups', { method:'POST', credentials:'same-origin', body: new URLSearchParams({}) }).then(()=>location.reload());
    });
})();
</script>
