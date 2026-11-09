# Spec Delta

## Purpose

Define o fuso horário único da aplicação: toda data criada, gravada, comparada ou exibida no site, no painel e no console vale no horário de Brasília (America/Sao_Paulo), qualquer que seja a configuração do PHP do servidor.

## ADDED Requirements

### Requirement: Horário de Brasília em toda a aplicação
O sistema SHALL usar o fuso `America/Sao_Paulo` para criar, comparar e exibir datas em todo pedido web, nos comandos de console e nos testes automatizados, mesmo quando o `date.timezone` do PHP do servidor for outro (como UTC).

#### Scenario: PHP do servidor em UTC
- **WHEN** o PHP do servidor está configurado em UTC e um visitante abre a página de uma notícia com data 10/11/2026
- **THEN** o `datePublished` do JSON-LD da página é `2026-11-10T00:00:00-03:00`

#### Scenario: Mensagem de contato recebida às 10h
- **WHEN** um visitante envia o formulário de contato às 10h00 do horário de Brasília
- **THEN** a lista de mensagens do painel mostra a mensagem com o horário 10:00

#### Scenario: Comando de console
- **WHEN** um comando de console cria um registro com a data e a hora atuais
- **THEN** o registro é gravado com a hora de Brasília, igual à de um pedido web no mesmo instante

### Requirement: Datas gravadas valem como horário de Brasília
O sistema SHALL ler as datas gravadas no banco, que não guardam fuso, como horário de Brasília. Os valores gravados antes desta regra MUST NOT ser convertidos: a data de publicação das notícias (sempre 00:00) passa a valer como meia-noite de Brasília, e os carimbos de criação e alteração antigos continuam exibidos com os números gravados.

#### Scenario: Notícia gravada antes da mudança
- **WHEN** uma notícia ativa tem a data de publicação `2026-11-10 00:00:00` gravada antes da mudança
- **THEN** o site mostra "10 de novembro de 2026" e a notícia fica pública a partir de 00:00 de 10/11/2026 no horário de Brasília

#### Scenario: Mensagem de contato antiga
- **WHEN** uma mensagem de contato foi gravada antes da mudança com `created_at` às 13:00 (10:00 em Brasília, gravado em UTC)
- **THEN** a lista de mensagens do painel continua mostrando 13:00
