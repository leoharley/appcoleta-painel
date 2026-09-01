<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Clonar Estratificação</h2>
            <a href="<?= base_url('stratifications') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Voltar
            </a>
        </div>
    </div>
</div>

<form action="<?= base_url('stratifications/duplicate/' . $stratification->id) ?>" method="POST">
    <div class="row">
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-copy me-2"></i>Nova Cópia</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Título da Cópia *</label>
                        <input type="text" class="form-control" id="title" name="title"
                               required maxlength="200"
                               value="<?= htmlspecialchars($stratification->title) ?> (cópia)">
                    </div>

                    <div class="mb-3">
                        <label for="questionnaire_id" class="form-label">Questionário de Destino *</label>
                        <select class="form-select" id="questionnaire_id" name="questionnaire_id" required>
                            <?php foreach ($questionnaires as $q): ?>
                            <option value="<?= $q->id ?>"
                                <?= ($stratification->questionnaire_id == $q->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($q->title) ?>
                                <?= ($stratification->questionnaire_id == $q->id) ? ' (mesmo da original)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Ao clonar para <strong>outro</strong> questionário, as categorias por pergunta
                            só são copiadas se existir uma pergunta com o <strong>mesmo texto</strong> no destino.
                            As demais são omitidas e você será avisado.
                        </small>
                    </div>

                    <div class="alert alert-info small mb-0">
                        <i class="fas fa-lightbulb me-1"></i>
                        A cópia mantém o status da original
                        (<strong><?= $stratification->is_active ? 'Ativa' : 'Inativa' ?></strong>)
                        e abre direto na tela de edição para você ajustar as cotas.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-list-check me-2"></i>O que será copiado</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($stratification->categories)): ?>
                        <p class="text-muted mb-0 small">Esta estratificação não possui categorias.</p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($stratification->categories as $cat): ?>
                        <?php
                            $meta = 0;
                            $tem_grade = false;
                            foreach ($cat->strata as $s) {
                                $meta += (int)$s->target_quota;
                                if (!empty($s->applicator_quotas)) $tem_grade = true;
                            }
                            $icones = array(
                                'question'   => 'fa-question-circle',
                                'applicator' => 'fa-user',
                                'weekday'    => 'fa-calendar-day'
                            );
                            $icone = isset($icones[$cat->dimension_type]) ? $icones[$cat->dimension_type] : 'fa-layer-group';
                            $depende_pergunta = ($cat->dimension_type === 'question');
                        ?>
                        <li class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <i class="fas <?= $icone ?> me-1 text-muted"></i>
                                    <strong><?= htmlspecialchars($cat->category_name) ?></strong>
                                    <div class="small text-muted">
                                        <?= count($cat->strata) ?> estrato(s) · meta <?= $meta ?>
                                        <?php if ($tem_grade): ?>
                                            · <span class="text-success">com cotas por aplicador</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($depende_pergunta): ?>
                                    <div class="small text-warning depends-warning" style="display:none;">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Depende de pergunta equivalente no destino
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="<?= base_url('stratifications') ?>" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-copy me-2"></i>Clonar Estratificação
        </button>
    </div>
</form>

<script>
// Avisa sobre as categorias que dependem de pergunta quando o destino muda
document.addEventListener('DOMContentLoaded', function() {
    var original = '<?= $stratification->questionnaire_id ?>';
    var select = document.getElementById('questionnaire_id');

    function refresh() {
        var outro = (select.value !== original);
        document.querySelectorAll('.depends-warning').forEach(function(el) {
            el.style.display = outro ? 'block' : 'none';
        });
    }

    select.addEventListener('change', refresh);
    refresh();
});
</script>
