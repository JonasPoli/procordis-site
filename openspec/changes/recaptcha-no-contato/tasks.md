# Tasks

## 1. Pré-requisitos

- [ ] 1.1 Conferir que o PR #8 (`contato-e-fuso-horario`) foi mergeado na `main`, rodar `git pull` e criar a branch `feat/recaptcha-no-contato` a partir dela. Verificar: `git log --oneline main -5` mostra o merge do PR #8, e `grep -n getEmailList src/Controller/ContactController.php` encontra a chamada.
- [ ] 1.2 Chave v3 criada pelo Jonas no console do reCAPTCHA, com os domínios `procordis.org.br`, `procordis.wab.com.br` e `localhost`, e os valores guardados fora do git. Verificar: o Jonas confirma no chat, sem colar os valores.

## 2. Verificação no servidor

- [ ] 2.1 Acrescentar ao `.env` do git `RECAPTCHA_SITE_KEY=`, `RECAPTCHA_SECRET_KEY=` (vazias) e `RECAPTCHA_SCORE_MIN=0.5`, e os parâmetros correspondentes no `config/services.yaml`. Verificar: `php bin/console debug:container --parameters | grep -i recaptcha` lista os três.
- [ ] 2.2 Criar `src/Service/RecaptchaVerifier.php` com `isEnabled()`, `siteKey()` e `verify(?string $token, string $expectedAction, ?string $ip)`, devolvendo `ACCEPTED`, `REJECTED` ou `UNAVAILABLE` conforme as regras do design (decisão 2, timeout de 5 s e log da recusa sem dados pessoais).
- [ ] 2.3 Criar `tests/Unit/Service/RecaptchaVerifierTest.php` com `MockHttpClient` e os oito casos do design (decisão 6). Verificar: `APP_ENV=test ./vendor/bin/phpunit tests/Unit/Service/RecaptchaVerifierTest.php` passa.

## 3. Formulários de contato

- [ ] 3.1 No `ContactController`, depois de `isValid()` e antes da gravação:
  - conferir `g-recaptcha-response` com a ação `contato`;
  - com `REJECTED`, adicionar o `FormError` "Não foi possível confirmar o envio. Tente de novo em alguns segundos." e renderizar de novo com status 422;
  - com `UNAVAILABLE` ou desligado, seguir como hoje, com aviso no log.
  - Verificar pelos testes da 3.4.
- [ ] 3.2 No `Api/ContactApiController`, depois da conferência dos campos obrigatórios e antes do `persist`:
  - conferir `recaptcha_token` com a ação `contato_home`;
  - com `REJECTED`, responder `400 {"error": "Não foi possível confirmar o envio. Tente de novo em alguns segundos."}`.
  - Verificar pelos testes da 3.4.
- [ ] 3.3 Fazer o mock HTTP do ambiente de teste (`YouTubeMockResponseFactory`, ou renomeado) responder a `www.google.com/recaptcha/api/siteverify` com uma resposta configurável por teste, e pôr as chaves de teste no `.env.test`. Verificar: a suíte inteira continua passando, sem nenhuma requisição real ao Google.
- [ ] 3.4 Acrescentar ao `ContactControllerTest` e a um `ContactApiControllerTest` os casos do design:
  - aceito;
  - recusado (nada gravado e zero e-mails);
  - API sem token;
  - Google indisponível (gravado, com aviso no log);
  - chaves vazias (gravado).
  - Verificar: `APP_ENV=test ./vendor/bin/phpunit` passa inteiro.

## 4. Front

- [ ] 4.1 Criar `templates/partials/recaptcha.html.twig`, incluído só com `isEnabled()`, com:
  - `window.procordisRecaptcha(action)`: script carregado no primeiro `focusin` do formulário, token pedido no envio, resolve com '' se falhar ou passar de 8 s;
  - o CSS que esconde o `.grecaptcha-badge`.
  - Verificar: com as chaves vazias, a home e o `/contato` não têm `recaptcha` no HTML.
- [ ] 4.2 Em `templates/contact/index.html.twig`:
  - acrescentar o campo escondido `g-recaptcha-response`;
  - interceptar o `submit` para pedir o token com a ação `contato` e reenviar uma única vez;
  - incluir o aviso do Google abaixo do botão.
  - Verificar localmente com a chave de `localhost`, de dois jeitos:
    - envio normal gravado;
    - formulário preenchido por 3 minutos antes de enviar, ainda aceito (token gerado no envio).
- [ ] 4.3 Em `templates/home/index.html.twig`:
  - no `submitContact()`, pôr `recaptcha_token` no `formData` com `await window.procordisRecaptcha('contato_home')` quando a função existir;
  - incluir o aviso abaixo do botão.
  - Verificar localmente:
    - envio pela home gravado;
    - na aba Rede, nenhuma requisição a `google.com/recaptcha` antes de clicar no formulário.
- [ ] 4.4 Criar um teste de HTML: home e `/contato` com as chaves configuradas mostram o aviso com os dois links do Google e não têm `<script src` de `recaptcha/api.js` no HTML inicial. Verificar: `APP_ENV=test ./vendor/bin/phpunit` passa.

## 5. Publicação

- [ ] 5.1 Commit, push da branch e PR no GitHub (JonasPoli/procordis-site), com a descrição do comportamento novo e da BREAKING do `/api/contact`. Verificar: o PR está aberto e sem conflitos.
- [ ] 5.2 Backup antes (leitura, depois cópia no servidor):
  - anotar o `git rev-parse HEAD` de `~/webapps/app-procordis-producao`;
  - copiar o `.env.local` para `.env.local.bak-<data>-recaptcha` (chmod 600).
  - Verificar: `ls -l` mostra a cópia com o mesmo tamanho.
- [ ] 5.3 Chaves na produção (escrita, com aprovação do Jonas): acrescentar `RECAPTCHA_SITE_KEY` e `RECAPTCHA_SECRET_KEY` ao `.env.local`, sem exibir os valores. Verificar: `grep -c '^RECAPTCHA_' .env.local` dá 2.
- [ ] 5.4 Deploy (escrita, com aprovação do Jonas): merge do PR na `main`; no servidor, como runcloud, `git pull --ff-only` e `nice ./build.sh`. Não há migration nem `composer install`. Verificar: o HEAD do servidor é o merge.
- [ ] 5.5 Conferência em procordis.org.br:
  - a home e o `/contato` respondem 200, com o aviso e sem `recaptcha/api.js` no HTML inicial;
  - um contato pelo `/contato` com "[Teste WAB]" no assunto é gravado (contagem de `contact_message` +1, com a liberação do e-mail de teste combinada com o Jonas);
  - um `curl -X POST /api/contact` sem token recebe 400, e a contagem não muda;
  - o log do nginx não tem 5xx novo.
- [ ] 5.6 Homologação (`procordis.wab.com.br`): só se o Jonas pedir. Ela está parada em c510dc2 desde 06/10 (ver `context`). Anotar aqui a decisão.
