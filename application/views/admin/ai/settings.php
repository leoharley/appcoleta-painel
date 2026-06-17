<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Configurações de IA</h2>
                <p class="text-muted mb-0">Chave da OpenAI e parâmetros por feature</p>
            </div>
            <a href="<?= base_url('ai') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><strong>Chave da OpenAI</strong></div>
    <div class="card-body">
        <p class="text-muted small mb-2">Armazenada em <code>ai_settings._openai_api_key.custom_params</code>. Use uma chave com escopo restrito.</p>
        <div class="input-group">
            <input type="password" class="form-control" id="apiKeyInput"
                   value="<?= htmlspecialchars($api_key_row && $api_key_row->custom_params ? str_repeat('•', 16) : '') ?>"
                   placeholder="sk-...">
            <button class="btn btn-outline-secondary" type="button" id="apiKeyShow">
                <i class="fas fa-eye"></i>
            </button>
            <button class="btn btn-primary" type="button" id="apiKeySave">
                <i class="fas fa-save me-1"></i> Salvar chave
            </button>
            <button class="btn btn-outline-success" type="button" id="apiKeyTest">
                <i class="fas fa-plug me-1"></i> Testar conexão
            </button>
        </div>
        <div id="apiKeyResult" class="small mt-2"></div>
    </div>
</div>

<?php foreach ($settings_by_cat as $cat => $items): ?>
<div class="card mb-4">
    <div class="card-header"><strong class="text-uppercase small"><?= htmlspecialchars($cat) ?></strong></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>Status</th>
                        <th>Modelo</th>
                        <th style="width:120px">Temp</th>
                        <th style="width:120px">Max tokens</th>
                        <th style="width:120px">Timeout (s)</th>
                        <th style="width:100px">Retry</th>
                        <th class="text-end" style="width:120px">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $s): ?>
                    <tr data-id="<?= (int)$s->id ?>" data-key="<?= htmlspecialchars($s->feature_key) ?>">
                        <td>
                            <strong><?= htmlspecialchars($s->feature_name) ?></strong><br>
                            <small class="text-muted"><code><?= htmlspecialchars($s->feature_key) ?></code></small>
                        </td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input feature-toggle" type="checkbox"
                                       <?= (int)$s->is_enabled === 1 ? 'checked' : '' ?>>
                            </div>
                        </td>
                        <td><input type="text" class="form-control form-control-sm field-model" value="<?= htmlspecialchars((string)$s->model) ?>"></td>
                        <td><input type="number" step="0.01" min="0" max="2" class="form-control form-control-sm field-temperature" value="<?= htmlspecialchars((string)$s->temperature) ?>"></td>
                        <td><input type="number" min="1" class="form-control form-control-sm field-max-tokens" value="<?= (int)$s->max_tokens ?>"></td>
                        <td><input type="number" min="1" class="form-control form-control-sm field-timeout" value="<?= (int)$s->timeout_seconds ?>"></td>
                        <td><input type="number" min="0" class="form-control form-control-sm field-retry" value="<?= (int)$s->retry_attempts ?>"></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-primary btn-save-setting"><i class="fas fa-save"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
(function() {
    const csrfBase = '<?= base_url() ?>';

    function postForm(url, params) {
        const body = new URLSearchParams();
        Object.keys(params).forEach(k => body.append(k, params[k]));
        return fetch(url, { method: 'POST', credentials: 'same-origin', body: body }).then(r => r.json());
    }

    // Salvar/testar chave
    const inp = document.getElementById('apiKeyInput');
    document.getElementById('apiKeyShow').addEventListener('click', function() {
        inp.type = inp.type === 'password' ? 'text' : 'password';
    });
    document.getElementById('apiKeySave').addEventListener('click', function() {
        const val = inp.value.trim();
        if (val.startsWith('•')) {
            document.getElementById('apiKeyResult').innerHTML =
                '<span class="text-warning">Cole a chave em texto claro antes de salvar.</span>';
            return;
        }
        postForm(csrfBase + 'ai/save_api_key', { api_key: val }).then(d => {
            document.getElementById('apiKeyResult').innerHTML = d.success
                ? '<span class="text-success">Chave salva.</span>'
                : '<span class="text-danger">Falha ao salvar.</span>';
        });
    });
    document.getElementById('apiKeyTest').addEventListener('click', function() {
        document.getElementById('apiKeyResult').innerHTML =
            '<i class="fas fa-spinner fa-spin"></i> testando...';
        postForm(csrfBase + 'ai/test_connection', {}).then(d => {
            document.getElementById('apiKeyResult').innerHTML = d.success
                ? '<span class="text-success">' + d.message + '</span>'
                : '<span class="text-danger">' + (d.message || 'Erro') + '</span>';
        });
    });

    // Toggle de feature (independente do save)
    document.querySelectorAll('.feature-toggle').forEach(cb => {
        cb.addEventListener('change', function() {
            const row = this.closest('tr');
            postForm(csrfBase + 'ai/toggle_feature', {
                feature_key: row.dataset.key,
                enabled: this.checked ? 1 : 0
            });
        });
    });

    // Save da linha inteira
    document.querySelectorAll('.btn-save-setting').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const original = btn.innerHTML;
            btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            postForm(csrfBase + 'ai/update_setting', {
                id:              row.dataset.id,
                is_enabled:      row.querySelector('.feature-toggle').checked ? 1 : 0,
                model:           row.querySelector('.field-model').value,
                temperature:     row.querySelector('.field-temperature').value,
                max_tokens:      row.querySelector('.field-max-tokens').value,
                timeout_seconds: row.querySelector('.field-timeout').value,
                retry_attempts:  row.querySelector('.field-retry').value,
            }).then(d => {
                btn.innerHTML = d.success ? '<i class="fas fa-check"></i>' : '<i class="fas fa-times text-danger"></i>';
                setTimeout(() => { btn.disabled = false; btn.innerHTML = original; }, 1200);
            });
        });
    });
})();
</script>
