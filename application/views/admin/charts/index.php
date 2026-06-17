<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><i class="fas fa-chart-pie me-2"></i>Gráficos & Relatórios</h2>
        <p class="text-muted mb-0">Escolha um questionário para gerar o relatório de apresentação</p>
    </div>
</div>

<?php if (empty($questionnaires)): ?>
    <div class="alert alert-info">Nenhum questionário cadastrado.</div>
<?php else: ?>

<div class="row g-3">
<?php foreach ($questionnaires as $q): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="mb-1"><?= htmlspecialchars($q->title) ?></h5>
                <?php if ($q->project_name): ?>
                    <small class="text-muted d-block mb-2">
                        <i class="fas fa-folder-open me-1"></i><?= htmlspecialchars($q->project_name) ?>
                    </small>
                <?php endif; ?>
                <?php if ($q->description): ?>
                    <p class="text-muted small mb-2"><?= htmlspecialchars(mb_strimwidth($q->description, 0, 120, '…')) ?></p>
                <?php endif; ?>
                <div class="d-flex align-items-center mt-3">
                    <span class="badge bg-primary me-2">
                        <i class="fas fa-clipboard-list me-1"></i><?= (int)$q->responses_count ?> respostas
                    </span>
                    <small class="text-muted ms-auto"><?= date('d/m/Y', strtotime($q->created_at)) ?></small>
                </div>
            </div>
            <div class="card-footer bg-white border-top-0">
                <details class="mb-2">
                    <summary class="small text-muted" style="cursor:pointer;">Filtros (opcional)</summary>
                    <form action="<?= base_url('charts/report/' . (int)$q->id) ?>" method="get" target="_blank" class="mt-2">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small mb-1">De</label>
                                <input type="date" class="form-control form-control-sm" name="from">
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Até</label>
                                <input type="date" class="form-control form-control-sm" name="to">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 mt-2 <?= (int)$q->responses_count === 0 ? 'disabled' : '' ?>">
                            <i class="fas fa-file-alt me-1"></i> Gerar com filtros
                        </button>
                    </form>
                </details>
                <a href="<?= base_url('charts/report/' . (int)$q->id) ?>"
                   target="_blank"
                   class="btn btn-primary w-100 <?= (int)$q->responses_count === 0 ? 'disabled' : '' ?>">
                    <i class="fas fa-file-alt me-1"></i> Gerar relatório
                </a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php endif; ?>
