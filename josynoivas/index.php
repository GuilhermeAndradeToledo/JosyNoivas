<?php

$titulo = 'Aluguel de vestidos de noiva e festa';
$paginaCheia = true;
require_once __DIR__ . '/includes/header.php';

$banners  = $pdo->query("SELECT * FROM tblBanner WHERE banStatus = 'Ativo' ORDER BY banOrdem, idBanner")->fetchAll();
$slides   = $banners ?: [[
    'banTitulo' => 'Encontre o vestido dos seus sonhos',
    'banTexto' => 'Aluguel de vestidos de noiva, festa e ternos, com atendimento personalizado.',
    'banTextoBotao' => 'Descobrir a coleção',
    'banLink' => 'paginas/catalogo.php',
    'banImgDesktop' => null,
    'banImgMobile' => null,
]];

$ocasioes   = $pdo->query("SELECT * FROM tblOcasiao ORDER BY ocaOrdem")->fetchAll();
$destaques  = buscarProdutos($pdo, 'p.proDestaque = 1', [], 'p.proDataCriacao DESC', 4)
    ?: buscarProdutos($pdo, '1=1', [], 'p.proDataCriacao DESC', 4);
$acessorios = buscarProdutos($pdo, "c.catSlug = 'acessorios'", [], 'p.proTipoPeca, p.proDataCriacao DESC', 24);
$tipos      = array_values(array_unique(array_filter(array_column($acessorios, 'proTipoPeca'))));
$posts      = $pdo->query("SELECT posTitulo, posSlug, posCategoria, posCapa FROM tblPost
                           WHERE posStatus = 'Publicado' ORDER BY posDataPublicacao DESC LIMIT 3")->fetchAll();
$rotuloPost = ['Notícias' => 'Novidades', 'Perguntas' => 'Perguntas e respostas'];
$wa = whatsappLink($cfg, 'Olá, gostaria de atendimento.');
?>

<section class="banner" aria-label="Destaques da loja">
    <?php foreach ($slides as $i => $b): $tag = $i === 0 ? 'h1' : 'h2'; ?>
        <div class="banner-slide <?= $i === 0 ? 'ativo' : '' ?>">
            <div class="banner-texto">
                <p class="eyebrow">Nova coleção</p>
                <<?= $tag ?> class="banner-titulo"><?= e($b['banTitulo']) ?></<?= $tag ?>>
                <?php if ($b['banTexto']): ?><p class="banner-sub"><?= e($b['banTexto']) ?></p><?php endif; ?>
                <div class="botoes">
                    <?php if ($b['banTextoBotao']): ?>
                        <a class="btn" href="<?= e(resolverLink($b['banLink'])) ?>"><?= e($b['banTextoBotao']) ?></a>
                    <?php endif; ?>
                    <a class="btn btn-contorno" href="<?= url('paginas/catalogo.php') ?>">Ver catálogo</a>
                </div>
            </div>
            <div class="arco">
                <?php if ($b['banImgDesktop'] || $b['banImgMobile']): ?>
                    <picture>
                        <?php if ($b['banImgMobile']): ?>
                            <source media="(max-width: 767px)" srcset="<?= e(url($b['banImgMobile'])) ?>"><?php endif; ?>
                        <img src="<?= e(url($b['banImgDesktop'] ?: $b['banImgMobile'])) ?>" alt="<?= e($b['banTitulo']) ?>">
                    </picture>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (count($slides) > 1): ?>
        <button type="button" class="seta seta-ant" aria-label="Banner anterior"><?= icone('prev', 20) ?></button>
        <button type="button" class="seta seta-prox" aria-label="Próximo banner"><?= icone('next', 20) ?></button>
        <div class="pontos"></div>
    <?php endif; ?>
</section>

<?php if ($ocasioes): ?>
    <section class="secao">
        <div class="secao-cab"><small>Escolha por ocasião</small>
            <h2>Para cada momento</h2>
        </div>
        <div class="grade-4">
            <?php foreach ($ocasioes as $o): ?>
                <a class="ocasiao" href="<?= url('paginas/catalogo.php?ocasiao=' . urlencode($o['ocaSlug'])) ?>">
                    <?php if ($o['ocaImagem']): ?><img src="<?= e(url($o['ocaImagem'])) ?>" alt="" loading="lazy"><?php endif; ?>
                    <span class="rotulo"><?= e($o['ocaNome']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="secao secao-branca">
    <div class="secao-cab"><small>Destaques</small>
        <h2>Nova coleção</h2>
    </div>
    <?php if ($destaques): ?>
        <div class="grade-4">
            <?php foreach ($destaques as $p) include __DIR__ . '/includes/card_produto.php'; ?>
        </div>
    <?php else: ?>
        <p style="text-align:center;opacity:.6">Em breve novos vestidos por aqui.</p>
    <?php endif; ?>
    <div class="secao-rodape"><a class="btn btn-contorno" href="<?= url('paginas/catalogo.php') ?>">Ver catálogo completo</a></div>
</section>

<section class="secao">
    <div class="duplo">
        <a class="bloco" href="<?= url('paginas/catalogo.php?ordem=novidades') ?>">
            <div class="bloco-foto"><span class="bloco-rotulo">Novidades</span></div>
        </a>
        <a class="bloco" href="<?= url('paginas/favoritos.php') ?>">
            <div class="bloco-foto"><span class="bloco-rotulo">Favoritos</span></div>
        </a>
    </div>
</section>

<?php if ($acessorios && $tipos): ?>
    <section class="secao secao-branca">
        <div class="secao-cab menor"><small>Acessórios</small>
            <h2>Complete o seu look</h2>
        </div>
        <div class="abas">
            <?php foreach ($tipos as $i => $t): ?>
                <button type="button" class="aba <?= $i === 0 ? 'ativo' : '' ?>" data-tipo="<?= e($t) ?>"><?= e(ucfirst($t)) ?></button>
            <?php endforeach; ?>
        </div>
        <div class="grade-4" id="grade-acessorios">
            <?php $n = 0;
            foreach ($acessorios as $p):
                $oculto = !($p['proTipoPeca'] === $tipos[0] && $n++ < 4);
                include __DIR__ . '/includes/card_produto.php';
            endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="beneficios">
    <span class="beneficio">Atendimento personalizado</span>
    <span class="beneficio">Peças para todas as ocasiões</span>
    <span class="beneficio">Fotos reais de cada peça</span>
    <a class="beneficio" href="<?= e($wa) ?>" target="_blank" rel="noopener">Peça pelo WhatsApp</a>
</section>

<?php if ($posts): ?>
    <section class="secao">
        <div class="secao-cab"><small>Blog</small>
            <h2>Dicas e novidades</h2>
        </div>
        <div class="grade-3">
            <?php foreach ($posts as $po): ?>
                <a class="post" href="<?= url('paginas/post.php?slug=' . urlencode($po['posSlug'])) ?>">
                    <div class="post-img"><?php if ($po['posCapa']): ?><img src="<?= e(url($po['posCapa'])) ?>" alt="" loading="lazy"><?php endif; ?></div>
                    <span class="post-cat"><?= e($rotuloPost[$po['posCategoria']] ?? $po['posCategoria']) ?></span>
                    <span class="post-titulo"><?= e($po['posTitulo']) ?></span>
                    <span class="post-mais">Leia mais →</span>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="secao-rodape"><a class="btn btn-contorno" href="<?= url('paginas/blog.php') ?>">Ver todas as publicações</a></div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>