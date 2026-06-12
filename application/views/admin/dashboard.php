<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Painel de Controle</h2>
                <p class="text-muted mb-0">Visão geral do sistema de coleta</p>
            </div>
            <div class="text-end">
                <span class="badge bg-light text-dark p-2">
                    <i class="fas fa-clock me-1"></i>
                    <?= date('d/m/Y H:i') ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards - Nova ordem e novos nomes -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="stat-label mb-1">Coletas Realizadas</p>
                    <h3 class="stat-number"><?= $stats['total_responses'] ?></h3>
                    <small class="text-success">
                        <i class="fas fa-arrow-up me-1"></i>
                        <?= $stats['responses_today'] ?> novas hoje
                    </small>
                </div>
                <div class="stat-icon" style="background: linear-gradient(45deg, #007bff, #0056b3);">
                    <i class="fas fa-database"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="stat-label mb-1">Formulários Ativos</p>
                    <h3 class="stat-number"><?= $stats['active_questionnaires'] ?></h3>
                    <small class="text-muted">
                        <i class="fas fa-layer-group me-1"></i>
                        <?= $stats['total_questionnaires'] ?> no total
                    </small>
                </div>
                <div class="stat-icon" style="background: linear-gradient(45deg, #E87722, #f08a3a);">
                    <i class="fas fa-poll"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="stat-label mb-1">Membros da Equipe</p>
                    <h3 class="stat-number"><?= $stats['total_users'] ?></h3>
                    <small class="text-info">
                        <i class="fas fa-user-check me-1"></i>
                        Ativos no sistema
                    </small>
                </div>
                <div class="stat-icon" style="background: linear-gradient(45deg, #17a2b8, #138496);">
                    <i class="fas fa-user-friends"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="stat-label mb-1">Aguardando Envio</p>
                    <h3 class="stat-number"><?= $stats['pending_sync'] ?></h3>
                    <small class="text-warning">
                        <i class="fas fa-hourglass-half me-1"></i>
                        Em processamento
                    </small>
                </div>
                <div class="stat-icon" style="background: linear-gradient(45deg, #ffc107, #e0a800);">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row - Ordem invertida -->
<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Situação das Coletas</h5>
            </div>
            <div class="card-body">
                <canvas id="syncChart" width="200" height="200"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i>Evolução Mensal</h5>
            </div>
            <div class="card-body">
                <canvas id="responsesChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity - Nova estrutura -->
<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-fire me-2"></i>Formulários Populares</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($charts['questionnaires_usage'])): ?>
                    <?php foreach ($charts['questionnaires_usage'] as $index => $usage): ?>
                        <div class="d-flex align-items-center mb-3 p-2 rounded <?= $index == 0 ? 'bg-light' : '' ?>">
                            <div class="me-3">
                                <span class="badge bg-<?= $index < 3 ? 'primary' : 'secondary' ?> rounded-pill"><?= $index + 1 ?>º</span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0"><?= character_limiter($usage->title, 25) ?></h6>
                                <div class="progress mt-1" style="height: 4px;">
                                    <div class="progress-bar" style="width: <?= min(100, ($usage->response_count / max(1, $charts['questionnaires_usage'][0]->response_count)) * 100) ?>%; background-color: var(--primary-color);"></div>
                                </div>
                            </div>
                            <div class="ms-2">
                                <span class="badge bg-light text-dark"><?= $usage->response_count ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chart-bar fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">Nenhum dado disponível</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Últimas Coletas</h5>
                <a href="<?= base_url('responses') ?>" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($recent_responses)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Formulário</th>
                                    <th>Coletor</th>
                                    <th>Quando</th>
                                    <th class="text-center">Situação</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_responses as $response): ?>
                                <tr>
                                    <td>
                                        <strong><?= character_limiter($response->questionnaire_title, 20) ?></strong>
                                    </td>
                                    <td>
                                        <small><?= $response->applied_by_name ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d/m H:i', strtotime($response->completed_at)) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $status_class = $response->sync_status == 'synced' ? 'success' : 'warning';
                                        $status_icon = $response->sync_status == 'synced' ? 'check-circle' : 'clock';
                                        ?>
                                        <i class="fas fa-<?= $status_icon ?> text-<?= $status_class ?>"></i>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('responses/view/' . $response->id) ?>" class="btn btn-sm btn-link p-0">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Nenhuma coleta registrada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Gráfico de Evolução - Tipo bar ao invés de line
const responsesCtx = document.getElementById('responsesChart').getContext('2d');
const responsesChart = new Chart(responsesCtx, {
    type: 'bar',
    data: {
        labels: [<?php
            echo '"' . implode('", "', array_map(function($item) {
                return date('d/m', strtotime($item->date));
            }, $charts['responses_by_day'])) . '"';
        ?>],
        datasets: [{
            label: 'Coletas',
            data: [<?= implode(', ', array_column($charts['responses_by_day'], 'count')) ?>],
            backgroundColor: 'rgba(232, 119, 34, 0.7)',
            borderColor: '#E87722',
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});

// Gráfico de Situação - Tipo pie ao invés de doughnut
const syncCtx = document.getElementById('syncChart').getContext('2d');
const syncChart = new Chart(syncCtx, {
    type: 'pie',
    data: {
        labels: [<?php
            echo '"' . implode('", "', array_map(function($item) {
                $labels = ['synced' => 'Enviadas', 'pending' => 'Pendentes', 'error' => 'Com erro'];
                return $labels[$item->sync_status] ?? ucfirst($item->sync_status);
            }, $charts['sync_status'])) . '"';
        ?>],
        datasets: [{
            data: [<?= implode(', ', array_column($charts['sync_status'], 'count')) ?>],
            backgroundColor: [
                '#28a745',
                '#ffc107',
                '#dc3545'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right'
            }
        }
    }
});
</script>
