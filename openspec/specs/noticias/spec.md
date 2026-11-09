# noticias Specification

## Purpose
Publica as notícias da Procordis no site (`/noticias`, home, busca) e permite ao Jornalista e ao Administrador cadastrá-las no painel, com categorias, data de publicação e o campo Ativo. A regra de visibilidade pública é única e fica em `NewsRepository::createPublicQueryBuilder()`/`applyPublicCriteria()` e `NewsCategoryRepository::createPublicQueryBuilder()`. Implementado em `NewsController` (público), `Admin/NewsController`, `Admin/NewsCategoryController`, `SearchController` e `HomeController`.

## Requirements
### Requirement: Notícia pública é ativa e já publicada
O sistema SHALL mostrar ao público somente notícias com Ativo ligado e data de publicação menor ou igual ao momento atual. A regra MUST valer para a listagem, a página da notícia, a home, a busca, as notícias recentes, a notícia anterior e a contagem da barra lateral.

#### Scenario: Notícia inativa
- **WHEN** um visitante abre `/noticias/{slug}` de uma notícia com Ativo desligado
- **THEN** a resposta é 404
- **AND** a notícia não aparece na listagem, na home, na busca nem na barra lateral

#### Scenario: Notícia agendada
- **WHEN** a data de publicação da notícia é posterior a agora
- **THEN** a página da notícia responde 404 até a data chegar

### Requirement: Data de publicação sem horário
O sistema SHALL gravar a data de publicação escolhida no painel como dia, sem horário (00:00), e com a data de hoje como padrão de uma notícia nova. A notícia SHALL ficar pública a partir da meia-noite desse dia, no fuso do PHP do servidor.

#### Scenario: Notícia com a data de amanhã
- **WHEN** o jornalista grava uma notícia ativa com a data de amanhã
- **THEN** ela só aparece no site quando o relógio do servidor passar da meia-noite de amanhã

### Requirement: Pré-visualização para quem gerencia notícias
O sistema SHALL mostrar a notícia inativa ou agendada a um Jornalista ou Administrador logado, com o aviso "Pré-visualização: esta notícia está agendada" (ou "inativa") "e não aparece para o público".

#### Scenario: Jornalista abre notícia inativa
- **WHEN** um Jornalista logado abre a URL de uma notícia inativa
- **THEN** a notícia é exibida com o aviso de pré-visualização

### Requirement: Categoria inativa
O sistema SHALL tratar categoria com Ativo desligado como inexistente no site: o filtro `/noticias?category={slug}` responde 404 e a categoria some da barra lateral e das etiquetas da notícia. As notícias dessa categoria SHALL continuar públicas.

#### Scenario: Filtro por categoria inativa
- **WHEN** um visitante abre `/noticias?category=eventos` e a categoria Eventos está inativa
- **THEN** a resposta é 404

#### Scenario: Notícia de categoria inativa
- **WHEN** uma notícia ativa pertence só a uma categoria inativa
- **THEN** a notícia continua na listagem e na sua página, sem a etiqueta da categoria

### Requirement: Listagem e busca
O sistema SHALL listar as notícias públicas da mais recente para a mais antiga. Em `/noticias?q=` a busca SHALL procurar o termo no título, no resumo e no conteúdo; em `/pesquisa?q=` SHALL procurar no título e no conteúdo e trazer no máximo 20 notícias, junto com os serviços encontrados. A home SHALL mostrar as 3 notícias públicas mais recentes.

#### Scenario: Termo só no resumo
- **WHEN** o termo buscado aparece só no resumo de uma notícia pública
- **THEN** ela aparece em `/noticias?q=` e não aparece em `/pesquisa?q=`

### Requirement: Barra lateral da notícia
A página da notícia SHALL mostrar na barra lateral as 5 notícias públicas mais recentes (sem a atual), a notícia pública anterior pela data de publicação e as categorias ativas que têm notícia pública, com a quantidade, em ordem alfabética.

#### Scenario: Categoria sem notícia pública
- **WHEN** uma categoria ativa só tem notícias inativas ou agendadas
- **THEN** ela não aparece na barra lateral

### Requirement: Endereço da notícia
O sistema SHALL usar o slug digitado no painel ou, em branco, gerar um a partir do título sem acentos, em minúsculas e sem as palavras "o, a, os, as, um, uns, uma, umas, menos, ja, agora, tambe, so, talvez". O slug não é único no banco: havendo duas notícias públicas com o mesmo slug, a página SHALL mostrar a de data de publicação mais recente.

#### Scenario: Slug gerado
- **WHEN** a notícia "A Procordis já atende o SUS" é gravada com o slug em branco
- **THEN** o slug fica "procordis-atende-sus"

### Requirement: Ativo pelo painel
O sistema SHALL listar no painel todas as notícias (e categorias) com o filtro Todos, Ativos e Inativos (`?status=ativos|inativos`) e a contagem de cada um, e SHALL permitir ligar e desligar o Ativo direto na listagem por AJAX. A alternância MUST exigir o token CSRF `admin_ajax` no cabeçalho `X-CSRF-Token` e, sem ele, responder 403 sem alterar nada.

#### Scenario: Desligar na listagem
- **WHEN** o jornalista desliga o Ativo de uma notícia na listagem
- **THEN** a notícia fica inativa e o painel mostra "Notícia desativada: ela não aparece mais no site."

### Requirement: Galeria logo depois de criar
Depois de criar uma notícia, o sistema SHALL abrir a edição dela na seção da galeria (`#galeria`), com a mensagem de que já é possível adicionar imagens e vídeos.

#### Scenario: Nova notícia gravada
- **WHEN** o jornalista grava uma notícia nova
- **THEN** ele é levado a `/admin/news/{id}/edit#galeria`
