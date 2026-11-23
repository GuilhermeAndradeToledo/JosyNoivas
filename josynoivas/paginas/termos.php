<?php
$titulo = 'Termos de uso';
$paginaCheia = true;
require_once __DIR__ . '/../includes/header.php';

$tel = !empty($cfg['whatsapp']) ? telefoneBR($cfg['whatsapp']) : '';
$canal = $tel ? 'pelo WhatsApp ' . $tel : 'pela página Sobre e contato';

$texto = <<<'TXT'
## 1. Aceite
Ao acessar ou usar este site, você concorda com estes Termos e com a Política de privacidade. Se não concordar, não use os serviços que exigem cadastro.

## 2. Sobre o site
Este site é uma vitrine da Josy Noivas, loja de aluguel de vestidos de noiva, festa, ternos e acessórios. Aqui você conhece as peças, vê detalhes e fala com a loja pelo WhatsApp. O site não realiza venda, cobrança nem reserva automática.

## 3. Como funciona o aluguel
Reserva, prova, ajustes, valor final, sinal, datas de retirada e devolução e condições de uso da peça são combinados diretamente com a loja, por WhatsApp ou pessoalmente. Preços, fotos e disponibilidade exibidos são de referência e podem mudar ou conter diferenças de tom por causa da tela. A disponibilidade deve ser confirmada com a loja antes de qualquer decisão.

## 4. Sua conta
Para favoritar, avaliar e enviar perguntas identificadas, você cria uma conta. Você declara ser maior de 18 anos e se compromete a informar dados verdadeiros (inclusive CPF próprio), manter a senha em sigilo e avisar a loja sobre uso indevido. Você responde pelas ações feitas com a sua conta. A loja pode suspender ou excluir contas com dados falsos, uso abusivo ou violação destes Termos.

## 5. Perguntas e avaliações
Perguntas e avaliações passam por moderação antes de aparecerem. Não são permitidos conteúdos ofensivos, discriminatórios, falsos, de propaganda, com dados pessoais de terceiros ou que violem direitos. A loja pode recusar ou remover esse conteúdo. Ao enviar, você autoriza a exibição pública do seu primeiro nome e do texto, sem custo, enquanto o conteúdo estiver publicado. Você pode pedir a remoção a qualquer momento.

## 6. Uso permitido
Você não pode: tentar acessar áreas restritas ou dados de outros usuários; usar robôs ou coleta automática de dados; sobrecarregar ou interferir no funcionamento do site; copiar o conteúdo para fins comerciais.

## 7. Propriedade intelectual
Textos, fotos, marca, logotipo e layout pertencem à Josy Noivas ou são usados com autorização (Lei nº 9.610/1998 e Lei nº 9.279/1996). É proibido copiar, reproduzir ou reutilizar sem permissão por escrito.

## 8. Privacidade e proteção de dados
O tratamento dos seus dados segue a LGPD (Lei nº 13.709/2018) e está descrito na Política de privacidade, que explica quais dados coletamos, para quê, por quanto tempo e como exercer seus direitos.

## 9. Responsabilidade
Nos esforçamos para manter o site correto e disponível, mas ele é oferecido como está, e pode ficar indisponível por manutenção ou falhas fora do nosso controle. A loja não responde por conteúdo de sites de terceiros (como WhatsApp, Instagram e Google Maps) acessados por links. Nada aqui limita direitos garantidos pelo Código de Defesa do Consumidor (Lei nº 8.078/1990).

## 10. Mudanças
Estes Termos podem ser atualizados, inclusive quando o site passar a oferecer carrinho, venda e pagamento online. A versão em vigor é sempre a publicada nesta página, com a data de atualização. O uso continuado após a mudança indica concordância.

## 11. Lei aplicável e foro
Estes Termos seguem as leis brasileiras. Fica eleito o foro da comarca do domicílio do consumidor para resolver conflitos.
TXT;
?>

<article class="artigo">
    <div class="artigo-coluna">
        <h1>Termos de uso</h1>
        <p class="artigo-meta">Última atualização: outubro de 2026</p>
        <div class="artigo-texto"><?= renderConteudoPost($texto) ?>
            <h2>12. Contato</h2>
            <p>Dúvidas sobre estes Termos? Fale com a Josy Noivas <?= e($canal) ?>.</p>
        </div>
    </div>
</article>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>