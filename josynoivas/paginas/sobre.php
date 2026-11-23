<?php
$titulo = 'Sobre e contato';
$paginaCheia = true;
require_once __DIR__ . '/../includes/header.php';

$tel = !empty($cfg['whatsapp']) ? telefoneBR($cfg['whatsapp']) : '';
$sobre = trim((string)($cfg['texto_sobre'] ?? ''));
if ($sobre === '') {
    $sobre = 'Na Josy Noivas você encontra vestidos de noiva, festa e ternos para alugar, com atendimento personalizado e ajustes feitos na própria loja.';
}
$mapa = trim((string)($cfg['mapa_url'] ?? ''));
$mapaEmbed = preg_match('~^https://www\.google\.com/maps\?.*output=embed~', $mapa);
$mapaLink = !$mapaEmbed && preg_match('~^https?://~i', $mapa);
$redes = array_filter(['Instagram' => $cfg['instagram'] ?? '', 'Facebook' => $cfg['facebook'] ?? '']);
?>

<section class="sobre-corpo">
    <div class="sobre-texto">
        <h1>Sobre a Josy Noivas</h1>
        <?= renderConteudoPost($sobre) ?>
        <h2>Como funciona</h2>
        <p>1. Escolha o vestido no site.<br>2. Fale com a equipe pelo WhatsApp.<br>3. Prove na loja e faça os ajustes.</p>
    </div>

    <div class="sobre-contato" id="contato">
        <h2>Fale com a gente</h2>
        <?php if ($tel): ?><p class="contato-destaque">WhatsApp: <?= e($tel) ?></p><?php endif; ?>
        <?php if (!empty($cfg['endereco'])): ?><p class="contato-linha">Endereço: <?= e($cfg['endereco']) ?></p><?php endif; ?>
        <?php if (!empty($cfg['horario'])): ?><p class="contato-linha">Horário: <?= e($cfg['horario']) ?></p><?php endif; ?>
        <?php if ($redes): ?>
            <p class="contato-linha">
                <?php $primeiro = true; foreach ($redes as $nome => $link): ?>
                    <?= $primeiro ? '' : ' · ' ?><a href="<?= e($link) ?>" target="_blank" rel="noopener"><u><?= $nome ?></u></a>
                <?php $primeiro = false; endforeach; ?>
            </p>
        <?php endif; ?>
        <a class="btn" href="<?= e(whatsappLink($cfg, 'Olá, gostaria de atendimento.')) ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
        <div class="mapa">
            <?php if ($mapaEmbed): ?>
                <iframe src="<?= e($mapa) ?>" width="100%" height="400" style="border:0;" loading="lazy"></iframe>
            <?php elseif ($mapaLink): ?>
                <a href="<?= e($mapa) ?>" target="_blank" rel="noopener"><u>Abrir mapa da loja →</u></a>
            <?php else: ?>
                Mapa da loja
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>