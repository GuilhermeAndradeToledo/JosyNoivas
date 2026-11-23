<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$voltar = $_GET['voltar'] ?? null;
if (!empty($_SESSION['usuario'])) {
    header('Location: ' . destinoSeguro($voltar));
    exit;
}

$v = ['nome' => '', 'cpf' => '', 'telefone' => '', 'email' => ''];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $k => $_) $v[$k] = trim(mb_substr((string)($_POST[$k] ?? ''), 0, 150));
    $senha = (string)($_POST['senha'] ?? '');
    $telDigitos = preg_replace('/\D/', '', $v['telefone']);

    if (!csrfOk($_POST['csrf'] ?? null)) $erros['geral'] = 'Sessão expirada. Tente novamente.';
    if (mb_strlen($v['nome']) < 3) $erros['nome'] = 'Informe seu nome completo.';
    if (!cpfValido($v['cpf'])) $erros['cpf'] = 'CPF inválido.';
    if (strlen($telDigitos) < 10 || strlen($telDigitos) > 11) $erros['telefone'] = 'Informe DDD e telefone.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $erros['email'] = 'E-mail inválido.';
    if (strlen($senha) < 8) $erros['senha'] = 'Use no mínimo 8 caracteres.';
    if (empty($_POST['aceite'])) $erros['aceite'] = 'Para criar a conta, aceite os Termos de uso e a Política de privacidade.';

    if (!$erros) {
        try {
            $st = $pdo->prepare('INSERT INTO tblUsuario (usuNome, usuEmail, usuSenha, usuCPF, usuTelefone) VALUES (?, ?, ?, ?, ?)');
            $st->execute([$v['nome'], $v['email'], password_hash($senha, PASSWORD_DEFAULT), mascaraCpf($v['cpf']), telefoneBR($telDigitos)]);
            iniciarSessaoUsuario([
                'idUsuario' => $pdo->lastInsertId(), 'usuNome' => $v['nome'],
                'usuEmail' => $v['email'], 'usuTipo' => 'Cliente',
            ]);
            header('Location: ' . destinoSeguro($voltar));
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erros['email'] = 'Este e-mail já está cadastrado.';
            } else {
                throw $e;
            }
        }
    }
}

$titulo = 'Cadastrar';
$abaAtiva = 'cadastrar';
require_once __DIR__ . '/../includes/auth_topo.php';

$campos = [
    ['nome', 'Nome completo', 'text', 'Seu nome', 'name', ''],
    ['cpf', 'CPF', 'text', '000.000.000-00', 'off', 'cpf'],
    ['telefone', 'Telefone', 'tel', '(12) 90000-0000', 'tel', 'telefone'],
    ['email', 'E-mail', 'email', 'seu@email.com', 'email', ''],
];
?>
    <?php if (!empty($erros['geral'])): ?><p class="aviso aviso-erro"><?= e($erros['geral']) ?></p><?php endif; ?>

    <form class="auth-form" method="post" action="<?= e(url('paginas/cadastro.php') . (isset($_GET['voltar']) && is_string($_GET['voltar']) ? '?voltar=' . urlencode($_GET['voltar']) : '')) ?>" novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <?php foreach ($campos as [$nome, $rotulo, $tipo, $ph, $auto, $mascara]): ?>
            <div class="campo">
                <label for="<?= $nome ?>"><?= $rotulo ?></label>
                <input type="<?= $tipo ?>" id="<?= $nome ?>" name="<?= $nome ?>" value="<?= e($v[$nome]) ?>" placeholder="<?= e($ph) ?>" maxlength="150" autocomplete="<?= $auto ?>" <?= $mascara ? 'data-mascara="' . $mascara . '"' : '' ?> required>
                <?php if (!empty($erros[$nome])): ?><span class="campo-erro"><?= e($erros[$nome]) ?></span><?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="campo">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" placeholder="Mínimo de 8 caracteres" autocomplete="new-password" required>
            <?php if (!empty($erros['senha'])): ?><span class="campo-erro"><?= e($erros['senha']) ?></span><?php endif; ?>
        </div>
        <div class="campo campo-aceite">
            <label class="aceite">
                <input type="checkbox" name="aceite" value="1" required <?= !empty($_POST['aceite']) ? 'checked' : '' ?>>
                <span>Li e aceito os <a href="<?= url('paginas/termos.php') ?>" target="_blank" rel="noopener"><u>Termos de uso</u></a> e a <a href="<?= url('paginas/privacidade.php') ?>" target="_blank" rel="noopener"><u>Política de privacidade</u></a>.</span>
            </label>
            <?php if (!empty($erros['aceite'])): ?><span class="campo-erro"><?= e($erros['aceite']) ?></span><?php endif; ?>
        </div>
        <button type="submit" class="btn btn-bloco">Criar conta</button>
    </form>
<?php require_once __DIR__ . '/../includes/auth_fim.php'; ?>