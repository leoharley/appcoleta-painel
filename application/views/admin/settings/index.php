<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Configurações do Sistema</h2>
            <div>
                <button class="btn btn-outline-warning me-2" onclick="backupDatabase()" style="display:none">
                    <i class="fas fa-database me-2"></i>
                    Backup BD
                </button>
                <button class="btn btn-success" onclick="saveAllSettings()">
                    <i class="fas fa-save me-2"></i>
                    Salvar Tudo
                </button>
            </div>
        </div>
    </div>
</div>

<?= form_open('settings/update', ['id' => 'settingsForm']) ?>
<div class="row">
    <div class="col-lg-8">
        <!-- Configurações Gerais -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Configurações Gerais</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="app_name" class="form-label">Nome do Sistema</label>
                            <input type="text" class="form-control" id="app_name" name="settings[app_name]" 
                                   value="<?= $settings['app_name'] ?? 'AppColeta' ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Nome da Empresa</label>
                            <input type="text" class="form-control" id="company_name" name="settings[company_name]" 
                                   value="<?= $settings['company_name'] ?? 'SocialX' ?>">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="app_description" class="form-label">Descrição do Sistema</label>
                    <textarea class="form-control" id="app_description" name="settings[app_description]" 
                              rows="3"><?= $settings['app_description'] ?? 'Sistema de coleta e análise de dados via questionários móveis' ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="timezone" class="form-label">Fuso Horário</label>
                            <select class="form-select" id="timezone" name="settings[timezone]">
                                <option value="America/Sao_Paulo" <?= set_select('settings[timezone]', 'America/Sao_Paulo', 
                                       ($settings['timezone'] ?? 'America/Sao_Paulo') == 'America/Sao_Paulo') ?>>
                                    América/São Paulo (BRT)
                                </option>
                                <option value="America/New_York" <?= set_select('settings[timezone]', 'America/New_York', 
                                       ($settings['timezone'] ?? '') == 'America/New_York') ?>>
                                    América/Nova York (EST)
                                </option>
                                <option value="Europe/London" <?= set_select('settings[timezone]', 'Europe/London', 
                                       ($settings['timezone'] ?? '') == 'Europe/London') ?>>
                                    Europa/Londres (GMT)
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="date_format" class="form-label">Formato de Data</label>
                            <select class="form-select" id="date_format" name="settings[date_format]">
                                <option value="d/m/Y" <?= set_select('settings[date_format]', 'd/m/Y', 
                                       ($settings['date_format'] ?? 'd/m/Y') == 'd/m/Y') ?>>
                                    dd/mm/aaaa (Brasil)
                                </option>
                                <option value="m/d/Y" <?= set_select('settings[date_format]', 'm/d/Y', 
                                       ($settings['date_format'] ?? '') == 'm/d/Y') ?>>
                                    mm/dd/aaaa (EUA)
                                </option>
                                <option value="Y-m-d" <?= set_select('settings[date_format]', 'Y-m-d', 
                                       ($settings['date_format'] ?? '') == 'Y-m-d') ?>>
                                    aaaa-mm-dd (ISO)
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Configurações de Segurança -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Segurança e Privacidade</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="session_timeout" class="form-label">Timeout de Sessão (minutos)</label>
                            <input type="number" class="form-control" id="session_timeout" name="settings[session_timeout]" 
                                   value="<?= $settings['session_timeout'] ?? '120' ?>" min="15" max="1440">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="max_login_attempts" class="form-label">Tentativas Máximas de Login</label>
                            <input type="number" class="form-control" id="max_login_attempts" name="settings[max_login_attempts]" 
                                   value="<?= $settings['max_login_attempts'] ?? '5' ?>" min="3" max="10">
                        </div>
                    </div>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="require_https" name="settings[require_https]" 
                           value="1" <?= set_checkbox('settings[require_https]', '1', ($settings['require_https'] ?? false)) ?>>
                    <label class="form-check-label" for="require_https">
                        <strong>Forçar HTTPS</strong>
                        <br><small class="text-muted">Redirecionar automaticamente para conexões seguras</small>
                    </label>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="enable_2fa" name="settings[enable_2fa]" 
                           value="1" <?= set_checkbox('settings[enable_2fa]', '1', ($settings['enable_2fa'] ?? false)) ?>>
                    <label class="form-check-label" for="enable_2fa">
                        <strong>Autenticação em Duas Etapas (2FA)</strong>
                        <br><small class="text-muted">Habilitar 2FA para administradores</small>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Configurações de Arquivo -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Arquivos e Uploads</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="max_file_size" class="form-label">Tamanho Máximo de Arquivo (MB)</label>
                            <input type="number" class="form-control" id="max_file_size" name="settings[max_file_size]" 
                                   value="<?= ($settings['max_file_size'] ?? 10485760) / 1048576 ?>" min="1" max="50">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="image_quality" class="form-label">Qualidade de Imagem (%)</label>
                            <input type="number" class="form-control" id="image_quality" name="settings[image_quality]" 
                                   value="<?= $settings['image_quality'] ?? '80' ?>" min="30" max="100">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="allowed_file_types" class="form-label">Tipos de Arquivo Permitidos</label>
                    <input type="text" class="form-control" id="allowed_file_types" name="settings[allowed_file_types]" 
                           value="<?= $settings['allowed_file_types'] ?? 'jpg,jpeg,png,pdf,doc,docx' ?>"
                           placeholder="jpg,jpeg,png,pdf">
                    <div class="form-text">Separar por vírgulas, sem espaços</div>
                </div>
                
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="auto_backup_photos" name="settings[auto_backup_photos]" 
                           value="1" <?= set_checkbox('settings[auto_backup_photos]', '1', ($settings['auto_backup_photos'] ?? true)) ?>>
                    <label class="form-check-label" for="auto_backup_photos">
                        <strong>Backup Automático de Fotos</strong>
                        <br><small class="text-muted">Criar backup das fotos automaticamente</small>
                    </label>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Configurações de Notificação -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Notificações</h5>
            </div>
            <div class="card-body">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="email_notifications" name="settings[email_notifications]" 
                           value="1" <?= set_checkbox('settings[email_notifications]', '1', ($settings['email_notifications'] ?? true)) ?>>
                    <label class="form-check-label" for="email_notifications">
                        <strong>Notificações por Email</strong>
                    </label>
                </div>
                
                <div class="mb-3">
                    <label for="notification_email" class="form-label">Email de Notificações</label>
                    <input type="email" class="form-control" id="notification_email" name="settings[notification_email]" 
                           value="<?= $settings['notification_email'] ?? '' ?>">
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="notify_new_responses" name="settings[notify_new_responses]" 
                           value="1" <?= set_checkbox('settings[notify_new_responses]', '1', ($settings['notify_new_responses'] ?? false)) ?>>
                    <label class="form-check-label" for="notify_new_responses">
                        Notificar novas respostas
                    </label>
                </div>
                
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="notify_sync_errors" name="settings[notify_sync_errors]" 
                           value="1" <?= set_checkbox('settings[notify_sync_errors]', '1', ($settings['notify_sync_errors'] ?? true)) ?>>
                    <label class="form-check-label" for="notify_sync_errors">
                        Notificar erros de sincronização
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Configurações de Sincronização -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Sincronização</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="sync_interval" class="form-label">Intervalo de Sincronização (minutos)</label>
                    <select class="form-select" id="sync_interval" name="settings[sync_interval]">
                        <option value="5" <?= set_select('settings[sync_interval]', '5', ($settings['sync_interval'] ?? '15') == '5') ?>>5 minutos</option>
                        <option value="15" <?= set_select('settings[sync_interval]', '15', ($settings['sync_interval'] ?? '15') == '15') ?>>15 minutos</option>
                        <option value="30" <?= set_select('settings[sync_interval]', '30', ($settings['sync_interval'] ?? '15') == '30') ?>>30 minutos</option>
                        <option value="60" <?= set_select('settings[sync_interval]', '60', ($settings['sync_interval'] ?? '15') == '60') ?>>1 hora</option>
                    </select>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="auto_retry_failed" name="settings[auto_retry_failed]" 
                           value="1" <?= set_checkbox('settings[auto_retry_failed]', '1', ($settings['auto_retry_failed'] ?? true)) ?>>
                    <label class="form-check-label" for="auto_retry_failed">
                        <strong>Tentar Novamente Automaticamente</strong>
                        <br><small class="text-muted">Retentar sincronizações que falharam</small>
                    </label>
                </div>
                
                <div class="mb-3">
                    <label for="max_retry_attempts" class="form-label">Máximo de Tentativas</label>
                    <input type="number" class="form-control" id="max_retry_attempts" name="settings[max_retry_attempts]" 
                           value="<?= $settings['max_retry_attempts'] ?? '3' ?>" min="1" max="10">
                </div>
            </div>
        </div>
        
        <!-- Informações do Sistema -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Informações do Sistema</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary mb-0"><?= $system_info['version'] ?></h4>
                        <small class="text-muted">Versão</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-0"><?= $system_info['uptime'] ?></h4>
                        <small class="text-muted">Uptime</small>
                    </div>
                </div>
                <hr>
                <small class="text-muted">
                    <strong>PHP:</strong> <?= PHP_VERSION ?><br>
                    <strong>Database:</strong> PostgreSQL <?= $system_info['db_version'] ?><br>
                    <strong>Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?><br>
                    <strong>Espaço em Disco:</strong> <?= $system_info['disk_usage'] ?><br>
                    <strong>Último Backup:</strong> <?= $system_info['last_backup'] ?? 'Nunca' ?>
                </small>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>

<script>
function saveAllSettings() {
    document.getElementById('settingsForm').submit();
}

function backupDatabase() {
    if (confirm('Tem certeza que deseja criar um backup do banco de dados?')) {
        window.location.href = '<?= base_url('settings/backup_database') ?>';
    }
}

// Auto-save (salvar automaticamente a cada 30 segundos)
let autoSaveInterval;
let formChanged = false;

document.getElementById('settingsForm').addEventListener('change', function() {
    formChanged = true;
    
    // Limpar intervalo anterior
    if (autoSaveInterval) {
        clearTimeout(autoSaveInterval);
    }
    
    // Definir novo intervalo
    autoSaveInterval = setTimeout(function() {
        if (formChanged) {
            // Mostrar indicador de salvamento
            const indicator = document.createElement('div');
            indicator.className = 'alert alert-info';
            indicator.innerHTML = '<i class="fas fa-save me-2"></i>Salvando automaticamente...';
            document.querySelector('.content-wrapper').insertBefore(indicator, document.querySelector('.row'));
            
            // Enviar dados via AJAX
            const formData = new FormData(document.getElementById('settingsForm'));
            
            fetch('<?= base_url('settings/auto_save') ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                indicator.className = 'alert alert-success';
                indicator.innerHTML = '<i class="fas fa-check me-2"></i>Configurações salvas automaticamente';
                setTimeout(() => indicator.remove(), 3000);
                formChanged = false;
            })
            .catch(error => {
                indicator.className = 'alert alert-warning';
                indicator.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Erro no salvamento automático';
                setTimeout(() => indicator.remove(), 5000);
            });
        }
    }, 10000); // 10 segundos após a última alteração
});
</script>