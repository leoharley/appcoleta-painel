<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><?= $mode === 'edit' ? 'Editar' : 'Nova' ?> Estratificação</h2>
            <a href="<?= base_url('stratifications') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Voltar
            </a>
        </div>
    </div>
</div>

<?php
    $action = $mode === 'edit'
        ? base_url('stratifications/edit/' . $stratification->id)
        : base_url('stratifications/create');
?>

<form id="stratificationForm" action="<?= $action ?>" method="POST">

    <!-- Dados Basicos -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Dados Básicos</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="title" class="form-label">Título da Estratificação *</label>
                        <input type="text" class="form-control" id="title" name="title" required maxlength="200"
                               value="<?= $stratification ? htmlspecialchars($stratification->title) : '' ?>"
                               placeholder="Ex: Estratificação - Pesquisa Eleitoral 2026">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="questionnaire_id" class="form-label">Questionário *</label>
                        <select class="form-select" id="questionnaire_id" name="questionnaire_id"
                                <?= $mode === 'edit' ? 'disabled' : 'required' ?>>
                            <option value="">Selecione um questionário...</option>
                            <?php foreach ($questionnaires as $q): ?>
                            <option value="<?= $q->id ?>"
                                <?= ($stratification && $stratification->questionnaire_id == $q->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($q->title) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($mode === 'edit'): ?>
                            <input type="hidden" name="questionnaire_id" value="<?= $stratification->questionnaire_id ?>">
                        <?php endif; ?>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Um questionário pode ter várias estratificações (por região, por dia da semana, por aplicador etc.).
                        </small>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição (opcional)</label>
                        <textarea class="form-control" id="description" name="description" rows="2"
                                  placeholder="Descrição da estratificação..."><?= $stratification ? htmlspecialchars($stratification->description) : '' ?></textarea>
                    </div>
                </div>
            </div>
            <?php if ($mode === 'edit'): ?>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                               <?= $stratification->is_active ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Estratificação Ativa</label>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Categorias de Estratificacao -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i>Categorias de Estratificação</h5>
            <span id="categoriesCount" class="badge bg-primary">0 categorias</span>
        </div>
        <div class="card-body">
            <div id="dimensionsLoading" class="text-center py-4" style="display: none;">
                <i class="fas fa-spinner fa-spin fa-2x text-primary mb-2"></i>
                <p class="text-muted">Carregando dimensões disponíveis...</p>
            </div>

            <div id="dimensionsEmpty" class="text-center py-4">
                <i class="fas fa-hand-pointer fa-2x text-muted mb-2"></i>
                <p class="text-muted">Selecione um questionário para configurar as categorias.</p>
            </div>

            <div id="dimensionsContainer" style="display: none;">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" type="button" data-bs-toggle="tab" data-bs-target="#tabQuestion">
                            <i class="fas fa-question-circle me-1"></i>Por Pergunta
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#tabApplicator">
                            <i class="fas fa-user me-1"></i>Por Aplicador
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#tabWeekday">
                            <i class="fas fa-calendar-day me-1"></i>Por Dia da Semana
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tabQuestion">
                        <p class="text-muted small">
                            Marque as perguntas que servirão de base para a estratificação. Cada opção da pergunta vira um estrato com sua cota.
                        </p>
                        <div id="questionsContainer"></div>
                    </div>

                    <div class="tab-pane fade" id="tabApplicator">
                        <p class="text-muted small">
                            Defina cota individual para cada pesquisador. Estratos com cota 0 são ignorados.
                        </p>
                        <div id="applicatorContainer"></div>
                    </div>

                    <div class="tab-pane fade" id="tabWeekday">
                        <p class="text-muted small">
                            Defina cota por dia da semana. A contagem usa a data de conclusão da entrevista. Estratos com cota 0 são ignorados.
                        </p>
                        <div id="weekdayContainer"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botoes -->
    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="<?= base_url('stratifications') ?>" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary" id="btnSave">
            <i class="fas fa-save me-2"></i>
            <?= $mode === 'edit' ? 'Atualizar' : 'Salvar' ?> Estratificação
        </button>
    </div>
</form>

<style>
.question-card, .auto-card {
    border: 2px solid #dee2e6;
    border-radius: 0.5rem;
    transition: border-color 0.3s;
    margin-bottom: 1rem;
}
.question-card.selected, .auto-card.selected {
    border-color: var(--primary-color, #E87722);
    background-color: #fff9f5;
}
.question-card .card-header, .auto-card .card-header {
    cursor: pointer;
}
.quota-input {
    width: 100px;
    text-align: center;
}
</style>

<script>
var existingData = <?= json_encode($stratification ? $stratification->categories : []) ?>;
var allApplicators = [];

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text == null ? '' : text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() {
    $('#questionnaire_id').on('change', function() {
        var qId = $(this).val();
        if (!qId) {
            $('#dimensionsContainer').hide();
            $('#dimensionsEmpty').show();
            updateCount();
            return;
        }
        loadDimensions(qId);
    });

    $('#stratificationForm').on('submit', function(e) {
        $(this).find('.dynamic-field').remove();

        var catIndex = 0;

        // Dimensao: question
        $('.question-card').each(function() {
            var card = $(this);
            if (!card.find('.question-check').prop('checked')) return;

            var questionId = card.data('question-id');
            var categoryName = card.find('.category-name').val().trim();
            if (!categoryName) categoryName = card.find('.form-check-label').text().trim();

            var strata = collectStrata(card);
            if (strata.length === 0) return;

            writeCategory(catIndex++, 'question', categoryName, strata, questionId);
        });

        // Dimensao: applicator
        var applicatorCard = $('#applicatorCard');
        if (applicatorCard.length && applicatorCard.find('.dimension-check').prop('checked')) {
            var name = applicatorCard.find('.category-name').val().trim() || 'Por Aplicador';
            var strata = collectStrata(applicatorCard);
            if (strata.length > 0) writeCategory(catIndex++, 'applicator', name, strata, null);
        }

        // Dimensao: weekday
        var weekdayCard = $('#weekdayCard');
        if (weekdayCard.length && weekdayCard.find('.dimension-check').prop('checked')) {
            var name = weekdayCard.find('.category-name').val().trim() || 'Por Dia da Semana';
            var strata = collectStrata(weekdayCard);
            if (strata.length > 0) writeCategory(catIndex++, 'weekday', name, strata, null);
        }

        if (catIndex === 0) {
            e.preventDefault();
            alert('Selecione pelo menos uma categoria com metas definidas.');
            return false;
        }
    });

    // Grade por aplicador: liga/desliga as colunas
    $(document).on('change', '.per-applicator-toggle', function() {
        var card = $(this).closest('.question-card');
        card.find('.per-app-col').toggle($(this).prop('checked'));
        refreshGrid(card);
    });

    // Recalcula somas e avisos ao digitar qualquer cota
    $(document).on('input', '.app-quota-input, .quota-input', function() {
        refreshGrid($(this).closest('.question-card'));
    });

    <?php if ($mode === 'edit' && $stratification): ?>
        loadDimensions(<?= $stratification->questionnaire_id ?>);
    <?php endif; ?>
});

/**
 * Atualiza somas por aplicador e avisa quando a soma das cotas
 * individuais nao bate com a meta geral da opcao.
 */
function refreshGrid(card) {
    if (!card || !card.length) return;
    var on = card.find('.per-applicator-toggle').prop('checked');
    var warn = card.find('.grid-warning');

    // Soma por coluna (total de cada aplicador na pergunta)
    card.find('.app-col-sum').each(function() {
        var uid = $(this).data('user-id');
        var sum = 0;
        card.find('.app-quota-input[data-user-id="' + uid + '"]').each(function() {
            sum += parseInt($(this).val()) || 0;
        });
        $(this).text(sum);
    });

    if (!on) { warn.hide(); return; }

    // Confere linha a linha: soma dos aplicadores vs meta geral
    var problemas = [];
    card.find('.quota-input').each(function() {
        var geral = parseInt($(this).val()) || 0;
        if (geral <= 0) return;
        var optValue = $(this).data('option-value');
        var label = $(this).data('option-label');
        var soma = 0;
        card.find('.app-quota-input').each(function() {
            if ($(this).data('option-value') === optValue) soma += parseInt($(this).val()) || 0;
        });
        if (soma !== geral) {
            problemas.push(label + ': aplicadores somam ' + soma + ', meta geral é ' + geral);
        }
    });

    if (problemas.length) {
        warn.html('<i class="fas fa-exclamation-triangle me-1"></i>' +
                  problemas.join('<br>') +
                  '<br><span class="text-muted">A estratificação será salva assim mesmo — ajuste se não for intencional.</span>').show();
    } else {
        warn.hide();
    }
}

function collectStrata(card) {
    var out = [];
    var perApp = card.find('.per-applicator-toggle').prop('checked');

    card.find('.quota-input').each(function() {
        var quota = parseInt($(this).val()) || 0;
        if (quota <= 0) return;

        var optValue = $(this).data('option-value');
        var stratum = {
            option_value: optValue,
            option_label: $(this).data('option-label'),
            target_quota: quota
        };

        // Cotas individuais so acompanham quando a grade esta ativa.
        if (perApp) {
            var quotas = {};
            card.find('.app-quota-input').each(function() {
                if ($(this).data('option-value') !== optValue) return;
                var q = parseInt($(this).val()) || 0;
                if (q > 0) quotas[$(this).data('user-id')] = q;
            });
            if (Object.keys(quotas).length) stratum.applicator_quotas = quotas;
        }

        out.push(stratum);
    });
    return out;
}

function writeCategory(idx, dimensionType, categoryName, strata, questionId) {
    var prefix = 'categories[' + idx + ']';
    addHidden(prefix + '[dimension_type]', dimensionType);
    addHidden(prefix + '[category_name]', categoryName);
    if (questionId !== null && questionId !== undefined) {
        addHidden(prefix + '[question_id]', questionId);
    }
    strata.forEach(function(s, j) {
        var sp = prefix + '[strata][' + j + ']';
        addHidden(sp + '[option_value]', s.option_value);
        addHidden(sp + '[option_label]', s.option_label);
        addHidden(sp + '[target_quota]', s.target_quota);

        if (s.applicator_quotas) {
            Object.keys(s.applicator_quotas).forEach(function(uid) {
                addHidden(sp + '[applicator_quotas][' + uid + ']', s.applicator_quotas[uid]);
            });
        }
    });
}

function loadDimensions(questionnaireId) {
    $('#dimensionsEmpty').hide();
    $('#dimensionsContainer').hide();
    $('#dimensionsLoading').show();

    $.ajax({
        url: '<?= base_url("stratifications/ajax_get_dimensions/") ?>' + questionnaireId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            $('#dimensionsLoading').hide();
            if (!response.success) {
                $('#dimensionsEmpty').html('<p class="text-danger">Erro ao carregar dimensões.</p>').show();
                return;
            }
            allApplicators = response.applicators || [];
            renderQuestions(response.questions || []);
            renderApplicators(response.applicators || []);
            renderWeekdays(response.weekdays || []);
            $('#dimensionsContainer').show();
            updateCount();
        },
        error: function() {
            $('#dimensionsLoading').hide();
            $('#dimensionsEmpty').html('<p class="text-danger">Erro de conexão ao carregar dimensões.</p>').show();
        }
    });
}

function getExistingByDimension(dimensionType, questionId) {
    if (!existingData || existingData.length === 0) return null;
    for (var i = 0; i < existingData.length; i++) {
        var c = existingData[i];
        if (c.dimension_type === dimensionType) {
            if (dimensionType === 'question' && questionId !== undefined) {
                if (parseInt(c.question_id) === parseInt(questionId)) return c;
            } else if (dimensionType !== 'question') {
                return c;
            }
        }
    }
    return null;
}

/**
 * Mapa "option_value|user_id" -> cota individual, a partir dos dados salvos.
 */
function buildApplicatorQuotaMap(category) {
    var m = {};
    if (category && category.strata) {
        category.strata.forEach(function(s) {
            var q = s.applicator_quotas;
            if (!q) return;
            Object.keys(q).forEach(function(uid) {
                m[s.option_value + '|' + uid] = parseInt(q[uid]) || 0;
            });
        });
    }
    return m;
}

function buildStrataMap(category) {
    var m = {};
    if (category && category.strata) {
        category.strata.forEach(function(s) {
            m[s.option_value] = parseInt(s.target_quota) || 0;
        });
    }
    return m;
}

function renderQuestions(questions) {
    var container = $('#questionsContainer');
    container.empty();

    if (questions.length === 0) {
        container.html('<div class="alert alert-warning small mb-0"><i class="fas fa-exclamation-triangle me-1"></i>Nenhuma pergunta com opções (Escolha Única, Múltipla Escolha ou Lista) neste questionário.</div>');
        return;
    }

    questions.forEach(function(q) {
        var existing = getExistingByDimension('question', q.id);
        var isSelected = !!existing;
        var categoryName = existing ? existing.category_name : q.question_text;
        var strataMap = buildStrataMap(existing);

        var typeLabels = {'radio': 'Escolha Única', 'checkbox': 'Múltipla Escolha', 'select': 'Lista Suspensa'};
        var typeLabel = typeLabels[q.question_type] || q.question_type;

        var html = '<div class="question-card ' + (isSelected ? 'selected' : '') + '" data-question-id="' + q.id + '">';
        html += '<div class="card-header d-flex justify-content-between align-items-center" onclick="toggleCard(this)">';
        html += '<div class="form-check">';
        html += '<input class="form-check-input question-check" type="checkbox" ' + (isSelected ? 'checked' : '') + ' onclick="event.stopPropagation(); toggleCard(this.closest(\'.card-header\'))">';
        html += '<label class="form-check-label fw-bold">' + escapeHtml(q.question_text) + '</label>';
        html += '</div>';
        html += '<span class="badge bg-secondary">' + typeLabel + '</span>';
        html += '</div>';

        html += '<div class="card-body" style="display: ' + (isSelected ? 'block' : 'none') + ';">';
        html += '<div class="mb-3">';
        html += '<label class="form-label fw-bold">Nome da Categoria</label>';
        html += '<input type="text" class="form-control category-name" value="' + escapeHtml(categoryName) + '">';
        html += '</div>';
        var perAppMap = buildApplicatorQuotaMap(existing);
        var hasGrid = allApplicators.length > 0;

        html += '<div class="d-flex justify-content-between align-items-center mb-2">';
        html += '<label class="form-label fw-bold mb-0">Opções e Metas</label>';
        if (hasGrid) {
            html += '<div class="form-check form-switch">';
            html += '<input class="form-check-input per-applicator-toggle" type="checkbox" ' +
                    (Object.keys(perAppMap).length ? 'checked' : '') + '>';
            html += '<label class="form-check-label small">Definir meta por aplicador</label>';
            html += '</div>';
        }
        html += '</div>';

        html += '<div class="table-responsive"><table class="table table-sm mb-0 align-middle">';
        html += '<thead><tr><th>Opção</th><th>Valor</th><th style="width:120px">Meta Geral</th>';
        if (hasGrid) {
            allApplicators.forEach(function(u) {
                html += '<th class="per-app-col text-center small" style="width:110px">' +
                        escapeHtml(u.full_name) + '</th>';
            });
        }
        html += '</tr></thead><tbody>';

        q.options.forEach(function(opt) {
            var quota = strataMap.hasOwnProperty(opt.option_value) ? strataMap[opt.option_value] : '';
            html += '<tr><td>' + escapeHtml(opt.option_text) + '</td>';
            html += '<td><code>' + escapeHtml(opt.option_value) + '</code></td>';
            html += '<td><input type="number" class="form-control form-control-sm quota-input" min="0" value="' + quota + '"';
            html += ' data-option-value="' + escapeHtml(opt.option_value) + '"';
            html += ' data-option-label="' + escapeHtml(opt.option_text) + '" placeholder="0"></td>';

            if (hasGrid) {
                allApplicators.forEach(function(u) {
                    var k = opt.option_value + '|' + u.id;
                    var v = perAppMap.hasOwnProperty(k) ? perAppMap[k] : '';
                    html += '<td class="per-app-col"><input type="number" min="0" placeholder="0"';
                    html += ' class="form-control form-control-sm app-quota-input"';
                    html += ' data-option-value="' + escapeHtml(opt.option_value) + '"';
                    html += ' data-user-id="' + u.id + '" value="' + v + '"></td>';
                });
            }
            html += '</tr>';
        });

        if (hasGrid) {
            html += '<tr class="table-light"><td colspan="2" class="text-end small fw-bold">Soma por aplicador</td>';
            html += '<td class="small text-muted">—</td>';
            allApplicators.forEach(function(u) {
                html += '<td class="per-app-col text-center small app-col-sum" data-user-id="' + u.id + '">0</td>';
            });
            html += '</tr>';
        }

        html += '</tbody></table></div>';
        if (hasGrid) {
            html += '<div class="grid-warning alert alert-warning small mt-2 mb-0" style="display:none;"></div>';
        }
        html += '</div></div>';

        container.append(html);
    });

    // Colunas da grade comecam ocultas quando o toggle esta desligado
    container.find('.question-card').each(function() {
        var card = $(this);
        if (!card.find('.per-applicator-toggle').prop('checked')) {
            card.find('.per-app-col').hide();
        }
        refreshGrid(card);
    });
}

function renderAutoCard(containerId, cardId, dimensionType, defaultName, items) {
    var container = $('#' + containerId);
    container.empty();

    if (items.length === 0) {
        container.html('<div class="alert alert-warning small mb-0">Nada a configurar.</div>');
        return;
    }

    var existing = getExistingByDimension(dimensionType);
    var isSelected = !!existing;
    var categoryName = existing ? existing.category_name : defaultName;
    var strataMap = buildStrataMap(existing);

    var html = '<div class="auto-card ' + (isSelected ? 'selected' : '') + '" id="' + cardId + '">';
    html += '<div class="card-header d-flex justify-content-between align-items-center" onclick="toggleCard(this)">';
    html += '<div class="form-check">';
    html += '<input class="form-check-input dimension-check" type="checkbox" ' + (isSelected ? 'checked' : '') + ' onclick="event.stopPropagation(); toggleCard(this.closest(\'.card-header\'))">';
    html += '<label class="form-check-label fw-bold">Usar como categoria</label>';
    html += '</div>';
    html += '</div>';
    html += '<div class="card-body" style="display: ' + (isSelected ? 'block' : 'none') + ';">';
    html += '<div class="mb-3">';
    html += '<label class="form-label fw-bold">Nome da Categoria</label>';
    html += '<input type="text" class="form-control category-name" value="' + escapeHtml(categoryName) + '">';
    html += '</div>';
    html += '<div class="table-responsive"><table class="table table-sm mb-0">';
    html += '<thead><tr><th>Estrato</th><th style="width:120px">Meta (Cota)</th></tr></thead><tbody>';
    items.forEach(function(it) {
        var quota = strataMap.hasOwnProperty(it.value) ? strataMap[it.value] : '';
        html += '<tr><td>' + escapeHtml(it.label) + '</td>';
        html += '<td><input type="number" class="form-control form-control-sm quota-input" min="0" value="' + quota + '"';
        html += ' data-option-value="' + escapeHtml(it.value) + '"';
        html += ' data-option-label="' + escapeHtml(it.label) + '" placeholder="0"></td></tr>';
    });
    html += '</tbody></table></div></div></div>';

    container.append(html);
}

function renderApplicators(applicators) {
    var items = applicators.map(function(u) {
        return {
            value: String(u.id),
            label: u.full_name + ' (' + u.role + ')'
        };
    });
    renderAutoCard('applicatorContainer', 'applicatorCard', 'applicator', 'Por Aplicador', items);
}

function renderWeekdays(weekdays) {
    var items = weekdays.map(function(w) {
        return { value: w.value, label: w.label };
    });
    renderAutoCard('weekdayContainer', 'weekdayCard', 'weekday', 'Por Dia da Semana', items);
}

function toggleCard(header) {
    var card = $(header).closest('.question-card, .auto-card');
    var checkbox = card.find('.question-check, .dimension-check');
    var body = card.find('.card-body');

    if (!$(event.target).is('.question-check, .dimension-check')) {
        checkbox.prop('checked', !checkbox.prop('checked'));
    }

    if (checkbox.prop('checked')) {
        card.addClass('selected');
        body.slideDown(200);
    } else {
        card.removeClass('selected');
        body.slideUp(200);
    }
    updateCount();
}

function updateCount() {
    var count = $('.question-check:checked').length + $('.dimension-check:checked').length;
    $('#categoriesCount').text(count + ' categoria' + (count !== 1 ? 's' : ''));
}

function addHidden(name, value) {
    $('<input>').attr({type: 'hidden', name: name, value: value, class: 'dynamic-field'})
        .appendTo('#stratificationForm');
}
</script>
