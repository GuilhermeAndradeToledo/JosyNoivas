<?php
require_once __DIR__ . '/../includes/admin_base.php';

$tot = $pdo->query("SELECT COUNT(*) AS total, SUM(proStatus = 'Disponível') AS disp FROM tblProduto")->fetch();
$pendentesP = (int)$pdo->query("SELECT COUNT(*) FROM tblPergunta WHERE perStatus = 'Pendente'")->fetchColumn();
$pendentesA = (int)$pdo->query("SELECT COUNT(*) FROM tblAvaliacao WHERE avaStatus = 'Pendente'")->fetchColumn();

$perguntas = $pdo->query("SELECT q.perNome, q.perTexto, p.proNome FROM tblPergunta q
                          LEFT JOIN tblProduto p ON p.idProduto = q.perIdProduto
                          WHERE q.perStatus = 'Pendente' ORDER BY q.perDataCriacao DESC LIMIT 3")->fetchAll();
$avaliacoes = $pdo->query("SELECT a.avaNota, a.avaTexto, a.avaStatus, u.usuNome, p.proNome FROM tblAvaliacao a
                           JOIN tblUsuario u ON u.idUsuario = a.avaIdUsuario
                           JOIN tblProduto p ON p.idProduto = a.avaIdProduto
                           ORDER BY a.avaData DESC LIMIT 3")->fetchAll();

$menuAtivo = 'dashboard';
$tituloAdmin = 'Dashboard';
require_once __DIR__ . '/../includes/admin_topo.php';

$cartoes = [
    ['Vestidos cadastrados', (int)$tot['total']],
    ['Disponíveis', (int)$tot['disp']],
    ['Perguntas pendentes', $pendentesP],
    ['Avaliações novas', $pendentesA],
];
?>
<div class="admin-topo"><h1>Dashboard</h1></div>

<div class="resumo">
    <?php foreach ($cartoes as [$rotulo, $valor]): ?>
        <div class="resumo-cartao"><small><?= $rotulo ?></small><strong><?= $valor ?></strong></div>
    <?php endforeach; ?>
</div>

<div class="listas">
    <div class="lista">
        <h2>Perguntas pendentes</h2>
        <?php foreach ($perguntas as $q): ?>
            <div class="lista-item">
                <strong><?= e($q['perNome']) ?> · <?= e($q['proNome'] ?? 'Geral') ?></strong>
                <span><?= e($q['perTexto']) ?></span>
            </div>
        <?php endforeach; ?>
        <?php if (!$perguntas): ?><p class="auth-aviso">Nenhuma pergunta pendente.</p><?php endif; ?>
        <a class="auth-link" href="<?= url('admin/perguntas.php') ?>">Ver todas →</a>
    </div>
    <div class="lista">
        <h2>Avaliações recentes</h2>
        <?php foreach ($avaliacoes as $a): ?>
            <div class="lista-item">
                <strong><?= e(explode(' ', $a['usuNome'])[0]) ?> · <?= e($a['proNome']) ?><?= $a['avaStatus'] === 'Pendente' ? ' (pendente)' : '' ?></strong>
                <span><?= estrelas((int)$a['avaNota']) ?> <?= e($a['avaTexto']) ?></span>
            </div>
        <?php endforeach; ?>
        <?php if (!$avaliacoes): ?><p class="auth-aviso">Ainda não há avaliações.</p><?php endif; ?>
        <a class="auth-link" href="<?= url('admin/avaliacoes.php') ?>">Ver todas →</a>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/admin_fim.php'; ?>