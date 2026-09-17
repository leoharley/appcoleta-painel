<?php 

function get_location_name($latitude, $longitude)
{
    // Verificar se as coordenadas são válidas
    if (empty($latitude) || empty($longitude) || 
        !is_numeric($latitude) || !is_numeric($longitude)) {
        return 'N/A';
    }

    // Validar range das coordenadas
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        return 'N/A';
    }

    try {
        // Usando OpenStreetMap Nominatim (gratuito, sem necessidade de API key)
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$latitude}&lon={$longitude}&zoom=18&addressdetails=1";
        
        // Configurar contexto da requisição
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: AppColeta-App/1.0',
                    'Accept: application/json'
                ],
                'timeout' => 5 // Reduzido para 5 segundos para não travar a página
            ]
        ]);

        $response = file_get_contents($url, false, $context);
        
        if ($response === FALSE) {
            return 'N/A';
        }

        $data = json_decode($response, true);
        
        if (isset($data['display_name'])) {
            // Retornar o nome formatado da localização
            return format_location_name($data); // Removido $this->
        }

    } catch (Exception $e) {
        // Log do erro se necessário (CodeIgniter)
        if (function_exists('log_message')) {
            log_message('error', 'Erro na geocodificação: ' . $e->getMessage());
        }
    }

    return 'N/A';
}

function format_location_name($data)
{
    if (!isset($data['address'])) {
        return isset($data['display_name']) ? $data['display_name'] : 'N/A';
    }

    $address = $data['address'];
    $location_parts = [];

    // Priorizar informações mais específicas para o Brasil
    if (!empty($address['road'])) {
        $location_parts[] = $address['road'];
    }
    
    if (!empty($address['suburb']) || !empty($address['neighbourhood'])) {
        $location_parts[] = $address['suburb'] ?? $address['neighbourhood'];
    }
    
    if (!empty($address['city']) || !empty($address['town']) || !empty($address['village'])) {
        $location_parts[] = $address['city'] ?? $address['town'] ?? $address['village'];
    }
    
    if (!empty($address['state'])) {
        $location_parts[] = $address['state'];
    }

    // Limitar o tamanho da string retornada para não quebrar o layout
    $result = !empty($location_parts) ? implode(', ', $location_parts) : $data['display_name'];
    
    // Se muito longo, truncar e adicionar "..."
    if (strlen($result) > 60) {
        $result = substr($result, 0, 57) . '...';
    }
    
    return $result;
}

// Função alternativa mais rápida que usa cache simples
function get_location_name_cached($latitude, $longitude)
{
    // Criar uma chave de cache baseada nas coordenadas (arredondadas)
    $cache_key = 'location_' . round($latitude, 3) . '_' . round($longitude, 3);
    
    // Verificar se já temos o resultado em cache (usando CI cache se disponível)
    $CI =& get_instance();
    if (method_exists($CI, 'cache')) {
        $cached_result = $CI->cache->get($cache_key);
        if ($cached_result !== FALSE) {
            return $cached_result;
        }
    }
    
    // Se não estiver em cache, buscar
    $result = get_location_name($latitude, $longitude);
    
    // Salvar no cache por 1 hora
    if (method_exists($CI, 'cache') && $result !== 'N/A') {
        $CI->cache->save($cache_key, $result, 3600);
    }
    
    return $result;
}

?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Respostas dos Formulários</h2>
            <div>
                <button class="btn btn-outline-primary me-2" data-bs-toggle="collapse" data-bs-target="#filtersCollapse">
                    <i class="fas fa-filter me-2"></i>
                    Filtros
                </button>
                <a href="#" id="btnExportar" class="btn btn-success me-2" onclick="exportarRespostas(event)">
                    <i class="fas fa-download me-2"></i>
                    Exportar
                </a>
                <!-- NOVO BOTÃO: Exportar Dados Brutos -->
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportRawDataModal">
                    <i class="fas fa-file-excel me-2"></i>
                    Exportar Dados Brutos
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="collapse <?= !empty($filters) ? 'show' : '' ?>" id="filtersCollapse">
    <div class="card mb-4">
        <div class="card-body">
            <?= form_open('responses', ['method' => 'GET']) ?>
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Questionário</label>
                    <select class="form-select" name="questionnaire_id">
                        <option value="">Todos</option>
                        <?php foreach ($questionnaires as $questionnaire): ?>
                        <option value="<?= $questionnaire->id ?>" 
                                <?= set_select('questionnaire_id', $questionnaire->id, 
                                   isset($filters['questionnaire_id']) && $filters['questionnaire_id'] == $questionnaire->id) ?>>
                            <?= $questionnaire->title ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Aplicador</label>
                    <select class="form-select" name="applied_by">
                        <option value="">Todos</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?= $user->id ?>" 
                                <?= set_select('applied_by', $user->id, 
                                   isset($filters['applied_by']) && $filters['applied_by'] == $user->id) ?>>
                            <?= $user->full_name ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Data Início</label>
                    <input type="date" class="form-control" name="date_from" 
                           value="<?= $filters['date_from'] ?? '' ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Data Fim</label>
                    <input type="date" class="form-control" name="date_to" 
                           value="<?= $filters['date_to'] ?? '' ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status de Sincronização</label>
                    <select class="form-select" name="sync_status">
                        <option value="">Todos</option>
                        <option value="synced" <?= set_select('sync_status', 'synced', 
                               isset($filters['sync_status']) && $filters['sync_status'] == 'synced') ?>>
                            Sincronizado
                        </option>
                        <option value="pending" <?= set_select('sync_status', 'pending', 
                               isset($filters['sync_status']) && $filters['sync_status'] == 'pending') ?>>
                            Pendente
                        </option>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<!-- NOVO MODAL: Exportação de Dados Brutos -->
<div class="modal fade" id="exportRawDataModal" tabindex="-1" aria-labelledby="exportRawDataModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportRawDataModalLabel">
                    <i class="fas fa-file-excel me-2"></i>
                    Exportar Dados Brutos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Container para alertas dinâmicos -->
                <div id="export-alert-container"></div>
                
                <form id="exportRawDataForm" action="<?= base_url('responses/export_raw_data') ?>" method="POST">
                    <div class="mb-3">
                        <label for="export_questionnaire_id" class="form-label">
                            <i class="fas fa-list me-2"></i>
                            Selecionar Questionário
                        </label>
                        <select class="form-select" id="export_questionnaire_id" name="questionnaire_id" required>
                            <option value="">Selecione um questionário...</option>
                            <option value="all">Todos os Questionários</option>
                            <?php foreach ($questionnaires as $questionnaire): ?>
                            <option value="<?= $questionnaire->id ?>">
                                <?= $questionnaire->title ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            Selecione "Todos os Questionários" para exportar dados de todos os formulários ou escolha um questionário específico.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-calendar me-2"></i>
                            Filtros Adicionais (Opcional)
                        </label>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="export_date_from" class="form-label small">Data Início</label>
                                <input type="date" class="form-control" id="export_date_from" name="date_from">
                            </div>
                            <div class="col-md-6">
                                <label for="export_date_to" class="form-label small">Data Fim</label>
                                <input type="date" class="form-control" id="export_date_to" name="date_to">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="export_applied_by" class="form-label">
                            <i class="fas fa-user me-2"></i>
                            Aplicador (Opcional)
                        </label>
                        <select class="form-select" id="export_applied_by" name="applied_by">
                            <option value="">Todos os aplicadores</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?= $user->id ?>">
                                <?= $user->full_name ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Formato de Exportação:</strong> Os dados serão exportados em formato Excel (.xlsx) seguindo o modelo de estrutura fornecido, com todas as respostas organizadas em colunas correspondentes às perguntas dos questionários.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>
                    Cancelar
                </button>
                <button type="submit" form="exportRawDataForm" class="btn btn-primary">
                    <i class="fas fa-download me-2"></i>
                    Exportar Dados
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Respostas (mantém o código original) -->
<div class="card">
    <div class="card-body">
        <?php if (!empty($responses)): ?>

        <?php
            $shown = count($responses);
            $total = isset($total_count) ? (int)$total_count : $shown;
            $lim   = isset($limit) ? (int)$limit : $shown;
        ?>
        <?php if ($total > $shown): ?>
        <div class="alert alert-warning py-2 small d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-info-circle me-1"></i>
                Exibindo as <strong><?= number_format($shown, 0, ',', '.') ?></strong> respostas mais
                recentes de <strong><?= number_format($total, 0, ',', '.') ?></strong> no total.
                Use os <strong>Filtros</strong> para localizar respostas específicas.
            </div>
            <div class="ms-3 text-nowrap">
                <?php
                    // Reconstroi a URL apenas com os filtros conhecidos —
                    // repassar $_GET inteiro deixaria entrar parametro qualquer.
                    $qs = array();
                    foreach (array('questionnaire_id','applied_by','date_from','date_to','sync_status') as $k) {
                        if (!empty($filters[$k])) $qs[$k] = $filters[$k];
                    }
                    $qs['limit'] = min($lim * 2, 5000);
                    $more_url = base_url('responses') . '?' . http_build_query($qs);
                ?>
                <?php if ($lim < 5000): ?>
                <a href="<?= $more_url ?>" class="btn btn-sm btn-outline-secondary">
                    Carregar mais
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Questionário</th>
                        <th>Respondente</th>
                        <th>Aplicador</th>
                        <th>Local</th>
                        <th>Data/Hora</th>
                        <th>Status</th>
                        <th class="text-center">Áudio</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($responses as $response): ?>
                    <tr>
                        <td><strong>#<?= $response->id ?></strong></td>
                        <td>
                            <strong><?= $response->questionnaire_title ?></strong>
                        </td>
                        <td>
                            <?php if ($response->respondent_name): ?>
                                <?= $response->respondent_name ?>
                                <?php if ($response->respondent_email): ?>
                                    <br><small class="text-muted"><?= $response->respondent_email ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Não informado</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $response->applied_by_name ?></td>
                        <td>
                            <?php if ($response->latitude && $response->longitude): ?>
                                <div class="location-info" data-lat="<?= $response->latitude ?>" data-lng="<?= $response->longitude ?>">
                                    <small class="text-muted d-block">
                                        <i class="fas fa-map-pin me-1"></i>
                                        <?= number_format($response->latitude, 5) ?>,
                                        <?= number_format($response->longitude, 5) ?>
                                    </small>
                                    <strong class="location-name d-block small text-dark" style="display:none!important;"></strong>
                                    <button type="button" class="btn btn-link btn-sm p-0 location-btn"
                                            onclick="lookupAddress(this)" title="Consultar o endereço destas coordenadas">
                                        <i class="fas fa-search-location me-1"></i>Ver endereço
                                    </button>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">
                                    <i class="fas fa-map-pin me-1"></i>
                                    Localização não capturada
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($response->completed_at): ?>
                                <small>
                                    <i class="fas fa-clock me-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($response->completed_at)) ?>
                                </small>
                            <?php else: ?>
                                <span class="text-warning">
                                    <i class="fas fa-clock me-1"></i>
                                    Incompleto
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $status_classes = [
                                'synced' => 'success',
                                'pending' => 'warning',
                                'error' => 'danger'
                            ];
                            $status_texts = [
                                'synced' => 'Sincronizado',
                                'pending' => 'Pendente',
                                'error' => 'Erro'
                            ];
                            $class = $status_classes[$response->sync_status] ?? 'secondary';
                            $text = $status_texts[$response->sync_status] ?? $response->sync_status;
                            ?>
                            <span class="badge bg-<?= $class ?>">
                                <?= $text ?>
                            </span>
                            
                            <?php if ($response->consent_given): ?>
                                <br><small class="text-success">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Consentimento
                                </small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if (!empty($response->audio_path)): ?>
                                <a href="<?= base_url('uploads/audios/' . rawurlencode($response->audio_path)) ?>"
                                   download="<?= htmlspecialchars($response->audio_path) ?>"
                                   class="btn btn-sm btn-outline-secondary"
                                   title="Baixar áudio (<?= htmlspecialchars($response->audio_path) ?>)">
                                    <i class="fas fa-microphone"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-muted" title="Sem áudio">
                                    <i class="fas fa-microphone-slash"></i>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <a href="<?= base_url('responses/view/' . $response->id) ?>"
                                   class="btn btn-sm btn-outline-primary" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($response->latitude && $response->longitude): ?>
                                <a href="https://maps.google.com/?q=<?= $response->latitude ?>,<?= $response->longitude ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-success" title="Ver no Mapa">
                                    <i class="fas fa-map-marker-alt"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($response->photo_path): ?>
                                <button type="button" class="btn btn-sm btn-outline-info"
                                        onclick="showPhoto('<?= base_url('uploads/photos/' . $response->photo_path) ?>')"
                                        title="Ver Foto 1">
                                    <i class="fas fa-camera"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($response->photo_path_2): ?>
                                <button type="button" class="btn btn-sm btn-outline-info"
                                        onclick="showPhoto('<?= base_url('uploads/photos/' . $response->photo_path_2) ?>')"
                                        title="Ver Foto 2">
                                    <i class="fas fa-camera"></i> 2
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Nenhuma resposta encontrada</h5>
            <p class="text-muted">Tente ajustar os filtros ou aguarde novas respostas serem coletadas.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para exibir foto -->
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Foto Capturada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="photoImage" src="" class="img-fluid" alt="Foto capturada" style="max-height: 70vh;">
            </div>
        </div>
    </div>
</div>

<style>

.location-info {
    max-width: 200px;
    word-wrap: break-word;
}

.table td {
    vertical-align: middle;
}

/* Melhorar a apresentação dos badges */
.badge {
    font-size: 0.7rem;
}

/* Loading animation */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}


/* Pulse animation para loading */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.loading-text {
    animation: pulse 1.5s infinite;
}

/* Botao "Ver endereco" — discreto, alinhado ao texto da celula */
.location-btn {
    font-size: 0.78rem;
    text-decoration: none;
}
.location-btn:hover {
    text-decoration: underline;
}
.location-name {
    line-height: 1.25;
    margin-top: 2px;
}

/* Estilos para o modal de exportação */
#exportRawDataModal .modal-dialog {
    max-width: 600px;
}

#exportRawDataModal .form-text {
    color: #6c757d;
    font-size: 0.875rem;
}

#exportRawDataModal .alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}
</style>

<script>
// ==========================================================================
// Geocoding rápido para a coluna "Localização" com:
//   - Cache no localStorage (persiste entre navegações da mesma sessão)
//   - Requisições em PARALELO ao endpoint do backend (que já tem cache em
//     disco de 7 dias). Antes ia direto no Nominatim → rate-limit → travava.
//   - Timeout curto (6s) — se não voltar, mostra as coordenadas em vez
//     de deixar "Carregando..." eterno.
//   - Cache-key arredondado (~11 m) → coletas próximas usam o mesmo resultado.
// ==========================================================================

const GEO_BASE_URL   = '<?= base_url("responses/get_location") ?>';
const GEO_CACHE_KEY  = 'geo_cache_v2';       // versão pra invalidar caches antigos
const GEO_TIMEOUT_MS = 6000;
// Nominatim pede no maximo ~1 req/s por cliente: com valor alto ele
// responde devagar ou bloqueia, o que piora o tempo total em vez de
// melhorar. 2 e o equilibrio — o ganho real vem do cache e de nao
// segurar o lock da sessao no servidor.
const GEO_ROUND_DECIMALS = 4;                 // ~11 metros

const _geoMem = new Map();   // cache in-memory (dentro da mesma página)
let _geoStore = {};
try {
    _geoStore = JSON.parse(localStorage.getItem(GEO_CACHE_KEY) || '{}');
} catch (e) { _geoStore = {}; }

function geoKey(lat, lng) {
    return lat.toFixed(GEO_ROUND_DECIMALS) + ',' + lng.toFixed(GEO_ROUND_DECIMALS);
}

function geoGetCached(lat, lng) {
    const k = geoKey(lat, lng);
    if (_geoMem.has(k)) return _geoMem.get(k);
    if (_geoStore[k])   return _geoStore[k];
    return null;
}

function geoSetCached(lat, lng, name) {
    if (!name || name === 'N/A' || name === 'Erro ao carregar') return;
    const k = geoKey(lat, lng);
    _geoMem.set(k, name);
    _geoStore[k] = name;
    // Persiste ocasionalmente (não a cada request, pra não gastar main thread)
    try { localStorage.setItem(GEO_CACHE_KEY, JSON.stringify(_geoStore)); } catch (e) {}
}

async function fetchLocationName(lat, lng) {
    // 1) Cache local antes de tudo
    const cached = geoGetCached(lat, lng);
    if (cached) return cached;

    // 2) Backend com timeout
    const ctrl = new AbortController();
    const to = setTimeout(() => ctrl.abort(), GEO_TIMEOUT_MS);
    try {
        const url = GEO_BASE_URL + '?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng);
        const resp = await fetch(url, { method: 'GET', signal: ctrl.signal, credentials: 'same-origin' });
        clearTimeout(to);
        if (!resp.ok) return null;
        const text = (await resp.text()).trim();
        if (!text || text === 'N/A' || text === 'Erro ao carregar') return null;
        geoSetCached(lat, lng, text);
        return text;
    } catch (e) {
        clearTimeout(to);
        return null; // timeout ou erro — cai no fallback (coordenadas)
    }
}

/**
 * Consulta o endereco de UMA resposta, sob demanda (clique no botao).
 *
 * A geocodificacao reversa automatica foi removida: ela disparava uma
 * requisicao por linha ao abrir a tela, o que deixava a navegacao lenta.
 * As coordenadas ja aparecem direto do banco; o endereco so e buscado
 * quando o usuario pede.
 */
async function lookupAddress(btn) {
    const box = btn.closest(".location-info");
    if (!box) return;

    const lat = parseFloat(box.getAttribute("data-lat"));
    const lng = parseFloat(box.getAttribute("data-lng"));
    const out = box.querySelector(".location-name");

    if (!isFinite(lat) || !isFinite(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
        btn.outerHTML = '<small class="text-danger">Coordenadas invalidas</small>';
        return;
    }

    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-1"></i>Buscando...';

    const name = await fetchLocationName(lat, lng);

    if (name) {
        out.textContent = name;
        out.style.setProperty("display", "block", "important");
        btn.remove();               // resolvido: botao nao e mais necessario
    } else {
        btn.disabled = false;
        btn.innerHTML = original;
        btn.classList.add("text-danger");
        btn.title = "Nao foi possivel obter o endereco. Clique para tentar de novo.";
    }
}

function showPhoto(photoUrl) {
    document.getElementById('photoImage').src = photoUrl;
    new bootstrap.Modal(document.getElementById('photoModal')).show();
}

// Função para exportar respostas (botão Exportar)
function exportarRespostas(e) {
    e.preventDefault();

    // Verificar se um questionário está selecionado no filtro
    var questionnaireSelect = document.querySelector('select[name="questionnaire_id"]');
    var questionnaireId = questionnaireSelect ? questionnaireSelect.value : '';

    if (!questionnaireId) {
        alert('Por favor, selecione um questionário no filtro antes de exportar.');
        // Abrir o painel de filtros se estiver fechado
        var filtersCollapse = document.getElementById('filtersCollapse');
        if (filtersCollapse && !filtersCollapse.classList.contains('show')) {
            new bootstrap.Collapse(filtersCollapse, { toggle: true });
        }
        if (questionnaireSelect) questionnaireSelect.focus();
        return;
    }

    // Montar URL com os filtros atuais
    var params = new URLSearchParams();
    params.set('questionnaire_id', questionnaireId);

    var appliedBy = document.querySelector('select[name="applied_by"]');
    if (appliedBy && appliedBy.value) params.set('applied_by', appliedBy.value);

    var dateFrom = document.querySelector('input[name="date_from"]');
    if (dateFrom && dateFrom.value) params.set('date_from', dateFrom.value);

    var dateTo = document.querySelector('input[name="date_to"]');
    if (dateTo && dateTo.value) params.set('date_to', dateTo.value);

    window.location.href = '<?= base_url("responses/export") ?>?' + params.toString();
}

// NOVA FUNÇÃO: Validação do formulário de exportação
function validateExportForm() {
    const questionnaireSelect = document.getElementById('export_questionnaire_id');
    
    if (!questionnaireSelect.value) {
        alert('Por favor, selecione um questionário para exportar.');
        questionnaireSelect.focus();
        return false;
    }
    
    return true;
}

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // A geocodificacao reversa NAO roda mais automaticamente: as
    // coordenadas ja vem do banco e o endereco e buscado sob demanda
    // pelo botao "Ver endereco" de cada linha (ver lookupAddress).

    // NOVO: Adicionar validação ao formulário de exportação
    const exportForm = document.getElementById('exportRawDataForm');
    if (exportForm) {
        exportForm.addEventListener('submit', function(e) {
            if (!validateExportForm()) {
                e.preventDefault();
                return false;
            }
            
            // Mostrar loading no botão
            const submitBtn = exportForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Exportando...';
            submitBtn.disabled = true;
            
            // Restaurar botão após alguns segundos (caso não haja redirect)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });
    }
});
</script>