# Design

## Context

- **`/contato`:** usa o formulário Symfony `ContactType`, com CSRF, e faz o POST normal com redirecionamento.
- **Home:** o formulário é Alpine.js e envia JSON por `fetch('/api/contact')`. Ele não tem CSRF e mostra o resultado com SweetAlert (`result.error` no erro).
- **O mesmo código do PR #8:** os dois controllers foram alterados pelo `contato-e-fuso-horario` (PR #8, branch `feat/contato-e-fuso-horario`, ainda sem merge em 10/11/2026). Este change parte da `main` depois desse merge.
- **HTTP externo:** o `symfony/http-client` já está instalado. No ambiente de teste, o `framework.http_client.mock_response_factory` aponta para `App\Tests\Support\YouTubeMockResponseFactory`, e nenhum teste sai para a internet.
- **Modelo da WAB:** a Mac Lub (`maclub-2024`: `src/Service/RecaptchaVerifier.php` e `templates/pub/component/recaptcha_lazy.html.twig`) e a Siatec já usam o reCAPTCHA v3 sob demanda. Este design segue esse modelo, com duas diferenças, nas decisões 2 e 4: o token é pedido no envio, e a falha do Google deixa o contato passar.
- **Logs:** vão para o stderr do PHP-FPM, que o runcloud não lê (ver o `context` do config.yaml). A recusa fica no mesmo canal dos outros avisos do contato.

## Goals / Non-Goals

**Goals:**
- Um único serviço de verificação, usado pelos dois caminhos de contato.
- Nenhum custo no carregamento das páginas: nem script nem requisição ao Google antes de o visitante entrar no formulário.
- Testes sem rede, com o Google simulado.

**Non-Goals:**
- Newsletter, limite por IP, campo-isca e painel de spam no admin.
- Gravar os contatos recusados. Eles ficam só no log, sem dados pessoais.

## Decisions

### 1. Serviço `App\Service\RecaptchaVerifier`
- `verify(?string $token, string $expectedAction, ?string $ip): RecaptchaResult`, com três resultados: `ACCEPTED`, `REJECTED` e `UNAVAILABLE`.
- A configuração vem do `services.yaml`, a partir de `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY` e `RECAPTCHA_SCORE_MIN` (padrão 0.5). No `.env` do git, as duas chaves ficam vazias.
- `isEnabled()` só é verdadeiro com as duas chaves preenchidas. Com uma chave só, vale como desligado e o log avisa, para não travar o formulário por erro de configuração.
- **Por que um serviço próprio, e não o `karser/recaptcha3-bundle`** (usado no nepe-search): o bundle se liga a formulários Symfony, e a home envia JSON. Ele também não separa "Google fora do ar" de "recusado". O serviço é pequeno, testável com o `MockHttpClient` e não acrescenta dependência ao composer.

### 2. Recusado × indisponível
- **`REJECTED`:**
  - token vazio;
  - `success: false` com códigos de erro do token (`invalid-input-response`, `timeout-or-duplicate`);
  - `action` diferente da esperada;
  - `score` menor que a nota mínima.
- **`UNAVAILABLE`:**
  - exceção de transporte ou tempo esgotado (timeout de 5 s no pedido);
  - status HTTP diferente de 200;
  - corpo que não é JSON;
  - `success: false` só com erro de configuração (`invalid-input-secret`, `missing-input-secret`, `bad-request`).
- O `UNAVAILABLE` deixa o contato passar, com aviso no log. Para um robô, ele não é explorável: o robô não controla a resposta do Google.
- A alternativa de fechar também na falha (como a Mac Lub) foi descartada. Uma queda do Google ou uma chave errada no deploy bloquearia todos os contatos reais em silêncio.

### 3. Uso nos controllers
- **`/contato`:**
  - Depois do `isSubmitted() && isValid()` e antes da gravação, a página lê `g-recaptcha-response` do POST (campo escondido fora do `ContactType`, para não mudar o formulário Symfony).
  - Com `REJECTED`, ela adiciona um `FormError` global com o texto da spec e renderiza de novo o formulário preenchido, com status 422.
  - Não usa flash com redirecionamento, que perderia os dados.
- **`/api/contact`:**
  - Lê `recaptcha_token` do JSON.
  - Com `REJECTED`, responde `400 {"error": ...}` antes do `persist`.
  - A conferência dos campos obrigatórios (400 "Dados inválidos.") continua vindo antes, sem chamar o Google.
- **Log da recusa:** `warning` com `action`, `score`, `error-codes` e o IP do pedido. Nunca nome, e-mail ou mensagem.

### 4. Front: script sob demanda e token no envio
- Um componente `templates/partials/recaptcha.html.twig`, incluído só nas duas páginas e só com `isEnabled()`, define `window.procordisRecaptcha(action)`, que devolve uma Promise com o token. O que ele faz:
  - carrega `https://www.google.com/recaptcha/api.js?render=<site key>` uma vez só;
  - espera o `grecaptcha.ready`;
  - chama `grecaptcha.execute(siteKey, {action})`;
  - quando o script falha ou o tempo de 8 s acaba, resolve com string vazia.
- O carregamento começa no primeiro `focusin` do formulário.
- O token é pedido no envio. Ele vence 2 minutos depois de gerado, e pedir no `focusin`, como faz a Mac Lub, recusaria quem demora para escrever.
- **`/contato`:** o `submit` é interceptado. O código pede o token, grava no campo escondido e reenvia com `form.submit()`, com uma marca para não interceptar duas vezes.
- **Home:** dentro do `submitContact()` do Alpine, `formData.recaptcha_token = await window.procordisRecaptcha('contato_home')` antes do `fetch`. Sem o componente (chaves vazias), a função não existe e o JSON segue sem token.
- **Aviso e selo:**
  - O texto da spec fica abaixo de cada formulário, em fonte pequena, com links para `https://policies.google.com/privacy` e `https://policies.google.com/terms`.
  - O selo é escondido com `.grecaptcha-badge { visibility: hidden; }` no próprio componente, o que o Google permite quando há o aviso.
  - O CSS vai inline no componente, para não depender do `built.css` nem do `app.css` (ver `context`).

### 5. Chaves
- Uma chave v3 só, com os domínios `procordis.org.br`, `procordis.wab.com.br` e `localhost`. Ela é criada pelo Jonas no console do reCAPTCHA, numa conta Google da WAB.
- Os valores vão para o `.env.local` da produção e da homologação, nunca para o git nem para o relatório.
- **Alternativa descartada:** uma chave por ambiente. Isso dá mais trabalho no console e não traz nada, já que a ação e a nota são as mesmas.

### 6. Testes (PHPUnit, SQLite, sem rede e sem e-mail real)
- **Unitário do serviço com `MockHttpClient`:**
  - nota 0,9 com ação certa → `ACCEPTED`;
  - nota 0,1 → `REJECTED`;
  - ação errada → `REJECTED`;
  - `timeout-or-duplicate` → `REJECTED`;
  - `invalid-input-secret` → `UNAVAILABLE`;
  - exceção de transporte → `UNAVAILABLE`;
  - corpo HTML → `UNAVAILABLE`;
  - chaves vazias → `isEnabled()` falso.
- **Controllers:**
  - Para o Google, o `YouTubeMockResponseFactory` atual passa a responder também a `www.google.com/recaptcha/api/siteverify`, com uma resposta configurável por teste. Ele pode ser renomeado para `ExternalMockResponseFactory` se ficar mais claro.
  - As chaves de teste entram no `.env.test`.
  - Casos:
    - aceito nos dois caminhos;
    - recusado nos dois caminhos (nada gravado, zero e-mails, 422 no `/contato` e 400 na API);
    - API sem token;
    - Google indisponível (gravado, com aviso no log pelo handler `test` do Monolog que veio com o PR #8);
    - chaves vazias (gravado e sem `recaptcha/api.js` no HTML).
- **HTML:** a home e o `/contato` com as chaves configuradas têm o aviso e não têm `<script src=".../recaptcha/api.js` no HTML inicial.

## Risks / Trade-offs

- [Visitante real com nota baixa, como VPN ou navegador muito restrito] → Ele vê a mensagem para tentar de novo, e o formulário de `/contato` volta preenchido. Se aparecerem recusas de gente real no log, a nota mínima pode baixar pelo `RECAPTCHA_SCORE_MIN` sem deploy de código.
- [Chave errada ou de outro domínio no deploy] → É tratada como `UNAVAILABLE` (`invalid-input-secret`): o contato passa e o log avisa. A conferência do deploy envia um contato de teste e confere que o log não tem o aviso.
- [Bloqueador de anúncios ou rede que barra `www.google.com/recaptcha`] → O script não carrega, o envio sai sem token e é recusado (sem token não é falha do Google). Na home, o aviso de recusa é o mesmo; no `/contato`, o formulário volta preenchido. Quem cair nesse caso pode usar telefone ou WhatsApp, que já estão na página de contato.
- [Spam feito à mão por pessoa] → O reCAPTCHA não barra. Fica fora do escopo e pode virar campo-isca ou limite por IP num change futuro.
- [Integração antiga chamando `/api/contact` sem token] → Não se conhece nenhuma além da home. A recusa com 400 fica no log, com o IP.
- [Logs no stderr do FPM, ilegíveis para o runcloud] → A conferência usa o teste automatizado e o resultado visível (gravou ou não gravou), e não depende do log de produção.

## Migration Plan

1. **Antes do deploy (Jonas):** criar a chave v3 no console e gravar `RECAPTCHA_SITE_KEY` e `RECAPTCHA_SECRET_KEY` no `.env.local` da produção (escrita no servidor, com backup do `.env.local` antes). Sem as chaves, o deploy é inofensivo: o formulário segue sem reCAPTCHA.
2. **Deploy:** PR → merge na `main` → no servidor, como runcloud, `git pull` e `./build.sh` (escrita). Não há migration nem `composer install`, porque o composer.lock não muda.
3. **Conferência:**
   - a home e o `/contato` sem `recaptcha/api.js` no HTML inicial e com o aviso;
   - um contato real de teste pelo `/contato`, com "[Teste WAB]" no assunto, gravado;
   - um `POST /api/contact` sem token, respondido com 400 e nada gravado.
4. **Volta:** apagar as duas chaves do `.env.local` e rodar `cache:clear` desliga o reCAPTCHA sem mexer no código. Para voltar o código, `git checkout <HEAD anterior>` e `./build.sh`.
