<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Gestão da Equipe</h2>
                <p class="text-muted mb-0">Gerencie os membros e permissões de acesso</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="fas fa-user-plus me-2"></i>
                Adicionar Membro
            </button>
        </div>
    </div>
</div>

<!-- Resumo da Equipe - Layout horizontal compacto -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="row align-items-center text-center">
            <div class="col-md-3 border-end">
                <div class="d-flex align-items-center justify-content-center">
                    <i class="fas fa-users fa-2x text-primary me-3"></i>
                    <div class="text-start">
                        <h4 class="mb-0"><?= $stats['total'] ?? 0 ?></h4>
                        <small class="text-muted">Total Cadastrados</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 border-end">
                <div class="d-flex align-items-center justify-content-center">
                    <i class="fas fa-clipboard-user fa-2x text-success me-3"></i>
                    <div class="text-start">
                        <h4 class="mb-0"><?= $stats['aplicadores'] ?? 0 ?></h4>
                        <small class="text-muted">Coletores</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 border-end">
                <div class="d-flex align-items-center justify-content-center">
                    <i class="fas fa-user-shield fa-2x text-warning me-3"></i>
                    <div class="text-start">
                        <h4 class="mb-0"><?= $stats['supervisores'] ?? 0 ?></h4>
                        <small class="text-muted">Coordenadores</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center justify-content-center">
                    <i class="fas fa-user-crown fa-2x text-danger me-3"></i>
                    <div class="text-start">
                        <h4 class="mb-0"><?= $stats['administradores'] ?? 0 ?></h4>
                        <small class="text-muted">Gestores</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Usuários -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>Membro</th>
                        <th>Identificador</th>
                        <th>Contato</th>
                        <th>Perfil</th>
                        <th>Situação</th>
                        <th>Acesso</th>
                        <th>Cadastro</th>
                        <th class="text-center">Opções</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-3">
                                    <?= strtoupper(substr($user->full_name, 0, 2)) ?>
                                </div>
                                <strong><?= $user->full_name ?></strong>
                            </div>
                        </td>
                        <td><code><?= $user->username ?></code></td>
                        <td><?= $user->email ?></td>
                        <td>
                            <?php 
                            $role_colors = [
                                'administrador' => 'danger',
                                'supervisor' => 'warning',
                                'aplicador' => 'success'
                            ];
                            $color = $role_colors[$user->role] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $color ?>"><?= ucfirst($user->role) ?></span>
                        </td>
                        <td>
                            <span class="badge <?= $user->is_active == 'f' ? 'bg-danger' : 'bg-success'?>"><?= $user->is_active == 'f'  ? 'Desativado' : 'Ativo' ?></span>
                        </td>
                        <td>
                            <?php if (isset($user->updated_at) && $user->updated_at): ?>
                                <small><?= date('d/m/Y H:i', strtotime($user->updated_at)) ?></small>
                            <?php else: ?>
                                <small class="text-muted">Nunca</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?= date('d/m/Y', strtotime($user->created_at)) ?></small>
                        </td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="editUser(<?= $user->id ?>)" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                        onclick="resetPassword(<?= $user->id ?>)" title="Redefinir Senha">
                                    <i class="fas fa-key"></i>
                                </button>
                                <?php 
                                $current_user_id = $this->session->userdata('admin_id') ?? $this->session->userdata('user_id');
                                if ($user->id != $current_user_id): 
                                    $is_active = ($user->is_active == 1 || $user->is_active === true || $user->is_active === 't');
                                    $button_class = $is_active ? 'danger' : 'success';
                                    $button_action = $is_active ? 'Desativar' : 'Ativar';
                                    $button_icon = $is_active ? 'ban' : 'check';
                                    $new_status = $is_active ? '0' : '1';
                                ?>
                                <button type="button"
                                        class="btn btn-sm btn-outline-<?= $button_class ?>"
                                        onclick="toggleUserStatus(<?= $user->id ?>, '<?= $new_status ?>')"
                                        title="<?= $button_action ?>"
                                        data-user-id="<?= $user->id ?>"
                                        data-user-name="<?= $user->full_name ?>"
                                        data-current-status="<?= $is_active ? '1' : '0' ?>">
                                    <i class="fas fa-<?= $button_icon ?>"></i>
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="confirmDeleteUser(<?= $user->id ?>, '<?= htmlspecialchars($user->full_name, ENT_QUOTES) ?>', '<?= htmlspecialchars($user->username, ENT_QUOTES) ?>')"
                                        title="Excluir definitivamente">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Criar Usuário -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Novo Membro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <?= form_open('users/create', ['id' => 'createUserForm']) ?>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="full_name" class="form-label">Nome Completo *</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                </div>
                
                <div class="mb-3">
                    <label for="username" class="form-label">Usuário *</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                    <div class="form-text">Deve ser único no sistema</div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="mb-3">
                    <label for="role" class="form-label">Perfil de Acesso *</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">Selecione...</option>
                        <option value="aplicador">Coletor de Campo</option>
                        <option value="supervisor">Coordenador</option>
                        <option value="administrador">Gestor</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Senha *</label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                    <div class="form-text">Mínimo 6 caracteres</div>
                </div>
                
                <div class="mb-3">
                    <label for="password_confirm" class="form-label">Confirmar Senha *</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Cadastrar</button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<!-- Modal Confirmar Exclusão -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Excluir Usuário
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Você tem certeza que deseja <strong>excluir definitivamente</strong> o usuário abaixo?</p>
                <div class="alert alert-warning mb-3">
                    <strong id="deleteUserName"></strong><br>
                    <small class="text-muted">Usuário: <code id="deleteUserUsername"></code></small>
                </div>
                <p class="text-danger mb-2">
                    <i class="fas fa-info-circle me-1"></i>
                    Esta ação <strong>não pode ser desfeita</strong>. Todos os dados de acesso do usuário serão removidos.
                </p>
                <p class="text-muted small mb-0">
                    Caso o usuário possua respostas ou registros vinculados, a exclusão poderá falhar. Nesse caso, considere apenas desativá-lo.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-2"></i>Excluir Definitivamente
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Usuário -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Membro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm" method="post">
                <div class="modal-body">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Nome Completo *</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Usuário *</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                        <div class="form-text">Deve ser único no sistema</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Perfil de Acesso *</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="">Selecione...</option>
                            <option value="aplicador">Coletor de Campo</option>
                            <option value="supervisor">Coordenador</option>
                            <option value="administrador">Gestor</option>
                        </select>
                    </div>
                    
                    <hr>
                    <h6 class="text-muted">Alterar Senha (opcional)</h6>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Nova Senha</label>
                        <input type="password" class="form-control" id="edit_password" name="password" minlength="6">
                        <div class="form-text">Deixe em branco para manter a senha atual. Mínimo 6 caracteres se alterar.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_password_confirm" class="form-label">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" id="edit_password_confirm" name="password_confirm">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: linear-gradient(135deg, #23345F, #1a2847);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 14px;
}

.btn-group .btn {
    margin: 0 1px;
}

#editUserModal .form-text {
    font-size: 0.875em;
    color: #6c757d;
}

.btn[disabled] {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn .fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

</style>

<script>
// Função para editar usuário
function editUser(userId) {
    // Fazer requisição AJAX para buscar dados do usuário
    fetch(`<?= base_url('users/get_user/') ?>${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Popular o modal com os dados do usuário
                document.getElementById('edit_user_id').value = data.user.id;
                document.getElementById('edit_full_name').value = data.user.full_name;
                document.getElementById('edit_username').value = data.user.username;
                document.getElementById('edit_email').value = data.user.email;
                document.getElementById('edit_role').value = data.user.role;
                
                // Limpar campos de senha
                document.getElementById('edit_password').value = '';
                document.getElementById('edit_password_confirm').value = '';
                
                // Definir a action do formulário
                document.getElementById('editUserForm').action = `<?= base_url('users/edit/') ?>${userId}`;
                
                // Exibir o modal
                const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                editModal.show();
            } else {
                alert('Erro ao carregar dados do usuário: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar dados do usuário');
        });
}

// Função para resetar senha
function resetPassword(userId) {
    if (confirm('Tem certeza que deseja redefinir a senha deste usuário?')) {
        window.location.href = '<?= base_url('users/reset_password/') ?>' + userId;
    }
}

// Função para alternar status do usuário
function toggleUserStatus(userId, newStatus) {
    const status = parseInt(newStatus);
    const action = status === 1 ? 'ativar' : 'desativar';
    const button = event.target.closest('button');
    const userName = button.getAttribute('data-user-name');
    
    if (confirm(`Tem certeza que deseja ${action} o usuário "${userName}"?`)) {
        // Mostrar loading
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        // Tentar primeiro com AJAX
        toggleUserStatusAjax(userId, newStatus, button, originalHTML);
    }
}

function toggleUserStatusAjax(userId, newStatus, button, originalHTML) {
    fetch('<?= base_url('users/toggle_status_ajax/') ?>' + userId + '/' + newStatus, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Sucesso - recarregar página
            location.reload();
        } else {
            alert('Erro: ' + data.message);
            button.innerHTML = originalHTML;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.log('AJAX falhou, tentando redirecionamento...', error);
        // Fallback para redirecionamento
        window.location.href = '<?= base_url('users/toggle_status/') ?>' + userId + '/' + newStatus;
    });
}

// Função para confirmar e excluir usuário definitivamente
let pendingDeleteUserId = null;
function confirmDeleteUser(userId, userName, username) {
    pendingDeleteUserId = userId;
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteUserUsername').textContent = username;

    const confirmBtn = document.getElementById('confirmDeleteBtn');
    confirmBtn.disabled = false;
    confirmBtn.innerHTML = '<i class="fas fa-trash me-2"></i>Excluir Definitivamente';

    const deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    deleteModal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (!pendingDeleteUserId) return;
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Excluindo...';

    fetch('<?= base_url('users/delete_ajax/') ?>' + pendingDeleteUserId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Não foi possível excluir.'));
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash me-2"></i>Excluir Definitivamente';
        }
    })
    .catch(error => {
        console.log('AJAX falhou, usando redirecionamento...', error);
        window.location.href = '<?= base_url('users/delete/') ?>' + pendingDeleteUserId;
    });
});

// Validação do formulário de edição
document.getElementById('editUserForm').addEventListener('submit', function(e) {
    const password = document.getElementById('edit_password').value;
    const confirmPassword = document.getElementById('edit_password_confirm').value;
    
    // Se uma nova senha foi informada, validar confirmação
    if (password || confirmPassword) {
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('As senhas não coincidem!');
            return false;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            alert('A nova senha deve ter pelo menos 6 caracteres!');
            return false;
        }
    }
});

// Validação do formulário de criação
document.getElementById('createUserForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('password_confirm').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('As senhas não coincidem!');
        return false;
    }
});

// Limpar formulário de edição quando modal for fechado
document.getElementById('editUserModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('editUserForm').reset();
});
</script>