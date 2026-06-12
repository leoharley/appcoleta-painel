<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Questionários</h2>
            <div>
                <a href="<?= base_url('projects') ?>" class="btn btn-info me-2">
                    <i class="fas fa-project-diagram me-2"></i>
                    Gerenciar Projetos
                </a>
                <a href="<?= base_url('questionnaires/create') ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Novo Questionário
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label for="projectFilter" class="form-label">Filtrar por Projeto</label>
                <select class="form-select" id="projectFilter">
                    <option value="">Todos os projetos</option>
                    <option value="no-project">Sem projeto</option>
                    <?php foreach ($projects as $project): ?>
                    <option value="<?= $project->id ?>"><?= $project->name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="statusFilter" class="form-label">Filtrar por Status</label>
                <select class="form-select" id="statusFilter">
                    <option value="">Todos os status</option>
                    <option value="active">Ativo</option>
                    <option value="paused">Pausado</option>
                    <option value="inactive">Inativo</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="searchInput" class="form-label">Buscar</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="searchInput" placeholder="Buscar questionário...">
                    <button class="btn btn-outline-secondary" type="button" onclick="clearFilters()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table" id="questionnairesTable">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Projeto</th>
                        <th>Status</th>
                        <th>Perguntas</th>
                        <th>Respostas</th>
                        <th>Aplicadores</th>
                        <th>Criado por</th>
                        <th>Data Criação</th>
                        <th>Última Resposta</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questionnaires as $questionnaire): ?>
                    <?php if ($questionnaire->status == 'active'): ?>
                    <tr data-project="<?= $questionnaire->project_id ?: 'no-project' ?>" 
                        data-status="<?= $questionnaire->status ?>"
                        data-title="<?= strtolower($questionnaire->title) ?>"
                        data-description="<?= strtolower($questionnaire->description ?? '') ?>">
                        <td>
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
                            </div>
                        </td>
                        <td>
                            <?php if ($questionnaire->project_id && $questionnaire->project_name): ?>
                                <a href="<?= base_url('projects/view/' . $questionnaire->project_id) ?>" 
                                   class="text-decoration-none" title="Ver projeto">
                                    <i class="fas fa-project-diagram me-1"></i>
                                    <?= $questionnaire->project_name ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">
                                    <i class="fas fa-minus me-1"></i>
                                    Sem projeto
                                </span>
                            <?php endif; ?>
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
                                <span class="badge bg-success" title="Todos os aplicadores podem usar este questionário">
                                    <i class="fas fa-users me-1"></i>
                                    Todos
                                </span>
                            <?php 
                            else:
                                $aplicadores_ids = json_decode($questionnaire->aplicadores, true);
                                if (is_array($aplicadores_ids)):
                                    $count = count($aplicadores_ids);
                            ?>
                                <span class="badge bg-info" title="<?= $count ?> aplicadores específicos selecionados">
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
                                <br><small class="text-muted"><?= date('d/m/Y', strtotime($questionnaire->created_at)) ?></small>
                            </div>
                        </td>
                        <td>
                            <small><?= date('d/m/Y', strtotime($questionnaire->created_at)) ?></small>
                            <?php if ($questionnaire->estimated_time): ?>
                                <br><small class="text-muted"><?= $questionnaire->estimated_time ?> min</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($questionnaire->last_response): ?>
                                <small><?= date('d/m/Y H:i', strtotime($questionnaire->last_response)) ?></small>
                            <?php else: ?>
                                <small class="text-muted">Nunca</small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <a href="<?= base_url('questionnaires/edit/' . $questionnaire->id) ?>" 
                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= base_url('questionnaires/duplicate/' . $questionnaire->id) ?>" 
                                   class="btn btn-sm btn-outline-secondary" title="Duplicar">
                                    <i class="fas fa-copy"></i>
                                </a>
                                <a href="<?= base_url('responses?questionnaire_id=' . $questionnaire->id) ?>" 
                                   class="btn btn-sm btn-outline-success" title="Ver Respostas">
                                    <i class="fas fa-chart-bar"></i>
                                </a>
                                <?php if ($questionnaire->project_id): ?>
                                    <a href="<?= base_url('projects/view/' . $questionnaire->project_id) ?>" 
                                       class="btn btn-sm btn-outline-info" title="Ver Projeto">
                                        <i class="fas fa-project-diagram"></i>
                                    </a>
                                <?php endif; ?>
                                <?php //if ($questionnaire->response_count == 0): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteQuestionnaire(<?= $questionnaire->id ?>)" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php //endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>   
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Estatísticas Rápidas -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-primary"><?= count($questionnaires) ?></h3>
                <p class="mb-0">Total de Questionários</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-success"><?= count(array_filter($questionnaires, function($q) { return $q->status == 'active'; })) ?></h3>
                <p class="mb-0">Questionários Ativos</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-info"><?= count(array_filter($questionnaires, function($q) { return $q->project_id; })) ?></h3>
                <p class="mb-0">Vinculados a Projetos</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-warning"><?= array_sum(array_column($questionnaires, 'response_count')) ?></h3>
                <p class="mb-0">Total de Respostas</p>
            </div>
        </div>
    </div>
</div>

<script>
function deleteQuestionnaire(id) {
    if (confirm('Tem certeza que deseja excluir este questionário? Esta ação não pode ser desfeita.')) {
        window.location.href = '<?= base_url('questionnaires/delete/') ?>' + id;
    }
}

// Sistema de filtros
document.addEventListener('DOMContentLoaded', function() {
    const projectFilter = document.getElementById('projectFilter');
    const statusFilter = document.getElementById('statusFilter');
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('questionnairesTable');
    const rows = table.querySelectorAll('tbody tr');
    
    function applyFilters() {
        const projectValue = projectFilter.value;
        const statusValue = statusFilter.value;
        const searchValue = searchInput.value.toLowerCase();
        
        rows.forEach(row => {
            let showRow = true;
            
            // Filtro de projeto
            if (projectValue && row.dataset.project !== projectValue) {
                showRow = false;
            }
            
            // Filtro de status
            if (statusValue && row.dataset.status !== statusValue) {
                showRow = false;
            }
            
            // Filtro de busca
            if (searchValue) {
                const title = row.dataset.title || '';
                const description = row.dataset.description || '';
                
                if (!title.includes(searchValue) && !description.includes(searchValue)) {
                    showRow = false;
                }
            }
            
            row.style.display = showRow ? '' : 'none';
        });
        
        // Atualizar contador
        updateResultsCounter();
    }
    
    function updateResultsCounter() {
        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
        const total = rows.length;
        const visible = visibleRows.length;
        
        // Criar ou atualizar contador (opcional)
        let counter = document.getElementById('resultsCounter');
        if (!counter) {
            counter = document.createElement('small');
            counter.id = 'resultsCounter';
            counter.className = 'text-muted';
            table.parentNode.insertBefore(counter, table);
        }
        
        if (visible !== total) {
            counter.textContent = `Mostrando ${visible} de ${total} questionários`;
            counter.style.display = 'block';
        } else {
            counter.style.display = 'none';
        }
    }
    
    // Event listeners
    projectFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);
    searchInput.addEventListener('input', debounce(applyFilters, 300));
    
    // Função debounce para otimizar a busca
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});

function clearFilters() {
    document.getElementById('projectFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('searchInput').value = '';
    
    // Mostrar todas as linhas
    const rows = document.querySelectorAll('#questionnairesTable tbody tr');
    rows.forEach(row => {
        row.style.display = '';
    });
    
    // Esconder contador
    const counter = document.getElementById('resultsCounter');
    if (counter) {
        counter.style.display = 'none';
    }
}

// Adicionar atalhos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + F para focar na busca
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        document.getElementById('searchInput').focus();
    }
    
    // Escape para limpar filtros
    if (e.key === 'Escape') {
        clearFilters();
    }
});

// Tooltip para badges de configuração
document.addEventListener('DOMContentLoaded', function() {
    // Adicionar tooltips do Bootstrap se disponível
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
</script>