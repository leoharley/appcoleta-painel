<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1"><i class="fas fa-brain me-2"></i>Inteligência Artificial</h2>
                <p class="text-muted mb-0">Painel central do módulo de IA</p>
            </div>
            <div>
                <a href="<?= base_url('ai/settings') ?>" class="btn btn-outline-primary me-1"><i class="fas fa-cog me-1"></i> Configurações</a>
                <a href="<?= base_url('ai/prompts') ?>" class="btn btn-outline-primary me-1"><i class="fas fa-comment-dots me-1"></i> Prompts</a>
                <a href="<?= base_url('ai/directives') ?>" class="btn btn-outline-primary me-1"><i class="fas fa-flag me-1"></i> Diretrizes</a>
                <a href="<?= base_url('ai/logs') ?>" class="btn btn-outline-secondary me-1"><i class="fas fa-list me-1"></i> Logs</a>
                <a href="<?= base_url('ai/security') ?>" class="btn btn-outline-secondary"><i class="fas fa-shield-alt me-1"></i> Diagnóstico</a>
            </div>
        </div>
    </div>
</div>

<?php if (!$is_configured): ?>
<div class="alert alert-warning d-flex align-items-center">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <div class="flex-grow-1">
        <strong>Chave OpenAI não configurada.</strong>
        Vá em <a href="<?= base_url('ai/settings') ?>" class="alert-link">Configurações</a> e adicione a chave para habilitar as features.
    </div>
</div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">Execuções (30d)</small>
                <h3 class="mb-0"><?= (int)($execution_stats['totals']->total ?? 0) ?></h3>
                <small class="text-success"><?= (int)($execution_stats['totals']->success_count ?? 0) ?> ok</small>
                <small class="text-danger ms-2"><?= (int)($execution_stats['totals']->error_count ?? 0) ?> erros</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">Custo total (30d)</small>
                <h3 class="mb-0">US$ <?= number_format((float)($execution_stats['totals']->cost_total ?? 0), 4) ?></h3>
                <small class="text-muted">
                    <?= number_format((int)($execution_stats['totals']->tokens_input_total ?? 0)) ?> in /
                    <?= number_format((int)($execution_stats['totals']->tokens_output_total ?? 0)) ?> out
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">Pendências</small>
                <h3 class="mb-0">
                    <?= (int)($stats['pending_inconsistencies'] + $stats['pending_corrections'] + $stats['pending_suggestions'] + $stats['pending_reformulations'] + $stats['pending_followups'] + $stats['pending_adaptive']) ?>
                </h3>
                <small class="text-muted">resultados aguardando revisão</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <small class="text-muted">Transcrições</small>
                <h3 class="mb-0"><?= (int)$stats['transcriptions_total'] ?></h3>
                <small class="text-muted">total registradas</small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><strong>Atalhos das features</strong></div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= base_url('ai/transcriptions') ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-microphone me-1"></i> Transcrições</a>
                    <a href="<?= base_url('ai/inconsistencies') ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-exclamation-triangle me-1"></i> Inconsistências <?= (int)$stats['pending_inconsistencies'] > 0 ? '<span class="badge bg-danger ms-1">'.(int)$stats['pending_inconsistencies'].'</span>' : '' ?></a>
                    <a href="<?= base_url('ai/corrections') ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit me-1"></i> Correções <?= (int)$stats['pending_corrections'] > 0 ? '<span class="badge bg-danger ms-1">'.(int)$stats['pending_corrections'].'</span>' : '' ?></a>
                    <a href="<?= base_url('ai/smart_fill') ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-magic me-1"></i> Smart fill <?= (int)$stats['pending_suggestions'] > 0 ? '<span class="badge bg-danger ms-1">'.(int)$stats['pending_suggestions'].'</span>' : '' ?></a>
                    <a href="<?= base_url('ai/reformulations') ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-pen-fancy me-1"></i> Reformulações <?= (int)$stats['pending_reformulations'] > 0 ? '<span class="badge bg-danger ms-1">'.(int)$stats['pending_reformulations'].'</span>' : '' ?></a>
                    <a href="<?= base_url('ai/adaptive') ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-route me-1"></i> Adaptativo <?= (int)$stats['pending_adaptive'] > 0 ? '<span class="badge bg-danger ms-1">'.(int)$stats['pending_adaptive'].'</span>' : '' ?></a>
                    <a href="<?= base_url('ai/followup') ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-comment-medical me-1"></i> Follow-up <?= (int)$stats['pending_followups'] > 0 ? '<span class="badge bg-danger ms-1">'.(int)$stats['pending_followups'].'</span>' : '' ?></a>
                    <a href="<?= base_url('ai/analysis') ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-chart-bar me-1"></i> Análise</a>
                    <a href="<?= base_url('ai/charts') ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-chart-pie me-1"></i> Gráficos</a>
                    <a href="<?= base_url('ai/reports') ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-alt me-1"></i> Relatórios</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header"><strong>Features por categoria</strong></div>
            <div class="card-body">
                <?php foreach ($settings_by_cat as $cat => $items): ?>
                <h6 class="text-muted text-uppercase small mt-2"><?= htmlspecialchars($cat) ?></h6>
                <div class="row g-2 mb-3">
                    <?php foreach ($items as $s): ?>
                    <div class="col-md-6">
                        <div class="border rounded p-2 d-flex justify-content-between align-items-center">
                            <div>
                                <div><?= htmlspecialchars($s->feature_name) ?></div>
                                <small class="text-muted"><code><?= htmlspecialchars($s->feature_key) ?></code> · <?= htmlspecialchars($s->model ?: '—') ?></small>
                            </div>
                            <span class="badge bg-<?= (int)$s->is_enabled === 1 ? 'success' : 'secondary' ?>">
                                <?= (int)$s->is_enabled === 1 ? 'Ativa' : 'Desativada' ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header"><strong>Custo por feature (30d)</strong></div>
            <div class="card-body">
                <?php if (empty($execution_stats['by_feature'])): ?>
                    <p class="text-muted mb-0">Sem execuções ainda.</p>
                <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Feature</th><th class="text-end">Chamadas</th><th class="text-end">Custo USD</th></tr></thead>
                    <tbody>
                    <?php foreach ($execution_stats['by_feature'] as $f): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($f->feature_key) ?></code></td>
                            <td class="text-end"><?= (int)$f->total ?></td>
                            <td class="text-end"><?= number_format((float)$f->cost, 4) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($execution_stats['recent_errors'])): ?>
        <div class="card">
            <div class="card-header bg-danger text-white"><strong>Erros recentes</strong></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                <?php foreach ($execution_stats['recent_errors'] as $e): ?>
                    <li class="list-group-item">
                        <small><code><?= htmlspecialchars($e->feature_key) ?></code> · <?= date('d/m H:i', strtotime($e->created_at)) ?></small>
                        <div class="text-danger small"><?= htmlspecialchars(mb_strimwidth((string)$e->error_message, 0, 160, '…')) ?></div>
                    </li>
                <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
