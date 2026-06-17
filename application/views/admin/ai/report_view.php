<?php
$sections = $item->sections ? json_decode($item->sections, true) : array();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><?= htmlspecialchars((string)$item->title) ?></h2>
        <p class="text-muted mb-0">Questionário <?= (int)$item->questionnaire_id ?> · gerado em <?= date('d/m/Y H:i', strtotime($item->created_at)) ?></p>
    </div>
    <div>
        <button class="btn btn-outline-primary me-2" id="btnEdit"><i class="fas fa-edit me-1"></i> Editar</button>
        <a href="<?= base_url('ai/reports') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div id="narrativeView"><?= nl2br(htmlspecialchars((string)$item->narrative_text)) ?></div>
        <form id="editForm" style="display:none">
            <input type="hidden" name="id" value="<?= (int)$item->id ?>">
            <div class="mb-2">
                <label class="form-label">Título</label>
                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars((string)$item->title) ?>">
            </div>
            <div class="mb-2">
                <label class="form-label">Narrativa</label>
                <textarea name="narrative_text" class="form-control" rows="12"><?= htmlspecialchars((string)$item->narrative_text) ?></textarea>
            </div>
            <button class="btn btn-primary btn-sm">Salvar</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCancel">Cancelar</button>
        </form>
    </div>
</div>

<?php if (!empty($sections) && is_array($sections)): ?>
<?php foreach ($sections as $s): ?>
<div class="card mb-3">
    <div class="card-header"><strong><?= htmlspecialchars((string)(isset($s['heading']) ? $s['heading'] : 'Seção')) ?></strong></div>
    <div class="card-body"><?= nl2br(htmlspecialchars((string)(isset($s['body']) ? $s['body'] : ''))) ?></div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<script>
(function() {
    const base = '<?= base_url() ?>';
    document.getElementById('btnEdit').addEventListener('click', function() {
        document.getElementById('narrativeView').style.display = 'none';
        document.getElementById('editForm').style.display = '';
    });
    document.getElementById('btnCancel').addEventListener('click', function() {
        document.getElementById('narrativeView').style.display = '';
        document.getElementById('editForm').style.display = 'none';
    });
    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch(base + 'ai/update_report', { method:'POST', credentials:'same-origin', body: new URLSearchParams(new FormData(this)) })
            .then(r=>r.json()).then(d => { if (d.success) location.reload(); else alert('Falha'); });
    });
})();
</script>
