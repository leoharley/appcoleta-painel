<?php
$decode = function($v) {
    if ($v === null || $v === '') return null;
    $d = json_decode($v, true);
    return $d === null ? $v : $d;
};
$patterns = $decode($item->patterns);
$outliers = $decode($item->outliers);
$trends   = $decode($item->trends);
$insights = $decode($item->insights);
$charts   = $decode($item->chart_suggestions);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Análise #<?= (int)$item->id ?></h2>
        <p class="text-muted mb-0">Questionário <?= (int)$item->questionnaire_id ?> · gerada em <?= date('d/m/Y H:i', strtotime($item->created_at)) ?></p>
    </div>
    <a href="<?= base_url('ai/analysis') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
</div>

<div class="card mb-3"><div class="card-header"><strong>Resumo</strong></div>
    <div class="card-body"><p class="mb-0"><?= nl2br(htmlspecialchars((string)$item->summary_text)) ?></p></div>
</div>

<?php
$render_block = function($title, $val) {
    echo '<div class="card mb-3"><div class="card-header"><strong>' . htmlspecialchars($title) . '</strong></div><div class="card-body">';
    if (is_array($val) && !empty($val)) {
        echo '<ul class="mb-0">';
        foreach ($val as $i) {
            if (is_array($i)) {
                echo '<li><pre class="mb-0 small">' . htmlspecialchars(json_encode($i, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . '</pre></li>';
            } else {
                echo '<li>' . htmlspecialchars((string)$i) . '</li>';
            }
        }
        echo '</ul>';
    } else {
        echo '<p class="text-muted mb-0">—</p>';
    }
    echo '</div></div>';
};
$render_block('Padrões', $patterns);
$render_block('Outliers', $outliers);
$render_block('Tendências', $trends);
$render_block('Insights', $insights);
$render_block('Sugestões de gráfico', $charts);
?>
