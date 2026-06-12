<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Histórico de Exportações</h2>
            <a href="<?= base_url('responses') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Voltar às Respostas
            </a>
        </div>
    </div>
</div>

<!-- Limites de Exportação -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Limites de Uso
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="<?= $export_limits['current_hour'] >= $export_limits['max_per_hour'] ? 'text-danger' : 'text-success' ?>">
                            <?= $export_limits['current_hour'] ?>/<?= $export_limits['max_per_hour'] ?>
                        </h4>
                        <small class="text-muted">Exportações por Hora</small>
                    </div>
                    <div class="col-6">
                        <h4 class="<?= $export_limits['current_day'] >= $export_limits['max_per_day'] ? 'text-danger' : 'text-success' ?>">
                            <?= $export_limits['current_day'] ?>/<?= $export_limits['max_per_day'] ?>
                        </h4>
                        <small class="text-muted">Exportações por Dia</small>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Informações
                </h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li><i class="fas fa-check text-success me-2"></i>Máximo 5 exportações por hora</li>
                    <li><i class="fas fa-check text-success me-2"></i>Máximo 20 exportações por dia</li>
                    <li><i class="fas fa-check text-success me-2"></i>Histórico mantido por 90 dias</li>
                    <li><i class="fas fa-check text-success me-2"></i>Logs de auditoria completos</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Histórico de Exportações -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-history me-2"></i>
            Minhas Exportações Recentes
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($export_history)): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Tipo</th>
                        <th>Filtros</th>
                        <th>Registros</th>
                        <th>Status</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($export_history as $record): ?>
                    <tr>
                        <td>
                            <small>
                                <?= date('d/m/Y H:i:s', strtotime($record->created_at)) ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-primary">
                                <?= ucfirst($record->details['export_type'] ?? 'Raw Data') ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $filters = $record->details['filters'] ?? [];
                            $filter_summary = [];
                            
                            if (isset($filters['questionnaire_id'])) {
                                $filter_summary[] = 'Questionário: ' . ($filters['questionnaire_id'] === 'all' ? 'Todos' : '#' . $filters['questionnaire_id']);
                            }
                            
                            if (isset($filters['date_from']) && $filters['date_from']) {
                                $filter_summary[] = 'De: ' . date('d/m/Y', strtotime($filters['date_from']));
                            }
                            
                            if (isset($filters['date_to']) && $filters['date_to']) {
                                $filter_summary[] = 'Até: ' . date('d/m/Y', strtotime($filters['date_to']));
                            }
                            
                            if (isset($filters['applied_by']) && $filters['applied_by']) {
                                $filter_summary[] = 'Aplicador: #' . $filters['applied_by'];
                            }
                            
                            echo !empty($filter_summary) ? implode('<br><small>', $filter_summary) : 'Sem filtros';
                            ?>
                        </td>
                        <td>
                            <strong><?= number_format($record->details['record_count'] ?? 0) ?></strong>
                        </td>
                        <td>
                            <?php 
                            $status = $record->details['status'] ?? 'unknown';
                            $status_classes = [
                                'success' => 'success',
                                'started' => 'info',
                                'error' => 'danger',
                                'validation_error' => 'warning'
                            ];
                            $status_texts = [
                                'success' => 'Concluído',
                                'started' => 'Iniciado',
                                'error' => 'Erro',
                                'validation_error' => 'Erro de Validação'
                            ];
                            $class = $status_classes[$status] ?? 'secondary';
                            $text = $status_texts[$status] ?? ucfirst($status);
                            ?>
                            <span class="badge bg-<?= $class ?>">
                                <?= $text ?>
                            </span>
                            
                            <?php if ($status === 'error' && isset($record->details['error_message'])): ?>
                            <br><small class="text-danger" title="<?= htmlspecialchars($record->details['error_message']) ?>">
                                <?= substr($record->details['error_message'], 0, 50) ?>...
                            </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?= $record->ip_address ?? 'N/A' ?>
                            </small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-file-export fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Nenhuma exportação realizada</h5>
            <p class="text-muted">Quando você realizar exportações, elas aparecerão aqui.</p>
            <a href="<?= base_url('responses') ?>" class="btn btn-primary">
                <i class="fas fa-download me-2"></i>
                Fazer Primeira Exportação
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Estatísticas Administrativas (apenas para administradores) -->
<?php if ($this->session->userdata('user_role') === 'administrador' && isset($export_history['daily_statistics'])): ?>
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Uso Diário (Últimos 30 dias)
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($export_history['daily_statistics'])): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Exportações</th>
                                <th>Usuários</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($export_history['daily_statistics'], -10) as $stat): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($stat->date)) ?></td>
                                <td><?= $stat->export_count ?></td>
                                <td><?= $stat->unique_users ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">Nenhum dado disponível.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users me-2"></i>
                    Usuários Mais Ativos
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($export_history['top_users'])): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Exportações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($export_history['top_users'] as $user): ?>
                            <tr>
                                <td>
                                    <?= $user->full_name ?>
                                    <br><small class="text-muted">@<?= $user->username ?></small>
                                </td>
                                <td><?= $user->export_count ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">Nenhum usuário ativo encontrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
    background-color: #f8f9fa;
}

.badge {
    font-size: 0.75rem;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.text-muted {
    font-size: 0.875rem;
}
</style>