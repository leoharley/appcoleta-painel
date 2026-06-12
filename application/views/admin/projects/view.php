<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><?= $project->name ?></h2>
                <?php 
                $status_config = [
                    'active' => ['class' => 'success', 'text' => 'Ativo', 'icon' => 'play-circle'],
                    'paused' => ['class' => 'warning', 'text' => 'Pausado', 'icon' => 'pause-circle'],
                    'completed' => ['class' => 'primary', 'text' => 'Concluído', 'icon' => 'check-circle'],
                    'cancelled' => ['class' => 'danger', 'text' => 'Cancelado', 'icon' => 'times-circle']
                ];
                $status = $status_config[$project->status] ?? $status_config['active'];
                ?>
                <span class="badge bg-<?= $status['class'] ?> fs-6">
                    <i class="fas fa-<?= $status['icon'] ?> me-1"></i>
                    <?= $status['text'] ?>
                </span>
            </div>
            <div>
                <a href="<?= base_url('projects/edit/' . $project->id) ?>" class="btn btn-primary me-2">
                    <i class="fas fa-edit me-2"></i>
                    Editar
                </a>
                <a href="<?= base_url('projects') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Voltar
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Informações do Projeto -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Informações do Projeto</h5>
            </div>
            <div class="card-body">
                <?php if ($project->description): ?>
                <div class="mb-3">
                    <h6>Descrição</h6>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($project->description)) ?></p>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <?php if ($project->client_name): ?>
                    <div class="col-md-6 mb-3">
                        <h6>Cliente</h6>
                        <p class="mb-0"><?= $project->client_name ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($project->budget): ?>
                    <div class="col-md-6 mb-3">
                        <h6>Orçamento</h6>
                        <p class="mb-0 text-success">R$ <?= number_format($project->budget, 2, ',', '.') ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-6 mb-3">
                        <h6>Criado por</h6>
                        <p class="mb-0"><?= $project->created_by_name ?></p>
                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($project->created_at)) ?></small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <h6>Última atualização</h6>
                        <p class="mb-0"><?= date('d/m/Y H:i', strtotime($project->updated_at)) ?></p>
                    </div>
                </div>
                
                <?php if ($project->start_date || $project->end_date): ?>
                <hr>
                <div class="row">
                    <?php if ($project->start_date): ?>
                    <div class="col-md-6">
                        <h6>Data de Início</h6>
                        <p class="mb-0"><?= date('d/m/Y', strtotime($project->start_date)) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($project->end_date): ?>
                    <div class="col-md-6">
                        <h6>Data de Fim</h6>
                        <p class="mb-0"><?= date('d/m/Y', strtotime($project->end_date)) ?></p>
                        <?php 
                        $days_remaining = (strtotime($project->end_date) - time()) / (60 * 60 * 24);
                        if ($days_remaining < 0): 
                        ?>
                            <small class="text-danger">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Vencido há <?= abs(round($days_remaining)) ?> dias
                            </small>
                        <?php elseif ($days_remaining <= 7): ?>
                            <small class="text-warning">
                                <i class="fas fa-clock me-1"></i>
                                Vence em <?= round($days_remaining) ?> dias
                            </small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($project->start_date && $project->end_date): ?>
                <div class="mt-3">
                    <h6>Progresso Temporal</h6>
                    <?php 
                    $start = strtotime($project->start_date);
                    $end = strtotime($project->end_date);
                    $now = time();
                    $progress = 0;
                    
                    if ($now >= $start && $now <= $end) {
                        $progress = round((($now - $start) / ($end - $start)) * 100, 1);
                    } elseif ($now > $end) {
                        $progress = 100;
                    }
                    
                    $total_days = round(($end - $start) / (60 * 60 * 24));
                    $elapsed_days = round(($now - $start) / (60 * 60 * 24));
                    ?>
                    <div class="progress mb-2" style="height: 25px;">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?= $progress ?>%"
                             aria-valuenow="<?= $progress ?>" 
                             aria-valuemin="0" aria-valuemax="100">
                            <?= $progress ?>%
                        </div>
                    </div>
                    <small class="text-muted">
                        <?= max(0, $elapsed_days) ?> de <?= $total_days ?> dias decorridos
                    </small>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Questionários -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Questionários (<?= count($questionnaires) ?>)</h5>
                <a href="<?= base_url('questionnaires/create?project_id=' . $project->id) ?>" 
                   class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>
                    Novo Questionário
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($questionnaires)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Questionário</th>
                                    <th>Status</th>
                                    <th>Perguntas</th>
                                    <th>Respostas</th>
                                    <th>Criado em</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($questionnaires as $questionnaire): ?>
                                <tr>
                                    <td>
                                        <strong><?= $questionnaire->title ?></strong>
                                        <?php if ($questionnaire->description): ?>
                                            <br><small class="text-muted">
                                                <?= character_limiter($questionnaire->description, 60) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_class = $questionnaire->status == 'active' ? 'success' : 'warning';
                                        $status_text = $questionnaire->status == 'active' ? 'Ativo' : 'Pausado';
                                        ?>
                                        <span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $questionnaire->question_count ?? 0 ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= $questionnaire->response_count ?? 0 ?></span>
                                    </td>
                                    <td>
                                        <small><?= date('d/m/Y', strtotime($questionnaire->created_at)) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url('questionnaires/edit/' . $questionnaire->id) ?>" 
                                               class="btn btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= base_url('responses?questionnaire_id=' . $questionnaire->id) ?>" 
                                               class="btn btn-outline-success" title="Ver Respostas">
                                                <i class="fas fa-chart-bar"></i>
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
                        <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum questionário criado</h5>
                        <p class="text-muted">Este projeto ainda não possui questionários vinculados.</p>
                        <a href="<?= base_url('questionnaires/create?project_id=' . $project->id) ?>" 
                           class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            Criar Primeiro Questionário
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar com Estatísticas -->
    <div class="col-lg-4">
        <!-- Estatísticas Gerais -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Estatísticas</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <h3 class="text-info mb-1"><?= $project_stats['questionnaires'] ?></h3>
                        <small class="text-muted">Questionários</small>
                    </div>
                    <div class="col-6 mb-3">
                        <h3 class="text-success mb-1"><?= $project_stats['responses'] ?></h3>
                        <small class="text-muted">Respostas</small>
                    </div>
                    <div class="col-6">
                        <h3 class="text-warning mb-1">
                            <?= $project_stats['by_status']['pending'] ?? 0 ?>
                        </h3>
                        <small class="text-muted">Pendentes</small>
                    </div>
                    <div class="col-6">
                        <h3 class="text-primary mb-1">
                            <?= $project_stats['by_status']['synced'] ?? 0 ?>
                        </h3>
                        <small class="text-muted">Sincronizadas</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Aplicadores -->
        <?php if (!empty($project_stats['top_aplicadores'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Top Aplicadores</h5>
            </div>
            <div class="card-body">
                <?php foreach ($project_stats['top_aplicadores'] as $aplicador): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span><?= $aplicador->full_name ?></span>
                    <span class="badge bg-primary"><?= $aplicador->response_count ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Atividade Recente -->
        <?php if (!empty($project_stats['recent_activity'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Atividade (30 dias)</h5>
            </div>
            <div class="card-body">
                <canvas id="activityChart" height="200"></canvas>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Ações Rápidas -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Ações Rápidas</h5>
            </div>
            <div class="card-body">
                <a href="<?= base_url('questionnaires/create?project_id=' . $project->id) ?>" 
                   class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-plus me-2"></i>
                    Novo Questionário
                </a>
                <a href="<?= base_url('responses?project_id=' . $project->id) ?>" 
                   class="btn btn-success w-100 mb-2">
                    <i class="fas fa-chart-line me-2"></i>
                    Ver Todas as Respostas
                </a>
                <a href="<?= base_url('projects/edit/' . $project->id) ?>" 
                   class="btn btn-outline-primary w-100 mb-2">
                    <i class="fas fa-edit me-2"></i>
                    Editar Projeto
                </a>
                <a href="<?= base_url('projects/duplicate/' . $project->id) ?>" 
                   class="btn btn-outline-secondary w-100">
                    <i class="fas fa-copy me-2"></i>
                    Duplicar Projeto
                </a>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($project_stats['recent_activity'])): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('activityChart').getContext('2d');
    
    const activityData = <?= json_encode($project_stats['recent_activity']) ?>;
    
    const labels = activityData.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('pt-BR', { month: 'short', day: 'numeric' });
    });
    
    const data = activityData.map(item => parseInt(item.count));
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Respostas',
                data: data,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.1,
                fill: true
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
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>