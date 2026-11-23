<?php
require_once __DIR__ . '/../includes/admin_base.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $acao = $_POST['acao'] ?? '';
    if (!csrfOk($_POST['csrf'] ?? null)) {
        flash('Sessão expirada. Tente novamente.', 'erro');
    } elseif ($acao === 'aprovar') {
        $pdo->prepare("UPDATE tblAvaliacao SET avaStatus = 'Aprovada' WHERE idAvaliacao = ?")->execute([$id]);
        flash('Avaliação aprovada.');
    } elseif ($acao === 'excluir') {
        $pdo->prepare('DELETE FROM tblAvaliacao WHERE idAvaliacao = ?')->execute([$id]);
        flash('Avaliação excluída.');
    }
    header('Location: ' . url('admin/avaliacoes.php'));
    exit;
}

$lista = $pdo->query("SELECT a.idAvaliacao, a.avaNota, a.avaTexto, a.avaStatus, u.usuNome, p.proNome
                      FROM tblAvaliacao a
                      JOIN tblUsuario u ON u.idUsuario = a.avaIdUsuario
                      JOIN tblProduto p ON p.idProduto = a.avaIdProduto
                      ORDER BY (a.avaStatus = 'Pendente') DESC, a.avaData DESC LIMIT 300")->fetchAll();

$menuAtivo = 'avaliacoes';
$tituloAdmin = 'Avaliações';
require_once __DIR__ . '/../includes/admin_topo.php';
?>
<div class="admin-topo"><h1>Avaliações</h1></div>

<div class="tabela-rolagem">
    <table class="admin-tabela">
        <thead>
            <tr><th>Cliente</th><th>Vestido</th><th>Nota</th><th>Comentário</th><th>Status</th><th>Ações</th></tr>
        </thead>
        <tbody>
            <?php foreach ($lista as $a): $pendente = $a['avaStatus'] === 'Pendente'; ?>
                <tr>
                    <td><?= e(explode(' ', $a['usuNome'])[0]) ?></td>
                    <td><?= e($a['proNome']) ?></td>
                    <td class="estrelas"><?= estrelas((int)$a['avaNota']) ?></td>
                    <td class="celula-texto"><?= $a['avaTexto'] ? e($a['avaTexto']) : '—' ?></td>
                    <td><span class="status <?= $pendente ? 'escuro' : '' ?>"><?= e($a['avaStatus']) ?></span></td>
                    <td class="acoes-link">
                        <?php if ($pendente): ?><?= formAcao('aprovar', (int)$a['idAvaliacao'], 'Aprovar') ?> · <?php endif; ?>
                        <?= formAcao('excluir', (int)$a['idAvaliacao'], 'Excluir', 'link-perigo', 'Excluir esta avaliação?') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$lista): ?><tr><td colspan="6">Ainda não há avaliações.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/admin_fim.php'; ?>