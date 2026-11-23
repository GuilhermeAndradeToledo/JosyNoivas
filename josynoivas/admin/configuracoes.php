<?php
require_once __DIR__ . '/../includes/admin_base.php';

$campos = [
    'whatsapp' => 'WhatsApp (com DDD)',
    'whatsapp_mensagem' => 'Mensagem do WhatsApp (use {produto} para o nome do vestido)',
    'barra_aviso' => 'Texto da barra do topo',
    'instagram' => 'Link do Instagram',
    'facebook' => 'Link do Facebook',
    'endereco' => 'Endereço da loja',
    'horario' => 'Horário de atendimento',
    'mapa_url' => 'Link do mapa (use o link de incorporar do Google Maps)',
    'texto_sobre' => 'Texto da página Sobre',
    'alta_procura_min' => 'Visualizações em 7 dias para o alerta de alta procura',
];
$limites = ['whatsapp_mensagem' => 255, 'barra_aviso' => 150, 'instagram' => 255, 'facebook' => 255, 'endereco' => 255, 'horario' => 255, 'mapa_url' => 500, 'texto_sobre' => 5000];

$atuais = configuracoes($pdo);
$v = [];
foreach ($campos as $k => $_) $v[$k] = (string)($atuais[$k] ?? '');
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($campos as $k => $_) $v[$k] = trim(mb_substr((string)($_POST[$k] ?? ''), 0, $limites[$k] ?? 20));

    $digitos = preg_replace('/\D/', '', $v['whatsapp']);
    if (!csrfOk($_POST['csrf'] ?? null)) {
        $erro = 'Sessão expirada. Tente novamente.';
    } elseif ($digitos !== '' && (strlen($digitos) < 10 || strlen($digitos) > 13)) {
        $erro = 'WhatsApp inválido. Informe DDD e número.';
    } elseif (!ctype_digit($v['alta_procura_min']) || (int)$v['alta_procura_min'] < 1 || (int)$v['alta_procura_min'] > 100000) {
        $erro = 'Informe um número válido para o alerta de alta procura.';
    } else {
        foreach (['instagram', 'facebook', 'mapa_url'] as $k) {
            if ($v[$k] !== '' && !preg_match('~^https?://\S+$~i', $v[$k])) {
                $erro = 'O campo "' . $campos[$k] . '" precisa começar com http:// ou https://.';
                break;
            }
        }
    }

    if (!$erro) {
        if ($digitos !== '' && strlen($digitos) <= 11) $digitos = '55' . $digitos;
        $v['whatsapp'] = $digitos;
        $st = $pdo->prepare('UPDATE tblConfiguracao SET cfgValor = ? WHERE cfgChave = ?');
        foreach ($campos as $k => $_) $st->execute([$v[$k] !== '' ? $v[$k] : null, $k]);
        flash('Configurações salvas.');
        header('Location: ' . url('admin/configuracoes.php'));
        exit;
    }
}

$menuAtivo = 'configuracoes';
$tituloAdmin = 'Configurações';
require_once __DIR__ . '/../includes/admin_topo.php';
?>
<div class="admin-topo"><h1>Configurações</h1></div>

<form class="admin-cartao" method="post" action="<?= url('admin/configuracoes.php') ?>" style="max-width:820px">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <?php if ($erro): ?><p class="aviso aviso-erro"><?= e($erro) ?></p><?php endif; ?>

    <?php foreach ($campos as $k => $rotulo): ?>
        <div class="campo">
            <label for="<?= $k ?>"><?= e($rotulo) ?></label>
            <?php if ($k === 'texto_sobre'): ?>
                <textarea id="<?= $k ?>" name="<?= $k ?>" rows="8" maxlength="5000"><?= e($v[$k]) ?></textarea>
                <small class="dica">Separe os parágrafos com uma linha em branco.</small>
            <?php else: ?>
                <input type="text" id="<?= $k ?>" name="<?= $k ?>" value="<?= e($v[$k]) ?>" maxlength="<?= $limites[$k] ?? 6 ?>">
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="acoes-form"><button type="submit" class="btn">Salvar configurações</button></div>
</form>
<?php require_once __DIR__ . '/../includes/admin_fim.php'; ?>