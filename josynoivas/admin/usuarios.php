<?php
require_once __DIR__ . '/../includes/admin_base.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    $id = (int)($_POST['id'] ?? 0);
    if (!csrfOk($_POST['csrf'] ?? null)) {
        flash('Sessão expirada. Tente novamente.', 'erro');
    } elseif ($id === (int)$_SESSION['usuario']['idUsuario']) {
        flash('Você não pode excluir a sua própria conta.', 'erro');
    } else {
        $st = $pdo->prepare('SELECT usuTipo FROM tblUsuario WHERE idUsuario = ?');
        $st->execute([$id]);
        $tipo = $st->fetchColumn();
        if ($tipo === false) {
            flash('Usuário não encontrado.', 'erro');
        } elseif ($tipo === 'Administrador') {
            flash('Administradores não podem ser excluídos por aqui.', 'erro');
        } else {
            try {
                $pdo->prepare('DELETE FROM tblUsuario WHERE idUsuario = ?')->execute([$id]);
                flash('Usuário excluído.');
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') throw $e;
                flash('Não dá para excluir: este usuário tem pedidos registrados.', 'erro');
            }
        }
    }
    header('Location: ' . url('admin/usuarios.php'));
    exit;
}

$detalhe = null;
if (isset($_GET['id'])) {
    $st = $pdo->prepare('SELECT idUsuario, usuNome, usuEmail, usuTelefone, usuTipo, usuDataCriacao FROM tblUsuario WHERE idUsuario = ?');
    $st->execute([(int)$_GET['id']]);
    $detalhe = $st->fetch();
    if (!$detalhe) {
        flash('Usuário não encontrado.', 'erro');
        header('Location: ' . url('admin/usuarios.php'));
        exit;
    }
    $contar = function (string $tabela, string $coluna) use ($pdo, $detalhe): int {
        $st = $pdo->prepare("SELECT COUNT(*) FROM $tabela WHERE $coluna = ?");
        $st->execute([$detalhe['idUsuario']]);
        return (int)$st->fetchColumn();
    };
    $atividade = [
        'Favoritos' => $contar('tblFavorito', 'favIdUsuario'),
        'Avaliações' => $contar('tblAvaliacao', 'avaIdUsuario'),
        'Perguntas' => $contar('tblPergunta', 'perIdUsuario'),
    ];
}

$q = isset($_GET['q']) && is_string($_GET['q']) ? trim(mb_substr($_GET['q'], 0, 80)) : '';
$lista = [];
if (!$detalhe) {
    $where = '1=1';
    $par = [];
    if ($q !== '') {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $where = '(usuNome LIKE :a OR usuEmail LIKE :b)';
        $par = [':a' => $like, ':b' => $like];
    }
    $st = $pdo->prepare("SELECT idUsuario, usuNome, usuEmail, usuTipo, usuDataCriacao FROM tblUsuario WHERE $where ORDER BY usuDataCriacao DESC LIMIT 300");
    $st->execute($par);
    $lista = $st->fetchAll();
}

$menuAtivo = 'usuarios';
$tituloAdmin = 'Usuários';
require_once __DIR__ . '/../includes/admin_topo.php';
?>
<div class="admin-topo"><h1>Usuários</h1></div>

<?php if ($detalhe): ?>
    <div class="admin-cartao">
        <dl class="dados">
            <dt>Nome</dt><dd><?= e($detalhe['usuNome']) ?></dd>
            <dt>E-mail</dt><dd><?= e($detalhe['usuEmail']) ?></dd>
            <dt>Telefone</dt><dd><?= e($detalhe['usuTelefone'] ?: '—') ?></dd>
            <dt>Tipo</dt><dd><?= e($detalhe['usuTipo']) ?></dd>
            <dt>Criado em</dt><dd><?= e(date('d/m/Y H:i', strtotime($detalhe['usuDataCriacao']))) ?></dd>
            <?php foreach ($atividade as $rotulo => $n): ?><dt><?= $rotulo ?></dt><dd><?= $n ?></dd><?php endforeach; ?>
        </dl>
        <div class="acoes-form"><a class="btn btn-contorno" href="<?= url('admin/usuarios.php') ?>">Voltar</a></div>
    </div>
<?php else: ?>
    <form action="<?= url('admin/usuarios.php') ?>" method="get" role="search">
        <input class="admin-busca" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nome ou e-mail" aria-label="Buscar">
    </form>

    <div class="tabela-rolagem">
        <table class="admin-tabela">
            <thead><tr><th>Nome</th><th>E-mail</th><th>Criado em</th><th>Ações</th></tr></thead>
            <tbody>
                <?php foreach ($lista as $u): $admin = $u['usuTipo'] === 'Administrador'; ?>
                    <tr>
                        <td><?= e($u['usuNome']) ?><?= $admin ? ' <span class="status">Admin</span>' : '' ?></td>
                        <td><?= e($u['usuEmail']) ?></td>
                        <td><?= e(date('d/m/Y', strtotime($u['usuDataCriacao']))) ?></td>
                        <td class="acoes-link">
                            <a href="<?= url('admin/usuarios.php?id=' . (int)$u['idUsuario']) ?>">Ver</a>
                            <?php if (!$admin): ?> · <?= formAcao('excluir', (int)$u['idUsuario'], 'Excluir', 'link-perigo', 'Excluir o usuário ' . $u['usuNome'] . '? Favoritos e avaliações dele também serão apagados.') ?><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$lista): ?><tr><td colspan="4">Nenhum usuário encontrado.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/admin_fim.php'; ?>