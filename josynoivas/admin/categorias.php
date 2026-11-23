<?php
require_once __DIR__ . '/../includes/admin_base.php';

$form = null;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if (!csrfOk($_POST['csrf'] ?? null)) {
        flash('Sessão expirada. Tente novamente.', 'erro');
    } elseif ($acao === 'excluir') {
        $st = $pdo->prepare('SELECT COUNT(*) FROM tblProduto WHERE proIdCategoria = ?');
        $st->execute([$id]);
        if ((int)$st->fetchColumn() > 0) {
            flash('Esta categoria tem vestidos. Mova ou exclua os vestidos antes.', 'erro');
        } else {
            $pdo->prepare('DELETE FROM tblCategoria WHERE idCategoria = ?')->execute([$id]);
            flash('Categoria excluída.');
        }
    } elseif ($acao === 'salvar') {
        $form = [
            'id' => $id,
            'nome' => trim(mb_substr((string)($_POST['nome'] ?? ''), 0, 100)),
            'ordem' => (int)($_POST['ordem'] ?? 0),
            'cor' => (string)($_POST['cor'] ?? ''),
        ];
        if (mb_strlen($form['nome']) < 2) {
            $erro = 'Informe o nome da categoria.';
        } elseif ($form['ordem'] < 0 || $form['ordem'] > 9999) {
            $erro = 'Ordem inválida.';
        } elseif (!preg_match('/^#[0-9a-fA-F]{6}$/', $form['cor'])) {
            $erro = 'Cor inválida.';
        } else {
            if ($id) {
                $pdo->prepare('UPDATE tblCategoria SET catNome = ?, catOrdem = ?, catCor = ? WHERE idCategoria = ?')
                    ->execute([$form['nome'], $form['ordem'], $form['cor'], $id]);
            } else {
                $base = slugify($form['nome']);
                $slug = $base;
                $n = 1;
                $chk = $pdo->prepare('SELECT 1 FROM tblCategoria WHERE catSlug = ?');
                while (true) {
                    $chk->execute([$slug]);
                    if (!$chk->fetchColumn()) break;
                    $slug = $base . '-' . (++$n);
                }
                $pdo->prepare('INSERT INTO tblCategoria (catNome, catSlug, catOrdem, catCor) VALUES (?, ?, ?, ?)')
                    ->execute([$form['nome'], $slug, $form['ordem'], $form['cor']]);
            }
            flash('Categoria salva.');
        }
    }

    if (!$erro) {
        header('Location: ' . url('admin/categorias.php'));
        exit;
    }
}

if ($form === null && isset($_GET['id'])) {
    $st = $pdo->prepare('SELECT idCategoria, catNome, catOrdem, catCor FROM tblCategoria WHERE idCategoria = ?');
    $st->execute([(int)$_GET['id']]);
    if ($c = $st->fetch()) {
        $form = ['id' => (int)$c['idCategoria'], 'nome' => $c['catNome'], 'ordem' => (int)$c['catOrdem'], 'cor' => $c['catCor']];
    } else {
        flash('Categoria não encontrada.', 'erro');
        header('Location: ' . url('admin/categorias.php'));
        exit;
    }
} elseif ($form === null && isset($_GET['novo'])) {
    $form = ['id' => 0, 'nome' => '', 'ordem' => (int)$pdo->query('SELECT COALESCE(MAX(catOrdem), 0) + 1 FROM tblCategoria')->fetchColumn(), 'cor' => '#0b8f9f'];
}

$lista = $form ? [] : $pdo->query('SELECT c.idCategoria, c.catNome, c.catCor,
        (SELECT COUNT(*) FROM tblProduto p WHERE p.proIdCategoria = c.idCategoria) AS qtd
        FROM tblCategoria c ORDER BY c.catOrdem, c.idCategoria')->fetchAll();

$menuAtivo = 'categorias';
$tituloAdmin = 'Categorias';
require_once __DIR__ . '/../includes/admin_topo.php';
?>
<div class="admin-topo">
    <h1>Categorias</h1>
    <?php if (!$form): ?><a class="btn" href="<?= url('admin/categorias.php?novo=1') ?>">Nova categoria</a><?php endif; ?>
</div>

<?php if ($form): ?>
    <form class="admin-cartao" method="post" action="<?= url('admin/categorias.php') ?>">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <?php if ($erro): ?><p class="aviso aviso-erro"><?= e($erro) ?></p><?php endif; ?>

        <div class="campo">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" value="<?= e($form['nome']) ?>" maxlength="100" required>
        </div>
        <div class="campo-linha">
            <div class="campo">
                <label for="cor">Cor</label>
                <input type="color" id="cor" name="cor" value="<?= e($form['cor']) ?>" class="campo-cor">
            </div>
            <div class="campo">
                <label for="ordem">Ordem no menu</label>
                <input type="number" id="ordem" name="ordem" value="<?= (int)$form['ordem'] ?>" min="0" max="9999">
            </div>
        </div>
        <div class="acoes-form">
            <button type="submit" class="btn">Salvar</button>
            <a class="btn btn-contorno" href="<?= url('admin/categorias.php') ?>">Cancelar</a>
        </div>
    </form>
<?php else: ?>
    <div class="tabela-rolagem">
        <table class="admin-tabela">
            <thead><tr><th>Cor</th><th>Nome</th><th>Vestidos</th><th>Ações</th></tr></thead>
            <tbody>
                <?php foreach ($lista as $c): ?>
                    <tr>
                        <td><span class="cor-ponto" style="background:<?= e($c['catCor']) ?>"></span><?= e(strtoupper($c['catCor'])) ?></td>
                        <td><?= e($c['catNome']) ?></td>
                        <td><?= (int)$c['qtd'] ?></td>
                        <td class="acoes-link">
                            <a href="<?= url('admin/categorias.php?id=' . (int)$c['idCategoria']) ?>">Editar</a> ·
                            <?= formAcao('excluir', (int)$c['idCategoria'], 'Excluir', 'link-perigo', 'Excluir a categoria ' . $c['catNome'] . '?') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$lista): ?><tr><td colspan="4">Nenhuma categoria cadastrada.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/admin_fim.php'; ?>