<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

if (empty($_SESSION['usuario'])) {
    header('Location: ' . url('paginas/login.php?voltar=' . urlencode(url('paginas/favoritos.php'))));
    exit;
}

$produtos = buscarProdutos(
    $pdo,
    'p.idProduto IN (SELECT favIdProduto FROM tblFavorito WHERE favIdUsuario = :u)',
    [':u' => $_SESSION['usuario']['idUsuario']],
    'p.proDataCriacao DESC',
    100
);

$textoFav = "Olha os vestidos que eu salvei na " . SITE_NAME . ":\n";
foreach (array_slice($produtos, 0, 10) as $p) {
    $textoFav .= "\n" . $p['proNome'] . "\n" . SITE_URL . url('paginas/produto.php?slug=' . urlencode($p['proSlug'])) . "\n";
}

$titulo = 'Meus favoritos';
$paginaCheia = true;
require_once __DIR__ . '/../includes/header.php';
?>

<section class="catalogo-titulo">
    <small>Minha conta</small>
    <h1>Meus favoritos</h1>
    <p class="sub">Vestidos que você salvou para ver depois.</p>
</section>

<section class="fav-lista">
    <?php if ($produtos): ?>
        <div class="grade-4">
            <?php foreach ($produtos as $p) include __DIR__ . '/../includes/card_produto.php'; ?>
        </div>
    <?php else: ?>
        <p class="vazio-cat">Você ainda não salvou nenhuma peça.</p>
    <?php endif; ?>
</section>

<section class="fav-acao">
    <a class="btn btn-contorno" href="<?= url('paginas/catalogo.php') ?>">Ver o catálogo</a>
    <?php if ($produtos): ?>
        <button type="button" class="btn" data-compartilhar-fav data-texto="<?= e($textoFav) ?>">Compartilhar favoritos</button>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>