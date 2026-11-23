<?php

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function url(string $caminho = ''): string
{
    return BASE_URL . '/' . ltrim($caminho, '/');
}

function preco($v): string
{
    return 'R$ ' . number_format((float)$v, 2, ',', '.');
}

function configuracoes(PDO $pdo): array
{
    return $pdo->query("SELECT cfgChave, cfgValor FROM tblConfiguracao")->fetchAll(PDO::FETCH_KEY_PAIR);
}

function whatsappLink(array $cfg, string $mensagem): string
{
    $numero = preg_replace('/\D/', '', $cfg['whatsapp'] ?? '');
    return 'https://wa.me/' . $numero . '?text=' . rawurlencode($mensagem);
}

function resolverLink(?string $l): string
{
    $l = trim((string)$l);
    if ($l === '') return url('paginas/catalogo.php');
    return preg_match('~^(https?:)?//|^/~', $l) ? $l : url($l);
}

function telefoneBR(string $n): string
{
    $d = preg_replace('/\D/', '', $n);
    if (strlen($d) > 11 && substr($d, 0, 2) === '55') $d = substr($d, 2);
    if (strlen($d) === 11) return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7));
    if (strlen($d) === 10) return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6));
    return $n;
}

function icone(string $nome, int $tam = 22): string
{
    $p = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'heart'  => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/>',
        'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
        'prev'   => '<path d="m15 18-6-6 6-6"/>',
        'next'   => '<path d="m9 18 6-6-6-6"/>',
        'menu'   => '<path d="M4 7h16M4 12h16M4 17h16"/>',
    ];
    return '<svg width="' . $tam . '" height="' . $tam . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        . 'stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$nome] ?? '') . '</svg>';
}

function buscarProdutos(PDO $pdo, string $where = '1=1', array $params = [], string $ordem = 'p.proDataCriacao DESC', int $limite = 8): array
{
    $sql = "SELECT p.idProduto, p.proNome, p.proSlug, p.proPreco, p.proStatus, p.proModelagem, p.proTipoPeca, p.proDataCriacao,
                c.catNome, c.catSlug,
                (SELECT f.fotCaminho FROM tblProdutoFoto f
                  WHERE f.fotIdProduto = p.idProduto
                  ORDER BY f.fotPrincipal DESC, f.fotOrdem LIMIT 1) AS foto,
                (SELECT COALESCE(SUM(v.visQtd), 0) FROM tblVisualizacao v
                  WHERE v.visIdProduto = p.idProduto
                    AND v.visData >= CURDATE() - INTERVAL 7 DAY) AS views7
            FROM tblProduto p
            JOIN tblCategoria c ON c.idCategoria = p.proIdCategoria
            WHERE $where
            ORDER BY $ordem
            LIMIT " . (int)$limite;
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfOk($token): bool
{
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}

function estrelas(int $n): string
{
    $n = max(0, min(5, $n));
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}

function destinoSeguro($v): string
{
    if (is_string($v) && strpos($v, BASE_URL . '/') === 0 && strpos($v, '//') === false && !preg_match('/[\x00-\x1f\x7f\\\\]/', $v)) {
        return $v;
    }
    return url('index.php');
}

function cpfValido(string $cpf): bool
{
    $d = preg_replace('/\D/', '', $cpf);
    if (strlen($d) !== 11 || preg_match('/^(\d)\1{10}$/', $d)) return false;
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($c = 0; $c < $t; $c++) $soma += (int)$d[$c] * (($t + 1) - $c);
        if ((int)$d[$t] !== ((10 * $soma) % 11) % 10) return false;
    }
    return true;
}

function mascaraCpf(string $cpf): string
{
    $d = preg_replace('/\D/', '', $cpf);
    return sprintf('%s.%s.%s-%s', substr($d, 0, 3), substr($d, 3, 3), substr($d, 6, 3), substr($d, 9, 2));
}

function iniciarSessaoUsuario(array $u): void
{
    session_regenerate_id(true);
    unset($_SESSION['login_falhas'], $_SESSION['login_bloqueio']);
    $_SESSION['usuario'] = [
        'idUsuario' => (int)$u['idUsuario'],
        'usuNome'   => $u['usuNome'],
        'usuEmail'  => $u['usuEmail'],
        'usuTipo'   => $u['usuTipo'],
    ];
}

function idsFavoritos(PDO $pdo): array
{
    static $ids = null;
    if ($ids === null) {
        $ids = [];
        if (!empty($_SESSION['usuario'])) {
            $st = $pdo->prepare('SELECT favIdProduto FROM tblFavorito WHERE favIdUsuario = ?');
            $st->execute([$_SESSION['usuario']['idUsuario']]);
            $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        }
    }
    return $ids;
}

function slugify(string $t): string
{
    $t = mb_strtolower($t, 'UTF-8');
    $t = strtr($t, [
        'á' => 'a',
        'à' => 'a',
        'ã' => 'a',
        'â' => 'a',
        'ä' => 'a',
        'é' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'í' => 'i',
        'ì' => 'i',
        'î' => 'i',
        'ï' => 'i',
        'ó' => 'o',
        'ò' => 'o',
        'õ' => 'o',
        'ô' => 'o',
        'ö' => 'o',
        'ú' => 'u',
        'ù' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'ç' => 'c',
        'ñ' => 'n',
    ]);
    $t = trim(preg_replace('/[^a-z0-9]+/', '-', $t), '-');
    return $t !== '' ? $t : 'item';
}

function parseMoeda(string $v): ?float
{
    $v = str_replace(['R$', ' '], '', trim($v));
    if ($v === '') return null;
    if (strpos($v, ',') !== false) {
        $v = str_replace(',', '.', str_replace('.', '', $v));
    }
    return is_numeric($v) ? round((float)$v, 2) : null;
}

function flash(string $msg, string $tipo = 'ok'): void
{
    $_SESSION['flash'] = [$tipo, $msg];
}

function pegarFlash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function salvarImagemProporcao(array $arq, string $pasta, int $largura, int $altura): string
{
    if ($arq['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Falha no envio da imagem.');
    if ($arq['size'] > 5 * 1024 * 1024) throw new RuntimeException('Cada imagem deve ter no máximo 5 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arq['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    $info = $ext ? @getimagesize($arq['tmp_name']) : false;
    if (!$info) throw new RuntimeException('Use imagens JPG, PNG ou WebP.');
    if ($info[0] * $info[1] > 25000000) throw new RuntimeException('Imagem muito grande (máximo de 25 megapixels).');

    $dir = __DIR__ . '/../img/' . $pasta;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $nome = bin2hex(random_bytes(8));

    if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
        $img = @imagecreatefromstring(file_get_contents($arq['tmp_name']));
        if ($img) {
            $w = imagesx($img);
            $h = imagesy($img);
            $cw = $w;
            $ch = (int)round($w * $altura / $largura);
            if ($ch > $h) {
                $ch = $h;
                $cw = (int)round($h * $largura / $altura);
            }
            $x = (int)(($w - $cw) / 2);
            $y = (int)(($h - $ch) / 2);
            $tw = min($cw, $largura);
            $th = (int)round($tw * $altura / $largura);
            $dst = imagecreatetruecolor($tw, $th);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            imagecopyresampled($dst, $img, 0, 0, $x, $y, $tw, $th, $cw, $ch);
            $ok = imagejpeg($dst, "$dir/$nome.jpg", 85);
            imagedestroy($img);
            imagedestroy($dst);
            if ($ok) return "img/$pasta/$nome.jpg";
        }
    }

    if (!move_uploaded_file($arq['tmp_name'], "$dir/$nome.$ext")) throw new RuntimeException('Não foi possível salvar a imagem.');
    return "img/$pasta/$nome.$ext";
}

function salvarFotoProduto(array $arq): string
{
    return salvarImagemProporcao($arq, 'produtos', 1200, 1500);
}

function apagarArquivoImagem(string $caminho): void
{
    $base = realpath(__DIR__ . '/../img');
    $real = realpath(__DIR__ . '/../' . $caminho);
    if ($base && $real && strpos($real, $base . DIRECTORY_SEPARATOR) === 0 && is_file($real)) {
        @unlink($real);
    }
}

function formAcao(string $acao, int $id, string $rotulo, string $classe = 'link-acao', ?string $confirma = null): string
{
    return '<form method="post" class="form-inline"' . ($confirma ? ' data-confirm="' . e($confirma) . '"' : '') . '>'
        . '<input type="hidden" name="acao" value="' . e($acao) . '">'
        . '<input type="hidden" name="id" value="' . $id . '">'
        . '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">'
        . '<button type="submit" class="' . e($classe) . '">' . e($rotulo) . '</button></form>';
}

function ehAdmin(PDO $pdo): bool
{
    static $resultado = null;
    if ($resultado === null) {
        $resultado = false;
        if (!empty($_SESSION['usuario'])) {
            $st = $pdo->prepare('SELECT usuTipo, usuAtivo FROM tblUsuario WHERE idUsuario = ?');
            $st->execute([$_SESSION['usuario']['idUsuario']]);
            $u = $st->fetch();
            if ($u && (int)$u['usuAtivo'] === 1) {
                $_SESSION['usuario']['usuTipo'] = $u['usuTipo'];
                $resultado = $u['usuTipo'] === 'Administrador';
            }
        }
    }
    return $resultado;
}

function rotuloPost(string $tipo): string
{
    return $tipo === 'Perguntas' ? 'Perguntas e respostas' : $tipo;
}

function dataExtenso(string $data): string
{
    $meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    $t = strtotime($data);
    return date('j', $t) . ' de ' . $meses[(int)date('n', $t) - 1] . ' de ' . date('Y', $t);
}

function renderConteudoPost(string $texto): string
{
    $html = '';
    foreach (preg_split('/\R{2,}/', trim($texto)) as $bloco) {
        $bloco = trim($bloco);
        if ($bloco === '') continue;
        $linhas = preg_split('/\R/', $bloco);
        if (strpos($linhas[0], '## ') === 0) {
            $html .= '<h2>' . e(substr(array_shift($linhas), 3)) . '</h2>';
            $bloco = trim(implode("\n", $linhas));
            if ($bloco === '') continue;
        }
        $html .= '<p>' . nl2br(e($bloco)) . '</p>';
    }
    return $html;
}

function loginBloqueado(PDO $pdo, string $email): bool
{
    $pdo->exec('DELETE FROM tblLoginTentativa WHERE logData < NOW() - INTERVAL 1 DAY');
    $st = $pdo->prepare('SELECT COALESCE(SUM(logIp = ?), 0) AS porIp, COALESCE(SUM(logEmail = ?), 0) AS porEmail
                         FROM tblLoginTentativa WHERE logData > NOW() - INTERVAL 15 MINUTE');
    $st->execute([substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45), mb_strtolower($email)]);
    $r = $st->fetch();
    return (int)$r['porIp'] >= 10 || (int)$r['porEmail'] >= 20;
}

function loginFalhou(PDO $pdo, string $email): void
{
    $pdo->prepare('INSERT INTO tblLoginTentativa (logIp, logEmail) VALUES (?, ?)')
        ->execute([substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45), mb_strtolower($email)]);
}
