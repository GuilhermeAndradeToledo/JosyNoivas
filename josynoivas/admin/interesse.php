<?php
require_once __DIR__ . '/../includes/admin_base.php';

$lista = $pdo->query("SELECT p.idProduto, p.proNome, p.proStatus, p.proLocal, p.proQuantidade, p.proDataCriacao,
        (SELECT COUNT(*) FROM tblContatoWhatsapp w WHERE w.whaIdProduto = p.idProduto AND w.whaData >= NOW() - INTERVAL 30 DAY) AS cliques,
        (SELECT COALESCE(SUM(v.visQtd), 0) FROM tblVisualizacao v WHERE v.visIdProduto = p.idProduto AND v.visData >= CURDATE() - INTERVAL 30 DAY) AS views,
        (SELECT COUNT(*) FROM tblLocacao l WHERE l.locIdProduto = p.idProduto AND l.locStatus IN ('Reservada','Retirada')) AS locAtivas
    FROM tblProduto p ORDER BY cliques DESC, views DESC, p.proNome LIMIT 500")->fetchAll();

$ranking = array_slice($lista, 0, 15);

$alertas = [
    'estoque' => ['Candidatas ao estoque', 'Na vitrine há mais de 30 dias, sem cliques e quase sem visualizações.', []],
    'vitrine' => ['Trazer para a vitrine', 'No estoque, mas com interesse nos últimos 30 dias.', []],
    'indisp'  => ['Indisponíveis sem locação', 'Marcadas como indisponíveis, mas sem locação reservada ou retirada.', []],
    'qtd'     => ['Disponíveis com quantidade 0', 'Confira a quantidade ou marque como indisponível.', []],
];
foreach ($lista as $p) {
    $dias = (time() - strtotime($p['proDataCriacao'])) / 86400;
    $cl = (int)$p['cliques'];
    $vw = (int)$p['views'];
    if ($p['proLocal'] === 'Vitrine' && $p['proStatus'] === 'Disponível' && $cl === 0 && $vw < 3 && $dias >= 30) {
        $alertas['estoque'][2][] = [$p, $vw . ' visualizações em 30 dias'];
    }
    if ($p['proLocal'] === 'Estoque' && ($cl >= 2 || $vw >= 30)) {
        $alertas['vitrine'][2][] = [$p, $cl . ' cliques e ' . $vw . ' visualizações em 30 dias'];
    }
    if ($p['proStatus'] === 'Indisponível' && (int)$p['locAtivas'] === 0) {
        $alertas['indisp'][2][] = [$p, 'Confira se já pode voltar a ficar disponível'];
    }
    if ($p['proStatus'] === 'Disponível' && (int)$p['proQuantidade'] === 0) {
        $alertas['qtd'][2][] = [$p, 'Quantidade cadastrada: 0'];
    }
}

$menuAtivo = 'interesse';
$tituloAdmin = 'Interesse e estoque';
require_once __DIR__ . '/../includes/admin_topo.php';
?>
<div class="admin-topo">
    <h1>Interesse e estoque</h1>
</div>

<div class="lista">
    <h2>Peças mais procuradas (30 dias)</h2>
    <p class="auth-aviso">Cliques no botão de WhatsApp e visualizações da página do vestido.</p>
    <div class="tabela-rolagem">
        <table class="admin-tabela">
            <thead>
                <tr>
                    <th>Vestido</th>
                    <th>Local</th>
                    <th>Status</th>
                    <th>Cliques</th>
                    <th>Visualizações</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ranking as $r): ?>
                    <tr>
                        <td><?= e($r['proNome']) ?></td>
                        <td><?= e($r['proLocal']) ?></td>
                        <td><span class="status <?= $r['proStatus'] === 'Indisponível' ? 'escuro' : '' ?>"><?= e($r['proStatus']) ?></span></td>
                        <td><?= (int)$r['cliques'] ?></td>
                        <td><?= (int)$r['views'] ?></td>
                        <td class="acoes-link"><a href="<?= url('admin/vestido-editar.php?id=' . (int)$r['idProduto']) ?>">Editar</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$ranking): ?><tr>
                        <td colspan="6">Nenhum vestido cadastrado.</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach (array_chunk($alertas, 2, true) as $par): ?>
    <div class="listas">
        <?php foreach ($par as [$titulo, $dica, $itens]): ?>
            <div class="lista">
                <h2><?= e($titulo) ?> (<?= count($itens) ?>)</h2>
                <p class="auth-aviso"><?= e($dica) ?></p>
                <?php foreach (array_slice($itens, 0, 8) as [$p, $motivo]): ?>
                    <div class="lista-item">
                        <strong><a href="<?= url('admin/vestido-editar.php?id=' . (int)$p['idProduto']) ?>"><?= e($p['proNome']) ?></a></strong>
                        <span><?= e($motivo) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (!$itens): ?><p class="auth-aviso">Nada por aqui.</p><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
<?php require_once __DIR__ . '/../includes/admin_fim.php'; ?>