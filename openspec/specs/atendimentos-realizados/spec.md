# atendimentos-realizados Specification

## Purpose
Página do Portal da Transparência que mostra a evolução das consultas e dos exames da Procordis desde 2012, com totais e gráficos. Os números vêm da API pública do procordis-painel (só totais agregados, sem dados de pacientes); o site só lê e exibe. Implementado em `AtendimentosController`, `Service/AtendimentosPainelClient`, `templates/atendimentos/index.html.twig` e `public/js/atendimentos.js` (Chart.js do CDN). O endereço da API vem da variável `PAINEL_ATENDIMENTOS_API`.

## Requirements
### Requirement: Página de atendimentos
O sistema SHALL responder em `/transparencia/atendimentos` (com prioridade sobre `/transparencia/{slug}`) com o total geral de atendimentos, o ano do primeiro registro e o total de cada tipo com total maior que zero, lidos do `/resumo` do painel. A página do Portal da Transparência SHALL ter um cartão de destaque com link para ela.

#### Scenario: Painel com dados
- **WHEN** um visitante abre `/transparencia/atendimentos` e o painel devolve o resumo
- **THEN** a página mostra o total geral, "desde" o ano do primeiro registro e os totais por tipo

#### Scenario: Painel sem resposta e sem reserva
- **WHEN** o painel não responde e não há resposta guardada
- **THEN** a página abre com "Os números de atendimentos estão sendo atualizados. Volte em instantes." no lugar dos totais

### Requirement: Séries para os gráficos
O sistema SHALL servir em `/transparencia/atendimentos/dados` a série do `/serie` do painel, com `agrupamento` dia, mes ou ano (padrão mes) e `de`/`ate` opcionais no formato AAAA-MM-DD. Parâmetro fora desse formato MUST responder 400 com "Parâmetros inválidos." sem consultar o painel. Sem dados, SHALL responder 503 com "Dados de atendimentos indisponíveis no momento.". A resposta com dados SHALL ser pública, com cache de 600 segundos.

#### Scenario: Agrupamento inválido
- **WHEN** o navegador pede `/transparencia/atendimentos/dados?agrupamento=semana`
- **THEN** a resposta é 400 com `{"erro": "Parâmetros inválidos."}`

#### Scenario: Série por ano
- **WHEN** o navegador pede `/transparencia/atendimentos/dados?agrupamento=ano`
- **THEN** a resposta é o JSON da série anual do painel, com `Cache-Control: public, max-age=600`

### Requirement: Cache e reserva quando o painel cai
O sistema SHALL guardar cada resposta do painel por 15 minutos e uma cópia de reserva por 7 dias, no cache da aplicação. Quando o painel falhar (erro ou mais de 15 segundos), SHALL usar a reserva com o campo `desatualizado: true` e registrar um aviso no log; sem reserva, a consulta SHALL resultar em "sem dados".

#### Scenario: Painel fora do ar por uma hora
- **WHEN** o painel fica fora do ar e a última resposta boa tem 2 horas
- **THEN** a página e os gráficos usam a reserva, marcada como desatualizada

#### Scenario: Deploy apaga a reserva
- **WHEN** o `build.sh` apaga `var/cache` e o painel está fora do ar
- **THEN** não há reserva e a página mostra a mensagem de números em atualização

### Requirement: Sem dados de pacientes e sem download
O sistema MUST exibir só totais agregados por período e por tipo de atendimento. A tabela de histórico MUST NOT oferecer download em CSV (retirado em 07/10/2026).

#### Scenario: Tabela de histórico
- **WHEN** um visitante abre a tabela de histórico da página
- **THEN** ele vê os totais por período e não há botão de download

### Requirement: Erro nos gráficos
Quando a série não carregar no navegador, a página SHALL mostrar "Não foi possível carregar os dados agora. Tente novamente em alguns minutos." no lugar do gráfico, sem quebrar o restante da página.

#### Scenario: Série com 503
- **WHEN** `/transparencia/atendimentos/dados` responde 503
- **THEN** o gráfico dá lugar à mensagem de erro e os totais do topo continuam visíveis
