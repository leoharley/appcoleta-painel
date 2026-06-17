<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Prompts da IA</h2>
                <p class="text-muted mb-0">Templates de system/user prompt por feature</p>
            </div>
            <div>
                <button class="btn btn-primary" id="btnNewPrompt" data-bs-toggle="modal" data-bs-target="#promptModal">
                    <i class="fas fa-plus me-1"></i> Novo prompt
                </button>
                <a href="<?= base_url('ai') ?>" class="btn btn-secondary ms-2"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
            </div>
        </div>
    </div>
</div>

<?php if (empty($prompts)): ?>
<div class="alert alert-info">Nenhum prompt cadastrado.</div>
<?php else: ?>
<div class="accordion" id="promptsAcc">
    <?php foreach ($prompts as $i => $p): ?>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#p<?= (int)$p->id ?>">
                <span class="badge bg-<?= (int)$p->is_active === 1 ? 'success' : 'secondary' ?> me-2">v<?= (int)$p->version ?></span>
                <strong class="me-2"><?= htmlspecialchars($p->prompt_name) ?></strong>
                <code class="text-muted"><?= htmlspecialchars($p->feature_key) ?></code>
            </button>
        </h2>
        <div id="p<?= (int)$p->id ?>" class="accordion-collapse collapse" data-bs-parent="#promptsAcc">
            <div class="accordion-body">
                <div class="mb-3">
                    <strong>System prompt</strong>
                    <pre class="bg-light p-3 rounded small mb-0"><?= htmlspecialchars((string)$p->system_prompt) ?></pre>
                </div>
                <div class="mb-3">
                    <strong>User prompt template</strong>
                    <pre class="bg-light p-3 rounded small mb-0"><?= htmlspecialchars((string)$p->user_prompt_template) ?></pre>
                </div>
                <button class="btn btn-sm btn-outline-primary btn-edit-prompt" data-prompt='<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>'>
                    <i class="fas fa-edit me-1"></i> Editar
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal -->
<div class="modal fade" id="promptModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="promptForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Prompt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="prId" value="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">feature_key *</label>
                            <input type="text" class="form-control" name="feature_key" id="prKey" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nome *</label>
                            <input type="text" class="form-control" name="prompt_name" id="prName" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Versão</label>
                            <input type="number" min="1" class="form-control" name="version" id="prVersion" value="1">
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="prActive" value="1" checked>
                        <label class="form-check-label" for="prActive">Ativo</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">System prompt</label>
                        <textarea class="form-control" name="system_prompt" id="prSystem" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">User prompt template (use <code>{{placeholder}}</code>)</label>
                        <textarea class="form-control" name="user_prompt_template" id="prUser" rows="5"></textarea>
                    </div>
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
    const modalEl = document.getElementById('promptModal');
    const modal = new bootstrap.Modal(modalEl);

    function resetForm() {
        document.getElementById('prId').value = '';
        document.getElementById('prKey').value = '';
        document.getElementById('prName').value = '';
        document.getElementById('prVersion').value = '1';
        document.getElementById('prActive').checked = true;
        document.getElementById('prSystem').value = '';
        document.getElementById('prUser').value = '';
    }
    document.getElementById('btnNewPrompt').addEventListener('click', resetForm);

    document.querySelectorAll('.btn-edit-prompt').forEach(btn => {
        btn.addEventListener('click', function() {
            const p = JSON.parse(this.dataset.prompt);
            document.getElementById('prId').value = p.id || '';
            document.getElementById('prKey').value = p.feature_key || '';
            document.getElementById('prName').value = p.prompt_name || '';
            document.getElementById('prVersion').value = p.version || 1;
            document.getElementById('prActive').checked = String(p.is_active) === '1' || p.is_active === 1 || p.is_active === true;
            document.getElementById('prSystem').value = p.system_prompt || '';
            document.getElementById('prUser').value = p.user_prompt_template || '';
            modal.show();
        });
    });

    document.getElementById('promptForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const body = new URLSearchParams(new FormData(this));
        if (!document.getElementById('prActive').checked) body.set('is_active', '');
        fetch(base + 'ai/save_prompt', { method: 'POST', credentials: 'same-origin', body: body })
            .then(r => r.json())
            .then(d => {
                if (d.success) { location.reload(); }
                else alert(d.message || 'Falha ao salvar.');
            });
    });
})();
</script>
