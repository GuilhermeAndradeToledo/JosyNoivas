<?php
http_response_code(404);
$titulo = 'Página não encontrada';
$paginaCheia = true;
require_once __DIR__ . '/../includes/header.php';
?>

<section class="secao">
    <div class="secao-cab">
        <small>Erro 404</small>
        <h1>Página não encontrada</h1>
        <p style="opacity:.7;max-width:480px">O endereço que você acessou não existe ou foi movido. Que tal voltar ao catálogo?</p>
    </div>
    <div class="secao-rodape" style="gap:16px;margin-top:0">
        <a class="btn" href="<?= url('paginas/catalogo.php') ?>">Ver o catálogo</a>
        <a class="btn btn-contorno" href="<?= url('index.php') ?>">Voltar ao início</a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
