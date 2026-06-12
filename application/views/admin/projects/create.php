<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Criar Projeto</h2>
            <a href="<?= base_url('projects') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Voltar
            </a>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <?= $error ?>
</div>
<?php endif; ?>

<?php if (isset($validation_errors) && !empty($validation_errors)): ?>
<div class="alert alert-danger">
    <h6><i class="fas fa-exclamation-triangle me-2"></i>Erro de Validação:</h6>
    <ul class="mb-0">
        <?php foreach ($validation_errors as $field => $error): ?>
        <li><?= $error ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?= form_open('projects/create', ['id' => 'projectForm', 'novalidate' => 'novalidate']) ?>
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
                           value="<?= set_value('name') ?>" required maxlength="200"
                           placeholder="Digite o nome do projeto">
                    <?= form_error('name', '<div class="invalid-feedback">', '</div>') ?>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea class="form-control <?= form_error('description') ? 'is-invalid' : '' ?>" 
                              id="description" name="description" 
                              rows="4" maxlength="1000"
                              placeholder="Descreva os objetivos e escopo do projeto"><?= set_value('description') ?></textarea>
                    <?= form_error('description', '<div class="invalid-feedback">', '</div>') ?>
                    <small class="form-text text-muted">
                        <span id="charCount">0</span>/1000 caracteres
                    </small>
                </div>
                
                <div class="mb-3">
                    <label for="client_name" class="form-label">Nome do Cliente</label>
                    <input type="text" class="form-control <?= form_error('client_name') ? 'is-invalid' : '' ?>" 
                           id="client_name" name="client_name" 
                           value="<?= set_value('client_name') ?>" maxlength="200"
                           placeholder="Nome da empresa ou pessoa contratante">
                    <?= form_error('client_name', '<div class="invalid-feedback">', '</div>') ?>
                </div>
            </div>
        </div>
        
        <!-- Cronograma e Orçamento -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Cronograma e Orçamento</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label for="start_date" class="form-label">Data de Início</label>
                        <input type="date" class="form-control <?= (form_error('start_date') || (isset($validation_errors['start_date']))) ? 'is-invalid' : '' ?>" 
                               id="start_date" name="start_date" 
                               value="<?= set_value('start_date') ?>"
                               min="2020-01-01" max="2050-12-31">
                        <?php if (form_error('start_date')): ?>
                            <?= form_error('start_date', '<div class="invalid-feedback">', '</div>') ?>
                        <?php elseif (isset($validation_errors['start_date'])): ?>
                            <div class="invalid-feedback"><?= $validation_errors['start_date'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label for="end_date" class="form-label">Data de Fim</label>
                        <input type="date" class="form-control <?= (form_error('end_date') || (isset($validation_errors['end_date']))) ? 'is-invalid' : '' ?>" 
                               id="end_date" name="end_date" 
                               value="<?= set_value('end_date') ?>"
                               min="2020-01-01" max="2050-12-31">
                        <?php if (form_error('end_date')): ?>
                            <?= form_error('end_date', '<div class="invalid-feedback">', '</div>') ?>
                        <?php elseif (isset($validation_errors['end_date'])): ?>
                            <div class="invalid-feedback"><?= $validation_errors['end_date'] ?></div>
                        <?php endif; ?>
                        <small class="form-text text-muted">A data de fim deve ser posterior à data de início</small>
                    </div>
                </div>
                
                <div class="mt-3">
                    <label for="budget" class="form-label">Orçamento (R$)</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" class="form-control <?= (form_error('budget') || (isset($validation_errors['budget']))) ? 'is-invalid' : '' ?>" 
                               id="budget" name="budget" 
                               value="<?= set_value('budget') ?>" step="0.01" min="0"
                               placeholder="0,00">
                        <?php if (form_error('budget')): ?>
                            <?= form_error('budget', '<div class="invalid-feedback">', '</div>') ?>
                        <?php elseif (isset($validation_errors['budget'])): ?>
                            <div class="invalid-feedback"><?= $validation_errors['budget'] ?></div>
                        <?php endif; ?>
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
    </div>
    
    <div class="col-lg-4">
        <!-- Configurações -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Configurações</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="status" class="form-label">Status Inicial</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?= set_select('status', 'active', TRUE) ?>>Ativo</option>
                        <option value="paused" <?= set_select('status', 'paused') ?>>Pausado</option>
                    </select>
                    <small class="form-text text-muted">
                        Projetos ativos podem receber questionários imediatamente
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Resumo -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Resumo</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Criado por:</span>
                    <strong><?= $this->session->userdata('admin_name') ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Data de criação:</span>
                    <strong><?= date('d/m/Y H:i') ?></strong>
                </div>
                <hr>
                <small class="text-muted">
                    <i class="fas fa-lightbulb me-1"></i>
                    <strong>Dica:</strong> Após criar o projeto, você poderá vincular questionários a ele.
                </small>
            </div>
        </div>
        
        <!-- Ações -->
        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary w-100 mb-2" id="submitBtn">
                    <i class="fas fa-save me-2"></i>
                    Criar Projeto
                </button>
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
    
    // Função para validar data no formato ISO (YYYY-MM-DD)
    function isValidDate(dateString) {
        if (!dateString) return true; // Campo não obrigatório
        
        const date = new Date(dateString + 'T00:00:00'); // Adicionar tempo para evitar problemas de timezone
        const year = date.getFullYear();
        
        // Verificar se é uma data válida e dentro do range aceitável
        return !isNaN(date.getTime()) && year >= 2020 && year <= 2050;
    }
    
    // Contador de caracteres para descrição
    function updateCharCount() {
        const count = descriptionInput.value.length;
        charCountSpan.textContent = count;
        
        if (count > 900) {
            charCountSpan.style.color = '#dc3545'; // text-danger
        } else if (count > 700) {
            charCountSpan.style.color = '#ffc107'; // text-warning
        } else {
            charCountSpan.style.color = '#6c757d'; // text-muted
        }
    }
    
    // Cálculo de duração do projeto
    function updateDuration() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        
        // Limpar classes de erro primeiro (apenas do JavaScript, não do PHP)
        const elementsToClean = document.querySelectorAll('.js-validation-error');
        elementsToClean.forEach(el => el.remove());
        
        if (startDate && endDate) {
            // Validar datas individualmente
            if (!isValidDate(startDate)) {
                showJSValidationError(startDateInput, 'Data de início inválida!');
                return;
            }
            
            if (!isValidDate(endDate)) {
                showJSValidationError(endDateInput, 'Data de fim inválida!');
                return;
            }
            
            const start = new Date(startDate + 'T00:00:00');
            const end = new Date(endDate + 'T00:00:00');
            const diffTime = end - start;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays < 0) {
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
    
    // Função para mostrar erro de validação JavaScript
    function showJSValidationError(element, message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback js-validation-error';
        errorDiv.textContent = message;
        element.classList.add('is-invalid');
        element.parentNode.appendChild(errorDiv);
        
        durationInfo.className = 'alert alert-danger';
        durationText.textContent = message;
        durationInfo.style.display = 'block';
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
    
    // Validação básica do formulário antes do submit (o PHP fará a validação final)
    form.addEventListener('submit', function(e) {
        // Limpar erros JavaScript anteriores
        const jsErrors = document.querySelectorAll('.js-validation-error');
        jsErrors.forEach(el => el.remove());
        
        // Validações básicas do lado cliente
        const name = document.getElementById('name').value.trim();
        
        if (!name) {
            e.preventDefault();
            alert('Nome do projeto é obrigatório.');
            document.getElementById('name').focus();
            return false;
        }
        
        // Desabilitar botão para evitar duplo envio
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Criando...';
        
        // Reabilitar botão após um tempo (caso haja erro de validação do servidor)
        setTimeout(function() {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Criar Projeto';
        }, 5000);
    });
    
    // Remover classes de erro do PHP ao corrigir campos
    ['name', 'description', 'client_name', 'budget', 'start_date', 'end_date'].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                const feedback = this.parentNode.querySelector('.invalid-feedback:not(.js-validation-error)');
                if (feedback) {
                    feedback.style.display = 'none';
                }
            });
            
            if (fieldId.includes('date')) {
                field.addEventListener('change', function() {
                    this.classList.remove('is-invalid');
                    const feedback = this.parentNode.querySelector('.invalid-feedback:not(.js-validation-error)');
                    if (feedback) {
                        feedback.style.display = 'none';
                    }
                });
            }
        }
    });
});
</script>