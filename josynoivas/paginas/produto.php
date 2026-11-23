<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? trim($_GET['slug']) : '';

$st = $pdo->prepare("SELECT p.*, c.catNome, c.catSlug,
        (SELECT COALESCE(SUM(v.visQtd), 0) FROM tblVisualizacao v
          WHERE v.visIdProduto = p.idProduto AND v.visData >= CURDATE() - INTERVAL 7 DAY) AS views7
    FROM tblProduto p JOIN tblCategoria c ON c.idCategoria = p.proIdCategoria
    WHERE p.proSlug = ?");
$st->execute([$slug]);
$p = $st->fetch();

if (!$p) {
    http_response_code(404);
    $titulo = 'Peça não encontrada';
    require_once __DIR__ . '/../includes/header.php';
    echo '<p>Não encontramos essa peça. <a href="' . url('paginas/catalogo.php') . '"><u>Ver o catálogo</u></a></p>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$id = (int)$p['idProduto'];
$cfg = configuracoes($pdo);
$usuario = $_SESSION['usuario'] ?? null;
$erroPergunta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'pergunta') {
    $nome  = trim(mb_substr((string)($_POST['nome'] ?? ''), 0, 100));
    $texto = trim(mb_substr((string)($_POST['texto'] ?? ''), 0, 1000));
    if (!csrfOk($_POST['csrf'] ?? null)) {
        $erroPergunta = 'Sessão expirada. Tente novamente.';
    } elseif ($nome === '' || mb_strlen($texto) < 5) {
        $erroPergunta = 'Informe seu nome e escreva a pergunta.';
    } else {
        $st = $pdo->prepare('INSERT INTO tblPergunta (perIdProduto, perIdUsuario, perNome, perTexto) VALUES (?, ?, ?, ?)');
        $st->execute([$id, $usuario['idUsuario'] ?? null, $nome, $texto]);
        header('Location: ' . url('paginas/produto.php?slug=' . urlencode($slug) . '&pergunta=ok#perguntas'));
        exit;
    }
}

$erroAvaliacao = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'avaliacao' && $usuario) {
    $nota = (int)($_POST['nota'] ?? 0);
    $textoA = trim(mb_substr((string)($_POST['texto'] ?? ''), 0, 1000));
    if (!csrfOk($_POST['csrf'] ?? null)) {
        $erroAvaliacao = 'Sessão expirada. Tente novamente.';
    } elseif ($nota < 1 || $nota > 5) {
        $erroAvaliacao = 'Escolha uma nota de 1 a 5.';
    } else {
        try {
            $pdo->prepare('INSERT INTO tblAvaliacao (avaIdProduto, avaIdUsuario, avaNota, avaTexto) VALUES (?, ?, ?, ?)')
                ->execute([$id, $usuario['idUsuario'], $nota, $textoA !== '' ? $textoA : null]);
            header('Location: ' . url('paginas/produto.php?slug=' . urlencode($slug) . '&avaliacao=ok#avaliacoes'));
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') throw $e;
            $erroAvaliacao = 'Você já avaliou esta peça.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($usuario['usuTipo'] ?? '') !== 'Administrador') {
    $pdo->prepare('UPDATE tblProduto SET proVisualizacoes = proVisualizacoes + 1 WHERE idProduto = ?')->execute([$id]);
    $pdo->prepare('INSERT INTO tblVisualizacao (visIdProduto, visData, visQtd) VALUES (?, CURDATE(), 1)
                   ON DUPLICATE KEY UPDATE visQtd = visQtd + 1')->execute([$id]);
}

$st = $pdo->prepare('SELECT fotCaminho FROM tblProdutoFoto WHERE fotIdProduto = ? ORDER BY fotPrincipal DESC, fotOrdem, idFoto');
$st->execute([$id]);
$fotos = $st->fetchAll(PDO::FETCH_COLUMN);

$st = $pdo->prepare('SELECT t.idTamanho, t.tamNome FROM tblProdutoTamanho pt
                     JOIN tblTamanho t ON t.idTamanho = pt.ptaIdTamanho
                     WHERE pt.ptaIdProduto = ? ORDER BY t.tamOrdem');
$st->execute([$id]);
$tamanhosProduto = $st->fetchAll();

$tabela = $pdo->query('SELECT tamNome, tamBusto, tamCintura, tamQuadril FROM tblTamanho ORDER BY tamOrdem')->fetchAll();

$st = $pdo->prepare("SELECT perNome, perTexto, perResposta FROM tblPergunta
                     WHERE perIdProduto = ? AND perStatus = 'Respondida' AND perPublica = 1
                     ORDER BY perDataResposta DESC LIMIT 5");
$st->execute([$id]);
$perguntas = $st->fetchAll();

$st = $pdo->prepare("SELECT COUNT(*) AS n, AVG(avaNota) AS media FROM tblAvaliacao WHERE avaIdProduto = ? AND avaStatus = 'Aprovada'");
$st->execute([$id]);
$resumo = $st->fetch();

$st = $pdo->prepare("SELECT a.avaNota, a.avaTexto, u.usuNome FROM tblAvaliacao a
                     JOIN tblUsuario u ON u.idUsuario = a.avaIdUsuario
                     WHERE a.avaIdProduto = ? AND a.avaStatus = 'Aprovada'
                     ORDER BY a.avaData DESC LIMIT 10");
$st->execute([$id]);
$avaliacoes = $st->fetchAll();

$minha = false;
if ($usuario) {
    $st = $pdo->prepare('SELECT avaStatus FROM tblAvaliacao WHERE avaIdProduto = ? AND avaIdUsuario = ?');
    $st->execute([$id, $usuario['idUsuario']]);
    $minha = $st->fetchColumn();
}

$relacionados = buscarProdutos($pdo, 'p.idProduto IN (SELECT prrIdRelacionado FROM tblProdutoRelacionado WHERE prrIdProduto = :id)', [':id' => $id], 'p.proDataCriacao DESC', 4)
    ?: buscarProdutos($pdo, 'p.proIdCategoria = :cat AND p.idProduto <> :id2', [':cat' => $p['proIdCategoria'], ':id2' => $id], 'p.proDataCriacao DESC', 4);

$indisp = $p['proStatus'] === 'Indisponível';
$alta   = !$indisp && (int)$p['views7'] >= (int)($cfg['alta_procura_min'] ?? 30);
$meta   = $p['catNome'] . ($p['proModelagem'] ? ' · ' . $p['proModelagem'] : '');
$urlWa  = url('paginas/whatsapp.php?produto=' . $id) . ($indisp ? '&aviso=1' : '');
$novo   = !$indisp && strtotime($p['proDataCriacao']) >= strtotime('-15 days');
$rotuloWa = $indisp ? 'Avise-me quando disponível' : 'Falar no WhatsApp';
$msgWa  = $indisp
    ? 'Olá, gostaria de ser avisada quando o vestido ' . $p['proNome'] . ' estiver disponível'
    : str_replace('{produto}', $p['proNome'], $cfg['whatsapp_mensagem'] ?? 'Olá, tenho interesse no vestido {produto}');

$titulo = $p['proNome'];
$paginaCheia = true;
require_once __DIR__ . '/../includes/header.php';
?>

<section class="faixa-migalhas">
    <p class="migalhas">
        <a href="<?= url('index.php') ?>">Início</a> /
        <a href="<?= e(url('paginas/catalogo.php?categoria=' . urlencode($p['catSlug']))) ?>"><?= e($p['catNome']) ?></a> /
        <?= e($p['proNome']) ?>
    </p>
</section>

<section class="produto">
    <div class="galeria">
        <div class="foto-principal">
            <?php if ($fotos): ?>
                <img id="foto-principal" src="<?= e(url($fotos[0])) ?>" alt="<?= e($p['proNome']) ?>">
            <?php else: ?>
                <span class="foto-vazia">Foto 4:5</span>
            <?php endif; ?>
        </div>
        <?php if (count($fotos) > 1): ?>
            <div class="miniaturas">
                <?php foreach ($fotos as $i => $foto): ?>
                    <button type="button" class="miniatura <?= $i === 0 ? 'ativo' : '' ?>" data-src="<?= e(url($foto)) ?>" aria-label="Foto <?= $i + 1 ?>">
                        <img src="<?= e(url($foto)) ?>" alt="" loading="lazy">
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="produto-info">
        <span class="card-meta"><?= e($meta) ?></span>
        <h1><?= e($p['proNome']) ?></h1>
        <span class="tag-inline <?= $indisp ? 'escura' : '' ?>"><?= $indisp ? 'Indisponível' : 'Disponível' ?></span>
        <?php if ($novo): ?><span class="tag-inline novo">Novo</span><?php endif; ?>

        <?php if ($alta): ?>
            <p class="alerta-procura">Alta procura: este vestido foi muito visto esta semana</p>
        <?php endif; ?>

        <p class="preco-prod"><?= preco($p['proPreco']) ?></p>

        <?php if ($tamanhosProduto): ?>
            <span class="rotulo-peq">Tamanho</span>
            <div class="tamanhos-prod">
                <?php foreach ($tamanhosProduto as $t): ?>
                    <button type="button" class="tam" data-tam="<?= (int)$t['idTamanho'] ?>" aria-pressed="false"><?= e($t['tamNome']) ?></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a class="link-medidas" href="#tabela-medidas">Ver tabela de medidas →</a>
        <p class="nota">Os ajustes são feitos na loja. Prove antes de alugar para garantir o caimento.</p>

        <a class="btn btn-bloco" data-wa data-base="<?= e($urlWa) ?>" href="<?= e($urlWa) ?>" target="_blank" rel="noopener"><?= $rotuloWa ?></a>
        <p class="nota-peq">Mensagem enviada: “<?= e($msgWa) ?>” + link da página.</p>
        <button type="button" class="btn btn-contorno btn-bloco" data-compartilhar data-titulo="<?= e($p['proNome']) ?>">Compartilhar</button>
    </div>
</section>

<section class="detalhes">
    <?php if (!empty($p['proDescricao'])): ?>
        <div class="bloco-det">
            <h2>Sobre o <?= $p['catSlug'] === 'noivas' || $p['catSlug'] === 'festa' ? 'vestido' : 'produto' ?></h2>
            <p class="texto-det"><?= nl2br(e($p['proDescricao'])) ?></p>
        </div>
    <?php endif; ?>

    <div class="bloco-det" id="tabela-medidas">
        <h2>Tabela de medidas</h2>
        <table class="tabela-med">
            <thead>
                <tr>
                    <th>Tamanho</th>
                    <th>Busto (cm)</th>
                    <th>Cintura (cm)</th>
                    <th>Quadril (cm)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tabela as $l): ?>
                    <tr>
                        <td><?= e($l['tamNome']) ?></td>
                        <td><?= e($l['tamBusto']) ?></td>
                        <td><?= e($l['tamCintura']) ?></td>
                        <td><?= e($l['tamQuadril']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="bloco-det" id="perguntas">
        <h2>Perguntas e respostas</h2>

        <?php if (($_GET['pergunta'] ?? '') === 'ok'): ?>
            <p class="aviso aviso-ok">Pergunta enviada! A loja responde em breve.</p>
        <?php endif; ?>

        <?php foreach ($perguntas as $q): ?>
            <div class="pergunta">
                <strong><?= e($q['perTexto']) ?></strong>
                <p><?= e($q['perResposta']) ?></p>
            </div>
        <?php endforeach; ?>
        <?php if (!$perguntas): ?><p class="texto-det">Ainda não há perguntas respondidas sobre esta peça.</p><?php endif; ?>

        <button type="button" class="btn btn-contorno btn-bloco" data-abrir-pergunta>Fazer uma pergunta</button>

        <form class="form-pergunta" id="form-pergunta" method="post" action="<?= e(url('paginas/produto.php?slug=' . urlencode($slug)) . '#perguntas') ?>" <?= $erroPergunta ? '' : 'hidden' ?>>
            <?php if ($erroPergunta): ?><p class="aviso aviso-erro"><?= e($erroPergunta) ?></p><?php endif; ?>
            <input type="hidden" name="acao" value="pergunta">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <input type="text" name="nome" maxlength="100" placeholder="Seu nome" required value="<?= e($_POST['nome'] ?? ($usuario['usuNome'] ?? '')) ?>">
            <textarea name="texto" rows="4" maxlength="1000" placeholder="Escreva sua pergunta" required><?= e($_POST['texto'] ?? '') ?></textarea>
            <button type="submit" class="btn">Enviar pergunta</button>
        </form>
    </div>

    <div class="bloco-det" id="avaliacoes">
        <h2>Avaliações</h2>
        <?php if (($_GET['avaliacao'] ?? '') === 'ok'): ?>
            <p class="aviso aviso-ok">Avaliação enviada! Ela aparece depois da aprovação da loja.</p>
        <?php endif; ?>
        <?php if ((int)$resumo['n'] > 0): ?>
            <p class="estrelas"><?= estrelas((int)round($resumo['media'])) ?> &nbsp;<?= number_format((float)$resumo['media'], 1, ',', '.') ?> · <?= (int)$resumo['n'] ?> avaliações</p>
            <?php foreach ($avaliacoes as $a): ?>
                <div class="pergunta">
                    <strong><?= e(explode(' ', $a['usuNome'])[0]) ?> · <?= estrelas((int)$a['avaNota']) ?></strong>
                    <?php if ($a['avaTexto']): ?><p><?= e($a['avaTexto']) ?></p><?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="texto-det">Ainda sem avaliações.</p>
        <?php endif; ?>

        <?php if (!$usuario): ?>
            <a class="link-medidas" href="<?= e(url('paginas/login.php?voltar=' . urlencode(url('paginas/produto.php?slug=' . urlencode($slug))))) ?>">Entre para avaliar →</a>
        <?php elseif ($minha): ?>
            <p class="texto-det">Sua avaliação: <?= $minha === 'Pendente' ? 'aguardando aprovação da loja.' : 'publicada.' ?></p>
        <?php else: ?>
            <form class="form-pergunta" method="post" action="<?= e(url('paginas/produto.php?slug=' . urlencode($slug)) . '#avaliacoes') ?>">
                <?php if ($erroAvaliacao): ?><p class="aviso aviso-erro"><?= e($erroAvaliacao) ?></p><?php endif; ?>
                <input type="hidden" name="acao" value="avaliacao">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <select name="nota" required>
                    <option value="">Sua nota</option>
                    <?php for ($n = 5; $n >= 1; $n--): ?><option value="<?= $n ?>"><?= estrelas($n) ?></option><?php endfor; ?>
                </select>
                <textarea name="texto" rows="3" maxlength="1000" placeholder="Conte como foi (opcional)"></textarea>
                <button type="submit" class="btn">Enviar avaliação</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php if ($relacionados): ?>
    <section class="relacionados">
        <h2>Você também pode gostar</h2>
        <div class="grade-4">
            <?php foreach ($relacionados as $r): ?>
                <a class="faixa-item" href="<?= url('paginas/produto.php?slug=' . urlencode($r['proSlug'])) ?>">
                    <div class="faixa-foto">
                        <?php if ($r['foto']): ?>
                            <img src="<?= e(url($r['foto'])) ?>" alt="<?= e($r['proNome']) ?>" loading="lazy">
                        <?php else: ?>
                            <span class="foto-vazia">Foto 4:5</span>
                        <?php endif; ?>
                    </div>
                    <span><?= e($r['proNome']) ?> · <?= e($r['catNome']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<div class="wa-fixo">
    <span class="wa-fixo-preco"><?= preco($p['proPreco']) ?></span>
    <a class="btn" data-wa data-base="<?= e($urlWa) ?>" href="<?= e($urlWa) ?>" target="_blank" rel="noopener"><?= $rotuloWa ?></a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>