# Spec Delta

## Purpose

Define o fuso horário único da aplicação: toda data criada, gravada, comparada ou exibida no site, no painel e no console vale no horário de Brasília (America/Sao_Paulo), qualquer que seja a configuração do PHP do servidor. Define também a conversão única, na implantação, dos carimbos de data e hora já gravados.

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
O sistema SHALL ler as datas gravadas no banco, que não guardam fuso, como horário de Brasília. Na implantação desta regra, os carimbos de criação e alteração gravados antes do momento da implantação SHALL ser convertidos uma única vez, subtraindo 3 horas, com backup das tabelas antes da conversão. As datas escolhidas no formulário sem horário (data de publicação das notícias e data da avaliação dos depoimentos) e as tabelas internas do Messenger e das migrations MUST NOT ser convertidas.

#### Scenario: Mensagem de contato antiga
- **WHEN** uma mensagem de contato foi gravada antes da implantação com `created_at` às 13:00
- **THEN** depois da implantação a lista de mensagens do painel mostra a mensagem às 10:00

#### Scenario: Mensagem recebida depois do momento da implantação
- **WHEN** um visitante envia o formulário de contato às 10h00 de Brasília, depois do momento da implantação e antes de a conversão terminar
- **THEN** a mensagem não é convertida e a lista de mensagens do painel mostra 10:00

#### Scenario: Notícia gravada antes da mudança
- **WHEN** uma notícia ativa tem a data de publicação `2026-11-10 00:00:00` gravada antes da mudança
- **THEN** o site mostra "10 de novembro de 2026" e a notícia fica pública a partir de 00:00 de 10/11/2026 no horário de Brasília

#### Scenario: Depoimento com data da avaliação
- **WHEN** um depoimento tem a data da avaliação 13/02/2018 gravada antes da implantação
- **THEN** depois da implantação o painel continua mostrando 13/02/2018
