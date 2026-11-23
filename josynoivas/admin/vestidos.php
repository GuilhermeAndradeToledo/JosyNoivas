<?php
require_once __DIR__ . '/../includes/admin_base.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    $id = (int)($_POST['id'] ?? 0);
    if (!csrfOk($_POST['csrf'] ?? null)) {
        flash('Sessão expirada. Tente novamente.', 'erro');
    } else {
        $st = $pdo->prepare('SELECT fotCaminho FROM tblProdutoFoto WHERE fotIdProduto = ?');
        $st->execute([$id]);
        $caminhos = $st->fetchAll(PDO::FETCH_COLUMN);
        try {
            $pdo->prepare('DELETE FROM tblProduto WHERE idProduto = ?')->execute([$id]);
            foreach ($caminhos as $c) apagarArquivoImagem($c);
            flash('Vestido excluído.');
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') throw $e;
            flash('Não dá para excluir: há locações ou pedidos ligados a esta peça. Marque-a como indisponível.', 'erro');
        }
    }
    header('Location: ' . url('admin/vestidos.php'));
    exit;
}

$q = isset($_GET['q']) && is_string($_GET['q']) ? trim(mb_substr($_GET['q'], 0, 80)) : '';
$where = '1=1';
$par = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where = '(p.proNome LIKE :a OR p.proCor LIKE :b OR c.catNome LIKE :c)';
    $par = [':a' => $like, ':b' => $like, ':c' => $like];
}
$st = $pdo->prepare("SELECT p.idProduto, p.proNome, p.proPreco, p.proStatus, c.catNome,
        (SELECT f.fotCaminho FROM tblProdutoFoto f WHERE f.fotIdProduto = p.idProduto
          ORDER BY f.fotPrincipal DESC, f.fotOrdem LIMIT 1) AS foto,
        (SELECT GROUP_CONCAT(t.tamNome ORDER BY t.tamOrdem SEPARATOR ',') FROM tblProdutoTamanho pt
          JOIN tblTamanho t ON t.idTamanho = pt.ptaIdTamanho WHERE pt.ptaIdProduto = p.idProduto) AS tamanhos
    FROM tblProduto p JOIN tblCategoria c ON c.idCategoria = p.proIdCategoria
    WHERE $where ORDER BY p.idProduto DESC LIMIT 300");
$st->execute($par);
$lista = $st->fetchAll();

$menuAtivo = 'vestidos';
$tituloAdmin = 'Vestidos';
require_once __DIR__ . '/../includes/admin_topo.php';
?>
<div class="admin-topo">
    <h1>Vestidos</h1>
    <a class="btn" href="<?= url('admin/vestido-editar.php') ?>">Novo vestido</a>
</div>

<form action="<?= url('admin/vestidos.php') ?>" method="get" role="search">
    <input class="admin-busca" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nome, cor ou categoria" aria-label="Buscar">
</form>

<div class="tabela-rolagem">
    <table class="admin-tabela">
        <thead>
            <tr><th>Foto</th><th>Nome</th><th>Categoria</th><th>Tamanhos</th><th>Preço</th><th>Status</th><th>Ações</th></tr>
        </thead>
        <tbody>
            <?php foreach ($lista as $v):
                $tams = $v['tamanhos'] ? explode(',', $v['tamanhos']) : [];
                $faixa = count($tams) > 1 ? $tams[0] . '–' . end($tams) : ($tams[0] ?? '—');
                ?>
                <tr>
                    <td><div class="miniatura-admin"><?php if ($v['foto']): ?><img src="<?= e(url($v['foto'])) ?>" alt=""><?php endif; ?></div></td>
                    <td><?= e($v['proNome']) ?></td>
                    <td><?= e($v['catNome']) ?></td>
                    <td><?= e($faixa) ?></td>
                    <td><?= preco($v['proPreco']) ?></td>
                    <td><span class="status <?= $v['proStatus'] === 'Indisponível' ? 'escuro' : '' ?>"><?= e($v['proStatus']) ?></span></td>
                    <td class="acoes-link">
                        <a href="<?= url('admin/vestido-editar.php?id=' . (int)$v['idProduto']) ?>">Editar</a> ·
                        <form method="post" action="<?= url('admin/vestidos.php') ?>" data-confirm="Excluir o vestido <?= e($v['proNome']) ?>? Esta ação não pode ser desfeita." style="display:inline">
                            <input type="hidden" name="acao" value="excluir">
                            <input type="hidden" name="id" value="<?= (int)$v['idProduto'] ?>">
                            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                            <button type="submit" class="link-perigo">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$lista): ?><tr><td colspan="7">Nenhum vestido encontrado.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/admin_fim.php'; ?>