<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Questionários do Projeto</h2>
                <h4 class="text-muted"><?= $project->name ?></h4>
            </div>
            <div>
                <a href="<?= base_url('questionnaires/create?project_id=' . $project->id) ?>" class="btn btn-primary me-2">
                    <i class="fas fa-plus me-2"></i>
                    Novo Questionário
                </a>
                <a href="<?= base_url('projects/view/' . $project->id) ?>" class="btn btn-info me-2">
                    <i class="fas fa-project-diagram me-2"></i>
                    Ver Projeto
                </a>
                <a href="<?= base_url('questionnaires') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Todos os Questionários
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Informações do Projeto -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h5 class="mb-2"><?= $project->name ?></h5>
                <?php if ($project->description): ?>
                    <p class="text-muted mb-2"><?= $project->description ?></p>
                <?php endif; ?>
                
                <div class="row">
                    <?php if ($project->client_name): ?>
                    <div class="col-md-6">
                        <small class="text-muted">Cliente:</small>
                        <strong><?= $project->client_name ?></strong>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($project->start_date || $project->end_date): ?>
                    <div class="col-md-6">
                        <small class="text-muted">Período:</small>
                        <strong>
                            <?= $project->start_date ? date('d/m/Y', strtotime($project->start_date)) : 'Sem início' ?>
                            até
                            <?= $project->end_date ? date('d/m/Y', strtotime($project->end_date)) : 'Sem fim' ?>
                        </strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <?php 
                $status_config = [
                    'active' => ['class' => 'success', 'text' => 'Ativo', 'icon' => 'play-circle'],
                    'paused' => ['class' => 'warning', 'text' => 'Pausado', 'icon' => 'pause-circle'],
                    'completed' => ['class' => 'primary', 'text' => 'Concluído', 'icon' => 'check-circle'],
                    'cancelled' => ['class' => 'danger', 'text' => 'Cancelado', 'icon' => 'times-circle']
                ];
                $status = $status_config[$project->status] ?? $status_config['active'];
                ?>
                <span class="badge bg-<?= $status['class'] ?> fs-6 mb-2">
                    <i class="fas fa-<?= $status['icon'] ?> me-1"></i>
                    <?= $status['text'] ?>
                </span>
                
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary mb-0"><?= count($questionnaires) ?></h4>
                        <small class="text-muted">Questionários</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-0"><?= array_sum(array_column($questionnaires, 'response_count')) ?></h4>
                        <small class="text-muted">Respostas</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($questionnaires)): ?>
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Questionários (<?= count($questionnaires) ?>)</h5>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary active" onclick="filterByStatus('all')">
                    Todos
                </button>
                <button type="button" class="btn btn-outline-success" onclick="filterByStatus('active')">
                    Ativos
                </button>
                <button type="button" class="btn btn-outline-warning" onclick="filterByStatus('paused')">
                    Pausados
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="questionnairesList">
                <thead>
                    <tr>
                        <th>Questionário</th>
                        <th>Status</th>
                        <th>Perguntas</th>
                        <th>Respostas</th>
                        <th>Aplicadores</th>
                        <th>Criado em</th>
                        <th>Última Resposta</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questionnaires as $questionnaire): ?>
                    <tr data-status="<?= $questionnaire->status ?>">
                        <td>
                            <div>
                                <strong><?= $questionnaire->title ?></strong>
                                <?php if ($questionnaire->description): ?>
                                    <br><small class="text-muted"><?= character_limiter($questionnaire->description, 60) ?></small>
                                <?php endif; ?>
                                
                                <!-- Badges de configuração -->
                                <div class="mt-1">
                                    <?php if ($questionnaire->requires_consent): ?>
                                        <span class="badge bg-info" title="Requer consentimento">
                                            <i class="fas fa-handshake"></i>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($questionnaire->requires_location): ?>
                                        <span class="badge bg-warning" title="Captura localização">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($questionnaire->requires_photo): ?>
                                        <span class="badge bg-secondary" title="Requer foto">
                                            <i class="fas fa-camera"></i>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($questionnaire->estimated_time): ?>
                                        <span class="badge bg-light text-dark" title="Tempo estimado">
                                            <i class="fas fa-clock"></i> <?= $questionnaire->estimated_time ?>min
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php 
                            $status_class = $questionnaire->status == 'active' ? 'success' : 'warning';
                            $status_text = $questionnaire->status == 'active' ? 'Ativo' : 'Pausado';
                            if ($questionnaire->status == 'inactive') {
                                $status_class = 'secondary';
                                $status_text = 'Inativo';
                            }
                            ?>
                            <span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span>
                        </td>
                        <td>
                            <span class="badge bg-info"><?= $questionnaire->question_count ?></span>
                        </td>
                        <td>
                            <span class="badge bg-primary"><?= $questionnaire->response_count ?></span>
                        </td>
                        <td>
                            <?php 
                            // Exibir informações sobre aplicadores
                            if (empty($questionnaire->aplicadores) || $questionnaire->aplicadores === null):
                            ?>
                                <span class="badge bg-success" title="Todos os aplicadores">
                                    <i class="fas fa-users me-1"></i>
                                    Todos
                                </span>
                            <?php 
                            else:
                                $aplicadores_ids = json_decode($questionnaire->aplicadores, true);
                                if (is_array($aplicadores_ids)):
                                    $count = count($aplicadores_ids);
                            ?>
                                <span class="badge bg-info" title="<?= $count ?> aplicadores específicos">
                                    <i class="fas fa-user me-1"></i>
                                    <?= $count ?>
                                </span>
                            <?php 
                                endif;
                            endif; 
                            ?>
                        </td>
                        <td>
                            <div>
                                <?= $questionnaire->created_by_name ?>
                                <br><small class="text-muted"><?= date('d/m/Y H:i', strtotime($questionnaire->created_at)) ?></small>
                            </div>
                        </td>
                        <td>
                            <?php if (isset($questionnaire->last_response) && $questionnaire->last_response): ?>
                                <small><?= date('d/m/Y H:i', strtotime($questionnaire->last_response)) ?></small>
                            <?php else: ?>
                                <small class="text-muted">Nunca</small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?= base_url('questionnaires/edit/' . $questionnaire->id) ?>" 
                                   class="btn btn-outline-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= base_url('questionnaires/duplicate/' . $questionnaire->id) ?>" 
                                   class="btn btn-outline-secondary" title="Duplicar">
                                    <i class="fas fa-copy"></i>
                                </a>
                                <a href="<?= base_url('responses?questionnaire_id=' . $questionnaire->id) ?>" 
                                   class="btn btn-outline-success" title="Ver Respostas">
                                    <i class="fas fa-chart-bar"></i>
                                </a>
                                <?php if ($questionnaire->response_count == 0): ?>
                                <button type="button" class="btn btn-outline-danger" 
                                        onclick="deleteQuestionnaire(<?= $questionnaire->id ?>)" title="Excluir">
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

<?php else: ?>
<!-- Estado vazio -->
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-clipboard-list fa-4x text-muted mb-4"></i>
        <h4 class="text-muted">Nenhum questionário encontrado</h4>
        <p class="text-muted mb-4">Este projeto ainda não possui questionários associados.</p>
        
        <div class="d-flex justify-content-center gap-2">
            <a href="<?= base_url('questionnaires/create?project_id=' . $project->id) ?>" 
               class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>
                Criar Primeiro Questionário
            </a>
            <a href="<?= base_url('questionnaires') ?>" 
               class="btn btn-outline-secondary">
                <i class="fas fa-search me-2"></i>
                Vincular Questionário Existente
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function deleteQuestionnaire(id) {
    if (confirm('Tem certeza que deseja excluir este questionário? Esta ação não pode ser desfeita.')) {
        window.location.href = '<?= base_url('questionnaires/delete/') ?>' + id;
    }
}

function filterByStatus(status) {
    const table = document.getElementById('questionnairesList');
    const rows = table.querySelectorAll('tbody tr');
    const buttons = document.querySelectorAll('.btn-group .btn');
    
    // Atualizar estado dos botões
    buttons.forEach(btn => btn.classList.remove('active'));
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

// Adicionar tooltips
document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
</script>