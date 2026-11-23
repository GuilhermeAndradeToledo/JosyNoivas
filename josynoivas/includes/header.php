<?php

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/funcoes.php';

$categorias  = $pdo->query("SELECT catNome, catSlug FROM tblCategoria ORDER BY catOrdem")->fetchAll();
$cfg         = configuracoes($pdo);
$paginaAtual = basename($_SERVER['PHP_SELF'], '.php');
$catAtiva    = $_GET['categoria'] ?? '';
$logado      = !empty($_SESSION['usuario']);
$waAviso     = whatsappLink($cfg, 'Olá, gostaria de atendimento.');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo ?? SITE_NAME) ?> | <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;1,500&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
    <link rel="icon" href="<?= url('img/jn.png') ?>" type="image/png">
</head>
<body data-logado="<?= $logado ? '1' : '0' ?>" data-login="<?= e(url('paginas/login.php')) ?>" data-csrf="<?= e(csrfToken()) ?>" data-fav-url="<?= e(url('paginas/favoritar.php')) ?>">

<div class="barra-aviso">
    <a href="<?= e($waAviso) ?>" target="_blank" rel="noopener"><?= e($cfg['barra_aviso'] ?? 'Atendimento personalizado pelo WhatsApp') ?></a>
</div>

<header class="topo">
    <div class="topo-linha">
        <button type="button" class="menu-toggle" aria-label="Abrir menu" aria-expanded="false"><?= icone('menu', 24) ?></button>
        <a href="<?= url('index.php') ?>" class="logo"><?= SITE_NAME ?></a>

        <nav class="menu-principal" id="menu">
            <?php foreach ($categorias as $cat): ?>
                <a href="<?= url('paginas/catalogo.php?categoria=' . urlencode($cat['catSlug'])) ?>"
                   class="<?= $paginaAtual === 'catalogo' && $catAtiva === $cat['catSlug'] ? 'ativo' : '' ?>"><?= e($cat['catNome']) ?></a>
            <?php endforeach; ?>
            <a href="<?= url('paginas/blog.php') ?>" class="<?= in_array($paginaAtual, ['blog', 'post'], true) ? 'ativo' : '' ?>">Blog</a>
            <a href="<?= url('paginas/perguntas.php') ?>" class="<?= $paginaAtual === 'perguntas' ? 'ativo' : '' ?>">Perguntas</a>
            <a href="<?= url('paginas/sobre.php') ?>" class="<?= in_array($paginaAtual, ['sobre', 'contato'], true) ? 'ativo' : '' ?>">Sobre</a>
        </nav>

        <div class="icones">
            <?php if (ehAdmin($pdo)): ?>
                <a class="link-painel" href="<?= url('admin/index.php') ?>">Painel</a>
            <?php endif; ?>
            <button type="button" class="busca-abrir" aria-label="Buscar" aria-expanded="false"><?= icone('search') ?></button>
            <a href="<?= url('paginas/favoritos.php') ?>" aria-label="Meus favoritos"><?= icone('heart') ?></a>
            <a href="<?= url($logado ? 'paginas/conta.php' : 'paginas/login.php') ?>" aria-label="<?= $logado ? 'Minha conta' : 'Entrar' ?>"><?= icone('user') ?></a>
        </div>
    </div>

    <form class="busca-painel" action="<?= url('paginas/catalogo.php') ?>" method="get" role="search" hidden>
        <input type="search" name="q" placeholder="Buscar por nome, cor, modelo ou tamanho" aria-label="Buscar">
    </form>
</header>

<main class="<?= !empty($paginaCheia) ? '' : 'container' ?>">