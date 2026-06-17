<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1">Diretrizes do agente</h2><p class="text-muted mb-0">Instruções permanentes injetadas no <em>system prompt</em></p></div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#dirModal" id="btnNew"><i class="fas fa-plus me-1"></i> Nova diretriz</button>
        <a href="<?= base_url('ai') ?>" class="btn btn-secondary ms-2"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>#</th><th>Título</th><th>Categoria</th><th>Aplica a</th><th>Prio</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($directives)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Sem diretrizes.</td></tr>
                <?php else: foreach ($directives as $d): ?>
                    <tr data-id="<?= (int)$d->id ?>">
                        <td><code>#<?= (int)$d->id ?></code></td>
                        <td><strong><?= htmlspecialchars((string)$d->title) ?></strong><br><small class="text-muted"><?= htmlspecialchars(mb_strimwidth((string)$d->content, 0, 120, '…')) ?></small></td>
                        <td><small><?= htmlspecialchars((string)$d->category) ?></small></td>
                        <td><small><code><?= htmlspecialchars((string)$d->applies_to ?: '*') ?></code></small></td>
                        <td><?= (int)$d->priority ?></td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input toggle-dir" type="checkbox" <?= (int)$d->is_active === 1 ? 'checked' : '' ?>>
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary btn-edit" data-id="<?= (int)$d->id ?>"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger btn-del" data-id="<?= (int)$d->id ?>"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="dirModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="dirForm">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Diretriz</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="dId">
                    <div class="row">
                        <div class="col-md-6 mb-2"><label class="form-label">Título *</label><input class="form-control" name="title" id="dTitle" required></div>
                        <div class="col-md-3 mb-2"><label class="form-label">Categoria</label><input class="form-control" name="category" id="dCat"></div>
                        <div class="col-md-3 mb-2"><label class="form-label">Prioridade</label><input type="number" class="form-control" name="priority" id="dPri" value="0"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-2">
                            <label class="form-label">Aplica a (CSV de feature_key ou <code>*</code>)</label>
                            <input class="form-control" name="applies_to" id="dApplies" placeholder="*, ou: inconsistency_detection, data_correction">
                        </div>
                        <div class="col-md-4 mb-2"><label class="form-label">Tipo</label><input class="form-control" name="directive_type" id="dType"></div>
                    </div>
                    <div class="mb-2"><label class="form-label">Conteúdo *</label><textarea class="form-control" name="content" id="dContent" rows="5" required></textarea></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="dActive" value="1" checked><label class="form-check-label" for="dActive">Ativa</label></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const base = '<?= base_url() ?>';
    const modal = new bootstrap.Modal(document.getElementById('dirModal'));

    document.getElementById('btnNew').addEventListener('click', function() {
        ['dId','dTitle','dCat','dPri','dApplies','dType','dContent'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('dPri').value = 0;
        document.getElementById('dActive').checked = true;
    });

    document.querySelectorAll('.btn-edit').forEach(b => b.addEventListener('click', function() {
        fetch(base + 'ai/get_directive/' + this.dataset.id).then(r=>r.json()).then(d => {
            if (!d.success) return;
            const x = d.directive;
            document.getElementById('dId').value = x.id;
            document.getElementById('dTitle').value = x.title || '';
            document.getElementById('dCat').value = x.category || '';
            document.getElementById('dPri').value = x.priority || 0;
            let applies = x.applies_to ? (typeof x.applies_to === 'string' ? JSON.parse(x.applies_to) : x.applies_to) : [];
            document.getElementById('dApplies').value = Array.isArray(applies) ? applies.join(', ') : '';
            document.getElementById('dType').value = x.directive_type || '';
            document.getElementById('dContent').value = x.content || '';
            document.getElementById('dActive').checked = String(x.is_active) === '1' || x.is_active === true;
            modal.show();
        });
    }));

    document.getElementById('dirForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const body = new URLSearchParams(new FormData(this));
        if (!document.getElementById('dActive').checked) body.set('is_active', '');
        fetch(base + 'ai/save_directive', { method:'POST', credentials:'same-origin', body })
            .then(r=>r.json()).then(d => { if (d.success) location.reload(); else alert('Falha'); });
    });

    document.querySelectorAll('.toggle-dir').forEach(t => t.addEventListener('change', function() {
        const id = this.closest('tr').dataset.id;
        fetch(base + 'ai/toggle_directive', { method:'POST', credentials:'same-origin',
            body: new URLSearchParams({ id, active: this.checked ? 1 : '' }) });
    }));

    document.querySelectorAll('.btn-del').forEach(b => b.addEventListener('click', function() {
        if (!confirm('Excluir esta diretriz?')) return;
        fetch(base + 'ai/delete_directive', { method:'POST', credentials:'same-origin',
            body: new URLSearchParams({ id: this.dataset.id }) }).then(()=>location.reload());
    }));
})();
</script>
