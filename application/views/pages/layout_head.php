<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - AppColeta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --navy: #23345F;
            --navy-dark: #1a2847;
            --orange: #F58220;
            --text: #2a2e3a;
            --muted: #6c757d;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background: #f4f6fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ---------- HERO ---------- */
        .page-hero {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-dark) 100%);
            color: white;
            padding: 3rem 1.5rem 6rem 1.5rem;
            position: relative;
            overflow: hidden;
        }
        .page-hero::before {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: rgba(245,130,32,.08);
            top: -80px; right: -80px;
        }
        .page-hero .container { position: relative; z-index: 2; }
        .page-hero h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: .5rem;
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .page-hero h1 i { color: var(--orange); }
        .page-hero p { opacity: .9; max-width: 640px; }

        /* ---------- NAV ---------- */
        .top-nav {
            padding: 1rem 1.5rem;
            background: rgba(0,0,0,.15);
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .top-nav .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .top-nav .brand {
            color: white;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .top-nav .brand i { color: var(--orange); }
        .top-nav ul {
            list-style: none;
            display: flex;
            gap: 1.5rem;
            margin: 0;
        }
        .top-nav ul a {
            color: rgba(255,255,255,.85);
            text-decoration: none;
            font-size: .9rem;
            transition: color .15s;
        }
        .top-nav ul a:hover, .top-nav ul a.active {
            color: var(--orange);
        }

        /* ---------- MAIN CONTENT ---------- */
        main.page-body {
            flex: 1;
            margin-top: -4rem;
            padding-bottom: 3rem;
        }
        .content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 24px rgba(35,52,95,.10);
            padding: 2.5rem;
        }
        .content-card h2 {
            color: var(--navy);
            font-size: 1.4rem;
            font-weight: 700;
            margin-top: 2rem;
            margin-bottom: 1rem;
            padding-bottom: .5rem;
            border-bottom: 2px solid var(--orange);
            display: inline-block;
        }
        .content-card h2:first-child { margin-top: 0; }
        .content-card h3 {
            color: var(--navy);
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: .75rem;
        }
        .content-card p { margin-bottom: 1rem; line-height: 1.65; }
        .content-card ul, .content-card ol { margin: 0 0 1rem 1.5rem; }
        .content-card li { margin-bottom: .35rem; line-height: 1.55; }
        .content-card .muted-note {
            color: var(--muted);
            font-size: .875rem;
            font-style: italic;
        }
        .info-box {
            background: #f8fafc;
            border-left: 4px solid var(--orange);
            padding: 1rem 1.25rem;
            border-radius: 6px;
            margin: 1rem 0;
        }
        .contact-card {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
            border: 1px solid #dfe4ec;
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: .75rem;
        }
        .contact-card .icon-box {
            flex-shrink: 0;
            width: 48px; height: 48px;
            border-radius: 10px;
            background: var(--navy);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
        }
        .contact-card .icon-box.orange { background: var(--orange); }
        .contact-card strong { color: var(--navy); display: block; }
        .contact-card a { color: var(--text); text-decoration: none; }
        .contact-card a:hover { color: var(--orange); }

        /* ---------- FAQ ---------- */
        .faq-item {
            border-bottom: 1px solid #eef0f4;
            padding: 1rem 0;
        }
        .faq-item:last-child { border-bottom: 0; }
        .faq-item summary {
            cursor: pointer;
            font-weight: 600;
            color: var(--navy);
            padding: .25rem 0;
            list-style: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .faq-item summary::-webkit-details-marker { display: none; }
        .faq-item summary::after {
            content: '\002B';
            color: var(--orange);
            font-size: 1.4rem;
            font-weight: 300;
            transition: transform .2s;
        }
        .faq-item[open] summary::after { content: '\2212'; }
        .faq-item .faq-body {
            padding-top: .75rem;
            color: var(--text);
            line-height: 1.6;
        }

        /* ---------- FOOTER ---------- */
        footer.page-footer {
            background: var(--navy-dark);
            color: rgba(255,255,255,.8);
            padding: 2rem 1.5rem;
            font-size: .875rem;
        }
        footer.page-footer .container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }
        footer.page-footer a {
            color: rgba(255,255,255,.9);
            text-decoration: none;
            margin-left: 1.25rem;
        }
        footer.page-footer a:hover { color: var(--orange); }

        @media (max-width: 640px) {
            .page-hero h1 { font-size: 1.5rem; }
            .content-card { padding: 1.5rem; }
        }
    </style>
</head>
<body>

<div class="page-hero">
    <nav class="top-nav">
        <div class="container">
            <a href="<?= base_url() ?>" class="brand">
                <i class="fas fa-mobile-alt"></i>
                AppColeta
            </a>
            <ul>
                <li><a href="<?= base_url('privacidade') ?>" class="<?= $active === 'privacidade' ? 'active' : '' ?>">Privacidade</a></li>
                <li><a href="<?= base_url('suporte') ?>" class="<?= $active === 'suporte' ? 'active' : '' ?>">Suporte</a></li>
                <li><a href="<?= base_url('exclusao-de-conta') ?>" class="<?= $active === 'exclusao-de-conta' ? 'active' : '' ?>">Excluir conta</a></li>
                <li><a href="<?= base_url('auth/login') ?>">Entrar</a></li>
            </ul>
        </div>
    </nav>
    <div class="container">
