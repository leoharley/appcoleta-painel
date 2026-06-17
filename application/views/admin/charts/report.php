<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <style>
        :root {
            --navy: #23345F;
            --navy-dark: #1a2847;
            --orange: #F58220;
            --gray-1: #f5f6f8;
            --gray-2: #c9ced6;
            --text: #2a2e3a;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #e9ecef; font-family: 'Inter', sans-serif; color: var(--text); }

        /* Garante que as cores de fundo apareçam no PDF/print */
        .cover, .metho, .institutional .swatch, .accent-bar, .underline, .toolbar,
        .stat-card, .freq-table th, .slide[style*="gradient"] {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        /* Toolbar (não aparece na impressão) */
        .toolbar {
            position: sticky; top: 0; z-index: 100;
            background: var(--navy); color: white;
            display: flex; align-items: center; gap: 12px;
            padding: 10px 18px; box-shadow: 0 2px 10px rgba(0,0,0,.15);
        }
        .toolbar h1 { font-size: 16px; margin: 0; font-weight: 500; }
        .toolbar .spacer { flex: 1; }
        .toolbar .btn {
            background: var(--orange); color: white; border: 0;
            padding: 8px 14px; border-radius: 4px; cursor: pointer; font-size: 14px;
            text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        }
        .toolbar .btn:hover { filter: brightness(1.1); }
        .toolbar .btn.secondary { background: transparent; border: 1px solid rgba(255,255,255,.3); }

        /* Cada slide = uma página A4 paisagem */
        .slide {
            width: 297mm; min-height: 210mm;
            margin: 18px auto; padding: 18mm 22mm;
            background: white; position: relative;
            box-shadow: 0 4px 24px rgba(0,0,0,.12);
            page-break-after: always; break-after: page;
            display: flex; flex-direction: column;
        }
        .slide:last-child { page-break-after: auto; }

        .slide h2 {
            font-family: 'Oswald', sans-serif;
            font-weight: 700; color: var(--navy);
            font-size: 38px; line-height: 1.05; margin: 0 0 4px 0;
            letter-spacing: 0.5px;
        }
        .slide h2 small {
            display: block; font-size: 16px; color: #6a7280;
            font-family: 'Inter', sans-serif; font-weight: 500;
            letter-spacing: 0; margin-bottom: 2px;
        }
        .slide h2.orange { color: var(--orange); }
        .slide .underline {
            height: 1px; background: #cfd3dc; margin: 10px 0 24px 0;
        }
        .slide .underline.orange { background: var(--orange); height: 2px; width: 110px; margin-bottom: 26px; }

        .slide p.subtitle {
            font-size: 14px; color: #8b8f99; margin: 0 0 18px 0;
        }

        /* CAPA */
        .cover {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-dark) 100%);
            color: white; padding: 36mm 30mm;
        }
        .cover .top-band {
            font-family: 'Inter', sans-serif; font-size: 14px;
            letter-spacing: 6px; color: #c9d6e8; margin-bottom: 14px;
        }
        .cover h1 {
            font-family: 'Oswald', sans-serif; font-weight: 700;
            font-size: 56px; line-height: 1.05; margin: 0 0 24px 0;
        }
        .cover .accent-bar { height: 8px; width: 100%; background: var(--orange); margin-top: 26mm; }
        .cover .logo-area {
            position: absolute; top: 20mm; right: 22mm;
            background: white; border-radius: 12px;
            padding: 8px 18px;
        }
        .cover .logo-area img { height: 36px; display: block; }

        /* CONTRACAPA institucional */
        .institutional {
            display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;
            padding-top: 26mm;
        }
        .institutional .swatch {
            background: var(--orange); height: 80px; width: 60%; margin-top: 10px;
        }
        .institutional .text p { font-size: 15px; line-height: 1.55; color: #2a2e3a; }

        /* Metodologia */
        .metho {
            background: var(--navy); color: white;
            min-height: 210mm; padding: 30mm;
            display: flex; flex-direction: column; justify-content: center;
        }
        .metho h2 { color: white !important; font-size: 44px; }
        .metho h2 + .underline { background: var(--orange); width: 140px; height: 3px; }
        .metho p { font-size: 18px; line-height: 1.55; color: #d4dbe6; max-width: 580px; }

        /* Demografia / Stats */
        .stats-row {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px;
        }
        .stat-card {
            border: 1px solid #e1e4ea; border-radius: 8px; padding: 16px; text-align: center;
        }
        .stat-card .num { font-family: 'Oswald', sans-serif; font-size: 30px; color: var(--navy); font-weight: 700; }
        .stat-card .lbl { font-size: 12px; color: #6a7280; margin-top: 2px; }

        /* Gráficos */
        .chart-wrap {
            flex: 1; display: flex; align-items: flex-start; justify-content: center;
            position: relative; padding-top: 4px;
        }
        .chart-wrap.with-table { display: grid; grid-template-columns: 1.5fr 1fr; gap: 28px; align-items: start; }
        .chart-host { width: 100%; position: relative; }
        .chart-host canvas { display: block; width: 100% !important; }

        .freq-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .freq-table th, .freq-table td { padding: 6px 10px; border-bottom: 1px solid #eef0f3; text-align: left; }
        .freq-table th { color: var(--navy); font-weight: 600; }
        .freq-table td.num { text-align: right; font-variant-numeric: tabular-nums; }

        .footer-info {
            position: absolute; bottom: 12mm; left: 22mm; right: 22mm;
            display: flex; justify-content: space-between;
            font-size: 11px; color: #99a0ad; border-top: 1px solid #eef0f3; padding-top: 8px;
        }

        /* No-data placeholder */
        .nodata {
            text-align: center; color: #9aa0ab; padding: 60px 0;
            font-style: italic;
        }

        /* Print */
        @page { size: A4 landscape; margin: 0; }
        @media print {
            /* Força impressão das cores de fundo em TODOS os elementos */
            *, *::before, *::after {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            html, body { background: white !important; }
            .toolbar { display: none !important; }
            .slide {
                margin: 0; box-shadow: none; width: 100%; min-height: 100vh;
                page-break-after: always; break-after: page;
            }
            .slide:last-child { page-break-after: auto; }

            /* Reforça fundos sólidos para o caso de o navegador
               descartar gradientes em modo de impressão */
            .cover { background: var(--navy) !important; }
            .metho { background: var(--navy) !important; }
        }
    </style>
</head>
<body>

<!-- Toolbar (oculta na impressão) -->
<div class="toolbar">
    <i class="fas fa-chart-pie"></i>
    <h1><?= htmlspecialchars($questionnaire->title) ?></h1>
    <span class="spacer"></span>
    <form method="get" class="d-inline-flex" style="display:inline-flex; gap:8px; align-items:center;">
        <input type="date" name="from" value="<?= htmlspecialchars((string)$filters['from']) ?>"
               title="De" style="border:0; border-radius:4px; padding:6px 8px; font-size:13px;">
        <input type="date" name="to"   value="<?= htmlspecialchars((string)$filters['to']) ?>"
               title="Até" style="border:0; border-radius:4px; padding:6px 8px; font-size:13px;">
        <select name="applied_by" style="border:0; border-radius:4px; padding:6px 8px; font-size:13px; max-width:200px;">
            <option value="">Todos os aplicadores</option>
            <?php foreach ($applicators as $ap): ?>
                <option value="<?= (int)$ap->id ?>" <?= (int)$filters['applied_by'] === (int)$ap->id ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$ap->full_name) ?> (<?= (int)$ap->total ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn secondary" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
    </form>
    <a href="<?= base_url('charts') ?>" class="btn secondary">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
    <button class="btn" onclick="printReport()" title="Ao imprimir, marque 'Mais configurações → Gráficos de plano de fundo' para preservar as cores">
        <i class="fas fa-print"></i> Imprimir / Salvar PDF
    </button>
</div>

<script>
function printReport() {
    // Aguarda os gráficos terminarem de renderizar antes de chamar print
    setTimeout(function() {
        alert('Dica: no Chrome/Edge, abra "Mais configurações" e marque "Gráficos em segundo plano" para imprimir as cores de fundo.');
        window.print();
    }, 100);
}
</script>

<!-- ============== SLIDE 1: CAPA ============== -->
<section class="slide cover">
    <div class="logo-area">
        <img src="<?= base_url('assets/img/logo_alveslima.png') ?>" alt="Instituto Alves Lima">
    </div>
    <div class="top-band"><?= strtoupper(htmlspecialchars($project ? $project->name : 'PESQUISA')) ?></div>
    <h1>RELATÓRIO:<br><?= htmlspecialchars($questionnaire->title) ?></h1>
    <div class="accent-bar"></div>
</section>

<!-- ============== SLIDE 2: APRESENTAÇÃO ============== -->
<section class="slide">
    <h2>Apresentação</h2>
    <div class="underline orange"></div>
    <div class="institutional">
        <div><div class="swatch"></div></div>
        <div class="text">
            <p>
                O <strong>Instituto Alves Lima</strong> surgiu, em Montes Claros - MG, a partir
                da união de um grupo de pesquisadores, especializados em diferentes áreas do
                conhecimento, e que possuem muita experiência em pesquisas de opinião pública.
            </p>
            <p>
                Nossa missão é fornecer dados imparciais que auxiliem o processo democrático,
                promovendo a transparência, a participação cidadã e a melhoria das políticas
                públicas.
            </p>
        </div>
    </div>
    <div class="footer-info">
        <span>Instituto Alves Lima</span>
        <span>Página 2</span>
    </div>
</section>

<!-- ============== SLIDE 3: METODOLOGIA ============== -->
<?php
$first_at = $range && $range->first_at ? date('d/m/Y', strtotime($range->first_at)) : null;
$last_at  = $range && $range->last_at  ? date('d/m/Y', strtotime($range->last_at))  : null;
$periodo  = ($first_at && $last_at) ? ($first_at === $last_at ? "no dia {$first_at}" : "entre {$first_at} e {$last_at}") : '';
?>
<section class="slide metho">
    <h2>Metodologia</h2>
    <div class="underline"></div>
    <p>
        Foram entrevistadas <strong><?= number_format($total_responses, 0, ',', '.') ?> pessoas</strong>
        respondentes do questionário <em><?= htmlspecialchars($questionnaire->title) ?></em>
        <?= $periodo ? ', coletadas ' . $periodo : '' ?>.
        Os dados apresentados a seguir refletem a coleta realizada pelos aplicadores
        cadastrados na plataforma.
    </p>
    <?php if (!empty($filters['from']) || !empty($filters['to']) || !empty($filters['applied_by'])): ?>
    <p style="margin-top:16px; font-size:14px; color:#a8b3c5;">
        Filtros aplicados:
        <?= !empty($filters['from']) || !empty($filters['to']) ? ' período ' . htmlspecialchars($filters['from'] ?: '—') . ' a ' . htmlspecialchars($filters['to'] ?: '—') . '; ' : '' ?>
        <?php
        if (!empty($filters['applied_by'])) {
            foreach ($applicators as $a) {
                if ((int)$a->id === (int)$filters['applied_by']) {
                    echo 'aplicador ' . htmlspecialchars($a->full_name);
                    break;
                }
            }
        }
        ?>
    </p>
    <?php endif; ?>
</section>

<!-- ============== SLIDE 4: VISÃO GERAL ============== -->
<section class="slide">
    <h2>Visão Geral</h2>
    <div class="underline orange"></div>
    <p class="subtitle">Resumo da coleta de dados deste questionário</p>

    <div class="stats-row" style="grid-template-columns: repeat(4, 1fr); margin-top: 18px;">
        <div class="stat-card">
            <div class="num"><?= number_format($total_responses, 0, ',', '.') ?></div>
            <div class="lbl">Respostas totais</div>
        </div>
        <div class="stat-card">
            <div class="num"><?= count($questions) ?></div>
            <div class="lbl">Perguntas no questionário</div>
        </div>
        <div class="stat-card">
            <div class="num"><?= (int)($range->applicators_count ?? 0) ?></div>
            <div class="lbl">Aplicadores ativos</div>
        </div>
        <div class="stat-card">
            <div class="num" style="font-size:18px;">
                <?= $first_at ? $first_at : '—' ?> <br>
                <small style="font-size:13px; font-weight:500;">→ <?= $last_at ? $last_at : '—' ?></small>
            </div>
            <div class="lbl">Período coberto</div>
        </div>
    </div>

    <?php if (!empty($applicators)): ?>
    <div style="margin-top: 22px;">
        <h3 style="font-family:'Oswald', sans-serif; color: var(--navy); font-size:20px; margin: 0 0 10px 0;">
            Top aplicadores
        </h3>
        <table class="freq-table" style="max-width: 520px;">
            <thead>
                <tr><th>Aplicador</th><th class="num">Respostas</th><th class="num">%</th></tr>
            </thead>
            <tbody>
                <?php $cnt = 0; foreach ($applicators as $ap): if ($cnt++ >= 8) break; ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$ap->full_name) ?></td>
                        <td class="num"><?= (int)$ap->total ?></td>
                        <td class="num"><?= $total_responses ? round(($ap->total / $total_responses) * 100, 1) : 0 ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="footer-info">
        <span><?= htmlspecialchars($questionnaire->title) ?></span>
        <span>Página 4</span>
    </div>
</section>

<!-- ============== UM SLIDE POR PERGUNTA ============== -->
<?php
$page_num = 5;
foreach ($questions as $idx => $q):
    $orange = ($idx % 3 === 0);
?>
<section class="slide" data-question-id="<?= (int)$q->id ?>">
    <h2 class="<?= $orange ? 'orange' : '' ?>">
        <small>Pergunta <?= $idx + 1 ?></small>
        <?= htmlspecialchars(mb_strimwidth($q->question_text, 0, 90, '…')) ?>
    </h2>
    <div class="underline <?= $orange ? 'orange' : '' ?>"></div>
    <p class="subtitle">Tipo: <code><?= htmlspecialchars($q->question_type) ?></code></p>

    <div class="chart-wrap with-table" id="wrap-<?= (int)$q->id ?>">
        <div class="chart-host" id="host-<?= (int)$q->id ?>">
            <canvas id="chart-<?= (int)$q->id ?>"></canvas>
        </div>
        <div id="table-<?= (int)$q->id ?>">
            <div class="nodata">Carregando…</div>
        </div>
    </div>

    <div class="footer-info">
        <span><?= htmlspecialchars($questionnaire->title) ?></span>
        <span>Página <?= $page_num++ ?></span>
    </div>
</section>
<?php endforeach; ?>

<!-- ============== ÚLTIMO SLIDE: CONTATO ============== -->
<section class="slide" style="background: linear-gradient(135deg, var(--navy) 0%, var(--navy-dark) 100%); color:white;">
    <h2 style="color:white;">Muito Obrigado!</h2>
    <div class="underline orange"></div>
    <p style="max-width:600px; line-height:1.6; color:#d4dbe6;">
        Esperamos que este relatório seja determinante para o seu sucesso.
    </p>
    <div style="margin-top:30mm; display:grid; grid-template-columns: 1fr 1fr; gap:24px; max-width:780px;">
        <div>
            <div style="font-size:11px; letter-spacing:4px; color:#a8b3c5;">ENDEREÇO</div>
            <div style="font-size:14px; margin-top:4px;">Rua Tiradentes, 765<br>Sala 106 - Centro<br>Montes Claros - Minas Gerais</div>
        </div>
        <div>
            <div style="font-size:11px; letter-spacing:4px; color:#a8b3c5;">CONTATO</div>
            <div style="font-size:14px; margin-top:4px;">institutoalveslima@gmail.com<br>(38) 99958-8456</div>
        </div>
    </div>
</section>

<script>
(function() {
    const baseUrl = '<?= base_url() ?>';
    const qid = <?= (int)$questionnaire->id ?>;
    const filters = {
        from:       '<?= htmlspecialchars((string)$filters['from'], ENT_QUOTES) ?>',
        to:         '<?= htmlspecialchars((string)$filters['to'],   ENT_QUOTES) ?>',
        applied_by: '<?= htmlspecialchars((string)$filters['applied_by'], ENT_QUOTES) ?>',
    };

    const NAVY = '#23345F';
    const ORANGE = '#F58220';
    const GRAY = '#c9ced6';
    const PALETTE = [NAVY, ORANGE, '#5b6b8c', '#f9a85b', '#3f8efc', '#7b8a9e', '#d56b1a', '#9aa2b4', '#26568f', '#ffba6a'];

    // Plugin global de datalabels (instalado no <head>)
    if (window.ChartDataLabels) Chart.register(window.ChartDataLabels);

    function fmtPct(v, total) {
        if (!total) return '';
        return ((v / total) * 100).toFixed(1) + '%';
    }

    function renderChart(question_id, data) {
        const wrap = document.getElementById('wrap-' + question_id);
        const host = document.getElementById('host-' + question_id);
        const canvas = document.getElementById('chart-' + question_id);
        const tableEl = document.getElementById('table-' + question_id);

        if (!data.labels || data.labels.length === 0 || data.total === 0) {
            wrap.innerHTML = '<div class="nodata">Sem respostas para esta pergunta.</div>';
            return;
        }

        // Tabela à direita
        let html = '<table class="freq-table"><thead><tr><th>Opção</th><th class="num">Qtd</th><th class="num">%</th></tr></thead><tbody>';
        for (let i = 0; i < data.labels.length; i++) {
            html += '<tr><td>' + escapeHtml(String(data.labels[i])) + '</td>' +
                    '<td class="num">' + data.values[i] + '</td>' +
                    '<td class="num">' + fmtPct(data.values[i], data.total) + '</td></tr>';
        }
        html += '</tbody></table>';
        if (data.stats) {
            html += '<div style="margin-top:10px; font-size:12px; color:#6a7280;">' +
                    'min ' + data.stats.min + ' · média ' + data.stats.avg + ' · max ' + data.stats.max + '</div>';
        }
        tableEl.innerHTML = html;

        const isDonut = data.chart_type === 'doughnut';
        const n = data.labels.length;

        // Altura do canvas proporcional ao número de itens
        //  - donut: quadrado de 360–460px
        //  - barra horizontal: 34px por item + 60px de padding (min 320px)
        let canvasHeight;
        if (isDonut) {
            canvasHeight = Math.min(460, Math.max(360, n * 32));
        } else {
            canvasHeight = Math.max(320, n * 34 + 60);
        }
        host.style.height = canvasHeight + 'px';

        // Tipografia adaptativa
        const labelFontSize = n > 10 ? 11 : (n > 6 ? 12 : 13);
        const tickFontSize  = n > 12 ? 11 : 12;
        // Trunca labels muito longos para o eixo Y
        const truncate = (s, max) => {
            s = String(s);
            return s.length > max ? s.slice(0, max - 1) + '…' : s;
        };

        const cfg = {
            type: isDonut ? 'doughnut' : 'bar',
            data: {
                labels: data.labels.map(l => isDonut ? l : truncate(l, 28)),
                datasets: [{
                    data: data.values,
                    backgroundColor: isDonut
                        ? data.labels.map((_, i) => PALETTE[i % PALETTE.length])
                        : data.labels.map((_, i) => i === 0 ? NAVY : (i === 1 ? ORANGE : PALETTE[i % PALETTE.length])),
                    borderColor: 'white',
                    borderWidth: isDonut ? 3 : 0,
                    barThickness: isDonut ? undefined : 'flex',
                    maxBarThickness: isDonut ? undefined : 30,
                    categoryPercentage: 0.85,
                    barPercentage: 0.9,
                }]
            },
            options: {
                indexAxis: isDonut ? 'x' : 'y',
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { right: isDonut ? 0 : 80 } }, // espaço para datalabel à direita
                plugins: {
                    legend: {
                        display: isDonut,
                        position: 'bottom',
                        labels: {
                            font: { family: 'Inter', size: 11 },
                            boxWidth: 14, padding: 10,
                            generateLabels: function(chart) {
                                const ds = chart.data.datasets[0];
                                const labels = chart.data.labels || [];
                                const total = ds.data.reduce((a, b) => a + (Number(b) || 0), 0);
                                return labels.map((lab, i) => {
                                    const v = ds.data[i] || 0;
                                    const pct = total > 0 ? ((v / total) * 100).toFixed(1) : '0.0';
                                    return {
                                        text: lab + ' — ' + v + ' (' + pct + '%)',
                                        fillStyle: ds.backgroundColor[i],
                                        strokeStyle: 'white',
                                        lineWidth: 1,
                                        hidden: false,
                                        index: i
                                    };
                                });
                            }
                        }
                    },
                    datalabels: {
                        color: isDonut ? 'white' : NAVY,
                        font: { family: 'Inter', weight: '700', size: labelFontSize },
                        anchor: isDonut ? 'center' : 'end',
                        align: isDonut ? 'center' : 'end',
                        clamp: true,
                        textStrokeColor: isDonut ? 'rgba(0,0,0,0.35)' : undefined,
                        textStrokeWidth: isDonut ? 3 : 0,
                        formatter: (v) => v + ' (' + fmtPct(v, data.total) + ')',
                        display: ctx => {
                            const v = ctx.dataset.data[ctx.dataIndex] || 0;
                            if (v <= 0) return false;
                            if (!isDonut) return true;
                            // Donut: só mostra se a fatia for >= 4% (caso contrário a info fica na legenda)
                            return data.total > 0 && (v / data.total) >= 0.04;
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const val = (typeof ctx.parsed === 'object') ? (ctx.parsed.x ?? ctx.parsed.y) : ctx.parsed;
                                return val + ' (' + fmtPct(val, data.total) + ')';
                            }
                        }
                    }
                },
                scales: isDonut ? {} : {
                    x: {
                        beginAtZero: true,
                        ticks: { font: { family: 'Inter', size: tickFontSize }, precision: 0 },
                        grid: { color: '#eef0f3' }
                    },
                    y: {
                        ticks: { font: { family: 'Inter', size: tickFontSize }, autoSkip: false },
                        grid: { display: false }
                    }
                }
            }
        };
        new Chart(canvas, cfg);
    }

    function escapeHtml(s) {
        return s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function loadAll() {
        const slides = document.querySelectorAll('section.slide[data-question-id]');
        slides.forEach(s => {
            const id = s.getAttribute('data-question-id');
            const url = new URL(baseUrl + 'charts/chart_data/' + qid + '/' + id);
            Object.keys(filters).forEach(k => { if (filters[k]) url.searchParams.set(k, filters[k]); });
            fetch(url.toString(), { credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => {
                    if (d.success) renderChart(id, d);
                    else document.getElementById('wrap-' + id).innerHTML = '<div class="nodata">' + (d.message || 'Erro ao carregar.') + '</div>';
                })
                .catch(err => {
                    document.getElementById('wrap-' + id).innerHTML = '<div class="nodata">Erro: ' + err.message + '</div>';
                });
        });
    }

    if (document.readyState !== 'loading') loadAll();
    else document.addEventListener('DOMContentLoaded', loadAll);
})();
</script>

</body>
</html>
