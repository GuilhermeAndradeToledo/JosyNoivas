<?php
require_once __DIR__ . '/../includes/admin_base.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$produto = null;
if ($id) {
    $st = $pdo->prepare('SELECT * FROM tblProduto WHERE idProduto = ?');
    $st->execute([$id]);
    $produto = $st->fetch();
    if (!$produto) {
        flash('Vestido não encontrado.', 'erro');
        header('Location: ' . url('admin/vestidos.php'));
        exit;
    }
}

$categorias = $pdo->query('SELECT idCategoria, catNome FROM tblCategoria ORDER BY catOrdem')->fetchAll();
$tamanhos   = $pdo->query('SELECT idTamanho, tamNome FROM tblTamanho ORDER BY tamOrdem')->fetchAll();
$ocasioes   = $pdo->query('SELECT idOcasiao, ocaNome FROM tblOcasiao ORDER BY ocaOrdem')->fetchAll();
$idsCat = array_column($categorias, 'idCategoria');
$idsTam = array_column($tamanhos, 'idTamanho');
$idsOca = array_column($ocasioes, 'idOcasiao');

$v = [
    'nome' => $produto['proNome'] ?? '',
    'categoria' => $produto['proIdCategoria'] ?? '',
    'preco' => isset($produto['proPreco']) ? number_format((float)$produto['proPreco'], 2, ',', '') : '',
    'descricao' => $produto['proDescricao'] ?? '',
    'estilo' => $produto['proEstilo'] ?? '',
    'modelagem' => $produto['proModelagem'] ?? '',
    'cor' => $produto['proCor'] ?? '',
    'tipo' => $produto['proTipoPeca'] ?? '',
    'status' => $produto['proStatus'] ?? 'Disponível',
    'local' => $produto['proLocal'] ?? 'Vitrine',
    'quantidade' => $produto['proQuantidade'] ?? 1,
    'destaque' => (int)($produto['proDestaque'] ?? 0),
];
$selTam = [];
$selOca = [];
if ($id) {
    $st = $pdo->prepare('SELECT ptaIdTamanho FROM tblProdutoTamanho WHERE ptaIdProduto = ?');
    $st->execute([$id]);
    $selTam = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    $st = $pdo->prepare('SELECT pocIdOcasiao FROM tblProdutoOcasiao WHERE pocIdProduto = ?');
    $st->execute([$id]);
    $selOca = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
}
$erros = [];
$st = $pdo->prepare('SELECT idProduto, proNome FROM tblProduto WHERE idProduto <> ? ORDER BY proNome');
$st->execute([$id]);
$outros = $st->fetchAll();
$idsOutros = array_map('intval', array_column($outros, 'idProduto'));
$selRel = [];
if ($id) {
    $st = $pdo->prepare('SELECT prrIdRelacionado FROM tblProdutoRelacionado WHERE prrIdProduto = ? ORDER BY prrOrdem');
    $st->execute([$id]);
    $selRel = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['nome']       = trim(mb_substr((string)($_POST['nome'] ?? ''), 0, 120));
    $v['categoria']  = (int)($_POST['categoria'] ?? 0);
    $v['preco']      = trim((string)($_POST['preco'] ?? ''));
    $v['descricao']  = trim(mb_substr((string)($_POST['descricao'] ?? ''), 0, 5000));
    $v['estilo']     = trim(mb_substr((string)($_POST['estilo'] ?? ''), 0, 60));
    $v['modelagem']  = trim(mb_substr((string)($_POST['modelagem'] ?? ''), 0, 60));
    $v['cor']        = trim(mb_substr((string)($_POST['cor'] ?? ''), 0, 40));
    $v['tipo']       = trim(mb_substr((string)($_POST['tipo'] ?? ''), 0, 40));
    $v['status']     = (string)($_POST['status'] ?? '');
    $v['local']      = (string)($_POST['local'] ?? '');
    $v['quantidade'] = (int)($_POST['quantidade'] ?? 1);
    $v['destaque']   = isset($_POST['destaque']) ? 1 : 0;
    $selTam = array_values(array_intersect($idsTam, array_map('intval', (array)($_POST['tamanhos'] ?? []))));
    $selOca = array_values(array_intersect($idsOca, array_map('intval', (array)($_POST['ocasioes'] ?? []))));
    $selRel = array_slice(array_values(array_intersect(array_map('intval', (array)($_POST['relacionados'] ?? [])), $idsOutros)), 0, 8);
    $remover = array_map('intval', (array)($_POST['remover'] ?? []));
    $preco = parseMoeda($v['preco']);

    if (!csrfOk($_POST['csrf'] ?? null)) $erros['geral'] = 'Sessão expirada. Tente novamente.';
    if (mb_strlen($v['nome']) < 2) $erros['nome'] = 'Informe o nome do vestido.';
    if (!in_array($v['categoria'], array_map('intval', $idsCat), true)) $erros['categoria'] = 'Escolha a categoria.';
    if ($preco === null || $preco <= 0 || $preco > 99999999) $erros['preco'] = 'Informe um preço válido.';
    if (!in_array($v['status'], ['Disponível', 'Indisponível'], true)) $erros['status'] = 'Status inválido.';
    if (!in_array($v['local'], ['Vitrine', 'Estoque'], true)) $erros['local'] = 'Local inválido.';
    if ($v['quantidade'] < 0 || $v['quantidade'] > 9999) $erros['quantidade'] = 'Quantidade inválida.';

    $extrasEnviados = [];
    if (!empty($_FILES['extras']['name'][0])) {
        foreach ($_FILES['extras']['name'] as $i => $_) {
            if ($_FILES['extras']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            $extrasEnviados[] = [
                'name' => $_FILES['extras']['name'][$i],
                'tmp_name' => $_FILES['extras']['tmp_name'][$i],
                'error' => $_FILES['extras']['error'][$i],
                'size' => $_FILES['extras']['size'][$i],
            ];
        }
    }
    $principalEnviada = isset($_FILES['principal']) && $_FILES['principal']['error'] !== UPLOAD_ERR_NO_FILE ? $_FILES['principal'] : null;

    if (!$erros) {
        $novos = [];
        $apagar = [];
        try {
            $pdo->beginTransaction();

            $opcionais = [$v['descricao'] ?: null, $v['estilo'] ?: null, $v['modelagem'] ?: null, $v['tipo'] ?: null, $v['cor'] ?: null];
            if ($id) {
                $pdo->prepare('UPDATE tblProduto SET proIdCategoria = ?, proNome = ?, proDescricao = ?, proPreco = ?, proEstilo = ?,
                               proModelagem = ?, proTipoPeca = ?, proCor = ?, proStatus = ?, proLocal = ?, proQuantidade = ?, proDestaque = ?
                               WHERE idProduto = ?')
                    ->execute([
                        $v['categoria'],
                        $v['nome'],
                        $opcionais[0],
                        $preco,
                        $opcionais[1],
                        $opcionais[2],
                        $opcionais[3],
                        $opcionais[4],
                        $v['status'],
                        $v['local'],
                        $v['quantidade'],
                        $v['destaque'],
                        $id
                    ]);
            } else {
                $base = slugify($v['nome']);
                $slug = $base;
                $n = 1;
                $chk = $pdo->prepare('SELECT 1 FROM tblProduto WHERE proSlug = ?');
                while (true) {
                    $chk->execute([$slug]);
                    if (!$chk->fetchColumn()) break;
                    $slug = $base . '-' . (++$n);
                }
                $pdo->prepare('INSERT INTO tblProduto (proIdCategoria, proNome, proSlug, proDescricao, proPreco, proEstilo, proModelagem,
                               proTipoPeca, proCor, proStatus, proLocal, proQuantidade, proDestaque) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute([
                        $v['categoria'],
                        $v['nome'],
                        $slug,
                        $opcionais[0],
                        $preco,
                        $opcionais[1],
                        $opcionais[2],
                        $opcionais[3],
                        $opcionais[4],
                        $v['status'],
                        $v['local'],
                        $v['quantidade'],
                        $v['destaque']
                    ]);
                $id = (int)$pdo->lastInsertId();
            }

            $pdo->prepare('DELETE FROM tblProdutoTamanho WHERE ptaIdProduto = ?')->execute([$id]);
            $ins = $pdo->prepare('INSERT INTO tblProdutoTamanho (ptaIdProduto, ptaIdTamanho) VALUES (?, ?)');
            foreach ($selTam as $t) $ins->execute([$id, $t]);

            $pdo->prepare('DELETE FROM tblProdutoOcasiao WHERE pocIdProduto = ?')->execute([$id]);
            $ins = $pdo->prepare('INSERT INTO tblProdutoOcasiao (pocIdProduto, pocIdOcasiao) VALUES (?, ?)');
            foreach ($selOca as $o) $ins->execute([$id, $o]);

            $pdo->prepare('DELETE FROM tblProdutoRelacionado WHERE prrIdProduto = ?')->execute([$id]);
            $ins = $pdo->prepare('INSERT INTO tblProdutoRelacionado (prrIdProduto, prrIdRelacionado, prrOrdem) VALUES (?, ?, ?)');
            foreach ($selRel as $i => $r) $ins->execute([$id, $r, $i]);

            foreach ($remover as $idFoto) {
                $st = $pdo->prepare('SELECT fotCaminho FROM tblProdutoFoto WHERE idFoto = ? AND fotIdProduto = ? AND fotPrincipal = 0');
                $st->execute([$idFoto, $id]);
                if ($caminho = $st->fetchColumn()) {
                    $pdo->prepare('DELETE FROM tblProdutoFoto WHERE idFoto = ?')->execute([$idFoto]);
                    $apagar[] = $caminho;
                }
            }

            if ($principalEnviada) {
                $st = $pdo->prepare('SELECT fotCaminho FROM tblProdutoFoto WHERE fotIdProduto = ? AND fotPrincipal = 1');
                $st->execute([$id]);
                foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $antigo) $apagar[] = $antigo;
                $pdo->prepare('DELETE FROM tblProdutoFoto WHERE fotIdProduto = ? AND fotPrincipal = 1')->execute([$id]);
                $caminho = salvarFotoProduto($principalEnviada);
                $novos[] = $caminho;
                $pdo->prepare('INSERT INTO tblProdutoFoto (fotIdProduto, fotCaminho, fotOrdem, fotPrincipal) VALUES (?, ?, 0, 1)')->execute([$id, $caminho]);
            }

            $st = $pdo->prepare('SELECT COUNT(*) FROM tblProdutoFoto WHERE fotIdProduto = ? AND fotPrincipal = 0');
            $st->execute([$id]);
            if ((int)$st->fetchColumn() + count($extrasEnviados) > 8) {
                throw new RuntimeException('Use no máximo 8 fotos extras.');
            }
            $st = $pdo->prepare('SELECT COALESCE(MAX(fotOrdem), 0) FROM tblProdutoFoto WHERE fotIdProduto = ?');
            $st->execute([$id]);
            $ordem = (int)$st->fetchColumn();
            foreach ($extrasEnviados as $arq) {
                $caminho = salvarFotoProduto($arq);
                $novos[] = $caminho;
                $pdo->prepare('INSERT INTO tblProdutoFoto (fotIdProduto, fotCaminho, fotOrdem, fotPrincipal) VALUES (?, ?, ?, 0)')->execute([$id, $caminho, ++$ordem]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            foreach ($novos as $c) apagarArquivoImagem($c);
            if (!$e instanceof RuntimeException) throw $e;
            $erros['geral'] = $e->getMessage();
        }

        if (!$erros) {
            foreach ($apagar as $c) apagarArquivoImagem($c);
            flash($produto ? 'Vestido atualizado.' : 'Vestido cadastrado.');
            header('Location: ' . url('admin/vestidos.php'));
            exit;
        }
    }
}

$fotoPrincipal = null;
$extras = [];
if ($id) {
    $st = $pdo->prepare('SELECT idFoto, fotCaminho, fotPrincipal FROM tblProdutoFoto WHERE fotIdProduto = ? ORDER BY fotPrincipal DESC, fotOrdem, idFoto');
    $st->execute([$id]);
    foreach ($st->fetchAll() as $f) {
        if ($f['fotPrincipal']) $fotoPrincipal = $f;
        else $extras[] = $f;
    }
}
$estilos    = $pdo->query("SELECT DISTINCT proEstilo FROM tblProduto WHERE proEstilo IS NOT NULL AND proEstilo <> '' ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN);
$modelagens = $pdo->query("SELECT DISTINCT proModelagem FROM tblProduto WHERE proModelagem IS NOT NULL AND proModelagem <> '' ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN);
$tipos      = $pdo->query("SELECT DISTINCT proTipoPeca FROM tblProduto WHERE proTipoPeca IS NOT NULL AND proTipoPeca <> '' ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN);

$menuAtivo = 'vestidos';
$tituloAdmin = $produto ? 'Editar vestido' : 'Novo vestido';
require_once __DIR__ . '/../includes/admin_topo.php';

function erroCampo(array $erros, string $k): string
{
    return !empty($erros[$k]) ? '<span class="campo-erro">' . e($erros[$k]) . '</span>' : '';
}
?>
<div class="admin-topo">
    <h1><?= e($tituloAdmin) ?></h1>
</div>

<?php if (!empty($erros['geral'])): ?><p class="aviso aviso-erro"><?= e($erros['geral']) ?></p><?php endif; ?>

<form method="post" enctype="multipart/form-data" action="<?= url('admin/vestido-editar.php' . ($id ? '?id=' . $id : '')) ?>" novalidate>
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="admin-form">
        <div class="col">
            <div class="campo">
                <label for="nome">Nome do vestido</label>
                <input type="text" id="nome" name="nome" value="<?= e($v['nome']) ?>" maxlength="120" required>
                <?= erroCampo($erros, 'nome') ?>
            </div>
            <div class="campo">
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria" required>
                    <option value="">Escolha</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int)$c['idCategoria'] ?>" <?= (int)$v['categoria'] === (int)$c['idCategoria'] ? 'selected' : '' ?>><?= e($c['catNome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= erroCampo($erros, 'categoria') ?>
            </div>
            <div class="campo">
                <label for="preco">Preço (R$)</label>
                <input type="text" id="preco" name="preco" value="<?= e($v['preco']) ?>" inputmode="decimal" placeholder="450,00" required>
                <?= erroCampo($erros, 'preco') ?>
            </div>
            <div class="campo">
                <label>Tamanhos disponíveis</label>
                <div class="checks">
                    <?php foreach ($tamanhos as $t): ?>
                        <label><input type="checkbox" name="tamanhos[]" value="<?= (int)$t['idTamanho'] ?>" <?= in_array((int)$t['idTamanho'], $selTam, true) ? 'checked' : '' ?>> <?= e($t['tamNome']) ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="campo">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao" rows="6" maxlength="5000" placeholder="Descrição do vestido"><?= e($v['descricao']) ?></textarea>
            </div>

            <div class="campo-linha">
                <div class="campo">
                    <label for="estilo">Estilo</label>
                    <input type="text" id="estilo" name="estilo" value="<?= e($v['estilo']) ?>" maxlength="60" list="l-estilo" placeholder="Clássico, romântico...">
                    <datalist id="l-estilo"><?php foreach ($estilos as $x): ?><option value="<?= e($x) ?>"><?php endforeach; ?></datalist>
                </div>
                <div class="campo">
                    <label for="modelagem">Modelagem</label>
                    <input type="text" id="modelagem" name="modelagem" value="<?= e($v['modelagem']) ?>" maxlength="60" list="l-modelagem" placeholder="Princesa, sereia...">
                    <datalist id="l-modelagem"><?php foreach ($modelagens as $x): ?><option value="<?= e($x) ?>"><?php endforeach; ?></datalist>
                </div>
            </div>
            <div class="campo-linha">
                <div class="campo">
                    <label for="cor">Cor</label>
                    <input type="text" id="cor" name="cor" value="<?= e($v['cor']) ?>" maxlength="40">
                </div>
                <div class="campo">
                    <label for="tipo">Tipo de peça</label>
                    <input type="text" id="tipo" name="tipo" value="<?= e($v['tipo']) ?>" maxlength="40" list="l-tipo" placeholder="Vestido, Grinaldas...">
                    <datalist id="l-tipo"><?php foreach ($tipos as $x): ?><option value="<?= e($x) ?>"><?php endforeach; ?></datalist>
                </div>
            </div>
            <?php if ($ocasioes): ?>
                <div class="campo">
                    <label>Ocasiões</label>
                    <div class="checks">
                        <?php foreach ($ocasioes as $o): ?>
                            <label><input type="checkbox" name="ocasioes[]" value="<?= (int)$o['idOcasiao'] ?>" <?= in_array((int)$o['idOcasiao'], $selOca, true) ? 'checked' : '' ?>> <?= e($o['ocaNome']) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="campo">
                <label for="relacionados">Você também pode gostar (manual)</label>
                <select id="relacionados" name="relacionados[]" multiple size="8">
                    <?php foreach ($outros as $o): ?>
                        <option value="<?= (int)$o['idProduto'] ?>" <?= in_array((int)$o['idProduto'], $selRel, true) ? 'selected' : '' ?>><?= e($o['proNome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="dica">Ctrl (ou Cmd) para marcar vários, até 8. Vazio = automático pela categoria.</small>
            </div>
            <div class="campo-linha">
                <div class="campo">
                    <label for="local">Local da peça</label>
                    <select id="local" name="local">
                        <?php foreach (['Vitrine', 'Estoque'] as $l): ?><option <?= $v['local'] === $l ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="quantidade">Quantidade</label>
                    <input type="number" id="quantidade" name="quantidade" value="<?= (int)$v['quantidade'] ?>" min="0" max="9999">
                    <?= erroCampo($erros, 'quantidade') ?>
                </div>
            </div>
            <div class="campo">
                <div class="checks"><label><input type="checkbox" name="destaque" value="1" <?= $v['destaque'] ? 'checked' : '' ?>> Mostrar nos destaques da home</label></div>
            </div>
        </div>

        <div class="col">
            <div class="campo">
                <label for="principal">Foto principal (4:5)</label>
                <div class="upload-principal">
                    <?php if ($fotoPrincipal): ?><img src="<?= e(url($fotoPrincipal['fotCaminho'])) ?>" alt=""><?php else: ?>Sem foto<?php endif; ?>
                </div>
                <input type="file" id="principal" name="principal" accept="image/jpeg,image/png,image/webp">
                <small class="dica">A foto é recortada automaticamente em 4:5. Máximo de 5 MB.</small>
            </div>

            <div class="campo">
                <label>Fotos extras (carrossel)</label>
                <?php if ($extras): ?>
                    <div class="extras">
                        <?php foreach ($extras as $x): ?>
                            <div class="extra">
                                <div class="thumb"><img src="<?= e(url($x['fotCaminho'])) ?>" alt=""></div>
                                <label><input type="checkbox" name="remover[]" value="<?= (int)$x['idFoto'] ?>"> remover</label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <input type="file" name="extras[]" multiple accept="image/jpeg,image/png,image/webp">
                <small class="dica">Até 8 fotos extras.</small>
            </div>

            <div class="campo">
                <label>Disponibilidade</label>
                <div class="radios">
                    <?php foreach (['Disponível', 'Indisponível'] as $s): ?>
                        <label><input type="radio" name="status" value="<?= $s ?>" <?= $v['status'] === $s ? 'checked' : '' ?>> <?= $s ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="acoes-form" style="margin-top:28px">
        <button type="submit" class="btn">Salvar alterações</button>
        <a class="btn btn-contorno" href="<?= url('admin/vestidos.php') ?>">Cancelar</a>
    </div>
</form>
<?php require_once __DIR__ . '/../includes/admin_fim.php'; ?>