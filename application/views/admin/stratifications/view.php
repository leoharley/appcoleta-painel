<?php
    $overall = $progress ? $progress['overall'] : (object)['total_target' => 0, 'total_current' => 0, 'percentage' => 0];
    $categories = $progress ? $progress['categories'] : [];
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><?= htmlspecialchars($stratification->title) ?></h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-poll me-1"></i>
                    Questionário: <strong><?= htmlspecialchars($stratification->questionnaire_title) ?></strong>
                    <?php if (!$stratification->is_active): ?>
                        <span class="badge bg-secondary ms-2">Inativa</span>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <button class="btn btn-outline-primary me-2" onclick="refreshProgress()">
                    <i class="fas fa-sync-alt me-1"></i>Atualizar
                </button>
                <a href="<?= base_url('stratifications/edit/' . $stratification->id) ?>" class="btn btn-outline-warning me-2">
                    <i class="fas fa-edit me-1"></i>Editar
                </a>
                <a href="<?= base_url('stratifications/duplicate/' . $stratification->id) ?>" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-copy me-1"></i>Clonar
                </a>
                <a href="<?= base_url('stratifications') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filtro por aplicador (item 1, A1) -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="<?= base_url('stratifications/view/' . $stratification->id) ?>" class="row g-2 align-items-center">
            <div class="col-auto">
                <label for="applied_by" class="col-form-label">
                    <i class="fas fa-filter me-1"></i>Filtrar por aplicador:
                </label>
            </div>
            <div class="col-md-4">
                <select id="applied_by" name="applied_by" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todos (visão geral)</option>
                    <?php foreach ($applicators as $u): ?>
                    <option value="<?= $u->id ?>" <?= ($applied_by_filter == $u->id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u->full_name) ?> (<?= ucfirst($u->role) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($applied_by_filter): ?>
            <div class="col-auto">
                <a href="<?= base_url('stratifications/view/' . $stratification->id) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Limpar filtro
                </a>
            </div>
            <div class="col-auto">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Mostrando apenas as respostas aplicadas por este pesquisador.
                </small>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Cards de Resumo -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="mb-1" style="color: var(--primary-color, #E87722);"><?= $overall->percentage ?>%</h3>
                <small class="text-muted">Progresso Geral</small>
                <div class="progress mt-2" style="height: 8px;">
                    <?php
                        $bar_class = 'bg-secondary';
                        if ($overall->percentage >= 100) $bar_class = 'bg-success';
                        elseif ($overall->percentage > 0) $bar_class = 'bg-warning';
                    ?>
                    <div class="progress-bar <?= $bar_class ?>" style="width: <?= min($overall->percentage, 100) ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="mb-1" style="color: var(--secondary-color, #23345F);"><?= $overall->total_target ?></h3>
                <small class="text-muted">Meta Total</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="mb-1" style="color: var(--primary-color, #E87722);"><?= $overall->total_current ?></h3>
                <small class="text-muted">Total Coletado</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="mb-1" style="color: var(--secondary-color, #23345F);"><?= count($categories) ?></h3>
                <small class="text-muted">Categorias</small>
            </div>
        </div>
    </div>
</div>

<!-- Barras de Progresso por Categoria -->
<div class="row mb-4">
    <?php foreach ($categories as $cat): ?>
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-layer-group me-2"></i>
                    <?= htmlspecialchars($cat->category_name) ?>
                </h5>
                <span class="badge bg-primary"><?= $cat->total_current ?>/<?= $cat->total_target ?></span>
            </div>
            <div class="card-body">
                <?php foreach ($cat->strata as $stratum): ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold"><?= htmlspecialchars($stratum->option_label) ?></span>
                        <span>
                            <?= $stratum->current_count ?>/<?= $stratum->target_quota ?>
                            <?php if ($stratum->quota_reached): ?>
                                <span class="badge bg-success ms-1">COMPLETO</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="progress" style="height: 22px;">
                        <?php
                            $pct = $stratum->percentage;
                            $bar_class = 'bg-secondary';
                            if ($pct >= 100) $bar_class = 'bg-success';
                            elseif ($pct > 50) $bar_class = 'bg-warning';
                            elseif ($pct > 0) $bar_class = 'bg-info';
                        ?>
                        <div class="progress-bar <?= $bar_class ?>" role="progressbar"
                             style="width: <?= min($pct, 100) ?>%; font-size: 0.8rem;"
                             aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                            <?= $pct ?>%
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Graficos -->
<?php if (!empty($categories)): ?>
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Coletado vs Meta por Categoria</h5>
            </div>
            <div class="card-body">
                <canvas id="barChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Progresso Geral</h5>
            </div>
            <div class="card-body">
                <canvas id="doughnutChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Tabela Detalhada -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Detalhamento por Estrato</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th>Estrato</th>
                                <th>Meta</th>
                                <th>Coletado</th>
                                <th>Faltam</th>
                                <th>Progresso</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <?php foreach ($cat->strata as $i => $stratum): ?>
                                <tr>
                                    <?php if ($i === 0): ?>
                                    <td rowspan="<?= count($cat->strata) ?>" class="fw-bold align-middle">
                                        <?= htmlspecialchars($cat->category_name) ?>
                                    </td>
                                    <?php endif; ?>
                                    <td><?= htmlspecialchars($stratum->option_label) ?></td>
                                    <td><?= $stratum->target_quota ?></td>
                                    <td><?= $stratum->current_count ?></td>
                                    <td>
                                        <?php $remaining = max(0, $stratum->target_quota - $stratum->current_count); ?>
                                        <?= $remaining ?>
                                    </td>
                                    <td style="min-width: 150px;">
                                        <?php
                                            $pct = $stratum->percentage;
                                            $bar_class = 'bg-secondary';
                                            if ($pct >= 100) $bar_class = 'bg-success';
                                            elseif ($pct > 50) $bar_class = 'bg-warning';
                                            elseif ($pct > 0) $bar_class = 'bg-info';
                                        ?>
                                        <div class="progress" style="height: 18px;">
                                            <div class="progress-bar <?= $bar_class ?>"
                                                 style="width: <?= min($pct, 100) ?>%; font-size: 0.75rem;">
                                                <?= $pct ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($stratum->quota_reached): ?>
                                            <span class="badge bg-success">COMPLETO</span>
                                        <?php elseif ($stratum->current_count > 0): ?>
                                            <span class="badge bg-warning text-dark">EM ANDAMENTO</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">PENDENTE</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Dados para graficos
var chartCategories = <?= json_encode(array_map(function($cat) {
    return [
        'name' => $cat->category_name,
        'target' => $cat->total_target,
        'current' => $cat->total_current
    ];
}, $categories)) ?>;

var overallCurrent = <?= $overall->total_current ?>;
var overallTarget = <?= $overall->total_target ?>;
var overallRemaining = Math.max(0, overallTarget - overallCurrent);

document.addEventListener('DOMContentLoaded', function() {
    // Grafico de Barras
    if (document.getElementById('barChart') && chartCategories.length > 0) {
        new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: chartCategories.map(function(c) { return c.name; }),
                datasets: [
                    {
                        label: 'Coletado',
                        data: chartCategories.map(function(c) { return c.current; }),
                        backgroundColor: 'rgba(232, 119, 34, 0.8)',
                        borderColor: 'rgba(232, 119, 34, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Meta',
                        data: chartCategories.map(function(c) { return c.target; }),
                        backgroundColor: 'rgba(35, 52, 95, 0.3)',
                        borderColor: 'rgba(35, 52, 95, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // Grafico Doughnut
    if (document.getElementById('doughnutChart')) {
        new Chart(document.getElementById('doughnutChart'), {
            type: 'doughnut',
            data: {
                labels: ['Coletado', 'Restante'],
                datasets: [{
                    data: [overallCurrent, overallRemaining],
                    backgroundColor: ['rgba(232, 119, 34, 0.8)', 'rgba(200, 200, 200, 0.5)'],
                    borderColor: ['rgba(232, 119, 34, 1)', 'rgba(200, 200, 200, 1)'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    title: {
                        display: true,
                        text: overallTarget > 0
                            ? Math.round(overallCurrent / overallTarget * 100) + '% concluído'
                            : '0% concluído',
                        font: { size: 16 }
                    }
                }
            }
        });
    }
});

function refreshProgress() {
    var btn = event.target.closest('button');
    var originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Atualizando...';
    btn.disabled = true;

    var url = '<?= base_url("stratifications/ajax_get_progress/" . $stratification->id) ?>';
    <?php if ($applied_by_filter): ?>
    url += '?applied_by=<?= (int)$applied_by_filter ?>';
    <?php endif; ?>

    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Recarregar a pagina para atualizar os dados
                location.reload();
            } else {
                alert('Erro ao atualizar dados.');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        },
        error: function() {
            alert('Erro de conexão.');
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });
}
</script>
