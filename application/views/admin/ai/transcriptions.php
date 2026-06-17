<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Transcrições</h2>
        <p class="text-muted mb-0">Áudios coletados pelos aplicadores no app + uploads do painel</p>
    </div>
    <div>
        <button class="btn btn-outline-primary me-1" id="btnImport" title="Importa áudios anexados aos formulários para esta tela">
            <i class="fas fa-file-import me-1"></i> Importar áudios das respostas
            <?php if ((int)$available_to_import > 0): ?>
                <span class="badge bg-danger ms-1"><?= (int)$available_to_import ?></span>
            <?php endif; ?>
        </button>
        <button class="btn btn-success me-1" id="btnProcessPending" <?= (int)($stats['pending'] ?? 0) === 0 ? 'disabled' : '' ?>>
            <i class="fas fa-bolt me-1"></i> Transcrever pendentes
            <?php if ((int)$stats['pending'] > 0): ?>
                <span class="badge bg-light text-dark ms-1"><?= (int)$stats['pending'] ?></span>
            <?php endif; ?>
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fas fa-upload me-1"></i> Enviar áudio
        </button>
        <a href="<?= base_url('ai') ?>" class="btn btn-secondary ms-1"><i class="fas fa-arrow-left me-1"></i> Voltar</a>
    </div>
</div>

<!-- Resumo -->
<div class="row mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body py-2"><small class="text-muted">Total</small><div class="h4 mb-0"><?= (int)$stats['total'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body py-2"><small class="text-muted">Concluídas</small><div class="h4 mb-0 text-success"><?= (int)$stats['completed'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body py-2"><small class="text-muted">Pendentes</small><div class="h4 mb-0 text-warning"><?= (int)$stats['pending'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body py-2"><small class="text-muted">Com erro</small><div class="h4 mb-0 text-danger"><?= (int)$stats['error'] ?></div></div></div></div>
</div>

<div id="opResult" class="small mb-2"></div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Questionário / Resposta</th>
                        <th>Áudio</th>
                        <th>Texto</th>
                        <th>Duração</th>
                        <th>Status</th>
                        <th>Aplicador</th>
                        <th>Quando</th>
                        <th class="text-end" style="width:140px">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($transcriptions)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Sem transcrições. Clique em <strong>"Importar áudios das respostas"</strong> para puxar os áudios já coletados pelo app.</td></tr>
                <?php else: foreach ($transcriptions as $t): ?>
                    <tr data-id="<?= (int)$t->id ?>">
                        <td><code>#<?= (int)$t->id ?></code></td>
                        <td>
                            <?php if ($t->questionnaire_title): ?>
                                <small><?= htmlspecialchars((string)$t->questionnaire_title) ?></small><br>
                            <?php endif; ?>
                            <?php if ($t->form_response_id): ?>
                                <small class="text-muted">
                                    <a href="<?= base_url('responses/view/' . (int)$t->form_response_id) ?>" target="_blank">
                                        Resposta #<?= (int)$t->form_response_id ?>
                                    </a>
                                </small>
                            <?php endif; ?>
                            <?php if (!empty($t->q_text)): ?>
                                <br><small class="text-muted"><?= htmlspecialchars(mb_strimwidth((string)$t->q_text, 0, 60, '…')) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($t->audio_file_path): ?>
                                <?php $audio_url = base_url($t->audio_file_path); ?>
                                <a href="<?= $audio_url ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Abrir áudio">
                                    <i class="fas fa-volume-up"></i>
                                </a>
                            <?php else: ?>
                                <small class="text-muted">—</small>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:340px">
                            <?php $text = (string)($t->transcription_edited ?: $t->edited_text ?: $t->transcription_text); ?>
                            <?php if ($text): ?>
                                <div class="transcription-text"><?= nl2br(htmlspecialchars(mb_strimwidth($text, 0, 200, '…'))) ?></div>
                            <?php elseif ($t->error_message): ?>
                                <small class="text-danger"><?= htmlspecialchars(mb_strimwidth((string)$t->error_message, 0, 200, '…')) ?></small>
                            <?php else: ?>
                                <small class="text-muted">—</small>
                            <?php endif; ?>
                        </td>
                        <td><?= $t->audio_duration_seconds ? number_format((float)$t->audio_duration_seconds, 1) . 's' : '—' ?></td>
                        <td>
                            <?php $cls = $t->status === 'completed' ? 'success' : ($t->status === 'error' ? 'danger' : ($t->status === 'pending' ? 'warning text-dark' : 'secondary')); ?>
                            <span class="badge bg-<?= $cls ?>"><?= htmlspecialchars((string)$t->status) ?></span>
                        </td>
                        <td><small><?= htmlspecialchars((string)($t->applicator_full_name ?: $t->applicator_name ?: $t->processed_by_name ?: '—')) ?></small></td>
                        <td><small><?= date('d/m H:i', strtotime($t->created_at)) ?></small></td>
                        <td class="text-end">
                            <?php if ($t->status === 'pending' || $t->status === 'error'): ?>
                                <button class="btn btn-sm btn-success btn-process-one" data-id="<?= (int)$t->id ?>" title="Transcrever este áudio">
                                    <i class="fas fa-bolt"></i>
                                </button>
                            <?php endif; ?>
                            <?php if ($text): ?>
                                <button class="btn btn-sm btn-outline-primary btn-edit-transc"
                                        data-id="<?= (int)$t->id ?>"
                                        data-text="<?= htmlspecialchars($text, ENT_QUOTES) ?>"
                                        title="Editar texto"><i class="fas fa-edit"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Upload modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="uploadForm" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Enviar áudio para transcrição</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="text-muted small">Formatos: mp3, wav, m4a, ogg, webm, aac, flac. Máx 25 MB.</p>
                    <input type="file" class="form-control" name="audio" accept="audio/*" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-1"></i> Enviar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="editForm">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Editar transcrição</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editId">
                    <textarea class="form-control" name="edited_text" id="editText" rows="10"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const base = '<?= base_url() ?>';
    const out = document.getElementById('opResult');
    function setOut(html) { out.innerHTML = html; }

    // Upload manual
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const btn = this.querySelector('button[type=submit]');
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        fetch(base + 'ai/upload_audio', { method: 'POST', credentials: 'same-origin', body: fd })
            .then(r => r.json()).then(d => {
                if (d.success) location.reload();
                else { alert(d.message || 'Falha ao transcrever.'); btn.disabled = false; btn.innerHTML = 'Enviar'; }
            });
    });

    // Importar áudios das respostas
    document.getElementById('btnImport').addEventListener('click', function() {
        if (!confirm('Importar todos os áudios anexados aos formulários (que ainda não estão nesta lista)?')) return;
        const btn = this;
        btn.disabled = true;
        setOut('<i class="fas fa-spinner fa-spin"></i> Importando…');
        fetch(base + 'ai/import_response_audios', {
            method: 'POST', credentials: 'same-origin',
            body: new URLSearchParams({})
        }).then(r => r.json()).then(d => {
            if (d.success) {
                setOut('<span class="text-success">' +
                    d.imported + ' áudios importados (' + d.skipped_already + ' já estavam, ' + d.skipped_missing + ' sem arquivo).</span>');
                if (d.imported > 0) setTimeout(() => location.reload(), 1200);
                else btn.disabled = false;
            } else {
                setOut('<span class="text-danger">' + (d.message || 'Falha') + '</span>');
                btn.disabled = false;
            }
        });
    });

    // Transcrever pendentes em lote
    const btnPending = document.getElementById('btnProcessPending');
    if (btnPending) btnPending.addEventListener('click', function() {
        if (!confirm('Processar até 10 áudios pendentes agora? (cada um consome créditos OpenAI)')) return;
        btnPending.disabled = true;
        setOut('<i class="fas fa-spinner fa-spin"></i> Transcrevendo… isso pode levar alguns segundos por áudio.');
        fetch(base + 'ai/process_pending_transcriptions', {
            method: 'POST', credentials: 'same-origin',
            body: new URLSearchParams({ limit: 10 })
        }).then(r => r.json()).then(d => {
            if (d.success) {
                setOut('<span class="text-success">Processadas ' + d.processed + ' (' + d.completed + ' ok, ' + d.errors + ' com erro).</span>');
                setTimeout(() => location.reload(), 1500);
            } else {
                setOut('<span class="text-danger">' + (d.message || 'Falha') + '</span>');
                btnPending.disabled = false;
            }
        });
    });

    // Transcrever uma única linha
    document.querySelectorAll('.btn-process-one').forEach(b => b.addEventListener('click', function() {
        const id = this.dataset.id;
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        fetch(base + 'ai/process_transcription/' + id, { method: 'POST', credentials: 'same-origin' })
            .then(r => r.json()).then(d => {
                if (d.success) location.reload();
                else { alert(d.message || 'Falha'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-bolt"></i>'; }
            });
    }));

    // Edição
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    document.querySelectorAll('.btn-edit-transc').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('editId').value = this.dataset.id;
            document.getElementById('editText').value = this.dataset.text;
            editModal.show();
        });
    });
    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const body = new URLSearchParams(new FormData(this));
        fetch(base + 'ai/save_transcription_edit', { method:'POST', credentials:'same-origin', body: body })
            .then(r => r.json()).then(d => { if (d.success) location.reload(); else alert('Falha'); });
    });
})();
</script>
