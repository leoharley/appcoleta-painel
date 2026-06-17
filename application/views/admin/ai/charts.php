<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="mb-1">Gráficos inteligentes</h2><p class="text-muted mb-0">Visualizações sugeridas a partir das análises da IA</p></div>
    <a href="<?= base_url('ai') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
</div>

<div class="alert alert-info small">
    <i class="fas fa-info-circle me-1"></i>
    Esta tela lista as <strong>sugestões de gráfico</strong> produzidas pela análise estatística.
    Para gerar uma nova análise (que inclui sugestões de gráfico), use a tela
    <a href="<?= base_url('ai/analysis') ?>" class="alert-link">Análise estatística</a>.
</div>

<div class="row g-3">
<?php if (empty($items)): ?>
    <div class="col-12"><p class="text-muted text-center py-4">Sem análises ainda.</p></div>
<?php else: foreach ($items as $it):
    $charts = json_decode((string)$it->chart_suggestions, true);
?>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><?= htmlspecialchars((string)$it->questionnaire_title) ?></strong>
                <small class="text-muted"><?= date('d/m/Y', strtotime($it->created_at)) ?></small>
            </div>
            <div class="card-body">
                <?php if (empty($charts)): ?>
                    <p class="text-muted small mb-0">Sem sugestões nesta análise.</p>
                <?php else: ?>
                    <ul class="small mb-0">
                    <?php foreach ($charts as $c): ?>
                        <li>
                            <?php if (is_array($c)): ?>
                                <code><?= htmlspecialchars(isset($c['type']) ? $c['type'] : 'chart') ?></code>
                                — <?= htmlspecialchars(isset($c['description']) ? $c['description'] : (isset($c['title']) ? $c['title'] : json_encode($c, JSON_UNESCAPED_UNICODE))) ?>
                            <?php else: ?>
                                <?= htmlspecialchars((string)$c) ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <a href="<?= base_url('ai/view_analysis/' . (int)$it->id) ?>" class="btn btn-sm btn-outline-primary mt-3"><i class="fas fa-eye me-1"></i> Ver análise</a>
            </div>
        </div>
    </div>
<?php endforeach; endif; ?>
</div>
