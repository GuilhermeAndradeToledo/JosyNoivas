<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

if (empty($_SESSION['usuario'])) {
    header('Location: ' . url('paginas/login.php?voltar=' . urlencode(url('paginas/conta.php'))));
    exit;
}

$st = $pdo->prepare('SELECT idUsuario, usuNome, usuEmail, usuSenha, usuCPF, usuTelefone FROM tblUsuario WHERE idUsuario = ?');
$st->execute([$_SESSION['usuario']['idUsuario']]);
$u = $st->fetch();
if (!$u) {
    header('Location: ' . url('paginas/logout.php'));
    exit;
}

$v = ['nome' => $u['usuNome'], 'cpf' => (string)$u['usuCPF'], 'telefone' => (string)$u['usuTelefone'], 'email' => $u['usuEmail']];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $k => $_) $v[$k] = trim(mb_substr((string)($_POST[$k] ?? ''), 0, 150));
    $atual = (string)($_POST['senha_atual'] ?? '');
    $nova = (string)($_POST['senha_nova'] ?? '');
    $telDigitos = preg_replace('/\D/', '', $v['telefone']);

    if (!csrfOk($_POST['csrf'] ?? null)) $erros['geral'] = 'Sessão expirada. Tente novamente.';
    if (mb_strlen($v['nome']) < 3) $erros['nome'] = 'Informe seu nome completo.';
    if (!cpfValido($v['cpf'])) $erros['cpf'] = 'CPF inválido.';
    if (strlen($telDigitos) < 10 || strlen($telDigitos) > 11) $erros['telefone'] = 'Informe DDD e telefone.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $erros['email'] = 'E-mail inválido.';
    if ($atual !== '' || $nova !== '') {
        if (!password_verify($atual, $u['usuSenha'])) $erros['senha_atual'] = 'Senha atual incorreta.';
        if (strlen($nova) < 8) $erros['senha_nova'] = 'Use no mínimo 8 caracteres.';
    }
    if (mb_strtolower($v['email']) !== mb_strtolower($u['usuEmail']) && !password_verify($atual, $u['usuSenha'])) {
        $erros['senha_atual'] = 'Para trocar o e-mail, informe a senha atual.';
    }

    if (!$erros) {
        $sql = 'UPDATE tblUsuario SET usuNome = ?, usuEmail = ?, usuCPF = ?, usuTelefone = ?';
        $par = [$v['nome'], $v['email'], mascaraCpf($v['cpf']), telefoneBR($telDigitos)];
        if ($nova !== '') {
            $sql .= ', usuSenha = ?';
            $par[] = password_hash($nova, PASSWORD_DEFAULT);
        }
        $par[] = $u['idUsuario'];
        try {
            $pdo->prepare($sql . ' WHERE idUsuario = ?')->execute($par);
            $_SESSION['usuario']['usuNome'] = $v['nome'];
            $_SESSION['usuario']['usuEmail'] = $v['email'];
            header('Location: ' . url('paginas/conta.php?ok=1'));
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') throw $e;
            $erros['email'] = 'Este e-mail já está em uso.';
        }
    }
}

$titulo = 'Minha conta';
$paginaCheia = true;
require_once __DIR__ . '/../includes/header.php';

$linhas = [
    [['nome', 'Nome completo', 'text', 'name', ''], ['cpf', 'CPF', 'text', 'off', 'cpf']],
    [['telefone', 'Telefone', 'tel', 'tel', 'telefone'], ['email', 'E-mail', 'email', 'email', '']],
];
?>

<section class="conta-corpo">
    <nav class="conta-menu" aria-label="Minha conta">
        <h1>Minha conta</h1>
        <a class="ativo" href="<?= url('paginas/conta.php') ?>">Meus dados</a>
        <a href="<?= url('paginas/favoritos.php') ?>">Meus favoritos</a>
        <?php if (ehAdmin($pdo)): ?>
            <a href="<?= url('admin/index.php') ?>">Painel admin</a>
        <?php endif; ?>
        <a href="<?= url('paginas/logout.php') ?>">Sair</a>
    </nav>

    <form class="conta-cartao" method="post" action="<?= url('paginas/conta.php') ?>" novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <h2>Meus dados</h2>

        <?php if (($_GET['ok'] ?? '') === '1'): ?><p class="aviso aviso-ok">Dados atualizados.</p><?php endif; ?>
        <?php if (!empty($erros['geral'])): ?><p class="aviso aviso-erro"><?= e($erros['geral']) ?></p><?php endif; ?>

        <?php foreach ($linhas as $linha): ?>
            <div class="linha-campos">
                <?php foreach ($linha as [$nome, $rotulo, $tipo, $auto, $mascara]): ?>
                    <div class="campo">
                        <label for="<?= $nome ?>"><?= $rotulo ?></label>
                        <input type="<?= $tipo ?>" id="<?= $nome ?>" name="<?= $nome ?>" value="<?= e($v[$nome]) ?>" maxlength="150" autocomplete="<?= $auto ?>" <?= $mascara ? 'data-mascara="' . $mascara . '"' : '' ?> required>
                        <?php if (!empty($erros[$nome])): ?><span class="campo-erro"><?= e($erros[$nome]) ?></span><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <h3>Alterar senha</h3>
        <div class="linha-campos">
            <div class="campo">
                <label for="senha_atual">Senha atual</label>
                <input type="password" id="senha_atual" name="senha_atual" placeholder="••••••••" autocomplete="current-password">
                <?php if (!empty($erros['senha_atual'])): ?><span class="campo-erro"><?= e($erros['senha_atual']) ?></span><?php endif; ?>
            </div>
            <div class="campo">
                <label for="senha_nova">Nova senha</label>
                <input type="password" id="senha_nova" name="senha_nova" placeholder="Mínimo de 8 caracteres" autocomplete="new-password">
                <?php if (!empty($erros['senha_nova'])): ?><span class="campo-erro"><?= e($erros['senha_nova']) ?></span><?php endif; ?>
            </div>
        </div>
        <p class="auth-aviso">Deixe a senha em branco para mantê-la.</p>

        <div class="acoes-form">
            <button type="submit" class="btn">Salvar alterações</button>
            <a class="btn btn-contorno" href="<?= url('paginas/conta.php') ?>">Cancelar</a>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>