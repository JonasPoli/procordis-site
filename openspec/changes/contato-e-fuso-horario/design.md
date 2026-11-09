# Design

## Context

Motivação em proposal.md (Why). Estado atual, lido no código em 09/11/2026:

- **Contato.** `ContactController` (`/contato`) grava a mensagem e envia um único e-mail, de `no-reply@procordis.org.br` para `contato@procordis.com.br` (fixo desde `c6f7b0c`), com Reply-To do visitante e corpo HTML montado no próprio controller. É a única referência a `procordis.com.br` no repositório. `Api/ContactApiController` (home) lê `contact_email_recipients` com `SystemVariableRepository::getValue()`, separa por `/\r\n|\r|\n|,/`, descarta o que não passa em `FILTER_VALIDATE_EMAIL` e envia um `TemplatedEmail` por endereço, de `noreply@wab.com.br`, todos dentro de um único `try`. A variável é editada só pelo Administrador em `/admin/system-variables`. O `EMAIL_CONTACT_TO` do `.env.local` existe em produção, mas não é lido pelo código.
- **Transporte.** O WMailer (`vendor/wab-ninjas/wmailer-transport`, `WabApiTransport::getPayload()`) envia à API só `mailFrom`, `nameFrom`, `mailTo`, `subject`, `bcc` e `body`: o Reply-To montado no `Email` não chega ao destinatário.
- **Fuso.** Nenhum ponto da aplicação define fuso: `src/Kernel.php` está vazio, não há `date.timezone` em `config/`, `.env*` ou `phpunit.dist.xml`. O PHP de linha de comando de produção roda em UTC; o `date.timezone` do pool FPM não é legível pelo runcloud, mas o comportamento observado (notícia de D às 21h de D-1) mostra UTC.
- **Como as datas passam pelas camadas (todas usam o fuso padrão do PHP):**
  - Formulário: `NewsType::publishedAt` é `DateType` `single_text` com `model_timezone`/`view_timezone` nulos, então a data D vira `D 00:00:00` no fuso padrão.
  - Doctrine: as colunas são `DATETIME` sem fuso; o tipo `datetime_immutable` grava `format('Y-m-d H:i:s')` no fuso do próprio objeto e hidrata no fuso padrão. Não há opção de fuso para `DATETIME` no Doctrine, e o `time_zone` da sessão MySQL só afeta `TIMESTAMP` e `NOW()`, que o código não usa.
  - Visibilidade: `NewsRepository::applyPublicCriteria()` compara `publishedAt <= :public_news_now` com `new \DateTimeImmutable()`; `News::isPubliclyVisible()` compara instantes.
  - Twig: o filtro `date` usa `CoreExtension::getTimezone()`, que lê `date_default_timezone_get()` na primeira formatação (o `twig.date.timezone` não está configurado).
  - Carimbos `createdAt`/`updatedAt` de várias entidades vêm de `new \DateTimeImmutable()` nos controllers e nos callbacks `PreUpdate`.

## Goals / Non-Goals

**Goals:**

- Uma só leitura da lista `contact_email_recipients`, usada pelos dois formulários de contato.
- Fuso `America/Sao_Paulo` garantido pelo código versionado, igual em produção, homologação, máquina local, console e PHPUnit.
- Nenhuma escrita no banco de produção para corrigir datas.

**Non-Goals:**

- Trocar o remetente, o assunto ou o corpo do e-mail do `/contato` (continua `no-reply@procordis.org.br` e HTML inline), ou passar a usar o modelo `emails/contact.html.twig`.
- Fazer o WMailer repassar o Reply-To (é pacote de terceiros em `vendor/`).
- Anti-spam ou limite por IP nos formulários.
- Converter os carimbos antigos (ver Decisão 5).
- Injetar relógio (`ClockInterface`) no repositório de notícias.
- Atualizar a homologação (`procordis.wab.com.br`), parada em `c510dc2` e com `composer.lock` alterado no servidor.

## Decisions

### 1. Lista de destinatários num lugar só

Novo método `SystemVariableRepository::getEmailList(string $key): array`, que devolve os endereços válidos da variável (mesma separação e mesmo `FILTER_VALIDATE_EMAIL` de hoje, com `trim`), ou lista vazia sem a variável. `ContactController` e `Api/ContactApiController` passam a usá-lo; o comportamento da home não muda.

- *Alternativa:* `EMAIL_CONTACT_TO` do `.env.local`. Rejeitada: não é a decisão do Jonas, o cliente não consegue editar pelo painel e cada troca seria escrita no servidor.
- *Alternativa:* copiar a separação para o `ContactController`. Rejeitada: duas regras que podem divergir.

### 2. Envio do `/contato`: um e-mail por destinatário, cada um no seu `try`

O `ContactController` percorre `getEmailList('contact_email_recipients')` e envia, para cada endereço, o mesmo `Email` de hoje (remetente, Reply-To, assunto e corpo iguais), cada envio no seu próprio `try/catch` com `logger->error` por endereço. Lista vazia: nenhum envio e `logger->warning` dizendo que o contato foi gravado sem e-mail. A gravação e a mensagem de sucesso continuam antes e independentes do envio.

- *Alternativa:* um só e-mail com todos em `To`. Rejeitada: a home envia separado e um endereço recusado derrubaria o envio inteiro.
- *Alternativa:* manter `contato@procordis.com.br` como reserva quando a lista está vazia. Rejeitada: é o endereço errado que motivou a mudança.

### 3. Fuso fixado no `Kernel::boot()`

Em `src/Kernel.php`, constante `TIMEZONE = 'America/Sao_Paulo'` e sobrescrita de `boot()` chamando `date_default_timezone_set(self::TIMEZONE)` antes de `parent::boot()`. O `boot()` roda no pedido web (`public/index.php`), no `bin/console` e nos testes (`WebTestCase`/`KernelTestCase`), antes de existir qualquer serviço, então Doctrine, formulários, Twig e Monolog já nascem no fuso certo. O identificador de região, e não `-03:00` fixo, acompanha o tzdata se o horário de verão voltar.

- *Alternativa:* `date.timezone` no php.ini do pool pelo painel do RunCloud. Rejeitada: não fica no git, o runcloud não consegue ler para conferir, não vale para o PHP de console (`/RunCloud/Packages/php83rc/bin/php`, em UTC) e teria de ser repetida na homologação, na máquina local e no PHPUnit.
- *Alternativa:* só Doctrine. Rejeitada: não há opção de fuso para `DATETIME`, como visto em Context.
- *Alternativa:* só `twig.date.timezone`. Rejeitada: muda apenas a exibição. Com o PHP em UTC e o Twig em Brasília, `D 00:00` seria exibido como D-1 e a visibilidade continuaria em UTC.
- *Alternativa:* `date_default_timezone_set` em `public/index.php` e `bin/console`. Rejeitada: dois lugares e os testes ficam de fora.

### 4. Regra de visibilidade sem mudança de código

`createPublicQueryBuilder()`/`applyPublicCriteria()` continuam sendo a única regra pública (regra do projeto) e não mudam: o `new \DateTimeImmutable()` passa a nascer em Brasília e é gravado no parâmetro como `Y-m-d H:i:s` de Brasília, comparado com `published_at`, que também passa a valer em Brasília. Assim, a data D fica pública às 00:00 de D em Brasília, e não mais às 21h de D-1. `News::isPubliclyVisible()`, a etiqueta "Agendada" da listagem do painel (`item.publishedAt > date()`) e a data padrão do formulário (`News::__construct`) passam a usar o mesmo relógio, sem mudança de código.

### 5. Datas já gravadas: nenhuma conversão

| Dado | Hoje | Depois do deploy |
| --- | --- | --- |
| `news.published_at` (sempre `00:00:00`, gravado pelo `DateType` desde `d55c3ec`) | meia-noite UTC = 21h da véspera em Brasília | meia-noite de Brasília, que é o dia que o jornalista escolheu. A data exibida não muda. Notícia já pública continua pública. Notícia agendada aparece 3 h mais tarde do que apareceria, que é a correção. |
| `published_at` com hora diferente de 00:00 (só se a produção tiver recebido dados do `app:populate-test-database` ou das fixtures) | hora em UTC | mesma data exibida; o momento de liberação anda 3 h e já passou |
| Carimbos `created_at`/`updated_at` (mensagens, newsletter, depoimentos, transparência, páginas, notícias, itens da galeria e demais entidades) | gravados e exibidos em UTC (3 h à frente de Brasília) | os antigos continuam com os mesmos números, ainda 3 h à frente; os novos saem em Brasília |
| `dateModified`/`datePublished` do JSON-LD | `+00:00` | `-03:00`, com os mesmos números |

Os carimbos não são convertidos porque só aparecem no painel, a correção seria `UPDATE` em cerca de 12 tabelas de produção com backup, e o `doctrine:migrations` está quebrado em produção (`serverVersion=8.0` contra MariaDB 11.1). Efeito aceito: nas mensagens recebidas nas 3 h antes do deploy, a ordem em `/admin/messages` pode inverter com as primeiras recebidas depois. A mudança não tem migration, então não esbarra no problema do `doctrine:migrations`.

### 6. Testes (PHPUnit, SQLite, sem e-mail real)

- **Kernel:** com `date_default_timezone_set('UTC')` antes, ligar o kernel e conferir `America/Sao_Paulo`.
- **Notícias** (em `PublicNewsVisibilityTest` ou num teste novo):
  - o parâmetro `public_news_now` de `createPublicQueryBuilder()` está em `America/Sao_Paulo`;
  - criar notícia pelo painel com a data D grava `D 00:00:00` (lido direto pela conexão);
  - `isPubliclyVisible()` é falso às 21h30 de D-1 e verdadeiro às 00:00 de D (Brasília);
  - o formulário novo vem com a data de hoje em Brasília.
- **Contato** (`ContactControllerTest`, novo): `MailerAssertionsTrait` logo depois do POST, sem seguir o redirecionamento. Casos:
  - dois destinatários: dois e-mails, cada um com o seu `To`, assunto e Reply-To;
  - linha inválida ignorada;
  - variável vazia: zero e-mails, mensagem gravada e mensagem de sucesso;
  - falha em um destinatário: `MailerInterface` trocado no container de teste antes do pedido por um que lança exceção no primeiro endereço;
  - nenhum envio para `contato@procordis.com.br`.
- **Home:** um caso de regressão em `POST /api/contact` com dois destinatários, para o método compartilhado.

## Risks / Trade-offs

- [`contact_email_recipients` vazia ou com endereços errados em produção: o `/contato` deixa de enviar, ou envia a quem não deve] → conferência (leitura) da variável no painel antes do deploy; se precisar mudar, o Jonas aprova e o valor é gravado pelo painel antes do deploy, com o valor anterior copiado.
- [O remetente `no-reply@procordis.org.br` pode não ser aceito ou entregue pelo WMailer, já que a home usa `noreply@wab.com.br`] → o contato de teste da conferência mostra isso; se não chegar, trocar o remetente para `noreply@wab.com.br` num PR pequeno (ver Open Questions).
- [O WMailer descarta o Reply-To: "Responder" no e-mail não vai para o visitante] → limitação já existente, fora do escopo; o e-mail do visitante está no corpo da mensagem.
- [Deploy entre 21h e 0h: uma notícia com a data de amanhã, já visível desde as 21h, sumiria até a meia-noite] → deploy fora dessa janela.
- [Ordem das mensagens perto da virada e carimbos antigos 3 h à frente] → aceito (Decisão 5).
- [Código que cria data antes do `boot()` (ex.: data provider do PHPUnit) fica no fuso do PHP de console] → os testes criam datas depois do `createClient()`; o teste do kernel cobre a regra.
- [Logs do Monolog passam a sair em horário de Brasília] → sem impacto: vão para o stderr do FPM, que ninguém lê.

## Migration Plan

1. **Antes (leitura):**
   - `curl` de uma notícia em procordis.org.br: o `datePublished` deve terminar em `+00:00`, o que confirma o FPM em UTC.
   - Valor de `contact_email_recipients` em `/admin/system-variables`.
   - Por SSH, contagem de `news.published_at` com hora diferente de `00:00:00` e lista das notícias com data futura.
   - `git rev-parse HEAD` e `git status` do webapp.
2. **Backup:** não há escrita no banco nem em arquivos fora do git. O backup é o commit atual anotado, para voltar, e, se a variável for alterada, o valor anterior copiado.
3. **Deploy (escrita, com aprovação do Jonas):**
   - PR → merge na `main`.
   - Como runcloud, em `~/webapps/app-procordis-producao`: `git pull` e `./build.sh`. As linhas `symfony console` do fim falham, o que é esperado.
   - Sem `composer install` e sem migrations.
   - Fora da janela das 21h às 0h e fora do pico.
4. **Conferência:**
   - `datePublished` com `-03:00` (leitura).
   - `/noticias` e a home com as mesmas notícias de antes (leitura).
   - Um contato de teste em `/contato` e outro pela home: é escrita, porque grava uma mensagem que o painel não exclui. Conferir que chegaram a cada destinatário e que `/admin/messages` mostra o horário de Brasília.
5. **Volta:** revert do PR no GitHub, `git pull` e `./build.sh`. Os registros gravados depois do deploy ficam em Brasília (3 h atrás do restante), o que é aceito.

## Open Questions

- Converter os carimbos antigos de `contact_message` e `newsletter_subscriber`, que o painel exibe com hora (a lista de páginas também exibe `page.updated_at` com hora)? O padrão é não converter. Se o Jonas quiser, é um `UPDATE … SET created_at = created_at - INTERVAL 3 HOUR WHERE created_at < '<instante do deploy em UTC>'`, que é escrita com backup antes.
- Se o contato de teste não chegar com o remetente `no-reply@procordis.org.br`, trocar pelo `noreply@wab.com.br` da home? Só se sabe na conferência depois do deploy; se acontecer, é uma mudança pequena à parte (uma linha de código e o remetente no requisito), sem alterar esta abordagem.
