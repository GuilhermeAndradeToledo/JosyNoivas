<?php
require_once __DIR__ . '/../includes/admin_base.php';

$tiposPost = ['Dicas', 'Notícias', 'Perguntas', 'Tendências'];
$form = null;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if (!csrfOk($_POST['csrf'] ?? null)) {
        flash('Sessão expirada. Tente novamente.', 'erro');
    } elseif ($acao === 'excluir') {
        $st = $pdo->prepare('SELECT posCapa FROM tblPost WHERE idPost = ?');
        $st->execute([$id]);
        $capa = $st->fetchColumn();
        $pdo->prepare('DELETE FROM tblPost WHERE idPost = ?')->execute([$id]);
        if ($capa) apagarArquivoImagem($capa);
        flash('Post excluído.');
    } elseif ($acao === 'salvar') {
        $form = [
            'id' => $id,
            'titulo' => trim(mb_substr((string)($_POST['titulo'] ?? ''), 0, 200)),
            'categoria' => (string)($_POST['categoria'] ?? ''),
            'resumo' => trim(mb_substr((string)($_POST['resumo'] ?? ''), 0, 300)),
            'conteudo' => trim(mb_substr((string)($_POST['conteudo'] ?? ''), 0, 100000)),
            'status' => (string)($_POST['status'] ?? ''),
            'capa' => null,
        ];
        if (mb_strlen($form['titulo']) < 3) {
            $erro = 'Informe o título do post.';
        } elseif (!in_array($form['categoria'], $tiposPost, true)) {
            $erro = 'Escolha a categoria.';
        } elseif (mb_strlen($form['conteudo']) < 10) {
            $erro = 'Escreva o conteúdo do post.';
        } elseif (!in_array($form['status'], ['Publicado', 'Rascunho'], true)) {
            $erro = 'Escolha o status.';
        } else {
            $capaNova = null;
            $apagar = null;
            try {
                if (isset($_FILES['capa']) && $_FILES['capa']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $capaNova = salvarImagemProporcao($_FILES['capa'], 'blog', 1200, 800);
                }
                if ($id) {
                    $st = $pdo->prepare('SELECT posCapa FROM tblPost WHERE idPost = ?');
                    $st->execute([$id]);
                    $atual = $st->fetch();
                    if (!$atual) throw new RuntimeException('Post não encontrado.');
                    if ($capaNova && $atual['posCapa']) $apagar = $atual['posCapa'];
                    $pdo->prepare("UPDATE tblPost SET posTitulo = ?, posCategoria = ?, posResumo = ?, posConteudo = ?, posCapa = ?, posStatus = ?,
                                   posDataPublicacao = IF(posStatus = 'Publicado', COALESCE(posDataPublicacao, NOW()), posDataPublicacao)
                                   WHERE idPost = ?")
                        ->execute([$form['titulo'], $form['categoria'], $form['resumo'] ?: null, $form['conteudo'], $capaNova ?: $atual['posCapa'], $form['status'], $id]);
                } else {
                    $base = slugify($form['titulo']);
                    $slug = $base;
                    $n = 1;
                    $chk = $pdo->prepare('SELECT 1 FROM tblPost WHERE posSlug = ?');
                    while (true) {
                        $chk->execute([$slug]);
                        if (!$chk->fetchColumn()) break;
                        $slug = $base . '-' . (++$n);
                    }
                    $pdo->prepare("INSERT INTO tblPost (posIdAutor, posTitulo, posSlug, posCategoria, posResumo, posConteudo, posCapa, posStatus, posDataPublicacao)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, IF(? = 'Publicado', NOW(), NULL))")
                        ->execute([$_SESSION['usuario']['idUsuario'], $form['titulo'], $slug, $form['categoria'], $form['resumo'] ?: null,
                                   $form['conteudo'], $capaNova, $form['status'], $form['status']]);
                }
            } catch (Throwable $e) {
                if ($capaNova) apagarArquivoImagem($capaNova);
                if (!$e instanceof RuntimeException) throw $e;
                $erro = $e->getMessage();
            }
            if (!$erro) {
                if ($apagar) apagarArquivoImagem($apagar);
                flash('Post salvo.');
            }
        }
    }

    if (!$erro) {
        header('Location: ' . url('admin/posts.php'));
        exit;
    }
}

if ($form === null && isset($_GET['id'])) {
    $st = $pdo->prepare('SELECT * FROM tblPost WHERE idPost = ?');
    $st->execute([(int)$_GET['id']]);
    if ($p = $st->fetch()) {
        $form = ['id' => (int)$p['idPost'], 'titulo' => $p['posTitulo'], 'categoria' => $p['posCategoria'], 'resumo' => (string)$p['posResumo'],
                 'conteudo' => $p['posConteudo'], 'status' => $p['posStatus'], 'capa' => $p['posCapa']];
    } else {
        flash('Post não encontrado.', 'erro');
        header('Location: ' . url('admin/posts.php'));
        exit;
    }
} elseif ($form === null && isset($_GET['novo'])) {
    $form = ['id' => 0, 'titulo' => '', 'categoria' => 'Dicas', 'resumo' => '', 'conteudo' => '', 'status' => 'Publicado', 'capa' => null];
}
if ($form && $form['id'] && $form['capa'] === null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $pdo->prepare('SELECT posCapa FROM tblPost WHERE idPost = ?');
    $st->execute([$form['id']]);
    $form['capa'] = $st->fetchColumn() ?: null;
}

$lista = $form ? [] : $pdo->query('SELECT idPost, posTitulo, posCategoria, posStatus, posCapa, COALESCE(posDataPublicacao, posDataCriacao) AS data
                                   FROM tblPost ORDER BY data DESC LIMIT 300')->fetchAll();

$menuAtivo = 'posts';
$tituloAdmin = 'Posts do blog';
require_once __DIR__ . '/../includes/admin_topo.php';
?>
<div class="admin-topo">
    <h1><?= $form ? ($form['id'] ? 'Editar post' : 'Novo post') : 'Posts do blog' ?></h1>
    <?php if (!$form): ?><a class="btn" href="<?= url('admin/posts.php?novo=1') ?>">Novo post</a><?php endif; ?>
</div>

<?php if ($form): ?>
    <form method="post" enctype="multipart/form-data" action="<?= url('admin/posts.php') ?>">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <?php if ($erro): ?><p class="aviso aviso-erro"><?= e($erro) ?></p><?php endif; ?>

        <div class="admin-form">
            <div class="col">
                <div class="campo">
                    <label for="titulo">Título</label>
                    <input type="text" id="titulo" name="titulo" value="<?= e($form['titulo']) ?>" maxlength="200" required>
                </div>
                <div class="campo">
                    <label for="categoria">Categoria</label>
                    <select id="categoria" name="categoria">
                        <?php foreach ($tiposPost as $t): ?><option <?= $form['categoria'] === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="resumo">Resumo</label>
                    <input type="text" id="resumo" name="resumo" value="<?= e($form['resumo']) ?>" maxlength="300" placeholder="Resumo curto exibido na listagem">
                </div>
                <div class="campo">
                    <label for="conteudo">Conteúdo</label>
                    <textarea id="conteudo" name="conteudo" rows="14" placeholder="Escreva o texto do post" required><?= e($form['conteudo']) ?></textarea>
                    <small class="dica">Separe os parágrafos com uma linha em branco. Comece uma linha com ## para criar um subtítulo.</small>
                </div>
            </div>
            <div class="col">
                <div class="campo">
                    <label for="capa">Imagem de capa</label>
                    <div class="upload-principal capa">
                        <?php if ($form['capa']): ?><img src="<?= e(url($form['capa'])) ?>" alt=""><?php else: ?>Sem imagem<?php endif; ?>
                    </div>
                    <input type="file" id="capa" name="capa" accept="image/jpeg,image/png,image/webp">
                    <small class="dica">Recortada em 3:2. Máximo de 5 MB.</small>
                </div>
                <div class="campo">
                    <label>Status</label>
                    <div class="radios">
                        <?php foreach (['Publicado', 'Rascunho'] as $s): ?>
                            <label><input type="radio" name="status" value="<?= $s ?>" <?= $form['status'] === $s ? 'checked' : '' ?>> <?= $s ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="acoes-form" style="margin-top:28px">
            <button type="submit" class="btn">Salvar post</button>
            <a class="btn btn-contorno" href="<?= url('admin/posts.php') ?>">Cancelar</a>
        </div>
    </form>
<?php else: ?>
    <div class="tabela-rolagem">
        <table class="admin-tabela">
            <thead><tr><th>Capa</th><th>Título</th><th>Categoria</th><th>Data</th><th>Status</th><th>Ações</th></tr></thead>
            <tbody>
                <?php foreach ($lista as $p): ?>
                    <tr>
                        <td><div class="miniatura-admin larga"><?php if ($p['posCapa']): ?><img src="<?= e(url($p['posCapa'])) ?>" alt=""><?php endif; ?></div></td>
                        <td class="celula-texto"><?= e($p['posTitulo']) ?></td>
                        <td><?= e($p['posCategoria']) ?></td>
                        <td><?= e(date('d/m/Y', strtotime($p['data']))) ?></td>
                        <td><span class="status <?= $p['posStatus'] === 'Rascunho' ? 'escuro' : '' ?>"><?= e($p['posStatus']) ?></span></td>
                        <td class="acoes-link">
                            <a href="<?= url('admin/posts.php?id=' . (int)$p['idPost']) ?>">Editar</a> ·
                            <?= formAcao('excluir', (int)$p['idPost'], 'Excluir', 'link-perigo', 'Excluir o post ' . $p['posTitulo'] . '?') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$lista): ?><tr><td colspan="6">Nenhum post ainda.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/admin_fim.php'; ?>