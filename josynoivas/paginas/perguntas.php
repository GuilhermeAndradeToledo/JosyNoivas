<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$usuario = $_SESSION['usuario'] ?? null;
$erro = '';
$v = ['nome' => $usuario['usuNome'] ?? '', 'produto' => 0, 'texto' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['nome'] = trim(mb_substr((string)($_POST['nome'] ?? ''), 0, 100));
    $v['produto'] = (int)($_POST['produto'] ?? 0);
    $v['texto'] = trim(mb_substr((string)($_POST['texto'] ?? ''), 0, 1000));

    if (!csrfOk($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada. Tente novamente.';
    } elseif (mb_strlen($v['nome']) < 2) {
        $erro = 'Informe o seu nome.';
    } elseif (mb_strlen($v['texto']) < 5) {
        $erro = 'Escreva a sua pergunta.';
    } else {
        $idProduto = null;
        if ($v['produto'] > 0) {
            $st = $pdo->prepare('SELECT idProduto FROM tblProduto WHERE idProduto = ?');
            $st->execute([$v['produto']]);
            $idProduto = $st->fetchColumn() ?: null;
        }
        $pdo->prepare('INSERT INTO tblPergunta (perIdProduto, perIdUsuario, perNome, perTexto) VALUES (?, ?, ?, ?)')
            ->execute([$idProduto, $usuario['idUsuario'] ?? null, $v['nome'], $v['texto']]);
        header('Location: ' . url('paginas/perguntas.php?enviado=1#enviar'));
        exit;
    }
}

$lista = $pdo->query("SELECT q.perTexto, q.perResposta, p.proNome, p.proSlug
                      FROM tblPergunta q LEFT JOIN tblProduto p ON p.idProduto = q.perIdProduto
                      WHERE q.perStatus = 'Respondida' AND q.perPublica = 1
                      ORDER BY q.perDataResposta DESC LIMIT 50")->fetchAll();
$produtos = $pdo->query('SELECT idProduto, proNome FROM tblProduto ORDER BY proNome')->fetchAll();

$titulo = 'Perguntas e respostas';
$paginaCheia = true;
require_once __DIR__ . '/../includes/header.php';
?>

<section class="secao" style="padding-bottom:0">
    <div class="secao-cab" style="margin-bottom:0">
        <small>Ajuda</small>
        <h1>Perguntas e respostas</h1>
    </div>
</section>

<section class="faq-corpo">
    <div class="faq">
        <?php foreach ($lista as $i => $q): ?>
            <details <?= $i === 0 ? 'open' : '' ?>>
                <summary><?= e($q['perTexto']) ?></summary>
                <p><?= nl2br(e($q['perResposta'])) ?></p>
                <?php if ($q['proNome']): ?>
                    <p class="faq-produto">Sobre: <a href="<?= e(url('paginas/produto.php?slug=' . urlencode($q['proSlug']))) ?>"><?= e($q['proNome']) ?></a></p>
                <?php endif; ?>
            </details>
        <?php endforeach; ?>
        <?php if (!$lista): ?><p class="vazio-cat">Ainda não há perguntas respondidas. Seja a primeira pessoa a perguntar.</p><?php endif; ?>
    </div>

    <form class="form-enviar" id="enviar" method="post" action="<?= e(url('paginas/perguntas.php') . '#enviar') ?>">
        <h2>Não encontrou sua resposta?</h2>

        <?php if (($_GET['enviado'] ?? '') === '1'): ?><p class="aviso aviso-ok">Pergunta enviada! A loja responde em breve.</p><?php endif; ?>
        <?php if ($erro): ?><p class="aviso aviso-erro"><?= e($erro) ?></p><?php endif; ?>

        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <div class="campo">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" value="<?= e($v['nome']) ?>" maxlength="100" required>
        </div>
        <div class="campo">
            <label for="produto">Vestido (opcional)</label>
            <select id="produto" name="produto">
                <option value="0">Pergunta geral</option>
                <?php foreach ($produtos as $p): ?>
                    <option value="<?= (int)$p['idProduto'] ?>" <?= (int)$v['produto'] === (int)$p['idProduto'] ? 'selected' : '' ?>><?= e($p['proNome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="texto">Sua pergunta</label>
            <textarea id="texto" name="texto" rows="5" maxlength="1000" required><?= e($v['texto']) ?></textarea>
        </div>
        <button type="submit" class="btn">Enviar pergunta</button>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>