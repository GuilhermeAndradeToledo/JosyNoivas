<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$voltar = $_GET['voltar'] ?? null;
if (!empty($_SESSION['usuario'])) {
    header('Location: ' . destinoSeguro($voltar));
    exit;
}

$erro = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(mb_substr((string)($_POST['email'] ?? ''), 0, 150));
    $senha = (string)($_POST['senha'] ?? '');

    if (!csrfOk($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada. Tente novamente.';
    } elseif (loginBloqueado($pdo, $email)) {
        $erro = 'Muitas tentativas. Aguarde 15 minutos e tente de novo.';
    } else {
        $st = $pdo->prepare('SELECT idUsuario, usuNome, usuEmail, usuSenha, usuTipo, usuAtivo FROM tblUsuario WHERE usuEmail = ?');
        $st->execute([$email]);
        $u = $st->fetch();

        if ($u && (int)$u['usuAtivo'] === 1 && password_verify($senha, $u['usuSenha'])) {
            if (password_needs_rehash($u['usuSenha'], PASSWORD_DEFAULT)) {
                $pdo->prepare('UPDATE tblUsuario SET usuSenha = ? WHERE idUsuario = ?')
                    ->execute([password_hash($senha, PASSWORD_DEFAULT), $u['idUsuario']]);
            }
            iniciarSessaoUsuario($u);
            header('Location: ' . ($u['usuTipo'] === 'Administrador' ? url('admin/index.php') : destinoSeguro($voltar)));
            exit;
        }

        loginFalhou($pdo, $email);
        $erro = 'E-mail ou senha incorretos.';
    }
}

$titulo = 'Entrar';
$abaAtiva = 'entrar';
require_once __DIR__ . '/../includes/auth_topo.php';
?>
<?php if (($_GET['ok'] ?? '') === 'senha'): ?>
    <p class="aviso aviso-ok">Senha alterada. Entre com a nova senha.</p>
<?php endif; ?>
<?php if ($erro): ?><p class="aviso aviso-erro"><?= e($erro) ?></p><?php endif; ?>

<form class="auth-form" method="post" action="<?= e(url('paginas/login.php') . (isset($_GET['voltar']) && is_string($_GET['voltar']) ? '?voltar=' . urlencode($_GET['voltar']) : '')) ?>">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <div class="campo">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="seu@email.com" maxlength="150" required autocomplete="email">
    </div>
    <div class="campo">
        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" placeholder="••••••••" required autocomplete="current-password">
    </div>
    <button type="submit" class="btn btn-bloco">Entrar</button>
</form>

<a class="auth-link" href="<?= url('paginas/recuperar-senha.php') ?>">Esqueci minha senha</a>
<p class="auth-aviso">Entre para favoritar vestidos e comprar.</p>
<?php require_once __DIR__ . '/../includes/auth_fim.php'; ?>