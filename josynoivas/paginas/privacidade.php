<?php
$titulo = 'Política de privacidade';
$paginaCheia = true;
require_once __DIR__ . '/../includes/header.php';

$tel = !empty($cfg['whatsapp']) ? telefoneBR($cfg['whatsapp']) : '';
$canal = $tel ? 'pelo WhatsApp ' . $tel : 'pela página Sobre e contato';

$texto = <<<'TXT'
## 1. Quem somos
A Josy Noivas, loja de aluguel de vestidos de noiva, festa, ternos e acessórios, é a controladora dos seus dados pessoais neste site, nos termos da Lei Geral de Proteção de Dados (LGPD, Lei nº 13.709/2018). Dados da controladora: Josy Noivas, Rua Ari Barroso, 32, Alto do Pedregulho, Guaratinguetá-SP, CEP 12514-130.

## 2. Quais dados coletamos
Fornecidos por você: nome, CPF, telefone, e-mail e senha (ao criar a conta); nome e texto das perguntas que envia; nota e texto das avaliações.
Gerados pelo uso: peças favoritadas; clique no botão de WhatsApp (peça, tamanho escolhido, data e hora e, se estiver logado, a sua conta); contagem de visualizações de cada peça, sem identificar você.
Técnicos: cookie de sessão, token temporário de recuperação de senha e, nas tentativas de login, o endereço IP e o e-mail informado, guardados por até 24 horas para prevenir ataques de força bruta. Não coletamos dados sensíveis (art. 5º, II) nem dados de crianças.

## 3. Para que usamos e em qual base legal
Criar e manter a conta, e permitir login: execução de contrato, a seu pedido (art. 7º, V).
Favoritos, perguntas e avaliações: execução de contrato e legítimo interesse (art. 7º, V e IX).
Atender você pelo WhatsApp e saber quais peças geram interesse: legítimo interesse (art. 7º, IX).
Validar o CPF e evitar contas falsas ou fraudes: legítimo interesse e prevenção à fraude (art. 7º, IX e art. 11, II, g, por analogia).
Segurança do site e prevenção de ataques e fraudes: legítimo interesse (art. 7º, IX).
Cumprir obrigações legais e exercer direitos em processos: art. 7º, II e VI.
Não usamos seus dados para decisões automatizadas nem para venda a terceiros.

## 4. Com quem compartilhamos
Não vendemos nem compartilhamos seus dados para publicidade. Compartilhamos apenas o necessário com:
Hospedagem do site (HostGator), que armazena o banco de dados como operadora.
Google Fonts, que carrega as fontes do site e pode registrar o seu endereço IP.
WhatsApp (Meta), quando você clica no botão: a conversa passa a seguir os termos e a política do WhatsApp. Podemos ainda compartilhar dados com autoridades quando houver obrigação legal.
Esses serviços podem tratar dados fora do Brasil. Nesses casos, a transferência internacional ocorre conforme o art. 33 da LGPD.

## 5. Cookies
Usamos apenas um cookie de sessão, essencial para manter você conectado e proteger os formulários contra fraude (CSRF). Ele não exige consentimento por ser estritamente necessário ao serviço, e é apagado ao sair da conta ou fechar o navegador. Não usamos cookies de publicidade, análise ou rastreamento. Para não repetir o aviso de cookies, guardamos no seu navegador (armazenamento local, não é cookie) apenas a informação de que você leu o aviso. Você pode bloquear ou apagar cookies nas configurações do navegador, mas o login deixará de funcionar.

## 6. Por quanto tempo guardamos
Conta, favoritos, avaliações e perguntas: enquanto a conta existir. Após a exclusão, apagamos os dados, salvo o que a lei obrigue a guardar ou o necessário para defesa em processos. Token de recuperação de senha: expira em 1 hora e fica inutilizado após o uso. Cliques de WhatsApp e visualizações: mantidos para fins estatísticos, sem uso para identificar você além do necessário.

## 7. Segurança
Senhas guardadas apenas como hash, formulários com proteção CSRF, bloqueio temporário após tentativas de login erradas, validação dos dados enviados e acesso restrito à área administrativa. Se ocorrer incidente que possa causar risco ou dano relevante, avisaremos você e a ANPD, conforme o art. 48 da LGPD.

## 8. Seus direitos (art. 18)
Você pode, a qualquer momento: confirmar que tratamos seus dados; acessar; corrigir; pedir anonimização, bloqueio ou eliminação de dados desnecessários; pedir portabilidade; saber com quem compartilhamos; ser informado sobre a possibilidade de não consentir e suas consequências; revogar consentimento; e se opor a tratamento feito com base em legítimo interesse.
Em Minha conta você edita seus dados e a senha. Para os demais pedidos, ou para excluir a conta, fale com a loja. Respondemos em até 15 dias. Você também pode reclamar à ANPD (gov.br/anpd).

## 9. Menores de idade
O site é voltado a maiores de 18 anos. Não coletamos intencionalmente dados de menores. Se identificarmos, excluiremos a conta.

## 10. Mudanças
Podemos atualizar esta política, por exemplo ao lançar carrinho, entrega e pagamento online, que exigirão novos dados (endereço e pagamento). A versão em vigor é sempre a publicada nesta página, com a data de atualização.
TXT;
?>

<article class="artigo">
    <div class="artigo-coluna">
        <h1>Política de privacidade</h1>
        <p class="artigo-meta">Última atualização: outubro de 2026</p>
        <div class="artigo-texto"><?= str_replace('<h2>5. Cookies', '<h2 id="cookies">5. Cookies', renderConteudoPost($texto)) ?>
            <h2>11. Fale com a gente</h2>
            <p>Para exercer seus direitos ou tirar dúvidas sobre privacidade, fale com a Josy Noivas <?= e($canal) ?>. Esse é o nosso canal de atendimento ao titular.</p>
        </div>
    </div>
</article>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>