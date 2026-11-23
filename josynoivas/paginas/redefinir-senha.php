<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$token = $_POST['token'] ?? $_GET['token'] ?? '';
$token = is_string($token) ? $token : '';
$erro = '';
$rec = false;

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $st = $pdo->prepare('SELECT idRecuperacao, recIdUsuario FROM tblRecuperacaoSenha
                         WHERE recToken = ? AND recUsadoEm IS NULL AND recExpiraEm > NOW()');
    $st->execute([hash('sha256', $token)]);
    $rec = $st->fetch();
}

if ($rec && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = (string)($_POST['senha'] ?? '');
    $conf = (string)($_POST['confirmar'] ?? '');

    if (!csrfOk($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada. Tente novamente.';
    } elseif (strlen($senha) < 8) {
        $erro = 'Use no mínimo 8 caracteres.';
    } elseif ($senha !== $conf) {
        $erro = 'As senhas não conferem.';
    } else {
        $pdo->prepare('UPDATE tblUsuario SET usuSenha = ? WHERE idUsuario = ?')
            ->execute([password_hash($senha, PASSWORD_DEFAULT), $rec['recIdUsuario']]);
        $pdo->prepare('UPDATE tblRecuperacaoSenha SET recUsadoEm = NOW() WHERE idRecuperacao = ?')->execute([$rec['idRecuperacao']]);
        header('Location: ' . url('paginas/login.php?ok=senha'));
        exit;
    }
}

$titulo = 'Nova senha';
require_once __DIR__ . '/../includes/auth_topo.php';
?>
    <h1 class="auth-titulo">Criar nova senha</h1>

    <?php if (!$rec): ?>
        <p class="aviso aviso-erro">Este link é inválido ou expirou.</p>
        <a class="auth-link" href="<?= url('paginas/recuperar-senha.php') ?>">Pedir um novo link</a>
    <?php else: ?>
        <?php if ($erro): ?><p class="aviso aviso-erro"><?= e($erro) ?></p><?php endif; ?>
        <form class="auth-form" method="post" action="<?= e(url('paginas/redefinir-senha.php')) ?>">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div class="campo">
                <label for="senha">Nova senha</label>
                <input type="password" id="senha" name="senha" placeholder="Mínimo de 8 caracteres" autocomplete="new-password" required>
            </div>
            <div class="campo">
                <label for="confirmar">Confirmar senha</label>
                <input type="password" id="confirmar" name="confirmar" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn btn-bloco">Salvar senha</button>
        </form>
    <?php endif; ?>
<?php require_once __DIR__ . '/../includes/auth_fim.php'; ?>