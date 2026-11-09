# Proposal

## Why

Duas falhas do site, decididas pelo Jonas em 09/11/2026:

- O formulário da página `/contato` envia o aviso para `contato@procordis.com.br`, endereço fixo no código desde o commit `c6f7b0c` (15/12/2025). O domínio `.com.br` usa o e-mail da Hostinger e não é o da Procordis (`procordis.org.br`): as mensagens da página Contato não chegam a quem atende. O formulário da home já usa a lista cadastrada no painel (variável de sistema `contact_email_recipients`).
- A aplicação não define fuso horário e o PHP do servidor roda em UTC. A data de publicação das notícias vale como meia-noite UTC, então uma notícia com a data D aparece às 21h de D-1 no horário de Brasília, e a data padrão de uma notícia nova já é "amanhã" depois das 21h.

## What Changes

- O `/contato` passa a enviar o aviso aos mesmos destinatários do formulário da home: cada endereço válido de `contact_email_recipients` recebe o seu e-mail. O endereço `contato@procordis.com.br` sai do código. Sem destinatário válido, a mensagem continua gravada e nenhum e-mail sai (como na home).
- A aplicação passa a usar o fuso `America/Sao_Paulo` em todo pedido web, nos comandos de console e nos testes, independentemente do `date.timezone` do PHP do servidor.
- Uma notícia com data D passa a aparecer à meia-noite de D no horário de Brasília; a data padrão de uma notícia nova passa a ser o dia de hoje em Brasília.
- As datas já gravadas não são convertidas: as datas de publicação (sempre 00:00) passam a valer como meia-noite de Brasília, que é o que o jornalista escolheu; os carimbos de criação e alteração antigos continuam exibidos como estão (em UTC). Detalhes em design.md.

## Capabilities

### New Capabilities
- `fuso-horario`: fuso horário único da aplicação (horário de Brasília) para criar, gravar, comparar e exibir datas no site, no painel e no console.

### Modified Capabilities
- `contato-e-newsletter`: o requisito "E-mail do formulário da página Contato" troca o destino fixo `contato@procordis.com.br` pelos destinatários da variável `contact_email_recipients`.
- `noticias`: o requisito "Data de publicação sem horário" passa a dizer que a meia-noite da data de publicação é a de Brasília, e não a do fuso do PHP do servidor.

## Impact

- Código: `src/Controller/ContactController.php` (destinatários), `src/Repository/SystemVariableRepository.php` (leitura da lista de e-mails, compartilhada com `Api/ContactApiController`), `src/Kernel.php` (fuso). A regra de visibilidade (`NewsRepository::createPublicQueryBuilder()`/`applyPublicCriteria()`, `News::isPubliclyVisible()`) não muda de código: passa a comparar em horário de Brasília.
- Testes PHPUnit novos para o contato e para o fuso.
- Sem migration, sem `composer install`, sem dependência nova. Deploy com `git pull` e `./build.sh`.
- Produção: depende de `contact_email_recipients` estar preenchida com os endereços certos da Procordis (conferência antes do deploy). O JSON-LD das notícias passa a mostrar o deslocamento `-03:00`.
- Pedido do cliente 212 (Associação Procordis Araraquara), projeto 81: registro em ticket do sistema WAB.
