<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$idProduto = (int)($_GET['produto'] ?? 0);
$idTamanho = (int)($_GET['tamanho'] ?? 0);

$st = $pdo->prepare('SELECT idProduto, proNome, proSlug FROM tblProduto WHERE idProduto = ?');
$st->execute([$idProduto]);
$p = $st->fetch();
if (!$p) {
    header('Location: ' . url('paginas/catalogo.php'));
    exit;
}

$tamanho = null;
if ($idTamanho > 0) {
    $st = $pdo->prepare('SELECT idTamanho, tamNome FROM tblTamanho WHERE idTamanho = ?');
    $st->execute([$idTamanho]);
    $tamanho = $st->fetch() ?: null;
}

$cfg = configuracoes($pdo);
$pagina = SITE_URL . url('paginas/produto.php?slug=' . urlencode($p['proSlug']));

$msg = isset($_GET['aviso'])
    ? 'Olá, gostaria de ser avisada quando o vestido ' . $p['proNome'] . ' estiver disponível'
    : str_replace('{produto}', $p['proNome'], $cfg['whatsapp_mensagem'] ?? 'Olá, tenho interesse no {produto}');
if ($tamanho) $msg .= "\nTamanho: " . $tamanho['tamNome'];
$msg .= "\n" . $pagina;

$st = $pdo->prepare('INSERT INTO tblContatoWhatsapp (whaIdProduto, whaIdUsuario, whaIdTamanho) VALUES (?, ?, ?)');
$st->execute([$p['idProduto'], $_SESSION['usuario']['idUsuario'] ?? null, $tamanho['idTamanho'] ?? null]);

header('Location: ' . whatsappLink($cfg, $msg));
exit;
