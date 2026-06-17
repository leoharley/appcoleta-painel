<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Logs de execução da IA</h2>
                <p class="text-muted mb-0">Cada chamada à OpenAI gera uma linha aqui</p>
            </div>
            <a href="<?= base_url('ai') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
        </div>
    </div>
</div>

<form method="get" class="card mb-3">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label small">Feature</label>
                <select class="form-select form-select-sm" name="feature_key">
                    <option value="">Todas</option>
                    <?php foreach ($settings as $s): ?>
                        <option value="<?= htmlspecialchars($s['feature_key']) ?>" <?= ($filters['feature_key'] === $s['feature_key'] ? 'selected' : '') ?>>
                            <?= htmlspecialchars($s['feature_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Status</label>
                <select class="form-select form-select-sm" name="status">
                    <?php $st = $filters['status']; ?>
                    <option value="">Todos</option>
                    <option value="success" <?= $st === 'success' ? 'selected' : '' ?>>Sucesso</option>
                    <option value="error"   <?= $st === 'error'   ? 'selected' : '' ?>>Erro</option>
                    <option value="running" <?= $st === 'running' ? 'selected' : '' ?>>Em execução</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">De</label>
                <input type="date" class="form-control form-control-sm" name="from" value="<?= htmlspecialchars((string)$filters['from']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Até</label>
                <input type="date" class="form-control form-control-sm" name="to" value="<?= htmlspecialchars((string)$filters['to']) ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm me-2"><i class="fas fa-filter me-1"></i>Filtrar</button>
                <a href="<?= base_url('ai/logs') ?>" class="btn btn-outline-secondary btn-sm">Limpar</a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Feature</th>
                        <th>Modelo</th>
                        <th>Status</th>
                        <th class="text-end">Tokens</th>
                        <th class="text-end">Custo USD</th>
                        <th class="text-end">Duração</th>
                        <th>Usuário</th>
                        <th>Quando</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">Nenhum log encontrado.</td></tr>
                <?php else: foreach ($logs as $l): ?>
                    <tr>
                        <td><code>#<?= (int)$l->id ?></code></td>
                        <td><code><?= htmlspecialchars($l->feature_key) ?></code></td>
                        <td><?= htmlspecialchars((string)$l->model_used) ?></td>
                        <td>
                            <?php $cls = $l->status === 'success' ? 'success' : ($l->status === 'error' ? 'danger' : 'secondary'); ?>
                            <span class="badge bg-<?= $cls ?>"><?= htmlspecialchars($l->status) ?></span>
                        </td>
                        <td class="text-end"><?= number_format((int)$l->tokens_input) ?>/<?= number_format((int)$l->tokens_output) ?></td>
                        <td class="text-end"><?= number_format((float)$l->cost_usd, 4) ?></td>
                        <td class="text-end"><?= (int)$l->duration_ms ?>ms</td>
                        <td><small><?= htmlspecialchars((string)$l->executed_by_name) ?></small></td>
                        <td><small><?= date('d/m/Y H:i:s', strtotime($l->created_at)) ?></small></td>
                        <td>
                            <?php if ($l->error_message): ?>
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip"
                                        title="<?= htmlspecialchars(mb_strimwidth((string)$l->error_message, 0, 300, '…')) ?>">
                                    <i class="fas fa-exclamation-circle"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($pages > 1):
        $qs = $_GET; ?>
    <div class="card-footer">
        <nav><ul class="pagination pagination-sm mb-0">
        <?php for ($i = 1; $i <= $pages; $i++):
            $qs['page'] = $i;
            $url = base_url('ai/logs') . '?' . http_build_query($qs); ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= $url ?>"><?= $i ?></a></li>
        <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>
