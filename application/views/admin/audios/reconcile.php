<?php
/**
 * Helper: tenta extrair o timestamp embutido no nome de arquivo do tipo
 *   audio_<formId>_<ts>.ext
 * para mostrar de forma legivel a data/hora aproximada da gravacao.
 */
function audio_filename_date($filename) {
    if (preg_match('/^audio_(\d+)_(\d+)\./', $filename, $m)) {
        $ts = (int)$m[2];
        if ($ts > 1000000000000) { // millisegundos
            $ts = (int)($ts / 1000);
        }
        if ($ts >= 1000000000 && $ts <= 9999999999) {
            return date('d/m/Y H:i', $ts);
        }
    }
    return null;
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-microphone me-2"></i>Reconciliação de Áudios</h2>
                <p class="text-muted mb-0">
                    Associar arquivos de áudio que estão no servidor com entrevistas que ficaram sem áudio.
                </p>
            </div>
            <a href="<?= base_url('responses') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Voltar para Respostas
            </a>
        </div>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-1"></i><?= htmlspecialchars($this->session->flashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($this->session->flashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-times-circle me-1"></i><?= htmlspecialchars($this->session->flashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Resumo -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="mb-1 text-success"><?= $healthy_count ?></h3>
                <small class="text-muted">Áudios funcionando</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-warning">
            <div class="card-body">
                <h3 class="mb-1 text-warning"><?= count($broken_forms) ?></h3>
                <small class="text-muted">Entrevistas com áudio quebrado</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-info">
            <div class="card-body">
                <h3 class="mb-1 text-info"><?= count($orphan_files) ?></h3>
                <small class="text-muted">Arquivos órfãos no servidor</small>
            </div>
        </div>
    </div>
</div>

<?php if (empty($broken_forms) && empty($orphan_files)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle me-2"></i>
    <strong>Tudo certo!</strong> Não há áudios para reconciliar. Todos os <?= $healthy_count ?> registros com áudio estão apontando para arquivos válidos no servidor.
</div>
<?php endif; ?>

<?php if (!empty($broken_forms)): ?>
<div class="card mb-4">
    <div class="card-header bg-warning bg-opacity-25">
        <h5 class="mb-0">
            <i class="fas fa-unlink me-2"></i>
            Entrevistas com áudio quebrado
            <span class="badge bg-warning text-dark ms-2"><?= count($broken_forms) ?></span>
        </h5>
        <small class="text-muted">
            O <code>audio_path</code> no banco aponta para um arquivo que não existe em <code>uploads/audios/</code>.
            Use o seletor para vincular a um arquivo órfão, ou marque "Sem áudio" se a gravação foi perdida.
        </small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 70px;">#</th>
                        <th>Questionário</th>
                        <th>Aplicador</th>
                        <th>Data/Hora</th>
                        <th>audio_path atual (no banco)</th>
                        <th style="min-width: 320px;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($broken_forms as $form): ?>
                    <tr>
                        <td><strong>#<?= $form->id ?></strong></td>
                        <td><?= htmlspecialchars($form->questionnaire_title ?: '—') ?></td>
                        <td><?= htmlspecialchars($form->applied_by_name ?: '—') ?></td>
                        <td>
                            <small><?= $form->completed_at ? date('d/m/Y H:i', strtotime($form->completed_at)) : '—' ?></small>
                        </td>
                        <td>
                            <code class="small text-danger"><?= htmlspecialchars($form->audio_path) ?></code>
                        </td>
                        <td>
                            <form method="POST" action="<?= base_url('audios/assign') ?>" class="d-flex gap-1 mb-1">
                                <input type="hidden" name="form_id" value="<?= $form->id ?>">
                                <select name="filename" class="form-select form-select-sm" required>
                                    <option value="">Selecionar arquivo órfão…</option>
                                    <?php foreach ($orphan_files as $f):
                                        $when = audio_filename_date($f['filename']);
                                    ?>
                                    <option value="<?= htmlspecialchars($f['filename']) ?>">
                                        <?= htmlspecialchars($f['filename']) ?>
                                        — <?= round($f['size'] / 1024) ?> KB
                                        <?= $when ? '— ' . $when : '' ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fas fa-link"></i>
                                </button>
                            </form>
                            <form method="POST" action="<?= base_url('audios/clear') ?>"
                                  onsubmit="return confirm('Remover o áudio desta entrevista? O registro continua, só perde o áudio.');">
                                <input type="hidden" name="form_id" value="<?= $form->id ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Marcar como sem áudio
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($orphan_files)): ?>
<div class="card">
    <div class="card-header bg-info bg-opacity-25">
        <h5 class="mb-0">
            <i class="fas fa-file-audio me-2"></i>
            Arquivos órfãos em <code>uploads/audios/</code>
            <span class="badge bg-info ms-2"><?= count($orphan_files) ?></span>
        </h5>
        <small class="text-muted">
            Arquivos que estão no servidor mas não estão vinculados a nenhuma entrevista. Use o player abaixo para identificar pela voz.
        </small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th>Tamanho</th>
                        <th>Data aproximada da gravação</th>
                        <th>Player</th>
                        <th>Download</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orphan_files as $f):
                        $url = base_url('uploads/audios/' . rawurlencode($f['filename']));
                        $when = audio_filename_date($f['filename']);
                    ?>
                    <tr>
                        <td><code class="small"><?= htmlspecialchars($f['filename']) ?></code></td>
                        <td><?= round($f['size'] / 1024) ?> KB</td>
                        <td><?= $when ? '<small>' . $when . '</small>' : '<small class="text-muted">—</small>' ?></td>
                        <td>
                            <audio controls preload="none" style="height: 30px; max-width: 220px;">
                                <source src="<?= $url ?>" type="audio/mp4">
                            </audio>
                        </td>
                        <td>
                            <a href="<?= $url ?>" download class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
