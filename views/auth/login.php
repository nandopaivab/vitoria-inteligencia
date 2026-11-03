<?php
/**
 * VITÓRIA INTELIGÊNCIA — Tela de Autenticação Segura
 */
$flashError = flash('error');
$flashSuccess = flash('success');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Seguro — Vitória Inteligência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #101B33 0%, #1e3a8a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
        }
        .login-header {
            background-color: #101B33;
            color: #ffffff;
            padding: 2rem 2rem 1.5rem;
            border-bottom: 4px solid #2563EB;
        }
    </style>
</head>
<body class="p-3">

<div class="login-card">
    <div class="login-header text-center">
        <div class="d-inline-flex align-items-center justify-content-center rounded-3 p-3 mb-3" style="background-color: #2563EB;">
            <i class="bi bi-compass-fill fs-2 text-white"></i>
        </div>
        <h4 class="fw-bold mb-1">VITÓRIA INTELIGÊNCIA</h4>
        <p class="small text-white-50 mb-0">Inteligência Territorial, Gestão Comunitária e Comunicação Digital</p>
    </div>

    <div class="p-4 p-md-4">
        <?php if ($flashError): ?>
            <div class="alert alert-danger small py-2">
                <i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($flashError) ?>
            </div>
        <?php endif; ?>
        <?php if ($flashSuccess): ?>
            <div class="alert alert-success small py-2">
                <i class="bi bi-check-circle-fill me-1"></i><?= e($flashSuccess) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?route=login_submit">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label fw-semibold small">E-mail Institucional</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="admin@fernandopaiva.com.br" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold small">Senha de Acesso</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" style="background-color: #2563EB;">
                <i class="bi bi-shield-lock me-2"></i>Entrar no Painel Estratégico
            </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center small text-muted">
            <i class="bi bi-shield-check text-success me-1"></i>Sessão protegida com CSRF, PDO e conformidade LGPD.<br>
            Município de Vitória / ES
        </div>
    </div>
</div>

</body>
</html>
