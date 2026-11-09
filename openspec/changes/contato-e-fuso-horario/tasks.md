# Tasks

## 1. Destinatários do formulário Contato

- [ ] 1.1 Criar `SystemVariableRepository::getEmailList(string $key): array` (mesma separação por vírgula ou linha, `trim` e `FILTER_VALIDATE_EMAIL` de hoje; lista vazia sem a variável) e trocar a separação inline do `Api/ContactApiController` por ele; verificar com um caso novo de regressão em `POST /api/contact` com dois destinatários (dois e-mails, um por endereço) passando em `APP_ENV=test ./vendor/bin/phpunit`
- [ ] 1.2 No `ContactController`, enviar o e-mail de hoje (remetente `no-reply@procordis.org.br`, Reply-To do visitante, assunto "Novo contato pelo site: <assunto>", mesmo corpo) a cada endereço de `getEmailList('contact_email_recipients')`, cada envio no seu `try/catch` com `logger->error`, e `logger->warning` quando a lista estiver vazia; verificar que `grep -rn "procordis.com.br" src templates config` não encontra nada
- [ ] 1.3 Criar `tests/Controller/ContactControllerTest.php` (sem seguir o redirecionamento, com `MailerAssertionsTrait`) com os casos: dois destinatários (um e-mail por endereço, com `To`, assunto e Reply-To certos); linha inválida ignorada; variável vazia (zero e-mails, mensagem gravada, mensagem de sucesso); falha no primeiro destinatário (`MailerInterface` trocado no container de teste) sem impedir o segundo; nenhum envio para `contato@procordis.com.br`; verificar com `APP_ENV=test ./vendor/bin/phpunit tests/Controller/ContactControllerTest.php` verde

## 2. Fuso horário de Brasília

- [ ] 2.1 Em `src/Kernel.php`, adicionar a constante `TIMEZONE = 'America/Sao_Paulo'` e sobrescrever `boot()` com `date_default_timezone_set(self::TIMEZONE)` antes de `parent::boot()`; verificar com um teste novo que, com `date_default_timezone_set('UTC')` antes, ligar o kernel deixa `date_default_timezone_get()` em `America/Sao_Paulo`
- [ ] 2.2 Acrescentar testes de notícias: parâmetro `public_news_now` de `createPublicQueryBuilder()` em `America/Sao_Paulo`; notícia criada pelo painel com a data D gravada como `D 00:00:00` (leitura direta pela conexão); `News::isPubliclyVisible()` falso às 21h30 de D-1 e verdadeiro às 00:00 de D (Brasília); formulário de nova notícia com a data de hoje em Brasília; verificar com `APP_ENV=test ./vendor/bin/phpunit tests/Controller/PublicNewsVisibilityTest.php` verde
- [ ] 2.3 Rodar a suíte inteira e conferir localmente; verificar `APP_ENV=test ./vendor/bin/phpunit` verde, sem deprecations, e, com o servidor local, o `datePublished` de uma notícia terminando em `-03:00` e uma mensagem de contato nova com o horário de Brasília em `/admin/messages`

## 3. Conferência em produção antes do deploy (somente leitura)

- [ ] 3.1 Leitura: `curl -s https://procordis.org.br/noticias/<slug> | grep datePublished` mostra `+00:00` (FPM em UTC); anotar o resultado no PR
- [ ] 3.2 Leitura: abrir `/admin/system-variables` e conferir que `contact_email_recipients` tem os endereços certos da Procordis (`@procordis.org.br`); se estiver vazia ou errada, parar e pedir ao Jonas os endereços: a gravação pelo painel é escrita e exige a aprovação dele e a cópia do valor anterior; verificar com o valor conferido (ou aprovado) anotado no PR, sem expor nada do `.env.local`
- [ ] 3.3 Leitura por SSH (`ssh runcloud@67.205.162.23`): contar `news.published_at` com hora diferente de `00:00:00`, listar as notícias com data futura e rodar `git rev-parse HEAD` e `git status` no webapp; verificar com os números e o commit anotados no PR

## 4. Backup e deploy

- [ ] 4.1 Backup: não há escrita no banco nem em arquivos fora do git; guardar o commit de produção da tarefa 3.3 como ponto de volta (revert do PR, `git pull` e `./build.sh`); verificar que o commit está anotado no PR antes do merge
- [ ] 4.2 Abrir o PR em JonasPoli/procordis-site com o resumo das tarefas 1 a 3 e fazer o merge na `main` depois da revisão; verificar com o PR em estado merged
- [ ] 4.3 Deploy (escrita, com aprovação do Jonas), fora da janela das 21h às 0h e fora do pico: como runcloud, em `~/webapps/app-procordis-producao`, `git pull` e `./build.sh` (as linhas `symfony console` do fim falham, o que é esperado); sem `composer install` (o `composer.lock` não muda) e sem migrations (a mudança não tem); verificar com `git log -1 --oneline` no servidor mostrando o merge e `git status` sem arquivos rastreados alterados

## 5. Conferência em procordis.org.br depois do deploy

- [ ] 5.1 Leitura: o `datePublished` da mesma notícia da tarefa 3.1 termina em `-03:00`, e `/noticias` e a home mostram as mesmas notícias de antes do deploy; verificar comparando com o anotado no PR
- [ ] 5.2 Escrita (grava mensagens que o painel não exclui; avisar o Jonas): enviar um contato de teste em `/contato` e outro pela home, com "[Teste WAB]" no assunto; verificar que cada endereço de `contact_email_recipients` recebeu os dois e-mails, que nada foi para `contato@procordis.com.br` e que `/admin/messages` mostra os horários de Brasília; se o e-mail do `/contato` não chegar, registrar e seguir a Open Question do remetente no design.md

## 6. Registro no ticket do cliente

- [ ] 6.1 Registrar no sistema WAB (cliente 212, projeto 81): procurar o ticket do pedido (`GET /tickets?q=...&status=abertos`) ou abrir um com `POST /tickets/open` (antes com `dry_run: true`). A resposta, na 1ª pessoa do plural, vai para `secretaria@procordis.org.br` com Cc `suporte@wab.com.br` e diz que o formulário Contato agora envia aos endereços cadastrados no painel e que as notícias passam a aparecer à meia-noite de Brasília. Ela é programada para daqui a 2 h, nunca antes do pedido mais as horas lançadas. As horas vão sem `contract_id` (o cliente não tem plano de horas). Verificar com `GET /tickets/{id}` mostrando a resposta programada e o lançamento de horas

## Workflow follow-up

- Arquivar a mudança (`/opsx:archive`) depois do deploy e da conferência.
- No arquivamento, ajustar o `## Purpose` de `openspec/specs/contato-e-newsletter/spec.md`: os dois formulários de contato deixam de ter "destinos de e-mail diferentes" e passam a usar a mesma lista `contact_email_recipients`.
- Rodar `openspec validate --specs --strict` depois de arquivar.
