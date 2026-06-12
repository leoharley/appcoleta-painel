<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso - AppColeta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .login-banner {
            flex: 1;
            background: linear-gradient(135deg, #23345F 0%, #1a2847 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(232, 119, 34, 0.1) 0%, transparent 70%);
            animation: pulse 15s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .login-banner .banner-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .login-banner img {
            max-width: 280px;
            margin-bottom: 2rem;
        }

        .login-banner h1 {
            font-size: 2rem;
            font-weight: 300;
            margin-bottom: 1rem;
        }

        .login-banner p {
            opacity: 0.8;
            font-size: 1.1rem;
        }

        .feature-list {
            margin-top: 3rem;
            text-align: left;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .feature-item i {
            color: #E87722;
            margin-right: 1rem;
            font-size: 1.2rem;
        }

        .login-form-section {
            width: 450px;
            min-width: 450px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem;
            background: #f8f9fa;
        }

        .login-form-container {
            max-width: 350px;
            margin: 0 auto;
            width: 100%;
        }

        .login-form-container h5 {
            color: #23345F;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .login-form-container .subtitle {
            color: #6c757d;
            margin-bottom: 2rem;
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .form-floating .form-control {
            border: 2px solid #dee2e6;
            border-radius: 0.75rem;
            height: 58px;
            padding: 1rem 1rem;
        }

        .form-floating .form-control:focus {
            border-color: #E87722;
            box-shadow: 0 0 0 0.2rem rgba(232, 119, 34, 0.15);
        }

        .form-floating label {
            padding: 1rem;
            color: #6c757d;
        }

        .btn-access {
            background: #E87722;
            border: none;
            border-radius: 0.75rem;
            padding: 1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            font-size: 1rem;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-access:hover {
            background: #d66a1a;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(232, 119, 34, 0.35);
            color: white;
        }

        .alert {
            border-radius: 0.75rem;
            border: none;
            margin-bottom: 1.5rem;
        }

        .footer-text {
            text-align: center;
            margin-top: 2rem;
            color: #adb5bd;
            font-size: 0.85rem;
        }

        @media (max-width: 992px) {
            .login-wrapper {
                flex-direction: column;
            }

            .login-banner {
                padding: 2rem;
                min-height: auto;
            }

            .login-banner img {
                max-width: 200px;
            }

            .login-banner h1 {
                font-size: 1.5rem;
            }

            .feature-list {
                display: none;
            }

            .login-form-section {
                width: 100%;
                min-width: auto;
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-banner">
            <div class="banner-content">
                <h1>Sistema de Coleta de Dados</h1>
                <p>Gerencie suas pesquisas de campo com eficiência</p>

                <div class="feature-list">
                    <div class="feature-item">
                        <i class="fas fa-mobile-alt"></i>
                        <span>Coleta via aplicativo mobile</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-map-marked-alt"></i>
                        <span>Geolocalização integrada</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Relatórios em tempo real</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Dados seguros e protegidos</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="login-form-section">
            <div class="login-form-container">
                <div class="text-center mb-5" style="margin-top: -2rem;">
                    <img src="<?php echo img_url('logo_alveslima.png'); ?>" alt="Logo" style="max-width: 280px;">
                </div>
                <h5>Acessar Sistema</h5>
                <p class="subtitle">Informe suas credenciais para continuar</p>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <?= form_open('auth/login') ?>
                    <div class="form-floating">
                        <input type="text" class="form-control" id="username" name="username"
                               placeholder="Seu login" required value="<?= set_value('username') ?>">
                        <label for="username"><i class="fas fa-user me-2"></i>Login</label>
                    </div>
                    <?= form_error('username', '<small class="text-danger d-block mb-2">', '</small>') ?>

                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Sua senha" required>
                        <label for="password"><i class="fas fa-key me-2"></i>Senha</label>
                    </div>
                    <?= form_error('password', '<small class="text-danger d-block mb-2">', '</small>') ?>

                    <button type="submit" class="btn btn-access">
                        Acessar
                        <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                <?= form_close() ?>

                <p class="footer-text">
                    <i class="fas fa-lock me-1"></i>
                    Ambiente seguro e criptografado
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
