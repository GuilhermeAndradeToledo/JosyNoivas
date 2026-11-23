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
        $st = $pdo->prepare('SELECT ocaImagem FROM tblOcasiao WHERE idOcasiao = ?');
        $st->execute([$id]);
        $img = $st->fetchColumn();
        $pdo->prepare('DELETE FROM tblOcasiao WHERE idOcasiao = ?')->execute([$id]);
        if ($img) apagarArquivoImagem($img);
        flash('Ocasião excluída.');
    } elseif ($acao === 'salvar') {
        $form = [
            'id' => $id,
            'nome' => trim(mb_substr((string)($_POST['nome'] ?? ''), 0, 80)),
            'ordem' => (int)($_POST['ordem'] ?? 0),
            'imagem' => null,
        ];
        if (mb_strlen($form['nome']) < 2) {
            $erro = 'Informe o nome da ocasião.';
        } elseif ($form['ordem'] < 0 || $form['ordem'] > 9999) {
            $erro = 'Ordem inválida.';
        } else {
            $nova = null;
            $apagar = null;
            try {
                $atual = null;
                if ($id) {
                    $st = $pdo->prepare('SELECT ocaImagem FROM tblOcasiao WHERE idOcasiao = ?');
                    $st->execute([$id]);
                    $atual = $st->fetchColumn();
                    if ($atual === false) throw new RuntimeException('Ocasião não encontrada.');
                }
                if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $nova = salvarImagemProporcao($_FILES['imagem'], 'ocasioes', 800, 740);
                    if ($atual) $apagar = $atual;
                }
                $img = $nova ?: $atual;
                if ($id) {
                    $pdo->prepare('UPDATE tblOcasiao SET ocaNome = ?, ocaOrdem = ?, ocaImagem = ? WHERE idOcasiao = ?')
                        ->execute([$form['nome'], $form['ordem'], $img, $id]);
                } else {
                    $base = slugify($form['nome']);
                    $slug = $base;
                    $n = 1;
                    $chk = $pdo->prepare('SELECT 1 FROM tblOcasiao WHERE ocaSlug = ?');
                    while (true) {
                        $chk->execute([$slug]);
                        if (!$chk->fetchColumn()) break;
                        $slug = $base . '-' . (++$n);
                    }
                    $pdo->prepare('INSERT INTO tblOcasiao (ocaNome, ocaSlug, ocaImagem, ocaOrdem) VALUES (?, ?, ?, ?)')
                        ->execute([$form['nome'], $slug, $img, $form['ordem']]);
                }
            } catch (Throwable $e) {
                if ($nova) apagarArquivoImagem($nova);
                if (!$e instanceof RuntimeException) throw $e;
                $erro = $e->getMessage();
            }
            if (!$erro) {
                if ($apagar) apagarArquivoImagem($apagar);
                flash('Ocasião salva.');
            }
        }
    }

    if (!$erro) {
        header('Location: ' . url('admin/ocasioes.php'));
        exit;
    }
}

if ($form === null && isset($_GET['id'])) {
    $st = $pdo->prepare('SELECT * FROM tblOcasiao WHERE idOcasiao = ?');
    $st->execute([(int)$_GET['id']]);
    if ($o = $st->fetch()) {
        $form = ['id' => (int)$o['idOcasiao'], 'nome' => $o['ocaNome'], 'ordem' => (int)$o['ocaOrdem'], 'imagem' => $o['ocaImagem']];
    } else {
        flash('Ocasião não encontrada.', 'erro');
        header('Location: ' . url('admin/ocasioes.php'));
        exit;
    }
} elseif ($form === null && isset($_GET['novo'])) {
    $form = [
        'id' => 0,
        'nome' => '',
        'imagem' => null,
        'ordem' => (int)$pdo->query('SELECT COALESCE(MAX(ocaOrdem), 0) + 1 FROM tblOcasiao')->fetchColumn()
    ];
}
if ($form && $form['id'] && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $pdo->prepare('SELECT ocaImagem FROM tblOcasiao WHERE idOcasiao = ?');
    $st->execute([$form['id']]);
    $form['imagem'] = $st->fetchColumn() ?: null;
}

$lista = $form ? [] : $pdo->query('SELECT o.idOcasiao, o.ocaNome, o.ocaImagem, o.ocaOrdem,
        (SELECT COUNT(*) FROM tblProdutoOcasiao po WHERE po.pocIdOcasiao = o.idOcasiao) AS qtd
        FROM tblOcasiao o ORDER BY o.ocaOrdem, o.idOcasiao')->fetchAll();

$menuAtivo = 'ocasioes';
$tituloAdmin = 'Ocasiões';
require_once __DIR__ . '/../includes/admin_topo.php';
?>
<div class="admin-topo">
    <h1>Ocasiões</h1>
    <?php if (!$form): ?><a class="btn" href="<?= url('admin/ocasioes.php?novo=1') ?>">Nova ocasião</a><?php endif; ?>
</div>

<?php if ($form): ?>
    <form class="admin-cartao" method="post" enctype="multipart/form-data" action="<?= url('admin/ocasioes.php') ?>">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <?php if ($erro): ?><p class="aviso aviso-erro"><?= e($erro) ?></p><?php endif; ?>

        <div class="campo">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" value="<?= e($form['nome']) ?>" maxlength="80" required>
        </div>
        <div class="campo">
            <label for="ordem">Ordem na home</label>
            <input type="number" id="ordem" name="ordem" value="<?= (int)$form['ordem'] ?>" min="0" max="9999">
        </div>
        <div class="campo">
            <label for="imagem">Imagem</label>
            <div class="upload-principal">
                <?php if ($form['imagem']): ?><img src="<?= e(url($form['imagem'])) ?>" alt=""><?php else: ?>Sem imagem<?php endif; ?>
            </div>
            <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp">
            <small class="dica">Recortada automaticamente. Máximo de 5 MB.</small>
        </div>
        <div class="acoes-form">
            <button type="submit" class="btn">Salvar</button>
            <a class="btn btn-contorno" href="<?= url('admin/ocasioes.php') ?>">Cancelar</a>
        </div>
    </form>
<?php else: ?>
    <div class="tabela-rolagem">
        <table class="admin-tabela">
            <thead>
                <tr>
                    <th>Imagem</th>
                    <th>Nome</th>
                    <th>Ordem</th>
                    <th>Vestidos</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lista as $o): ?>
                    <tr>
                        <td>
                            <div class="miniatura-admin"><?php if ($o['ocaImagem']): ?><img src="<?= e(url($o['ocaImagem'])) ?>" alt=""><?php endif; ?></div>
                        </td>
                        <td><?= e($o['ocaNome']) ?></td>
                        <td><?= (int)$o['ocaOrdem'] ?></td>
                        <td><?= (int)$o['qtd'] ?></td>
                        <td class="acoes-link">
                            <a href="<?= url('admin/ocasioes.php?id=' . (int)$o['idOcasiao']) ?>">Editar</a> ·
                            <?= formAcao('excluir', (int)$o['idOcasiao'], 'Excluir', 'link-perigo', 'Excluir a ocasião ' . $o['ocaNome'] . '?') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$lista): ?><tr>
                        <td colspan="5">Nenhuma ocasião cadastrada.</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/admin_fim.php'; ?>