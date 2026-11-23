<?php
require_once __DIR__ . '/../includes/admin_base.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $acao = $_POST['acao'] ?? '';
    $volta = url('admin/perguntas.php');

    if (!csrfOk($_POST['csrf'] ?? null)) {
        flash('Sessão expirada. Tente novamente.', 'erro');
    } elseif ($acao === 'responder') {
        $resposta = trim(mb_substr((string)($_POST['resposta'] ?? ''), 0, 2000));
        if (mb_strlen($resposta) < 2) {
            flash('Escreva a resposta.', 'erro');
            $volta = url('admin/perguntas.php?id=' . $id);
        } else {
            $pdo->prepare("UPDATE tblPergunta SET perResposta = ?, perStatus = 'Respondida', perPublica = ?, perDataResposta = NOW() WHERE idPergunta = ?")
                ->execute([$resposta, isset($_POST['publica']) ? 1 : 0, $id]);
            flash('Resposta salva.');
        }
    } elseif ($acao === 'excluir') {
        $pdo->prepare('DELETE FROM tblPergunta WHERE idPergunta = ?')->execute([$id]);
        flash('Pergunta excluída.');
    }
    header('Location: ' . $volta);
    exit;
}

$detalhe = null;
if (isset($_GET['id'])) {
    $st = $pdo->prepare('SELECT q.*, p.proNome, p.proSlug FROM tblPergunta q LEFT JOIN tblProduto p ON p.idProduto = q.perIdProduto WHERE q.idPergunta = ?');
    $st->execute([(int)$_GET['id']]);
    $detalhe = $st->fetch();
    if (!$detalhe) {
        flash('Pergunta não encontrada.', 'erro');
        header('Location: ' . url('admin/perguntas.php'));
        exit;
    }
}

$lista = [];
if (!$detalhe) {
    $lista = $pdo->query("SELECT q.idPergunta, q.perNome, q.perTexto, q.perStatus, p.proNome
                          FROM tblPergunta q LEFT JOIN tblProduto p ON p.idProduto = q.perIdProduto
                          ORDER BY (q.perStatus = 'Pendente') DESC, q.perDataCriacao DESC LIMIT 300")->fetchAll();
}

$menuAtivo = 'perguntas';
$tituloAdmin = 'Perguntas';
require_once __DIR__ . '/../includes/admin_topo.php';
?>
<div class="admin-topo"><h1>Perguntas</h1></div>

<?php if ($detalhe): ?>
    <form class="admin-cartao" method="post" action="<?= url('admin/perguntas.php') ?>">
        <input type="hidden" name="acao" value="responder">
        <input type="hidden" name="id" value="<?= (int)$detalhe['idPergunta'] ?>">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

        <p class="auth-aviso">
            <strong><?= e($detalhe['perNome']) ?></strong> · <?= e($detalhe['proNome'] ?? 'Pergunta geral') ?> · <?= e(date('d/m/Y H:i', strtotime($detalhe['perDataCriacao']))) ?>
        </p>
        <p class="pergunta-texto"><?= nl2br(e($detalhe['perTexto'])) ?></p>

        <div class="campo">
            <label for="resposta">Resposta</label>
            <textarea id="resposta" name="resposta" rows="5" maxlength="2000" required><?= e($detalhe['perResposta'] ?? '') ?></textarea>
        </div>
        <div class="checks"><label><input type="checkbox" name="publica" value="1" <?= (int)$detalhe['perPublica'] ? 'checked' : '' ?>> Mostrar na página pública de Perguntas e respostas</label></div>

        <div class="acoes-form">
            <button type="submit" class="btn"><?= $detalhe['perStatus'] === 'Pendente' ? 'Responder' : 'Salvar resposta' ?></button>
            <a class="btn btn-contorno" href="<?= url('admin/perguntas.php') ?>">Voltar</a>
        </div>
    </form>
    <div><?= formAcao('excluir', (int)$detalhe['idPergunta'], 'Excluir pergunta', 'link-perigo', 'Excluir esta pergunta?') ?></div>
<?php else: ?>
    <div class="tabela-rolagem">
        <table class="admin-tabela">
            <thead>
                <tr><th>Cliente</th><th>Vestido</th><th>Pergunta</th><th>Status</th><th>Ações</th></tr>
            </thead>
            <tbody>
                <?php foreach ($lista as $q): $pendente = $q['perStatus'] === 'Pendente'; ?>
                    <tr>
                        <td><?= e($q['perNome']) ?></td>
                        <td><?= e($q['proNome'] ?? 'Geral') ?></td>
                        <td class="celula-texto"><?= e(mb_strimwidth($q['perTexto'], 0, 90, '…')) ?></td>
                        <td><span class="status <?= $pendente ? 'escuro' : '' ?>"><?= e($q['perStatus']) ?></span></td>
                        <td class="acoes-link"><a href="<?= url('admin/perguntas.php?id=' . (int)$q['idPergunta']) ?>"><?= $pendente ? 'Responder' : 'Ver resposta' ?></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$lista): ?><tr><td colspan="5">Nenhuma pergunta ainda.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/admin_fim.php'; ?>