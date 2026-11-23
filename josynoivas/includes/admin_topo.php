<?php
require_once __DIR__ . '/admin_base.php';

$itensMenu = [
    'dashboard'  => ['Dashboard', 'index.php'],
    'vestidos'   => ['Vestidos', 'vestidos.php'],
    'interesse'  => ['Interesse', 'interesse.php'],
    'categorias' => ['Categorias', 'categorias.php'],
    'ocasioes'   => ['Ocasiões', 'ocasioes.php'],
    'posts'      => ['Posts', 'posts.php'],
    'banners'    => ['Banners', 'banners.php'],
    'avaliacoes' => ['Avaliações', 'avaliacoes.php'],
    'perguntas'  => ['Perguntas', 'perguntas.php'],
    'usuarios'   => ['Usuários', 'usuarios.php'],
    'configuracoes' => ['Configurações', 'configuracoes.php'],
];
$flash = pegarFlash();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($tituloAdmin ?? 'Painel') ?> | Admin <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;1,500&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
    <link rel="icon" href="<?= url('img/jn.png') ?>" type="image/png">
</head>

<body class="admin">
    <script>
        try {
            if (localStorage.getItem("adminEscuro") === "1") document.body.classList.add("escuro")
        } catch (e) {}
    </script>
    <aside class="admin-menu">
        <span class="admin-logo"><?= SITE_NAME ?></span>
        <?php foreach ($itensMenu as $chave => [$rotulo, $arquivo]): ?>
            <a class="item <?= ($menuAtivo ?? '') === $chave ? 'ativo' : '' ?>" href="<?= url('admin/' . $arquivo) ?>"><?= $rotulo ?></a>
        <?php endforeach; ?>
        <div class="admin-menu-rodape">
            <button type="button" class="item" data-tema>Modo escuro</button>
            <a class="item" href="<?= url('index.php') ?>" target="_blank">Ver o site</a>
            <a class="item" href="<?= url('paginas/logout.php') ?>">Sair</a>
        </div>
    </aside>
    <main class="admin-conteudo">
        <?php if ($flash): ?><p class="aviso aviso-<?= $flash[0] === 'ok' ? 'ok' : 'erro' ?>"><?= e($flash[1]) ?></p><?php endif; ?>