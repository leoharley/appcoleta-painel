<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1">Análise estatística</h2><p class="text-muted mb-0">Padrões, outliers, tendências por questionário</p></div>
    <a href="<?= base_url('ai') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form id="genForm" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small mb-1">Questionário</label>
                <select class="form-select form-select-sm" name="questionnaire_id" required>
                    <option value="">Selecione…</option>
                    <?php foreach ($questionnaires as $q): ?>
                        <option value="<?= (int)$q->id ?>"><?= htmlspecialchars($q->title) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><label class="form-label small mb-1">De</label><input type="date" class="form-control form-control-sm" name="from"></div>
            <div class="col-md-2"><label class="form-label small mb-1">Até</label><input type="date" class="form-control form-control-sm" name="to"></div>
            <div class="col-md-3"><button class="btn btn-primary btn-sm w-100"><i class="fas fa-chart-bar me-1"></i> Gerar análise</button></div>
        </form>
        <div id="genResult" class="small mt-2"></div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>#</th><th>Questionário</th><th>Resumo</th><th>Quem</th><th>Quando</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Sem análises ainda.</td></tr>
                <?php else: foreach ($items as $it): ?>
                    <tr>
                        <td><code>#<?= (int)$it->id ?></code></td>
                        <td><small><?= htmlspecialchars((string)$it->questionnaire_title) ?></small></td>
                        <td style="max-width:480px"><small><?= htmlspecialchars(mb_strimwidth((string)$it->summary_text, 0, 280, '…')) ?></small></td>
                        <td><small><?= htmlspecialchars((string)$it->generated_by_name) ?></small></td>
                        <td><small><?= date('d/m H:i', strtotime($it->created_at)) ?></small></td>
                        <td>
                            <a href="<?= base_url('ai/view_analysis/' . (int)$it->id) ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                            <button class="btn btn-sm btn-outline-danger btn-del" data-id="<?= (int)$it->id ?>"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    const base = '<?= base_url() ?>';
    document.getElementById('genForm').addEventListener('submit', function(e) {
        e.preventDefault();
        document.getElementById('genResult').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        fetch(base + 'ai/generate_analysis', { method:'POST', credentials:'same-origin', body: new URLSearchParams(new FormData(this)) })
            .then(r=>r.json()).then(d => {
                document.getElementById('genResult').innerHTML = d.success
                    ? '<span class="text-success">Análise criada (id ' + d.id + ').</span>'
                    : '<span class="text-danger">' + (d.message||'Falha') + '</span>';
                if (d.success) setTimeout(()=>location.reload(), 700);
            });
    });
    document.querySelectorAll('.btn-del').forEach(b => b.addEventListener('click', function() {
        if (!confirm('Excluir esta análise?')) return;
        fetch(base + 'ai/delete_analysis', { method:'POST', credentials:'same-origin', body: new URLSearchParams({ id: this.dataset.id }) }).then(()=>location.reload());
    }));
})();
</script>
