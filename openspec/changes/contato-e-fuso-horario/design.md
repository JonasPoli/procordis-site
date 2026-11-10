# Design

## Context

Motivação em proposal.md (Why). Estado atual, lido no código em 09/11/2026 e na produção em 10/11/2026 (leitura):

- **Contato.** `ContactController` (`/contato`) grava a mensagem e envia um único e-mail, de `no-reply@procordis.org.br` para `contato@procordis.com.br` (fixo desde `c6f7b0c`), com Reply-To do visitante e corpo HTML montado no próprio controller. É a única referência a `procordis.com.br` no repositório. `Api/ContactApiController` (home) lê `contact_email_recipients` com `SystemVariableRepository::getValue()`, separa por `/\r\n|\r|\n|,/`, descarta o que não passa em `FILTER_VALIDATE_EMAIL` e envia um `TemplatedEmail` por endereço, de `noreply@wab.com.br`, todos dentro de um único `try`. A variável é editada só pelo Administrador em `/admin/system-variables`. O `EMAIL_CONTACT_TO` do `.env.local` existe em produção, mas não é lido pelo código.
- **Transporte.** O WMailer (`vendor/wab-ninjas/wmailer-transport`, `WabApiTransport::getPayload()`) envia à API só `mailFrom`, `nameFrom`, `mailTo`, `subject`, `bcc` e `body`: o Reply-To montado no `Email` não chega ao destinatário.
- **Fuso.** Nenhum ponto da aplicação define fuso: `src/Kernel.php` está vazio, não há `date.timezone` em `config/`, `.env*` ou `phpunit.dist.xml`, nem `twig.date.timezone`. Em produção:
  - o PHP de linha de comando roda em UTC (`php -i`: `date.timezone => UTC`);
  - o PHP dos pedidos web (FPM) roda no horário de Brasília. O `date.timezone` do pool fica na configuração do RunCloud, fora do git, e o runcloud não consegue lê-lo, mas três sinais concordam:
    - o JSON-LD público das notícias sai com `-03:00` (ex.: `2026-09-28T00:00:00-03:00`), em produção e na homologação;
    - o POST da newsletter registrado pelo nginx às `00:58:34 +0000` de 17/10/2026 foi gravado como `2026-10-16 21:58:34`, e o POST do `/contato` das `07:09:57 +0000` de 29/07/2026 foi gravado como `2026-07-29 04:09:56`;
    - o primeiro item da galeria (`created_at` `2026-09-28 22:11:33`) é de 5 minutos depois da migration que criou a tabela (`executed_at` `2026-09-29 01:06:52`, gravado pelo console em UTC); em UTC, o item seria anterior à tabela.
  - Logo, os carimbos gravados pelos pedidos web já estão em Brasília. Em UTC estão só os gravados pelo console (`app:populate-test-database`: `page.created_at`/`page.published_at` de 15/12/2025 e 2 depoimentos; `doctrine_migration_versions.executed_at`) e os do Messenger, que grava em UTC de propósito.
- **Como as datas passam pelas camadas (todas usam o fuso padrão do PHP):**
  - Formulário: `NewsType::publishedAt` e `TestimonyType::createdAt` ("Data da Avaliação") são `DateType` `single_text` com `model_timezone`/`view_timezone` nulos, então a data D vira `D 00:00:00` no fuso padrão.
  - Doctrine: as colunas são `DATETIME` sem fuso; o tipo `datetime_immutable` grava `format('Y-m-d H:i:s')` no fuso do próprio objeto e hidrata no fuso padrão. Não há opção de fuso para `DATETIME` no Doctrine, e o `time_zone` da sessão MySQL só afeta `TIMESTAMP` e `NOW()`, que o código não usa.
  - Visibilidade: `NewsRepository::applyPublicCriteria()` compara `publishedAt <= :public_news_now` com `new \DateTimeImmutable()`; `News::isPubliclyVisible()` compara instantes.
  - Twig: o filtro `date` usa `CoreExtension::getTimezone()`, que lê `date_default_timezone_get()` na primeira formatação.
  - Carimbos `createdAt`/`updatedAt` vêm de `new \DateTimeImmutable()` nos controllers, nos construtores e nos callbacks `PreUpdate`; os `updatedAt` de about_page, doctor, home_banner, news, partner, service, specialty e timeline_item só mudam na troca de imagem (`setImageFile()` do VichUploader).
  - JSON-LD: `News` não tem `getUpdatedAt()`, então o `dateModified` (`news.updatedAt|default(news.publishedAt)`) sai sempre igual ao `datePublished`.
- **Banco de produção.** MariaDB 11.1.6, sem triggers, nenhuma coluna `DATETIME` com `ON UPDATE` ou valor padrão; as tabelas de fuso do MariaDB não estão carregadas (`CONVERT_TZ` com `America/Sao_Paulo` devolve NULL); `mysql` e `mysqldump` estão disponíveis para o runcloud. O `doctrine:migrations` voltou a funcionar em 09/11/2026, com a correção do `serverVersion` no `.env.local`.

## Goals / Non-Goals

**Goals:**

- Uma só leitura da lista `contact_email_recipients`, usada pelos dois formulários de contato.
- Fuso `America/Sao_Paulo` garantido pelo código versionado, igual em produção, homologação, máquina local, console e PHPUnit.
- Converter (−3 h) os carimbos de data e hora já gravados pela aplicação, com backup antes e conferência depois, no mesmo deploy que fixa o fuso (decisão do Jonas em 10/11/2026).

**Non-Goals:**

- Trocar o remetente, o assunto ou o corpo do e-mail do `/contato` (continua `no-reply@procordis.org.br` e HTML inline), ou passar a usar o modelo `emails/contact.html.twig`.
- Fazer o WMailer repassar o Reply-To (é pacote de terceiros em `vendor/`).
- Anti-spam ou limite por IP nos formulários.
- Converter as datas escolhidas no formulário (`news.published_at`, `testimony.created_at`) e as tabelas `messenger_messages` e `doctrine_migration_versions` (ver Decisão 5).
- Corrigir o `dateModified` do JSON-LD (falta `News::getUpdatedAt()`).
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

- *Alternativa:* `date.timezone` no php.ini do pool pelo painel do RunCloud. Rejeitada: é o que deixa o FPM da produção e da homologação em Brasília hoje, mas não fica no git, o runcloud não consegue ler para conferir, não vale para o PHP de console (`/RunCloud/Packages/php83rc/bin/php`, em UTC) e teria de ser repetida em cada ambiente novo, na máquina local e no PHPUnit.
- *Alternativa:* só Doctrine. Rejeitada: não há opção de fuso para `DATETIME`, como visto em Context.
- *Alternativa:* só `twig.date.timezone`. Rejeitada: muda apenas a exibição. Com o PHP em UTC e o Twig em Brasília, `D 00:00` seria exibido como D-1 e a visibilidade continuaria em UTC.
- *Alternativa:* `date_default_timezone_set` em `public/index.php` e `bin/console`. Rejeitada: dois lugares e os testes ficam de fora.

### 4. Regra de visibilidade sem mudança de código

`createPublicQueryBuilder()`/`applyPublicCriteria()` continuam sendo a única regra pública (regra do projeto) e não mudam: o `new \DateTimeImmutable()` nasce em Brasília e é gravado no parâmetro como `Y-m-d H:i:s` de Brasília, comparado com `published_at`, que também vale em Brasília. Assim, a data D fica pública às 00:00 de D em Brasília em qualquer ambiente; em produção, onde o FPM já está em Brasília, nada muda para o visitante. `News::isPubliclyVisible()`, a etiqueta "Agendada" da listagem do painel (`item.publishedAt > date()`) e a data padrão do formulário (`News::__construct`) usam o mesmo relógio, sem mudança de código.

### 5. Datas já gravadas: conversão de −3 h no deploy (decisão do Jonas, 10/11/2026)

Em 10/11/2026, depois de ver as evidências de Context (FPM em Brasília, JSON-LD com `-03:00`, log do nginx comparado com o banco), o Jonas confirmou: converter para o horário de Brasília, subtraindo 3 h, todos os carimbos de data e hora gravados pela aplicação, com backup antes.

**Convertidas: 17 colunas em 14 tabelas.**

| Tabela | Colunas | Onde aparece |
| --- | --- | --- |
| `contact_message` | `created_at` | `/admin/messages`, com hora |
| `newsletter_subscriber` | `created_at` | `/admin/newsletter`, com hora |
| `page` | `created_at`, `published_at`, `updated_at` | `updated_at` na lista de páginas do painel, com hora; as outras duas em nenhuma tela |
| `transparency` | `created_at` | ordem das categorias do Portal da Transparência |
| `transparency_doc` | `updated_at` | "Atualizado em" (só a data) no Portal da Transparência |
| `news_gallery_item` | `created_at`, `updated_at` | em nenhuma tela |
| `news` | `updated_at` | em nenhuma tela (o JSON-LD não lê, ver Context) |
| `about_page`, `doctor`, `home_banner`, `service`, `specialty`, `timeline_item`, `partner` | `updated_at` | em nenhuma tela (marcam a troca de imagem) |

`partner` está vazia hoje e entra assim mesmo: o `UPDATE` não altera nada se ela continuar vazia.

**Ficam fora:**

- `news.published_at`: `DateType`, sempre 00:00 (9 de 9 linhas em 10/11/2026). Passa a valer como meia-noite de Brasília sem conversão, que já é como o FPM de produção a lê. Com −3 h, viraria 21:00 da véspera e a notícia mudaria de dia.
- `testimony.created_at`: é a "Data da Avaliação", escolhida em `DateType` no `TestimonyType` (10 de 12 linhas às 00:00; as outras 2 vieram do `app:populate-test-database` e só a data é exibida). Com −3 h, voltaria um dia.
- `messenger_messages` (`created_at`, `available_at`, `delivered_at`): o transporte Doctrine do Messenger grava e compara em UTC de propósito (`new \DateTimeImmutable('UTC')`), qualquer que seja o fuso da aplicação.
- `doctrine_migration_versions.executed_at`: registro interno do Doctrine Migrations, que não é exibido nem decide a ordem das migrations.

**Datas antes de 2019 (horário de verão).** O Brasil não tem horário de verão desde 2019; antes, no verão, o deslocamento era de −2 h. Nenhuma coluna convertida tem valor anterior a 2019 (o menor é de 15/12/2025, em 10/11/2026); as datas antigas estão só em `news.published_at` (2 linhas, desde 2017) e `testimony.created_at` (5 linhas de 2018), que ficam fora. Por isso o deslocamento é fixo, de 3 h, sem tabela por período. A leitura de antes confere isso no dia do deploy (contagem de valores anteriores a `2019-02-17 02:00:00` igual a zero em cada coluna convertida); se aparecer algum, a conversão para e volta ao design. `CONVERT_TZ` com `America/Sao_Paulo` não seria alternativa: as tabelas de fuso do MariaDB não estão carregadas.

**Momento e filtro.** O FPM grava em Brasília antes e depois do deploy, e o console passa a gravar em Brasília com o `Kernel::boot()`. Assim, um só corte separa o antigo do novo: o momento do deploy `T`, em horário de Brasília, anotado logo antes do `git pull`. A ordem é:

1. backup e leitura de antes (com o corte `T`);
2. `git pull` e `./build.sh`;
3. logo depois, um `UPDATE <tabela> SET <coluna> = <coluna> - INTERVAL 3 HOUR WHERE <coluna> < 'T'` por coluna, todos numa só transação;
4. leitura de depois e comparação.

- `T` precisa estar em horário de Brasília (`TZ=America/Sao_Paulo date '+%F %T'`), não em UTC: com `T` em UTC, o que fosse gravado nas 3 h seguintes ao deploy cairia no filtro e seria convertido por engano.
- Não é preciso parar o uso do site nem do painel: o que for gravado entre `T` e o `UPDATE` tem valor igual ou posterior a `T` e não é convertido. Como cada coluna tem o seu `UPDATE`, uma linha antiga alterada depois de `T` fica com o `updated_at` novo, sem conversão, e o `created_at` antigo convertido.
- O script roda uma vez só: repetir deslocaria de novo as linhas antigas. Ele se recusa a rodar se o arquivo com o resultado da conversão já existir na pasta do backup.

**Por que script SQL e não migration do Doctrine.** O `doctrine:migrations` voltou a funcionar em 09/11, mas o corte `T` só existe na hora do deploy, a migration rodaria também nos bancos da homologação, da máquina local e dos testes, e o `down()` não teria como saber o corte. O script é gerado na pasta do backup, fora do git.

**Risco aceito pelo Jonas.** A conversão desloca para 3 h antes carimbos que o log indica já estarem em Brasília. Exemplo: a mensagem recebida às 04:09 de Brasília, gravada como 04:09, passa a aparecer às 01:09 em `/admin/messages`. A ordem dentro de cada tabela não muda, e os registros antigos continuam antes dos novos. Em 10/11/2026 nenhuma linha das colunas convertidas tem hora antes das 03:00, então nenhuma data exibida (como o "Atualizado em" da Transparência) muda de dia; a leitura de antes repete essa contagem.

**Volta do banco.** `UPDATE <tabela> SET <coluna> = <coluna> + INTERVAL 3 HOUR WHERE <coluna> < 'T'` nas mesmas colunas, com o mesmo `T` e também uma vez só: os valores convertidos ficaram abaixo de `T − 3 h`, e os gravados depois estão em `T` ou depois. O dump é a referência linha a linha. Ele não é restaurado por cima das tabelas, porque isso apagaria o que foi gravado depois do deploy.

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
- A conversão das datas é um script SQL de produção, sem teste PHPUnit: a conferência é a comparação das leituras de antes e de depois (Migration Plan).

## Risks / Trade-offs

- [`contact_email_recipients` vazia ou com endereços errados em produção: o `/contato` deixa de enviar, ou envia a quem não deve] → conferência (leitura) da variável no painel antes do deploy; se precisar mudar, o Jonas aprova e o valor é gravado pelo painel antes do deploy, com o valor anterior copiado.
- [O remetente `no-reply@procordis.org.br` pode não ser aceito ou entregue pelo WMailer, já que a home usa `noreply@wab.com.br`] → o contato de teste da conferência mostra isso; se não chegar, trocar o remetente para `noreply@wab.com.br` num PR pequeno (ver Open Questions).
- [O WMailer descarta o Reply-To: "Responder" no e-mail não vai para o visitante] → limitação já existente, fora do escopo; o e-mail do visitante está no corpo da mensagem.
- [A conversão desloca para 3 h antes carimbos que o log indica já estarem em Brasília] → risco aceito pelo Jonas em 10/11/2026 (Decisão 5); backup antes e volta pelo `UPDATE` inverso.
- [`T` anotado em UTC, ou script rodado duas vezes, desloca linhas erradas] → `T` com `TZ=America/Sao_Paulo`, script que se recusa a rodar de novo, transação única e comparação das leituras de antes e de depois.
- [O backup tem dados pessoais (mensagens de contato e e-mails da newsletter)] → pasta fora de `~/webapps`, permissões 700/600, arquivo de credenciais temporário apagado no fim e backup apagado 30 dias depois, com aprovação do Jonas.
- [O deploy muda a hora de liberação das notícias] → não muda em produção, onde o FPM já está em Brasília; por isso a janela das 21h às 0h deixou de ser restrição para o deploy.
- [Código que cria data antes do `boot()` (ex.: data provider do PHPUnit) fica no fuso do PHP de console] → os testes criam datas depois do `createClient()`; o teste do kernel cobre a regra.
- [Logs do Monolog passam a sair em horário de Brasília] → sem impacto: vão para o stderr do FPM, que ninguém lê.

## Migration Plan

1. **Antes (leitura):**
   - `curl` de uma notícia em procordis.org.br: o `datePublished` deve terminar em `-03:00`, como em 10/11/2026 (FPM em Brasília).
   - Valor de `contact_email_recipients` em `/admin/system-variables`.
   - Por SSH: contagem de `news.published_at` com hora diferente de `00:00:00` (esperado: zero), lista das notícias com data futura, triggers do banco (esperado: nenhum), `git rev-parse HEAD` e `git status` do webapp.
2. **Backup (escrita de arquivos fora do git):** pasta `~/backups/procordis-fuso-AAAAMMDD` (700, fora de `~/webapps`), arquivo de credenciais temporário gerado a partir do `DATABASE_URL` sem exibir valores (600), anotação de `T`, leitura de antes e `mysqldump` das 14 tabelas (600). O commit de produção anotado é o ponto de volta do código.
3. **Deploy e conversão (escrita; a conversão foi aprovada pelo Jonas em 10/11/2026, e o deploy é feito com a aprovação dele):**
   - PR → merge na `main`.
   - Como runcloud, em `~/webapps/app-procordis-producao`: `git pull` e `./build.sh`. As linhas `symfony console` do fim falham, o que é esperado. Sem `composer install` e sem migrations do Doctrine.
   - Logo depois, o script de conversão numa só transação, com o `T` anotado. Se o modo automático barrar a escrita, o comando é entregue pronto ao Jonas (bloco abaixo).
   - Fora do pico.
4. **Conferência:**
   - Leitura de depois comparada com a de antes (abaixo); depois, o arquivo de credenciais é apagado.
   - `datePublished` com `-03:00` (leitura).
   - `/noticias` e a home com as mesmas notícias de antes (leitura).
   - Um contato de teste em `/contato` e outro pela home: é escrita, porque grava uma mensagem que o painel não exclui. Conferir que chegaram a cada destinatário e que `/admin/messages` mostra o horário de Brasília para os testes e as mensagens antigas 3 h antes do que mostravam.
5. **Volta:** código pelo revert do PR no GitHub, `git pull` e `./build.sh`; banco pelo `UPDATE` inverso com o mesmo `T` (Decisão 5). As duas voltas são independentes: o FPM grava em Brasília com ou sem o código novo.

### Comandos do backup e da conversão

No servidor (`ssh runcloud@67.205.162.23`), numa só sessão de shell, para as variáveis valerem do começo ao fim:

```bash
# A. Pasta e credenciais (nada é exibido)
cd ~/webapps/app-procordis-producao
umask 077
B=~/backups/procordis-fuso-$(TZ=America/Sao_Paulo date +%Y%m%d)
mkdir -p "$B" && chmod 700 "$B"
/RunCloud/Packages/php83rc/bin/php -r '
  require "vendor/autoload.php";
  (new Symfony\Component\Dotenv\Dotenv())->bootEnv(".env");
  $u = parse_url($_SERVER["DATABASE_URL"]);
  file_put_contents($argv[1], sprintf("[client]\nuser=\"%s\"\npassword=\"%s\"\nhost=%s\nport=%d\n",
    addcslashes(urldecode($u["user"]), "\\\""), addcslashes(urldecode($u["pass"] ?? ""), "\\\""),
    $u["host"], $u["port"] ?? 3306));
  file_put_contents($argv[2], ltrim($u["path"], "/"));
' "$B/cliente.cnf" "$B/banco.txt"
SQL() { mysql --defaults-extra-file="$B/cliente.cnf" --table "$(cat "$B/banco.txt")"; }
COLS="contact_message.created_at newsletter_subscriber.created_at news.updated_at news_gallery_item.created_at news_gallery_item.updated_at page.created_at page.published_at page.updated_at transparency.created_at transparency_doc.updated_at about_page.updated_at doctor.updated_at home_banner.updated_at service.updated_at specialty.updated_at timeline_item.updated_at partner.updated_at"
FORA="news.published_at testimony.created_at"
TABELAS="contact_message newsletter_subscriber news news_gallery_item page transparency transparency_doc about_page doctor home_banner service specialty timeline_item partner"
leitura() { # $1 = arquivo de saída
  for tc in $COLS $FORA; do t=${tc%.*}; c=${tc#*.}
    echo "SELECT '$tc' AS col, COUNT(*) AS n, COUNT($c) AS nn, MIN($c) AS mn, MAX(IF($c < '$T', $c, NULL)) AS mx_antes_de_T, SUM($c >= '$T') AS depois_de_T, SUM($c < '2019-02-17 02:00:00') AS antes_2019, SUM(TIME($c) < '03:00:00') AS antes_das_3 FROM \`$t\`;"
  done | SQL > "$1"
}

# B. Corte, leitura de antes e backup (logo antes do git pull)
TZ=America/Sao_Paulo date '+%F %T' > "$B/T.txt"; T=$(cat "$B/T.txt"); echo "T = $T (Brasília)"
leitura "$B/antes.txt"; cat "$B/antes.txt"   # antes_2019 = 0 e antes_das_3 = 0 nas colunas convertidas; senão, parar
mysqldump --defaults-extra-file="$B/cliente.cnf" --single-transaction --no-tablespaces "$(cat "$B/banco.txt")" $TABELAS > "$B/backup-antes.sql"
ls -l "$B"; tail -1 "$B/backup-antes.sql"; grep -c '^INSERT INTO' "$B/backup-antes.sql"

# C. Deploy (git pull e ./build.sh) e, logo depois, a conversão numa só transação
{ echo "START TRANSACTION;"
  for tc in $COLS; do t=${tc%.*}; c=${tc#*.}
    echo "UPDATE \`$t\` SET $c = $c - INTERVAL 3 HOUR WHERE $c < '$T';"
    echo "SELECT '$tc' AS col, ROW_COUNT() AS convertidas;"
  done
  echo "COMMIT;"; } > "$B/converter.sql"
cat "$B/converter.sql"   # conferir o T e as 17 colunas
if [ -e "$B/convertidas.txt" ]; then echo "conversão já feita: não rodar de novo"; else SQL < "$B/converter.sql" > "$B/convertidas.txt"; cat "$B/convertidas.txt"; fi

# D. Conferência e limpeza
leitura "$B/depois.txt"; diff "$B/antes.txt" "$B/depois.txt"
rm -f "$B/cliente.cnf"; ls -l "$B"
```

Se o `mysql` parar num erro, a conexão fecha com a transação aberta e o MariaDB desfaz tudo; nesse caso, apagar o `convertidas.txt` incompleto antes de rodar de novo. Na comparação, para cada coluna convertida:

- `n` e `nn` iguais aos de antes, somando as linhas de `depois_de_T`;
- `mn` e `mx_antes_de_T` exatamente 3 h antes;
- `convertidas` igual a `nn − depois_de_T` da leitura de antes, ou menor só por linhas antigas alteradas pelo painel depois de `T`.

Nas duas colunas de `FORA`, tudo igual. O `umask 077` deixa os arquivos novos da pasta com permissão 600.

## Open Questions

- Se o contato de teste não chegar com o remetente `no-reply@procordis.org.br`, trocar pelo `noreply@wab.com.br` da home? Só se sabe na conferência depois do deploy; se acontecer, é uma mudança pequena à parte (uma linha de código e o remetente no requisito), sem alterar esta abordagem.
