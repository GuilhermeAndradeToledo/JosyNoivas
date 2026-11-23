<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? trim($_GET['slug']) : '';
$st = $pdo->prepare('SELECT *, COALESCE(posDataPublicacao, posDataCriacao) AS data FROM tblPost WHERE posSlug = ?');
$st->execute([$slug]);
$post = $st->fetch();
$rascunho = $post && $post['posStatus'] !== 'Publicado';

if (!$post || ($rascunho && !ehAdmin($pdo))) {
    http_response_code(404);
    $titulo = 'Post não encontrado';
    require_once __DIR__ . '/../includes/header.php';
    echo '<p>Não encontramos esse post. <a href="' . url('paginas/blog.php') . '"><u>Ver o blog</u></a></p>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$st = $pdo->prepare("SELECT posTitulo, posSlug, posCategoria, posCapa FROM tblPost
                     WHERE posStatus = 'Publicado' AND idPost <> ?
                     ORDER BY (posCategoria = ?) DESC, COALESCE(posDataPublicacao, posDataCriacao) DESC LIMIT 3");
$st->execute([$post['idPost'], $post['posCategoria']]);
$relacionados = $st->fetchAll();

$palavras = preg_match_all('/\S+/u', $post['posConteudo']);
$minutos = max(1, (int)ceil($palavras / 200));

$titulo = $post['posTitulo'];
$paginaCheia = true;
require_once __DIR__ . '/../includes/header.php';
?>

<article class="artigo">
    <div class="artigo-coluna">
        <?php if ($rascunho): ?><p class="aviso aviso-erro">Rascunho: só administradores veem esta página.</p><?php endif; ?>

        <p class="migalhas">
            <a href="<?= url('paginas/blog.php') ?>">Blog</a> /
            <a href="<?= e(url('paginas/blog.php?tipo=' . urlencode($post['posCategoria']))) ?>"><?= e(rotuloPost($post['posCategoria'])) ?></a>
        </p>
        <span class="post-cat"><?= e(rotuloPost($post['posCategoria'])) ?></span>
        <h1><?= e($post['posTitulo']) ?></h1>
        <p class="artigo-meta">Por <?= SITE_NAME ?> · <?= e(dataExtenso($post['data'])) ?> · <?= $minutos ?> min de leitura</p>

        <?php if ($post['posCapa']): ?>
            <div class="artigo-capa"><img src="<?= e(url($post['posCapa'])) ?>" alt="<?= e($post['posTitulo']) ?>"></div>
        <?php endif; ?>

        <div class="artigo-texto"><?= renderConteudoPost($post['posConteudo']) ?></div>

        <div class="chamada-artigo">
            <h2>Encontre o seu vestido</h2>
            <a class="btn" href="<?= url('paginas/catalogo.php') ?>">Ver o catálogo</a>
        </div>
    </div>
</article>

<?php if ($relacionados): ?>
<section class="leia-tambem">
    <h2>Leia também</h2>
    <div class="grade-3">
        <?php foreach ($relacionados as $po) include __DIR__ . '/../includes/card_post.php'; ?>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>