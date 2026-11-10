# Proposal

## Why

Duas falhas do site, decididas pelo Jonas em 09/11/2026, e a decisão dele sobre as datas já gravadas, de 10/11/2026:

- O formulário da página `/contato` envia o aviso para `contato@procordis.com.br`, endereço fixo no código desde o commit `c6f7b0c` (15/12/2025). O domínio `.com.br` usa o e-mail da Hostinger e não é o da Procordis (`procordis.org.br`): as mensagens da página Contato não chegam a quem atende. O formulário da home já usa a lista cadastrada no painel (variável de sistema `contact_email_recipients`).
- A aplicação não define fuso horário no código: cada ambiente segue a configuração do seu PHP. Em produção, o PHP dos pedidos web (FPM) já roda no horário de Brasília, por uma configuração do pool no painel do RunCloud que fica fora do git (o JSON-LD das notícias sai com `-03:00`); o PHP de linha de comando roda em UTC, e os testes e a máquina local seguem a configuração de cada um. Num PHP em UTC, a data de publicação das notícias vale como meia-noite UTC: a notícia com a data D aparece às 21h de D-1 no horário de Brasília e a data padrão de uma notícia nova já é "amanhã" depois das 21h. O fuso passa a ficar fixado no código para não depender da configuração do painel.
- Em 10/11/2026 o Jonas decidiu converter para o horário de Brasília (−3 h), com backup antes, os carimbos de data e hora já gravados pela aplicação, mesmo com as evidências de que os gravados pelos pedidos web já estão em Brasília (risco aceito, em design.md).

## What Changes

- O `/contato` passa a enviar o aviso aos mesmos destinatários do formulário da home: cada endereço válido de `contact_email_recipients` recebe o seu e-mail. O endereço `contato@procordis.com.br` sai do código. Sem destinatário válido, a mensagem continua gravada e nenhum e-mail sai (como na home).
- A aplicação passa a usar o fuso `America/Sao_Paulo` em todo pedido web, nos comandos de console e nos testes, independentemente do `date.timezone` do PHP do servidor.
- Uma notícia com data D aparece à meia-noite de D no horário de Brasília e a data padrão de uma notícia nova é o dia de hoje em Brasília, em qualquer ambiente (em produção, o FPM já se comporta assim).
- No deploy, os carimbos de criação e alteração gravados antes do momento do deploy são convertidos (−3 h) em 17 colunas de 14 tabelas, com backup dessas tabelas antes e conferência depois. Ficam fora as datas escolhidas no formulário (`news.published_at` e a "Data da Avaliação" dos depoimentos, sempre 00:00) e as tabelas internas do Messenger e das migrations. Detalhes em design.md.

## Capabilities

### New Capabilities
- `fuso-horario`: fuso horário único da aplicação (horário de Brasília) para criar, gravar, comparar e exibir datas no site, no painel e no console, e a conversão única dos carimbos já gravados na implantação.

### Modified Capabilities
- `contato-e-newsletter`: o requisito "E-mail do formulário da página Contato" troca o destino fixo `contato@procordis.com.br` pelos destinatários da variável `contact_email_recipients`.
- `noticias`: o requisito "Data de publicação sem horário" passa a dizer que a meia-noite da data de publicação é a de Brasília, e não a do fuso do PHP do servidor.

## Impact

- Código: `src/Controller/ContactController.php` (destinatários), `src/Repository/SystemVariableRepository.php` (leitura da lista de e-mails, compartilhada com `Api/ContactApiController`), `src/Kernel.php` (fuso). A regra de visibilidade (`NewsRepository::createPublicQueryBuilder()`/`applyPublicCriteria()`, `News::isPubliclyVisible()`) não muda de código: passa a comparar em horário de Brasília em qualquer ambiente.
- Testes PHPUnit novos para o contato e para o fuso.
- Sem migration do Doctrine, sem `composer install`, sem dependência nova. Deploy com `git pull` e `./build.sh`.
- Banco de produção (escrita aprovada pelo Jonas em 10/11/2026): backup (`mysqldump`) das 14 tabelas afetadas e um script SQL de conversão numa só transação, rodado no mesmo deploy, com corte no momento do deploy em horário de Brasília. O backup tem dados pessoais (mensagens e e-mails da newsletter) e fica fora da pasta pública, com permissão 600.
- Produção: depende de `contact_email_recipients` estar preenchida com os endereços certos da Procordis (conferência antes do deploy). O JSON-LD das notícias já sai com `-03:00` e continua igual. No painel, as mensagens, as inscrições da newsletter e as páginas antigas passam a mostrar a hora 3 h antes da atual.
- Pedido do cliente 212 (Associação Procordis Araraquara), projeto 81: registro em ticket do sistema WAB.
