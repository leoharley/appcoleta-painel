        <h1><i class="fas fa-user-slash"></i> Exclusão de Conta</h1>
        <p>Solicite a exclusão da sua conta e dos seus dados pessoais no AppColeta.</p>
    </div>
</div>

<main class="page-body">
    <div class="container">
        <div class="content-card">

            <div class="info-box">
                <strong><i class="fas fa-info-circle me-1"></i> Antes de continuar</strong><br>
                A exclusão de conta é <strong>definitiva</strong>. Você perderá o acesso ao aplicativo e
                ao painel. Se você é aplicador de campo em algum projeto ativo, avise a coordenação da
                pesquisa antes de solicitar a exclusão.
            </div>

            <h2>1. O que será excluído</h2>
            <ul>
                <li>Seu <strong>nome de usuário</strong>, <strong>senha</strong> e <strong>e-mail</strong> de acesso</li>
                <li>Seu <strong>nome completo</strong> vinculado ao cadastro</li>
                <li>Registros de <strong>login e atividade</strong> associados ao seu usuário</li>
                <li>Qualquer outro <strong>dado pessoal identificável</strong> que esteja no seu perfil</li>
            </ul>

            <h2>2. O que será preservado (e por quê)</h2>
            <p>
                Nos termos da LGPD (Art. 16), alguns registros precisam ser mantidos para cumprimento
                de obrigações legais, contratuais e de auditoria da pesquisa contratada:
            </p>
            <ul>
                <li>As <strong>respostas de questionários já coletados</strong> permanecem no banco de
                    dados do projeto, mas <strong>desvinculadas de você</strong> — o campo "aplicador"
                    passa a ficar vazio ou identificado apenas como "aplicador removido".</li>
                <li><strong>Coordenadas de GPS, fotos e áudios</strong> das entrevistas continuam
                    associados às respostas (não a você), pois pertencem ao acervo do projeto de
                    pesquisa contratado pelo cliente.</li>
                <li>Registros contábeis e fiscais (quando aplicáveis) são mantidos pelo prazo legal.</li>
            </ul>

            <h2>3. Como solicitar a exclusão</h2>
            <p>
                Envie a solicitação por um dos canais abaixo. Precisamos confirmar sua identidade antes
                de executar a exclusão — por isso o pedido precisa vir <strong>do e-mail cadastrado</strong>
                ou <strong>do WhatsApp associado à sua conta</strong>.
            </p>

            <div class="contact-card">
                <div class="icon-box orange"><i class="fab fa-whatsapp"></i></div>
                <div>
                    <strong>WhatsApp (mais rápido)</strong>
                    <a href="https://wa.me/5538999588456?text=<?= rawurlencode('Olá! Quero solicitar a EXCLUSÃO da minha conta no AppColeta. Meu nome de usuário é: [preencher]') ?>" target="_blank" rel="noopener">
                        (38) 99958-8456
                    </a>
                </div>
            </div>
            <div class="contact-card">
                <div class="icon-box"><i class="fas fa-envelope"></i></div>
                <div>
                    <strong>E-mail</strong>
                    <a href="mailto:institutoalveslima@gmail.com?subject=<?= rawurlencode('Solicitação de exclusão de conta - AppColeta') ?>&body=<?= rawurlencode("Olá,\n\nSolicito a exclusão da minha conta e dos meus dados pessoais no AppColeta, nos termos da LGPD.\n\nMeu nome de usuário no sistema: \nMeu nome completo: \nData da solicitação: ") ?>">
                        institutoalveslima@gmail.com
                    </a>
                </div>
            </div>

            <div class="info-box" style="border-left-color:#23345F;">
                <strong>Modelo de mensagem</strong> (copie e cole se preferir):
                <pre style="background:white; padding:12px; margin-top:8px; border-radius:6px; font-size:.85rem; white-space:pre-wrap;">Olá,

Solicito a exclusão da minha conta e dos meus dados pessoais
no AppColeta, nos termos da LGPD.

- Meu nome de usuário no sistema: __________
- Meu nome completo: __________
- Data da solicitação: <?= date('d/m/Y') ?>

Estou ciente de que a exclusão é definitiva.
Obrigado.</pre>
            </div>

            <h2>4. Prazo de atendimento</h2>
            <ul>
                <li><strong>Confirmação do pedido:</strong> em até <strong>2 dias úteis</strong> respondemos
                    confirmando o recebimento e, se necessário, pedimos algum comprovante adicional para
                    validar a identidade.</li>
                <li><strong>Execução da exclusão:</strong> em até <strong>15 dias úteis</strong> após a
                    validação da identidade, conforme o Art. 18 da LGPD.</li>
                <li>Você recebe uma <strong>confirmação por escrito</strong> quando a exclusão é concluída.</li>
            </ul>

            <h2>5. Quero apenas parar de usar, sem excluir</h2>
            <p>
                Se você quer só pausar sua participação (sem apagar dados), peça à coordenação do projeto
                para <strong>desativar seu usuário</strong>. Isso bloqueia seu acesso mas mantém tudo intacto,
                caso você volte a colaborar depois.
            </p>

            <h2>6. Reversão</h2>
            <p>
                Após a exclusão ser concluída, <strong>não é possível reverter</strong>. Se você quiser voltar
                a usar o AppColeta no futuro, será necessário criar uma nova conta.
            </p>

            <h2>7. Dúvidas</h2>
            <p>
                Consulte também nossa <a href="<?= base_url('privacidade') ?>">Política de Privacidade</a>
                ou fale com o suporte pela <a href="<?= base_url('suporte') ?>">página de Suporte</a>.
            </p>

        </div>
    </div>
