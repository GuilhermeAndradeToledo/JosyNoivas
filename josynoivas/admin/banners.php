<?php
require_once __DIR__ . '/../includes/admin_base.php';

$form = null;
$erro = '';

function linkBannerValido(string $l): bool
{
    return $l === '' || preg_match('~^https?://\S+$~i', $l) || preg_match('~^[a-zA-Z0-9_\-/][a-zA-Z0-9_\-./?=&#%]*$~', $l);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if (!csrfOk($_POST['csrf'] ?? null)) {
        flash('Sessão expirada. Tente novamente.', 'erro');
    } elseif ($acao === 'excluir') {
        $st = $pdo->prepare('SELECT banImgDesktop, banImgMobile FROM tblBanner WHERE idBanner = ?');
        $st->execute([$id]);
        $imgs = $st->fetch();
        $pdo->prepare('DELETE FROM tblBanner WHERE idBanner = ?')->execute([$id]);
        if ($imgs) foreach ($imgs as $c) if ($c) apagarArquivoImagem($c);
        flash('Banner excluído.');
    } elseif ($acao === 'salvar') {
        $form = [
            'id' => $id,
            'titulo' => trim(mb_substr((string)($_POST['titulo'] ?? ''), 0, 150)),
            'texto' => trim(mb_substr((string)($_POST['texto'] ?? ''), 0, 255)),
            'botao' => trim(mb_substr((string)($_POST['botao'] ?? ''), 0, 60)),
            'link' => trim(mb_substr((string)($_POST['link'] ?? ''), 0, 255)),
            'ordem' => (int)($_POST['ordem'] ?? 0),
            'status' => (string)($_POST['status'] ?? ''),
            'desktop' => null,
            'mobile' => null,
        ];
        if (mb_strlen($form['titulo']) < 2) {
            $erro = 'Informe o título do banner.';
        } elseif (!linkBannerValido($form['link'])) {
            $erro = 'Link inválido. Use um endereço do site (ex.: paginas/catalogo.php) ou uma URL completa.';
        } elseif ($form['ordem'] < 0 || $form['ordem'] > 9999) {
            $erro = 'Ordem inválida.';
        } elseif (!in_array($form['status'], ['Ativo', 'Inativo'], true)) {
            $erro = 'Escolha o status.';
        } else {
            $novos = [];
            $apagar = [];
            try {
                $atual = ['banImgDesktop' => null, 'banImgMobile' => null];
                if ($id) {
                    $st = $pdo->prepare('SELECT banImgDesktop, banImgMobile FROM tblBanner WHERE idBanner = ?');
                    $st->execute([$id]);
                    $atual = $st->fetch();
                    if (!$atual) throw new RuntimeException('Banner não encontrado.');
                }
                $desktop = $atual['banImgDesktop'];
                $mobile = $atual['banImgMobile'];
                if (isset($_FILES['img_desktop']) && $_FILES['img_desktop']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $novos[] = $desktop2 = salvarImagemProporcao($_FILES['img_desktop'], 'banners', 1440, 640);
                    if ($desktop) $apagar[] = $desktop;
                    $desktop = $desktop2;
                }
                if (isset($_FILES['img_mobile']) && $_FILES['img_mobile']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $novos[] = $mobile2 = salvarImagemProporcao($_FILES['img_mobile'], 'banners', 390, 460);
                    if ($mobile) $apagar[] = $mobile;
                    $mobile = $mobile2;
                }
                $par = [$form['titulo'], $form['texto'] ?: null, $form['botao'] ?: null, $form['link'] ?: null, $desktop, $mobile, $form['ordem'], $form['status']];
                if ($id) {
                    $pdo->prepare('UPDATE tblBanner SET banTitulo = ?, banTexto = ?, banTextoBotao = ?, banLink = ?, banImgDesktop = ?, banImgMobile = ?, banOrdem = ?, banStatus = ? WHERE idBanner = ?')
                        ->execute(array_merge($par, [$id]));
                } else {
                    $pdo->prepare('INSERT INTO tblBanner (banTitulo, banTexto, banTextoBotao, banLink, banImgDesktop, banImgMobile, banOrdem, banStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                        ->execute($par);
                }
            } catch (Throwable $e) {
                foreach ($novos as $c) apagarArquivoImagem($c);
                if (!$e instanceof RuntimeException) throw $e;
                $erro = $e->getMessage();
            }
            if (!$erro) {
                foreach ($apagar as $c) apagarArquivoImagem($c);
                flash('Banner salvo.');
            }
        }
    }

    if (!$erro) {
        header('Location: ' . url('admin/banners.php'));
        exit;
    }
}

if ($form === null && isset($_GET['id'])) {
    $st = $pdo->prepare('SELECT * FROM tblBanner WHERE idBanner = ?');
    $st->execute([(int)$_GET['id']]);
    if ($b = $st->fetch()) {
        $form = ['id' => (int)$b['idBanner'], 'titulo' => $b['banTitulo'], 'texto' => (string)$b['banTexto'], 'botao' => (string)$b['banTextoBotao'],
                 'link' => (string)$b['banLink'], 'ordem' => (int)$b['banOrdem'], 'status' => $b['banStatus'],
                 'desktop' => $b['banImgDesktop'], 'mobile' => $b['banImgMobile']];
    } else {
        flash('Banner não encontrado.', 'erro');
        header('Location: ' . url('admin/banners.php'));
        exit;
    }
} elseif ($form === null && isset($_GET['novo'])) {
    $form = ['id' => 0, 'titulo' => '', 'texto' => '', 'botao' => '', 'link' => '',
             'ordem' => (int)$pdo->query('SELECT COALESCE(MAX(banOrdem), 0) + 1 FROM tblBanner')->fetchColumn(),
             'status' => 'Ativo', 'desktop' => null, 'mobile' => null];
}
if ($form && $form['id'] && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $pdo->prepare('SELECT banImgDesktop, banImgMobile FROM tblBanner WHERE idBanner = ?');
    $st->execute([$form['id']]);
    if ($i = $st->fetch()) {
        $form['desktop'] = $i['banImgDesktop'];
        $form['mobile'] = $i['banImgMobile'];
    }
}

$lista = $form ? [] : $pdo->query('SELECT idBanner, banTitulo, banOrdem, banStatus, banImgDesktop FROM tblBanner ORDER BY banOrdem, idBanner')->fetchAll();

$menuAtivo = 'banners';
$tituloAdmin = 'Banners';
require_once __DIR__ . '/../includes/admin_topo.php';
?>
<div class="admin-topo">
    <h1><?= $form ? ($form['id'] ? 'Editar banner' : 'Novo banner') : 'Banners' ?></h1>
    <?php if (!$form): ?><a class="btn" href="<?= url('admin/banners.php?novo=1') ?>">Novo banner</a><?php endif; ?>
</div>

<?php if ($form): ?>
    <form method="post" enctype="multipart/form-data" action="<?= url('admin/banners.php') ?>">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <?php if ($erro): ?><p class="aviso aviso-erro"><?= e($erro) ?></p><?php endif; ?>

        <div class="admin-form">
            <div class="col">
                <div class="campo">
                    <label for="titulo">Título</label>
                    <input type="text" id="titulo" name="titulo" value="<?= e($form['titulo']) ?>" maxlength="150" required>
                </div>
                <div class="campo">
                    <label for="texto">Texto de apoio</label>
                    <input type="text" id="texto" name="texto" value="<?= e($form['texto']) ?>" maxlength="255">
                </div>
                <div class="campo">
                    <label for="botao">Texto do botão</label>
                    <input type="text" id="botao" name="botao" value="<?= e($form['botao']) ?>" maxlength="60">
                </div>
                <div class="campo">
                    <label for="link">Link do botão</label>
                    <input type="text" id="link" name="link" value="<?= e($form['link']) ?>" maxlength="255" placeholder="paginas/catalogo.php ou https://...">
                </div>
                <div class="campo">
                    <label for="ordem">Ordem no carrossel</label>
                    <input type="number" id="ordem" name="ordem" value="<?= (int)$form['ordem'] ?>" min="0" max="9999">
                </div>
            </div>
            <div class="col">
                <div class="campo">
                    <label for="img_desktop">Imagem desktop (1440 x 640)</label>
                    <div class="upload-principal desktop">
                        <?php if ($form['desktop']): ?><img src="<?= e(url($form['desktop'])) ?>" alt=""><?php else: ?>Sem imagem<?php endif; ?>
                    </div>
                    <input type="file" id="img_desktop" name="img_desktop" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="campo">
                    <label for="img_mobile">Imagem mobile (390 x 460)</label>
                    <div class="upload-principal mobile">
                        <?php if ($form['mobile']): ?><img src="<?= e(url($form['mobile'])) ?>" alt=""><?php else: ?>Sem imagem<?php endif; ?>
                    </div>
                    <input type="file" id="img_mobile" name="img_mobile" accept="image/jpeg,image/png,image/webp">
                    <small class="dica">As imagens são recortadas nessas proporções. Máximo de 5 MB.</small>
                </div>
                <div class="campo">
                    <label>Status</label>
                    <div class="radios">
                        <?php foreach (['Ativo', 'Inativo'] as $s): ?>
                            <label><input type="radio" name="status" value="<?= $s ?>" <?= $form['status'] === $s ? 'checked' : '' ?>> <?= $s ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="acoes-form" style="margin-top:28px">
            <button type="submit" class="btn">Salvar banner</button>
            <a class="btn btn-contorno" href="<?= url('admin/banners.php') ?>">Cancelar</a>
        </div>
    </form>
<?php else: ?>
    <div class="tabela-rolagem">
        <table class="admin-tabela">
            <thead><tr><th>Imagem</th><th>Título</th><th>Ordem</th><th>Status</th><th>Ações</th></tr></thead>
            <tbody>
                <?php foreach ($lista as $b): ?>
                    <tr>
                        <td><div class="miniatura-admin larga"><?php if ($b['banImgDesktop']): ?><img src="<?= e(url($b['banImgDesktop'])) ?>" alt=""><?php endif; ?></div></td>
                        <td class="celula-texto"><?= e($b['banTitulo']) ?></td>
                        <td><?= (int)$b['banOrdem'] ?></td>
                        <td><span class="status <?= $b['banStatus'] === 'Inativo' ? 'escuro' : '' ?>"><?= e($b['banStatus']) ?></span></td>
                        <td class="acoes-link">
                            <a href="<?= url('admin/banners.php?id=' . (int)$b['idBanner']) ?>">Editar</a> ·
                            <?= formAcao('excluir', (int)$b['idBanner'], 'Excluir', 'link-perigo', 'Excluir o banner ' . $b['banTitulo'] . '?') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$lista): ?><tr><td colspan="5">Nenhum banner ainda. Sem banners, a home mostra o texto padrão.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/admin_fim.php'; ?>