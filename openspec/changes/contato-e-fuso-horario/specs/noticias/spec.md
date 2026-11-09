# Spec Delta

## MODIFIED Requirements

### Requirement: Data de publicação sem horário
O sistema SHALL gravar a data de publicação escolhida no painel como dia, sem horário (00:00), e com a data de hoje no horário de Brasília como padrão de uma notícia nova. A notícia SHALL ficar pública a partir da meia-noite desse dia no horário de Brasília (America/Sao_Paulo), qualquer que seja o fuso do PHP do servidor.

#### Scenario: Notícia com a data de amanhã
- **WHEN** o jornalista grava uma notícia ativa com a data de amanhã
- **THEN** ela só aparece no site quando passar da meia-noite de amanhã no horário de Brasília

#### Scenario: Noite da véspera
- **WHEN** uma notícia ativa tem a data 10/11/2026 e um visitante abre o site às 21h30 de 09/11/2026 no horário de Brasília
- **THEN** a notícia não aparece na listagem, na home nem na busca, e a página dela responde 404

#### Scenario: Notícia nova criada à noite
- **WHEN** o jornalista abre o formulário de nova notícia às 22h00 de 09/11/2026 no horário de Brasília
- **THEN** a data de publicação vem preenchida com 09/11/2026
