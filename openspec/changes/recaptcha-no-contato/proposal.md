# Proposal

## Why

Os dois formulários de contato do site, o da página `/contato` e o da home (`POST /api/contact`), não têm nenhum anti-spam além do CSRF do formulário Symfony. A rota da API não tem nem o CSRF.

Em 10/11/2026 o Jonas pediu o reCAPTCHA porque "recebemos muito spam". No banco de produção há 21 contatos desde dez/2025 (leitura de 10/11/2026): os 3 de jul/2026 são todos spam com link. Como a API aceita qualquer JSON, um robô consegue gravar e disparar e-mail sem nem abrir a página.

## What Changes

- **reCAPTCHA v3 invisível nos dois formulários**, no mesmo modelo da Mac Lub e da Siatec:
  - o script do Google só carrega quando o visitante começa a preencher o formulário, para não pesar no PageSpeed;
  - o token é pedido no envio;
  - o servidor confere o token, a ação e a nota mínima (0,5 por padrão) antes de gravar.
- **Contato recusado:** não é gravado nem gera e-mail. O visitante vê um aviso para tentar de novo. A recusa vai para o log com a nota e o IP, sem os dados do visitante.
- **Google fora do ar** (erro de rede ou resposta inválida): o contato é aceito como hoje e o log recebe um aviso, para não perder contato real por falha do Google.
- **Sem chave configurada:** o formulário funciona sem reCAPTCHA (ambiente local e de teste) e o log avisa. Na produção, as chaves entram antes do deploy.
- **Aviso exigido pelo Google:** o selo flutuante fica escondido, e abaixo de cada formulário aparece o aviso "Este site é protegido pelo reCAPTCHA..." com os links da Política de Privacidade e dos Termos do Google.
- **BREAKING:** `POST /api/contact` passa a exigir o campo `recaptcha_token` quando as chaves estão configuradas. Quem chamar a API sem o token, como um robô ou uma integração antiga, recebe 400.

## Capabilities

### New Capabilities
<!-- nenhuma -->

### Modified Capabilities
- `contato-e-newsletter`: os dois formulários de contato passam a exigir o reCAPTCHA v3 antes de gravar. Entram as regras para a recusa, a falha do Google, a falta de chave e o aviso do Google. A newsletter fica como está.

## Impact

- **Código:**
  - `src/Controller/ContactController.php`, `src/Controller/Api/ContactApiController.php` e um serviço novo `src/Service/RecaptchaVerifier.php`, que usa o `symfony/http-client` já instalado;
  - `templates/contact/index.html.twig`, `templates/home/index.html.twig` e um componente Twig do carregamento sob demanda;
  - os testes PHPUnit dos dois caminhos, com `MockHttpClient` e sem chamada ao Google.
- **Configuração:**
  - `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY` e `RECAPTCHA_SCORE_MIN`, vazias no `.env` do git e preenchidas no `.env.local` da produção e da homologação;
  - a chave v3 é criada pelo Jonas no console do Google para `procordis.org.br`, `procordis.wab.com.br` e `localhost`.
- **Dependência:** este change mexe nos mesmos controllers do `contato-e-fuso-horario` (PR #8). A implementação começa depois do merge desse PR, a partir da `main` atualizada.
- **Fora do escopo:** a newsletter (`/api/newsletter`), o limite por IP e o campo-isca.
