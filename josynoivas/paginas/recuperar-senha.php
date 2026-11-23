<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$erro = '';
$enviado = false;
$linkLocal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(mb_substr((string)($_POST['email'] ?? ''), 0, 150));

    if (!csrfOk($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada. Tente novamente.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } else {
        $st = $pdo->prepare('SELECT idUsuario, usuNome FROM tblUsuario WHERE usuEmail = ? AND usuAtivo = 1');
        $st->execute([$email]);
        $u = $st->fetch();

        if ($u) {
            $pdo->prepare('DELETE FROM tblRecuperacaoSenha WHERE recIdUsuario = ? AND recUsadoEm IS NULL')->execute([$u['idUsuario']]);
            $token = bin2hex(random_bytes(32));
            $pdo->prepare('INSERT INTO tblRecuperacaoSenha (recIdUsuario, recToken, recExpiraEm) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')
                ->execute([$u['idUsuario'], hash('sha256', $token)]);

            $host = parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost';
            $link = SITE_URL . url('paginas/redefinir-senha.php?token=' . $token);

            $corpo = "Olá, " . explode(' ', $u['usuNome'])[0] . "!\n\nPara criar uma nova senha, acesse o link abaixo (vale por 1 hora):\n" . $link . "\n\nSe você não pediu isso, ignore este e-mail.";
            $assunto = '=?UTF-8?B?' . base64_encode('Recuperação de senha - ' . SITE_NAME) . '?=';
            $cabecalhos = "From: " . SITE_NAME . " <noreply@" . preg_replace('/:\d+$/', '', $host) . ">\r\nContent-Type: text/plain; charset=UTF-8";
            @mail($email, $assunto, $corpo, $cabecalhos);

            if (in_array(preg_replace('/:\d+$/', '', $host), ['localhost', '127.0.0.1'], true)) {
                $linkLocal = $link;
            }
        }
        $enviado = true;
    }
}

$titulo = 'Recuperar senha';
require_once __DIR__ . '/../includes/auth_topo.php';
?>
<h1 class="auth-titulo">Esqueci minha senha</h1>

<?php if ($enviado): ?>
    <p class="aviso aviso-ok">Se o e-mail estiver cadastrado, enviamos um link para criar uma nova senha. Ele vale por 1 hora.</p>
    <?php if ($linkLocal): ?>
        <p class="auth-aviso">Ambiente local (sem envio de e-mail): <a class="auth-link" href="<?= e($linkLocal) ?>">abrir link de redefinição</a></p>
    <?php endif; ?>
<?php else: ?>
    <?php if ($erro): ?><p class="aviso aviso-erro"><?= e($erro) ?></p><?php endif; ?>
    <p class="auth-aviso">Informe o e-mail da sua conta e enviaremos um link para criar uma nova senha.</p>
    <form class="auth-form" method="post" action="<?= e(url('paginas/recuperar-senha.php')) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <div class="campo">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="seu@email.com" maxlength="150" required autocomplete="email">
        </div>
        <button type="submit" class="btn btn-bloco">Enviar link</button>
    </form>
<?php endif; ?>

<a class="auth-link" href="<?= url('paginas/login.php') ?>">Voltar para entrar</a>
<?php require_once __DIR__ . '/../includes/auth_fim.php'; ?>