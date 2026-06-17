<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1">Relatórios em linguagem natural</h2><p class="text-muted mb-0">Narrativas geradas pela IA a partir dos dados coletados</p></div>
    <a href="<?= base_url('ai') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form id="genForm" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Questionário</label>
                <select name="questionnaire_id" class="form-select form-select-sm" required>
                    <option value="">Selecione…</option>
                    <?php foreach ($questionnaires as $q): ?>
                        <option value="<?= (int)$q->id ?>"><?= htmlspecialchars($q->title) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3"><label class="form-label small mb-1">Título</label><input type="text" name="title" class="form-control form-control-sm" placeholder="opcional"></div>
            <div class="col-md-2"><label class="form-label small mb-1">De</label><input type="date" class="form-control form-control-sm" name="from"></div>
            <div class="col-md-2"><label class="form-label small mb-1">Até</label><input type="date" class="form-control form-control-sm" name="to"></div>
            <div class="col-md-1"><button class="btn btn-primary btn-sm w-100"><i class="fas fa-magic"></i></button></div>
        </form>
        <div id="genResult" class="small mt-2"></div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>#</th><th>Título</th><th>Questionário</th><th>Trecho</th><th>Quem</th><th>Quando</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Sem relatórios.</td></tr>
                <?php else: foreach ($items as $it): ?>
                    <tr>
                        <td><code>#<?= (int)$it->id ?></code></td>
                        <td><strong><?= htmlspecialchars((string)$it->title) ?></strong></td>
                        <td><small><?= htmlspecialchars((string)$it->questionnaire_title) ?></small></td>
                        <td style="max-width:380px"><small><?= htmlspecialchars(mb_strimwidth((string)$it->narrative_text, 0, 240, '…')) ?></small></td>
                        <td><small><?= htmlspecialchars((string)$it->generated_by_name) ?></small></td>
                        <td><small><?= date('d/m H:i', strtotime($it->created_at)) ?></small></td>
                        <td><a href="<?= base_url('ai/view_report/' . (int)$it->id) ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
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
        fetch(base + 'ai/generate_report', { method:'POST', credentials:'same-origin', body: new URLSearchParams(new FormData(this)) })
            .then(r=>r.json()).then(d => {
                document.getElementById('genResult').innerHTML = d.success
                    ? '<span class="text-success">Relatório criado (id ' + d.id + ').</span>'
                    : '<span class="text-danger">' + (d.message||'Falha') + '</span>';
                if (d.success) setTimeout(()=>location.reload(), 800);
            });
    });
})();
</script>
