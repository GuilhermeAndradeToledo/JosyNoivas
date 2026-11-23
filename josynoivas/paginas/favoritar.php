<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

header('Content-Type: application/json; charset=utf-8');

function resp(bool $ok, array $extra = [], int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode(array_merge(['success' => $ok], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') resp(false, ['message' => 'Método inválido.'], 405);
if (empty($_SESSION['usuario'])) resp(false, ['message' => 'Entre para favoritar.'], 401);
if (!csrfOk($_POST['csrf'] ?? null)) resp(false, ['message' => 'Sessão expirada.'], 403);

$idUsuario = (int)$_SESSION['usuario']['idUsuario'];
$idProduto = (int)($_POST['produto'] ?? 0);

$st = $pdo->prepare('SELECT 1 FROM tblProduto WHERE idProduto = ?');
$st->execute([$idProduto]);
if (!$st->fetchColumn()) resp(false, ['message' => 'Peça não encontrada.'], 404);

$st = $pdo->prepare('DELETE FROM tblFavorito WHERE favIdUsuario = ? AND favIdProduto = ?');
$st->execute([$idUsuario, $idProduto]);
$favorito = $st->rowCount() === 0;
if ($favorito) {
    $pdo->prepare('INSERT INTO tblFavorito (favIdUsuario, favIdProduto) VALUES (?, ?)')->execute([$idUsuario, $idProduto]);
}

resp(true, ['favorito' => $favorito]);