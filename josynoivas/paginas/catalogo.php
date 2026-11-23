<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$permitidos = ['categoria', 'ocasiao', 'q', 'estilo', 'modelagem', 'tamanho', 'status', 'ordem', 'pagina'];
$f = [];
foreach ($permitidos as $k) {
    $v = isset($_GET[$k]) && is_string($_GET[$k]) ? trim($_GET[$k]) : '';
    if ($v !== '') $f[$k] = mb_substr($v, 0, 80);
}

function urlCatalogo(array $f, array $mudar = []): string
{
    $q = array_merge($f, $mudar);
    if (!array_key_exists('pagina', $mudar)) unset($q['pagina']);
    $q = array_filter($q, fn($v) => $v !== null && $v !== '');
    return url('paginas/catalogo.php') . ($q ? '?' . http_build_query($q) : '');
}

function camposOcultos(array $f, array $excluir): string
{
    $html = '';
    foreach ($f as $k => $v) {
        if (in_array($k, $excluir, true)) continue;
        $html .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    return $html;
}

$onde = [];
$par = [];
if (isset($f['categoria'])) {
    $onde[] = 'c.catSlug = :cat';
    $par[':cat'] = $f['categoria'];
}
if (isset($f['ocasiao'])) {
    $onde[] = 'EXISTS (SELECT 1 FROM tblProdutoOcasiao po JOIN tblOcasiao o ON o.idOcasiao = po.pocIdOcasiao
                       WHERE po.pocIdProduto = p.idProduto AND o.ocaSlug = :oca)';
    $par[':oca'] = $f['ocasiao'];
}
if (isset($f['estilo'])) {
    $onde[] = 'p.proEstilo = :estilo';
    $par[':estilo'] = $f['estilo'];
}
if (isset($f['modelagem'])) {
    $onde[] = 'p.proModelagem = :modelagem';
    $par[':modelagem'] = $f['modelagem'];
}
if (isset($f['tamanho'])) {
    $onde[] = 'EXISTS (SELECT 1 FROM tblProdutoTamanho pt JOIN tblTamanho t ON t.idTamanho = pt.ptaIdTamanho
                       WHERE pt.ptaIdProduto = p.idProduto AND t.tamNome = :tam)';
    $par[':tam'] = $f['tamanho'];
}
if (isset($f['status']) && in_array($f['status'], ['Disponível', 'Indisponível'], true)) {
    $onde[] = 'p.proStatus = :status';
    $par[':status'] = $f['status'];
}
if (isset($f['q'])) {
    $onde[] = '(p.proNome LIKE :q1 OR p.proCor LIKE :q2 OR p.proModelagem LIKE :q3 OR p.proEstilo LIKE :q4
                OR EXISTS (SELECT 1 FROM tblProdutoTamanho pq JOIN tblTamanho tq ON tq.idTamanho = pq.ptaIdTamanho
                           WHERE pq.ptaIdProduto = p.idProduto AND tq.tamNome = :q5))';
    $like = '%' . addcslashes($f['q'], '%_\\') . '%';
    $par[':q1'] = $par[':q2'] = $par[':q3'] = $par[':q4'] = $like;
    $par[':q5'] = $f['q'];
}
$where = $onde ? implode(' AND ', $onde) : '1=1';

$ordens = [
    'novidades'   => ['Novidades', 'p.proDataCriacao DESC'],
    'menor-preco' => ['Menor preço', 'p.proPreco ASC'],
    'maior-preco' => ['Maior preço', 'p.proPreco DESC'],
    'mais-vistos' => ['Mais vistos', 'views7 DESC, p.proDataCriacao DESC'],
];
$ordem = isset($ordens[$f['ordem'] ?? '']) ? $f['ordem'] : 'novidades';

$porPagina = 12;
$pagina = max(1, min(50, (int)($f['pagina'] ?? 1)));

$st = $pdo->prepare("SELECT COUNT(*) FROM tblProduto p JOIN tblCategoria c ON c.idCategoria = p.proIdCategoria WHERE $where");
$st->execute($par);
$total = (int)$st->fetchColumn();
$produtos = buscarProdutos($pdo, $where, $par, $ordens[$ordem][1], $pagina * $porPagina);
$temMais = $total > count($produtos);

$tituloPagina = 'Catálogo';
if (isset($f['categoria'])) {
    $st = $pdo->prepare('SELECT catNome FROM tblCategoria WHERE catSlug = ?');
    $st->execute([$f['categoria']]);
    $tituloPagina = $st->fetchColumn() ?: 'Catálogo';
} elseif (isset($f['ocasiao'])) {
    $st = $pdo->prepare('SELECT ocaNome FROM tblOcasiao WHERE ocaSlug = ?');
    $st->execute([$f['ocasiao']]);
    $tituloPagina = $st->fetchColumn() ?: 'Catálogo';
} elseif (isset($f['q'])) {
    $tituloPagina = 'Resultados para “' . $f['q'] . '”';
}

$estilos    = $pdo->query("SELECT DISTINCT proEstilo FROM tblProduto WHERE proEstilo IS NOT NULL AND proEstilo <> '' ORDER BY proEstilo")->fetchAll(PDO::FETCH_COLUMN);
$modelagens = $pdo->query("SELECT DISTINCT proModelagem FROM tblProduto WHERE proModelagem IS NOT NULL AND proModelagem <> '' ORDER BY proModelagem")->fetchAll(PDO::FETCH_COLUMN);
$tamanhos   = $pdo->query("SELECT tamNome FROM tblTamanho ORDER BY tamOrdem")->fetchAll(PDO::FETCH_COLUMN);

$titulo = $tituloPagina;
$paginaCheia = true;
require_once __DIR__ . '/../includes/header.php';
?>

<section class="catalogo-titulo">
    <p class="migalhas"><a href="<?= url('index.php') ?>">Início</a> / <?= e($tituloPagina) ?></p>
    <h1><?= e($tituloPagina) ?></h1>
</section>

<section class="barra-cat">
    <form class="busca-cat" action="<?= url('paginas/catalogo.php') ?>" method="get" role="search">
        <?= camposOcultos($f, ['q', 'pagina']) ?>
        <input type="search" name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="Buscar por nome, cor, modelo ou tamanho" aria-label="Buscar">
    </form>

    <nav class="chips" aria-label="Categorias">
        <?php foreach ($categorias as $cat): $ativa = ($f['categoria'] ?? '') === $cat['catSlug']; ?>
            <a class="chip <?= $ativa ? 'ativo' : '' ?>"
                href="<?= e(urlCatalogo($f, ['categoria' => $ativa ? null : $cat['catSlug'], 'ocasiao' => null])) ?>"><?= e($cat['catNome']) ?></a>
        <?php endforeach; ?>
    </nav>

    <button type="button" class="btn btn-contorno btn-filtros" data-filtros>Filtros</button>

    <form class="ordenar" action="<?= url('paginas/catalogo.php') ?>" method="get">
        <?= camposOcultos($f, ['ordem', 'pagina']) ?>
        <label for="ordem">Ordenar:</label>
        <select id="ordem" name="ordem" data-auto-submit>
            <?php foreach ($ordens as $chave => $o): ?>
                <option value="<?= e($chave) ?>" <?= $ordem === $chave ? 'selected' : '' ?>><?= e($o[0]) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</section>

<section class="corpo-cat">
    <aside class="filtros" aria-label="Filtros">
        <button type="button" class="filtros-fechar" data-filtros aria-label="Fechar">Fechar ✕</button>
        <?php
        $grupos = [
            ['Estilo', 'estilo', $estilos],
            ['Modelagem', 'modelagem', $modelagens],
        ];
        foreach ($grupos as [$rotulo, $chave, $opcoes]):
            if (!$opcoes) continue; ?>
            <div class="filtro-grupo">
                <h3><?= $rotulo ?></h3>
                <?php foreach ($opcoes as $op): $sel = ($f[$chave] ?? '') === $op; ?>
                    <a class="<?= $sel ? 'ativo' : '' ?>" href="<?= e(urlCatalogo($f, [$chave => $sel ? null : $op])) ?>"><?= e($op) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="filtro-grupo">
            <h3>Tamanho</h3>
            <div class="tamanhos">
                <?php foreach ($tamanhos as $tam): $sel = ($f['tamanho'] ?? '') === $tam; ?>
                    <a class="<?= $sel ? 'ativo' : '' ?>" href="<?= e(urlCatalogo($f, ['tamanho' => $sel ? null : $tam])) ?>"><?= e($tam) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="filtro-grupo">
            <h3>Disponibilidade</h3>
            <?php foreach (['Disponível', 'Indisponível'] as $s): $sel = ($f['status'] ?? '') === $s; ?>
                <a class="<?= $sel ? 'ativo' : '' ?>" href="<?= e(urlCatalogo($f, ['status' => $sel ? null : $s])) ?>"><?= $s ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (array_diff_key($f, ['ordem' => 1, 'pagina' => 1])): ?>
            <a class="limpar-filtros" href="<?= url('paginas/catalogo.php') ?>">Limpar filtros</a>
        <?php endif; ?>
    </aside>

    <div class="grade-cat">
        <?php if ($produtos): ?>
            <div class="grade-produtos-cat">
                <?php foreach ($produtos as $p) include __DIR__ . '/../includes/card_produto.php'; ?>
            </div>
            <?php if ($temMais): ?>
                <a class="btn btn-contorno" href="<?= e(urlCatalogo($f, ['pagina' => $pagina + 1])) ?>">Carregar mais vestidos</a>
            <?php endif; ?>
        <?php else: ?>
            <p class="vazio-cat">Nenhuma peça encontrada com esses filtros. <a href="<?= url('paginas/catalogo.php') ?>">Ver tudo</a></p>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>