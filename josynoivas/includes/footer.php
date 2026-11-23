</main>

<?php

$tel = !empty($cfg['whatsapp']) ? telefoneBR($cfg['whatsapp']) : '';
$end = !empty($cfg['endereco']) ? $cfg['endereco'] : 'Rua Ari Barroso, 32 · Alto do Pedregulho · Guaratinguetá-SP · CEP 12514-130';
?>
<footer class="rodape">
    <div class="rodape-linha">
        <div class="rodape-marca">
            <span class="logo-rodape"><?= SITE_NAME ?></span>
            <p>Aluguel de vestidos e ternos para todas as ocasiões.</p>
        </div>

        <div class="rodape-col">
            <h3>Loja</h3>
            <?php foreach ($categorias as $cat): ?>
                <a href="<?= url('paginas/catalogo.php?categoria=' . urlencode($cat['catSlug'])) ?>"><?= e($cat['catNome']) ?></a>
            <?php endforeach; ?>
        </div>

        <div class="rodape-col">
            <h3>Ajuda</h3>
            <a href="<?= url('paginas/blog.php') ?>">Blog</a>
            <a href="<?= url('paginas/perguntas.php') ?>">Perguntas e respostas</a>
            <a href="<?= url('paginas/sobre.php') ?>">Sobre</a>
            <a href="<?= url('paginas/contato.php') ?>">Contato</a>
        </div>

        <div class="rodape-col">
            <h3>Contato</h3>
            <span class="rodape-end"><?= e($end) ?></span>
            <?php if ($tel): ?>
                <a href="<?= e(whatsappLink($cfg, 'Olá, gostaria de atendimento.')) ?>" target="_blank" rel="noopener">WhatsApp: <?= e($tel) ?></a>
            <?php endif; ?>
            <?php if (!empty($cfg['instagram'])): ?><a href="<?= e($cfg['instagram']) ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
            <?php if (!empty($cfg['facebook'])): ?><a href="<?= e($cfg['facebook']) ?>" target="_blank" rel="noopener">Facebook</a><?php endif; ?>
        </div>
    </div>

    <div class="rodape-base">
        <span>&copy; <?= date('Y') ?> <?= SITE_NAME ?> · Guaratinguetá-SP</span>
        <span><a href="<?= url('paginas/termos.php') ?>">Termos de uso</a> · <a href="<?= url('paginas/privacidade.php') ?>">Privacidade</a></span>
    </div>
</footer>

<?php require __DIR__ . '/cookies.php'; ?>
<script src="<?= url('js/main.js') ?>"></script>
</body>
</html>