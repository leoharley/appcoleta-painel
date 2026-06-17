<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1">Questionário adaptativo</h2><p class="text-muted mb-0">Regras de roteamento condicional geradas pela IA</p></div>
    <a href="<?= base_url('ai') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
</div>

<form method="get" class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label small mb-1">Questionário</label>
                <select name="questionnaire_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Selecione…</option>
                    <?php foreach ($questionnaires as $q): ?>
                        <option value="<?= (int)$q->id ?>" <?= (int)$selected_qid === (int)$q->id ? 'selected' : '' ?>><?= htmlspecialchars($q->title) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($selected_qid): ?>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-primary btn-sm" id="btnGenerate"><i class="fas fa-magic me-1"></i> Gerar regras</button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="btnClear"><i class="fas fa-broom me-1"></i> Limpar</button>
            </div>
            <?php endif; ?>
        </div>
        <div id="genResult" class="small mt-2"></div>
    </div>
</form>

<?php if ($selected_qid): ?>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>#</th><th>Source</th><th>→ Target</th><th>Condição</th><th>Pri.</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($rules)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Sem regras para este questionário.</td></tr>
                <?php else: foreach ($rules as $r): ?>
                    <tr>
                        <td><code>#<?= (int)$r->id ?></code></td>
                        <td><small>Q#<?= (int)$r->source_question_id ?> <?= htmlspecialchars((string)$r->source_text) ?></small></td>
                        <td><small>Q#<?= (int)$r->target_question_id ?> <?= htmlspecialchars((string)$r->target_text) ?></small></td>
                        <td style="max-width:300px"><small><code><?= htmlspecialchars((string)$r->condition_logic) ?></code></small></td>
                        <td><?= (int)$r->priority ?></td>
                        <td><span class="badge bg-<?= (int)$r->is_active === 1 ? 'success' : 'secondary' ?>"><?= (int)$r->is_active === 1 ? 'Ativa' : 'Inativa' ?></span></td>
                        <td>
                            <?php if ((int)$r->is_active !== 1): ?>
                                <button class="btn btn-sm btn-outline-success btn-act" data-act="approve" data-id="<?= (int)$r->id ?>"><i class="fas fa-check"></i></button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-warning btn-act" data-act="reject" data-id="<?= (int)$r->id ?>"><i class="fas fa-pause"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(function() {
    const base = '<?= base_url() ?>';
    const qid  = <?= (int)$selected_qid ?>;
    const out = (h) => document.getElementById('genResult').innerHTML = h;

    const btnG = document.getElementById('btnGenerate');
    if (btnG) btnG.addEventListener('click', function() {
        out('<i class="fas fa-spinner fa-spin"></i> gerando…');
        fetch(base + 'ai/generate_adaptive_rules', { method:'POST', credentials:'same-origin',
            body: new URLSearchParams({ questionnaire_id: qid }) }).then(r=>r.json()).then(d => {
                out(d.success ? '<span class="text-success">' + (d.created||0) + ' regras criadas (inativas).</span>'
                              : '<span class="text-danger">' + (d.message||'Falha') + '</span>');
                if (d.success) setTimeout(() => location.reload(), 700);
            });
    });
    const btnC = document.getElementById('btnClear');
    if (btnC) btnC.addEventListener('click', function() {
        if (!confirm('Apagar TODAS as regras deste questionário?')) return;
        fetch(base + 'ai/clear_adaptive_rules', { method:'POST', credentials:'same-origin',
            body: new URLSearchParams({ questionnaire_id: qid }) }).then(() => location.reload());
    });
    document.querySelectorAll('.btn-act').forEach(b => b.addEventListener('click', function() {
        const url = base + 'ai/' + (this.dataset.act === 'approve' ? 'approve_adaptive_rule' : 'reject_adaptive_rule');
        fetch(url, { method:'POST', credentials:'same-origin', body: new URLSearchParams({ id: this.dataset.id }) }).then(()=>location.reload());
    }));
})();
</script>
