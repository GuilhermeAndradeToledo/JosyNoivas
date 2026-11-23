<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$tipos = ['Dicas', 'Notícias', 'Perguntas', 'Tendências'];
$tipo = isset($_GET['tipo']) && is_string($_GET['tipo']) && in_array($_GET['tipo'], $tipos, true) ? $_GET['tipo'] : '';
$pagina = max(1, min(50, (int)($_GET['pagina'] ?? 1)));
$porPagina = 6;

$where = "posStatus = 'Publicado'";
$par = [];
if ($tipo !== '') {
    $where .= ' AND posCategoria = :t';
    $par[':t'] = $tipo;
}

$st = $pdo->prepare("SELECT COUNT(*) FROM tblPost WHERE $where");
$st->execute($par);
$total = (int)$st->fetchColumn();

$st = $pdo->prepare("SELECT posTitulo, posSlug, posCategoria, posCapa FROM tblPost WHERE $where
                     ORDER BY COALESCE(posDataPublicacao, posDataCriacao) DESC LIMIT " . ($pagina * $porPagina));
$st->execute($par);
$posts = $st->fetchAll();

$titulo = 'Blog';
$paginaCheia = true;
require_once __DIR__ . '/../includes/header.php';

$linkTipo = fn(string $t, int $pg = 1) => url('paginas/blog.php') . (($t !== '' || $pg > 1) ? '?' . http_build_query(array_filter(['tipo' => $t, 'pagina' => $pg > 1 ? $pg : null])) : '');
?>

<section class="secao" style="padding-bottom:0">
    <div class="secao-cab" style="margin-bottom:0">
        <small>Blog Josy Noivas</small>
        <h1>Dicas e novidades para noivas</h1>
    </div>
</section>

<nav class="chips chips-centro" aria-label="Categorias do blog">
    <a class="chip <?= $tipo === '' ? 'ativo' : '' ?>" href="<?= e($linkTipo('')) ?>">Todos</a>
    <?php foreach ($tipos as $t): ?>
        <a class="chip <?= $tipo === $t ? 'ativo' : '' ?>" href="<?= e($linkTipo($t)) ?>"><?= e(rotuloPost($t)) ?></a>
    <?php endforeach; ?>
</nav>

<section class="secao" style="padding-top:40px">
    <?php if ($posts): ?>
        <div class="grade-3">
            <?php foreach ($posts as $po) include __DIR__ . '/../includes/card_post.php'; ?>
        </div>
        <?php if ($total > count($posts)): ?>
            <div class="secao-rodape"><a class="btn btn-contorno" href="<?= e($linkTipo($tipo, $pagina + 1)) ?>">Ver mais publicações</a></div>
        <?php endif; ?>
    <?php else: ?>
        <p class="vazio-cat" style="text-align:center">Ainda não há publicações por aqui.</p>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>