<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Projetos</h2>
            <a href="<?= base_url('projects/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>
                Novo Projeto
            </a>
        </div>
    </div>
</div>

<!-- Cards de Estatísticas Rápidas -->
<div class="row mb-4">
    <?php 
    $total_projects = count($projects);
    $active_projects = count(array_filter($projects, function($p) { return $p->status == 'active'; }));
    $total_responses = array_sum(array_column($projects, 'total_responses'));
    $total_questionnaires = array_sum(array_column($projects, 'questionnaire_count'));
    ?>
    
    <div class="col-xl-3 col-md-6">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?= $total_projects ?></h4>
                        <p class="mb-0">Total de Projetos</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-project-diagram fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?= $active_projects ?></h4>
                        <p class="mb-0">Projetos Ativos</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-play-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card text-white bg-info mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?= $total_questionnaires ?></h4>
                        <p class="mb-0">Questionários</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clipboard-list fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?= $total_responses ?></h4>
                        <p class="mb-0">Respostas Coletadas</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-bar fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Lista de Projetos</h5>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="filterProjects('all')">
                    Todos
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="filterProjects('active')">
                    Ativos
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning" onclick="filterProjects('paused')">
                    Pausados
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="filterProjects('completed')">
                    Concluídos
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table" id="projectsTable">
                <thead>
                    <tr>
                        <th>Projeto</th>
                        <th>Cliente</th>
                        <th>Status</th>
                        <th>Progresso</th>
                        <th>Questionários</th>
                        <th>Respostas</th>
                        <th>Orçamento</th>
                        <th>Criado por</th>
                        <th>Última Atividade</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                    <tr data-status="<?= $project->status ?>">
                        <td>
                            <div>
                                <strong><?= $project->name ?></strong>
                                <?php if ($project->description): ?>
                                    <br><small class="text-muted"><?= character_limiter($project->description, 60) ?></small>
                                <?php endif; ?>
                                
                                <!-- Indicadores de prazo -->
                                <?php if ($project->end_date): ?>
                                    <?php 
                                    $days_remaining = (strtotime($project->end_date) - time()) / (60 * 60 * 24);
                                    if ($days_remaining < 0): 
                                    ?>
                                        <br><small class="text-danger">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            Vencido há <?= abs(round($days_remaining)) ?> dias
                                        </small>
                                    <?php elseif ($days_remaining <= 7): ?>
                                        <br><small class="text-warning">
                                            <i class="fas fa-clock me-1"></i>
                                            Vence em <?= round($days_remaining) ?> dias
                                        </small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?= $project->client_name ?: '<span class="text-muted">Não informado</span>' ?>
                        </td>
                        <td>
                            <?php 
                            $status_config = [
                                'active' => ['class' => 'success', 'text' => 'Ativo', 'icon' => 'play-circle'],
                                'paused' => ['class' => 'warning', 'text' => 'Pausado', 'icon' => 'pause-circle'],
                                'completed' => ['class' => 'primary', 'text' => 'Concluído', 'icon' => 'check-circle'],
                                'cancelled' => ['class' => 'danger', 'text' => 'Cancelado', 'icon' => 'times-circle']
                            ];
                            $status = $status_config[$project->status] ?? $status_config['active'];
                            ?>
                            <span class="badge bg-<?= $status['class'] ?>">
                                <i class="fas fa-<?= $status['icon'] ?> me-1"></i>
                                <?= $status['text'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($project->progress !== null): ?>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?= $project->progress ?>%"
                                         aria-valuenow="<?= $project->progress ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        <?= $project->progress ?>%
                                    </div>
                                </div>
                                <?php if ($project->start_date && $project->end_date): ?>
                                    <small class="text-muted">
                                        <?= date('d/m/Y', strtotime($project->start_date)) ?> - 
                                        <?= date('d/m/Y', strtotime($project->end_date)) ?>
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Sem prazo definido</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-info"><?= $project->questionnaire_count ?></span>
                        </td>
                        <td>
                            <span class="badge bg-primary"><?= $project->total_responses ?></span>
                        </td>
                        <td>
                            <?php if ($project->budget): ?>
                                R$ <?= number_format($project->budget, 2, ',', '.') ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div>
                                <?= $project->created_by_name ?>
                                <br><small class="text-muted"><?= date('d/m/Y', strtotime($project->created_at)) ?></small>
                            </div>
                        </td>
                        <td>
                            <?php if ($project->last_activity): ?>
                                <small><?= date('d/m/Y H:i', strtotime($project->last_activity)) ?></small>
                            <?php else: ?>
                                <small class="text-muted">Sem atividade</small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <a href="<?= base_url('projects/view/' . $project->id) ?>" 
                                   class="btn btn-sm btn-outline-info" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= base_url('projects/edit/' . $project->id) ?>" 
                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= base_url('projects/duplicate/' . $project->id) ?>" 
                                   class="btn btn-sm btn-outline-secondary" title="Duplicar">
                                    <i class="fas fa-copy"></i>
                                </a>
                                <?php if ($project->questionnaire_count == 0): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteProject(<?= $project->id ?>)" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function deleteProject(id) {
    if (confirm('Tem certeza que deseja excluir este projeto? Esta ação não pode ser desfeita.')) {
        window.location.href = '<?= base_url('projects/delete/') ?>' + id;
    }
}

function filterProjects(status) {
    const table = document.getElementById('projectsTable');
    const rows = table.querySelectorAll('tbody tr');
    
    // Atualizar botões ativos
    document.querySelectorAll('.btn-group .btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Filtrar linhas
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Ativar o primeiro botão por padrão
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.btn-group .btn').classList.add('active');
});
</script>