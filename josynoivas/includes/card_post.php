<a class="post" href="<?= url('paginas/post.php?slug=' . urlencode($po['posSlug'])) ?>">
    <div class="post-img">
        <?php if ($po['posCapa']): ?>
            <img src="<?= e(url($po['posCapa'])) ?>" alt="" loading="lazy">
        <?php else: ?>
            <span class="foto-vazia">Imagem</span>
        <?php endif; ?>
    </div>
    <span class="post-cat"><?= e(rotuloPost($po['posCategoria'])) ?></span>
    <span class="post-titulo"><?= e($po['posTitulo']) ?></span>
    <span class="post-mais">Leia mais →</span>
</a>