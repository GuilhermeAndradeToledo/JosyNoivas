<?php

$indisp = $p['proStatus'] === 'Indisponível';
$alta   = !$indisp && (int)$p['views7'] >= (int)($cfg['alta_procura_min'] ?? 30);
$novo   = !$indisp && !empty($p['proDataCriacao']) && strtotime($p['proDataCriacao']) >= strtotime('-15 days');
$fav    = in_array((int)$p['idProduto'], idsFavoritos($pdo), true);
$link   = url('paginas/produto.php?slug=' . urlencode($p['proSlug']));
$det    = $p['proModelagem'] ?: $p['proTipoPeca'];
$meta   = $p['catNome'] . ($det ? ' · ' . $det : '');
?>
<article class="card-produto" data-tipo="<?= e($p['proTipoPeca']) ?>" <?= !empty($oculto) ? 'hidden' : '' ?>>
    <div class="card-foto">
        <a class="card-foto-link" href="<?= $link ?>" tabindex="-1" aria-hidden="true">
            <?php if (!empty($p['foto'])): ?>
                <img src="<?= e(url($p['foto'])) ?>" alt="<?= e($p['proNome']) ?>" loading="lazy">
            <?php else: ?>
                <span class="foto-vazia">Foto 4:5</span>
            <?php endif; ?>
        </a>
        <?php if ($novo): ?><span class="selo-novo">Novo</span><?php endif; ?>
        <button type="button" class="fav-btn <?= $fav ? 'ativo' : '' ?>" aria-pressed="<?= $fav ? 'true' : 'false' ?>" data-fav="<?= (int)$p['idProduto'] ?>" aria-label="Favoritar <?= e($p['proNome']) ?>"><?= icone('heart', 18) ?></button>
        <span class="tag <?= $indisp ? 'tag-escura' : '' ?>"><?= $indisp ? 'Indisponível' : ($alta ? 'Alta procura' : 'Disponível') ?></span>
    </div>
    <a class="card-info" href="<?= $link ?>">
        <span class="card-meta"><?= e($meta) ?></span>
        <span class="card-nome"><?= e($p['proNome']) ?></span>
        <span class="card-preco <?= $indisp ? 'apagado' : '' ?>"><?= preco($p['proPreco']) ?></span>
    </a>
</article>