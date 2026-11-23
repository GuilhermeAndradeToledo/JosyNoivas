<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/funcoes.php';
$voltarQs = isset($_GET['voltar']) && is_string($_GET['voltar']) ? '?voltar=' . urlencode($_GET['voltar']) : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo ?? 'Entrar') ?> | <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;1,500&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
    <link rel="icon" href="<?= url('img/jn.png') ?>" type="image/png">
</head>
<body class="auth">
<main class="auth-cartao">
    <a class="auth-logo" href="<?= url('index.php') ?>"><?= SITE_NAME ?></a>
    <?php if (!empty($abaAtiva)): ?>
        <nav class="auth-abas">
            <a class="auth-aba <?= $abaAtiva === 'entrar' ? 'ativo' : '' ?>" href="<?= e(url('paginas/login.php') . $voltarQs) ?>">Entrar</a>
            <a class="auth-aba <?= $abaAtiva === 'cadastrar' ? 'ativo' : '' ?>" href="<?= e(url('paginas/cadastro.php') . $voltarQs) ?>">Cadastrar</a>
        </nav>
    <?php endif; ?>