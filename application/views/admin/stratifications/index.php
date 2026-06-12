<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Estratificação da Amostra</h2>
            <a href="<?= base_url('stratifications/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>
                Nova Estratificação
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($stratifications)): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Questionário</th>
                        <th>Categorias</th>
                        <th>Progresso</th>
                        <th>Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stratifications as $strat): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($strat->title) ?></strong></td>
                        <td><?= htmlspecialchars($strat->questionnaire_title) ?></td>
                        <td>
                            <span class="badge bg-secondary"><?= $strat->categories_count ?></span>
                        </td>
                        <td style="min-width: 200px;">
                            <?php
                                $pct = $strat->overall->percentage;
                                $bar_class = 'bg-secondary';
                                if ($pct >= 100) $bar_class = 'bg-success';
                                elseif ($pct > 0) $bar_class = 'bg-warning';
                            ?>
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1" style="height: 20px;">
                                    <div class="progress-bar <?= $bar_class ?>" style="width: <?= min($pct, 100) ?>%">
                                        <?= $pct ?>%
                                    </div>
                                </div>
                                <small class="ms-2 text-muted"><?= $strat->overall->total_current ?>/<?= $strat->overall->total_target ?></small>
                            </div>
                        </td>
                        <td>
                            <?php if ($strat->is_active): ?>
                                <span class="badge bg-success">Ativa</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inativa</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <a href="<?= base_url('stratifications/view/' . $strat->id) ?>"
                                   class="btn btn-sm btn-outline-primary" title="Dashboard">
                                    <i class="fas fa-chart-bar"></i>
                                </a>
                                <a href="<?= base_url('stratifications/edit/' . $strat->id) ?>"
                                   class="btn btn-sm btn-outline-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= base_url('stratifications/delete/' . $strat->id) ?>"
                                   class="btn btn-sm btn-outline-danger" title="Excluir"
                                   onclick="return confirm('Tem certeza que deseja excluir esta estratificação?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Nenhuma estratificação configurada</h5>
            <p class="text-muted">Crie uma estratificação para controlar as cotas da sua pesquisa.</p>
            <a href="<?= base_url('stratifications/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Nova Estratificação
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
