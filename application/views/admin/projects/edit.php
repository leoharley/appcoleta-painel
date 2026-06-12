<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Editar Projeto</h2>
            <div>
                <a href="<?= base_url('projects/view/' . $project->id) ?>" class="btn btn-info me-2">
                    <i class="fas fa-eye me-2"></i>
                    Visualizar
                </a>
                <a href="<?= base_url('projects') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Voltar
                </a>
            </div>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <?= $error ?>
</div>
<?php endif; ?>

<?= form_open('projects/edit/' . $project->id, ['id' => 'projectForm', 'novalidate' => 'novalidate']) ?>
<div class="row">
    <div class="col-lg-8">
        <!-- Informações Básicas -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Informações Básicas</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Nome do Projeto *</label>
                    <input type="text" class="form-control <?= form_error('name') ? 'is-invalid' : '' ?>" 
                           id="name" name="name" 
                           value="<?= set_value('name', $project->name) ?>" required maxlength="200"
                           placeholder="Digite o nome do projeto">
                    <?= form_error('name', '<div class="invalid-feedback">', '</div>') ?>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea class="form-control <?= form_error('description') ? 'is-invalid' : '' ?>" 
                              id="description" name="description" 
                              rows="4" maxlength="1000"
                              placeholder="Descreva os objetivos e escopo do projeto"><?= set_value('description', $project->description) ?></textarea>
                    <?= form_error('description', '<div class="invalid-feedback">', '</div>') ?>
                    <small class="form-text text-muted">
                        <span id="charCount">0</span>/1000 caracteres
                    </small>
                </div>
                
                <div class="mb-3">
                    <label for="client_name" class="form-label">Nome do Cliente</label>
                    <input type="text" class="form-control <?= form_error('client_name') ? 'is-invalid' : '' ?>" 
                           id="client_name" name="client_name" 
                           value="<?= set_value('client_name', $project->client_name) ?>" maxlength="200"
                           placeholder="Nome da empresa ou pessoa contratante">
                    <?= form_error('client_name', '<div class="invalid-feedback">', '</div>') ?>
                </div>
            </div>
        </div>
        
        <!-- Cronograma e Orçamento -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Cronograma e Orçamento</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label for="start_date" class="form-label">Data de Início</label>
                        <input type="date" class="form-control <?= form_error('start_date') ? 'is-invalid' : '' ?>" 
                               id="start_date" name="start_date" 
                               value="<?= set_value('start_date', $project->start_date) ?>"
                               min="2020-01-01" max="2050-12-31">
                        <?= form_error('start_date', '<div class="invalid-feedback">', '</div>') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="end_date" class="form-label">Data de Fim</label>
                        <input type="date" class="form-control <?= form_error('end_date') ? 'is-invalid' : '' ?>" 
                               id="end_date" name="end_date" 
                               value="<?= set_value('end_date', $project->end_date) ?>"
                               min="2020-01-01" max="2050-12-31">
                        <?= form_error('end_date', '<div class="invalid-feedback">', '</div>') ?>
                        <small class="form-text text-muted">A data de fim deve ser posterior à data de início</small>
                    </div>
                </div>
                
                <div class="mt-3">
                    <label for="budget" class="form-label">Orçamento (R$)</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" class="form-control <?= form_error('budget') ? 'is-invalid' : '' ?>" 
                               id="budget" name="budget" 
                               value="<?= set_value('budget', $project->budget) ?>" step="0.01" min="0"
                               placeholder="0,00">
                        <?= form_error('budget', '<div class="invalid-feedback">', '</div>') ?>
                    </div>
                </div>
                
                <div class="mt-3">
                    <div id="durationInfo" class="alert alert-info" style="display: none;">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="durationText"></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Questionários Vinculados -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Questionários Vinculados (<?= count($questionnaires) ?>)</h5>
                <a href="<?= base_url('questionnaires/create?project_id=' . $project->id) ?>" 
                   class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>
                    Novo Questionário
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($questionnaires)): ?>
                    <div class="row">
                        <?php foreach ($questionnaires as $questionnaire): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card border">
                                <div class="card-body p-3">
                                    <h6 class="card-title mb-2"><?= $questionnaire->title ?></h6>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <?php 
                                        $status_class = $questionnaire->status == 'active' ? 'success' : 'warning';
                                        $status_text = $questionnaire->status == 'active' ? 'Ativo' : 'Pausado';
                                        ?>
                                        <span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span>
                                        <small class="text-muted">
                                            <?= date('d/m/Y', strtotime($questionnaire->created_at)) ?>
                                        </small>
                                    </div>
                                    <?php if ($questionnaire->description): ?>
                                        <p class="card-text small text-muted mb-2">
                                            <?= character_limiter($questionnaire->description, 80) ?>
                                        </p>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-question-circle me-1"></i>
                                            <?= $questionnaire->question_count ?? 0 ?> perguntas
                                        </small>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url('questionnaires/edit/' . $questionnaire->id) ?>" 
                                               class="btn btn-outline-primary btn-sm" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= base_url('responses?questionnaire_id=' . $questionnaire->id) ?>" 
                                               class="btn btn-outline-success btn-sm" title="Respostas">
                                                <i class="fas fa-chart-bar"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-3">Nenhum questionário vinculado a este projeto ainda.</p>
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
    
    <div class="col-lg-4">
        <!-- Configurações -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Configurações</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?= set_select('status', 'active', $project->status == 'active') ?>>Ativo</option>
                        <option value="paused" <?= set_select('status', 'paused', $project->status == 'paused') ?>>Pausado</option>
                        <option value="completed" <?= set_select('status', 'completed', $project->status == 'completed') ?>>Concluído</option>
                        <option value="cancelled" <?= set_select('status', 'cancelled', $project->status == 'cancelled') ?>>Cancelado</option>
                    </select>
                    <small class="form-text text-muted">
                        Projetos inativos não podem receber novos questionários
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Estatísticas -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Estatísticas</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary mb-0"><?= count($questionnaires) ?></h4>
                        <small class="text-muted">Questionários</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-0">
                            <?= array_sum(array_column($questionnaires, 'response_count') ?? [0]) ?>
                        </h4>
                        <small class="text-muted">Respostas</small>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <small class="text-muted">
                        <strong>Criado:</strong> <?= date('d/m/Y H:i', strtotime($project->created_at)) ?>
                        <br>por <?= $project->created_by_name ?>
                    </small>
                </div>
                
                <?php if ($project->start_date || $project->end_date): ?>
                <hr>
                <div class="text-center">
                    <small class="text-muted">
                        <?php if ($project->start_date && $project->end_date): ?>
                            <?php 
                            $progress = 0;
                            $start = strtotime($project->start_date);
                            $end = strtotime($project->end_date);
                            $now = time();
                            
                            if ($now >= $start && $now <= $end) {
                                $progress = round((($now - $start) / ($end - $start)) * 100, 1);
                            } elseif ($now > $end) {
                                $progress = 100;
                            }
                            ?>
                            <strong>Progresso temporal:</strong><br>
                            <div class="progress mt-2 mb-2" style="height: 20px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?= $progress ?>%"
                                     aria-valuenow="<?= $progress ?>" 
                                     aria-valuemin="0" aria-valuemax="100">
                                    <?= $progress ?>%
                                </div>
                            </div>
                            <?= date('d/m/Y', strtotime($project->start_date)) ?> - 
                            <?= date('d/m/Y', strtotime($project->end_date)) ?>
                        <?php elseif ($project->start_date): ?>
                            <strong>Início:</strong> <?= date('d/m/Y', strtotime($project->start_date)) ?>
                        <?php elseif ($project->end_date): ?>
                            <strong>Prazo:</strong> <?= date('d/m/Y', strtotime($project->end_date)) ?>
                        <?php endif; ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Ações -->
        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary w-100 mb-2" id="submitBtn">
                    <i class="fas fa-save me-2"></i>
                    Salvar Alterações
                </button>
                <a href="<?= base_url('projects/duplicate/' . $project->id) ?>" 
                   class="btn btn-outline-secondary w-100 mb-2">
                    <i class="fas fa-copy me-2"></i>
                    Duplicar Projeto
                </a>
                <a href="<?= base_url('projects/view/' . $project->id) ?>" 
                   class="btn btn-outline-info w-100 mb-2">
                    <i class="fas fa-eye me-2"></i>
                    Visualizar Detalhes
                </a>
                <a href="<?= base_url('projects') ?>" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-times me-2"></i>
                    Cancelar
                </a>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const descriptionInput = document.getElementById('description');
    const charCountSpan = document.getElementById('charCount');
    const durationInfo = document.getElementById('durationInfo');
    const durationText = document.getElementById('durationText');
    const form = document.getElementById('projectForm');
    const submitBtn = document.getElementById('submitBtn');
    
    // Função para validar data
    function isValidDate(dateString) {
        if (!dateString) return true; // Campo não obrigatório
        
        const date = new Date(dateString);
        const year = date.getFullYear();
        
        return !isNaN(date.getTime()) && year >= 2020 && year <= 2050;
    }
    
    // Contador de caracteres para descrição
    function updateCharCount() {
        const count = descriptionInput.value.length;
        charCountSpan.textContent = count;
        
        if (count > 900) {
            charCountSpan.style.color = '#dc3545';
        } else if (count > 700) {
            charCountSpan.style.color = '#ffc107';
        } else {
            charCountSpan.style.color = '#6c757d';
        }
    }
    
    // Cálculo de duração do projeto
    function updateDuration() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        
        // Limpar classes de erro primeiro
        startDateInput.classList.remove('is-invalid');
        endDateInput.classList.remove('is-invalid');
        
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            
            // Validar datas individualmente
            if (!isValidDate(startDate)) {
                startDateInput.classList.add('is-invalid');
                durationInfo.className = 'alert alert-danger';
                durationText.textContent = 'Data de início inválida!';
                durationInfo.style.display = 'block';
                return;
            }
            
            if (!isValidDate(endDate)) {
                endDateInput.classList.add('is-invalid');
                durationInfo.className = 'alert alert-danger';
                durationText.textContent = 'Data de fim inválida!';
                durationInfo.style.display = 'block';
                return;
            }
            
            const diffTime = end - start;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays < 0) {
                endDateInput.classList.add('is-invalid');
                durationInfo.className = 'alert alert-danger';
                durationText.textContent = 'A data de fim deve ser posterior à data de início!';
                durationInfo.style.display = 'block';
            } else if (diffDays === 0) {
                durationInfo.className = 'alert alert-warning';
                durationText.textContent = 'Projeto de apenas 1 dia';
                durationInfo.style.display = 'block';
            } else {
                durationInfo.className = 'alert alert-info';
                const weeks = Math.floor(diffDays / 7);
                const remainingDays = diffDays % 7;
                
                let durationStr = `Duração: ${diffDays} dias`;
                if (weeks > 0) {
                    durationStr += ` (${weeks} semana${weeks > 1 ? 's' : ''}`;
                    if (remainingDays > 0) {
                        durationStr += ` e ${remainingDays} dia${remainingDays > 1 ? 's' : ''}`;
                    }
                    durationStr += ')';
                }
                
                durationText.textContent = durationStr;
                durationInfo.style.display = 'block';
            }
        } else {
            durationInfo.style.display = 'none';
        }
    }
    
    // Event listeners
    descriptionInput.addEventListener('input', updateCharCount);
    startDateInput.addEventListener('change', updateDuration);
    endDateInput.addEventListener('change', updateDuration);
    
    // Inicializar contadores
    updateCharCount();
    updateDuration();
    
    // Quando a data de início muda, ajustar a data mínima da data de fim
    startDateInput.addEventListener('change', function() {
        if (this.value) {
            endDateInput.setAttribute('min', this.value);
            if (endDateInput.value && endDateInput.value < this.value) {
                endDateInput.value = '';
                updateDuration();
            }
        }
    });
    
    // Validação do formulário
    form.addEventListener('submit', function(e) {
        let hasErrors = false;
        const errors = [];
        
        // Validar nome do projeto
        const name = document.getElementById('name').value.trim();
        if (!name) {
            hasErrors = true;
            errors.push('Nome do projeto é obrigatório');
            document.getElementById('name').classList.add('is-invalid');
        }
        
        // Validar datas
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        
        if (startDate && !isValidDate(startDate)) {
            hasErrors = true;
            errors.push('Data de início inválida');
            startDateInput.classList.add('is-invalid');
        }
        
        if (endDate && !isValidDate(endDate)) {
            hasErrors = true;
            errors.push('Data de fim inválida');
            endDateInput.classList.add('is-invalid');
        }
        
        if (startDate && endDate && isValidDate(startDate) && isValidDate(endDate)) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            
            if (end <= start) {
                hasErrors = true;
                errors.push('A data de fim deve ser posterior à data de início');
                endDateInput.classList.add('is-invalid');
            }
        }
        
        // Validar orçamento
        const budget = document.getElementById('budget').value;
        if (budget && (isNaN(budget) || parseFloat(budget) < 0)) {
            hasErrors = true;
            errors.push('Orçamento deve ser um valor positivo');
            document.getElementById('budget').classList.add('is-invalid');
        }
        
        if (hasErrors) {
            e.preventDefault();
            
            let errorMsg = 'Por favor, corrija os seguintes erros:\n\n';
            errors.forEach(error => {
                errorMsg += '• ' + error + '\n';
            });
            
            alert(errorMsg);
            submitBtn.disabled = false;
            return false;
        }
        
        // Desabilitar botão para evitar duplo envio
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvando...';
    });
    
    // Remover classes de erro ao corrigir campos
    ['name', 'description', 'client_name', 'budget'].forEach(fieldId => {
        document.getElementById(fieldId).addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });
    
    startDateInput.addEventListener('change', function() {
        this.classList.remove('is-invalid');
    });
    
    endDateInput.addEventListener('change', function() {
        this.classList.remove('is-invalid');
    });
});
</script>