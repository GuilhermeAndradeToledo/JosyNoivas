<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) && is_string($_GET['q']) ? trim(mb_substr($_GET['q'], 0, 60)) : '';
if (mb_strlen($q) < 2) {
    echo '[]';
    exit;
}

$like = '%' . addcslashes($q, '%_\\') . '%';
$achados = buscarProdutos(
    $pdo,
    '(p.proNome LIKE :a OR p.proCor LIKE :b OR p.proModelagem LIKE :c)',
    [':a' => $like, ':b' => $like, ':c' => $like],
    'p.proNome',
    6
);

echo json_encode(array_map(fn($p) => [
    'nome' => $p['proNome'],
    'meta' => $p['catNome'] . ' · ' . preco($p['proPreco']),
    'foto' => $p['foto'] ? url($p['foto']) : null,
    'url'  => url('paginas/produto.php?slug=' . urlencode($p['proSlug'])),
], $achados), JSON_UNESCAPED_UNICODE);
